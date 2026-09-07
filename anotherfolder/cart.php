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

    <title>
        FROSTCORE — Cart
    </title>


    <!-- GLOBAL CSS -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <!-- SHARED PRODUCT / HEADER CSS -->

    <link
        rel="stylesheet"
        href="css/products.css"
    >


    <!-- CART PAGE CSS -->

    <link
        rel="stylesheet"
        href="css/cart.css"
    >

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
            <?= $cartCount ?>
            item<?= $cartCount === 1 ? "" : "s" ?>
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
                            "assets/products/fc1-cooler.svg";

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

                                <?= e(
                                    $item["category"]
                                ) ?>

                            </p>

                            <div class="cart-item-price">

                                <?= money(
                                    $item["price"]
                                ) ?>

                            </div>

                        </div>


                        <div class="cart-item-actions">


                            <!-- UPDATE QUANTITY -->

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


                            <!-- REMOVE -->

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


                <!-- CLEAR CART -->

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



<!-- ==================================================
     LOGOUT POPUP
================================================== -->

<?php require_once "includes/logout-popup.php"; ?>


<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script src="js/script.js"></script>


</body>

</html>