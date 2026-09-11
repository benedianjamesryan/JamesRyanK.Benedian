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
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount)
{
    return "₱" . number_format((float)$amount, 2);
}

if (empty($_SESSION["admin_product_csrf"])) {
    $_SESSION["admin_product_csrf"] = bin2hex(random_bytes(32));
}

$successMessage = "";
$errorMessage = "";

$allowedCategories = [
    "Phone Cooler",
    "Laptop Cooler",
    "Bundle"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $csrfToken = $_POST["csrf_token"] ?? "";
    $action = $_POST["action"] ?? "";

    if (
        empty($csrfToken) ||
        empty($_SESSION["admin_product_csrf"]) ||
        !hash_equals($_SESSION["admin_product_csrf"], $csrfToken)
    ) {
        $errorMessage = "Invalid request. Please refresh the page.";
    } else {
        if ($action === "add") {
            $name = trim($_POST["name"] ?? "");
            $category = trim($_POST["category"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $price = filter_var($_POST["price"] ?? null, FILTER_VALIDATE_FLOAT);
            $image = trim($_POST["image"] ?? "");
            $rating = filter_var($_POST["rating"] ?? null, FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST["stock"] ?? null, FILTER_VALIDATE_INT);

            if ($name === "") {
                $errorMessage = "Product name is required.";
            } elseif (!in_array($category, $allowedCategories, true)) {
                $errorMessage = "Please select a valid category.";
            } elseif ($description === "") {
                $errorMessage = "Product description is required.";
            } elseif ($price === false || $price < 0) {
                $errorMessage = "Please enter a valid price.";
            } elseif ($rating === false || $rating < 0 || $rating > 5) {
                $errorMessage = "Rating must be between 0 and 5.";
            } elseif ($stock === false || $stock < 0) {
                $errorMessage = "Stock cannot be negative.";
            } else {
                $insertStmt = $pdo->prepare("
                    INSERT INTO products
                    (name, category, description, price, image, rating, stock)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $insertStmt->execute([
                    $name,
                    $category,
                    $description,
                    $price,
                    $image,
                    $rating,
                    $stock
                ]);

                $successMessage = "Product added successfully.";
            }
        } elseif ($action === "update") {
            $productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);
            $name = trim($_POST["name"] ?? "");
            $category = trim($_POST["category"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $price = filter_var($_POST["price"] ?? null, FILTER_VALIDATE_FLOAT);
            $image = trim($_POST["image"] ?? "");
            $rating = filter_var($_POST["rating"] ?? null, FILTER_VALIDATE_FLOAT);
            $stock = filter_var($_POST["stock"] ?? null, FILTER_VALIDATE_INT);

            if (!$productId || $productId <= 0) {
                $errorMessage = "Invalid product.";
            } elseif ($name === "") {
                $errorMessage = "Product name is required.";
            } elseif (!in_array($category, $allowedCategories, true)) {
                $errorMessage = "Please select a valid category.";
            } elseif ($description === "") {
                $errorMessage = "Product description is required.";
            } elseif ($price === false || $price < 0) {
                $errorMessage = "Please enter a valid price.";
            } elseif ($rating === false || $rating < 0 || $rating > 5) {
                $errorMessage = "Rating must be between 0 and 5.";
            } elseif ($stock === false || $stock < 0) {
                $errorMessage = "Stock cannot be negative.";
            } else {
                $checkStmt = $pdo->prepare("
                    SELECT id
                    FROM products
                    WHERE id = ?
                    LIMIT 1
                ");

                $checkStmt->execute([$productId]);

                if (!$checkStmt->fetch()) {
                    $errorMessage = "Product not found.";
                } else {
                    $updateStmt = $pdo->prepare("
                        UPDATE products
                        SET
                            name = ?,
                            category = ?,
                            description = ?,
                            price = ?,
                            image = ?,
                            rating = ?,
                            stock = ?
                        WHERE id = ?
                    ");

                    $updateStmt->execute([
                        $name,
                        $category,
                        $description,
                        $price,
                        $image,
                        $rating,
                        $stock,
                        $productId
                    ]);

                    $successMessage = "Product updated successfully.";
                }
            }
        } elseif ($action === "delete") {
            $productId = filter_input(INPUT_POST, "product_id", FILTER_VALIDATE_INT);

            if (!$productId || $productId <= 0) {
                $errorMessage = "Invalid product.";
            } else {
                $orderCheckStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM order_items
                    WHERE product_id = ?
                ");

                $orderCheckStmt->execute([$productId]);
                $orderItemCount = (int)$orderCheckStmt->fetchColumn();

                if ($orderItemCount > 0) {
                    $errorMessage = "This product cannot be deleted because it is already part of an existing order.";
                } else {
                    try {
                        $pdo->beginTransaction();

                        $cartDeleteStmt = $pdo->prepare("
                            DELETE FROM cart_items
                            WHERE product_id = ?
                        ");

                        $cartDeleteStmt->execute([$productId]);

                        $deleteStmt = $pdo->prepare("
                            DELETE FROM products
                            WHERE id = ?
                        ");

                        $deleteStmt->execute([$productId]);

                        if ($deleteStmt->rowCount() !== 1) {
                            throw new Exception("Product was not found.");
                        }

                        $pdo->commit();
                        $successMessage = "Product deleted successfully.";
                    } catch (Throwable $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        $errorMessage = "Unable to delete the product.";
                    }
                }
            }
        } else {
            $errorMessage = "Invalid action.";
        }

        $_SESSION["admin_product_csrf"] = bin2hex(random_bytes(32));
    }
}

$search = trim($_GET["search"] ?? "");
$selectedCategory = trim($_GET["category"] ?? "");

if (
    $selectedCategory !== "" &&
    !in_array($selectedCategory, $allowedCategories, true)
) {
    $selectedCategory = "";
}

$sql = "
    SELECT
        id,
        name,
        category,
        description,
        price,
        image,
        rating,
        stock
    FROM products
    WHERE 1 = 1
";

$params = [];

if ($search !== "") {
    $sql .= "
        AND (
            name LIKE ?
            OR category LIKE ?
            OR description LIKE ?
        )
    ";

    $searchTerm = "%" . $search . "%";

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($selectedCategory !== "") {
    $sql .= " AND category = ?";
    $params[] = $selectedCategory;
}

$sql .= " ORDER BY id ASC";

$productStmt = $pdo->prepare($sql);
$productStmt->execute($params);
$products = $productStmt->fetchAll(PDO::FETCH_ASSOC);

$totalProductsStmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
");

$totalProducts = (int)$totalProductsStmt->fetchColumn();

$inStockStmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE stock > 0
");

$inStock = (int)$inStockStmt->fetchColumn();

$outOfStockStmt = $pdo->query("
    SELECT COUNT(*)
    FROM products
    WHERE stock <= 0
");

$outOfStock = (int)$outOfStockStmt->fetchColumn();

$adminName = $_SESSION["username"] ?? "Administrator";

$editId = filter_input(INPUT_GET, "edit", FILTER_VALIDATE_INT);
$editProduct = null;

if ($editId && $editId > 0) {
    $editStmt = $pdo->prepare("
        SELECT
            id,
            name,
            category,
            description,
            price,
            image,
            rating,
            stock
        FROM products
        WHERE id = ?
        LIMIT 1
    ");

    $editStmt->execute([$editId]);
    $editProduct = $editStmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FROSTCORE - Admin Products</title>
    <link rel="stylesheet" href="../css/admin/products.css">
</head>

<body>

<header class="admin-header">
    <a href="dashboard.php" class="admin-brand">
        <img src="../assets/logo/frostcore_logo.png" alt="FROSTCORE Logo">
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
        <a href="orders.php">ORDERS</a>
        <a href="customers.php">CUSTOMERS</a>
        <a href="products.php" class="active">PRODUCTS</a>
        <a href="reviews.php">REVIEWS</a>

        <div class="sidebar-title">WEBSITE</div>

        <a href="../products.php">VIEW STORE</a>
        <a href="../index.php">HOMEPAGE</a>
    </aside>

    <main class="admin-main">

        <div class="page-title">
            <h1>PRODUCTS</h1>
            <p>Manage FROSTCORE products, pricing, stock, and catalog information.</p>
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

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">TOTAL PRODUCTS</div>
                <div class="stat-value"><?= $totalProducts ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">IN STOCK</div>
                <div class="stat-value"><?= $inStock ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">OUT OF STOCK</div>
                <div class="stat-value"><?= $outOfStock ?></div>
            </div>
        </section>

        <div class="toolbar">
            <form method="GET" action="products.php" class="search-form">
                <input
                    class="search-input"
                    type="text"
                    name="search"
                    value="<?= e($search) ?>"
                    placeholder="Search products..."
                >

                <?php if ($selectedCategory !== ""): ?>
                    <input
                        type="hidden"
                        name="category"
                        value="<?= e($selectedCategory) ?>"
                    >
                <?php endif; ?>

                <button type="submit" class="search-button">SEARCH</button>
            </form>

            <a
                href="products.php"
                class="filter-link <?= $selectedCategory === "" ? "active" : "" ?>"
            >
                ALL
            </a>

            <a
                href="products.php?category=Phone+Cooler"
                class="filter-link <?= $selectedCategory === "Phone Cooler" ? "active" : "" ?>"
            >
                PHONE
            </a>

            <a
                href="products.php?category=Laptop+Cooler"
                class="filter-link <?= $selectedCategory === "Laptop Cooler" ? "active" : "" ?>"
            >
                LAPTOP
            </a>

            <a
                href="products.php?category=Bundle"
                class="filter-link <?= $selectedCategory === "Bundle" ? "active" : "" ?>"
            >
                BUNDLE
            </a>
        </div>

        <div class="add-product-area">
            <a href="?new=1" class="add-button">+ ADD NEW PRODUCT</a>
        </div>

        <?php if ($editProduct): ?>
            <section class="product-form-card">
                <h2>EDIT PRODUCT #<?= (int)$editProduct["id"] ?></h2>

                <form method="POST" action="products.php" class="product-form">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["admin_product_csrf"]) ?>"
                    >

                    <input type="hidden" name="action" value="update">

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int)$editProduct["id"] ?>"
                    >

                    <div class="form-field">
                        <label for="name">PRODUCT NAME</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="<?= e($editProduct["name"]) ?>"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="category">CATEGORY</label>
                        <select id="category" name="category" required>
                            <?php foreach ($allowedCategories as $category): ?>
                                <option
                                    value="<?= e($category) ?>"
                                    <?= $editProduct["category"] === $category ? "selected" : "" ?>
                                >
                                    <?= e($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="price">PRICE</label>
                        <input
                            id="price"
                            type="number"
                            name="price"
                            value="<?= e($editProduct["price"]) ?>"
                            min="0"
                            step="0.01"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="stock">STOCK</label>
                        <input
                            id="stock"
                            type="number"
                            name="stock"
                            value="<?= (int)$editProduct["stock"] ?>"
                            min="0"
                            step="1"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="rating">RATING</label>
                        <input
                            id="rating"
                            type="number"
                            name="rating"
                            value="<?= e($editProduct["rating"]) ?>"
                            min="0"
                            max="5"
                            step="0.1"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="image">IMAGE PATH</label>
                        <input
                            id="image"
                            type="text"
                            name="image"
                            value="<?= e($editProduct["image"]) ?>"
                            maxlength="255"
                            placeholder="../assets/products/product-image.png"
                        >
                    </div>

                    <div class="form-field full">
                        <label for="description">DESCRIPTION</label>
                        <textarea
                            id="description"
                            name="description"
                            maxlength="2000"
                            required
                        ><?= e($editProduct["description"]) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-button">SAVE CHANGES</button>
                        <a href="products.php" class="cancel-button">CANCEL</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <?php if (isset($_GET["new"])): ?>
            <section class="product-form-card">
                <h2>ADD NEW PRODUCT</h2>

                <form method="POST" action="products.php" class="product-form">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION["admin_product_csrf"]) ?>"
                    >

                    <input type="hidden" name="action" value="add">

                    <div class="form-field">
                        <label for="new-name">PRODUCT NAME</label>
                        <input
                            id="new-name"
                            type="text"
                            name="name"
                            maxlength="150"
                            placeholder="FrostCore X5"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="new-category">CATEGORY</label>
                        <select id="new-category" name="category" required>
                            <option value="">Select category</option>

                            <?php foreach ($allowedCategories as $category): ?>
                                <option value="<?= e($category) ?>">
                                    <?= e($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="new-price">PRICE</label>
                        <input
                            id="new-price"
                            type="number"
                            name="price"
                            min="0"
                            step="0.01"
                            placeholder="999.00"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="new-stock">STOCK</label>
                        <input
                            id="new-stock"
                            type="number"
                            name="stock"
                            min="0"
                            step="1"
                            placeholder="10"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="new-rating">RATING</label>
                        <input
                            id="new-rating"
                            type="number"
                            name="rating"
                            min="0"
                            max="5"
                            step="0.1"
                            value="0"
                            required
                        >
                    </div>

                    <div class="form-field">
                        <label for="new-image">IMAGE PATH</label>
                        <input
                            id="new-image"
                            type="text"
                            name="image"
                            maxlength="255"
                            placeholder="../assets/products/product-image.png"
                        >
                    </div>

                    <div class="form-field full">
                        <label for="new-description">DESCRIPTION</label>
                        <textarea
                            id="new-description"
                            name="description"
                            maxlength="2000"
                            placeholder="Enter product description..."
                            required
                        ></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-button">ADD PRODUCT</button>
                        <a href="products.php" class="cancel-button">CANCEL</a>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <section class="products-card">
            <?php if (!empty($products)): ?>
                <div class="table-wrap">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>IMAGE</th>
                                <th>PRODUCT</th>
                                <th>CATEGORY</th>
                                <th>PRICE</th>
                                <th>RATING</th>
                                <th>STOCK</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $stock = (int)$product["stock"];

                                if ($stock <= 0) {
                                    $stockClass = "stock-empty";
                                    $stockText = "OUT OF STOCK";
                                } elseif ($stock <= 5) {
                                    $stockClass = "stock-low";
                                    $stockText = $stock . " LEFT";
                                } else {
                                    $stockClass = "stock-good";
                                    $stockText = $stock . " IN STOCK";
                                }

                                $imagePath = trim((string)$product["image"]);

                                if ($imagePath === "") {
                                    $imagePath = "../assets/products/fc1-cooler.svg";
                                } elseif (str_starts_with($imagePath, "assets/")) {
                                    $imagePath = "../" . $imagePath;
                                }
                                ?>

                                <tr>
                                    <td>
                                        <img
                                            src="<?= e($imagePath) ?>"
                                            alt="<?= e($product["name"]) ?>"
                                            class="product-thumb"
                                            onerror="this.src='../assets/products/fc1-cooler.svg';"
                                        >
                                    </td>

                                    <td>
                                        <div class="product-name">
                                            <?= e($product["name"]) ?>
                                        </div>

                                        <div class="product-category">
                                            ID: #<?= (int)$product["id"] ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e($product["category"]) ?>
                                    </td>

                                    <td>
                                        <strong class="product-price">
                                            <?= money($product["price"]) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= number_format((float)$product["rating"], 1) ?> / 5
                                    </td>

                                    <td>
                                        <span class="stock-badge <?= e($stockClass) ?>">
                                            <?= e($stockText) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a
                                            href="?edit=<?= (int)$product["id"] ?>"
                                            class="edit-link"
                                        >
                                            EDIT
                                        </a>

                                        <form
                                            method="POST"
                                            action="products.php"
                                            class="delete-form"
                                            onsubmit="return confirm('Are you sure you want to delete this product?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION["admin_product_csrf"]) ?>"
                                            >

                                            <input type="hidden" name="action" value="delete">

                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int)$product["id"] ?>"
                                            >

                                            <button type="submit" class="delete-button">
                                                DELETE
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty">
                    No products found.
                </div>
            <?php endif; ?>
        </section>

        <div class="info-note">
            Products that already appear in an existing order cannot be deleted,
            because deleting them would damage your order history. You can edit
            their name, price, stock, rating, description, or image instead.
        </div>

    </main>
</div>

</body>
</html>