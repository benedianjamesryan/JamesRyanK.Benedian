<?php

// ==================================================
// FROSTCORE ADMIN LOGIN
// ==================================================

session_start();

require_once "../database/config.php";


// ==================================================
// ALREADY LOGGED IN AS ADMIN?
// ==================================================

if (
    !empty($_SESSION["user_id"]) &&
    isset($_SESSION["role"]) &&
    $_SESSION["role"] === "admin"
) {

    header("Location: dashboard.php");
    exit;

}


// ==================================================
// VARIABLES
// ==================================================

$error = "";


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
        // FIND ACCOUNT
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
            $user &&
            password_verify(
                $password,
                $user["password"]
            )
        ) {


            // --------------------------------------------------
            // CHECK ADMIN ROLE
            // --------------------------------------------------

            if ($user["role"] !== "admin") {

                $error =
                    "You do not have administrator access.";

            }

            else {

                // --------------------------------------------------
                // REGENERATE SESSION
                // --------------------------------------------------

                session_regenerate_id(true);


                // --------------------------------------------------
                // SAVE ADMIN SESSION
                // --------------------------------------------------

                $_SESSION["user_id"] =
                    (int)$user["id"];

                $_SESSION["username"] =
                    $user["full_name"];

                $_SESSION["email"] =
                    $user["email"];

                $_SESSION["role"] =
                    $user["role"];


                // --------------------------------------------------
                // REDIRECT
                // --------------------------------------------------

                header(
                    "Location: dashboard.php"
                );

                exit;

            }

        }

        else {

            $error =
                "Invalid email or password.";

        }

    }

}


// ==================================================
// HELPER FUNCTION
// ==================================================

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        FROSTCORE — Admin Login
    </title>


    <link
        rel="stylesheet"
        href="../product.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            min-height: 100vh;

            margin: 0;

            padding: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #050A16;

            color: #F4F7FF;

            font-family:
                Inter,
                Arial,
                sans-serif;

        }


        .admin-login {

            width: 100%;

            max-width: 420px;

            padding: 40px;

            background: #111A31;

            border:
                1px solid #263452;

            box-shadow:
                0 0 35px
                rgba(0, 215, 255, 0.08);

        }


        .admin-brand {

            text-align: center;

            margin-bottom: 30px;

        }


        .admin-brand img {

            width: 70px;

            height: 70px;

            object-fit: contain;

            margin:
                0 auto 15px;

        }


        .admin-brand h1 {

            margin: 0;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 24px;

            letter-spacing: 1px;

        }


        .admin-brand p {

            margin:
                8px 0 0;

            color: #AAB5CA;

            font-size: 11px;

        }


        .admin-error {

            margin-bottom: 18px;

            padding: 12px;

            background:
                rgba(255, 95, 95, 0.08);

            border:
                1px solid #ff5f5f;

            color: #ff9a9a;

            font-size: 10px;

            line-height: 1.5;

        }


        .admin-field {

            margin-bottom: 18px;

        }


        .admin-field label {

            display: block;

            margin-bottom: 7px;

            color: #AAB5CA;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1px;

        }


        .admin-field input {

            width: 100%;

            height: 44px;

            padding: 0 12px;

            background: #081225;

            border:
                1px solid #263452;

            color: #F4F7FF;

            outline: none;

            font-size: 11px;

        }


        .admin-field input:focus {

            border-color:
                #4DBCF4;

            box-shadow:
                0 0 10px
                rgba(77, 188, 244, 0.08);

        }


        .admin-submit {

            width: 100%;

            height: 45px;

            border:
                1px solid #4DBCF4;

            background:
                #4DBCF4;

            color:
                #050A16;

            font-size: 10px;

            font-weight: 800;

            cursor: pointer;

        }


        .admin-submit:hover {

            filter:
                brightness(1.08);

        }


        .back-home {

            display: block;

            margin-top: 18px;

            text-align: center;

            color: #AAB5CA;

            font-size: 9px;

            text-decoration: none;

        }


        .back-home:hover {

            color:
                #4DBCF4;

        }

    </style>

</head>


<body>


<div class="admin-login">


    <!-- ==================================================
         BRAND
    ================================================== -->

    <div class="admin-brand">

        <img
            src="../assets/frostcore_logo.png"
            alt="FROSTCORE Logo"
        >

        <h1>
            ADMIN LOGIN
        </h1>

        <p>
            FROSTCORE Management System
        </p>

    </div>


    <!-- ==================================================
         ERROR MESSAGE
    ================================================== -->

    <?php if ($error !== ""): ?>

        <div class="admin-error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <!-- ==================================================
         LOGIN FORM
    ================================================== -->

    <form
        method="POST"
        action="admin-login.php"
    >


        <div class="admin-field">

            <label for="email">
                EMAIL
            </label>

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

            <label for="password">
                PASSWORD
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter admin password"
                required
                autocomplete="current-password"
            >

        </div>


        <button
            type="submit"
            class="admin-submit"
        >
            SIGN IN AS ADMIN
        </button>


    </form>


    <a
        href="../index.php"
        class="back-home"
    >
        ← BACK TO FROSTCORE
    </a>


</div>


</body>

</html>