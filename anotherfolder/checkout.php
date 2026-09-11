<?php

session_start();

require_once "database/config.php";

if (empty($_SESSION["user_id"])) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount)
{
    return "₱" . number_format((float)$amount, 2);
}

$errors = [];
$orderPlaced = false;
$orderNumber = "";
$orderTotal = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        empty($submittedToken) ||
        empty($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $submittedToken)
    ) {
        $errors[] = "Invalid form request. Please refresh the page.";
    }

    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $city = trim($_POST["city"] ?? "");
    $province = trim($_POST["province"] ?? "");
    $postalCode = trim($_POST["postal_code"] ?? "");
    $paymentMethod = $_POST["payment_method"] ?? "";

    if ($firstName === "") {
        $errors[] = "First name is required.";
    }

    if ($lastName === "") {
        $errors[] = "Last name is required.";
    }

    if (
        $email === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($phone === "") {
        $errors[] = "Phone number is required.";
    }

    if ($address === "") {
        $errors[] = "Complete address is required.";
    }

    if ($city === "") {
        $errors[] = "City/Municipality is required.";
    }

    if ($province === "") {
        $errors[] = "Province is required.";
    }

    if ($postalCode === "") {
        $errors[] = "Postal code is required.";
    }

    $allowedPaymentMethods = ["COD", "GCash", "Card"];

    if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
        $errors[] = "Please select a valid payment method.";
    }

    $cartStmt = $pdo->prepare("
        SELECT
            cart_items.id AS cart_id,
            cart_items.product_id,
            cart_items.quantity,
            products.name,
            products.price,
            products.stock
        FROM cart_items
        INNER JOIN products
            ON products.id = cart_items.product_id
        WHERE cart_items.user_id = ?
        ORDER BY cart_items.id ASC
    ");

    $cartStmt->execute([$userId]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        $errors[] = "Your cart is empty.";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $lockedItems = [];
            $subtotal = 0;

            foreach ($cartItems as $item) {
                $productStmt = $pdo->prepare("
                    SELECT
                        id,
                        name,
                        price,
                        stock
                    FROM products
                    WHERE id = ?
                    LIMIT 1
                    FOR UPDATE
                ");

                $productStmt->execute([(int)$item["product_id"]]);
                $product = $productStmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception(
                        "One of the products in your cart is no longer available."
                    );
                }

                $quantity = (int)$item["quantity"];
                $stock = (int)$product["stock"];
                $price = (float)$product["price"];

                if ($stock <= 0) {
                    throw new Exception(
                        $product["name"] . " is out of stock."
                    );
                }

                if ($quantity > $stock) {
                    throw new Exception(
                        "Not enough stock for " .
                        $product["name"] .
                        ". Only " .
                        $stock .
                        " unit(s) available."
                    );
                }

                $itemSubtotal = $price * $quantity;
                $subtotal += $itemSubtotal;

                $lockedItems[] = [
                    "product_id" => (int)$product["id"],
                    "product_name" => $product["name"],
                    "price" => $price,
                    "quantity" => $quantity,
                    "subtotal" => $itemSubtotal
                ];
            }

            $shippingFee = 0;
            $total = $subtotal + $shippingFee;
            $fullName = $firstName . " " . $lastName;

            $orderNumber =
                "FC-" .
                date("Ymd") .
                "-" .
                strtoupper(bin2hex(random_bytes(3)));

            $orderStmt = $pdo->prepare("
                INSERT INTO orders
                (
                    user_id,
                    order_number,
                    full_name,
                    email,
                    phone,
                    address,
                    city,
                    province,
                    postal_code,
                    payment_method,
                    subtotal,
                    shipping_fee,
                    total,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    'Pending'
                )
            ");

            $orderStmt->execute([
                $userId,
                $orderNumber,
                $fullName,
                $email,
                $phone,
                $address,
                $city,
                $province,
                $postalCode,
                $paymentMethod,
                $subtotal,
                $shippingFee,
                $total
            ]);

            $orderId = (int)$pdo->lastInsertId();

            $orderItemStmt = $pdo->prepare("
                INSERT INTO order_items
                (
                    order_id,
                    product_id,
                    product_name,
                    price,
                    quantity,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stockUpdateStmt = $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
                AND stock >= ?
            ");

            foreach ($lockedItems as $item) {
                $orderItemStmt->execute([
                    $orderId,
                    $item["product_id"],
                    $item["product_name"],
                    $item["price"],
                    $item["quantity"],
                    $item["subtotal"]
                ]);

                $stockUpdateStmt->execute([
                    $item["quantity"],
                    $item["product_id"],
                    $item["quantity"]
                ]);

                if ($stockUpdateStmt->rowCount() !== 1) {
                    throw new Exception(
                        "Stock changed while your order was being processed. Please try again."
                    );
                }
            }

            $clearCartStmt = $pdo->prepare("
                DELETE FROM cart_items
                WHERE user_id = ?
            ");

            $clearCartStmt->execute([$userId]);

            $pdo->commit();

            $orderPlaced = true;
            $orderTotal = $total;
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e->getMessage();
        }
    }
}

$cartStmt = $pdo->prepare("
    SELECT
        cart_items.product_id,
        cart_items.quantity,
        products.name,
        products.category,
        products.price,
        products.image,
        products.stock
    FROM cart_items
    INNER JOIN products
        ON products.id = cart_items.product_id
    WHERE cart_items.user_id = ?
    ORDER BY cart_items.id DESC
");

$cartStmt->execute([$userId]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
$cartCount = 0;

foreach ($cartItems as $item) {
    $quantity = (int)$item["quantity"];
    $price = (float)$item["price"];

    $subtotal += $price * $quantity;
    $cartCount += $quantity;
}

if ($orderPlaced) {
    $cartCount = 0;
    $subtotal = 0;
}

$shipping = 0;
$total = $subtotal + $shipping;

foreach ($cartItems as &$item) {
    $image = trim((string)$item["image"]);

    if (
        $image === "" ||
        !file_exists(__DIR__ . "/" . $image)
    ) {
        $image = "assets/products/fc1-cooler.svg";
    }

    $item["display_image"] = $image;
}

unset($item);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FROSTCORE — Checkout</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/checkout.css">
</head>

<body>

<header class="products-header">
    <a href="index.php" class="brand">
        <img src="assets/logo/frostcore_logo.png" alt="FROSTCORE Logo" class="brand-logo">
        <span>FROSTCORE</span>
    </a>

    <nav class="products-nav">
        <a href="index.php">HOME</a>
        <a href="products.php">PRODUCTS</a>
        <a href="about.php">ABOUT US</a>
        <a href="contact.php">CONTACT</a>
    </nav>

    <div class="header-actions">
        <a href="#" class="header-icon logout-button" title="Logout">♙</a>

        <a href="cart.php" class="cart-link">
            🛒
            <span class="cart-number"><?= $cartCount ?></span>
        </a>
    </div>
</header>

<main class="checkout-page">
    <div class="checkout-title">
        <h1>CHECKOUT</h1>
        <p>Review your order and enter your delivery information.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="checkout-errors">
            <?php foreach ($errors as $error): ?>
                <p><?= e($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!$orderPlaced && !empty($cartItems)): ?>

        <form method="POST" action="checkout.php" id="checkoutForm">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">

            <div class="checkout-layout">
                <section>
                    <div class="checkout-section">
                        <h2>CUSTOMER INFORMATION</h2>

                        <div class="checkout-form-grid">
                            <div class="checkout-field">
                                <label for="first_name">FIRST NAME</label>
                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    value="<?= e($_POST["first_name"] ?? "") ?>"
                                    placeholder="Enter your first name"
                                    maxlength="75"
                                    required
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="last_name">LAST NAME</label>
                                <input
                                    type="text"
                                    id="last_name"
                                    name="last_name"
                                    value="<?= e($_POST["last_name"] ?? "") ?>"
                                    placeholder="Enter your last name"
                                    maxlength="75"
                                    required
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="email">EMAIL</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?= e($_POST["email"] ?? "") ?>"
                                    placeholder="Enter your email"
                                    maxlength="190"
                                    required
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="phone">PHONE</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    value="<?= e($_POST["phone"] ?? "") ?>"
                                    placeholder="09XXXXXXXXX"
                                    maxlength="40"
                                    required
                                >
                            </div>

                            <div class="checkout-field full">
                                <label for="address">COMPLETE ADDRESS</label>
                                <textarea
                                    id="address"
                                    name="address"
                                    placeholder="House/Unit, Street, Barangay"
                                    maxlength="255"
                                    required
                                ><?= e($_POST["address"] ?? "") ?></textarea>
                            </div>

                            <div class="checkout-field">
                                <label for="city">CITY / MUNICIPALITY</label>
                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    value="<?= e($_POST["city"] ?? "") ?>"
                                    placeholder="Enter your city"
                                    maxlength="100"
                                    required
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="province">PROVINCE</label>
                                <input
                                    type="text"
                                    id="province"
                                    name="province"
                                    value="<?= e($_POST["province"] ?? "") ?>"
                                    placeholder="Enter your province"
                                    maxlength="100"
                                    required
                                >
                            </div>

                            <div class="checkout-field">
                                <label for="postal_code">POSTAL CODE</label>
                                <input
                                    type="text"
                                    id="postal_code"
                                    name="postal_code"
                                    value="<?= e($_POST["postal_code"] ?? "") ?>"
                                    placeholder="Enter postal code"
                                    maxlength="20"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="checkout-section">
                        <h2>PAYMENT METHOD</h2>

                        <div class="payment-options">
                            <label class="payment-option">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="COD"
                                    <?= ($_POST["payment_method"] ?? "COD") === "COD" ? "checked" : "" ?>
                                    required
                                >
                                <span>Cash on Delivery</span>
                            </label>

                            <label class="payment-option">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="GCash"
                                    <?= ($_POST["payment_method"] ?? "") === "GCash" ? "checked" : "" ?>
                                >
                                <span>GCash</span>
                            </label>

                            <label class="payment-option">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="Card"
                                    <?= ($_POST["payment_method"] ?? "") === "Card" ? "checked" : "" ?>
                                >
                                <span>Card</span>
                            </label>
                        </div>
                    </div>

                    <div class="checkout-section">
                        <h2>YOUR ORDER</h2>

                        <?php foreach ($cartItems as $item): ?>
                            <?php
                            $itemTotal =
                                (float)$item["price"] *
                                (int)$item["quantity"];
                            ?>

                            <div class="checkout-item">
                                <div class="checkout-item-image">
                                    <img
                                        src="<?= e($item["display_image"]) ?>"
                                        alt="<?= e($item["name"]) ?>"
                                    >
                                </div>

                                <div class="checkout-item-info">
                                    <h3><?= e($item["name"]) ?></h3>
                                    <p>
                                        <?= (int)$item["quantity"] ?>
                                        ×
                                        <?= money($item["price"]) ?>
                                    </p>
                                </div>

                                <div class="checkout-item-price">
                                    <?= money($itemTotal) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <aside class="checkout-summary">
                    <h2>ORDER SUMMARY</h2>

                    <div class="summary-row">
                        <span>Items</span>
                        <strong><?= $cartCount ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong><?= money($subtotal) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Shipping</span>
                        <strong>FREE</strong>
                    </div>

                    <div class="summary-total">
                        <span>TOTAL</span>
                        <strong><?= money($total) ?></strong>
                    </div>

                    <button type="submit" class="place-order-button">
                        PLACE ORDER
                    </button>

                    <a href="cart.php" class="back-cart">
                        ← BACK TO CART
                    </a>
                </aside>
            </div>
        </form>

    <?php elseif (!$orderPlaced): ?>

        <div class="checkout-section empty-checkout">
            <h2>YOUR CART IS EMPTY</h2>

            <p class="empty-checkout-message">
                Add products to your cart before checking out.
            </p>

            <a href="products.php" class="checkout-button">
                CONTINUE SHOPPING
            </a>
        </div>

    <?php endif; ?>
</main>

<?php if ($orderPlaced): ?>
    <div class="order-success-overlay">
        <div class="order-success-modal">
            <div class="order-success-icon">✓</div>

            <h2>ORDER CONFIRMED</h2>

            <p>Thank you for your FROSTCORE order.</p>

            <p>
                Order Number:
                <strong><?= e($orderNumber) ?></strong>
            </p>

            <p class="order-success-total">
                <?= money($orderTotal) ?>
            </p>

            <a href="products.php" class="order-success-button">
                CONTINUE SHOPPING
            </a>
        </div>
    </div>
<?php endif; ?>

<?php require_once "includes/logout-popup.php"; ?>

<script src="js/script.js"></script>

</body>
</html>