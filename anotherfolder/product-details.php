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
// GET PRODUCT ID
// ==================================================

$product_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);


// Check if product ID is valid.
if (!$product_id || $product_id <= 0) {

    http_response_code(404);

    die("Product not found.");
}


// ==================================================
// GET PRODUCT FROM DATABASE
// ==================================================

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        category,
        description,
        price,
        image,
        rating,
        stock
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $product_id
]);

$product = $stmt->fetch(PDO::FETCH_ASSOC);


// Product doesn't exist.
if (!$product) {

    http_response_code(404);

    die("Product not found.");
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


function money($value)
{
    return "₱" . number_format(
        (float)$value,
        2
    );
}


// ==================================================
// PRODUCT INFORMATION
// ==================================================

$product_name =
    $product["name"];

$product_category =
    $product["category"];

$product_description =
    $product["description"];

$product_price =
    $product["price"];

$product_rating =
    (float)$product["rating"];

$product_stock =
    (int)$product["stock"];


// ==================================================
// PRODUCT IMAGE
// ==================================================

$product_image =
    trim((string)$product["image"]);


// Use default image if database image
// is empty or does not exist.
if (
    $product_image === "" ||
    !file_exists(
        __DIR__ . "/" . $product_image
    )
) {

    $product_image =
        "assets/fc1-cooler.svg";
}


// ==================================================
// CART COUNT
// ==================================================

$cart_count = 0;

if (!empty($_SESSION["user_id"])) {

    $cart_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0)
        FROM cart_items
        WHERE user_id = ?
    ");

    $cart_stmt->execute([
        $_SESSION["user_id"]
    ]);

    $cart_count =
        (int)$cart_stmt->fetchColumn();
}

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
        <?= e($product_name) ?> — FROSTCORE
    </title>


    <!-- Product Details CSS -->

    <link
        rel="stylesheet"
        href="product-details.css"
    >

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="site-header">


    <!-- BRAND -->

    <div class="brand">

        <a href="index.php">
            FROSTCORE
        </a>

    </div>


    <!-- NAVIGATION -->

    <nav class="main-nav">

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


    <!-- HEADER ACTIONS -->

    <div class="header-actions">


        <!-- LOGIN / LOGOUT -->

        <?php if (!empty($_SESSION["user_id"])): ?>

            <a
                href="#"
                class="login-link logout-button"
                id="logoutButton"
                title="Logout"
            >
                LOGOUT
            </a>

        <?php else: ?>

            <a
                href="login.php?redirect=product-details.php?id=<?= $product_id ?>"
                class="login-link"
                title="Login"
            >
                LOGIN
            </a>

        <?php endif; ?>


        <!-- CART -->

        <a
            href="cart.php"
            class="cart-link"
        >

            CART

            <span class="cart-count">
                <?= $cart_count ?>
            </span>

        </a>

    </div>

</header>



<!-- ==================================================
     PRODUCT DETAILS
================================================== -->

<main class="product-details-page">


    <!-- BACK TO PRODUCTS -->

    <div class="product-back">

        <a href="products.php">
            ← BACK TO PRODUCTS
        </a>

    </div>



    <!-- ==================================================
         PRODUCT AREA
    ================================================== -->

    <section class="product-details">


        <!-- PRODUCT IMAGE -->

        <div class="product-details-image">

            <img
                src="<?= e($product_image) ?>"
                alt="<?= e($product_name) ?>"
            >

        </div>



        <!-- PRODUCT INFORMATION -->

        <div class="product-details-info">


            <!-- CATEGORY -->

            <p class="product-category">

                <?= e($product_category) ?>

            </p>



            <!-- PRODUCT NAME -->

            <h1>

                <?= e($product_name) ?>

            </h1>



            <!-- RATING -->

            <div class="product-rating">

                <span class="stars">

                    <?php

                    $rounded_rating =
                        (int)round($product_rating);

                    for (
                        $i = 1;
                        $i <= 5;
                        $i++
                    ) {

                        if (
                            $i <= $rounded_rating
                        ) {

                            echo "★";

                        } else {

                            echo "☆";

                        }

                    }

                    ?>

                </span>


                <span>

                    <?= number_format(
                        $product_rating,
                        1
                    ) ?>

                    / 5

                </span>

            </div>



            <!-- PRICE -->

            <div class="product-details-price">

                <?= money($product_price) ?>

            </div>



            <!-- STOCK -->

            <div class="product-stock">


                <?php if ($product_stock > 0): ?>

                    <span class="stock-available">

                        IN STOCK

                    </span>


                    <span>

                        <?= $product_stock ?>

                        available

                    </span>


                <?php else: ?>

                    <span class="stock-out">

                        OUT OF STOCK

                    </span>

                <?php endif; ?>

            </div>



            <!-- DESCRIPTION -->

            <div class="product-description">


                <h3>
                    PRODUCT DESCRIPTION
                </h3>


                <p>

                    <?= nl2br(
                        e($product_description)
                    ) ?>

                </p>


            </div>



            <!-- ==================================================
                 QUANTITY + ADD TO CART
            ================================================== -->

            <?php if ($product_stock > 0): ?>

                <form
                    action="cart.php"
                    method="POST"
                    class="add-to-cart-form"
                >


                    <!-- PRODUCT ID -->

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= $product_id ?>"
                    >


                    <!-- QUANTITY -->

                    <div class="quantity-box">

                        <label for="quantity">
                            QUANTITY
                        </label>


                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            value="1"
                            min="1"
                            max="<?= $product_stock ?>"
                        >

                    </div>


                    <!-- ADD TO CART -->

                    <button
                        type="submit"
                        name="add_to_cart"
                        class="add-cart-button"
                    >
                        ADD TO CART
                    </button>

                </form>


            <?php else: ?>

                <button
                    type="button"
                    class="add-cart-button disabled"
                    disabled
                >
                    OUT OF STOCK
                </button>

            <?php endif; ?>

        </div>

    </section>



    <!-- ==================================================
         PRODUCT SPECIFICATIONS
    ================================================== -->

    <section class="product-specifications">


        <h2>
            PRODUCT SPECIFICATIONS
        </h2>


        <div class="spec-grid">


            <!-- CATEGORY -->

            <div class="spec-item">

                <span>
                    CATEGORY
                </span>


                <strong>

                    <?= e(
                        $product_category
                    ) ?>

                </strong>

            </div>



            <!-- PRODUCT -->

            <div class="spec-item">

                <span>
                    PRODUCT
                </span>


                <strong>

                    <?= e(
                        $product_name
                    ) ?>

                </strong>

            </div>



            <!-- RATING -->

            <div class="spec-item">

                <span>
                    RATING
                </span>


                <strong>

                    <?= number_format(
                        $product_rating,
                        1
                    ) ?>

                    / 5

                </strong>

            </div>



            <!-- AVAILABILITY -->

            <div class="spec-item">

                <span>
                    AVAILABILITY
                </span>


                <strong>

                    <?php if ($product_stock > 0): ?>

                        <?= $product_stock ?>

                        UNITS

                    <?php else: ?>

                        OUT OF STOCK

                    <?php endif; ?>

                </strong>

            </div>

        </div>

    </section>



    <!-- ==================================================
         CUSTOMER REVIEWS
    ================================================== -->

    <section class="product-reviews">


        <h2>
            CUSTOMER REVIEWS
        </h2>


        <div class="no-reviews">

            <p>
                Customer reviews will appear here.
            </p>

        </div>

    </section>

</main>



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="site-footer">


    <div class="footer-brand">

        <h2>
            FROSTCORE
        </h2>


        <p>
            Advanced cooling solutions for gamers.
        </p>

    </div>



    <div class="footer-links">


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
            CONTACT US
        </a>

    </div>

</footer>



<!-- ==================================================
     SHARED LOGOUT POPUP
================================================== -->

<?php require_once "logout-popup.php"; ?>



<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script src="script.js"></script>


</body>
</html>