<?php

session_start();

require_once "database/config.php";


// --------------------------------------------------
// CHECK LOGIN
// --------------------------------------------------

if (empty($_SESSION["user_id"])) {

    header("Location: login.php?redirect=cart.php");
    exit;

}

$userId = (int)$_SESSION["user_id"];


// --------------------------------------------------
// HELPER FUNCTIONS
// --------------------------------------------------

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


// --------------------------------------------------
// ADD PRODUCT TO CART
// --------------------------------------------------

if (
    isset($_GET["add"]) &&
    filter_var($_GET["add"], FILTER_VALIDATE_INT)
) {

    $productId = (int)$_GET["add"];

    // Check that the product exists and get its stock.
    $productStmt = $pdo->prepare("
        SELECT id, stock
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $productStmt->execute([
        $productId
    ]);

    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {

        $stock = (int)$product["stock"];

        if ($stock > 0) {

            // Check if product is already in the user's cart.
            $cartStmt = $pdo->prepare("
                SELECT id, quantity
                FROM cart_items
                WHERE user_id = ?
                AND product_id = ?
                LIMIT 1
            ");

            $cartStmt->execute([
                $userId,
                $productId
            ]);

            $existing = $cartStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {

                $newQuantity = (int)$existing["quantity"] + 1;

                // Never allow cart quantity to exceed stock.
                $newQuantity = min(
                    $newQuantity,
                    $stock
                );

                $updateStmt = $pdo->prepare("
                    UPDATE cart_items
                    SET quantity = ?
                    WHERE id = ?
                ");

                $updateStmt->execute([
                    $newQuantity,
                    $existing["id"]
                ]);

            } else {

                $insertStmt = $pdo->prepare("
                    INSERT INTO cart_items
                    (
                        user_id,
                        product_id,
                        quantity
                    )
                    VALUES (?, ?, 1)
                ");

                $insertStmt->execute([
                    $userId,
                    $productId
                ]);

            }

        }

    }

    // Return to the cart after adding.
    header("Location: cart.php");
    exit;
}


// --------------------------------------------------
// UPDATE CART QUANTITY
// --------------------------------------------------

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["update_cart"])
) {

    $productId = filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );

    $quantity = filter_input(
        INPUT_POST,
        "quantity",
        FILTER_VALIDATE_INT
    );

    if (
        $productId &&
        $quantity &&
        $quantity > 0
    ) {

        // Get the product's current stock.
        $stockStmt = $pdo->prepare("
            SELECT stock
            FROM products
            WHERE id = ?
            LIMIT 1
        ");

        $stockStmt->execute([
            $productId
        ]);

        $stock = $stockStmt->fetchColumn();

        if ($stock !== false) {

            $quantity = min(
                $quantity,
                (int)$stock
            );

            $updateStmt = $pdo->prepare("
                UPDATE cart_items
                SET quantity = ?
                WHERE user_id = ?
                AND product_id = ?
            ");

            $updateStmt->execute([
                $quantity,
                $userId,
                $productId
            ]);

        }

    }

    header("Location: cart.php");
    exit;
}


// --------------------------------------------------
// REMOVE ITEM
// --------------------------------------------------

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["remove_item"])
) {

    $productId = filter_input(
        INPUT_POST,
        "product_id",
        FILTER_VALIDATE_INT
    );

    if ($productId) {

        $deleteStmt = $pdo->prepare("
            DELETE FROM cart_items
            WHERE user_id = ?
            AND product_id = ?
        ");

        $deleteStmt->execute([
            $userId,
            $productId
        ]);

    }

    header("Location: cart.php");
    exit;
}


// --------------------------------------------------
// CLEAR CART
// --------------------------------------------------

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["clear_cart"])
) {

    $clearStmt = $pdo->prepare("
        DELETE FROM cart_items
        WHERE user_id = ?
    ");

    $clearStmt->execute([
        $userId
    ]);

    header("Location: cart.php");
    exit;
}


// --------------------------------------------------
// GET CART ITEMS
// --------------------------------------------------

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

$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);


// --------------------------------------------------
// CALCULATE TOTALS
// --------------------------------------------------

$subtotal = 0;
$cartCount = 0;

foreach ($cartItems as $item) {

    $quantity = (int)$item["quantity"];
    $price = (float)$item["price"];

    $subtotal += $price * $quantity;
    $cartCount += $quantity;
}


// For now, shipping is free.
$shipping = 0;

$total = $subtotal + $shipping;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>FROSTCORE — Cart</title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="product.css">

    <style>

        /* ==================================================
           CART PAGE
        ================================================== */

        .cart-page {
            width: 82%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 70px 0 90px;
        }


        .cart-title {
            margin-bottom: 40px;
        }


        .cart-title h1 {
            margin: 0 0 8px;
            color: var(--text);
            font-family: "Orbitron", sans-serif;
            font-size: 42px;
        }


        .cart-title p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
        }


        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 30px;
        }


        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }


        .cart-item {
            display: grid;
            grid-template-columns: 130px 1fr auto;
            gap: 20px;
            align-items: center;

            padding: 20px;

            background: var(--panel);
            border: 1px solid var(--border);
        }


        .cart-item-image {
            width: 130px;
            height: 120px;

            display: grid;
            place-items: center;

            background: var(--dark-blue);
            border: 1px solid var(--border);
        }


        .cart-item-image img {
            width: 90%;
            height: 90%;
            object-fit: contain;
        }


        .cart-item-info h2 {
            margin: 0 0 6px;

            font-family: "Orbitron", sans-serif;
            font-size: 15px;
        }


        .cart-item-category {
            margin: 0 0 10px;

            color: var(--muted);
            font-size: 10px;
        }


        .cart-item-price {
            color: var(--blue);
            font-family: "Orbitron", sans-serif;
            font-size: 17px;
            font-weight: 700;
        }


        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }


        .quantity-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }


        .quantity-form input {
            width: 55px;
            height: 34px;

            text-align: center;

            background: var(--dark-blue);
            color: var(--text);

            border: 1px solid var(--border);

            font-size: 11px;
        }


        .quantity-form button,
        .remove-button {
            height: 34px;

            padding: 0 10px;

            background: transparent;
            color: var(--muted);

            border: 1px solid var(--border);

            cursor: pointer;
        }


        .quantity-form button:hover,
        .remove-button:hover {
            color: var(--blue);
            border-color: var(--blue);
        }


        .cart-summary {
            height: fit-content;

            padding: 25px;

            background: var(--panel);
            border: 1px solid var(--border);
        }


        .cart-summary h2 {
            margin: 0 0 25px;

            font-family: "Orbitron", sans-serif;
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

            border-top: 1px solid var(--border);

            color: var(--text);
            font-family: "Orbitron", sans-serif;
            font-size: 16px;
        }


        .checkout-button {
            display: block;

            width: 100%;

            margin-top: 25px;
            padding: 13px 15px;

            text-align: center;

            background: var(--blue);
            color: var(--bg);

            border: 1px solid var(--blue);

            font-size: 10px;
            font-weight: 800;

            cursor: pointer;
        }


        .checkout-button:hover {
            filter: brightness(1.08);
        }


        .clear-cart-form {
            margin-top: 10px;
        }


        .clear-cart-button {
            width: 100%;

            padding: 10px;

            background: transparent;
            color: var(--muted);

            border: 1px solid var(--border);

            cursor: pointer;

            font-size: 9px;
        }


        .clear-cart-button:hover {
            color: #ff7f8f;
            border-color: #ff7f8f;
        }


        .empty-cart {
            padding: 70px 30px;

            text-align: center;

            background: var(--panel);
            border: 1px solid var(--border);
        }


        .empty-cart h2 {
            margin-bottom: 10px;

            font-family: "Orbitron", sans-serif;
            font-size: 22px;
        }


        .empty-cart p {
            margin-bottom: 25px;

            color: var(--muted);
            font-size: 11px;
        }


        .continue-shopping {
            display: inline-block;

            padding: 12px 20px;

            background: var(--blue);
            color: var(--bg);

            font-size: 9px;
            font-weight: 800;
        }


        @media (max-width: 800px) {

            .cart-page {
                width: 90%;
                padding: 50px 0 70px;
            }


            .cart-layout {
                grid-template-columns: 1fr;
            }


            .cart-item {
                grid-template-columns: 100px 1fr;
            }


            .cart-item-actions {
                grid-column: 1 / -1;
            }


            .cart-item-image {
                width: 100px;
                height: 100px;
            }

        }


        @media (max-width: 520px) {

            .cart-title h1 {
                font-size: 32px;
            }


            .cart-item {
                grid-template-columns: 1fr;
            }


            .cart-item-image {
                width: 100%;
            }

        }

    </style>

</head>


<body>

<!-- ==================================================
     HEADER
================================================== -->

<header class="products-header">

    <a href="index.php" class="brand">

        <img
            src="assets/frostcore_logo.png"
            alt="FROSTCORE Logo"
            class="brand-logo"
        >

        <span>FROSTCORE</span>

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
        <?php if (!empty($_SESSION["user_id"])): ?>

        <a
            href="#"
            class="header-icon logout-button"
            title="Logout"
        >
            ♙
        </a>
        <?php else: ?>

            <a
                href="login.php?redirect=cart.php"
                class="header-icon"
                title="Login"
            >
                ♙
            </a>
        <?php endif; ?>


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
     CART
================================================== -->

<main class="cart-page">

    <div class="cart-title">

        <h1>
            YOUR CART
        </h1>

        <p>
            <?= $cartCount ?> item<?= $cartCount === 1 ? "" : "s" ?>
            in your FROSTCORE cart.
        </p>

    </div>


    <?php if (empty($cartItems)): ?>

        <div class="empty-cart">

            <h2>
                YOUR CART IS EMPTY
            </h2>

            <p>
                You haven't added any products yet.
            </p>

            <a
                href="products.php"
                class="continue-shopping"
            >
                CONTINUE SHOPPING
            </a>

        </div>

    <?php else: ?>

        <div class="cart-layout">


            <!-- ==============================
                 CART ITEMS
            =============================== -->

            <section class="cart-items">

                <?php foreach ($cartItems as $item): ?>

                    <?php

                    $image = trim(
                        (string)$item["image"]
                    );

                    if (
                        $image === "" ||
                        !file_exists(
                            __DIR__ . "/" . $image
                        )
                    ) {

                        $image =
                            "assets/fc1-cooler.svg";

                    }

                    ?>

                    <article class="cart-item">

                        <div class="cart-item-image">

                            <img
                                src="<?= e($image) ?>"
                                alt="<?= e($item["name"]) ?>"
                            >

                        </div>


                        <div class="cart-item-info">

                            <h2>
                                <?= e($item["name"]) ?>
                            </h2>

                            <p class="cart-item-category">
                                <?= e($item["category"]) ?>
                            </p>

                            <div class="cart-item-price">
                                <?= money($item["price"]) ?>
                            </div>

                        </div>


                        <div class="cart-item-actions">

                            <!-- Update quantity -->
                            <form
                                method="post"
                                class="quantity-form"
                            >

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int)$item["product_id"] ?>"
                                >

                                <input
                                    type="number"
                                    name="quantity"
                                    value="<?= (int)$item["quantity"] ?>"
                                    min="1"
                                    max="<?= (int)$item["stock"] ?>"
                                >

                                <button
                                    type="submit"
                                    name="update_cart"
                                >
                                    UPDATE
                                </button>

                            </form>


                            <!-- Remove -->
                            <form method="post">

                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= (int)$item["product_id"] ?>"
                                >

                                <button
                                    type="submit"
                                    name="remove_item"
                                    class="remove-button"
                                >
                                    REMOVE
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>


                <!-- Clear cart -->
                <form
                    method="post"
                    class="clear-cart-form"
                >

                    <button
                        type="submit"
                        name="clear_cart"
                        class="clear-cart-button"
                    >
                        CLEAR CART
                    </button>

                </form>

            </section>


            <!-- ==============================
                 SUMMARY
            =============================== -->

            <aside class="cart-summary">

                <h2>
                    ORDER SUMMARY
                </h2>


                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <strong>
                        <?= money($subtotal) ?>
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
                        <?= money($total) ?>
                    </strong>

                </div>


                <!-- Checkout will be built later -->
                <a
                    href="checkout.php"
                    class="checkout-button"
                >
                    PROCEED TO CHECKOUT →
                </a>

            </aside>

        </div>

    <?php endif; ?>

</main>
<?php require_once "logout-popup.php"; ?>

<script src="script.js"></script>

</body>
</html>