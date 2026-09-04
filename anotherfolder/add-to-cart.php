<?php

session_start();

require_once "database/config.php";

header("Content-Type: application/json");


// --------------------------------------------------
// CHECK LOGIN
// --------------------------------------------------

if (empty($_SESSION["user_id"])) {

    echo json_encode([
        "success" => false,
        "logged_in" => false,
        "message" => "Please log in first."
    ]);

    exit;
}

$userId = (int)$_SESSION["user_id"];


// --------------------------------------------------
// GET PRODUCT ID
// --------------------------------------------------

$productId = filter_input(
    INPUT_POST,
    "product_id",
    FILTER_VALIDATE_INT
);

if (!$productId || $productId <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid product."
    ]);

    exit;
}


// --------------------------------------------------
// CHECK PRODUCT
// --------------------------------------------------

$productStmt = $pdo->prepare("
    SELECT
        id,
        name,
        stock
    FROM products
    WHERE id = ?
    LIMIT 1
");

$productStmt->execute([
    $productId
]);

$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {

    echo json_encode([
        "success" => false,
        "message" => "Product not found."
    ]);

    exit;
}

$stock = (int)$product["stock"];

if ($stock <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "This product is out of stock."
    ]);

    exit;
}


// --------------------------------------------------
// CHECK EXISTING CART ITEM
// --------------------------------------------------

$cartStmt = $pdo->prepare("
    SELECT
        id,
        quantity
    FROM cart_items
    WHERE user_id = ?
    AND product_id = ?
    LIMIT 1
");

$cartStmt->execute([
    $userId,
    $productId
]);

$existing = $cartStmt->fetch(PDO::FETCH_ASSOC);


// --------------------------------------------------
// ADD OR INCREASE QUANTITY
// --------------------------------------------------

if ($existing) {

    $newQuantity = (int)$existing["quantity"] + 1;

    // Don't go above available stock.
    if ($newQuantity > $stock) {
        $newQuantity = $stock;
    }

    $updateStmt = $pdo->prepare("
        UPDATE cart_items
        SET quantity = ?
        WHERE id = ?
    ");

    $updateStmt->execute([
        $newQuantity,
        $existing["id"]
    ]);

} else {

    $insertStmt = $pdo->prepare("
        INSERT INTO cart_items
        (
            user_id,
            product_id,
            quantity
        )
        VALUES (?, ?, 1)
    ");

    $insertStmt->execute([
        $userId,
        $productId
    ]);
}


// --------------------------------------------------
// GET UPDATED CART COUNT
// --------------------------------------------------

$countStmt = $pdo->prepare("
    SELECT COALESCE(SUM(quantity), 0)
    FROM cart_items
    WHERE user_id = ?
");

$countStmt->execute([
    $userId
]);

$cartCount = (int)$countStmt->fetchColumn();


// --------------------------------------------------
// SUCCESS RESPONSE
// --------------------------------------------------

echo json_encode([
    "success" => true,
    "logged_in" => true,
    "product_name" => $product["name"],
    "cart_count" => $cartCount,
    "message" => "Product added to cart."
]);

exit;