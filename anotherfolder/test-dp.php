<?php

// Connect to the FROSTCORE database
require_once "database/config.php";

echo "<h2>FROSTCORE Database Test</h2>";

// Test 1: Check the users table
$users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

echo "Users table: ✅ Working<br>";
echo "Users count: " . $users . "<br><br>";

// Test 2: Check the products table
$products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

echo "Products table: ✅ Working<br>";
echo "Products count: " . $products . "<br><br>";

// Test 3: Check the cart_items table
$cart = $pdo->query("SELECT COUNT(*) FROM cart_items")->fetchColumn();

echo "Cart table: ✅ Working<br>";
echo "Cart items: " . $cart . "<br><br>";

// Test 4: Check the orders table
$orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

echo "Orders table: ✅ Working<br>";
echo "Orders count: " . $orders . "<br><br>";

// Test 5: Check the order_items table
$orderItems = $pdo->query("SELECT COUNT(*) FROM order_items")->fetchColumn();

echo "Order items table: ✅ Working<br>";
echo "Order items count: " . $orderItems . "<br><br>";

// Test 6: Check the reviews table
$reviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

echo "Reviews table: ✅ Working<br>";
echo "Reviews count: " . $reviews . "<br><br>";

echo "<h3>🎉 Database test completed successfully!</h3>";

?>