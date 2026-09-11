<?php
session_start();
require_once "database/config.php";

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$contactEmail = "frostcorecoolers@gmail.com";
$contactPhone = "09123456778";
$facebookUrl = "https://www.facebook.com/FrostCoreCoolers";
$instagramUrl = "https://www.instagram.com/FrostCoreCoolers";
$tiktokUrl = "https://www.tiktok.com/@FrostCoreCoolers";
$location = "Bacong, Negros Oriental";
$supportHours = "Monday–Saturday, 9:00 AM–6:00 PM";

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

$formName = trim($_POST["name"] ?? "");
$formEmail = trim($_POST["email"] ?? "");
$subject = trim($_POST["subject"] ?? "");
$message = trim($_POST["message"] ?? "");

$formError = "";
$mailToUrl = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        $formName === "" ||
        $formEmail === "" ||
        $subject === "" ||
        $message === ""
    ) {
        $formError = "Please complete all fields.";
    } elseif (!filter_var($formEmail, FILTER_VALIDATE_EMAIL)) {
        $formError = "Please enter a valid email address.";
    } elseif (strlen($formName) > 120) {
        $formError = "Your name is too long.";
    } elseif (strlen($subject) > 180) {
        $formError = "Your subject is too long.";
    } elseif (strlen($message) > 3000) {
        $formError = "Your message cannot exceed 3000 characters.";
    } else {
        $mailBody =
            "Hello FROSTCORE Team,\n\n" .
            "Name: " . $formName . "\n" .
            "Email: " . $formEmail . "\n\n" .
            "Message:\n" . $message . "\n\n" .
            "Sent from the FROSTCORE website.";

        $mailToUrl =
            "mailto:" . $contactEmail .
            "?subject=" . rawurlencode($subject) .
            "&body=" . rawurlencode($mailBody);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FROSTCORE — Contact Us</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/contact.css">
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

        <a href="index.php">
            HOME
        </a>

        <a href="products.php">
            PRODUCTS
        </a>

        <?php if ($isLoggedIn): ?>
            <a href="my-orders.php">
                MY ORDERS
            </a>
        <?php endif; ?>

        <a href="about.php">
            ABOUT US
        </a>

        <a href="contact.php" class="active">
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
                href="login.php?redirect=contact.php"
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

<main class="contact-page">

    <section class="contact-hero">

        <div class="contact-eyebrow">
            GET IN TOUCH
        </div>

        <h1>
            CONTACT US.
        </h1>

        <p>
            Have a question about a product, your order, or FROSTCORE?
            Reach out through our contact information or send us a message below.
        </p>

    </section>

    <section class="contact-grid">

        <div class="panel">

            <h2>
                REACH FROSTCORE
            </h2>

            <div class="contact-card">

                <span>
                    EMAIL
                </span>

                <a href="mailto:<?= e($contactEmail) ?>">
                    <?= e($contactEmail) ?>
                </a>

            </div>

            <div class="contact-card">

                <span>
                    PHONE
                </span>

                <a href="tel:<?= e($contactPhone) ?>">
                    <?= e($contactPhone) ?>
                </a>

            </div>

            <div class="contact-card">

                <span>
                    LOCATION
                </span>

                <p>
                    <?= e($location) ?>
                </p>

            </div>

            <div class="contact-card">

                <span>
                    SUPPORT HOURS
                </span>

                <p>
                    <?= e($supportHours) ?>
                </p>

            </div>

            <div class="contact-card">

                <span>
                    SOCIAL MEDIA
                </span>

                <div class="social-links">

                    <a
                        href="<?= e($facebookUrl) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="social-name">
                            FACEBOOK
                        </span>

                        <span class="social-handle">
                            @FrostCoreCoolers →
                        </span>
                    </a>

                    <a
                        href="<?= e($instagramUrl) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="social-name">
                            INSTAGRAM
                        </span>

                        <span class="social-handle">
                            @FrostCoreCoolers →
                        </span>
                    </a>

                    <a
                        href="<?= e($tiktokUrl) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <span class="social-name">
                            TIKTOK
                        </span>

                        <span class="social-handle">
                            @FrostCoreCoolers →
                        </span>
                    </a>

                </div>

            </div>

        </div>

        <div class="panel">

            <h2>
                SEND A MESSAGE
            </h2>

            <?php if ($formError !== ""): ?>

                <div class="error">
                    <?= e($formError) ?>
                </div>

            <?php endif; ?>

            <?php if ($mailToUrl !== ""): ?>

                <script>
                    window.addEventListener("DOMContentLoaded", function () {
                        window.location.href = <?= json_encode($mailToUrl) ?>;
                    });
                </script>

                <div class="info-success">

                    Your email application is being opened with your message prepared.

                    <br><br>

                    If it does not open automatically, email us directly at:

                    <strong>
                        <?= e($contactEmail) ?>
                    </strong>

                </div>

            <?php endif; ?>

            <form method="POST" action="contact.php">

                <div class="form-grid">

                    <div class="field">

                        <label for="name">
                            NAME
                        </label>

                        <input
                            id="name"
                            name="name"
                            type="text"
                            maxlength="120"
                            value="<?= e($formName) ?>"
                            placeholder="Your name"
                            required
                        >

                    </div>

                    <div class="field">

                        <label for="email">
                            EMAIL
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            maxlength="190"
                            value="<?= e($formEmail) ?>"
                            placeholder="your@email.com"
                            required
                        >

                    </div>

                    <div class="field full">

                        <label for="subject">
                            SUBJECT
                        </label>

                        <input
                            id="subject"
                            name="subject"
                            type="text"
                            maxlength="180"
                            value="<?= e($subject) ?>"
                            placeholder="What can we help you with?"
                            required
                        >

                    </div>

                    <div class="field full">

                        <label for="message">
                            MESSAGE
                        </label>

                        <textarea
                            id="message"
                            name="message"
                            maxlength="3000"
                            placeholder="Write your message here..."
                            required
                        ><?= e($message) ?></textarea>

                    </div>

                </div>

                <button
                    type="submit"
                    class="submit"
                >
                    OPEN EMAIL →
                </button>

            </form>

            <p class="info">
                Your message will open in your device's email application
                and will be addressed to
                <strong><?= e($contactEmail) ?></strong>.
            </p>

        </div>

    </section>

</main>

<footer class="contact-footer">

    <div class="contact-footer-top">

        <div>

            <h3>
                FROSTCORE
            </h3>

            <p>
                High performance cooling solutions built for gamers.
            </p>

        </div>

        <div>

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

        <div>

            <h3>
                COMPANY
            </h3>

            <a href="about.php">
                About Us
            </a>

            <a href="contact.php">
                Contact Us
            </a>

        </div>

        <div>

            <h3>
                SUPPORT
            </h3>

            <a href="contact.php">
                Contact Support
            </a>

            <a href="contact.php">
                FAQs
            </a>

        </div>

    </div>

    <div class="footer-copy">

        <span>
            © <?= date("Y") ?>
            FROSTCORE. All rights reserved.
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