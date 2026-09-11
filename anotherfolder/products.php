<?php
session_start();
require_once "database/config.php";

$loggedIn = !empty($_SESSION["user_id"]);
$isAdmin = ($_SESSION["role"] ?? "") === "admin";

$category = trim($_GET["category"] ?? "");
$search = trim($_GET["search"] ?? "");
$sort = $_GET["sort"] ?? "featured";
$minPrice = isset($_GET["min_price"]) ? (float)$_GET["min_price"] : 0;
$maxPrice = isset($_GET["max_price"]) ? (float)$_GET["max_price"] : 0;
$minRating = isset($_GET["min_rating"]) ? (float)$_GET["min_rating"] : 0;
$availability = $_GET["availability"] ?? "";

$allowedCategories = ["Phone Cooler", "Laptop Cooler", "Bundle"];
$allowedSorts = ["featured", "price_low", "price_high", "rating"];
$allowedAvailability = ["in_stock", "out_of_stock"];
$allowedRatings = [3, 4, 5];

if (!in_array($category, $allowedCategories, true)) {
    $category = "";
}

if (!in_array($sort, $allowedSorts, true)) {
    $sort = "featured";
}

if (!in_array($availability, $allowedAvailability, true)) {
    $availability = "";
}

if ($minRating != 0 && !in_array((int)$minRating, $allowedRatings, true)) {
    $minRating = 0;
}

$minPrice = max(0, $minPrice);
$maxPrice = max(0, $maxPrice);

if ($maxPrice > 0 && $minPrice > $maxPrice) {
    [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
}

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

if ($category !== "") {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR category LIKE ? OR description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($minPrice > 0) {
    $sql .= " AND price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $sql .= " AND price <= ?";
    $params[] = $maxPrice;
}

if ($minRating > 0) {
    $sql .= " AND rating >= ?";
    $params[] = $minRating;
}

if ($availability === "in_stock") {
    $sql .= " AND stock > 0";
}

if ($availability === "out_of_stock") {
    $sql .= " AND stock <= 0";
}

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

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cartCount = 0;

if ($loggedIn) {
    $cartStmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0)
        FROM cart_items
        WHERE user_id = ?
    ");
    $cartStmt->execute([(int)$_SESSION["user_id"]]);
    $cartCount = (int)$cartStmt->fetchColumn();
}

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount)
{
    return "₱" . number_format((float)$amount, 2);
}

function filterUrl($changes = [])
{
    $filters = [
        "category" => $_GET["category"] ?? "",
        "search" => $_GET["search"] ?? "",
        "sort" => $_GET["sort"] ?? "featured",
        "min_price" => $_GET["min_price"] ?? "",
        "max_price" => $_GET["max_price"] ?? "",
        "min_rating" => $_GET["min_rating"] ?? "",
        "availability" => $_GET["availability"] ?? ""
    ];

    foreach ($changes as $key => $value) {
        $filters[$key] = $value;
    }

    $filters = array_filter($filters, fn($value) => $value !== "");

    return empty($filters)
        ? "products.php"
        : "products.php?" . http_build_query($filters);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FROSTCORE — Products</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
</head>

<body>

<header class="products-header">
    <a href="index.php" class="brand">
        <img
            src="assets/logo/frostcore_logo.png"
            alt="FROSTCORE Logo"
            class="brand-logo"
        >
        <span>FROSTCORE</span>
    </a>

    <nav class="products-nav">
        <a href="index.php">HOME</a>

        <a href="products.php" class="active">
            PRODUCTS
        </a>

        <?php if ($loggedIn): ?>
            <a href="my-orders.php">
                MY ORDERS
            </a>
        <?php endif; ?>

        <a href="about.php">
            ABOUT US
        </a>

        <a href="contact.php">
            CONTACT
        </a>
    </nav>

    <div class="header-actions">

        <?php if ($isAdmin): ?>
            <a
                href="admin/dashboard.php"
                class="admin-link"
            >
                ADMIN
            </a>
        <?php endif; ?>

        <?php if ($loggedIn): ?>
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

        <a href="cart.php" class="cart-link">
            🛒
            <span class="cart-number">
                <?= $cartCount ?>
            </span>
        </a>

    </div>
</header>

<section class="products-hero">
    <div class="hero-content">

        <div class="breadcrumbs">
            <a href="index.php">Home</a>
            <span>›</span>
            <span>Products</span>
        </div>

        <h1>
            <span>OUR</span> PRODUCTS
        </h1>

        <p>
            High performance cooling solutions for gamers.
        </p>

        <div class="hero-line"></div>

    </div>
</section>

<main class="products-main">

    <aside class="filter-sidebar">

        <div class="filter-section">
            <h2>CATEGORIES</h2>

            <a
                href="<?= e(filterUrl(["category" => ""])) ?>"
                class="<?= $category === "" ? "selected" : "" ?>"
            >
                All Products
            </a>

            <a
                href="<?= e(filterUrl(["category" => "Phone Cooler"])) ?>"
                class="<?= $category === "Phone Cooler" ? "selected" : "" ?>"
            >
                Phone Coolers
            </a>

            <a
                href="<?= e(filterUrl(["category" => "Laptop Cooler"])) ?>"
                class="<?= $category === "Laptop Cooler" ? "selected" : "" ?>"
            >
                Laptop Coolers
            </a>

            <a
                href="<?= e(filterUrl(["category" => "Bundle"])) ?>"
                class="<?= $category === "Bundle" ? "selected" : "" ?>"
            >
                Bundles
            </a>
        </div>

        <div class="filter-section">
            <h2>PRICE RANGE</h2>

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

                    <span>—</span>

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
            <h2>RATING</h2>

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

                <label class="check-option">
                    <input
                        type="radio"
                        name="min_rating"
                        value="5"
                        <?= $minRating == 5 ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >
                    <span>★★★★★</span>
                </label>

                <label class="check-option">
                    <input
                        type="radio"
                        name="min_rating"
                        value="4"
                        <?= $minRating == 4 ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >
                    <span>★★★★☆ &amp; Up</span>
                </label>

                <label class="check-option">
                    <input
                        type="radio"
                        name="min_rating"
                        value="3"
                        <?= $minRating == 3 ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >
                    <span>★★★☆☆ &amp; Up</span>
                </label>

                <label class="check-option">
                    <input
                        type="radio"
                        name="min_rating"
                        value="0"
                        <?= $minRating == 0 ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >
                    <span>All Ratings</span>
                </label>

            </form>
        </div>

        <div class="filter-section">
            <h2>AVAILABILITY</h2>

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

                <label class="check-option">
                    <input
                        type="radio"
                        name="availability"
                        value="in_stock"
                        <?= $availability === "in_stock" ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >
                    <span>In Stock</span>
                </label>

                <label class="check-option">
                    <input
                        type="radio"
                        name="availability"
                        value="out_of_stock"
                        <?= $availability === "out_of_stock" ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >
                    <span>Out of Stock</span>
                </label>

                <label class="check-option">
                    <input
                        type="radio"
                        name="availability"
                        value=""
                        <?= $availability === "" ? "checked" : "" ?>
                        onchange="this.form.submit()"
                    >
                    <span>All Products</span>
                </label>

            </form>
        </div>

        <a href="products.php" class="clear-filters">
            CLEAR FILTERS
        </a>

    </aside>

    <section class="products-results">

        <div class="products-toolbar">

            <div class="product-count">
                Showing
                <strong><?= count($products) ?></strong>
                products
            </div>

            <form method="get" class="products-tools">

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

        <div class="product-grid">

            <?php if (empty($products)): ?>

                <div class="no-products">

                    <h2>No products found.</h2>

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
                    $image = trim((string)$product["image"]);

                    if (
                        $image === "" ||
                        !file_exists(__DIR__ . "/" . $image)
                    ) {
                        $image = "assets/products/fc1-cooler.svg";
                    }
                    ?>

                    <article class="product-card">

                        <div class="product-image">

                            <span class="category-badge">
                                <?= e(strtoupper($product["category"])) ?>
                            </span>

                            <img
                                src="<?= e($image) ?>"
                                alt="<?= e($product["name"]) ?>"
                            >

                        </div>

                        <div class="product-info">

                            <h2>
                                <?= e($product["name"]) ?>
                            </h2>

                            <p class="product-category">
                                <?= e($product["category"]) ?>
                            </p>

                            <div class="product-status">

                                <span class="rating">
                                    ★ <?= e($product["rating"]) ?>
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

                            <div class="product-price">
                                <?= money($product["price"]) ?>
                            </div>

                            <?php if ((int)$product["stock"] > 0): ?>

                                <small class="available">
                                    <?= (int)$product["stock"] ?> available
                                </small>

                            <?php endif; ?>

                            <div class="product-buttons">

                                <a
                                    href="product-details.php?id=<?= (int)$product["id"] ?>"
                                    class="view-button"
                                >
                                    VIEW
                                </a>

                                <?php if ((int)$product["stock"] <= 0): ?>

                                    <button
                                        type="button"
                                        class="cart-button disabled"
                                        disabled
                                    >
                                        OUT OF STOCK
                                    </button>

                                <?php elseif ($loggedIn): ?>

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

        <div class="pagination">
            <button disabled>‹</button>
            <span class="current">1</span>
            <button disabled>›</button>
        </div>

    </section>

</main>

<div class="cart-popup-overlay" id="cartPopup">

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

<footer class="products-footer">

    <div class="footer-column footer-brand">

        <div class="footer-brand-name">

            <img
                src="assets/logo/frostcore_logo.png"
                alt="FROSTCORE Logo"
            >

            <strong>
                FROSTCORE
            </strong>

        </div>

        <p>
            High performance cooling solutions built for gamers.
        </p>

        <div class="socials">
            <span>f</span>
            <span>◎</span>
            <span>♪</span>
            <span>▶</span>
        </div>

    </div>

    <div class="footer-column">

        <h3>SHOP</h3>

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

        <h3>SUPPORT</h3>

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

        <h3>COMPANY</h3>

        <a href="about.php">
            About Us
        </a>

        <a href="contact.php">
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

        <h3>NEWSLETTER</h3>

        <p>
            Stay updated with our latest products and exclusive offers.
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
        FROSTCORE. All rights reserved.

        <span>
            STAY COOL. PLAY BETTER.
        </span>

    </div>

</footer>

<?php require_once "includes/logout-popup.php"; ?>

<script src="js/script.js"></script>

</body>
</html>