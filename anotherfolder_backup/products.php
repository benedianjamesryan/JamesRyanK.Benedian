<?php

// ==================================================
// START SESSION
// ==================================================

session_start();


// ==================================================
// DATABASE
// ==================================================

require_once "database/config.php";


// ==================================================
// GET FILTER VALUES
// ==================================================

$category = trim($_GET["category"] ?? "");

$search = trim($_GET["search"] ?? "");

$sort = $_GET["sort"] ?? "featured";

$minPrice = isset($_GET["min_price"])
    ? (float)$_GET["min_price"]
    : 0;

$maxPrice = isset($_GET["max_price"])
    ? (float)$_GET["max_price"]
    : 0;

$minRating = isset($_GET["min_rating"])
    ? (float)$_GET["min_rating"]
    : 0;

$availability = $_GET["availability"] ?? "";


// ==================================================
// VALIDATE FILTER VALUES
// ==================================================

$allowedCategories = [
    "Phone Cooler",
    "Laptop Cooler",
    "Bundle"
];

if (!in_array($category, $allowedCategories, true)) {

    $category = "";

}


$allowedSorts = [
    "featured",
    "price_low",
    "price_high",
    "rating"
];

if (!in_array($sort, $allowedSorts, true)) {

    $sort = "featured";

}


$allowedAvailability = [
    "in_stock",
    "out_of_stock"
];

if (!in_array($availability, $allowedAvailability, true)) {

    $availability = "";

}


$allowedRatings = [
    3,
    4,
    5
];

if (
    $minRating != 0 &&
    !in_array((int)$minRating, $allowedRatings, true)
) {

    $minRating = 0;

}


// Make sure price values are valid.

if ($minPrice < 0) {

    $minPrice = 0;

}

if ($maxPrice < 0) {

    $maxPrice = 0;

}


// Swap values if entered backwards.

if (
    $maxPrice > 0 &&
    $minPrice > $maxPrice
) {

    $temp = $minPrice;

    $minPrice = $maxPrice;

    $maxPrice = $temp;

}


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


// ==================================================
// CATEGORY FILTER
// ==================================================

if ($category !== "") {

    $sql .= "
        AND category = ?
    ";

    $params[] = $category;

}


// ==================================================
// SEARCH FILTER
// ==================================================

if ($search !== "") {

    $sql .= "

        AND (

            name LIKE ?

            OR category LIKE ?

            OR description LIKE ?

        )

    ";

    $searchTerm =
        "%" . $search . "%";

    $params[] = $searchTerm;

    $params[] = $searchTerm;

    $params[] = $searchTerm;

}


// ==================================================
// PRICE FILTER
// ==================================================

if ($minPrice > 0) {

    $sql .= "
        AND price >= ?
    ";

    $params[] = $minPrice;

}

if ($maxPrice > 0) {

    $sql .= "
        AND price <= ?
    ";

    $params[] = $maxPrice;

}


// ==================================================
// RATING FILTER
// ==================================================

if ($minRating > 0) {

    $sql .= "
        AND rating >= ?
    ";

    $params[] = $minRating;

}


// ==================================================
// AVAILABILITY FILTER
// ==================================================

if ($availability === "in_stock") {

    $sql .= "
        AND stock > 0
    ";

}

if ($availability === "out_of_stock") {

    $sql .= "
        AND stock <= 0
    ";

}


// ==================================================
// SORT
// ==================================================

switch ($sort) {

    case "price_low":

        $sql .= "
            ORDER BY price ASC
        ";

        break;


    case "price_high":

        $sql .= "
            ORDER BY price DESC
        ";

        break;


    case "rating":

        $sql .= "
            ORDER BY rating DESC
        ";

        break;


    default:

        $sql .= "
            ORDER BY id ASC
        ";

        break;

}


// ==================================================
// GET PRODUCTS
// ==================================================

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$products =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==================================================
// CART COUNT
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

    $cartCount =
        (int)$cartStmt->fetchColumn();

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
// BUILD FILTER URL
// ==================================================

function filterUrl($changes = [])
{

    $filters = [

        "category" =>
            $_GET["category"] ?? "",

        "search" =>
            $_GET["search"] ?? "",

        "sort" =>
            $_GET["sort"] ?? "featured",

        "min_price" =>
            $_GET["min_price"] ?? "",

        "max_price" =>
            $_GET["max_price"] ?? "",

        "min_rating" =>
            $_GET["min_rating"] ?? "",

        "availability" =>
            $_GET["availability"] ?? ""

    ];


    foreach ($changes as $key => $value) {

        $filters[$key] = $value;

    }


    // Remove empty values.

    $filters = array_filter(
        $filters,
        function ($value) {

            return $value !== "";

        }
    );


    if (empty($filters)) {

        return "products.php";

    }


    return "products.php?" .
        http_build_query($filters);

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


    <!-- BRAND -->

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


        <?php if (!empty($_SESSION["user_id"])): ?>

            <a href="my-orders.php">
                MY ORDERS
            </a>

        <?php endif; ?>


        <a href="/webprog2try/anotherfolder/about.php">
            ABOUT US
        </a>


        <a href="/webprog2try/anotherfolder/contact.php">
            CONTACT
        </a>

    </nav>


    <!-- HEADER ACTIONS -->

    <div class="header-actions">


        <?php if (!empty($_SESSION["user_id"])): ?>

            <a
                href="#"
                class="header-icon logout-button"
                id="logoutButton"
                title="Logout"
            >
                LOGOUT
            </a>

        <?php else: ?>

            <a
                href="login.php?redirect=products.php"
                class="header-icon"
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

            🛒

            <span class="cart-number">

                <?= $cartCount ?>

            </span>

        </a>

    </div>

</header>



<!-- ==================================================
     HERO
================================================== -->

<section class="products-hero">

    <div class="hero-content">


        <!-- BREADCRUMBS -->

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


        <!-- TITLE -->

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


        <!-- ==================================================
             CATEGORY
        ================================================== -->

        <div class="filter-section">

            <h2>
                CATEGORIES
            </h2>


            <a
                href="<?= e(
                    filterUrl([
                        "category" => ""
                    ])
                ) ?>"
                class="<?= $category === "" ? "selected" : "" ?>"
            >
                All Products
            </a>


            <a
                href="<?= e(
                    filterUrl([
                        "category" => "Phone Cooler"
                    ])
                ) ?>"
                class="<?= $category === "Phone Cooler" ? "selected" : "" ?>"
            >
                Phone Coolers
            </a>


            <a
                href="<?= e(
                    filterUrl([
                        "category" => "Laptop Cooler"
                    ])
                ) ?>"
                class="<?= $category === "Laptop Cooler" ? "selected" : "" ?>"
            >
                Laptop Coolers
            </a>


            <a
                href="<?= e(
                    filterUrl([
                        "category" => "Bundle"
                    ])
                ) ?>"
                class="<?= $category === "Bundle" ? "selected" : "" ?>"
            >
                Bundles
            </a>

        </div>



        <!-- ==================================================
             PRICE RANGE
        ================================================== -->

        <div class="filter-section">

            <h2>
                PRICE RANGE
            </h2>


            <form
                method="get"
                class="price-filter-form"
            >


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


                <?php if ($minRating > 0): ?>

                    <input
                        type="hidden"
                        name="min_rating"
                        value="<?= e($minRating) ?>"
                    >

                <?php endif; ?>


                <?php if ($availability !== ""): ?>

                    <input
                        type="hidden"
                        name="availability"
                        value="<?= e($availability) ?>"
                    >

                <?php endif; ?>


                <div class="price-inputs">

                    <input
                        type="number"
                        name="min_price"
                        min="0"
                        max="5500"
                        step="100"
                        value="<?= $minPrice > 0 ? e($minPrice) : "" ?>"
                        placeholder="Min"
                    >


                    <span>
                        —
                    </span>


                    <input
                        type="number"
                        name="max_price"
                        min="0"
                        max="5500"
                        step="100"
                        value="<?= $maxPrice > 0 ? e($maxPrice) : "" ?>"
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


            <!-- VISUAL RANGE -->

            <div class="price-line">

                <span class="price-dot"></span>

                <span class="price-track"></span>

                <span class="price-dot"></span>

            </div>


            <div class="price-labels">

                <span>
                    ₱0
                </span>


                <span>
                    ₱5,500+
                </span>

            </div>

        </div>



        <!-- ==================================================
             RATING
        ================================================== -->

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
                    value="<?= $minPrice > 0 ? e($minPrice) : "" ?>"
                >


                <input
                    type="hidden"
                    name="max_price"
                    value="<?= $maxPrice > 0 ? e($maxPrice) : "" ?>"
                >


                <?php if ($availability !== ""): ?>

                    <input
                        type="hidden"
                        name="availability"
                        value="<?= e($availability) ?>"
                    >

                <?php endif; ?>


                <!-- 5 STARS -->

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


                <!-- 4 STARS -->

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


                <!-- 3 STARS -->

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


                <!-- ALL RATINGS -->

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



        <!-- ==================================================
             AVAILABILITY
        ================================================== -->

        <div class="filter-section">

            <h2>
                AVAILABILITY
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
                    value="<?= $minPrice > 0 ? e($minPrice) : "" ?>"
                >


                <input
                    type="hidden"
                    name="max_price"
                    value="<?= $maxPrice > 0 ? e($maxPrice) : "" ?>"
                >


                <?php if ($minRating > 0): ?>

                    <input
                        type="hidden"
                        name="min_rating"
                        value="<?= e($minRating) ?>"
                    >

                <?php endif; ?>


                <!-- IN STOCK -->

                <label class="check-option">

                    <input
                        type="radio"
                        name="availability"
                        value="in_stock"
                        <?= $availability === "in_stock" ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >

                    <span>
                        In Stock
                    </span>

                </label>


                <!-- OUT OF STOCK -->

                <label class="check-option">

                    <input
                        type="radio"
                        name="availability"
                        value="out_of_stock"
                        <?= $availability === "out_of_stock" ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >

                    <span>
                        Out of Stock
                    </span>

                </label>


                <!-- ALL PRODUCTS -->

                <label class="check-option">

                    <input
                        type="radio"
                        name="availability"
                        value=""
                        <?= $availability === "" ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >

                    <span>
                        All Products
                    </span>

                </label>


            </form>

        </div>



        <!-- ==================================================
             CLEAR FILTERS
        ================================================== -->

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


            <div class="product-count">

                Showing

                <strong>
                    <?= count($products) ?>
                </strong>

                products

            </div>


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


                <?php if ($minPrice > 0): ?>

                    <input
                        type="hidden"
                        name="min_price"
                        value="<?= e($minPrice) ?>"
                    >

                <?php endif; ?>


                <?php if ($maxPrice > 0): ?>

                    <input
                        type="hidden"
                        name="max_price"
                        value="<?= e($maxPrice) ?>"
                    >

                <?php endif; ?>


                <?php if ($minRating > 0): ?>

                    <input
                        type="hidden"
                        name="min_rating"
                        value="<?= e($minRating) ?>"
                    >

                <?php endif; ?>


                <?php if ($availability !== ""): ?>

                    <input
                        type="hidden"
                        name="availability"
                        value="<?= e($availability) ?>"
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


                <div class="no-products">

                    <h2>
                        No products found.
                    </h2>


                    <p>
                        Try another filter or clear the filters.
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

                    // --------------------------------------------------
                    // PRODUCT IMAGE
                    // --------------------------------------------------

                    $image =
                        trim(
                            (string)$product["image"]
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


                    <article class="product-card">


                        <!-- PRODUCT IMAGE -->

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



                        <!-- PRODUCT INFORMATION -->

                        <div class="product-info">


                            <h2>

                                <?= e(
                                    $product["name"]
                                ) ?>

                            </h2>


                            <p class="product-category">

                                <?= e(
                                    $product["category"]
                                ) ?>

                            </p>


                            <div class="product-status">


                                <span class="rating">

                                    ★

                                    <?= e(
                                        $product["rating"]
                                    ) ?>

                                </span>


                                <?php if (
                                    (int)$product["stock"] > 0
                                ): ?>

                                    <span class="stock">

                                        ● In Stock

                                    </span>

                                <?php else: ?>

                                    <span class="out-stock">

                                        ● Out of Stock

                                    </span>

                                <?php endif; ?>


                            </div>


                            <div class="product-price">

                                <?= money(
                                    $product["price"]
                                ) ?>

                            </div>


                            <?php if (
                                (int)$product["stock"] > 0
                            ): ?>

                                <small class="available">

                                    <?= (int)$product["stock"] ?>

                                    available

                                </small>

                            <?php endif; ?>


                            <!-- BUTTONS -->

                            <div class="product-buttons">


                                <!-- VIEW -->

                                <a
                                    href="product-details.php?id=<?= (int)$product["id"] ?>"
                                    class="view-button"
                                >
                                    VIEW
                                </a>


                                <!-- ADD TO CART -->

                                <?php if (
                                    (int)$product["stock"] <= 0
                                ): ?>


                                    <button
                                        type="button"
                                        class="cart-button disabled"
                                        disabled
                                    >
                                        OUT OF STOCK
                                    </button>


                                <?php elseif (
                                    !empty($_SESSION["user_id"])
                                ): ?>


                                    <button
                                        type="button"
                                        class="cart-button add-cart-button"
                                        data-product-id="<?= (int)$product["id"] ?>"
                                    >
                                        🛒 ADD TO CART
                                    </button>


                                <?php else: ?>


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


        <button
            type="button"
            class="cart-popup-close"
            id="cartPopupClose"
        >
            ×
        </button>


        <div class="cart-popup-icon">
            ✓
        </div>


        <h2>
            ADDED TO CART
        </h2>


        <p id="cartPopupMessage">
            Product added to your cart.
        </p>


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
     LOGOUT POPUP
================================================== -->

<?php require_once "logout-popup.php"; ?>



<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script src="script.js"></script>


</body>

</html>