<?php

// ==================================================
// FROSTCORE — MY ORDERS
// ==================================================

session_start();

require_once "database/config.php";


// ==================================================
// CUSTOMER LOGIN CHECK
// ==================================================

if (empty($_SESSION["user_id"])) {

    header("Location: login.php?redirect=my-orders.php");
    exit;

}

$userId = (int)$_SESSION["user_id"];


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
// GET CUSTOMER NAME
// ==================================================

$customerName =
    $_SESSION["username"] ??
    "Customer";


// ==================================================
// GET CART COUNT
// ==================================================

$cartCount = 0;

$cartCountStmt = $pdo->prepare("
    SELECT COALESCE(SUM(quantity), 0)
    FROM cart_items
    WHERE user_id = ?
");

$cartCountStmt->execute([
    $userId
]);

$cartCount =
    (int)$cartCountStmt->fetchColumn();


// ==================================================
// ALLOWED STATUSES
// ==================================================

$allowedStatuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Completed"
];


// ==================================================
// GET SELECTED ORDER
// ==================================================

$viewId =
    filter_input(
        INPUT_GET,
        "view",
        FILTER_VALIDATE_INT
    );

$selectedOrder = null;

$selectedItems = [];


// --------------------------------------------------
// IMPORTANT:
// user_id is included in the query so one customer
// cannot view another customer's order by changing
// the URL.
// --------------------------------------------------

if (
    $viewId &&
    $viewId > 0
) {

    $orderStmt = $pdo->prepare("
        SELECT
            id,
            order_number,
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
        AND user_id = ?
        LIMIT 1
    ");

    $orderStmt->execute([
        $viewId,
        $userId
    ]);

    $selectedOrder =
        $orderStmt->fetch(
            PDO::FETCH_ASSOC
        );


    // --------------------------------------------------
    // GET ITEMS
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

        $selectedItems =
            $itemsStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    }

}


// ==================================================
// GET ALL CUSTOMER ORDERS
// ==================================================

$ordersStmt = $pdo->prepare("
    SELECT
        id,
        order_number,
        subtotal,
        shipping_fee,
        total,
        payment_method,
        status,
        created_at
    FROM orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$ordersStmt->execute([
    $userId
]);

$orders =
    $ordersStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

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
        FROSTCORE — My Orders
    </title>


    <link
        rel="stylesheet"
        href="css/products.css"
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

            background: #050A16;

            color: #F4F7FF;

            font-family:
                Inter,
                Arial,
                sans-serif;

        }


        a {
            color: inherit;
            text-decoration: none;
        }


        /* ==================================================
           HEADER
        ================================================== */
        .orders-header {

            height: 72px;

            padding:
                0 5.5%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            background:
                rgba(5, 10, 22, 0.96);

            border-bottom:
                1px solid #263452;

            position: sticky;

            top: 0;

            z-index: 9999;

            backdrop-filter:
                blur(12px);

        }


        .orders-brand {

            display: flex;

            align-items: center;

            gap: 10px;

            flex-shrink: 0;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 18px;

            font-weight: 700;

        }


        .orders-brand img {

            width: 40px;

            height: 40px;

            object-fit: contain;

        }


        .orders-nav {

            display: flex;

            align-items: center;

            gap: 45px;

        }


        .orders-nav a {

            color: #F4F7FF;

            font-size: 11px;

            font-weight: 600;

            text-decoration: none;

            transition:
                color 0.2s ease;

        }


        .orders-nav a:hover {

            color: #4DBCF4;

        }


        .orders-nav a.active {

            color: #4DBCF4;

        }


        .orders-actions {

            display: flex;

            align-items: center;

            gap: 20px;

        }


        .orders-actions a {

            color: #F4F7FF;

            font-size: 10px;

            font-weight: 700;

            text-decoration: none;

        }


        .orders-actions a:hover {

            color: #4DBCF4;

        }


        .orders-cart {

            position: relative;

            font-size: 20px !important;

            color: #F4F7FF !important;

        }


        .orders-cart-count {

            position: absolute;

            top: -9px;

            right: -10px;

            width: 18px;

            height: 18px;

            display: grid;

            place-items: center;

            border-radius: 50%;

            background: #4DBCF4;

            color: #050A16;

            font-size: 8px;

            font-weight: 800;

        }


        .admin-link {

            padding:
                7px 10px;

            color: #4DBCF4 !important;

            border:
                1px solid #4DBCF4;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 8px !important;

            font-weight: 800 !important;

        }


        .admin-link:hover {

            background:
                rgba(
                    77,
                    188,
                    244,
                    0.08
                );

        }


        /* ==================================================
           MAIN
        ================================================== */

        .orders-page {

            width: 82%;

            max-width: 1120px;

            margin:
                0 auto;

            padding:
                65px 0 90px;

        }


        .orders-title {

            margin-bottom: 35px;

        }


        .orders-title h1 {

            margin:
                0 0 8px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                clamp(30px, 4vw, 48px);

        }


        .orders-title p {

            margin: 0;

            color: #AAB5CA;

            font-size: 11px;

        }


        /* ==================================================
           ORDER LIST
        ================================================== */

        .orders-list {

            display: grid;

            gap: 15px;

        }


        .order-card {

            padding: 22px;

            background: #111A31;

            border:
                1px solid #263452;

            transition:
                border-color 0.2s ease;

        }


        .order-card:hover {

            border-color:
                rgba(77, 188, 244, 0.5);

        }


        .order-card-top {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 18px;

        }


        .order-number {

            color: #4DBCF4;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 12px;

            font-weight: 800;

        }


        .order-date {

            margin-top: 5px;

            color: #68758D;

            font-size: 8px;

        }


        /* ==================================================
           STATUS BADGES
        ================================================== */

        .status {

            display: inline-block;

            padding:
                6px 10px;

            border: 1px solid;

            font-size: 8px;

            font-weight: 800;

            white-space: nowrap;

        }


        .status-pending {

            color: #FFD166;

            border-color:
                rgba(255, 209, 102, 0.4);

            background:
                rgba(255, 209, 102, 0.05);

        }


        .status-processing {

            color: #4DBCF4;

            border-color:
                rgba(77, 188, 244, 0.4);

            background:
                rgba(77, 188, 244, 0.05);

        }


        .status-shipped {

            color: #9B8CFF;

            border-color:
                rgba(155, 140, 255, 0.4);

            background:
                rgba(155, 140, 255, 0.05);

        }


        .status-completed {

            color: #72E38A;

            border-color:
                rgba(114, 227, 138, 0.4);

            background:
                rgba(114, 227, 138, 0.05);

        }


        /* ==================================================
           ORDER INFO
        ================================================== */

        .order-info {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 10px;

            margin-bottom: 18px;

        }


        .order-info-box {

            padding:
                12px;

            background: #081225;

            border:
                1px solid #263452;

        }


        .order-info-box span {

            display: block;

            margin-bottom: 5px;

            color: #68758D;

            font-size: 7px;

            font-weight: 700;

            letter-spacing:
                0.5px;

        }


        .order-info-box strong {

            color: #F4F7FF;

            font-size: 10px;

        }


        /* ==================================================
           VIEW BUTTON
        ================================================== */

        .view-order {

            display: inline-block;

            padding:
                9px 13px;

            color: #4DBCF4;

            border:
                1px solid #4DBCF4;

            font-size: 8px;

            font-weight: 800;

        }


        .view-order:hover {

            background:
                rgba(77, 188, 244, 0.08);

        }


        /* ==================================================
           EMPTY
        ================================================== */

        .empty-orders {

            padding:
                60px 30px;

            text-align: center;

            background: #111A31;

            border:
                1px solid #263452;

        }


        .empty-orders h2 {

            margin:
                0 0 10px;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 19px;

        }


        .empty-orders p {

            margin:
                0 0 20px;

            color: #68758D;

            font-size: 10px;

        }


        .shop-button {

            display: inline-block;

            padding:
                11px 18px;

            background:
                #4DBCF4;

            color: #050A16;

            font-size: 9px;

            font-weight: 800;

        }


        /* ==================================================
           ORDER DETAILS
        ================================================== */

        .order-details {

            margin-top: 30px;

            padding: 28px;

            background: #111A31;

            border:
                1px solid #263452;

        }


        .details-heading {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 25px;

        }


        .details-heading h2 {

            margin: 0 0 6px;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 20px;

        }


        .details-heading p {

            margin: 0;

            color: #68758D;

            font-size: 9px;

        }


        /* ==================================================
           STATUS TRACKER
        ================================================== */

        .status-tracker {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            margin:
                30px 0 35px;

        }


        .tracker-step {

            position: relative;

            text-align: center;

        }


        .tracker-step:not(:last-child)::after {

            content: "";

            position: absolute;

            top: 14px;

            left: 50%;

            width: 100%;

            height: 2px;

            background:
                #263452;

            z-index: 0;

        }


        .tracker-dot {

            position: relative;

            z-index: 1;

            width: 28px;

            height: 28px;

            margin:
                0 auto 8px;

            display: grid;

            place-items: center;

            border:
                2px solid #263452;

            border-radius: 50%;

            background:
                #081225;

            color:
                #68758D;

            font-size: 9px;

            font-weight: 800;

        }


        .tracker-step.done .tracker-dot,
        .tracker-step.current .tracker-dot {

            border-color:
                #4DBCF4;

            background:
                #4DBCF4;

            color:
                #050A16;

        }


        .tracker-step.done:not(:last-child)::after {

            background:
                #4DBCF4;

        }


        .tracker-label {

            color:
                #68758D;

            font-size: 8px;

            font-weight: 700;

        }


        .tracker-step.done .tracker-label,
        .tracker-step.current .tracker-label {

            color:
                #4DBCF4;

        }


        /* ==================================================
           DETAILS GRID
        ================================================== */

        .details-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 12px;

            margin-bottom: 25px;

        }


        .details-box {

            padding: 14px;

            background: #081225;

            border:
                1px solid #263452;

        }


        .details-box span {

            display: block;

            margin-bottom: 6px;

            color: #68758D;

            font-size: 8px;

            font-weight: 700;

        }


        .details-box strong {

            color: #F4F7FF;

            font-size: 10px;

            line-height: 1.5;

            word-break: break-word;

        }


        /* ==================================================
           ITEMS
        ================================================== */

        .items-heading {

            margin:
                0 0 15px;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 14px;

        }


        .items-table-wrap {

            overflow-x:
                auto;

        }


        .items-table {

            width: 100%;

            border-collapse:
                collapse;

            min-width: 550px;

        }


        .items-table th {

            padding: 10px;

            text-align: left;

            color: #68758D;

            font-size: 8px;

            border-bottom:
                1px solid #263452;

        }


        .items-table td {

            padding: 12px 10px;

            color: #AAB5CA;

            font-size: 9px;

            border-bottom:
                1px solid
                rgba(38, 52, 82, 0.7);

        }


        .items-table tr:last-child td {

            border-bottom: none;

        }


        .item-total {

            color: #4DBCF4 !important;

            font-weight: 700;

        }


        .details-footer {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-top: 25px;

            padding-top: 20px;

            border-top:
                1px solid #263452;

        }


        .back-orders {

            color: #AAB5CA;

            font-size: 9px;

            font-weight: 700;

        }


        .back-orders:hover {

            color: #4DBCF4;

        }


        .grand-total {

            color: #4DBCF4;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 18px;

            font-weight: 800;

        }


        /* ==================================================
           RESPONSIVE
        ================================================== */

        @media (max-width: 850px) {

            .orders-nav {

                display: none;

            }


            .orders-page {

                width: 90%;

            }


            .order-info {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 600px) {

            .orders-header {

                padding:
                    0 4%;

            }


            .orders-brand {

                font-size: 14px;

            }


            .orders-brand img {

                width: 34px;

                height: 34px;

            }


            .orders-page {

                padding:
                    45px 0 60px;

            }


            .order-card-top,
            .details-heading,
            .details-footer {

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }


            .order-info {

                grid-template-columns:
                    1fr;

            }


            .details-grid {

                grid-template-columns:
                    1fr;

            }


            .status-tracker {

                grid-template-columns:
                    1fr;

                gap: 15px;

            }


            .tracker-step {

                display: flex;

                align-items: center;

                text-align: left;

                gap: 10px;

            }


            .tracker-dot {

                flex-shrink: 0;

                margin: 0;

            }


            .tracker-step::after {

                display: none;

            }

        }

    </style>

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="orders-header">


    <a
        href="index.php"
        class="orders-brand"
    >

        <img
            src="assets/logo/frostcore_logo.png"
            alt="FROSTCORE Logo"
        >

        <span>
            FROSTCORE
        </span>

    </a>


    <nav class="orders-nav">

    <a href="index.php">
        HOME
    </a>

    <a href="products.php">
        PRODUCTS
    </a>

    <a
        href="my-orders.php"
        class="active"
    >
        MY ORDERS
    </a>

    <a href="about.php">
        ABOUT US
    </a>

    <a href="contact.php">
        CONTACT
    </a>

</nav>

    <div class="orders-actions">


        <?php if (
            !empty($_SESSION["user_id"]) &&
            ($_SESSION["role"] ?? "") === "admin"
        ): ?>

            <a
                href="admin/dashboard.php"
                class="admin-link"
            >
                ADMIN
            </a>

        <?php endif; ?>


            <a
               href="#"
               class="login-link logout-button"
               id="logoutButton"
               title="Logout"
          >
                LOGOUT
            </a>


        <a
            href="cart.php"
            class="orders-cart"
        >

            🛒

            <span class="orders-cart-count">
                <?= $cartCount ?>
            </span>

        </a>


    </div>

</header>



<!-- ==================================================
     MAIN
================================================== -->

<main class="orders-page">


    <div class="orders-title">

        <h1>
            MY ORDERS
        </h1>


        <p>

            Welcome back,
            <?= e($customerName) ?>.
            Here you can track your FROSTCORE orders.

        </p>

    </div>



    <!-- ==================================================
         ORDER LIST
    ================================================== -->

    <?php if (!empty($orders)): ?>


        <div class="orders-list">


            <?php foreach ($orders as $order): ?>


                <?php

                $status =
                    (string)$order["status"];


                switch ($status) {

                    case "Pending":

                        $statusClass =
                            "status-pending";

                        break;

                    case "Processing":

                        $statusClass =
                            "status-processing";

                        break;

                    case "Shipped":

                        $statusClass =
                            "status-shipped";

                        break;

                    case "Completed":

                        $statusClass =
                            "status-completed";

                        break;

                    default:

                        $statusClass =
                            "";

                        break;

                }

                ?>


                <article class="order-card">


                    <div class="order-card-top">


                        <div>

                            <div class="order-number">

                                <?= e(
                                    $order["order_number"]
                                ) ?>

                            </div>


                            <div class="order-date">

                                <?= e(
                                    date(
                                        "F d, Y · h:i A",
                                        strtotime(
                                            $order["created_at"]
                                        )
                                    )
                                ) ?>

                            </div>

                        </div>


                        <span
                            class="
                                status
                                <?= e($statusClass) ?>
                            "
                        >

                            <?= e($status) ?>

                        </span>


                    </div>



                    <div class="order-info">


                        <div class="order-info-box">

                            <span>
                                PAYMENT
                            </span>


                            <strong>

                                <?= e(
                                    $order["payment_method"]
                                ) ?>

                            </strong>

                        </div>


                        <div class="order-info-box">

                            <span>
                                SUBTOTAL
                            </span>


                            <strong>

                                <?= money(
                                    $order["subtotal"]
                                ) ?>

                            </strong>

                        </div>


                        <div class="order-info-box">

                            <span>
                                SHIPPING
                            </span>


                            <strong>

                                <?php if (
                                    (float)$order["shipping_fee"] > 0
                                ): ?>

                                    <?= money(
                                        $order["shipping_fee"]
                                    ) ?>

                                <?php else: ?>

                                    FREE

                                <?php endif; ?>

                            </strong>

                        </div>


                        <div class="order-info-box">

                            <span>
                                TOTAL
                            </span>


                            <strong>

                                <?= money(
                                    $order["total"]
                                ) ?>

                            </strong>

                        </div>

                    </div>


                    <a
                        href="my-orders.php?view=<?= (int)$order["id"] ?>"
                        class="view-order"
                    >
                        VIEW ORDER
                    </a>


                </article>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="empty-orders">

            <h2>
                NO ORDERS YET
            </h2>


            <p>
                You haven't placed any FROSTCORE orders yet.
            </p>


            <a
                href="products.php"
                class="shop-button"
            >
                START SHOPPING
            </a>

        </div>


    <?php endif; ?>



    <!-- ==================================================
         SELECTED ORDER DETAILS
    ================================================== -->

    <?php if ($selectedOrder): ?>


        <section class="order-details">


            <div class="details-heading">


                <div>

                    <h2>

                        ORDER

                        <?= e(
                            $selectedOrder["order_number"]
                        ) ?>

                    </h2>


                    <p>

                        Placed on

                        <?= e(
                            date(
                                "F d, Y · h:i A",
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


                switch ($selectedStatus) {

                    case "Pending":

                        $selectedStatusClass =
                            "status-pending";

                        break;

                    case "Processing":

                        $selectedStatusClass =
                            "status-processing";

                        break;

                    case "Shipped":

                        $selectedStatusClass =
                            "status-shipped";

                        break;

                    case "Completed":

                        $selectedStatusClass =
                            "status-completed";

                        break;

                    default:

                        $selectedStatusClass =
                            "";

                        break;

                }

                ?>


                <span
                    class="
                        status
                        <?= e($selectedStatusClass) ?>
                    "
                >

                    <?= e($selectedStatus) ?>

                </span>

            </div>



            <!-- ==================================================
                 STATUS TRACKER
            ================================================== -->

            <?php

            $statusIndexes = [
                "Pending" => 0,
                "Processing" => 1,
                "Shipped" => 2,
                "Completed" => 3
            ];


            $currentIndex =
                $statusIndexes[$selectedStatus] ?? 0;


            $statusLabels = [
                "Pending",
                "Processing",
                "Shipped",
                "Completed"
            ];

            ?>


            <div class="status-tracker">


                <?php foreach (
                    $statusLabels
                    as $index => $label
                ): ?>


                    <?php

                    $stepClass = "";


                    if (
                        $index < $currentIndex
                    ) {

                        $stepClass =
                            "done";

                    }

                    elseif (
                        $index === $currentIndex
                    ) {

                        $stepClass =
                            "current";

                    }

                    ?>


                    <div
                        class="
                            tracker-step
                            <?= e($stepClass) ?>
                        "
                    >


                        <div class="tracker-dot">

                            <?php if (
                                $index < $currentIndex
                            ): ?>

                                ✓

                            <?php else: ?>

                                <?= $index + 1 ?>

                            <?php endif; ?>

                        </div>


                        <div class="tracker-label">

                            <?= e($label) ?>

                        </div>


                    </div>


                <?php endforeach; ?>


            </div>



            <!-- ==================================================
                 CUSTOMER DETAILS
            ================================================== -->

            <div class="details-grid">


                <div class="details-box">

                    <span>
                        CUSTOMER
                    </span>

                    <strong>
                        <?= e(
                            $selectedOrder["full_name"]
                        ) ?>
                    </strong>

                </div>


                <div class="details-box">

                    <span>
                        EMAIL
                    </span>

                    <strong>
                        <?= e(
                            $selectedOrder["email"]
                        ) ?>
                    </strong>

                </div>


                <div class="details-box">

                    <span>
                        PHONE
                    </span>

                    <strong>
                        <?= e(
                            $selectedOrder["phone"]
                        ) ?>
                    </strong>

                </div>


                <div class="details-box">

                    <span>
                        PAYMENT METHOD
                    </span>

                    <strong>
                        <?= e(
                            $selectedOrder["payment_method"]
                        ) ?>
                    </strong>

                </div>


                <div class="details-box">

                    <span>
                        DELIVERY ADDRESS
                    </span>

                    <strong>

                        <?= e(
                            $selectedOrder["address"]
                        ) ?>

                        <br>

                        <?= e(
                            $selectedOrder["city"]
                        ) ?>,

                        <?= e(
                            $selectedOrder["province"]
                        ) ?>

                    </strong>

                </div>


                <div class="details-box">

                    <span>
                        POSTAL CODE
                    </span>

                    <strong>
                        <?= e(
                            $selectedOrder["postal_code"]
                        ) ?>
                    </strong>

                </div>

            </div>



            <!-- ==================================================
                 ORDER ITEMS
            ================================================== -->

            <h3 class="items-heading">
                ORDER ITEMS
            </h3>


            <?php if (!empty($selectedItems)): ?>


                <div class="items-table-wrap">

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
                                    QTY
                                </th>

                                <th>
                                    SUBTOTAL
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $selectedItems
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


                                    <td
                                        class="item-total"
                                    >

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


                <p
                    style="
                        color:#68758D;
                        font-size:9px;
                    "
                >
                    No order items found.
                </p>


            <?php endif; ?>



            <!-- ==================================================
                 FOOTER
            ================================================== -->

            <div class="details-footer">


                <a
                    href="my-orders.php"
                    class="back-orders"
                >
                    ← BACK TO MY ORDERS
                </a>


                <div class="grand-total">

                    TOTAL:

                    <?= money(
                        $selectedOrder["total"]
                    ) ?>

                </div>


            </div>


        </section>


    <?php elseif ($viewId): ?>


        <section class="order-details">

            <h2
                style="
                    margin:0 0 10px;
                    font-family:Orbitron,sans-serif;
                    font-size:18px;
                "
            >
                ORDER NOT FOUND
            </h2>


            <p
                style="
                    color:#68758D;
                    font-size:10px;
                "
            >
                This order does not exist or does not belong to your account.
            </p>


            <a
                href="my-orders.php"
                class="back-orders"
            >
                ← BACK TO MY ORDERS
            </a>

        </section>

    <?php endif; ?>


</main>


<!-- ==================================================
     LOGOUT POPUP
================================================== -->

<?php

$logoutPopupFile = "includes/logout-popup.php";

if (file_exists(__DIR__ . "/" . $logoutPopupFile)) {

    require_once $logoutPopupFile;

}

?>


<script src="js/script.js"></script>


</body>

</html>