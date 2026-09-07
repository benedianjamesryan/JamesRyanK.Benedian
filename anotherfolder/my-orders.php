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


// ==================================================
// VIEW SINGLE CUSTOMER ORDER
// ==================================================

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


    // ==================================================
    // GET ORDER ITEMS
    // ==================================================

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


    <!-- SHARED CSS -->

    <link
        rel="stylesheet"
        href="css/style.css"
    >


    <!-- PRODUCTS / SHARED STORE CSS -->

    <link
        rel="stylesheet"
        href="css/products.css"
    >


    <!-- MY ORDERS CSS -->

    <link
        rel="stylesheet"
        href="css/my-orders.css"
    >

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="orders-header">


    <!-- BRAND -->

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


    <!-- NAVIGATION -->

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


    <!-- ACTIONS -->

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


    <!-- ==================================================
         PAGE TITLE
    ================================================== -->

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


            <!-- ==================================================
                 DETAILS HEADING
            ================================================== -->

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

                "Pending" =>
                    0,

                "Processing" =>
                    1,

                "Shipped" =>
                    2,

                "Completed" =>
                    3

            ];


            $currentIndex =
                $statusIndexes[$selectedStatus] ??
                0;


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

                    $stepClass =
                        "";


                    if (
                        $index <
                        $currentIndex
                    ) {

                        $stepClass =
                            "done";

                    }

                    elseif (
                        $index ===
                        $currentIndex
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
                                $index <
                                $currentIndex
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


                <p class="no-items-message">

                    No order items found.

                </p>


            <?php endif; ?>



            <!-- ==================================================
                 DETAILS FOOTER
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


            <h2 class="order-not-found-title">

                ORDER NOT FOUND

            </h2>


            <p class="order-not-found-message">

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

$logoutPopupFile =
    "includes/logout-popup.php";


if (
    file_exists(
        __DIR__ .
        "/" .
        $logoutPopupFile
    )
) {

    require_once
        $logoutPopupFile;

}

?>


<script src="js/script.js"></script>


</body>

</html>