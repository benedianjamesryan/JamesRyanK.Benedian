<?php

// ==================================================
// FROSTCORE — CONTACT US
// ==================================================

session_start();


// ==================================================
// HELPER
// ==================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


// ==================================================
// CONTACT DETAILS
// ==================================================

$contactEmail =
    "frostcorecoolers@gmail.com";

$contactPhone =
    "09123456778";

$facebookUrl =
    "https://www.facebook.com/FrostCoreCoolers";

$instagramUrl =
    "https://www.instagram.com/FrostCoreCoolers";

$tiktokUrl =
    "https://www.tiktok.com/@FrostCoreCoolers";

$location =
    "Bacong, Negros Oriental";

$supportHours =
    "Monday–Saturday, 9:00 AM–6:00 PM";


// ==================================================
// LOGIN / ROLE
// ==================================================

$isLoggedIn =
    !empty($_SESSION["user_id"]);

$isAdmin =
    ($_SESSION["role"] ?? "") === "admin";


// ==================================================
// FORM
// ==================================================

$formName =
    trim(
        $_POST["name"] ?? ""
    );

$formEmail =
    trim(
        $_POST["email"] ?? ""
    );

$subject =
    trim(
        $_POST["subject"] ?? ""
    );

$message =
    trim(
        $_POST["message"] ?? ""
    );

$formError =
    "";

$mailToUrl =
    "";


// ==================================================
// PROCESS CONTACT FORM
// ==================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    if (
        $formName === "" ||
        $formEmail === "" ||
        $subject === "" ||
        $message === ""
    ) {

        $formError =
            "Please complete all fields.";

    }

    elseif (
        !filter_var(
            $formEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $formError =
            "Please enter a valid email address.";

    }

    elseif (
        strlen($formName) > 120
    ) {

        $formError =
            "Your name is too long.";

    }

    elseif (
        strlen($subject) > 180
    ) {

        $formError =
            "Your subject is too long.";

    }

    elseif (
        strlen($message) > 3000
    ) {

        $formError =
            "Your message cannot exceed 3000 characters.";

    }

    else {

        $mailBody =
            "Hello FROSTCORE Team,\n\n" .

            "Name: " .
            $formName .
            "\n" .

            "Email: " .
            $formEmail .
            "\n\n" .

            "Message:\n" .
            $message .
            "\n\n" .

            "Sent from the FROSTCORE website.";

        $mailToUrl =
            "mailto:" .
            $contactEmail .
            "?subject=" .
            rawurlencode($subject) .
            "&body=" .
            rawurlencode($mailBody);

    }

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
        FROSTCORE — Contact Us
    </title>


    <link
        rel="stylesheet"
        href="style.css"
    >


    <style>

        /* ==================================================
           GLOBAL
        ================================================== */

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
        }


        body {

            margin: 0;

            background: #050A16;

            color: #F4F7FF;

            font-family:
                Inter,
                Arial,
                sans-serif;

        }


        a {

            color: inherit;

            text-decoration: none;

        }


        /* ==================================================
           HEADER
           SAME STYLE AS PRODUCTS PAGE
        ================================================== */

        .products-header {

            width: 100%;

            height: 72px;

            padding:
                0 5.5%;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            border-bottom:
                1px solid #263452;

            background:
                rgba(
                    5,
                    10,
                    22,
                    0.96
                );

            position:
                sticky;

            top:
                0;

            z-index:
                9999;

            backdrop-filter:
                blur(12px);

        }


        /* ==================================================
           BRAND
        ================================================== */

        .products-header .brand {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            flex-shrink:
                0;

            font-family:
                Orbitron,
                Arial,
                sans-serif;

            font-weight:
                700;

            font-size:
                18px;

        }


        .products-header .brand-logo {

            width:
                40px;

            height:
                40px;

            object-fit:
                contain;

        }


        /* ==================================================
           NAVIGATION
        ================================================== */

        .products-nav {

            display:
                flex;

            align-items:
                center;

            gap:
                45px;

        }


        .products-nav a {

            color:
                #F4F7FF;

            font-size:
                11px;

            font-weight:
                600;

            transition:
                color 0.2s ease;

        }


        .products-nav a:hover,

        .products-nav a.active {

            color:
                #4DBCF4;

        }


        /* ==================================================
           HEADER ACTIONS
        ================================================== */

        .header-actions {

            display:
                flex;

            align-items:
                center;

            gap:
                20px;

            flex-shrink:
                0;

        }


        .header-actions a {

            text-decoration:
                none;

        }


        .login-link {

            color:
                #AAB5CA;

            font-size:
                9px;

            font-weight:
                800;

            letter-spacing:
                0;

        }


        .login-link:hover {

            color:
                #4DBCF4;

        }


        .admin-link {

            padding:
                7px 10px;

            color:
                #4DBCF4 !important;

            border:
                1px solid #4DBCF4;

            font-size:
                8px !important;

            font-weight:
                800 !important;

        }


        .admin-link:hover {

            background:
                rgba(
                    77,
                    188,
                    244,
                    0.08
                );

        }


        .cart-link {

            position:
                relative;

            color:
                #F4F7FF;

            font-size:
                20px;

        }


        .cart-link:hover {

            color:
                #4DBCF4;

        }


        .cart-count {

            position:
                absolute;

            top:
                -9px;

            right:
                -10px;

            width:
                18px;

            height:
                18px;

            display:
                grid;

            place-items:
                center;

            border-radius:
                50%;

            background:
                #4DBCF4;

            color:
                #050A16;

            font-size:
                8px;

            font-weight:
                800;

        }


        /* ==================================================
           MAIN
        ================================================== */

        .contact-page {

            width:
                82%;

            max-width:
                1120px;

            margin:
                0 auto;

            padding:
                75px 0 90px;

        }


        .contact-hero {

            margin-bottom:
                35px;

        }


        .contact-eyebrow {

            color:
                #4DBCF4;

            font-size:
                9px;

            font-weight:
                800;

            letter-spacing:
                2px;

        }


        .contact-hero h1 {

            margin:
                12px 0 15px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                clamp(
                    42px,
                    5vw,
                    62px
                );

            line-height:
                1;

        }


        .contact-hero p {

            max-width:
                760px;

            margin:
                0;

            color:
                #AAB5CA;

            font-size:
                11px;

            line-height:
                1.8;

        }


        /* ==================================================
           CONTACT GRID
        ================================================== */

        .contact-grid {

            display:
                grid;

            grid-template-columns:
                350px 1fr;

            gap:
                18px;

        }


        .panel {

            padding:
                25px;

            background:
                #111A31;

            border:
                1px solid #263452;

        }


        .panel h2 {

            margin:
                0 0 18px;

            font-family:
                Orbitron,
                Arial,
                sans-serif;

            font-size:
                15px;

        }


        /* ==================================================
           CONTACT CARDS
        ================================================== */

        .contact-card {

            padding:
                16px;

            margin-bottom:
                10px;

            background:
                #081225;

            border:
                1px solid #263452;

        }


        .contact-card:last-child {

            margin-bottom:
                0;

        }


        .contact-card > span {

            display:
                block;

            margin-bottom:
                7px;

            color:
                #68758D;

            font-size:
                7px;

            font-weight:
                800;

            letter-spacing:
                0.5px;

        }


        .contact-card a {

            color:
                #4DBCF4;

            font-size:
                10px;

            font-weight:
                700;

            word-break:
                break-word;

        }


        .contact-card p {

            margin:
                0;

            color:
                #AAB5CA;

            font-size:
                9px;

            line-height:
                1.6;

        }


        /* ==================================================
           SOCIAL
        ================================================== */

        .social-links {

            display:
                flex;

            flex-direction:
                column;

            gap:
                8px;

        }


        .social-links a {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                10px;

            padding:
                10px 12px;

            background:
                #081225;

            border:
                1px solid #263452;

        }


        .social-links a:hover {

            border-color:
                #4DBCF4;

        }


        .social-name {

            color:
                #AAB5CA !important;

            font-size:
                8px !important;

        }


        .social-handle {

            color:
                #4DBCF4;

            font-size:
                8px;

            font-weight:
                800;

        }


        /* ==================================================
           FORM
        ================================================== */

        .form-grid {

            display:
                grid;

            grid-template-columns:
                1fr 1fr;

            gap:
                12px;

        }


        .field {

            display:
                flex;

            flex-direction:
                column;

            gap:
                7px;

        }


        .field.full {

            grid-column:
                1 / -1;

        }


        .field label {

            color:
                #AAB5CA;

            font-size:
                8px;

            font-weight:
                800;

        }


        .field input,

        .field textarea {

            width:
                100%;

            padding:
                11px;

            background:
                #081225;

            color:
                #F4F7FF;

            border:
                1px solid #263452;

            outline:
                none;

            font-family:
                Inter,
                Arial,
                sans-serif;

            font-size:
                9px;

        }


        .field input:focus,

        .field textarea:focus {

            border-color:
                #4DBCF4;

        }


        .field textarea {

            min-height:
                160px;

            resize:
                vertical;

            line-height:
                1.6;

        }


        .field input::placeholder,

        .field textarea::placeholder {

            color:
                #68758D;

        }


        /* ==================================================
           SUBMIT
        ================================================== */

        .submit {

            margin-top:
                14px;

            padding:
                12px 18px;

            background:
                #4DBCF4;

            color:
                #050A16;

            border:
                1px solid #4DBCF4;

            font-size:
                8px;

            font-weight:
                900;

            cursor:
                pointer;

        }


        .submit:hover {

            filter:
                brightness(1.05);

        }


        /* ==================================================
           ERROR
        ================================================== */

        .error {

            margin-bottom:
                15px;

            padding:
                12px;

            color:
                #FF9A9A;

            background:
                rgba(
                    255,
                    95,
                    95,
                    0.07
                );

            border:
                1px solid
                rgba(
                    255,
                    95,
                    95,
                    0.35
                );

            font-size:
                9px;

        }


        /* ==================================================
           EMAIL MESSAGE
        ================================================== */

        .info-success {

            margin-bottom:
                15px;

            padding:
                13px 15px;

            color:
                #72E38A;

            background:
                rgba(
                    114,
                    227,
                    138,
                    0.07
                );

            border:
                1px solid
                rgba(
                    114,
                    227,
                    138,
                    0.35
                );

            font-size:
                9px;

            line-height:
                1.6;

        }


        .info {

            margin:
                18px 0 0;

            color:
                #68758D;

            font-size:
                8px;

            line-height:
                1.7;

        }


        .info strong {

            color:
                #AAB5CA;

        }


        /* ==================================================
           FOOTER
        ================================================== */

        .contact-footer {

            margin:
                0;

            padding:
                45px 7% 25px;

            background:
                #081225;

            border-top:
                1px solid #263452;

        }


        .contact-footer-top {

            display:
                grid;

            grid-template-columns:
                2fr 1fr 1fr 1fr;

            gap:
                45px;

        }


        .contact-footer h3 {

            margin:
                0 0 15px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                10px;

        }


        .contact-footer p {

            color:
                #AAB5CA;

            font-size:
                9px;

            line-height:
                1.7;

        }


        .contact-footer a {

            display:
                block;

            margin:
                8px 0;

            color:
                #AAB5CA;

            font-size:
                9px;

        }


        .contact-footer a:hover {

            color:
                #4DBCF4;

        }


        .footer-copy {

            margin-top:
                30px;

            padding-top:
                20px;

            display:
                flex;

            justify-content:
                space-between;

            gap:
                15px;

            border-top:
                1px solid #263452;

            color:
                #68758D;

            font-size:
                8px;

        }


        .footer-copy span {

            color:
                #4DBCF4;

        }


        /* ==================================================
           RESPONSIVE
        ================================================== */

        @media (max-width: 900px) {

            .products-nav {

                gap:
                    20px;

            }


            .contact-page {

                width:
                    90%;

            }


            .contact-grid {

                grid-template-columns:
                    1fr;

            }


            .contact-footer-top {

                grid-template-columns:
                    repeat(
                        2,
                        1fr
                    );

            }

        }


        @media (max-width: 800px) {

            .products-nav {

                display:
                    none;

            }

        }


        @media (max-width: 650px) {

            .form-grid {

                grid-template-columns:
                    1fr;

            }


            .field.full {

                grid-column:
                    auto;

            }


            .contact-footer-top {

                grid-template-columns:
                    1fr;

            }


            .footer-copy {

                flex-direction:
                    column;

            }

        }


        @media (max-width: 500px) {

            .products-header {

                height:
                    65px;

                padding:
                    0 4%;

            }


            .products-header .brand {

                font-size:
                    14px;

            }


            .products-header .brand-logo {

                width:
                    34px;

                height:
                    34px;

            }


            .contact-page {

                padding:
                    50px 0 65px;

            }

        }

    </style>

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


        <a href="products.php">
            PRODUCTS
        </a>


        <?php if (
            $isLoggedIn &&
            !$isAdmin
        ): ?>

            <a href="my-orders.php">
                MY ORDERS
            </a>

        <?php endif; ?>


        <a href="about.php">
            ABOUT US
        </a>


        <a
            href="contact.php"
            class="active"
        >
            CONTACT
        </a>


    </nav>



    <!-- ACTIONS -->

    <div class="header-actions">


        <!-- ADMIN ONLY -->

        <?php if ($isAdmin): ?>

            <a
                href="admin/dashboard.php"
                class="admin-link"
            >
                ADMIN
            </a>

        <?php endif; ?>


        <!-- LOGIN / LOGOUT -->

        <?php if ($isLoggedIn): ?>

            <!-- IMPORTANT:
                 DO NOT change href to logout.php.
                 script.js needs logout-button.
            -->

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
                href="login.php"
                class="login-link"
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

            <span class="cart-count">
                0
            </span>

        </a>


    </div>

</header>



<!-- ==================================================
     MAIN
================================================== -->

<main class="contact-page">


    <section class="contact-hero">


        <div class="contact-eyebrow">
            GET IN TOUCH
        </div>


        <h1>
            CONTACT US.
        </h1>


        <p>

            Have a question about a product,
            your order, or FROSTCORE?

            Reach out through our contact
            information or send us a message
            below.

        </p>


    </section>



    <!-- ==================================================
         CONTACT GRID
    ================================================== -->

    <section class="contact-grid">


        <!-- ==================================================
             CONTACT INFORMATION
        ================================================== -->

        <div class="panel">


            <h2>
                REACH FROSTCORE
            </h2>



            <!-- EMAIL -->

            <div class="contact-card">


                <span>
                    EMAIL
                </span>


                <a
                    href="mailto:<?= e($contactEmail) ?>"
                >

                    <?= e(
                        $contactEmail
                    ) ?>

                </a>


            </div>



            <!-- PHONE -->

            <div class="contact-card">


                <span>
                    PHONE
                </span>


                <a
                    href="tel:<?= e($contactPhone) ?>"
                >

                    <?= e(
                        $contactPhone
                    ) ?>

                </a>


            </div>



            <!-- LOCATION -->

            <div class="contact-card">


                <span>
                    LOCATION
                </span>


                <p>

                    <?= e(
                        $location
                    ) ?>

                </p>


            </div>



            <!-- HOURS -->

            <div class="contact-card">


                <span>
                    SUPPORT HOURS
                </span>


                <p>

                    <?= e(
                        $supportHours
                    ) ?>

                </p>


            </div>



            <!-- SOCIAL -->

            <div class="contact-card">


                <span>
                    SOCIAL MEDIA
                </span>


                <div class="social-links">


                    <!-- FACEBOOK -->

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



                    <!-- INSTAGRAM -->

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



                    <!-- TIKTOK -->

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



        <!-- ==================================================
             SEND MESSAGE
        ================================================== -->

        <div class="panel">


            <h2>
                SEND A MESSAGE
            </h2>



            <!-- ERROR -->

            <?php if (
                $formError !== ""
            ): ?>

                <div class="error">

                    <?= e(
                        $formError
                    ) ?>

                </div>

            <?php endif; ?>



            <!-- EMAIL SUCCESS -->

            <?php if (
                $mailToUrl !== ""
            ): ?>

                <script>

                    window.addEventListener(
                        "DOMContentLoaded",
                        function () {

                            window.location.href =
                                <?= json_encode(
                                    $mailToUrl
                                ) ?>;

                        }
                    );

                </script>


                <div class="info-success">

                    Your email application is
                    being opened with your message
                    prepared.

                    <br><br>

                    If it does not open automatically,
                    email us directly at:

                    <strong>
                        <?= e(
                            $contactEmail
                        ) ?>
                    </strong>

                </div>

            <?php endif; ?>



            <!-- CONTACT FORM -->

            <form
                method="POST"
                action="contact.php"
            >


                <div class="form-grid">


                    <!-- NAME -->

                    <div class="field">


                        <label for="name">
                            NAME
                        </label>


                        <input
                            id="name"
                            name="name"
                            type="text"
                            maxlength="120"
                            value="<?= e(
                                $formName
                            ) ?>"
                            placeholder="Your name"
                            required
                        >


                    </div>



                    <!-- EMAIL -->

                    <div class="field">


                        <label for="email">
                            EMAIL
                        </label>


                        <input
                            id="email"
                            name="email"
                            type="email"
                            maxlength="190"
                            value="<?= e(
                                $formEmail
                            ) ?>"
                            placeholder="your@email.com"
                            required
                        >


                    </div>



                    <!-- SUBJECT -->

                    <div class="field full">


                        <label for="subject">
                            SUBJECT
                        </label>


                        <input
                            id="subject"
                            name="subject"
                            type="text"
                            maxlength="180"
                            value="<?= e(
                                $subject
                            ) ?>"
                            placeholder="What can we help you with?"
                            required
                        >


                    </div>



                    <!-- MESSAGE -->

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
                        ><?= e(
                            $message
                        ) ?></textarea>


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

                Your message will open in your
                device's email application and
                will be addressed to

                <strong>
                    <?= e(
                        $contactEmail
                    ) ?>
                </strong>.

            </p>


        </div>


    </section>


</main>



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="contact-footer">


    <div class="contact-footer-top">


        <div>


            <h3>
                FROSTCORE
            </h3>


            <p>

                High performance cooling
                solutions built for gamers.

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

            FROSTCORE.
            All rights reserved.

        </span>


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