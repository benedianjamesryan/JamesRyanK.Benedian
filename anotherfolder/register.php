<?php

// Start the PHP session.
// We need this so the user can stay logged in after registering.
session_start();

// Connect to the FROSTCORE MySQL database.
require_once "database/config.php";

// Store error messages here if something goes wrong.
$error = "";

// Store the submitted values so we can keep them in the form
// when there is an error.
$fullname = "";
$email = "";


// Check if the registration form was submitted.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Get the values submitted by the user.
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    // Check whether the Terms & Conditions checkbox was checked.
    $terms = isset($_POST["terms"]);


    // -------------------------------
    // 1. CHECK REQUIRED INFORMATION
    // -------------------------------

    if ($fullname === "" || $email === "" || $password === "" || $confirmPassword === "") {

        $error = "Please complete all required fields.";

    }

    // Check if the email address is valid.
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    }

    // Require a stronger password.
    elseif (strlen($password) < 8) {

        $error = "Password must be at least 8 characters.";

    }

    // Make sure both password fields match.
    elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    }

    // Make sure the user accepted the terms.
    elseif (!$terms) {

        $error = "Please agree to the Terms & Conditions.";

    }

    else {

        // --------------------------------
        // 2. CHECK IF EMAIL ALREADY EXISTS
        // --------------------------------

        // Prepared statements protect the database from SQL injection.
        $checkUser = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $checkUser->execute([$email]);

        $existingUser = $checkUser->fetch();


        if ($existingUser) {

            $error = "An account with this email already exists.";

        }

        else {

            // -------------------------------
            // 3. HASH THE PASSWORD
            // -------------------------------

            // NEVER save plain-text passwords in the database.
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );


            // -------------------------------
            // 4. SAVE THE USER TO DATABASE
            // -------------------------------

            try {

                // Prepared statement for inserting the new account.
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (full_name, email, password, role)
                    VALUES (?, ?, ?, 'customer')"
                );

                // Send the values safely to MySQL.
                $stmt->execute([
                    $fullname,
                    $email,
                    $hashedPassword
                ]);


                // --------------------------------
                // 5. LOG THE USER IN AUTOMATICALLY
                // --------------------------------

                // Generate a new session ID after authentication.
                session_regenerate_id(true);

                // Store the user's information in the session.
                $_SESSION["user_id"] = $pdo->lastInsertId();
                $_SESSION["username"] = $fullname;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = "customer";


                // --------------------------------
                // 6. SEND USER TO PRODUCTS PAGE
                // --------------------------------

                header("Location: index.php");
                exit;


            } catch (PDOException $e) {

                // Show a simple error instead of exposing database details.
                $error = "Registration failed. Please try again.";

            }
        }
    }
}

$year = date("Y");

?>
<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FROSTCORE — Create Account</title>

    <link rel="stylesheet" href="style.css">

</head>

<body class="register-page">


    <main class="register-main">

        <div class="register-card">


            <!-- LOGO -->
            <div class="register-brand">

                <img
                    src="assets/frostcore_logo.png"
                    alt="FROSTCORE Logo"
                    class="register-logo"
                >

                <div class="register-brand-name">
                    FROSTCORE
                </div>

            </div>


            <!-- TITLE -->
            <h1>CREATE YOUR ACCOUNT</h1>

            <p class="register-subtitle">
                Join the FROSTCORE experience.
            </p>


            <!-- SHOW ERROR MESSAGE -->
            <?php if ($error !== ""): ?>

                <div class="form-error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <!-- REGISTER FORM -->
            <form
                action="register.php"
                method="post"
                class="register-form"
            >


                <!-- FULL NAME -->
                <div class="form-group">

                    <label for="fullname">
                        FULL NAME
                    </label>

                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        placeholder="Enter your full name"
                        value="<?= htmlspecialchars($fullname) ?>"
                        required
                    >

                </div>


                <!-- EMAIL -->
                <div class="form-group">

                    <label for="register-email">
                        EMAIL
                    </label>

                    <input
                        type="email"
                        id="register-email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= htmlspecialchars($email) ?>"
                        required
                    >

                </div>


                <!-- PASSWORD -->
                <div class="form-group">

                    <label for="register-password">
                        PASSWORD
                    </label>

                    <div class="register-password">

                        <input
                            type="password"
                            id="register-password"
                            name="password"
                            placeholder="Create a password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword(
                                'register-password',
                                this
                            )"
                        >
                            SHOW
                        </button>

                    </div>

                </div>


                <!-- CONFIRM PASSWORD -->
                <div class="form-group">

                    <label for="confirm-password">
                        CONFIRM PASSWORD
                    </label>

                    <div class="register-password">

                        <input
                            type="password"
                            id="confirm-password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            minlength="8"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword(
                                'confirm-password',
                                this
                            )"
                        >
                            SHOW
                        </button>

                    </div>

                </div>


                <!-- TERMS -->
                <label class="terms">

                    <input
                        type="checkbox"
                        name="terms"
                        required
                    >

                    <span>
                        I agree to the
                        <a href="#">Terms &amp; Conditions</a>.
                    </span>

                </label>


                <!-- CREATE ACCOUNT -->
                <button
                    type="submit"
                    class="btn register-button"
                >
                    CREATE ACCOUNT →
                </button>


            </form>


            <!-- LOGIN LINK -->
            <p class="register-login">

                Already have an account?

                <a href="login.php">
                    SIGN IN
                </a>

            </p>


        </div>

    </main>


    <!-- FOOTER -->
    <footer class="register-footer">

        © <?= htmlspecialchars($year) ?>
        FROSTCORE. All rights reserved.

        <span>
            STAY COOL. PLAY BETTER.
        </span>

    </footer>


    <script>

        // Show or hide the password.
        function togglePassword(inputId, button) {

            const input = document.getElementById(inputId);

            if (input.type === "password") {

                input.type = "text";
                button.textContent = "HIDE";

            } else {

                input.type = "password";
                button.textContent = "SHOW";

            }

        }

    </script>

</body>
</html>