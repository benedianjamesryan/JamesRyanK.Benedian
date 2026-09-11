<?php
session_start();
require_once "database/config.php";

if (empty($_SESSION["user_id"])) {
    header("Location: login.php?redirect=my-orders.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$loggedIn = true;
$isAdmin = ($_SESSION["role"] ?? "") === "admin";

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount)
{
    return "₱" . number_format((float)$amount, 2);
}

$customerName = $_SESSION["username"] ?? "Customer";

$cartStmt = $pdo->prepare("
    SELECT COALESCE(SUM(quantity), 0)
    FROM cart_items
    WHERE user_id = ?
");
$cartStmt->execute([$userId]);
$cartCount = (int)$cartStmt->fetchColumn();

$viewId = filter_input(INPUT_GET, "view", FILTER_VALIDATE_INT);

$selectedOrder = null;
$selectedItems = [];

if ($viewId && $viewId > 0) {
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

    $orderStmt->execute([$viewId, $userId]);
    $selectedOrder = $orderStmt->fetch(PDO::FETCH_ASSOC);

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

        $itemsStmt->execute([$viewId]);
        $selectedItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

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

$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FROSTCORE — My Orders</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="css/my-orders.css">
</head>

<body>

<header class="products-header">

    <a href="index.php" class="brand">
        <img
            src="assets/logo/frostcore_logo.png"
            alt="FROSTCORE Logo"
            class="brand-logo"
        >
        <span>FROSTCORE</span>
    </a>

    <nav class="products-nav">

        <a href="index.php">
            HOME
        </a>

        <a href="products.php">
            PRODUCTS
        </a>

        <a href="my-orders.php" class="active">
            MY ORDERS
        </a>

        <a href="about.php">
            ABOUT US
        </a>

        <a href="contact.php">
            CONTACT
        </a>

    </nav>

    <div class="header-actions">

        <?php if ($isAdmin): ?>

            <a
                href="admin/dashboard.php"
                class="admin-link"
            >
                ADMIN
            </a>

        <?php endif; ?>

        <a
            href="#"
            class="header-icon logout-button"
            id="logoutButton"
            title="Logout"
        >
            LOGOUT
        </a>

        <a href="cart.php" class="cart-link">

            🛒

            <span class="cart-number">
                <?= $cartCount ?>
            </span>

        </a>

    </div>

</header>

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

    <?php if (!empty($orders)): ?>

        <div class="orders-list">

            <?php foreach ($orders as $order): ?>

                <?php
                $status = (string)$order["status"];

                $statusClass = match ($status) {
                    "Pending" => "status-pending",
                    "Processing" => "status-processing",
                    "Shipped" => "status-shipped",
                    "Completed" => "status-completed",
                    default => ""
                };
                ?>

                <article class="order-card">

                    <div class="order-card-top">

                        <div>

                            <div class="order-number">
                                <?= e($order["order_number"]) ?>
                            </div>

                            <div class="order-date">
                                <?= e(
                                    date(
                                        "F d, Y · h:i A",
                                        strtotime($order["created_at"])
                                    )
                                ) ?>
                            </div>

                        </div>

                        <span class="status <?= e($statusClass) ?>">
                            <?= e($status) ?>
                        </span>

                    </div>

                    <div class="order-info">

                        <div class="order-info-box">
                            <span>PAYMENT</span>
                            <strong>
                                <?= e($order["payment_method"]) ?>
                            </strong>
                        </div>

                        <div class="order-info-box">
                            <span>SUBTOTAL</span>
                            <strong>
                                <?= money($order["subtotal"]) ?>
                            </strong>
                        </div>

                        <div class="order-info-box">
                            <span>SHIPPING</span>
                            <strong>
                                <?= (float)$order["shipping_fee"] > 0
                                    ? money($order["shipping_fee"])
                                    : "FREE"
                                ?>
                            </strong>
                        </div>

                        <div class="order-info-box">
                            <span>TOTAL</span>
                            <strong>
                                <?= money($order["total"]) ?>
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

    <?php if ($selectedOrder): ?>

        <?php
        $selectedStatus = (string)$selectedOrder["status"];

        $selectedStatusClass = match ($selectedStatus) {
            "Pending" => "status-pending",
            "Processing" => "status-processing",
            "Shipped" => "status-shipped",
            "Completed" => "status-completed",
            default => ""
        };

        $statusIndexes = [
            "Pending" => 0,
            "Processing" => 1,
            "Shipped" => 2,
            "Completed" => 3
        ];

        $currentIndex = $statusIndexes[$selectedStatus] ?? 0;

        $statusLabels = [
            "Pending",
            "Processing",
            "Shipped",
            "Completed"
        ];
        ?>

        <section class="order-details">

            <div class="details-heading">

                <div>

                    <h2>
                        ORDER
                        <?= e($selectedOrder["order_number"]) ?>
                    </h2>

                    <p>
                        Placed on
                        <?= e(
                            date(
                                "F d, Y · h:i A",
                                strtotime($selectedOrder["created_at"])
                            )
                        ) ?>
                    </p>

                </div>

                <span class="status <?= e($selectedStatusClass) ?>">
                    <?= e($selectedStatus) ?>
                </span>

            </div>

            <div class="status-tracker">

                <?php foreach ($statusLabels as $index => $label): ?>

                    <?php
                    $stepClass = "";

                    if ($index < $currentIndex) {
                        $stepClass = "done";
                    } elseif ($index === $currentIndex) {
                        $stepClass = "current";
                    }
                    ?>

                    <div class="tracker-step <?= e($stepClass) ?>">

                        <div class="tracker-dot">

                            <?php if ($index < $currentIndex): ?>
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

            <div class="details-grid">

                <div class="details-box">
                    <span>CUSTOMER</span>
                    <strong>
                        <?= e($selectedOrder["full_name"]) ?>
                    </strong>
                </div>

                <div class="details-box">
                    <span>EMAIL</span>
                    <strong>
                        <?= e($selectedOrder["email"]) ?>
                    </strong>
                </div>

                <div class="details-box">
                    <span>PHONE</span>
                    <strong>
                        <?= e($selectedOrder["phone"]) ?>
                    </strong>
                </div>

                <div class="details-box">
                    <span>PAYMENT METHOD</span>
                    <strong>
                        <?= e($selectedOrder["payment_method"]) ?>
                    </strong>
                </div>

                <div class="details-box">
                    <span>DELIVERY ADDRESS</span>
                    <strong>
                        <?= e($selectedOrder["address"]) ?><br>
                        <?= e($selectedOrder["city"]) ?>,
                        <?= e($selectedOrder["province"]) ?>
                    </strong>
                </div>

                <div class="details-box">
                    <span>POSTAL CODE</span>
                    <strong>
                        <?= e($selectedOrder["postal_code"]) ?>
                    </strong>
                </div>

            </div>

            <h3 class="items-heading">
                ORDER ITEMS
            </h3>

            <?php if (!empty($selectedItems)): ?>

                <div class="items-table-wrap">

                    <table class="items-table">

                        <thead>

                            <tr>
                                <th>PRODUCT</th>
                                <th>PRICE</th>
                                <th>QTY</th>
                                <th>SUBTOTAL</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($selectedItems as $item): ?>

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

                <p class="no-items-message">
                    No order items found.
                </p>

            <?php endif; ?>

            <div class="details-footer">

                <a
                    href="my-orders.php"
                    class="back-orders"
                >
                    ← BACK TO MY ORDERS
                </a>

                <div class="grand-total">
                    TOTAL:
                    <?= money($selectedOrder["total"]) ?>
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

<?php
$logoutPopupFile = "includes/logout-popup.php";

if (file_exists(__DIR__ . "/" . $logoutPopupFile)) {
    require_once $logoutPopupFile;
}
?>

<script src="js/script.js"></script>

</body>
</html>