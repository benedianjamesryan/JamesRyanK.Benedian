<?php

session_start();

require_once "database/config.php";

$error = "";
$fullname = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $terms = isset($_POST["terms"]);

    if (
        $fullname === "" ||
        $email === "" ||
        $password === "" ||
        $confirmPassword === ""
    ) {
        $error = "Please complete all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (!$terms) {
        $error = "Please agree to the Terms & Conditions.";
    } else {
        $checkUser = $pdo->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $checkUser->execute([$email]);
        $existingUser = $checkUser->fetch();

        if ($existingUser) {
            $error = "An account with this email already exists.";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO users
                    (
                        full_name,
                        email,
                        password,
                        role
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'customer'
                    )"
                );

                $stmt->execute([
                    $fullname,
                    $email,
                    $hashedPassword
                ]);

                session_regenerate_id(true);

                $_SESSION["user_id"] = $pdo->lastInsertId();
                $_SESSION["username"] = $fullname;
                $_SESSION["email"] = $email;
                $_SESSION["role"] = "customer";

                header("Location: index.php");
                exit;
            } catch (PDOException $e) {
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
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>FROSTCORE — Create Account</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="css/register.css"
    >
</head>

<body class="register-page">

<main class="register-main">
    <div class="register-card">

        <div class="register-brand">
            <img
                src="assets/logo/frostcore_logo.png"
                alt="FROSTCORE Logo"
                class="register-logo"
            >

            <div class="register-brand-name">
                FROSTCORE
            </div>
        </div>

        <h1>CREATE YOUR ACCOUNT</h1>

        <p class="register-subtitle">
            Join the FROSTCORE experience.
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
            action="register.php"
            method="post"
            class="register-form"
        >
            <div class="form-group">
                <label for="fullname">
                    FULL NAME
                </label>

                <input
                    type="text"
                    id="fullname"
                    name="fullname"
                    placeholder="Enter your full name"
                    value="<?= htmlspecialchars(
                        $fullname,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="register-email">
                    EMAIL
                </label>

                <input
                    type="email"
                    id="register-email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?= htmlspecialchars(
                        $email,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                    required
                >
            </div>

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

            <label class="terms">
                <input
                    type="checkbox"
                    name="terms"
                    required
                >

                <span>
                    I agree to the

                    <a href="#">
                        Terms &amp; Conditions
                    </a>.
                </span>
            </label>

            <button
                type="submit"
                class="btn register-button"
            >
                CREATE ACCOUNT →
            </button>
        </form>

        <p class="register-login">
            Already have an account?

            <a href="login.php">
                SIGN IN
            </a>
        </p>

    </div>
</main>

<footer class="register-footer">
    © <?= htmlspecialchars(
        $year,
        ENT_QUOTES,
        "UTF-8"
    ) ?>

    FROSTCORE.
    All rights reserved.

    <span>
        STAY COOL. PLAY BETTER.
    </span>
</footer>

<script>
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