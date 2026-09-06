<?php

// ==================================================
// FROSTCORE ADMIN - CUSTOMERS
// ==================================================

session_start();

require_once "../database/config.php";


// ==================================================
// ADMIN ACCESS CHECK
// ==================================================

if (
    empty($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {

    header("Location: admin-login.php");
    exit;

}


// ==================================================
// HELPER FUNCTION
// ==================================================

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


// ==================================================
// ADMIN NAME
// ==================================================

$adminName =
    $_SESSION["username"] ??
    "Administrator";


// ==================================================
// SEARCH
// ==================================================

$search =
    trim($_GET["search"] ?? "");


// ==================================================
// ROLE FILTER
// ==================================================

$roleFilter =
    trim($_GET["role"] ?? "");


$allowedRoles = [
    "customer",
    "admin"
];


if (
    $roleFilter !== "" &&
    !in_array(
        $roleFilter,
        $allowedRoles,
        true
    )
) {

    $roleFilter = "";

}


// ==================================================
// GET CUSTOMERS / USERS
// ==================================================

$sql = "
    SELECT
        id,
        full_name,
        email,
        role
    FROM users
    WHERE 1 = 1
";


$params = [];


// ==================================================
// SEARCH FILTER
// ==================================================

if ($search !== "") {

    $sql .= "
        AND (
            full_name LIKE ?
            OR email LIKE ?
        )
    ";

    $searchTerm =
        "%" . $search . "%";

    $params[] =
        $searchTerm;

    $params[] =
        $searchTerm;

}


// ==================================================
// ROLE FILTER
// ==================================================

if ($roleFilter !== "") {

    $sql .= "
        AND role = ?
    ";

    $params[] =
        $roleFilter;

}


// ==================================================
// ORDER
// ==================================================

$sql .= "
    ORDER BY id ASC
";


// ==================================================
// EXECUTE
// ==================================================

$stmt =
    $pdo->prepare($sql);

$stmt->execute($params);


$users =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==================================================
// TOTAL COUNTS
// ==================================================

$totalUsersStmt =
    $pdo->query("
        SELECT COUNT(*)
        FROM users
    ");

$totalUsers =
    (int)$totalUsersStmt->fetchColumn();


$totalCustomersStmt =
    $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'customer'
    ");

$totalCustomers =
    (int)$totalCustomersStmt->fetchColumn();


$totalAdminsStmt =
    $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE role = 'admin'
    ");

$totalAdmins =
    (int)$totalAdminsStmt->fetchColumn();

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
        FROSTCORE - Customers
    </title>


    <link
        rel="stylesheet"
        href="../product.css"
    >


    <style>

        /* ==================================================
           BASE
        ================================================== */

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            background: #050A16;

            color: #F4F7FF;

            font-family:
                Inter,
                Arial,
                sans-serif;

        }


        a {
            text-decoration: none;
        }


        /* ==================================================
           HEADER
        ================================================== */

        .admin-header {

            min-height: 72px;

            padding:
                0 5%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            background:
                #070D1C;

            border-bottom:
                1px solid #263452;

        }


        .admin-brand {

            display: flex;

            align-items: center;

            gap: 10px;

            color: #F4F7FF;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 18px;

            font-weight: 700;

        }


        .admin-brand img {

            width: 40px;

            height: 40px;

            object-fit: contain;

        }


        .admin-header-right {

            display: flex;

            align-items: center;

            gap: 18px;

        }


        .admin-name {

            color: #AAB5CA;

            font-size: 10px;

        }


        .admin-logout {

            padding:
                9px 14px;

            color:
                #4DBCF4;

            border:
                1px solid #4DBCF4;

            font-size:
                9px;

            font-weight:
                800;

        }


        .admin-logout:hover {

            background:
                rgba(77, 188, 244, 0.08);

        }


        /* ==================================================
           LAYOUT
        ================================================== */

        .admin-layout {

            display: grid;

            grid-template-columns:
                220px 1fr;

            min-height:
                calc(100vh - 72px);

        }


        /* ==================================================
           SIDEBAR
        ================================================== */

        .admin-sidebar {

            padding:
                25px 15px;

            background:
                #0A1223;

            border-right:
                1px solid #263452;

        }


        .sidebar-title {

            margin:
                0 10px 15px;

            color:
                #68758D;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                8px;

            letter-spacing:
                1.5px;

        }


        .admin-sidebar a {

            display:
                block;

            padding:
                11px 12px;

            margin-bottom:
                5px;

            color:
                #AAB5CA;

            font-size:
                10px;

            border:
                1px solid transparent;

        }


        .admin-sidebar a:hover,
        .admin-sidebar a.active {

            color:
                #4DBCF4;

            background:
                #111A31;

            border-color:
                #263452;

        }


        /* ==================================================
           MAIN
        ================================================== */

        .admin-main {

            padding:
                40px;

            overflow-x:
                auto;

        }


        .page-title {

            margin-bottom:
                25px;

        }


        .page-title h1 {

            margin:
                0 0 7px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                clamp(26px, 3vw, 40px);

        }


        .page-title p {

            margin:
                0;

            color:
                #AAB5CA;

            font-size:
                11px;

        }


        /* ==================================================
           STAT CARDS
        ================================================== */

        .stats-grid {

            display:
                grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap:
                15px;

            margin-bottom:
                25px;

        }


        .stat-card {

            padding:
                20px;

            background:
                #111A31;

            border:
                1px solid #263452;

        }


        .stat-label {

            margin-bottom:
                10px;

            color:
                #AAB5CA;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                8px;

        }


        .stat-value {

            color:
                #4DBCF4;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                25px;

            font-weight:
                800;

        }


        /* ==================================================
           SEARCH AREA
        ================================================== */

        .toolbar {

            display:
                flex;

            align-items:
                center;

            gap:
                10px;

            flex-wrap:
                wrap;

            margin-bottom:
                20px;

        }


        .search-form {

            display:
                flex;

            gap:
                7px;

            flex:
                1;

            min-width:
                260px;

        }


        .search-form input {

            flex:
                1;

            height:
                38px;

            padding:
                0 12px;

            background:
                #081225;

            color:
                #F4F7FF;

            border:
                1px solid #263452;

            outline:
                none;

            font-size:
                9px;

        }


        .search-form input:focus {

            border-color:
                #4DBCF4;

        }


        .search-form button {

            height:
                38px;

            padding:
                0 15px;

            color:
                #050A16;

            background:
                #4DBCF4;

            border:
                1px solid #4DBCF4;

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        .filter-link {

            padding:
                10px 13px;

            color:
                #AAB5CA;

            background:
                #081225;

            border:
                1px solid #263452;

            font-size:
                8px;

            font-weight:
                700;

        }


        .filter-link:hover,
        .filter-link.active {

            color:
                #050A16;

            background:
                #4DBCF4;

            border-color:
                #4DBCF4;

        }


        /* ==================================================
           CUSTOMER TABLE
        ================================================== */

        .customers-card {

            background:
                #111A31;

            border:
                1px solid #263452;

        }


        .table-wrap {

            width:
                100%;

            overflow-x:
                auto;

        }


        .customers-table {

            width:
                100%;

            min-width:
                650px;

            border-collapse:
                collapse;

        }


        .customers-table th {

            padding:
                15px 13px;

            text-align:
                left;

            color:
                #68758D;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                8px;

            border-bottom:
                1px solid #263452;

        }


        .customers-table td {

            padding:
                15px 13px;

            color:
                #AAB5CA;

            font-size:
                9px;

            border-bottom:
                1px solid
                rgba(38, 52, 82, 0.7);

        }


        .customers-table tr:last-child td {

            border-bottom:
                none;

        }


        .customer-id {

            color:
                #68758D;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                8px;

        }


        .customer-name {

            color:
                #F4F7FF;

            font-weight:
                700;

        }


        .customer-email {

            color:
                #4DBCF4;

        }


        /* ==================================================
           ROLE
        ================================================== */

        .role-badge {

            display:
                inline-block;

            padding:
                5px 9px;

            font-size:
                7px;

            font-weight:
                800;

            border:
                1px solid;

        }


        .role-customer {

            color:
                #72E38A;

            border-color:
                rgba(114, 227, 138, 0.35);

            background:
                rgba(114, 227, 138, 0.05);

        }


        .role-admin {

            color:
                #4DBCF4;

            border-color:
                rgba(77, 188, 244, 0.35);

            background:
                rgba(77, 188, 244, 0.05);

        }


        /* ==================================================
           EMPTY
        ================================================== */

        .empty {

            padding:
                55px 20px;

            text-align:
                center;

            color:
                #68758D;

            font-size:
                10px;

        }


        /* ==================================================
           NOTE
        ================================================== */

        .admin-note {

            margin-top:
                15px;

            padding:
                13px 15px;

            color:
                #68758D;

            background:
                rgba(77, 188, 244, 0.04);

            border:
                1px solid
                #263452;

            font-size:
                8px;

            line-height:
                1.6;

        }


        /* ==================================================
           RESPONSIVE
        ================================================== */

        @media (max-width: 800px) {

            .admin-layout {

                grid-template-columns:
                    1fr;

            }


            .admin-sidebar {

                border-right:
                    none;

                border-bottom:
                    1px solid #263452;

            }


            .admin-sidebar a {

                display:
                    inline-block;

                margin:
                    2px;

            }


            .admin-main {

                padding:
                    25px;

            }

        }


        @media (max-width: 600px) {

            .stats-grid {

                grid-template-columns:
                    1fr;

            }


            .admin-name {

                display:
                    none;

            }

        }

    </style>

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="admin-header">


    <a
        href="dashboard.php"
        class="admin-brand"
    >

        <img
            src="../assets/frostcore_logo.png"
            alt="FROSTCORE Logo"
        >

        <span>
            FROSTCORE ADMIN
        </span>

    </a>


    <div class="admin-header-right">


        <span class="admin-name">

            Welcome,
            <?= e($adminName) ?>

        </span>


        <a
            href="../logout.php"
            class="admin-logout"
        >
            LOGOUT
        </a>


    </div>

</header>



<!-- ==================================================
     LAYOUT
================================================== -->

<div class="admin-layout">


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="admin-sidebar">


        <div class="sidebar-title">
            MANAGEMENT
        </div>


        <a href="dashboard.php">
            DASHBOARD
        </a>


        <a href="orders.php">
            ORDERS
        </a>


        <a
            href="customers.php"
            class="active"
        >
            CUSTOMERS
        </a>


        <a href="products.php">
            PRODUCTS
        </a>


        <a href="reviews.php">
            REVIEWS
        </a>


        <div class="sidebar-title">
            WEBSITE
        </div>


        <a href="../products.php">
            VIEW STORE
        </a>


        <a href="../index.php">
            HOMEPAGE
        </a>


    </aside>



    <!-- ==================================================
         MAIN
    ================================================== -->

    <main class="admin-main">


        <div class="page-title">

            <h1>
                CUSTOMERS
            </h1>


            <p>
                View registered FROSTCORE accounts and account roles.
            </p>

        </div>



        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-label">
                    TOTAL ACCOUNTS
                </div>

                <div class="stat-value">
                    <?= $totalUsers ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    CUSTOMERS
                </div>

                <div class="stat-value">
                    <?= $totalCustomers ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    ADMINS
                </div>

                <div class="stat-value">
                    <?= $totalAdmins ?>
                </div>

            </div>


        </section>



        <!-- ==================================================
             SEARCH / FILTER
        ================================================== -->

        <div class="toolbar">


            <form
                method="GET"
                action="customers.php"
                class="search-form"
            >


                <input
                    type="text"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Search by name or email..."
                >


                <?php if ($roleFilter !== ""): ?>

                    <input
                        type="hidden"
                        name="role"
                        value="<?= e($roleFilter) ?>"
                    >

                <?php endif; ?>


                <button
                    type="submit"
                >
                    SEARCH
                </button>


            </form>


            <a
                href="customers.php"
                class="
                    filter-link
                    <?= $roleFilter === "" ? "active" : "" ?>
                "
            >
                ALL
            </a>


            <a
                href="customers.php?role=customer"
                class="
                    filter-link
                    <?= $roleFilter === "customer" ? "active" : "" ?>
                "
            >
                CUSTOMERS
            </a>


            <a
                href="customers.php?role=admin"
                class="
                    filter-link
                    <?= $roleFilter === "admin" ? "active" : "" ?>
                "
            >
                ADMINS
            </a>


        </div>



        <!-- ==================================================
             CUSTOMER TABLE
        ================================================== -->

        <section class="customers-card">


            <?php if (!empty($users)): ?>


                <div class="table-wrap">

                    <table class="customers-table">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    FULL NAME
                                </th>

                                <th>
                                    EMAIL
                                </th>

                                <th>
                                    ROLE
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $users as $user
                            ): ?>


                                <?php

                                $role =
                                    (string)$user["role"];

                                $roleClass =
                                    $role === "admin"
                                        ? "role-admin"
                                        : "role-customer";

                                ?>


                                <tr>


                                    <td>

                                        <span
                                            class="customer-id"
                                        >
                                            #<?= (int)$user["id"] ?>
                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="customer-name"
                                        >

                                            <?= e(
                                                $user["full_name"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="customer-email"
                                        >

                                            <?= e(
                                                $user["email"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                role-badge
                                                <?= e(
                                                    $roleClass
                                                ) ?>
                                            "
                                        >

                                            <?= e(
                                                strtoupper($role)
                                            ) ?>

                                        </span>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    No accounts found.

                </div>


            <?php endif; ?>


        </section>



        <!-- ==================================================
             NOTE
        ================================================== -->

        <div class="admin-note">

            Customer passwords are intentionally not displayed.
            This page is currently read-only so the administrator
            cannot accidentally delete or modify accounts.

        </div>


    </main>

</div>


</body>

</html>