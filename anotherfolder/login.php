<?php

$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

$email = $submitted
    ? htmlspecialchars($_POST['email'] ?? '')
    : '';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FROSTCORE — Login</title>

    <link rel="stylesheet" href="style.css">
</head>

<body class="login-page">

    <!-- Header -->
    <header class="site-header">

        <a class="brand" href="index.php">
            <img
                class="brand-logo"
                src="assets/frostcore_logo.png"
                alt="FROSTCORE logo"
            >

            <span>FROSTCORE</span>
        </a>

        <a class="btn btn-small" href="index.php">
            BACK HOME
        </a>

    </header>


    <!-- Main Login Section -->
    <main class="login-page-main">

        <div class="login-card">

            <!-- Logo -->
            <img
                class="login-logo"
                src="assets/frostcore_logo.png"
                alt="FROSTCORE logo"
            >

            <!-- Heading -->
            <h1>WELCOME BACK</h1>

            <p class="login-subtitle">
                Sign in to your FROSTCORE experience.
            </p>


            <!-- Demo Message -->
            <?php if ($submitted): ?>

                <div class="demo-message">
                    Demo login submitted for
                    <strong><?= $email ?></strong>.
                    <br>
                    This is a static mockup — no database is used.
                </div>

            <?php endif; ?>


            <!-- Login Form -->
            <form action="login.php" method="post">

                <label for="page-email">
                    EMAIL
                </label>

                <input
                    id="page-email"
                    name="email"
                    type="email"
                    placeholder="Enter your email"
                    required
                >


                <label for="page-password">
                    PASSWORD
                </label>

                <input
                    id="page-password"
                    name="password"
                    type="password"
                    placeholder="Enter your password"
                    required
                >


                <button
                    class="btn login-submit"
                    type="submit"
                >
                    SIGN IN →
                </button>

            </form>


            <!-- Create Account -->
     <p class="create">
            Don't have an account?
    <a href="register.php">CREATE ACCOUNT</a>
         </p>

        </div>

    </main>


    <!-- Footer -->
    <footer class="footer-bottom login-footer">

        <span>
            © <?= date('Y') ?> FROSTCORE. All rights reserved.
        </span>

        <span>
            STAY COOL. PLAY BETTER.
        </span>

    </footer>

</body>
</html>
```
