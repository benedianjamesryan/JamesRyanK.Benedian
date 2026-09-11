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

$adminName = $_SESSION["username"] ?? "Administrator";

$search = trim($_GET["search"] ?? "");

$roleFilter = trim($_GET["role"] ?? "");

$allowedRoles = [
    "customer",
    "admin"
];

if (
    $roleFilter !== "" &&
    !in_array($roleFilter, $allowedRoles, true)
) {
    $roleFilter = "";
}

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

if ($search !== "") {
    $sql .= "
        AND (
            full_name LIKE ?
            OR email LIKE ?
        )
    ";

    $searchTerm = "%" . $search . "%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($roleFilter !== "") {
    $sql .= "
        AND role = ?
    ";

    $params[] = $roleFilter;
}

$sql .= "
    ORDER BY id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalUsersStmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
");

$totalUsers = (int)$totalUsersStmt->fetchColumn();

$totalCustomersStmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'customer'
");

$totalCustomers = (int)$totalCustomersStmt->fetchColumn();

$totalAdminsStmt = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'admin'
");

$totalAdmins = (int)$totalAdminsStmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>FROSTCORE - Customers</title>

    <link
        rel="stylesheet"
        href="../css/products.css"
    >

    <link
        rel="stylesheet"
        href="../css/admin/customers.css"
    >

</head>

<body>

<header class="admin-header">

    <a
        href="dashboard.php"
        class="admin-brand"
    >

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

    <main class="admin-main">

        <div class="page-title">

            <h1>CUSTOMERS</h1>

            <p>
                View registered FROSTCORE accounts and account roles.
            </p>

        </div>

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

                <button type="submit">
                    SEARCH
                </button>

            </form>

            <a
                href="customers.php"
                class="filter-link <?= $roleFilter === "" ? "active" : "" ?>"
            >
                ALL
            </a>

            <a
                href="customers.php?role=customer"
                class="filter-link <?= $roleFilter === "customer" ? "active" : "" ?>"
            >
                CUSTOMERS
            </a>

            <a
                href="customers.php?role=admin"
                class="filter-link <?= $roleFilter === "admin" ? "active" : "" ?>"
            >
                ADMINS
            </a>

        </div>

        <section class="customers-card">

            <?php if (!empty($users)): ?>

                <div class="table-wrap">

                    <table class="customers-table">

                        <thead>

                            <tr>
                                <th>ID</th>
                                <th>FULL NAME</th>
                                <th>EMAIL</th>
                                <th>ROLE</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($users as $user): ?>

                                <?php

                                $role = (string)$user["role"];

                                $roleClass =
                                    $role === "admin"
                                        ? "role-admin"
                                        : "role-customer";

                                ?>

                                <tr>

                                    <td>
                                        <span class="customer-id">
                                            #<?= (int)$user["id"] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="customer-name">
                                            <?= e($user["full_name"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="customer-email">
                                            <?= e($user["email"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span
                                            class="role-badge <?= e($roleClass) ?>"
                                        >
                                            <?= e(strtoupper($role)) ?>
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

        <div class="admin-note">
            Customer passwords are intentionally not displayed.
            This page is currently read-only so the administrator
            cannot accidentally delete or modify accounts.
        </div>

    </main>

</div>

</body>
</html>