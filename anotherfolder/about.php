<?php

// ==================================================
// FROSTCORE — ABOUT US
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
// LOGIN / ROLE
// ==================================================

$isLoggedIn =
    !empty($_SESSION["user_id"]);

$isAdmin =
    ($_SESSION["role"] ?? "") === "admin";

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
        FROSTCORE — About Us
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
           MATCHES PRODUCTS PAGE
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

        .brand {

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


        .brand-logo {

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


        /* ==================================================
           LOGIN / LOGOUT
        ================================================== */

        .login-link {

            color:
                #AAB5CA;

            font-size:
                9px;

            font-weight:
                800;

            letter-spacing:
                0;

            text-decoration:
                none;

            cursor:
                pointer;

        }


        .login-link:hover {

            color:
                #4DBCF4;

        }


        /* ==================================================
           ADMIN
        ================================================== */

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


        /* ==================================================
           CART
        ================================================== */

        .cart-link {

            position:
                relative;

            color:
                #F4F7FF;

            font-size:
                20px;

            font-weight:
                700;

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

        .about-page {

            width:
                82%;

            max-width:
                1120px;

            margin:
                0 auto;

            padding:
                75px 0 90px;

        }


        .about-hero {

            margin-bottom:
                45px;

        }


        .about-eyebrow {

            color:
                #4DBCF4;

            font-size:
                9px;

            font-weight:
                800;

            letter-spacing:
                2px;

        }


        .about-hero h1 {

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


        .about-hero p {

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
           MISSION / VISION
        ================================================== */

        .about-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    2,
                    1fr
                );

            gap:
                18px;

        }


        .about-card {

            padding:
                28px;

            background:
                #111A31;

            border:
                1px solid #263452;

        }


        .about-card h2 {

            margin:
                0 0 14px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                16px;

        }


        .about-card p {

            margin:
                0;

            color:
                #AAB5CA;

            font-size:
                10px;

            line-height:
                1.8;

        }


        /* ==================================================
           VALUES
        ================================================== */

        .values-section {

            margin-top:
                18px;

            padding:
                28px;

            background:
                #111A31;

            border:
                1px solid #263452;

        }


        .values-section h2 {

            margin:
                0 0 20px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                16px;

        }


        .values-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    3,
                    1fr
                );

            gap:
                14px;

        }


        .value-card {

            padding:
                20px;

            background:
                #081225;

            border:
                1px solid #263452;

        }


        .value-card strong {

            display:
                block;

            margin-bottom:
                8px;

            color:
                #4DBCF4;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                11px;

        }


        .value-card p {

            margin:
                0;

            color:
                #AAB5CA;

            font-size:
                9px;

            line-height:
                1.7;

        }


        /* ==================================================
           CTA
        ================================================== */

        .about-cta {

            margin-top:
                22px;

            padding:
                25px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            background:
                #111A31;

            border:
                1px solid #263452;

        }


        .about-cta p {

            margin:
                0;

            color:
                #AAB5CA;

            font-size:
                10px;

        }


        .about-cta a {

            display:
                inline-block;

            padding:
                11px 17px;

            background:
                #4DBCF4;

            color:
                #050A16;

            font-size:
                8px;

            font-weight:
                900;

        }


        /* ==================================================
           FOOTER
        ================================================== */

        .about-footer {

            margin-top:
                0;

            padding:
                45px 7% 25px;

            background:
                #081225;

            border-top:
                1px solid #263452;

        }


        .about-footer-top {

            display:
                grid;

            grid-template-columns:
                2fr 1fr 1fr 1fr;

            gap:
                45px;

        }


        .about-footer h3 {

            margin:
                0 0 15px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                10px;

        }


        .about-footer p {

            color:
                #AAB5CA;

            font-size:
                9px;

            line-height:
                1.7;

        }


        .about-footer a {

            display:
                block;

            margin:
                8px 0;

            color:
                #AAB5CA;

            font-size:
                9px;

        }


        .about-footer a:hover {

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

            .products-header {

                padding:
                    0 4%;

            }


            .products-nav {

                gap:
                    20px;

            }


            .about-page {

                width:
                    90%;

            }


            .about-footer-top {

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


            .about-grid {

                grid-template-columns:
                    1fr;

            }


            .values-grid {

                grid-template-columns:
                    1fr;

            }


            .about-cta {

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }


            .about-footer-top {

                grid-template-columns:
                    1fr 1fr;

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

            }


            .brand {

                font-size:
                    14px;

            }


            .brand-logo {

                width:
                    34px;

                height:
                    34px;

            }


            .about-page {

                padding:
                    50px 0 65px;

            }


            .about-footer-top {

                grid-template-columns:
                    1fr;

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


        <a
            href="about.php"
            class="active"
        >
            ABOUT US
        </a>


        <a href="contact.php">
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

<main class="about-page">


    <section class="about-hero">


        <div class="about-eyebrow">
            ABOUT FROSTCORE
        </div>


        <h1>
            COOLING BUILT FOR PLAYERS.
        </h1>


        <p>

            FROSTCORE is a student-built
            e-commerce concept focused on
            affordable external cooling
            solutions for gamers.

            Our goal is to make practical
            phone and CPU cooling products
            easier to discover, compare,
            and purchase through a simple
            online experience.

        </p>


    </section>



    <!-- ==================================================
         MISSION / VISION
    ================================================== -->

    <section class="about-grid">


        <article class="about-card">


            <h2>
                OUR MISSION
            </h2>


            <p>

                To provide accessible cooling
                solutions that help gamers
                manage heat during demanding
                sessions while keeping the
                shopping experience simple,
                clear, and reliable.

            </p>


        </article>



        <article class="about-card">


            <h2>
                OUR VISION
            </h2>


            <p>

                To become a trusted local
                cooling brand known for
                practical products,
                gamer-focused design,
                responsive service,
                and an easy-to-use
                online store.

            </p>


        </article>


    </section>



    <!-- ==================================================
         VALUES
    ================================================== -->

    <section class="values-section">


        <h2>
            WHAT WE VALUE
        </h2>


        <div class="values-grid">


            <div class="value-card">


                <strong>
                    PERFORMANCE
                </strong>


                <p>

                    Products should solve
                    a real cooling problem
                    without unnecessary
                    complexity.

                </p>


            </div>



            <div class="value-card">


                <strong>
                    ACCESSIBILITY
                </strong>


                <p>

                    Cooling should be
                    understandable and
                    affordable for
                    everyday gamers.

                </p>


            </div>



            <div class="value-card">


                <strong>
                    TRUST
                </strong>


                <p>

                    Clear product information,
                    order tracking, and
                    customer support matter.

                </p>


            </div>


        </div>


    </section>



    <!-- ==================================================
         CONTACT CTA
    ================================================== -->

    <div class="about-cta">


        <p>

            Have a question about a product,
            order, or the FROSTCORE project?

        </p>


        <a href="contact.php">

            CONTACT US →

        </a>


    </div>


</main>



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="about-footer">


    <div class="about-footer-top">


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