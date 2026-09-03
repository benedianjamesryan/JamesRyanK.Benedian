<?php

// Start the session so we can keep the user logged in.
session_start();


// Connect to the FROSTCORE database.
require_once "database/config.php";


// Store an error message if login fails.
$error = "";


// Keep the email after a failed login attempt.
$email = "";


// Allow the user to return to the page they originally wanted.
$redirect = $_GET["redirect"] ?? $_POST["redirect"] ?? "index.php";


// Only allow internal website pages as redirects.
if (
    $redirect === "" ||
    str_contains($redirect, "://") ||
    str_starts_with($redirect, "//")
) {
    $redirect = "index.php";
}


// Check whether the login form was submitted.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get the submitted email.
    $email = trim($_POST["email"] ?? "");

    // Get the submitted password.
    $password = $_POST["password"] ?? "";


    // -----------------------------
    // 1. VALIDATE THE INPUT
    // -----------------------------

    if ($email === "" || $password === "") {

        $error = "Please enter your email and password.";

    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }

    else {

        // --------------------------------
        // 2. FIND THE USER IN THE DATABASE
        // --------------------------------

        // Prepared statements help protect against SQL injection.
        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);

        // Get the matching user.
        $user = $stmt->fetch();


        // --------------------------------
        // 3. VERIFY THE PASSWORD
        // --------------------------------

        // The database stores a password hash,
        // so we use password_verify() instead of comparing text.
        if (
            !$user ||
            !password_verify($password, $user["password"])
        ) {

            $error = "Invalid email or password.";

        }

        else {

            // --------------------------------
            // 4. REGENERATE SESSION ID
            // --------------------------------

            // Helps protect against session fixation.
            session_regenerate_id(true);


            // --------------------------------
            // 5. SAVE USER INFORMATION
            // --------------------------------

            $_SESSION["user_id"] = $user["id"];

            $_SESSION["username"] = $user["full_name"];

            $_SESSION["email"] = $user["email"];

            $_SESSION["role"] = $user["role"];


            // --------------------------------
            // 6. GO TO THE NEXT PAGE
            // --------------------------------

            header("Location: " . $redirect);

            exit;
        }
    }
}


// Get the current year for the footer.
$year = date("Y");

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
            <h1>
                WELCOME BACK
            </h1>


            <p class="login-subtitle">
                Sign in to your FROSTCORE experience.
            </p>



            <!-- ERROR MESSAGE -->
            <?php if ($error !== ""): ?>

                <div class="form-error">

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>



            <!-- Login Form -->
            <form
                action="login.php"
                method="post"
            >

                <!-- Keep the original destination -->
                <input
                    type="hidden"
                    name="redirect"
                    value="<?= htmlspecialchars($redirect) ?>"
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
                    value="<?= htmlspecialchars($email) ?>"
                    required
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
                >



                <!-- SIGN IN -->
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

                <a
                    href="register.php?redirect=<?= urlencode($redirect) ?>"
                >
                    CREATE ACCOUNT
                </a>

            </p>


        </div>

    </main>



    <!-- Footer -->
    <footer class="footer-bottom login-footer">

        <span>

            © <?= htmlspecialchars($year) ?>
            FROSTCORE. All rights reserved.

        </span>


        <span>

            STAY COOL. PLAY BETTER.

        </span>

    </footer>


</body>

</html>