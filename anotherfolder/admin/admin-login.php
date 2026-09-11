<?php

session_start();

require_once "../database/config.php";

if (
    !empty($_SESSION["user_id"]) &&
    ($_SESSION["role"] ?? "") === "admin"
) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $pdo->prepare("
            SELECT id, full_name, email, password, role
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $user &&
            password_verify($password, $user["password"])
        ) {
            if ($user["role"] !== "admin") {
                $error = "You do not have administrator access.";
            } else {
                session_regenerate_id(true);

                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["username"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FROSTCORE — Admin Login</title>
    <link rel="stylesheet" href="../css/admin/admin-login.css">
</head>

<body>

<div class="admin-login">

    <div class="admin-brand">
        <img
            src="../assets/logo/frostcore_logo.png"
            alt="FROSTCORE Logo"
        >

        <h1>ADMIN LOGIN</h1>
        <p>FROSTCORE Management System</p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="admin-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="admin-login.php">

        <div class="admin-field">
            <label for="email">EMAIL</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter admin email"
                value="<?= e($_POST["email"] ?? "") ?>"
                required
                autocomplete="username"
            >
        </div>

        <div class="admin-field">
            <label for="password">PASSWORD</label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter admin password"
                required
                autocomplete="current-password"
            >
        </div>

        <button type="submit" class="admin-submit">
            SIGN IN AS ADMIN
        </button>

    </form>

    <a href="../index.php" class="back-home">
        ← BACK TO FROSTCORE
    </a>

</div>

</body>
</html>