<?php

// ==================================================
// START SESSION
// ==================================================

session_start();


// ==================================================
// DATABASE CONNECTION
// ==================================================

require_once "database/config.php";


// ==================================================
// CHECK LOGIN
// ==================================================

if (empty($_SESSION["user_id"])) {

    header("Location: login.php?redirect=checkout.php");
    exit;

}

$userId = (int)$_SESSION["user_id"];


// ==================================================
// CSRF TOKEN
// ==================================================

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] =
        bin2hex(random_bytes(32));
}


// ==================================================
// HELPER FUNCTIONS
// ==================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


function money($amount)
{
    return "₱" . number_format(
        (float)$amount,
        2
    );
}


// ==================================================
// DEFAULT VALUES
// ==================================================

$errors = [];

$orderPlaced = false;

$orderNumber = "";

$orderTotal = 0;


// ==================================================
// PROCESS ORDER
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    // --------------------------------------------------
    // CSRF CHECK
    // --------------------------------------------------

    $submittedToken =
        $_POST["csrf_token"] ?? "";

    if (
        empty($submittedToken) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $submittedToken
        )
    ) {

        $errors[] =
            "Invalid form request. Please refresh the page and try again.";

    }


    // --------------------------------------------------
    // GET FORM VALUES
    // --------------------------------------------------

    $firstName =
        trim($_POST["first_name"] ?? "");

    $lastName =
        trim($_POST["last_name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $address =
        trim($_POST["address"] ?? "");

    $city =
        trim($_POST["city"] ?? "");

    $province =
        trim($_POST["province"] ?? "");

    $postalCode =
        trim($_POST["postal_code"] ?? "");

    $paymentMethod =
        $_POST["payment_method"] ?? "";


    // --------------------------------------------------
    // VALIDATE CUSTOMER INFORMATION
    // --------------------------------------------------

    if ($firstName === "") {

        $errors[] =
            "First name is required.";

    }


    if ($lastName === "") {

        $errors[] =
            "Last name is required.";

    }


    if (
        $email === "" ||
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            "Please enter a valid email address.";

    }


    if ($phone === "") {

        $errors[] =
            "Phone number is required.";

    }


    if ($address === "") {

        $errors[] =
            "Complete address is required.";

    }


    if ($city === "") {

        $errors[] =
            "City/Municipality is required.";

    }


    if ($province === "") {

        $errors[] =
            "Province is required.";

    }


    if ($postalCode === "") {

        $errors[] =
            "Postal code is required.";

    }


    // --------------------------------------------------
    // VALIDATE PAYMENT METHOD
    // --------------------------------------------------

    $allowedPaymentMethods = [
        "COD",
        "GCash",
        "Card"
    ];

    if (
        !in_array(
            $paymentMethod,
            $allowedPaymentMethods,
            true
        )
    ) {

        $errors[] =
            "Please select a valid payment method.";

    }


    // --------------------------------------------------
    // GET CURRENT CART
    // --------------------------------------------------

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

    $cartStmt->execute([
        $userId
    ]);

    $cartItems =
        $cartStmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    // --------------------------------------------------
    // CHECK CART
    // --------------------------------------------------

    if (empty($cartItems)) {

        $errors[] =
            "Your cart is empty.";

    }


    // --------------------------------------------------
    // PLACE ORDER
    // --------------------------------------------------

    if (empty($errors)) {

        try {

            // Start database transaction.
            $pdo->beginTransaction();


            // --------------------------------------------------
            // LOCK PRODUCTS AND RE-CHECK STOCK
            // --------------------------------------------------

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

                $productStmt->execute([
                    (int)$item["product_id"]
                ]);

                $product =
                    $productStmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                // Product was removed.
                if (!$product) {

                    throw new Exception(
                        "One of the products in your cart is no longer available."
                    );

                }


                $quantity =
                    (int)$item["quantity"];

                $stock =
                    (int)$product["stock"];

                $price =
                    (float)$product["price"];


                // --------------------------------------------------
                // STOCK CHECK
                // --------------------------------------------------

                if ($stock <= 0) {

                    throw new Exception(
                        $product["name"] .
                        " is out of stock."
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


                $itemSubtotal =
                    $price * $quantity;


                $subtotal +=
                    $itemSubtotal;


                $lockedItems[] = [
                    "product_id" =>
                        (int)$product["id"],

                    "product_name" =>
                        $product["name"],

                    "price" =>
                        $price,

                    "quantity" =>
                        $quantity,

                    "subtotal" =>
                        $itemSubtotal
                ];
            }


            // --------------------------------------------------
            // SHIPPING
            // --------------------------------------------------

            $shippingFee = 0;

            $total =
                $subtotal +
                $shippingFee;


            // --------------------------------------------------
            // CREATE FULL NAME
            // --------------------------------------------------

            $fullName =
                $firstName .
                " " .
                $lastName;


            // --------------------------------------------------
            // GENERATE ORDER NUMBER
            // --------------------------------------------------

            $orderNumber =
                "FC-" .
                date("Ymd") .
                "-" .
                strtoupper(
                    bin2hex(
                        random_bytes(3)
                    )
                );


            // --------------------------------------------------
            // INSERT ORDER
            // --------------------------------------------------

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


            $orderId =
                (int)$pdo->lastInsertId();


            // --------------------------------------------------
            // INSERT ORDER ITEMS
            // --------------------------------------------------

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


            // --------------------------------------------------
            // DECREASE STOCK
            // --------------------------------------------------

            $stockUpdateStmt = $pdo->prepare("
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
                AND stock >= ?
            ");


            foreach ($lockedItems as $item) {


                // Save order item.
                $orderItemStmt->execute([
                    $orderId,
                    $item["product_id"],
                    $item["product_name"],
                    $item["price"],
                    $item["quantity"],
                    $item["subtotal"]
                ]);


                // Decrease inventory.
                $stockUpdateStmt->execute([
                    $item["quantity"],
                    $item["product_id"],
                    $item["quantity"]
                ]);


                // Make sure stock was actually reduced.
                if (
                    $stockUpdateStmt->rowCount() !== 1
                ) {

                    throw new Exception(
                        "Stock changed while your order was being processed. Please try again."
                    );

                }

            }


            // --------------------------------------------------
            // CLEAR USER CART
            // --------------------------------------------------

            $clearCartStmt = $pdo->prepare("
                DELETE FROM cart_items
                WHERE user_id = ?
            ");

            $clearCartStmt->execute([
                $userId
            ]);


            // --------------------------------------------------
            // COMMIT
            // --------------------------------------------------

            $pdo->commit();


            // Mark order as successful.
            $orderPlaced = true;

            $orderTotal =
                $total;


            // Generate a new CSRF token
            // after a successful state-changing request.
            $_SESSION["csrf_token"] =
                bin2hex(random_bytes(32));


        } catch (Throwable $e) {

            // Roll back everything.
            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();

            }


            $errors[] =
                $e->getMessage();

        }

    }

}


// ==================================================
// GET CURRENT CART FOR DISPLAY
// ==================================================

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

$cartStmt->execute([
    $userId
]);

$cartItems =
    $cartStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==================================================
// IF ORDER WAS PLACED
// ==================================================

$subtotal = 0;

$cartCount = 0;


foreach ($cartItems as $item) {

    $quantity =
        (int)$item["quantity"];

    $price =
        (float)$item["price"];

    $subtotal +=
        $price * $quantity;

    $cartCount +=
        $quantity;
}


// If order was successfully placed,
// the cart is empty after processing.
if ($orderPlaced) {

    $cartCount = 0;

    $subtotal = 0;

}


$shipping = 0;

$total =
    $subtotal +
    $shipping;


// ==================================================
// IMAGE FALLBACK
// ==================================================

foreach ($cartItems as &$item) {

    $image =
        trim((string)$item["image"]);


    if (
        $image === "" ||
        !file_exists(
            __DIR__ . "/" . $image
        )
    ) {

        $image =
            "assets/products/fc1-cooler.svg";

    }


    $item["display_image"] =
        $image;

}

unset($item);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        FROSTCORE — Checkout
    </title>


    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/products.css"
    >


    <style>

        /* ==================================================
           CHECKOUT
        ================================================== */

        .checkout-page {

            width: 82%;

            max-width: 1120px;

            margin: 0 auto;

            padding: 65px 0 90px;

        }


        .checkout-title {

            margin-bottom: 40px;

        }


        .checkout-title h1 {

            margin: 0 0 8px;

            color: var(--text);

            font-family:
                "Orbitron",
                sans-serif;

            font-size: 42px;

        }


        .checkout-title p {

            margin: 0;

            color: var(--muted);

            font-size: 12px;

        }


        /* ==================================================
           ERRORS
        ================================================== */

        .checkout-errors {

            margin-bottom: 20px;

            padding: 15px 18px;

            background:
                rgba(255, 95, 95, 0.08);

            border:
                1px solid #ff5f5f;

            color: #ff9a9a;

            font-size: 10px;

        }


        .checkout-errors p {

            margin: 5px 0;

        }


        /* ==================================================
           LAYOUT
        ================================================== */

        .checkout-layout {

            display: grid;

            grid-template-columns:
                1fr 350px;

            gap: 30px;

        }


        .checkout-section {

            margin-bottom: 20px;

            padding: 25px;

            background:
                var(--panel);

            border:
                1px solid var(--border);

        }


        .checkout-section h2 {

            margin: 0 0 22px;

            color: var(--text);

            font-family:
                "Orbitron",
                sans-serif;

            font-size: 17px;

        }


        /* ==================================================
           FORM GRID
        ================================================== */

        .checkout-form-grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;

        }


        .checkout-field {

            display: flex;

            flex-direction: column;

            gap: 7px;

        }


        .checkout-field.full {

            grid-column:
                1 / -1;

        }


        .checkout-field label {

            color: var(--muted);

            font-size: 10px;

            font-weight: 700;

        }


        .checkout-field input,
        .checkout-field textarea {

            width: 100%;

            box-sizing: border-box;

            padding: 11px;

            background:
                var(--dark-blue);

            color: var(--text);

            border:
                1px solid var(--border);

            outline: none;

            font-family:
                Inter,
                Arial,
                sans-serif;

            font-size: 10px;

        }


        .checkout-field input {

            height: 40px;

        }


        .checkout-field textarea {

            min-height: 90px;

            resize: vertical;

        }


        .checkout-field input:focus,
        .checkout-field textarea:focus {

            border-color:
                var(--blue);

        }


        /* ==================================================
           PAYMENT
        ================================================== */

        .payment-options {

            display: grid;

            gap: 10px;

        }


        .payment-option {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 13px;

            background:
                var(--dark-blue);

            border:
                1px solid var(--border);

            color: var(--text);

            font-size: 10px;

            cursor: pointer;

        }


        .payment-option input {

            accent-color:
                var(--blue);

        }


        /* ==================================================
           ORDER ITEMS
        ================================================== */

        .checkout-item {

            display: flex;

            align-items: center;

            gap: 15px;

            padding: 12px 0;

            border-bottom:
                1px solid var(--border);

        }


        .checkout-item:last-child {

            border-bottom:
                none;

        }


        .checkout-item-image {

            width: 70px;

            height: 70px;

            display: grid;

            place-items: center;

            flex-shrink: 0;

            background:
                var(--dark-blue);

            border:
                1px solid var(--border);

        }


        .checkout-item-image img {

            width: 90%;

            height: 90%;

            object-fit: contain;

        }


        .checkout-item-info {

            flex: 1;

        }


        .checkout-item-info h3 {

            margin: 0 0 4px;

            color: var(--text);

            font-family:
                "Orbitron",
                sans-serif;

            font-size: 11px;

        }


        .checkout-item-info p {

            margin: 0;

            color: var(--muted);

            font-size: 9px;

        }


        .checkout-item-price {

            color: var(--blue);

            font-family:
                "Orbitron",
                sans-serif;

            font-size: 11px;

            font-weight: 700;

            white-space: nowrap;

        }


        /* ==================================================
           SUMMARY
        ================================================== */

        .checkout-summary {

            height: fit-content;

            padding: 25px;

            background:
                var(--panel);

            border:
                1px solid var(--border);

            position: sticky;

            top: 95px;

        }


        .checkout-summary h2 {

            margin: 0 0 25px;

            font-family:
                "Orbitron",
                sans-serif;

            font-size: 17px;

        }


        .summary-row {

            display: flex;

            justify-content: space-between;

            margin-bottom: 15px;

            color: var(--muted);

            font-size: 11px;

        }


        .summary-total {

            display: flex;

            justify-content: space-between;

            padding-top: 18px;

            margin-top: 18px;

            border-top:
                1px solid var(--border);

            color: var(--text);

            font-family:
                "Orbitron",
                sans-serif;

            font-size: 17px;

        }


        .place-order-button {

            width: 100%;

            margin-top: 25px;

            padding: 14px;

            background: var(--blue);

            color: var(--bg);

            border:
                1px solid var(--blue);

            font-size: 10px;

            font-weight: 800;

            cursor: pointer;

        }


        .place-order-button:hover {

            filter:
                brightness(1.08);

        }


        .back-cart {

            display: inline-block;

            margin-top: 12px;

            color: var(--muted);

            font-size: 9px;

        }


        .back-cart:hover {

            color: var(--blue);

        }


        /* ==================================================
           SUCCESS POPUP
        ================================================== */

        .order-success-overlay {

            position: fixed;

            inset: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            background:
                rgba(0, 0, 0, 0.80);

            z-index: 99999;

        }


        .order-success-modal {

            width: 100%;

            max-width: 450px;

            padding: 38px 30px;

            background:
                #101820;

            border:
                1px solid var(--blue);

            border-radius: 12px;

            text-align: center;

            box-shadow:
                0 0 35px
                rgba(77, 188, 244, 0.20);

        }


        .order-success-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 60px;

            height: 60px;

            margin: 0 auto 18px;

            border:
                2px solid var(--blue);

            border-radius: 50%;

            color: var(--blue);

            font-size: 28px;

        }


        .order-success-modal h2 {

            margin: 0 0 10px;

            color:
                var(--text);

            font-family:
                "Orbitron",
                sans-serif;

            font-size: 22px;

        }


        .order-success-modal p {

            margin: 8px 0;

            color:
                var(--muted);

            font-size: 12px;

        }


        .order-success-total {

            color:
                var(--blue) !important;

            font-family:
                "Orbitron",
                sans-serif;

            font-size: 18px !important;

            font-weight: 800;

        }


        .order-success-button {

            display: inline-block;

            margin-top: 20px;

            padding: 12px 20px;

            background:
                var(--blue);

            color:
                var(--bg);

            font-size: 10px;

            font-weight: 800;

            text-decoration: none;

        }


        /* ==================================================
           RESPONSIVE
        ================================================== */

        @media (max-width: 800px) {

            .checkout-page {

                width: 90%;

                padding:
                    50px 0 70px;

            }


            .checkout-layout {

                grid-template-columns:
                    1fr;

            }


            .checkout-summary {

                position:
                    static;

            }

        }


        @media (max-width: 520px) {

            .checkout-title h1 {

                font-size: 32px;

            }


            .checkout-form-grid {

                grid-template-columns:
                    1fr;

            }


            .checkout-field.full {

                grid-column:
                    auto;

            }

        }

    </style>

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="products-header">


    <a
        href="index.php"
        class="brand"
    >

        <img
            src="assets/logo/frostcore_logo.png"
            alt="FROSTCORE Logo"
            class="brand-logo"
        >

        <span>
            FROSTCORE
        </span>

    </a>


    <nav class="products-nav">

        <a href="index.php">
            HOME
        </a>

        <a href="products.php">
            PRODUCTS
        </a>

        <a href="about.php">
            ABOUT US
        </a>

        <a href="contact.php">
            CONTACT
        </a>

    </nav>


    <div class="header-actions">

        <a
            href="#"
            class="header-icon logout-button"
            title="Logout"
        >
            ♙
        </a>


        <a
            href="cart.php"
            class="cart-link"
        >

            🛒

            <span class="cart-number">
                <?= $cartCount ?>
            </span>

        </a>

    </div>

</header>



<!-- ==================================================
     CHECKOUT
================================================== -->

<main class="checkout-page">


    <div class="checkout-title">

        <h1>
            CHECKOUT
        </h1>

        <p>
            Review your order and enter your delivery information.
        </p>

    </div>



    <!-- ==================================================
         ERRORS
    ================================================== -->

    <?php if (!empty($errors)): ?>

        <div class="checkout-errors">

            <?php foreach ($errors as $error): ?>

                <p>
                    <?= e($error) ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>



    <?php if (!$orderPlaced && !empty($cartItems)): ?>


        <!-- ==================================================
             ORDER FORM
        ================================================== -->

        <form
            method="POST"
            action="checkout.php"
            id="checkoutForm"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION["csrf_token"]) ?>"
            >


            <div class="checkout-layout">


                <!-- ==================================================
                     LEFT SIDE
                ================================================== -->

                <section>


                    <!-- CUSTOMER INFORMATION -->

                    <div class="checkout-section">

                        <h2>
                            CUSTOMER INFORMATION
                        </h2>


                        <div class="checkout-form-grid">


                            <div class="checkout-field">

                                <label for="first_name">
                                    FIRST NAME
                                </label>

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

                                <label for="last_name">
                                    LAST NAME
                                </label>

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

                                <label for="email">
                                    EMAIL
                                </label>

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

                                <label for="phone">
                                    PHONE
                                </label>

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

                                <label for="address">
                                    COMPLETE ADDRESS
                                </label>

                                <textarea
                                    id="address"
                                    name="address"
                                    placeholder="House/Unit, Street, Barangay"
                                    maxlength="255"
                                    required
                                ><?= e($_POST["address"] ?? "") ?></textarea>

                            </div>


                            <div class="checkout-field">

                                <label for="city">
                                    CITY / MUNICIPALITY
                                </label>

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

                                <label for="province">
                                    PROVINCE
                                </label>

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

                                <label for="postal_code">
                                    POSTAL CODE
                                </label>

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



                    <!-- PAYMENT -->

                    <div class="checkout-section">

                        <h2>
                            PAYMENT METHOD
                        </h2>


                        <div class="payment-options">


                            <label class="payment-option">

                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="COD"
                                    <?= ($_POST["payment_method"] ?? "COD") === "COD" ? "checked" : "" ?>
                                    required
                                >

                                <span>
                                    Cash on Delivery
                                </span>

                            </label>


                            <label class="payment-option">

                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="GCash"
                                    <?= ($_POST["payment_method"] ?? "") === "GCash" ? "checked" : "" ?>
                                >

                                <span>
                                    GCash
                                </span>

                            </label>


                            <label class="payment-option">

                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="Card"
                                    <?= ($_POST["payment_method"] ?? "") === "Card" ? "checked" : "" ?>
                                >

                                <span>
                                    Card
                                </span>

                            </label>

                        </div>

                    </div>



                    <!-- YOUR ORDER -->

                    <div class="checkout-section">

                        <h2>
                            YOUR ORDER
                        </h2>


                        <?php foreach ($cartItems as $item): ?>

                            <?php

                            $itemTotal =
                                (float)$item["price"] *
                                (int)$item["quantity"];

                            ?>


                            <div class="checkout-item">


                                <div class="checkout-item-image">

                                    <img
                                        src="<?= e(
                                            (
                                                !empty($item["image"]) &&
                                                file_exists(
                                                    __DIR__ . "/" . $item["image"]
                                                )
                                            )
                                                ? $item["image"]
                                                : "assets/products/fc1-cooler.svg"
                                        ) ?>"
                                        alt="<?= e($item["name"]) ?>"
                                    >

                                </div>


                                <div class="checkout-item-info">

                                    <h3>

                                        <?= e(
                                            $item["name"]
                                        ) ?>

                                    </h3>


                                    <p>

                                        <?= (int)$item["quantity"] ?>

                                        ×

                                        <?= money(
                                            $item["price"]
                                        ) ?>

                                    </p>

                                </div>


                                <div class="checkout-item-price">

                                    <?= money(
                                        $itemTotal
                                    ) ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </section>



                <!-- ==================================================
                     RIGHT SIDE
                ================================================== -->

                <aside class="checkout-summary">


                    <h2>
                        ORDER SUMMARY
                    </h2>


                    <div class="summary-row">

                        <span>
                            Items
                        </span>

                        <strong>
                            <?= $cartCount ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Subtotal
                        </span>

                        <strong>
                            <?= money(
                                $subtotal
                            ) ?>
                        </strong>

                    </div>


                    <div class="summary-row">

                        <span>
                            Shipping
                        </span>

                        <strong>
                            FREE
                        </strong>

                    </div>


                    <div class="summary-total">

                        <span>
                            TOTAL
                        </span>

                        <strong>
                            <?= money(
                                $total
                            ) ?>
                        </strong>

                    </div>


                    <button
                        type="submit"
                        class="place-order-button"
                    >
                        PLACE ORDER
                    </button>


                    <a
                        href="cart.php"
                        class="back-cart"
                    >
                        ← BACK TO CART
                    </a>

                </aside>

            </div>

        </form>


    <?php elseif (!$orderPlaced): ?>


        <!-- ==================================================
             EMPTY CART
        ================================================== -->

        <div class="checkout-section">

            <h2>
                YOUR CART IS EMPTY
            </h2>


            <p
                style="
                    color: var(--muted);
                    font-size: 11px;
                "
            >
                Add products to your cart before checking out.
            </p>


            <a
                href="products.php"
                class="checkout-button"
            >
                CONTINUE SHOPPING
            </a>

        </div>

    <?php endif; ?>

</main>



<!-- ==================================================
     ORDER SUCCESS
================================================== -->

<?php if ($orderPlaced): ?>

    <div class="order-success-overlay">

        <div class="order-success-modal">


            <div class="order-success-icon">
                ✓
            </div>


            <h2>
                ORDER CONFIRMED
            </h2>


            <p>
                Thank you for your FROSTCORE order.
            </p>


            <p>
                Order Number:
                <strong>
                    <?= e($orderNumber) ?>
                </strong>
            </p>


            <p class="order-success-total">

                <?= money(
                    $orderTotal
                ) ?>

            </p>


            <a
                href="products.php"
                class="order-success-button"
            >
                CONTINUE SHOPPING
            </a>

        </div>

    </div>

<?php endif; ?>



<!-- ==================================================
     SHARED LOGOUT POPUP
================================================== -->

<?php require_once "includes/logout-popup.php"; ?>


<script src="js/script.js"></script>


</body>
</html>