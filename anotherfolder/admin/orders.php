<?php

// ==================================================
// FROSTCORE ADMIN - ORDERS
// ==================================================

session_start();

require_once "../database/config.php";


// ==================================================
// ADMIN ACCESS
// ==================================================

if (
    empty($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: admin-login.php");
    exit;
}


// ==================================================
// HELPER FUNCTIONS
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
// CSRF TOKEN
// ==================================================

if (empty($_SESSION["admin_csrf_token"])) {

    $_SESSION["admin_csrf_token"] =
        bin2hex(random_bytes(32));

}


// ==================================================
// ORDER STATUSES
// ==================================================

$allowedStatuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Completed"
];


// ==================================================
// MESSAGES
// ==================================================

$successMessage = "";
$errorMessage = "";


// ==================================================
// UPDATE ORDER STATUS
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token =
        $_POST["csrf_token"] ?? "";

    $action =
        $_POST["action"] ?? "";

    $orderId =
        filter_input(
            INPUT_POST,
            "order_id",
            FILTER_VALIDATE_INT
        );

    $newStatus =
        trim(
            $_POST["status"] ?? ""
        );


    // --------------------------------------------------
    // CSRF CHECK
    // --------------------------------------------------

    if (
        empty($token) ||
        empty($_SESSION["admin_csrf_token"]) ||
        !hash_equals(
            $_SESSION["admin_csrf_token"],
            $token
        )
    ) {

        $errorMessage =
            "Invalid request. Please refresh the page.";

    }


    // --------------------------------------------------
    // ACTION CHECK
    // --------------------------------------------------

    elseif ($action !== "update_status") {

        $errorMessage =
            "Invalid action.";

    }


    // --------------------------------------------------
    // ORDER ID CHECK
    // --------------------------------------------------

    elseif (
        !$orderId ||
        $orderId <= 0
    ) {

        $errorMessage =
            "Invalid order.";

    }


    // --------------------------------------------------
    // STATUS CHECK
    // --------------------------------------------------

    elseif (
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $errorMessage =
            "Invalid order status.";

    }


    // --------------------------------------------------
    // UPDATE
    // --------------------------------------------------

    else {

        $updateStmt = $pdo->prepare("
            UPDATE orders
            SET status = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
            $newStatus,
            $orderId
        ]);

        $successMessage =
            "Order status updated successfully.";


        // Regenerate CSRF token.
        $_SESSION["admin_csrf_token"] =
            bin2hex(random_bytes(32));

    }

}


// ==================================================
// STATUS FILTER
// ==================================================

$selectedStatus =
    trim(
        $_GET["status"] ?? ""
    );


if (
    $selectedStatus !== "" &&
    !in_array(
        $selectedStatus,
        $allowedStatuses,
        true
    )
) {

    $selectedStatus = "";

}


// ==================================================
// GET ORDERS
// ==================================================

if ($selectedStatus !== "") {

    $ordersStmt = $pdo->prepare("
        SELECT
            id,
            order_number,
            user_id,
            full_name,
            email,
            phone,
            address,
            city,
            province,
            postal_code,
            payment_method,
            subtotal,
            shipping_fee,
            total,
            status,
            created_at
        FROM orders
        WHERE status = ?
        ORDER BY created_at DESC
    ");

    $ordersStmt->execute([
        $selectedStatus
    ]);

} else {

    $ordersStmt = $pdo->query("
        SELECT
            id,
            order_number,
            user_id,
            full_name,
            email,
            phone,
            address,
            city,
            province,
            postal_code,
            payment_method,
            subtotal,
            shipping_fee,
            total,
            status,
            created_at
        FROM orders
        ORDER BY created_at DESC
    ");

}


$orders =
    $ordersStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==================================================
// VIEW SINGLE ORDER
// ==================================================

$selectedOrder = null;

$selectedOrderItems = [];

$viewId =
    filter_input(
        INPUT_GET,
        "view",
        FILTER_VALIDATE_INT
    );


// --------------------------------------------------
// GET SELECTED ORDER
// --------------------------------------------------

if (
    $viewId &&
    $viewId > 0
) {

    $orderStmt = $pdo->prepare("
        SELECT
            id,
            order_number,
            user_id,
            full_name,
            email,
            phone,
            address,
            city,
            province,
            postal_code,
            payment_method,
            subtotal,
            shipping_fee,
            total,
            status,
            created_at
        FROM orders
        WHERE id = ?
        LIMIT 1
    ");

    $orderStmt->execute([
        $viewId
    ]);

    $selectedOrder =
        $orderStmt->fetch(
            PDO::FETCH_ASSOC
        );


    // --------------------------------------------------
    // GET ITEMS ONLY IF ORDER EXISTS
    // --------------------------------------------------

    if ($selectedOrder) {

        $itemsStmt = $pdo->prepare("
            SELECT
                id,
                product_id,
                product_name,
                price,
                quantity,
                subtotal
            FROM order_items
            WHERE order_id = ?
            ORDER BY id ASC
        ");

        $itemsStmt->execute([
            $viewId
        ]);

        $selectedOrderItems =
            $itemsStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    }

}


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
        FROSTCORE - Admin Orders
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


        html {
            scroll-behavior: smooth;
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

            padding: 0 5%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            background: #070D1C;

            border-bottom:
                1px solid #263452;

            position: sticky;

            top: 0;

            z-index: 100;

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

            padding: 9px 14px;

            color: #4DBCF4;

            border:
                1px solid #4DBCF4;

            font-size: 9px;

            font-weight: 800;

            cursor: pointer;

        }


        .admin-logout:hover {

            background:
                rgba(
                    77,
                    188,
                    244,
                    0.08
                );

        }


        /* ==================================================
           LAYOUT
        ================================================== */

        .admin-layout {

            display: grid;

            grid-template-columns:
                220px 1fr;

            min-height:
                calc(
                    100vh - 72px
                );

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
                clamp(
                    26px,
                    3vw,
                    40px
                );

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
           MESSAGES
        ================================================== */

        .message {

            margin-bottom:
                20px;

            padding:
                13px 15px;

            font-size:
                10px;

        }


        .message.success {

            color:
                #72E38A;

            background:
                rgba(
                    114,
                    227,
                    138,
                    0.08
                );

            border:
                1px solid
                rgba(
                    114,
                    227,
                    138,
                    0.35
                );

        }


        .message.error {

            color:
                #FF9A9A;

            background:
                rgba(
                    255,
                    95,
                    95,
                    0.08
                );

            border:
                1px solid
                rgba(
                    255,
                    95,
                    95,
                    0.35
                );

        }


        /* ==================================================
           FILTERS
        ================================================== */

        .filter-bar {

            display:
                flex;

            flex-wrap:
                wrap;

            gap:
                8px;

            margin-bottom:
                20px;

        }


        .filter-bar a {

            padding:
                9px 13px;

            color:
                #AAB5CA;

            background:
                #081225;

            border:
                1px solid #263452;

            font-size:
                9px;

            font-weight:
                700;

        }


        .filter-bar a:hover,

        .filter-bar a.active {

            color:
                #050A16;

            background:
                #4DBCF4;

            border-color:
                #4DBCF4;

        }


        /* ==================================================
           TABLE
        ================================================== */

        .orders-card {

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


        .orders-table {

            width:
                100%;

            min-width:
                900px;

            border-collapse:
                collapse;

        }


        .orders-table th {

            padding:
                14px 12px;

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


        .orders-table td {

            padding:
                14px 12px;

            color:
                #AAB5CA;

            font-size:
                9px;

            border-bottom:
                1px solid
                rgba(
                    38,
                    52,
                    82,
                    0.7
                );

            vertical-align:
                middle;

        }


        .orders-table tr:last-child td {

            border-bottom:
                none;

        }


        .order-number {

            color:
                #4DBCF4;

            font-weight:
                800;

        }


        .order-total {

            color:
                #F4F7FF;

            font-weight:
                700;

        }


        /* ==================================================
           STATUS
        ================================================== */

        .status {

            display:
                inline-block;

            padding:
                5px 8px;

            font-size:
                7px;

            font-weight:
                800;

            border:
                1px solid;

        }


        .status-pending {

            color:
                #FFD166;

            border-color:
                rgba(
                    255,
                    209,
                    102,
                    0.4
                );

        }


        .status-processing {

            color:
                #4DBCF4;

            border-color:
                rgba(
                    77,
                    188,
                    244,
                    0.4
                );

        }


        .status-shipped {

            color:
                #9B8CFF;

            border-color:
                rgba(
                    155,
                    140,
                    255,
                    0.4
                );

        }


        .status-completed {

            color:
                #72E38A;

            border-color:
                rgba(
                    114,
                    227,
                    138,
                    0.4
                );

        }


        .status-default {

            color:
                #AAB5CA;

            border-color:
                #263452;

        }


        /* ==================================================
           ACTION AREA
        ================================================== */

        .action-area {

            display:
                flex;

            flex-direction:
                column;

            gap:
                6px;

        }


        .view-button {

            display:
                inline-block;

            padding:
                7px 10px;

            text-align:
                center;

            color:
                #AAB5CA;

            border:
                1px solid #263452;

            font-size:
                8px;

            font-weight:
                700;

        }


        .view-button:hover {

            color:
                #4DBCF4;

            border-color:
                #4DBCF4;

        }


        .status-form {

            display:
                flex;

            gap:
                5px;

        }


        .status-form select {

            height:
                32px;

            background:
                #081225;

            color:
                #F4F7FF;

            border:
                1px solid #263452;

            font-size:
                8px;

            outline:
                none;

        }


        .status-form select:focus {

            border-color:
                #4DBCF4;

        }


        .status-form button {

            height:
                32px;

            padding:
                0 8px;

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


        /* ==================================================
           DETAILS
        ================================================== */

        .details-card {

            margin-top:
                20px;

            padding:
                25px;

            background:
                #111A31;

            border:
                1px solid #263452;

            scroll-margin-top:
                95px;

        }


        .details-header {

            display:
                flex;

            align-items:
                flex-start;

            justify-content:
                space-between;

            gap:
                20px;

            margin-bottom:
                25px;

        }


        .details-header h2 {

            margin:
                0 0 6px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                17px;

        }


        .details-header p {

            margin:
                0;

            color:
                #68758D;

            font-size:
                9px;

        }


        .details-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    2,
                    1fr
                );

            gap:
                12px;

            margin-bottom:
                25px;

        }


        .detail-box {

            padding:
                14px;

            background:
                #081225;

            border:
                1px solid #263452;

        }


        .detail-box span {

            display:
                block;

            margin-bottom:
                6px;

            color:
                #68758D;

            font-size:
                8px;

            font-weight:
                700;

        }


        .detail-box strong {

            color:
                #F4F7FF;

            font-size:
                10px;

            word-break:
                break-word;

            line-height:
                1.5;

        }


        .items-title {

            margin:
                0 0 15px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                13px;

        }


        .items-table {

            width:
                100%;

            border-collapse:
                collapse;

            min-width:
                600px;

        }


        .items-table th {

            padding:
                10px;

            color:
                #68758D;

            text-align:
                left;

            font-size:
                8px;

            border-bottom:
                1px solid #263452;

        }


        .items-table td {

            padding:
                10px;

            color:
                #AAB5CA;

            font-size:
                9px;

            border-bottom:
                1px solid
                rgba(
                    38,
                    52,
                    82,
                    0.7
                );

        }


        .items-table tr:last-child td {

            border-bottom:
                none;

        }


        .item-total {

            color:
                #4DBCF4;

            font-weight:
                700;

        }


        .details-summary {

            margin-top:
                20px;

            padding-top:
                20px;

            border-top:
                1px solid #263452;

            display:
                flex;

            justify-content:
                flex-end;

        }


        .details-summary-total {

            color:
                #4DBCF4;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                18px;

            font-weight:
                800;

        }


        .close-button {

            display:
                inline-block;

            margin-top:
                20px;

            padding:
                9px 13px;

            color:
                #AAB5CA;

            border:
                1px solid #263452;

            font-size:
                9px;

        }


        .close-button:hover {

            color:
                #4DBCF4;

            border-color:
                #4DBCF4;

        }


        /* ==================================================
           EMPTY
        ================================================== */

        .empty {

            padding:
                50px 20px;

            text-align:
                center;

            color:
                #68758D;

            font-size:
                10px;

        }


        /* ==================================================
           RESPONSIVE
        ================================================== */

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

        }


        @media (max-width: 600px) {

            .admin-header {

                padding:
                    0 4%;

            }


            .admin-name {

                display:
                    none;

            }


            .admin-brand {

                font-size:
                    14px;

            }


            .admin-brand img {

                width:
                    34px;

                height:
                    34px;

            }


            .details-grid {

                grid-template-columns:
                    1fr;

            }


            .details-header {

                flex-direction:
                    column;

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


        <a
            href="orders.php"
            class="active"
        >
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
                ORDERS
            </h1>

            <p>
                View customer orders and manage order status.
            </p>

        </div>



        <!-- ==================================================
             MESSAGES
        ================================================== -->

        <?php if ($successMessage !== ""): ?>

            <div class="message success">

                <?= e($successMessage) ?>

            </div>

        <?php endif; ?>


        <?php if ($errorMessage !== ""): ?>

            <div class="message error">

                <?= e($errorMessage) ?>

            </div>

        <?php endif; ?>



        <!-- ==================================================
             FILTERS
        ================================================== -->

        <div class="filter-bar">


            <a
                href="orders.php"
                class="<?= $selectedStatus === "" ? "active" : "" ?>"
            >
                ALL
            </a>


            <a
                href="orders.php?status=Pending"
                class="<?= $selectedStatus === "Pending" ? "active" : "" ?>"
            >
                PENDING
            </a>


            <a
                href="orders.php?status=Processing"
                class="<?= $selectedStatus === "Processing" ? "active" : "" ?>"
            >
                PROCESSING
            </a>


            <a
                href="orders.php?status=Shipped"
                class="<?= $selectedStatus === "Shipped" ? "active" : "" ?>"
            >
                SHIPPED
            </a>


            <a
                href="orders.php?status=Completed"
                class="<?= $selectedStatus === "Completed" ? "active" : "" ?>"
            >
                COMPLETED
            </a>

        </div>



        <!-- ==================================================
             ORDERS TABLE
        ================================================== -->

        <section class="orders-card">


            <?php if (!empty($orders)): ?>

                <div class="table-wrap">

                    <table class="orders-table">


                        <thead>

                            <tr>

                                <th>
                                    ORDER
                                </th>

                                <th>
                                    CUSTOMER
                                </th>

                                <th>
                                    PAYMENT
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

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($orders as $order): ?>


                                <?php

                                $status =
                                    (string)$order["status"];


                                $statusClass =
                                    "";


                                if ($status === "Pending") {

                                    $statusClass =
                                        "status-pending";

                                }

                                elseif ($status === "Processing") {

                                    $statusClass =
                                        "status-processing";

                                }

                                elseif ($status === "Shipped") {

                                    $statusClass =
                                        "status-shipped";

                                }

                                elseif ($status === "Completed") {

                                    $statusClass =
                                        "status-completed";

                                }

                                else {

                                    $statusClass =
                                        "status-default";

                                }

                                ?>


                                <tr>


                                    <td>

                                        <span class="order-number">

                                            <?= e(
                                                $order["order_number"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?= e(
                                            $order["full_name"]
                                        ) ?>

                                        <br>

                                        <small
                                            style="
                                                color:#68758D;
                                                font-size:7px;
                                            "
                                        >

                                            <?= e(
                                                $order["email"]
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <?= e(
                                            $order["payment_method"]
                                        ) ?>

                                    </td>


                                    <td>

                                        <span class="order-total">

                                            <?= money(
                                                $order["total"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="status <?= e($statusClass) ?>"
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

                                        <br>

                                        <small
                                            style="
                                                color:#68758D;
                                                font-size:7px;
                                            "
                                        >

                                            <?= e(
                                                date(
                                                    "h:i A",
                                                    strtotime(
                                                        $order["created_at"]
                                                    )
                                                )
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <div class="action-area">


                                            <!-- IMPORTANT:
                                                 #order-details makes the
                                                 browser jump to the details.
                                            -->

                                            <a
                                                href="orders.php?view=<?= (int)$order["id"] ?>#order-details"
                                                class="view-button"
                                            >
                                                VIEW
                                            </a>


                                            <form
                                                method="POST"
                                                class="status-form"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e(
                                                        $_SESSION["admin_csrf_token"]
                                                    ) ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="update_status"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="order_id"
                                                    value="<?= (int)$order["id"] ?>"
                                                >


                                                <select
                                                    name="status"
                                                >

                                                    <?php foreach (
                                                        $allowedStatuses
                                                        as $option
                                                    ): ?>

                                                        <option
                                                            value="<?= e($option) ?>"
                                                            <?= $status === $option
                                                                ? "selected"
                                                                : "" ?>
                                                        >

                                                            <?= e($option) ?>

                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>


                                                <button
                                                    type="submit"
                                                >
                                                    SAVE
                                                </button>

                                            </form>


                                        </div>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty">

                    No orders found.

                </div>


            <?php endif; ?>


        </section>



        <!-- ==================================================
             ORDER DETAILS
        ================================================== -->

        <?php if ($selectedOrder): ?>


            <section
                class="details-card"
                id="order-details"
            >


                <div class="details-header">


                    <div>

                        <h2>

                            ORDER
                            <?= e(
                                $selectedOrder["order_number"]
                            ) ?>

                        </h2>


                        <p>

                            Created:

                            <?= e(
                                date(
                                    "M d, Y h:i A",
                                    strtotime(
                                        $selectedOrder["created_at"]
                                    )
                                )
                            ) ?>

                        </p>

                    </div>


                    <?php

                    $selectedStatus =
                        (string)$selectedOrder["status"];


                    $selectedStatusClass =
                        "status-default";


                    if ($selectedStatus === "Pending") {

                        $selectedStatusClass =
                            "status-pending";

                    }

                    elseif ($selectedStatus === "Processing") {

                        $selectedStatusClass =
                            "status-processing";

                    }

                    elseif ($selectedStatus === "Shipped") {

                        $selectedStatusClass =
                            "status-shipped";

                    }

                    elseif ($selectedStatus === "Completed") {

                        $selectedStatusClass =
                            "status-completed";

                    }

                    ?>


                    <span
                        class="status <?= e(
                            $selectedStatusClass
                        ) ?>"
                    >

                        <?= e(
                            $selectedStatus
                        ) ?>

                    </span>

                </div>



                <!-- ==================================================
                     CUSTOMER DETAILS
                ================================================== -->

                <div class="details-grid">


                    <div class="detail-box">

                        <span>
                            CUSTOMER
                        </span>

                        <strong>

                            <?= e(
                                $selectedOrder["full_name"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            EMAIL
                        </span>

                        <strong>

                            <?= e(
                                $selectedOrder["email"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            PHONE
                        </span>

                        <strong>

                            <?= e(
                                $selectedOrder["phone"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            PAYMENT
                        </span>

                        <strong>

                            <?= e(
                                $selectedOrder["payment_method"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            ADDRESS
                        </span>

                        <strong>

                            <?= e(
                                $selectedOrder["address"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            CITY
                        </span>

                        <strong>

                            <?= e(
                                $selectedOrder["city"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            PROVINCE
                        </span>

                        <strong>

                            <?= e(
                                $selectedOrder["province"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            POSTAL CODE
                        </span>

                        <strong>

                            <?= e(
                                $selectedOrder["postal_code"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            SUBTOTAL
                        </span>

                        <strong>

                            <?= money(
                                $selectedOrder["subtotal"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            SHIPPING
                        </span>

                        <strong>

                            <?php if (
                                (float)$selectedOrder["shipping_fee"] > 0
                            ): ?>

                                <?= money(
                                    $selectedOrder["shipping_fee"]
                                ) ?>

                            <?php else: ?>

                                FREE

                            <?php endif; ?>

                        </strong>

                    </div>


                    <div class="detail-box">

                        <span>
                            TOTAL
                        </span>

                        <strong>

                            <?= money(
                                $selectedOrder["total"]
                            ) ?>

                        </strong>

                    </div>


                </div>



                <!-- ==================================================
                     ORDER ITEMS
                ================================================== -->

                <h3 class="items-title">

                    ORDER ITEMS

                </h3>


                <?php if (!empty($selectedOrderItems)): ?>


                    <div class="table-wrap">

                        <table class="items-table">


                            <thead>

                                <tr>

                                    <th>
                                        PRODUCT
                                    </th>

                                    <th>
                                        PRICE
                                    </th>

                                    <th>
                                        QUANTITY
                                    </th>

                                    <th>
                                        SUBTOTAL
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach (
                                    $selectedOrderItems
                                    as $item
                                ): ?>


                                    <tr>


                                        <td>

                                            <?= e(
                                                $item["product_name"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= money(
                                                $item["price"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= (int)$item["quantity"] ?>

                                        </td>


                                        <td class="item-total">

                                            <?= money(
                                                $item["subtotal"]
                                            ) ?>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty">

                        No order items found.

                    </div>


                <?php endif; ?>



                <!-- TOTAL -->

                <div class="details-summary">

                    <div class="details-summary-total">

                        TOTAL:

                        <?= money(
                            $selectedOrder["total"]
                        ) ?>

                    </div>

                </div>



                <!-- CLOSE -->

                <a
                    href="orders.php"
                    class="close-button"
                >

                    CLOSE DETAILS

                </a>


            </section>


        <?php elseif ($viewId): ?>


            <section
                class="details-card"
                id="order-details"
            >

                <div class="empty">

                    <h2
                        style="
                            margin:0 0 10px;
                            color:#F4F7FF;
                            font-family:Orbitron,sans-serif;
                            font-size:18px;
                        "
                    >

                        ORDER NOT FOUND

                    </h2>


                    <p>

                        Order #<?= (int)$viewId ?>

                        does not exist.

                    </p>

                </div>


                <a
                    href="orders.php"
                    class="close-button"
                >

                    BACK TO ORDERS

                </a>

            </section>


        <?php endif; ?>


    </main>

</div>



</body>

</html>