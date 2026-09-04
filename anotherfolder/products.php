<?php

// ==================================================
// START SESSION
// ==================================================

session_start();

// Connect to the FROSTCORE database.
require_once "database/config.php";


// ==================================================
// FILTER / SEARCH VALUES
// ==================================================

$category = trim($_GET["category"] ?? "");

$search = trim($_GET["search"] ?? "");

$sort = $_GET["sort"] ?? "featured";

$minPrice = isset($_GET["min_price"])
    ? (float)$_GET["min_price"]
    : 0;

$maxPrice = isset($_GET["max_price"])
    ? (float)$_GET["max_price"]
    : 5500;

$minRating = isset($_GET["min_rating"])
    ? (float)$_GET["min_rating"]
    : 0;


// ==================================================
// BUILD PRODUCT QUERY
// ==================================================

$sql = "
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
    WHERE 1 = 1
";

$params = [];


// --------------------------------------------------
// CATEGORY FILTER
// --------------------------------------------------

if (
    $category !== "" &&
    in_array(
        $category,
        [
            "Phone Cooler",
            "Laptop Cooler",
            "Bundle"
        ],
        true
    )
) {

    $sql .= " AND category = ?";

    $params[] = $category;
}


// --------------------------------------------------
// SEARCH FILTER
// --------------------------------------------------

if ($search !== "") {

    $sql .= "
        AND (
            name LIKE ?
            OR category LIKE ?
            OR description LIKE ?
        )
    ";

    $searchTerm = "%" . $search . "%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// --------------------------------------------------
// PRICE FILTER
// --------------------------------------------------

if ($minPrice > 0) {

    $sql .= " AND price >= ?";

    $params[] = $minPrice;
}

if ($maxPrice < 5500) {

    $sql .= " AND price <= ?";

    $params[] = $maxPrice;
}


// --------------------------------------------------
// RATING FILTER
// --------------------------------------------------

if ($minRating > 0) {

    $sql .= " AND rating >= ?";

    $params[] = $minRating;
}


// ==================================================
// SORT PRODUCTS
// ==================================================

switch ($sort) {

    case "price_low":

        $sql .= " ORDER BY price ASC";

        break;


    case "price_high":

        $sql .= " ORDER BY price DESC";

        break;


    case "rating":

        $sql .= " ORDER BY rating DESC";

        break;


    default:

        $sql .= " ORDER BY id ASC";

        break;
}


// ==================================================
// GET PRODUCTS
// ==================================================

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);


// ==================================================
// GET CART COUNT
// ==================================================

$cartCount = 0;

if (!empty($_SESSION["user_id"])) {

    $cartStmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0)
        FROM cart_items
        WHERE user_id = ?
    ");

    $cartStmt->execute([
        $_SESSION["user_id"]
    ]);

    $cartCount = (int)$cartStmt->fetchColumn();
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
        FROSTCORE — Products
    </title>


    <!-- Products page stylesheet -->
    <link
        rel="stylesheet"
        href="product.css"
    >

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="products-header">


    <!-- FROSTCORE BRAND -->

    <a
        href="index.php"
        class="brand"
    >

        <img
            src="assets/frostcore_logo.png"
            alt="FROSTCORE Logo"
            class="brand-logo"
        >

        <span>
            FROSTCORE
        </span>

    </a>


    <!-- NAVIGATION -->

    <nav class="products-nav">

        <a href="index.php">
            HOME
        </a>


        <a
            href="products.php"
            class="active"
        >
            PRODUCTS
        </a>


        <a href="index.php#why">
            ABOUT US
        </a>


        <a href="index.php#reviews">
            CONTACT
        </a>

    </nav>


    <!-- HEADER ACTIONS -->

    <div class="header-actions">


        <!-- LOGIN / LOGOUT -->

        <?php if (!empty($_SESSION["user_id"])): ?>

            <a
                href="#"
                class="header-icon logout-button"
                id = "logoutButton"
                title="Logout"
            >
                ♙
            </a>

        <?php else: ?>

            <a
                href="login.php?redirect=products.php"
                class="header-icon"
                title="Login"
            >
                ♙
            </a>

        <?php endif; ?>


        <!-- CART -->

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
     PRODUCTS HERO
================================================== -->

<section class="products-hero">

    <div class="hero-content">


        <!-- Breadcrumb -->

        <div class="breadcrumbs">

            <a href="index.php">
                Home
            </a>


            <span>
                ›
            </span>


            <span>
                Products
            </span>

        </div>


        <!-- Heading -->

        <h1>

            <span>
                OUR
            </span>

            PRODUCTS

        </h1>


        <p>
            High performance cooling solutions for gamers.
        </p>


        <div class="hero-line"></div>

    </div>

</section>



<!-- ==================================================
     MAIN PRODUCTS AREA
================================================== -->

<main class="products-main">


    <!-- ==================================================
         FILTER SIDEBAR
    ================================================== -->

    <aside class="filter-sidebar">


        <!-- CATEGORIES -->

        <div class="filter-section">

            <h2>
                CATEGORIES
            </h2>


            <a
                href="products.php"
                class="<?= $category === "" ? "selected" : "" ?>"
            >
                All Products
            </a>


            <a
                href="products.php?category=Phone+Cooler"
                class="<?= $category === "Phone Cooler" ? "selected" : "" ?>"
            >
                Phone Coolers
            </a>


            <a
                href="products.php?category=Laptop+Cooler"
                class="<?= $category === "Laptop Cooler" ? "selected" : "" ?>"
            >
                Laptop Coolers
            </a>


            <a
                href="products.php?category=Bundle"
                class="<?= $category === "Bundle" ? "selected" : "" ?>"
            >
                Bundles
            </a>

        </div>



        <!-- PRICE RANGE -->

<div class="filter-section">

    <h2>
        PRICE RANGE
    </h2>

    <form method="get" class="price-filter-form">

        <?php if ($category !== ""): ?>
            <input
                type="hidden"
                name="category"
                value="<?= e($category) ?>"
            >
        <?php endif; ?>

        <?php if ($search !== ""): ?>
            <input
                type="hidden"
                name="search"
                value="<?= e($search) ?>"
            >
        <?php endif; ?>

        <input
            type="hidden"
            name="sort"
            value="<?= e($sort) ?>"
        >

        <div class="price-inputs">

            <input
                type="number"
                name="min_price"
                min="0"
                max="5500"
                step="100"
                value="<?= e($minPrice) ?>"
                placeholder="Min"
            >

            <span>—</span>

            <input
                type="number"
                name="max_price"
                min="0"
                max="5500"
                step="100"
                value="<?= e($maxPrice) ?>"
                placeholder="Max"
            >

        </div>

        <button
            type="submit"
            class="apply-filter-button"
        >
            APPLY
        </button>

    </form>

</div>



        <!-- RATING -->

<div class="filter-section">

    <h2>
        RATING
    </h2>

    <form method="get">

        <?php if ($category !== ""): ?>
            <input
                type="hidden"
                name="category"
                value="<?= e($category) ?>"
            >
        <?php endif; ?>

        <?php if ($search !== ""): ?>
            <input
                type="hidden"
                name="search"
                value="<?= e($search) ?>"
            >
        <?php endif; ?>

        <input
            type="hidden"
            name="sort"
            value="<?= e($sort) ?>"
        >

        <input
            type="hidden"
            name="min_price"
            value="<?= e($minPrice) ?>"
        >

        <input
            type="hidden"
            name="max_price"
            value="<?= e($maxPrice) ?>"
        >


        <label class="check-option">

            <input
                type="radio"
                name="min_rating"
                value="5"
                <?= $minRating == 5 ? "checked" : "" ?>
                onchange="this.form.submit()"
            >

            <span>
                ★★★★★
            </span>

        </label>


        <label class="check-option">

            <input
                type="radio"
                name="min_rating"
                value="4"
                <?= $minRating == 4 ? "checked" : "" ?>
                onchange="this.form.submit()"
            >

            <span>
                ★★★★☆ &amp; Up
            </span>

        </label>


        <label class="check-option">

            <input
                type="radio"
                name="min_rating"
                value="3"
                <?= $minRating == 3 ? "checked" : "" ?>
                onchange="this.form.submit()"
            >

            <span>
                ★★★☆☆ &amp; Up
            </span>

        </label>


        <label class="check-option">

            <input
                type="radio"
                name="min_rating"
                value="0"
                <?= $minRating == 0 ? "checked" : "" ?>
                onchange="this.form.submit()"
            >

            <span>
                All Ratings
            </span>

        </label>

    </form>

</div>



        <!-- AVAILABILITY -->

        <div class="filter-section">

            <h2>
                AVAILABILITY
            </h2>


            <label class="check-option">

                <input
                    type="checkbox"
                    checked
                    disabled
                >

                <span>
                    In Stock
                </span>

            </label>


            <label class="check-option">

                <input
                    type="checkbox"
                    disabled
                >

                <span>
                    Out of Stock
                </span>

            </label>

        </div>


        <!-- CLEAR FILTERS -->

        <a
            href="products.php"
            class="clear-filters"
        >
            CLEAR FILTERS
        </a>

    </aside>



    <!-- ==================================================
         PRODUCTS RESULTS
    ================================================== -->

    <section class="products-results">


        <!-- ==================================================
             TOOLBAR
        ================================================== -->

        <div class="products-toolbar">


            <!-- Product count -->

            <div class="product-count">

                Showing

                <strong>
                    <?= count($products) ?>
                </strong>

                products

            </div>


            <!-- Search / Sort -->

            <form
                method="get"
                class="products-tools"
            >


                <?php if ($category !== ""): ?>

                    <input
                        type="hidden"
                        name="category"
                        value="<?= e($category) ?>"
                    >

                <?php endif; ?>


                <input
                    type="search"
                    name="search"
                    placeholder="Search products..."
                    value="<?= e($search) ?>"
                >


                <select name="sort">


                    <option
                        value="featured"
                        <?= $sort === "featured" ? "selected" : "" ?>
                    >
                        Featured
                    </option>


                    <option
                        value="price_low"
                        <?= $sort === "price_low" ? "selected" : "" ?>
                    >
                        Price: Low to High
                    </option>


                    <option
                        value="price_high"
                        <?= $sort === "price_high" ? "selected" : "" ?>
                    >
                        Price: High to Low
                    </option>


                    <option
                        value="rating"
                        <?= $sort === "rating" ? "selected" : "" ?>
                    >
                        Highest Rating
                    </option>

                </select>


                <button
                    type="submit"
                    class="search-button"
                >
                    SEARCH
                </button>

            </form>

        </div>



        <!-- ==================================================
             PRODUCT GRID
        ================================================== -->

        <div class="product-grid">


            <?php if (empty($products)): ?>

                <!-- NO PRODUCTS -->

                <div class="no-products">

                    <h2>
                        No products found.
                    </h2>


                    <p>
                        Try another search or clear the filters.
                    </p>


                    <a
                        href="products.php"
                        class="clear-filters"
                    >
                        SHOW ALL PRODUCTS
                    </a>

                </div>


            <?php else: ?>


                <?php foreach ($products as $product): ?>


                    <?php

                    // Get product image.
                    $image = trim(
                        (string)$product["image"]
                    );


                    // Use fallback image if the
                    // database image doesn't exist.
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


                    <article class="product-card">


                        <!-- ==========================================
                             PRODUCT IMAGE
                        =========================================== -->

                        <div class="product-image">


                            <span class="category-badge">

                                <?= e(
                                    strtoupper(
                                        $product["category"]
                                    )
                                ) ?>

                            </span>


                            <img
                                src="<?= e($image) ?>"
                                alt="<?= e($product["name"]) ?>"
                            >

                        </div>



                        <!-- ==========================================
                             PRODUCT INFORMATION
                        =========================================== -->

                        <div class="product-info">


                            <!-- Product name -->

                            <h2>

                                <?= e(
                                    $product["name"]
                                ) ?>

                            </h2>


                            <!-- Category -->

                            <p class="product-category">

                                <?= e(
                                    $product["category"]
                                ) ?>

                            </p>



                            <!-- Rating / stock -->

                            <div class="product-status">


                                <span class="rating">

                                    ★

                                    <?= e(
                                        $product["rating"]
                                    ) ?>

                                </span>


                                <?php if ((int)$product["stock"] > 0): ?>

                                    <span class="stock">

                                        ● In Stock

                                    </span>

                                <?php else: ?>

                                    <span class="out-stock">

                                        ● Out of Stock

                                    </span>

                                <?php endif; ?>

                            </div>



                            <!-- Price -->

                            <div class="product-price">

                                <?= money(
                                    $product["price"]
                                ) ?>

                            </div>



                            <!-- Available stock -->

                            <?php if ((int)$product["stock"] > 0): ?>

                                <small class="available">

                                    <?= (int)$product["stock"] ?>

                                    available

                                </small>

                            <?php endif; ?>



                            <!-- ==========================================
                                 BUTTONS
                            =========================================== -->

                            <div class="product-buttons">


                                <!-- VIEW DETAILS -->

                                <a
                                    href="product-details.php?id=<?= (int)$product["id"] ?>"
                                    class="view-button"
                                >
                                    VIEW
                                </a>



                                <!-- ======================================
                                     ADD TO CART
                                ======================================= -->

                                <?php if ((int)$product["stock"] <= 0): ?>

                                    <!-- OUT OF STOCK -->

                                    <button
                                        type="button"
                                        class="cart-button disabled"
                                        disabled
                                    >
                                        OUT OF STOCK
                                    </button>


                                <?php elseif (!empty($_SESSION["user_id"])): ?>

                                    <!-- LOGGED IN -->

                                    <button
                                        type="button"
                                        class="cart-button add-cart-button"
                                        data-product-id="<?= (int)$product["id"] ?>"
                                    >
                                        🛒 ADD TO CART
                                    </button>


                                <?php else: ?>

                                    <!-- NOT LOGGED IN -->

                                    <a
                                        href="login.php?redirect=products.php"
                                        class="cart-button"
                                    >
                                        🛒 ADD TO CART
                                    </a>

                                <?php endif; ?>


                            </div>

                        </div>

                    </article>


                <?php endforeach; ?>


            <?php endif; ?>

        </div>



        <!-- ==================================================
             PAGINATION
        ================================================== -->

        <div class="pagination">

            <button disabled>
                ‹
            </button>


            <span class="current">
                1
            </span>


            <button disabled>
                ›
            </button>

        </div>

    </section>

</main>



<!-- ==================================================
     ADD TO CART POPUP
================================================== -->

<div
    class="cart-popup-overlay"
    id="cartPopup"
>


    <div class="cart-popup">


        <!-- Close button -->

        <button
            type="button"
            class="cart-popup-close"
            id="cartPopupClose"
        >
            ×
        </button>


        <!-- Success icon -->

        <div class="cart-popup-icon">
            ✓
        </div>


        <!-- Popup title -->

        <h2>
            ADDED TO CART
        </h2>


        <!-- Popup message -->

        <p id="cartPopupMessage">
            Product added to your cart.
        </p>


        <!-- Popup buttons -->

        <div class="cart-popup-actions">


            <button
                type="button"
                class="cart-continue"
                id="cartContinue"
            >
                CONTINUE SHOPPING
            </button>


            <a
                href="cart.php"
                class="cart-view"
            >
                VIEW CART
            </a>

        </div>

    </div>

</div>



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="products-footer">


    <!-- BRAND -->

    <div class="footer-column footer-brand">

        <div class="footer-brand-name">

            <img
                src="assets/frostcore_logo.png"
                alt="FROSTCORE Logo"
            >

            <strong>
                FROSTCORE
            </strong>

        </div>


        <p>
            High performance cooling solutions
            built for gamers.
        </p>


        <div class="socials">

            <span>
                f
            </span>

            <span>
                ◎
            </span>

            <span>
                ♪
            </span>

            <span>
                ▶
            </span>

        </div>

    </div>



    <!-- SHOP -->

    <div class="footer-column">

        <h3>
            SHOP
        </h3>


        <a href="products.php">
            All Products
        </a>


        <a href="products.php?category=Phone+Cooler">
            Phone Coolers
        </a>


        <a href="products.php?category=Laptop+Cooler">
            Laptop Coolers
        </a>


        <a href="products.php?category=Bundle">
            Bundles
        </a>

    </div>



    <!-- SUPPORT -->

    <div class="footer-column">

        <h3>
            SUPPORT
        </h3>


        <a href="#">
            Warranty
        </a>


        <a href="#">
            Shipping &amp; Delivery
        </a>


        <a href="#">
            Returns
        </a>


        <a href="#">
            FAQs
        </a>

    </div>



    <!-- COMPANY -->

    <div class="footer-column">

        <h3>
            COMPANY
        </h3>


        <a href="index.php#why">
            About Us
        </a>


        <a href="index.php#reviews">
            Contact Us
        </a>


        <a href="#">
            Privacy Policy
        </a>


        <a href="#">
            Terms of Service
        </a>

    </div>



    <!-- NEWSLETTER -->

    <div class="footer-column">

        <h3>
            NEWSLETTER
        </h3>


        <p>
            Stay updated with our latest products
            and exclusive offers.
        </p>


        <div class="newsletter">

            <input
                type="email"
                placeholder="Enter your email"
            >


            <button type="button">
                ➤
            </button>

        </div>

    </div>



    <!-- COPYRIGHT -->

    <div class="footer-copy">

        © <?= date("Y") ?>

        FROSTCORE.
        All rights reserved.


        <span>
            STAY COOL. PLAY BETTER.
        </span>

    </div>

</footer>



<!-- ==================================================
     JAVASCRIPT
================================================== -->
<?php require_once "logout-popup.php"; ?>
<script src="script.js"></script>


</body>

</html>