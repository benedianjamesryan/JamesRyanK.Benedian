<?php

// Start the session.
session_start();

// Connect to the FROSTCORE MySQL database.
require_once "database/config.php";


// --------------------------------------------------
// GET FILTER / SEARCH VALUES
// --------------------------------------------------

// Selected category from the URL.
$category = trim($_GET["category"] ?? "");

// Search text from the search box.
$search = trim($_GET["search"] ?? "");

// Selected sorting option.
$sort = $_GET["sort"] ?? "featured";


// --------------------------------------------------
// BUILD PRODUCT QUERY
// --------------------------------------------------

// Start with all products.
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


// Filter by category.
if (
    $category !== "" &&
    in_array($category, [
        "Phone Cooler",
        "Laptop Cooler",
        "Bundle"
    ], true)
) {

    $sql .= " AND category = ?";

    $params[] = $category;
}


// Search by product name, category, or description.
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
// SORT PRODUCTS
// --------------------------------------------------

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
        // Keep the original product order.
        $sql .= " ORDER BY id ASC";
        break;
}


// --------------------------------------------------
// GET PRODUCTS FROM DATABASE
// --------------------------------------------------

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$products = $stmt->fetchAll();


// --------------------------------------------------
// GET CART COUNT
// --------------------------------------------------

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


// --------------------------------------------------
// SMALL HELPER FUNCTIONS
// --------------------------------------------------

// Safely display database values in HTML.
function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


// Format Philippine peso.
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

    <title>FROSTCORE — Products</title>

    <!--
        Separate CSS file for the Products page.
        This keeps your existing landing-page style.css untouched.
    -->
    <link rel="stylesheet" href="product.css">

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="products-header">

    <!-- FROSTCORE logo -->
    <a href="index.php" class="brand">

        <img
            src="assets/frostcore_logo.png"
            alt="FROSTCORE Logo"
            class="brand-logo"
        >

        <span>FROSTCORE</span>

    </a>


    <!-- Navigation -->
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


    <!-- Header actions -->
    <div class="header-actions">

        <!-- Login / account -->
        <?php if (!empty($_SESSION["user_id"])): ?>

            <a href="logout.php" class="header-icon">
                ♙
            </a>

        <?php else: ?>

            <a
                href="login.php?redirect=products.php"
                class="header-icon"
            >
                ♙
            </a>

        <?php endif; ?>


        <!-- Cart -->
        <a href="cart.php" class="cart-link">

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

        <!-- Breadcrumb -->
        <div class="breadcrumbs">

            <a href="index.php">
                Home
            </a>

            <span>›</span>

            <span>
                Products
            </span>

        </div>


        <!-- Main title -->
        <h1>

            <span>OUR</span>

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

        <div class="filter-section">

            <h2>
                CATEGORIES
            </h2>


            <!-- All products -->
            <a
                href="products.php"
                class="<?= $category === "" ? "selected" : "" ?>"
            >
                All Products
            </a>


            <!-- Phone coolers -->
            <a
                href="products.php?category=Phone+Cooler"
                class="<?= $category === "Phone Cooler" ? "selected" : "" ?>"
            >
                Phone Coolers
            </a>


            <!-- Laptop coolers -->
            <a
                href="products.php?category=Laptop+Cooler"
                class="<?= $category === "Laptop Cooler" ? "selected" : "" ?>"
            >
                Laptop Coolers
            </a>


            <!-- Bundles -->
            <a
                href="products.php?category=Bundle"
                class="<?= $category === "Bundle" ? "selected" : "" ?>"
            >
                Bundles
            </a>

        </div>



        <div class="filter-section">

            <h2>
                PRICE RANGE
            </h2>

            <!-- Visual price range for now -->
            <div class="price-line">

                <span class="price-dot"></span>

                <span class="price-track"></span>

                <span class="price-dot"></span>

            </div>


            <div class="price-labels">

                <span>₱0</span>

                <span>₱5,500+</span>

            </div>

        </div>



        <div class="filter-section">

            <h2>
                RATING
            </h2>


            <label class="check-option">

                <input
                    type="checkbox"
                    disabled
                >

                <span>★★★★★</span>

            </label>


            <label class="check-option">

                <input
                    type="checkbox"
                    disabled
                >

                <span>★★★★☆ &amp; Up</span>

            </label>


            <label class="check-option">

                <input
                    type="checkbox"
                    disabled
                >

                <span>★★★☆☆ &amp; Up</span>

            </label>

        </div>



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


        <!-- Clear filters -->
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


        <!-- Toolbar -->
        <div class="products-toolbar">

            <div class="product-count">

                Showing

                <strong>
                    <?= count($products) ?>
                </strong>

                products

            </div>


            <!-- Search and sorting -->
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

                <!-- No results -->
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


            <?php endif; ?>



            <?php foreach ($products as $product): ?>

                <article class="product-card">


                    <!-- Product image -->
                    <div class="product-image">

                        <span class="category-badge">

                            <?= e(
                                strtoupper($product["category"])
                            ) ?>

                        </span>


                        <?php

                        // Use the image saved in the database.
                        $image = trim(
                            (string)$product["image"]
                        );


                        // If the image doesn't exist yet,
                        // temporarily use the existing FROSTCORE cooler.
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


                        <img
                            src="<?= e($image) ?>"
                            alt="<?= e($product["name"]) ?>"
                        >

                    </div>



                    <!-- Product information -->
                    <div class="product-info">

                        <h2>
                            <?= e($product["name"]) ?>
                        </h2>


                        <p class="product-category">
                            <?= e($product["category"]) ?>
                        </p>


                        <!-- Rating + stock -->
                        <div class="product-status">

                            <span class="rating">

                                ★

                                <?= e($product["rating"]) ?>

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


                        <!-- Stock count -->
                        <?php if ((int)$product["stock"] > 0): ?>

                            <small class="available">

                                <?= (int)$product["stock"] ?>

                                available

                            </small>

                        <?php endif; ?>


                        <!-- Buttons -->
                        <div class="product-buttons">

                            <!-- Details -->
                            <a
                                href="product-details.php?id=<?= (int)$product["id"] ?>"
                                class="view-button"
                            >
                                VIEW
                            </a>


                            <!-- Add to cart -->
                            <?php if ((int)$product["stock"] > 0): ?>

                                <a
                                    href="login.php?redirect=products.php"
                                    class="cart-button"
                                >
                                    🛒 ADD TO CART
                                </a>

                            <?php else: ?>

                                <button
                                    class="cart-button disabled"
                                    disabled
                                >
                                    OUT OF STOCK
                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>


        <!-- Pagination visual -->
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
     FOOTER
================================================== -->

<footer class="products-footer">

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
            <span>f</span>
            <span>◎</span>
            <span>♪</span>
            <span>▶</span>
        </div>

    </div>



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


    <div class="footer-copy">

        © <?= date("Y") ?>

        FROSTCORE.
        All rights reserved.

        <span>
            STAY COOL. PLAY BETTER.
        </span>

    </div>

</footer>

</body>
</html>