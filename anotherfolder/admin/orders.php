<?php
session_start();

require_once "../database/config.php";

if (
    empty($_SESSION["user_id"]) ||
    ($_SESSION["role"] ?? "") !== "admin"
) {
    header("Location: admin-login.php");
    exit;
}

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

if (empty($_SESSION["admin_csrf_token"])) {
    $_SESSION["admin_csrf_token"] = bin2hex(
        random_bytes(32)
    );
}

$allowedStatuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Completed"
];

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $action = $_POST["action"] ?? "";

    $orderId = filter_input(
        INPUT_POST,
        "order_id",
        FILTER_VALIDATE_INT
    );

    $newStatus = trim(
        $_POST["status"] ?? ""
    );

    if (
        empty($token) ||
        empty($_SESSION["admin_csrf_token"]) ||
        !hash_equals(
            $_SESSION["admin_csrf_token"],
            $token
        )
    ) {
        $errorMessage = "Invalid request. Please refresh the page.";
    } elseif ($action !== "update_status") {
        $errorMessage = "Invalid action.";
    } elseif (!$orderId || $orderId <= 0) {
        $errorMessage = "Invalid order.";
    } elseif (!in_array($newStatus, $allowedStatuses, true)) {
        $errorMessage = "Invalid order status.";
    } else {
        $updateStmt = $pdo->prepare("
            UPDATE orders
            SET status = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
            $newStatus,
            $orderId
        ]);

        $successMessage = "Order status updated successfully.";

        $_SESSION["admin_csrf_token"] = bin2hex(
            random_bytes(32)
        );
    }
}

$selectedStatus = trim(
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

$orderColumns = "
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
";

if ($selectedStatus !== "") {
    $ordersStmt = $pdo->prepare("
        SELECT $orderColumns
        FROM orders
        WHERE status = ?
        ORDER BY created_at DESC
    ");

    $ordersStmt->execute([
        $selectedStatus
    ]);
} else {
    $ordersStmt = $pdo->query("
        SELECT $orderColumns
        FROM orders
        ORDER BY created_at DESC
    ");
}

$orders = $ordersStmt->fetchAll(
    PDO::FETCH_ASSOC
);

$selectedOrder = null;
$selectedOrderItems = [];

$viewId = filter_input(
    INPUT_GET,
    "view",
    FILTER_VALIDATE_INT
);

if ($viewId && $viewId > 0) {
    $orderStmt = $pdo->prepare("
        SELECT $orderColumns
        FROM orders
        WHERE id = ?
        LIMIT 1
    ");

    $orderStmt->execute([
        $viewId
    ]);

    $selectedOrder = $orderStmt->fetch(
        PDO::FETCH_ASSOC
    );

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

        $selectedOrderItems = $itemsStmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }
}

$adminName = $_SESSION["username"] ?? "Administrator";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>FROSTCORE - Admin Orders</title>
    <link rel="stylesheet" href="../css/admin/orders.css">
</head>

<body>

<header class="admin-header">
    <a href="dashboard.php" class="admin-brand">
        <img
            src="../assets/logo/frostcore_logo.png"
            alt="FROSTCORE Logo"
        >
        <span>FROSTCORE ADMIN</span>
    </a>

    <div class="admin-header-right">
        <span class="admin-name">
            Welcome, <?= e($adminName) ?>
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

<div class="admin-layout">

    <aside class="admin-sidebar">
        <div class="sidebar-title">MANAGEMENT</div>

        <a href="dashboard.php">DASHBOARD</a>
        <a href="orders.php" class="active">ORDERS</a>
        <a href="customers.php">CUSTOMERS</a>
        <a href="products.php">PRODUCTS</a>
        <a href="reviews.php">REVIEWS</a>

        <div class="sidebar-title">WEBSITE</div>

        <a href="../products.php">VIEW STORE</a>
        <a href="../index.php">HOMEPAGE</a>
    </aside>

    <main class="admin-main">

        <div class="page-title">
            <h1>ORDERS</h1>
            <p>View customer orders and manage order status.</p>
        </div>

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

        <section class="orders-card">

            <?php if (!empty($orders)): ?>

                <div class="table-wrap">
                    <table class="orders-table">

                        <thead>
                            <tr>
                                <th>ORDER</th>
                                <th>CUSTOMER</th>
                                <th>PAYMENT</th>
                                <th>TOTAL</th>
                                <th>STATUS</th>
                                <th>DATE</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($orders as $order): ?>

                                <?php
                                $status = (string)$order["status"];

                                $statusClass = match ($status) {
                                    "Pending" => "status-pending",
                                    "Processing" => "status-processing",
                                    "Shipped" => "status-shipped",
                                    "Completed" => "status-completed",
                                    default => "status-default"
                                };
                                ?>

                                <tr>

                                    <td>
                                        <span class="order-number">
                                            <?= e($order["order_number"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e($order["full_name"]) ?>
                                        <br>
                                        <small class="sub-info">
                                            <?= e($order["email"]) ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= e($order["payment_method"]) ?>
                                    </td>

                                    <td>
                                        <span class="order-total">
                                            <?= money($order["total"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="status <?= e($statusClass) ?>">
                                            <?= e($status) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= e(
                                            date(
                                                "M d, Y",
                                                strtotime($order["created_at"])
                                            )
                                        ) ?>
                                        <br>
                                        <small class="sub-info">
                                            <?= e(
                                                date(
                                                    "h:i A",
                                                    strtotime($order["created_at"])
                                                )
                                            ) ?>
                                        </small>
                                    </td>

                                    <td>
                                        <div class="action-area">

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
                                                    value="<?= e($_SESSION["admin_csrf_token"]) ?>"
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

                                                <select name="status">
                                                    <?php foreach ($allowedStatuses as $option): ?>
                                                        <option
                                                            value="<?= e($option) ?>"
                                                            <?= $status === $option ? "selected" : "" ?>
                                                        >
                                                            <?= e($option) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button type="submit">
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

        <?php if ($selectedOrder): ?>

            <?php
            $selectedOrderStatus = (string)$selectedOrder["status"];

            $selectedStatusClass = match ($selectedOrderStatus) {
                "Pending" => "status-pending",
                "Processing" => "status-processing",
                "Shipped" => "status-shipped",
                "Completed" => "status-completed",
                default => "status-default"
            };
            ?>

            <section
                class="details-card"
                id="order-details"
            >

                <div class="details-header">

                    <div>
                        <h2>
                            ORDER <?= e($selectedOrder["order_number"]) ?>
                        </h2>

                        <p>
                            Created:
                            <?= e(
                                date(
                                    "M d, Y h:i A",
                                    strtotime($selectedOrder["created_at"])
                                )
                            ) ?>
                        </p>
                    </div>

                    <span class="status <?= e($selectedStatusClass) ?>">
                        <?= e($selectedOrderStatus) ?>
                    </span>

                </div>

                <div class="details-grid">

                    <div class="detail-box">
                        <span>CUSTOMER</span>
                        <strong>
                            <?= e($selectedOrder["full_name"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>EMAIL</span>
                        <strong>
                            <?= e($selectedOrder["email"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>PHONE</span>
                        <strong>
                            <?= e($selectedOrder["phone"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>PAYMENT</span>
                        <strong>
                            <?= e($selectedOrder["payment_method"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>ADDRESS</span>
                        <strong>
                            <?= e($selectedOrder["address"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>CITY</span>
                        <strong>
                            <?= e($selectedOrder["city"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>PROVINCE</span>
                        <strong>
                            <?= e($selectedOrder["province"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>POSTAL CODE</span>
                        <strong>
                            <?= e($selectedOrder["postal_code"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>SUBTOTAL</span>
                        <strong>
                            <?= money($selectedOrder["subtotal"]) ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>SHIPPING</span>
                        <strong>
                            <?php if ((float)$selectedOrder["shipping_fee"] > 0): ?>
                                <?= money($selectedOrder["shipping_fee"]) ?>
                            <?php else: ?>
                                FREE
                            <?php endif; ?>
                        </strong>
                    </div>

                    <div class="detail-box">
                        <span>TOTAL</span>
                        <strong>
                            <?= money($selectedOrder["total"]) ?>
                        </strong>
                    </div>

                </div>

                <h3 class="items-title">
                    ORDER ITEMS
                </h3>

                <?php if (!empty($selectedOrderItems)): ?>

                    <div class="table-wrap">
                        <table class="items-table">

                            <thead>
                                <tr>
                                    <th>PRODUCT</th>
                                    <th>PRICE</th>
                                    <th>QUANTITY</th>
                                    <th>SUBTOTAL</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($selectedOrderItems as $item): ?>

                                    <tr>

                                        <td>
                                            <?= e($item["product_name"]) ?>
                                        </td>

                                        <td>
                                            <?= money($item["price"]) ?>
                                        </td>

                                        <td>
                                            <?= (int)$item["quantity"] ?>
                                        </td>

                                        <td class="item-total">
                                            <?= money($item["subtotal"]) ?>
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

                <div class="details-summary">
                    <div class="details-summary-total">
                        TOTAL: <?= money($selectedOrder["total"]) ?>
                    </div>
                </div>

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
                    <h2 class="not-found-title">
                        ORDER NOT FOUND
                    </h2>

                    <p>
                        Order #<?= (int)$viewId ?> does not exist.
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