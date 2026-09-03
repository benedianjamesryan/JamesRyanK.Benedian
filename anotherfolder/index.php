<?php

// Start the session so Home can recognize a logged-in user.
session_start();

// Check if the customer is already logged in.
 $loggedIn = !empty($_SESSION['user_id']);

 $year = date('Y');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FROSTCORE — Stay Cool. Play Better.</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- HEADER -->
    <header class="site-header">
        <a class="brand" href="#home">
            <img class="brand-logo" src="assets/frostcore_logo.png" alt="FROSTCORE logo">
            <span>FROSTCORE</span>
        </a>

        <nav class="nav">
            <a href="#why">Why Cooling</a>
            <a href="#product">Product</a>
            <a href="#specs">Specs</a>
            <a href="#reviews">Reviews</a>
        </nav>

        <?php if ($loggedIn): ?>
            <a class="btn btn-small" href="products.php">
                SHOP NOW
            </a>
        <?php else: ?>
            <a class="btn btn-small requires-login" href="#login">
                SHOP NOW
            </a>
        <?php endif; ?>
    </header>


    <!-- MAIN -->
    <main>

        <!-- HERO -->
        <section id="home" class="hero">
            <div class="hero-copy">
                <div class="eyebrow">● EXTERNAL COOLING • ENGINEERED</div>

                <h1>
                    Stay cool.<br>
                    <span>Play better.</span>
                </h1>

                <p class="lead">
                    FROSTCORE phone and laptop coolers pull heat away from your device in seconds — so frame drops, throttling, and overheat warnings stop deciding your matches.
                </p>

                <?php if ($loggedIn): ?>
                    <a class="btn" href="products.php">
                        SHOP COOLERS <b>→</b>
                    </a>
                <?php else: ?>
                    <a class="btn requires-login" href="#login">
                        SHOP COOLERS <b>→</b>
                    </a>
                <?php endif; ?>

                <a class="btn btn-outline" href="#why">
                    SEE HOW IT WORKS
                </a>
            </div>

            <div class="hero-stats">
                <div>
                    <strong>-12°C</strong>
                    <small>Avg. drop in 90 sec</small>
                </div>

                <div>
                    <strong>40dB</strong>
                    <small>Whisper-quiet fan</small>
                </div>

                <div>
                    <strong>5,600+</strong>
                    <small>Gamers cooled daily</small>
                </div>
            </div>
        </div>

        <div class="hero-art">
            <div class="hex-logo">
                <img src="assets/frostcore_logo.png" alt="FROSTCORE logo">
            </div>

            <div class="temp-card">
                <small>Core Temp</small>
                <strong>28.4°C</strong>
            </div>
        </div>
    </section>


    <!-- WHY COOLING -->
    <section id="why" class="problem section">
        <div class="container">
            <h2>
                Heat is quietly ruining your<br>
                gameplay.
            </h2>

            <p class="section-intro">
                Extended sessions push your phone or laptop past safe operating temperatures — and performance pays the price before you even notice.
            </p>

            <div class="problem-grid">

                <article>
                    <span class="icon">↕</span>
                    <h3>Thermal throttling</h3>
                    <p>
                        Chips slow themselves down to avoid damage, costing you FPS exactly when you need it most.
                    </p>
                </article>

                <article>
                    <span class="icon">⌁</span>
                    <h3>Battery drain</h3>
                    <p>
                        Excess heat accelerates battery wear, shortening the life of the device you rely on daily.
                    </p>
                </article>

                <article>
                    <span class="icon">⚠</span>
                    <h3>Sudden shutdowns</h3>
                    <p>
                        Devices auto-shut off at critical temperatures, ending a ranked match at the worst possible moment.
                    </p>
                </article>

            </div>
        </div>
    </section>


    <!-- PRODUCT -->
    <section id="product" class="product section">
        <div class="container">

            <div class="eyebrow">THE FIX</div>

            <h2>
                Active cooling, built for how you<br>
                actually play.
            </h2>

            <div class="product-layout">

                <div class="product-image-card">
                    <span>FC-1 / CLIP COOLER</span>
                    <img src="assets/fc1-cooler.svg" alt="FROSTCORE FC-1 clip cooler">
                </div>

                <div class="features">

                    <div class="feature">
                        <b>01</b>
                        <div>
                            <h3>Semiconductor cooling plate</h3>
                            <p>
                                Direct-contact cold plate draws heat off the chipset faster than airflow alone.
                            </p>
                        </div>
                    </div>

                    <div class="feature">
                        <b>02</b>
                        <div>
                            <h3>Adjustable clip mount</h3>
                            <p>
                                Fits phones and thin laptops from 6&quot; to 16&quot; without blocking your grip or ports.
                            </p>
                        </div>
                    </div>

                    <div class="feature">
                        <b>03</b>
                        <div>
                            <h3>Whisper-quiet turbine fan</h3>
                            <p>
                                Runs at 40dB or lower — audible airflow, not a distraction during voice chat.
                            </p>
                        </div>
                    </div>

                    <div class="feature">
                        <b>04</b>
                        <div>
                            <h3>USB-C powered, 6hr runtime</h3>
                            <p>
                                No batteries to charge. Separately plug in and go for a full session.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>


    <!-- SPECS -->
    <section id="specs" class="specs">
        <div class="container specs-grid">

            <div>
                <strong>-12°C</strong>
                <small>TEMP DROP, AVG.</small>
            </div>

            <div>
                <strong>18W</strong>
                <small>COOLING CAPACITY</small>
            </div>

            <div>
                <strong>40dB</strong>
                <small>PEAK FAN NOISE</small>
            </div>

            <div>
                <strong>6hr</strong>
                <small>CONTINUOUS RUNTIME</small>
            </div>

            <div>
                <strong>2yr</strong>
                <small>WARRANTY</small>
            </div>

        </div>
    </section>


    <!-- REVIEWS -->
    <section id="reviews" class="reviews section">
        <div class="container">

            <div class="eyebrow">FROM THE TRIBE</div>

            <h2>
                Trusted by players who can't<br>
                afford to lag.
            </h2>

            <div class="review-grid">

                <article class="review">
                    <div class="stars">★★★★★</div>

                    <p>
                        "My phone used to throttle by the second round. Now I finish full tournaments without it getting warm."
                    </p>

                    <div class="person">
                        <span class="avatar"></span>
                        <div>
                            <b>Russel R.</b>
                            <small>Mobile MOBA player</small>
                        </div>
                    </div>
                </article>

                <article class="review">
                    <div class="stars">★★★★★</div>

                    <p>
                        "Clips on in two seconds, fan is genuinely quiet, and my laptop stopped shutting down mid-render."
                    </p>

                    <div class="person">
                        <span class="avatar"></span>
                        <div>
                            <b>Marlou A.</b>
                            <small>Streamer / Editor</small>
                        </div>
                    </div>
                </article>

                <article class="review">
                    <div class="stars">★★★★★</div>

                    <p>
                        "Bought it for the FPS drops, stayed for the battery life. Both got noticeably better within a week."
                    </p>

                    <div class="person">
                        <span class="avatar"></span>
                        <div>
                            <b>Zerna B.</b>
                            <small>Competitive FPS player</small>
                        </div>
                    </div>
                </article>

            </div>
        </div>
    </section>


    <!-- CTA -->
    <section id="shop" class="cta">
        <div class="cta-logo">
            <img src="assets/frostcore_logo.png" alt="FROSTCORE logo">
        </div>

        <h2>
            Your next overheat warning<br>
            doesn't have to happen.
        </h2>

        <p>
            Free shipping on all coolers this week. 2 years warranty included.
        </p>

        <?php if ($loggedIn): ?>
            <a class="btn" href="products.php">
                SHOP COOLERS →
            </a>
        <?php else: ?>
            <a class="btn requires-login" href="#login">
                SHOP COOLERS →
            </a>
        <?php endif; ?>
    </section>

</main>


<!-- FOOTER -->
<footer class="footer">
    <div class="footer-grid">

        <div>
            <h3>FROSTCORE</h3>
            <p>
                Affordable external cooling solutions for gamers — phone coolers and laptop cooling systems built for everyday use.
            </p>
        </div>

        <div>
            <h3>SHOP</h3>
            <?php if ($loggedIn): ?>
                <a href="products.php?category=Phone+Cooler">
                    Phone Coolers
                </a>

                <a href="products.php?category=Laptop+Cooler">
                    Laptop Coolers
                </a>

                <a href="products.php?category=Bundle">
                    Bundles
                </a>
            <?php else: ?>
                <a class="requires-login" href="#login">
                    Phone Coolers
                </a>

                <a class="requires-login" href="#login">
                    Laptop Coolers
                </a>

                <a class="requires-login" href="#login">
                    Bundles
                </a>
            <?php endif; ?>
        </div>

        <div>
            <h3>COMPANY</h3>
            <a href="#why">Mission</a>
            <a href="#reviews">Reviews</a>
            <a href="#shop">Support</a>
        </div>

        <div>
            <h3>CONNECT</h3>
            <a href="#">Instagram</a>
            <a href="#">TikTok</a>
            <a href="#">Discord</a>
        </div>

    </div>

    <div class="footer-bottom">
        <span>
            © <?= htmlspecialchars($year) ?> FROSTCORE. All rights reserved.
        </span>

        <span>
            STAY COOL. PLAY BETTER.
        </span>
    </div>
</footer>


<!-- LOGIN POPUP -->
<div
    class="login-overlay"
    id="loginOverlay"
    aria-hidden="true"
>
    <div
        class="login-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="loginTitle"
    >

        <button
            class="close-login"
            id="closeLogin"
            aria-label="Close"
        >
            ×
        </button>

        <img
            class="login-logo"
            src="assets/frostcore_logo.png"
            alt="FROSTCORE logo"
        >

        <h2 id="loginTitle">
            WELCOME BACK
        </h2>

        <p class="login-subtitle">
            Sign in to your FROSTCORE experience.
        </p>

        <form
            id="loginForm"
            action="login.php"
            method="post"
        >

            <label for="email">EMAIL</label>

            <input
                id="email"
                name="email"
                type="email"
                placeholder="Enter your email"
                required
            >

            <label for="password">PASSWORD</label>

            <div class="password-wrap">

                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Enter your password"
                    required
                >

                <button
                    type="button"
                    id="showPassword"
                >
                    SHOW
                </button>

            </div>

            <button
                class="btn login-submit"
                type="submit"
            >
                SIGN IN →
            </button>

        </form>

        <p class="forgot">
            Forgot password?
        </p>

        <p class="create">
            Don't have an account?
            <a href="login.php">CREATE ACCOUNT</a>
        </p>

    </div>
</div>


<!-- JAVASCRIPT -->
<script src="script.js"></script>

</body>
</html>