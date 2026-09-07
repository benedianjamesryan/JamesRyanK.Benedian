<?php

// ==================================================
// FROSTCORE ADMIN - PRODUCTS
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

if (empty($_SESSION["admin_product_csrf"])) {

    $_SESSION["admin_product_csrf"] =
        bin2hex(random_bytes(32));

}


// ==================================================
// VARIABLES
// ==================================================

$successMessage = "";

$errorMessage = "";


// ==================================================
// ALLOWED CATEGORIES
// ==================================================

$allowedCategories = [
    "Phone Cooler",
    "Laptop Cooler",
    "Bundle"
];


// ==================================================
// HANDLE POST ACTIONS
// ==================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfToken =
        $_POST["csrf_token"] ?? "";

    $action =
        $_POST["action"] ?? "";


    // --------------------------------------------------
    // CSRF CHECK
    // --------------------------------------------------

    if (
        empty($csrfToken) ||
        empty($_SESSION["admin_product_csrf"]) ||
        !hash_equals(
            $_SESSION["admin_product_csrf"],
            $csrfToken
        )
    ) {

        $errorMessage =
            "Invalid request. Please refresh the page.";

    }

    else {


        // ==================================================
        // ADD PRODUCT
        // ==================================================

        if ($action === "add") {

            $name =
                trim($_POST["name"] ?? "");

            $category =
                trim($_POST["category"] ?? "");

            $description =
                trim($_POST["description"] ?? "");

            $price =
                filter_var(
                    $_POST["price"] ?? null,
                    FILTER_VALIDATE_FLOAT
                );

            $image =
                trim($_POST["image"] ?? "");

            $rating =
                filter_var(
                    $_POST["rating"] ?? null,
                    FILTER_VALIDATE_FLOAT
                );

            $stock =
                filter_var(
                    $_POST["stock"] ?? null,
                    FILTER_VALIDATE_INT
                );


            // --------------------------------------------------
            // VALIDATION
            // --------------------------------------------------

            if ($name === "") {

                $errorMessage =
                    "Product name is required.";

            }

            elseif (
                !in_array(
                    $category,
                    $allowedCategories,
                    true
                )
            ) {

                $errorMessage =
                    "Please select a valid category.";

            }

            elseif ($description === "") {

                $errorMessage =
                    "Product description is required.";

            }

            elseif (
                $price === false ||
                $price < 0
            ) {

                $errorMessage =
                    "Please enter a valid price.";

            }

            elseif (
                $rating === false ||
                $rating < 0 ||
                $rating > 5
            ) {

                $errorMessage =
                    "Rating must be between 0 and 5.";

            }

            elseif (
                $stock === false ||
                $stock < 0
            ) {

                $errorMessage =
                    "Stock cannot be negative.";

            }

            else {

                // --------------------------------------------------
                // INSERT
                // --------------------------------------------------

                $insertStmt = $pdo->prepare("
                    INSERT INTO products
                    (
                        name,
                        category,
                        description,
                        price,
                        image,
                        rating,
                        stock
                    )
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


                $successMessage =
                    "Product added successfully.";

            }

        }


        // ==================================================
        // UPDATE PRODUCT
        // ==================================================

        elseif ($action === "update") {

            $productId =
                filter_input(
                    INPUT_POST,
                    "product_id",
                    FILTER_VALIDATE_INT
                );

            $name =
                trim($_POST["name"] ?? "");

            $category =
                trim($_POST["category"] ?? "");

            $description =
                trim($_POST["description"] ?? "");

            $price =
                filter_var(
                    $_POST["price"] ?? null,
                    FILTER_VALIDATE_FLOAT
                );

            $image =
                trim($_POST["image"] ?? "");

            $rating =
                filter_var(
                    $_POST["rating"] ?? null,
                    FILTER_VALIDATE_FLOAT
                );

            $stock =
                filter_var(
                    $_POST["stock"] ?? null,
                    FILTER_VALIDATE_INT
                );


            // --------------------------------------------------
            // VALIDATION
            // --------------------------------------------------

            if (
                !$productId ||
                $productId <= 0
            ) {

                $errorMessage =
                    "Invalid product.";

            }

            elseif ($name === "") {

                $errorMessage =
                    "Product name is required.";

            }

            elseif (
                !in_array(
                    $category,
                    $allowedCategories,
                    true
                )
            ) {

                $errorMessage =
                    "Please select a valid category.";

            }

            elseif ($description === "") {

                $errorMessage =
                    "Product description is required.";

            }

            elseif (
                $price === false ||
                $price < 0
            ) {

                $errorMessage =
                    "Please enter a valid price.";

            }

            elseif (
                $rating === false ||
                $rating < 0 ||
                $rating > 5
            ) {

                $errorMessage =
                    "Rating must be between 0 and 5.";

            }

            elseif (
                $stock === false ||
                $stock < 0
            ) {

                $errorMessage =
                    "Stock cannot be negative.";

            }

            else {

                // --------------------------------------------------
                // CHECK PRODUCT
                // --------------------------------------------------

                $checkStmt = $pdo->prepare("
                    SELECT id
                    FROM products
                    WHERE id = ?
                    LIMIT 1
                ");

                $checkStmt->execute([
                    $productId
                ]);


                if (!$checkStmt->fetch()) {

                    $errorMessage =
                        "Product not found.";

                }

                else {

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


                    $successMessage =
                        "Product updated successfully.";

                }

            }

        }


        // ==================================================
        // DELETE PRODUCT
        // ==================================================

        elseif ($action === "delete") {

            $productId =
                filter_input(
                    INPUT_POST,
                    "product_id",
                    FILTER_VALIDATE_INT
                );


            if (
                !$productId ||
                $productId <= 0
            ) {

                $errorMessage =
                    "Invalid product.";

            }

            else {

                // --------------------------------------------------
                // Check whether existing orders use this product.
                // We preserve products that are part of order history.
                // --------------------------------------------------

                $orderCheckStmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM order_items
                    WHERE product_id = ?
                ");

                $orderCheckStmt->execute([
                    $productId
                ]);

                $orderItemCount =
                    (int)$orderCheckStmt->fetchColumn();


                if ($orderItemCount > 0) {

                    $errorMessage =
                        "This product cannot be deleted because it is already part of an existing order.";

                }

                else {

                    try {

                        $pdo->beginTransaction();


                        // --------------------------------------------------
                        // Remove from carts first.
                        // --------------------------------------------------

                        $cartDeleteStmt =
                            $pdo->prepare("
                                DELETE FROM cart_items
                                WHERE product_id = ?
                            ");

                        $cartDeleteStmt->execute([
                            $productId
                        ]);


                        // --------------------------------------------------
                        // Delete product.
                        // --------------------------------------------------

                        $deleteStmt =
                            $pdo->prepare("
                                DELETE FROM products
                                WHERE id = ?
                            ");

                        $deleteStmt->execute([
                            $productId
                        ]);


                        if (
                            $deleteStmt->rowCount() !== 1
                        ) {

                            throw new Exception(
                                "Product was not found."
                            );

                        }


                        $pdo->commit();


                        $successMessage =
                            "Product deleted successfully.";

                    }

                    catch (Throwable $e) {

                        if (
                            $pdo->inTransaction()
                        ) {

                            $pdo->rollBack();

                        }


                        $errorMessage =
                            "Unable to delete the product.";

                    }

                }

            }

        }


        else {

            $errorMessage =
                "Invalid action.";

        }


        // --------------------------------------------------
        // Regenerate CSRF token after POST.
        // --------------------------------------------------

        $_SESSION["admin_product_csrf"] =
            bin2hex(random_bytes(32));

    }

}


// ==================================================
// SEARCH
// ==================================================

$search =
    trim($_GET["search"] ?? "");


// ==================================================
// CATEGORY FILTER
// ==================================================

$selectedCategory =
    trim($_GET["category"] ?? "");


if (
    $selectedCategory !== "" &&
    !in_array(
        $selectedCategory,
        $allowedCategories,
        true
    )
) {

    $selectedCategory = "";

}


// ==================================================
// GET PRODUCTS
// ==================================================

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


// --------------------------------------------------
// SEARCH
// --------------------------------------------------

if ($search !== "") {

    $sql .= "
        AND (
            name LIKE ?
            OR category LIKE ?
            OR description LIKE ?
        )
    ";

    $searchTerm =
        "%" . $search . "%";

    $params[] =
        $searchTerm;

    $params[] =
        $searchTerm;

    $params[] =
        $searchTerm;

}


// --------------------------------------------------
// CATEGORY
// --------------------------------------------------

if ($selectedCategory !== "") {

    $sql .= "
        AND category = ?
    ";

    $params[] =
        $selectedCategory;

}


// --------------------------------------------------
// ORDER
// --------------------------------------------------

$sql .= "
    ORDER BY id ASC
";


// --------------------------------------------------
// EXECUTE
// --------------------------------------------------

$productStmt =
    $pdo->prepare($sql);

$productStmt->execute(
    $params
);

$products =
    $productStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==================================================
// PRODUCT COUNTS
// ==================================================

$totalProductsStmt =
    $pdo->query("
        SELECT COUNT(*)
        FROM products
    ");

$totalProducts =
    (int)$totalProductsStmt->fetchColumn();


$inStockStmt =
    $pdo->query("
        SELECT COUNT(*)
        FROM products
        WHERE stock > 0
    ");

$inStock =
    (int)$inStockStmt->fetchColumn();


$outOfStockStmt =
    $pdo->query("
        SELECT COUNT(*)
        FROM products
        WHERE stock <= 0
    ");

$outOfStock =
    (int)$outOfStockStmt->fetchColumn();


// ==================================================
// ADMIN NAME
// ==================================================

$adminName =
    $_SESSION["username"] ??
    "Administrator";


// ==================================================
// EDIT PRODUCT
// ==================================================

$editId =
    filter_input(
        INPUT_GET,
        "edit",
        FILTER_VALIDATE_INT
    );

$editProduct = null;


if (
    $editId &&
    $editId > 0
) {

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

    $editStmt->execute([
        $editId
    ]);

    $editProduct =
        $editStmt->fetch(
            PDO::FETCH_ASSOC
        );

}

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
        FROSTCORE - Admin Products
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
                rgba(77,188,244,0.08);

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
                rgba(114,227,138,0.08);

            border:
                1px solid
                rgba(114,227,138,0.35);

        }


        .message.error {

            color:
                #FF9A9A;

            background:
                rgba(255,95,95,0.08);

            border:
                1px solid
                rgba(255,95,95,0.35);

        }


        /* ==================================================
           STATS
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
                9px;

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
           TOOLBAR
        ================================================== */

        .toolbar {

            display:
                flex;

            align-items:
                center;

            gap:
                8px;

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
                250px;

        }


        .search-input {

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


        .search-input:focus {

            border-color:
                #4DBCF4;

        }


        .search-button {

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


        .add-button {

            padding:
                10px 14px;

            color:
                #050A16;

            background:
                #72E38A;

            border:
                1px solid #72E38A;

            font-size:
                8px;

            font-weight:
                800;

        }


        .add-button:hover {

            filter:
                brightness(1.05);

        }


        /* ==================================================
           FORM CARD
        ================================================== */

        .product-form-card {

            margin-bottom:
                25px;

            padding:
                25px;

            background:
                #111A31;

            border:
                1px solid #263452;

        }


        .product-form-card h2 {

            margin:
                0 0 20px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                15px;

        }


        .product-form {

            display:
                grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap:
                15px;

        }


        .form-field {

            display:
                flex;

            flex-direction:
                column;

            gap:
                7px;

        }


        .form-field.full {

            grid-column:
                1 / -1;

        }


        .form-field label {

            color:
                #AAB5CA;

            font-size:
                8px;

            font-weight:
                700;

            letter-spacing:
                0.5px;

        }


        .form-field input,
        .form-field select,
        .form-field textarea {

            width:
                100%;

            background:
                #081225;

            color:
                #F4F7FF;

            border:
                1px solid #263452;

            outline:
                none;

            font-family:
                inherit;

            font-size:
                9px;

        }


        .form-field input,
        .form-field select {

            height:
                40px;

            padding:
                0 10px;

        }


        .form-field textarea {

            min-height:
                100px;

            padding:
                10px;

            resize:
                vertical;

        }


        .form-field input:focus,
        .form-field select:focus,
        .form-field textarea:focus {

            border-color:
                #4DBCF4;

        }


        .form-actions {

            grid-column:
                1 / -1;

            display:
                flex;

            gap:
                8px;

            margin-top:
                5px;

        }


        .save-button {

            padding:
                11px 17px;

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


        .cancel-button {

            display:
                inline-flex;

            align-items:
                center;

            padding:
                11px 17px;

            color:
                #AAB5CA;

            background:
                #081225;

            border:
                1px solid #263452;

            font-size:
                8px;

            font-weight:
                800;

        }


        /* ==================================================
           TABLE
        ================================================== */

        .products-card {

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


        .products-table {

            width:
                100%;

            min-width:
                1050px;

            border-collapse:
                collapse;

        }


        .products-table th {

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


        .products-table td {

            padding:
                13px 12px;

            color:
                #AAB5CA;

            font-size:
                9px;

            border-bottom:
                1px solid
                rgba(38,52,82,0.7);

            vertical-align:
                middle;

        }


        .products-table tr:last-child td {

            border-bottom:
                none;

        }


        /* ==================================================
           PRODUCT IMAGE
        ================================================== */

        .product-thumb {

            width:
                55px;

            height:
                55px;

            object-fit:
                contain;

            padding:
                5px;

            background:
                #081225;

            border:
                1px solid #263452;

        }


        .product-name {

            color:
                #F4F7FF;

            font-weight:
                700;

        }


        .product-category {

            color:
                #68758D;

            font-size:
                8px;

        }


        /* ==================================================
           STOCK
        ================================================== */

        .stock-badge {

            display:
                inline-block;

            padding:
                5px 8px;

            border:
                1px solid;

            font-size:
                7px;

            font-weight:
                800;

        }


        .stock-good {

            color:
                #72E38A;

            border-color:
                rgba(114,227,138,0.35);

        }


        .stock-low {

            color:
                #FFD166;

            border-color:
                rgba(255,209,102,0.35);

        }


        .stock-empty {

            color:
                #FF7F8F;

            border-color:
                rgba(255,127,143,0.35);

        }


        /* ==================================================
           ACTIONS
        ================================================== */

        .edit-link {

            display:
                inline-block;

            padding:
                7px 10px;

            margin-right:
                5px;

            color:
                #4DBCF4;

            border:
                1px solid #4DBCF4;

            font-size:
                8px;

            font-weight:
                800;

        }


        .delete-button {

            padding:
                7px 10px;

            color:
                #FF7F8F;

            background:
                transparent;

            border:
                1px solid
                rgba(255,127,143,0.5);

            font-size:
                8px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        .edit-link:hover {

            background:
                rgba(77,188,244,0.08);

        }


        .delete-button:hover {

            background:
                rgba(255,127,143,0.08);

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
           INFO NOTE
        ================================================== */

        .info-note {

            margin-top:
                15px;

            padding:
                13px 15px;

            color:
                #68758D;

            background:
                rgba(77,188,244,0.04);

            border:
                1px solid #263452;

            font-size:
                8px;

            line-height:
                1.6;

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


        @media (max-width: 650px) {

            .stats-grid {

                grid-template-columns:
                    1fr;

            }


            .product-form {

                grid-template-columns:
                    1fr;

            }


            .form-field.full {

                grid-column:
                    auto;

            }


            .form-actions {

                grid-column:
                    auto;

                flex-wrap:
                    wrap;

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


        <a href="orders.php">
            ORDERS
        </a>


        <a href="customers.php">
            CUSTOMERS
        </a>


        <a
            href="products.php"
            class="active"
        >
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
                PRODUCTS
            </h1>


            <p>
                Manage FROSTCORE products, pricing, stock, and catalog information.
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
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-label">
                    TOTAL PRODUCTS
                </div>

                <div class="stat-value">
                    <?= $totalProducts ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    IN STOCK
                </div>

                <div class="stat-value">
                    <?= $inStock ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    OUT OF STOCK
                </div>

                <div class="stat-value">
                    <?= $outOfStock ?>
                </div>

            </div>


        </section>



        <!-- ==================================================
             TOOLBAR
        ================================================== -->

        <div class="toolbar">


            <form
                method="GET"
                action="products.php"
                class="search-form"
            >


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


                <button
                    type="submit"
                    class="search-button"
                >
                    SEARCH
                </button>


            </form>


            <a
                href="products.php"
                class="
                    filter-link
                    <?= $selectedCategory === "" ? "active" : "" ?>
                "
            >
                ALL
            </a>


            <a
                href="products.php?category=Phone+Cooler"
                class="
                    filter-link
                    <?= $selectedCategory === "Phone Cooler" ? "active" : "" ?>
                "
            >
                PHONE
            </a>


            <a
                href="products.php?category=Laptop+Cooler"
                class="
                    filter-link
                    <?= $selectedCategory === "Laptop Cooler" ? "active" : "" ?>
                "
            >
                LAPTOP
            </a>


            <a
                href="products.php?category=Bundle"
                class="
                    filter-link
                    <?= $selectedCategory === "Bundle" ? "active" : "" ?>
                "
            >
                BUNDLE
            </a>


            <a
                href="products.php?add=1"
                class="add-button"
                style="display:none;"
            >
                ADD PRODUCT
            </a>


        </div>



        <!-- ==================================================
             ADD / EDIT FORM
        ================================================== -->

        <?php if ($editProduct): ?>


            <section class="product-form-card">


                <h2>
                    EDIT PRODUCT #<?= (int)$editProduct["id"] ?>
                </h2>


                <form
                    method="POST"
                    action="products.php"
                    class="product-form"
                >


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(
                            $_SESSION["admin_product_csrf"]
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="update"
                    >


                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int)$editProduct["id"] ?>"
                    >


                    <div class="form-field">

                        <label for="name">
                            PRODUCT NAME
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="<?= e(
                                $editProduct["name"]
                            ) ?>"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="form-field">

                        <label for="category">
                            CATEGORY
                        </label>

                        <select
                            id="category"
                            name="category"
                            required
                        >


                            <?php foreach (
                                $allowedCategories
                                as $category
                            ): ?>

                                <option
                                    value="<?= e($category) ?>"
                                    <?= $editProduct["category"] === $category
                                        ? "selected"
                                        : "" ?>
                                >
                                    <?= e($category) ?>
                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                    <div class="form-field">

                        <label for="price">
                            PRICE
                        </label>

                        <input
                            id="price"
                            type="number"
                            name="price"
                            value="<?= e(
                                $editProduct["price"]
                            ) ?>"
                            min="0"
                            step="0.01"
                            required
                        >

                    </div>


                    <div class="form-field">

                        <label for="stock">
                            STOCK
                        </label>

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

                        <label for="rating">
                            RATING
                        </label>

                        <input
                            id="rating"
                            type="number"
                            name="rating"
                            value="<?= e(
                                $editProduct["rating"]
                            ) ?>"
                            min="0"
                            max="5"
                            step="0.1"
                            required
                        >

                    </div>


                    <div class="form-field">

                        <label for="image">
                            IMAGE PATH
                        </label>

                        <input
                            id="image"
                            type="text"
                            name="image"
                            value="<?= e(
                                $editProduct["image"]
                            ) ?>"
                            maxlength="255"
                            placeholder="../assets/products/product-image.png"
                        >

                    </div>


                    <div class="form-field full">

                        <label for="description">
                            DESCRIPTION
                        </label>

                        <textarea
                            id="description"
                            name="description"
                            maxlength="2000"
                            required
                        ><?= e(
                            $editProduct["description"]
                        ) ?></textarea>

                    </div>


                    <div class="form-actions">


                        <button
                            type="submit"
                            class="save-button"
                        >
                            SAVE CHANGES
                        </button>


                        <a
                            href="products.php"
                            class="cancel-button"
                        >
                            CANCEL
                        </a>


                    </div>


                </form>


            </section>


        <?php endif; ?>



        <!-- ==================================================
             ADD PRODUCT BUTTON
        ================================================== -->

        <div
            style="
                margin-bottom:20px;
                text-align:right;
            "
        >

            <a
                href="?new=1"
                class="add-button"
            >
                + ADD NEW PRODUCT
            </a>

        </div>



        <!-- ==================================================
             NEW PRODUCT FORM
        ================================================== -->

        <?php if (isset($_GET["new"])): ?>


            <section class="product-form-card">


                <h2>
                    ADD NEW PRODUCT
                </h2>


                <form
                    method="POST"
                    action="products.php"
                    class="product-form"
                >


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(
                            $_SESSION["admin_product_csrf"]
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="action"
                        value="add"
                    >


                    <div class="form-field">

                        <label for="new-name">
                            PRODUCT NAME
                        </label>

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

                        <label for="new-category">
                            CATEGORY
                        </label>

                        <select
                            id="new-category"
                            name="category"
                            required
                        >

                            <option value="">
                                Select category
                            </option>


                            <?php foreach (
                                $allowedCategories
                                as $category
                            ): ?>

                                <option
                                    value="<?= e($category) ?>"
                                >
                                    <?= e($category) ?>
                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                    <div class="form-field">

                        <label for="new-price">
                            PRICE
                        </label>

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

                        <label for="new-stock">
                            STOCK
                        </label>

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

                        <label for="new-rating">
                            RATING
                        </label>

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

                        <label for="new-image">
                            IMAGE PATH
                        </label>

                        <input
                            id="new-image"
                            type="text"
                            name="image"
                            maxlength="255"
                            placeholder="../assets/products/product-image.png"
                        >

                    </div>


                    <div class="form-field full">

                        <label for="new-description">
                            DESCRIPTION
                        </label>

                        <textarea
                            id="new-description"
                            name="description"
                            maxlength="2000"
                            placeholder="Enter product description..."
                            required
                        ></textarea>

                    </div>


                    <div class="form-actions">


                        <button
                            type="submit"
                            class="save-button"
                        >
                            ADD PRODUCT
                        </button>


                        <a
                            href="products.php"
                            class="cancel-button"
                        >
                            CANCEL
                        </a>


                    </div>


                </form>


            </section>


        <?php endif; ?>



        <!-- ==================================================
             PRODUCTS TABLE
        ================================================== -->

        <section class="products-card">


            <?php if (!empty($products)): ?>


                <div class="table-wrap">

                    <table class="products-table">


                        <thead>

                            <tr>

                                <th>
                                    IMAGE
                                </th>

                                <th>
                                    PRODUCT
                                </th>

                                <th>
                                    CATEGORY
                                </th>

                                <th>
                                    PRICE
                                </th>

                                <th>
                                    RATING
                                </th>

                                <th>
                                    STOCK
                                </th>

                                <th>
                                    ACTIONS
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $products
                                as $product
                            ): ?>


                                <?php

                                $stock =
                                    (int)$product["stock"];

                                if ($stock <= 0) {

                                    $stockClass =
                                        "stock-empty";

                                    $stockText =
                                        "OUT OF STOCK";

                                }

                                elseif ($stock <= 5) {

                                    $stockClass =
                                        "stock-low";

                                    $stockText =
                                        $stock .
                                        " LEFT";

                                }

                                else {

                                    $stockClass =
                                        "stock-good";

                                    $stockText =
                                        $stock .
                                        " IN STOCK";

                                }

                                ?>


                                <tr>


                                    <td>


                                        <?php

                                        $imagePath =
                                            trim(
                                                (string)$product["image"]
                                            );

                                        if (
                                            $imagePath === ""
                                        ) {

                                            $imagePath =
                                                "../assets/products/fc1-cooler.svg";

                                        }

                                        else {

                                            // If the stored path begins
                                            // with assets/, it is relative
                                            // to the project root.
                                            if (
                                                str_starts_with(
                                                    $imagePath,
                                                    "assets/"
                                                )
                                            ) {

                                                $imagePath =
                                                    "../" .
                                                    $imagePath;

                                            }

                                        }

                                        ?>


                                        <img
                                            src="<?= e(
                                                $imagePath
                                            ) ?>"
                                            alt="<?= e(
                                                $product["name"]
                                            ) ?>"
                                            class="product-thumb"
                                            onerror="this.src='../assets/products/fc1-cooler.svg';"
                                        >


                                    </td>


                                    <td>


                                        <div
                                            class="product-name"
                                        >

                                            <?= e(
                                                $product["name"]
                                            ) ?>

                                        </div>


                                        <div
                                            class="product-category"
                                        >

                                            ID:
                                            #<?= (int)$product["id"] ?>

                                        </div>


                                    </td>


                                    <td>

                                        <?= e(
                                            $product["category"]
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong
                                            style="
                                                color:#F4F7FF;
                                            "
                                        >

                                            <?= money(
                                                $product["price"]
                                            ) ?>

                                        </strong>

                                    </td>


                                    <td>

                                        <?= number_format(
                                            (float)$product["rating"],
                                            1
                                        ) ?>

                                        / 5

                                    </td>


                                    <td>

                                        <span
                                            class="
                                                stock-badge
                                                <?= e(
                                                    $stockClass
                                                ) ?>
                                            "
                                        >

                                            <?= e(
                                                $stockText
                                            ) ?>

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
                                            style="
                                                display:inline;
                                            "
                                            onsubmit="
                                                return confirm(
                                                    'Are you sure you want to delete this product?'
                                                );
                                            "
                                        >


                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(
                                                    $_SESSION["admin_product_csrf"]
                                                ) ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >


                                            <input
                                                type="hidden"
                                                name="product_id"
                                                value="<?= (int)$product["id"] ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="delete-button"
                                            >
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



        <!-- ==================================================
             NOTE
        ================================================== -->

        <div class="info-note">

            Products that already appear in an existing order
            cannot be deleted, because deleting them would damage
            your order history. You can edit their name, price,
            stock, rating, description, or image instead.

        </div>


    </main>

</div>

</body>

</html>