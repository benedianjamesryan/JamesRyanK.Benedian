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
        trim($_POST["email"] ?? "");

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
            // IMPORTANT SESSION RESET
            // ==================================================
            //
            // Remove the previous account's session data
            // BEFORE creating the new authenticated session.
            //

            $_SESSION = [];


            // Generate a completely new session ID.
            session_regenerate_id(true);


            // --------------------------------------------------
            // SAVE ONLY THE NEW USER
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

$year = date("Y");

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

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body class="login-page">


<header class="site-header">


    <a
        class="brand"
        href="index.php"
    >

        <img
            class="brand-logo"
            src="assets/frostcore_logo.png"
            alt="FROSTCORE logo"
        >

        <span>
            FROSTCORE
        </span>

    </a>


    <a
        class="btn btn-small"
        href="index.php"
    >
        BACK HOME
    </a>


</header>



<main class="login-page-main">


    <div class="login-card">


        <img
            class="login-logo"
            src="assets/frostcore_logo.png"
            alt="FROSTCORE logo"
        >


        <h1>
            WELCOME BACK
        </h1>


        <p class="login-subtitle">
            Sign in to your FROSTCORE experience.
        </p>



        <?php if ($error !== ""): ?>

            <div class="form-error">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>



        <form
            action="login.php"
            method="post"
        >


            <input
                type="hidden"
                name="redirect"
                value="<?= htmlspecialchars(
                    $redirect,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >


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



            <button
                class="btn login-submit"
                type="submit"
            >
                SIGN IN →
            </button>


        </form>



        <p class="create">

            Don't have an account?

            <a
                href="register.php?redirect=<?= urlencode($redirect) ?>"
            >
                CREATE ACCOUNT
            </a>

        </p>


    </div>

</main>



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