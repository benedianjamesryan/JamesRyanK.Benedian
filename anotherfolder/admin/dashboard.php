<?php
session_start();
require_once "../database/config.php";

if (empty($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: admin-login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount) {
    return "₱" . number_format((float)$amount, 2);
}

$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$totalCustomers = (int)$pdo->query("
    SELECT COUNT(*) FROM users WHERE role = 'customer'
")->fetchColumn();

$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

$pendingOrders = (int)$pdo->query("
    SELECT COUNT(*) FROM orders WHERE status = 'Pending'
")->fetchColumn();

$totalOrderValue = (float)$pdo->query("
    SELECT COALESCE(SUM(total), 0) FROM orders
")->fetchColumn();

$outOfStock = (int)$pdo->query("
    SELECT COUNT(*) FROM products WHERE stock <= 0
")->fetchColumn();

$recentOrders = $pdo->query("
    SELECT id, order_number, full_name, total, status, created_at
    FROM orders
    ORDER BY created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$lowStockProducts = $pdo->query("
    SELECT id, name, stock, price
    FROM products
    WHERE stock <= 5
    ORDER BY stock ASC, name ASC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$adminName = $_SESSION["username"] ?? "Administrator";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FROSTCORE — Admin Dashboard</title>
<link rel="stylesheet" href="../css/admin/dashboard.css">
</head>
<body>

<header class="admin-header">
    <a href="dashboard.php" class="admin-brand">
        <img src="../assets/logo/frostcore_logo.png" alt="FROSTCORE Logo">
        <span>FROSTCORE ADMIN</span>
    </a>

    <div class="admin-header-right">
        <span class="admin-name">Welcome, <?= e($adminName) ?></span>
        <a href="../logout.php" class="admin-logout" onclick="return confirm('Are you sure you want to log out of your FROSTCORE administrator account?');">LOGOUT</a>
    </div>
</header>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <div class="admin-sidebar-title">MANAGEMENT</div>
        <a href="dashboard.php" class="active">DASHBOARD</a>
        <a href="orders.php">ORDERS</a>
        <a href="customers.php">CUSTOMERS</a>
        <a href="products.php">PRODUCTS</a>
        <a href="reviews.php">REVIEWS</a>

        <div class="admin-sidebar-title">WEBSITE</div>
        <a href="../products.php">VIEW STORE</a>
        <a href="../index.php">HOMEPAGE</a>
    </aside>

    <main class="admin-main">

        <div class="admin-page-title">
            <h1>DASHBOARD</h1>
            <p>FROSTCORE system overview and management.</p>
        </div>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">PRODUCTS</div>
                <div class="stat-value"><?= $totalProducts ?></div>
                <div class="stat-note">Total products</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">CUSTOMERS</div>
                <div class="stat-value"><?= $totalCustomers ?></div>
                <div class="stat-note">Registered customers</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">ORDERS</div>
                <div class="stat-value"><?= $totalOrders ?></div>
                <div class="stat-note">All orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">PENDING</div>
                <div class="stat-value"><?= $pendingOrders ?></div>
                <div class="stat-note">Awaiting processing</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">ORDER VALUE</div>
                <div class="stat-value order-value"><?= money($totalOrderValue) ?></div>
                <div class="stat-note">Total recorded order value</div>
            </div>
        </section>

        <div class="content-grid">

            <section class="admin-card">
                <h2>RECENT ORDERS</h2>

                <?php if (!empty($recentOrders)): ?>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ORDER</th>
                                    <th>CUSTOMER</th>
                                    <th>TOTAL</th>
                                    <th>STATUS</th>
                                    <th>DATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
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
                                        <td><span class="order-number"><?= e($order["order_number"]) ?></span></td>
                                        <td><?= e($order["full_name"]) ?></td>
                                        <td><span class="order-total"><?= money($order["total"]) ?></span></td>
                                        <td><span class="status <?= $statusClass ?>"><?= e($status) ?></span></td>
                                        <td><?= e(date("M d, Y", strtotime($order["created_at"]))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">No orders yet.</div>
                <?php endif; ?>
            </section>

            <section class="admin-card">
                <h2>LOW STOCK</h2>

                <?php if (!empty($lowStockProducts)): ?>
                    <?php foreach ($lowStockProducts as $product): ?>
                        <div class="low-stock-item">
                            <div>
                                <div class="low-stock-name"><?= e($product["name"]) ?></div>
                                <div class="low-stock-price"><?= money($product["price"]) ?></div>
                            </div>
                            <div class="stock-number"><?= (int)$product["stock"] ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">All products have more than 5 units.</div>
                <?php endif; ?>

                <div class="out-stock">
                    <?= $outOfStock ?> product(s) currently out of stock.
                </div>
            </section>

            <section class="admin-card">
                <h2>QUICK ACTIONS</h2>

                <div class="quick-actions">
                    <a href="orders.php" class="quick-action">VIEW ORDERS</a>
                    <a href="products.php" class="quick-action">MANAGE PRODUCTS</a>
                    <a href="customers.php" class="quick-action">VIEW CUSTOMERS</a>
                    <a href="reviews.php" class="quick-action">MANAGE REVIEWS</a>
                </div>
            </section>

            <section class="admin-card">
                <h2>SYSTEM STATUS</h2>

                <div class="system-status">
                    <span>●</span>
                    System operational
                </div>

                <p class="system-description">
                    FROSTCORE administration panel is connected to the database and ready for management.
                </p>
            </section>

        </div>
    </main>
</div>

</body>
</html>