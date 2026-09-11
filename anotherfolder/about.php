<?php
session_start();
require_once "database/config.php";

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$isLoggedIn = !empty($_SESSION["user_id"]);
$isAdmin = ($_SESSION["role"] ?? "") === "admin";
$cartCount = 0;

if ($isLoggedIn) {
    $cartStmt = $pdo->prepare("
        SELECT COALESCE(SUM(quantity), 0)
        FROM cart_items
        WHERE user_id = ?
    ");
    $cartStmt->execute([(int)$_SESSION["user_id"]]);
    $cartCount = (int)$cartStmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FROSTCORE — About Us</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/about.css">
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

        <a href="products.php">PRODUCTS</a>

        <?php if ($isLoggedIn): ?>
            <a href="my-orders.php">MY ORDERS</a>
        <?php endif; ?>

        <a href="about.php" class="active">ABOUT US</a>

        <a href="contact.php">CONTACT</a>

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

        <?php if ($isLoggedIn): ?>
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
                href="login.php?redirect=about.php"
                class="header-icon"
                title="Login"
            >
                LOGIN
            </a>
        <?php endif; ?>

        <a href="cart.php" class="cart-link">
            🛒
            <span class="cart-number"><?= $cartCount ?></span>
        </a>

    </div>

</header>

<main class="about-page">

    <section class="about-hero">

        <div class="about-eyebrow">
            ABOUT FROSTCORE
        </div>

        <h1>
            COOLING BUILT FOR PLAYERS.
        </h1>

        <p>
            FROSTCORE is a student-built e-commerce concept focused on
            affordable external cooling solutions for gamers. Our goal is
            to make practical phone and CPU cooling products easier to
            discover, compare, and purchase through a simple online experience.
        </p>

    </section>

    <section class="about-grid">

        <article class="about-card">

            <h2>OUR MISSION</h2>

            <p>
                To provide accessible cooling solutions that help gamers
                manage heat during demanding sessions while keeping the
                shopping experience simple, clear, and reliable.
            </p>

        </article>

        <article class="about-card">

            <h2>OUR VISION</h2>

            <p>
                To become a trusted local cooling brand known for practical
                products, gamer-focused design, responsive service, and an
                easy-to-use online store.
            </p>

        </article>

    </section>

    <section class="values-section">

        <h2>WHAT WE VALUE</h2>

        <div class="values-grid">

            <div class="value-card">
                <strong>PERFORMANCE</strong>
                <p>
                    Products should solve a real cooling problem without
                    unnecessary complexity.
                </p>
            </div>

            <div class="value-card">
                <strong>ACCESSIBILITY</strong>
                <p>
                    Cooling should be understandable and affordable for
                    everyday gamers.
                </p>
            </div>

            <div class="value-card">
                <strong>TRUST</strong>
                <p>
                    Clear product information, order tracking, and customer
                    support matter.
                </p>
            </div>

        </div>

    </section>

    <div class="about-cta">

        <p>
            Have a question about a product, order, or the FROSTCORE project?
        </p>

        <a href="contact.php">
            CONTACT US →
        </a>

    </div>

</main>

<footer class="about-footer">

    <div class="about-footer-top">

        <div>
            <h3>FROSTCORE</h3>
            <p>
                High performance cooling solutions built for gamers.
            </p>
        </div>

        <div>
            <h3>SHOP</h3>

            <a href="products.php">All Products</a>
            <a href="products.php?category=Phone+Cooler">Phone Coolers</a>
            <a href="products.php?category=Laptop+Cooler">Laptop Coolers</a>
            <a href="products.php?category=Bundle">Bundles</a>
        </div>

        <div>
            <h3>COMPANY</h3>

            <a href="about.php">About Us</a>
            <a href="contact.php">Contact Us</a>
        </div>

        <div>
            <h3>SUPPORT</h3>

            <a href="contact.php">Contact Support</a>
            <a href="contact.php">FAQs</a>
        </div>

    </div>

    <div class="footer-copy">

        <span>
            © <?= date("Y") ?> FROSTCORE. All rights reserved.
        </span>

        <span>
            STAY COOL. PLAY BETTER.
        </span>

    </div>

</footer>

<?php require_once "includes/logout-popup.php"; ?>

<script src="js/script.js"></script>

</body>
</html>