<?php
$year = date('Y');
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

            <!-- REGISTER FORM -->
            <form action="register.php" method="post" class="register-form">

                <!-- FULL NAME -->
                <div class="form-group">
                    <label for="fullname">FULL NAME</label>

                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        placeholder="Enter your full name"
                        required
                    >
                </div>

                <!-- EMAIL -->
                <div class="form-group">
                    <label for="register-email">EMAIL</label>

                    <input
                        type="email"
                        id="register-email"
                        name="email"
                        placeholder="Enter your email"
                        required
                    >
                </div>

                <!-- PASSWORD -->
                <div class="form-group">
                    <label for="register-password">PASSWORD</label>

                    <div class="register-password">
                        <input
                            type="password"
                            id="register-password"
                            name="password"
                            placeholder="Create a password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('register-password', this)"
                        >
                            SHOW
                        </button>
                    </div>
                </div>

                <!-- CONFIRM PASSWORD -->
                <div class="form-group">
                    <label for="confirm-password">CONFIRM PASSWORD</label>

                    <div class="register-password">
                        <input
                            type="password"
                            id="confirm-password"
                            name="confirm_password"
                            placeholder="Confirm your password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword('confirm-password', this)"
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
                <button type="submit" class="btn register-button">
                    CREATE ACCOUNT →
                </button>

            </form>

            <!-- LOGIN LINK -->
            <p class="register-login">
                Already have an account?
                <a href="login.php">SIGN IN</a>
            </p>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="register-footer">
        © <?= htmlspecialchars($year) ?> FROSTCORE. All rights reserved.
        <span>STAY COOL. PLAY BETTER.</span>
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