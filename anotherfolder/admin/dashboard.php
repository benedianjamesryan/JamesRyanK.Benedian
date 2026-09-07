<?php

// ==================================================
// FROSTCORE ADMIN DASHBOARD
// ==================================================

session_start();

require_once "../database/config.php";


// ==================================================
// ADMIN ACCESS CHECK
// ==================================================

if (
    empty($_SESSION["user_id"]) ||
    empty($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {

    header("Location: admin-login.php");
    exit;
}


// ==================================================
// HELPER
// ==================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


function money($amount)
{
    return "₱" . number_format(
        (float)$amount,
        2
    );
}


// ==================================================
// DASHBOARD STATISTICS
// ==================================================

// --------------------------------------------------
// TOTAL PRODUCTS
// --------------------------------------------------

$productStmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
");

$totalProducts =
    (int)$productStmt->fetchColumn();


// --------------------------------------------------
// TOTAL CUSTOMERS
// --------------------------------------------------

$customerStmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$totalCustomers =
    (int)$customerStmt->fetchColumn();


// --------------------------------------------------
// TOTAL ORDERS
// --------------------------------------------------

$orderStmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
");

$totalOrders =
    (int)$orderStmt->fetchColumn();


// --------------------------------------------------
// PENDING ORDERS
// --------------------------------------------------

$pendingStmt = $pdo->query("
    SELECT COUNT(*)
    FROM orders
    WHERE status = 'Pending'
");

$pendingOrders =
    (int)$pendingStmt->fetchColumn();


// --------------------------------------------------
// TOTAL ORDER VALUE
// --------------------------------------------------

$totalValueStmt = $pdo->query("
    SELECT COALESCE(SUM(total), 0)
    FROM orders
");

$totalOrderValue =
    (float)$totalValueStmt->fetchColumn();


// --------------------------------------------------
// OUT OF STOCK PRODUCTS
// --------------------------------------------------

$outStockStmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE stock <= 0
");

$outOfStock =
    (int)$outStockStmt->fetchColumn();


// ==================================================
// RECENT ORDERS
// ==================================================

$recentStmt = $pdo->query("
    SELECT
        id,
        order_number,
        full_name,
        total,
        status,
        created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 8
");

$recentOrders =
    $recentStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==================================================
// LOW STOCK PRODUCTS
// ==================================================

$lowStockStmt = $pdo->query("
    SELECT
        id,
        name,
        stock,
        price
    FROM products
    WHERE stock <= 5
    ORDER BY stock ASC, name ASC
    LIMIT 6
");

$lowStockProducts =
    $lowStockStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==================================================
// ADMIN NAME
// ==================================================

$adminName =
    $_SESSION["username"] ??
    "Administrator";

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
        FROSTCORE — Admin Dashboard
    </title>


    <link
        rel="stylesheet"
        href="../css/products.css"
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
           ADMIN HEADER
        ================================================== */

        .admin-header {

            min-height: 72px;

            padding:
                0 5%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            border-bottom:
                1px solid #263452;

            background:
                #070D1C;

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

            border:
                1px solid #4DBCF4;

            color: #4DBCF4;

            font-size: 9px;

            font-weight: 800;

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


        .admin-sidebar-title {

            margin:
                0 10px 15px;

            color:
                #68758D;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 8px;

            letter-spacing:
                1.5px;

        }


        .admin-sidebar a {

            display: block;

            padding:
                11px 12px;

            margin-bottom:
                5px;

            color:
                #AAB5CA;

            font-size: 10px;

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
           MAIN CONTENT
        ================================================== */

        .admin-main {

            padding:
                40px;

            overflow:
                auto;

        }


        .admin-page-title {

            margin-bottom:
                30px;

        }


        .admin-page-title h1 {

            margin:
                0 0 7px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                clamp(26px, 3vw, 40px);

        }


        .admin-page-title p {

            margin: 0;

            color:
                #AAB5CA;

            font-size: 11px;

        }


        /* ==================================================
           STAT CARDS
        ================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(5, 1fr);

            gap: 15px;

            margin-bottom:
                30px;

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

            font-size: 8px;

        }


        .stat-value {

            color:
                #4DBCF4;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 24px;

            font-weight: 800;

        }


        .stat-note {

            margin-top:
                7px;

            color:
                #68758D;

            font-size: 8px;

        }


        /* ==================================================
           CONTENT GRID
        ================================================== */

        .content-grid {

            display: grid;

            grid-template-columns:
                1.5fr 1fr;

            gap: 20px;

        }


        .admin-card {

            margin-bottom:
                20px;

            padding:
                22px;

            background:
                #111A31;

            border:
                1px solid #263452;

        }


        .admin-card h2 {

            margin:
                0 0 20px;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 14px;

        }


        /* ==================================================
           TABLE
        ================================================== */

        .admin-table-wrap {

            overflow-x:
                auto;

        }


        .admin-table {

            width: 100%;

            border-collapse:
                collapse;

        }


        .admin-table th {

            padding:
                10px;

            text-align:
                left;

            color:
                #68758D;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 8px;

            border-bottom:
                1px solid #263452;

        }


        .admin-table td {

            padding:
                11px 10px;

            color:
                #AAB5CA;

            font-size: 9px;

            border-bottom:
                1px solid
                rgba(38, 52, 82, 0.7);

        }


        .admin-table tr:last-child td {

            border-bottom:
                none;

        }


        .order-number {

            color:
                #4DBCF4;

            font-weight:
                700;

        }


        .order-total {

            color:
                #F4F7FF;

            font-weight:
                700;

        }


        /* ==================================================
           STATUS BADGES
        ================================================== */

        .status {

            display:
                inline-block;

            padding:
                5px 8px;

            border:
                1px solid #263452;

            font-size:
                7px;

            font-weight:
                800;

        }


        .status-pending {

            color:
                #FFD166;

            border-color:
                rgba(255, 209, 102, 0.35);

        }


        .status-processing {

            color:
                #4DBCF4;

            border-color:
                rgba(77, 188, 244, 0.35);

        }


        .status-shipped {

            color:
                #9B8CFF;

            border-color:
                rgba(155, 140, 255, 0.35);

        }


        .status-completed {

            color:
                #72E38A;

            border-color:
                rgba(114, 227, 138, 0.35);

        }


        .status-default {

            color:
                #AAB5CA;

        }


        /* ==================================================
           LOW STOCK
        ================================================== */

        .low-stock-item {

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            padding:
                12px 0;

            border-bottom:
                1px solid
                rgba(38, 52, 82, 0.7);

        }


        .low-stock-item:last-child {

            border-bottom:
                none;

        }


        .low-stock-name {

            color:
                #F4F7FF;

            font-size:
                10px;

        }


        .low-stock-price {

            margin-top:
                3px;

            color:
                #68758D;

            font-size:
                8px;

        }


        .stock-number {

            color:
                #FF7F8F;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                11px;

            font-weight:
                800;

        }


        .stock-ok {

            color:
                #72E38A;

        }


        /* ==================================================
           QUICK ACTIONS
        ================================================== */

        .quick-actions {

            display:
                grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap:
                10px;

        }


        .quick-action {

            padding:
                13px;

            text-align:
                center;

            background:
                #081225;

            border:
                1px solid #263452;

            color:
                #AAB5CA;

            font-size:
                9px;

            font-weight:
                700;

        }


        .quick-action:hover {

            color:
                #4DBCF4;

            border-color:
                #4DBCF4;

        }


        /* ==================================================
           EMPTY STATE
        ================================================== */

        .empty-state {

            padding:
                25px 0;

            color:
                #68758D;

            font-size:
                10px;

            text-align:
                center;

        }


        /* ==================================================
           RESPONSIVE
        ================================================== */

        @media (max-width: 1100px) {

            .stats-grid {

                grid-template-columns:
                    repeat(3, 1fr);

            }

        }


        @media (max-width: 900px) {

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


            .content-grid {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 600px) {

            .stats-grid {

                grid-template-columns:
                    1fr 1fr;

            }


            .admin-header {

                padding:
                    0 4%;

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
     ADMIN HEADER
================================================== -->

<header class="admin-header">


    <a
        href="dashboard.php"
        class="admin-brand"
    >

        <img
            src="../assets/logo/frostcore_logo.png"
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
    onclick="return confirm('Are you sure you want to log out of your FROSTCORE administrator account?');"
>
    LOGOUT
</a>

    </div>

</header>



<!-- ==================================================
     ADMIN LAYOUT
================================================== -->

<div class="admin-layout">


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="admin-sidebar">


        <div class="admin-sidebar-title">
            MANAGEMENT
        </div>


        <a
            href="dashboard.php"
            class="active"
        >
            DASHBOARD
        </a>


        <a href="orders.php">
            ORDERS
        </a>


        <a href="customers.php">
            CUSTOMERS
        </a>


        <a href="products.php">
            PRODUCTS
        </a>


        <a href="reviews.php">
            REVIEWS
        </a>


        <div class="admin-sidebar-title">
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


        <!-- PAGE TITLE -->

        <div class="admin-page-title">

            <h1>
                DASHBOARD
            </h1>


            <p>
                FROSTCORE system overview and management.
            </p>

        </div>



        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-label">
                    PRODUCTS
                </div>

                <div class="stat-value">
                    <?= $totalProducts ?>
                </div>

                <div class="stat-note">
                    Total products
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    CUSTOMERS
                </div>

                <div class="stat-value">
                    <?= $totalCustomers ?>
                </div>

                <div class="stat-note">
                    Registered customers
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    ORDERS
                </div>

                <div class="stat-value">
                    <?= $totalOrders ?>
                </div>

                <div class="stat-note">
                    All orders
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    PENDING
                </div>

                <div class="stat-value">
                    <?= $pendingOrders ?>
                </div>

                <div class="stat-note">
                    Awaiting processing
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    ORDER VALUE
                </div>

                <div
                    class="stat-value"
                    style="font-size:18px;"
                >
                    <?= money($totalOrderValue) ?>
                </div>

                <div class="stat-note">
                    Total recorded order value
                </div>

            </div>

        </section>



        <!-- ==================================================
             CONTENT
        ================================================== -->

        <div class="content-grid">


            <!-- ==================================================
                 RECENT ORDERS
            ================================================== -->

            <section class="admin-card">


                <h2>
                    RECENT ORDERS
                </h2>


                <?php if (!empty($recentOrders)): ?>


                    <div class="admin-table-wrap">

                        <table class="admin-table">


                            <thead>

                                <tr>

                                    <th>
                                        ORDER
                                    </th>

                                    <th>
                                        CUSTOMER
                                    </th>

                                    <th>
                                        TOTAL
                                    </th>

                                    <th>
                                        STATUS
                                    </th>

                                    <th>
                                        DATE
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $recentOrders
                                    as $order
                                ): ?>


                                    <?php

                                    $status =
                                        (string)$order["status"];

                                    $statusClass =
                                        match ($status) {

                                            "Pending" =>
                                                "status-pending",

                                            "Processing" =>
                                                "status-processing",

                                            "Shipped" =>
                                                "status-shipped",

                                            "Completed" =>
                                                "status-completed",

                                            default =>
                                                "status-default"

                                        };

                                    ?>


                                    <tr>


                                        <td>

                                            <span
                                                class="order-number"
                                            >
                                                <?= e(
                                                    $order["order_number"]
                                                ) ?>
                                            </span>

                                        </td>


                                        <td>

                                            <?= e(
                                                $order["full_name"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <span
                                                class="order-total"
                                            >
                                                <?= money(
                                                    $order["total"]
                                                ) ?>
                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="status <?= $statusClass ?>"
                                            >

                                                <?= e(
                                                    $status
                                                ) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?= e(
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $order["created_at"]
                                                    )
                                                )
                                            ) ?>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty-state">
                        No orders yet.
                    </div>


                <?php endif; ?>


            </section>



            <!-- ==================================================
                 LOW STOCK
            ================================================== -->

            <section class="admin-card">


                <h2>
                    LOW STOCK
                </h2>


                <?php if (!empty($lowStockProducts)): ?>


                    <?php foreach (
                        $lowStockProducts
                        as $product
                    ): ?>


                        <div
                            class="low-stock-item"
                        >


                            <div>

                                <div
                                    class="low-stock-name"
                                >
                                    <?= e(
                                        $product["name"]
                                    ) ?>
                                </div>


                                <div
                                    class="low-stock-price"
                                >
                                    <?= money(
                                        $product["price"]
                                    ) ?>
                                </div>

                            </div>


                            <div
                                class="stock-number"
                            >

                                <?= (int)$product["stock"] ?>

                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="empty-state">

                        All products have
                        more than 5 units.

                    </div>


                <?php endif; ?>


                <div
                    style="
                        margin-top:15px;
                        color:#68758D;
                        font-size:8px;
                    "
                >

                    <?= $outOfStock ?>
                    product(s) currently out of stock.

                </div>


            </section>



            <!-- ==================================================
                 QUICK ACTIONS
            ================================================== -->

            <section class="admin-card">


                <h2>
                    QUICK ACTIONS
                </h2>


                <div class="quick-actions">


                    <a
                        href="orders.php"
                        class="quick-action"
                    >
                        VIEW ORDERS
                    </a>


                    <a
                        href="products.php"
                        class="quick-action"
                    >
                        MANAGE PRODUCTS
                    </a>


                    <a
                        href="customers.php"
                        class="quick-action"
                    >
                        VIEW CUSTOMERS
                    </a>


                    <a
                        href="reviews.php"
                        class="quick-action"
                    >
                        MANAGE REVIEWS
                    </a>

                </div>


            </section>



            <!-- ==================================================
                 ADMIN INFO
            ================================================== -->

            <section class="admin-card">


                <h2>
                    SYSTEM STATUS
                </h2>


                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                        color:#72E38A;
                        font-size:10px;
                    "
                >

                    <span>
                        ●
                    </span>

                    System operational

                </div>


                <p
                    style="
                        margin:12px 0 0;
                        color:#68758D;
                        font-size:9px;
                        line-height:1.6;
                    "
                >

                    FROSTCORE administration
                    panel is connected to the
                    database and ready for management.

                </p>

            </section>


        </div>


    </main>

</div>
<?php require_once "../includes/logout-popup.php"; ?>

</body>

</html>