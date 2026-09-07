<?php

// ==================================================
// FROSTCORE LOGIN
// ==================================================

session_start();

require_once "database/config.php";


// ==================================================
// DEFAULT VALUES
// ==================================================

$error = "";

$email = "";

$redirect =
    $_GET["redirect"] ??
    $_POST["redirect"] ??
    "products.php";


// ==================================================
// SAFE REDIRECT
// ==================================================

if (
    $redirect === "" ||
    str_contains($redirect, "://") ||
    str_starts_with($redirect, "//") ||
    str_starts_with($redirect, "../")
) {

    $redirect = "index.php";

}


// ==================================================
// HANDLE LOGIN
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email =
        trim(
            $_POST["email"] ?? ""
        );

    $password =
        $_POST["password"] ?? "";


    // --------------------------------------------------
    // VALIDATION
    // --------------------------------------------------

    if (
        $email === "" ||
        $password === ""
    ) {

        $error =
            "Please enter your email and password.";

    }

    elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            "Please enter a valid email address.";

    }

    else {


        // --------------------------------------------------
        // FIND USER
        // --------------------------------------------------

        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name,
                email,
                password,
                role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([
            $email
        ]);

        $user =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        // --------------------------------------------------
        // VERIFY PASSWORD
        // --------------------------------------------------

        if (
            !$user ||
            !password_verify(
                $password,
                $user["password"]
            )
        ) {

            $error =
                "Invalid email or password.";

        }

        else {


            // ==================================================
            // RESET PREVIOUS SESSION
            // ==================================================

            $_SESSION = [];


            // Generate a new session ID.
            session_regenerate_id(true);


            // --------------------------------------------------
            // SAVE USER SESSION
            // --------------------------------------------------

            $_SESSION["user_id"] =
                (int)$user["id"];

            $_SESSION["username"] =
                (string)$user["full_name"];

            $_SESSION["email"] =
                (string)$user["email"];

            $_SESSION["role"] =
                (string)$user["role"];


            // --------------------------------------------------
            // REDIRECT
            // --------------------------------------------------

            header(
                "Location: " . $redirect
            );

            exit;

        }

    }

}


// ==================================================
// YEAR
// ==================================================

$year =
    date("Y");

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
        FROSTCORE — Login
    </title>


    <!-- ==================================================
         SHARED CSS
    ================================================== -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <!-- ==================================================
         LOGIN PAGE CSS
    ================================================== -->

    <link
        rel="stylesheet"
        href="css/login.css"
    >

</head>


<body class="login-page">


<!-- ==================================================
     HEADER
================================================== -->

<header class="site-header">


    <!-- BRAND -->

    <a
        class="brand"
        href="index.php"
    >

        <img
            class="brand-logo"
            src="assets/logo/frostcore_logo.png"
            alt="FROSTCORE logo"
        >


        <span>
            FROSTCORE
        </span>

    </a>


    <!-- BACK HOME -->

    <a
        class="btn btn-small"
        href="index.php"
    >

        BACK HOME

    </a>


</header>



<!-- ==================================================
     LOGIN MAIN
================================================== -->

<main class="login-page-main">


    <div class="login-card">


        <!-- LOGO -->

        <img
            class="login-logo"
            src="assets/logo/frostcore_logo.png"
            alt="FROSTCORE logo"
        >


        <!-- TITLE -->

        <h1>
            WELCOME BACK
        </h1>


        <p class="login-subtitle">

            Sign in to your FROSTCORE experience.

        </p>



        <!-- ==================================================
             ERROR MESSAGE
        ================================================== -->

        <?php if ($error !== ""): ?>

            <div class="form-error">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>



        <!-- ==================================================
             LOGIN FORM
        ================================================== -->

        <form
            action="login.php"
            method="post"
        >


            <!-- PRESERVE REDIRECT -->

            <input
                type="hidden"
                name="redirect"
                value="<?= htmlspecialchars(
                    $redirect,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >


            <!-- EMAIL -->

            <label for="page-email">

                EMAIL

            </label>


            <input
                id="page-email"
                name="email"
                type="email"
                placeholder="Enter your email"
                value="<?= htmlspecialchars(
                    $email,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
                required
                autocomplete="username"
            >



            <!-- PASSWORD -->

            <label for="page-password">

                PASSWORD

            </label>


            <input
                id="page-password"
                name="password"
                type="password"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
            >



            <!-- LOGIN BUTTON -->

            <button
                class="btn login-submit"
                type="submit"
            >

                SIGN IN →

            </button>


        </form>



        <!-- CREATE ACCOUNT -->

        <p class="create">

            Don't have an account?

            <a
                href="register.php?redirect=<?= urlencode(
                    $redirect
                ) ?>"
            >

                CREATE ACCOUNT

            </a>

        </p>


    </div>


</main>



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="footer-bottom login-footer">


    <span>

        © <?= htmlspecialchars(
            $year,
            ENT_QUOTES,
            "UTF-8"
        ) ?>

        FROSTCORE.
        All rights reserved.

    </span>


    <span>

        STAY COOL. PLAY BETTER.

    </span>


</footer>


</body>

</html>