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

if (empty($_SESSION["admin_reviews_csrf"])) {
    $_SESSION["admin_reviews_csrf"] = bin2hex(random_bytes(32));
}

$successMessage = "";
$errorMessage = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "delete"
) {
    $csrfToken = $_POST["csrf_token"] ?? "";

    if (
        empty($csrfToken) ||
        !hash_equals($_SESSION["admin_reviews_csrf"], $csrfToken)
    ) {
        $errorMessage = "Invalid request. Please refresh the page.";
    } else {
        $reviewId = filter_input(
            INPUT_POST,
            "review_id",
            FILTER_VALIDATE_INT
        );

        if (!$reviewId || $reviewId <= 0) {
            $errorMessage = "Invalid review.";
        } else {
            try {
                $deleteStmt = $pdo->prepare("
                    DELETE FROM reviews
                    WHERE id = ?
                    LIMIT 1
                ");

                $deleteStmt->execute([$reviewId]);

                if ($deleteStmt->rowCount() === 1) {
                    $successMessage = "Review deleted successfully.";
                } else {
                    $errorMessage = "Review not found.";
                }
            } catch (Throwable $e) {
                $errorMessage = "Unable to delete the review.";
            }
        }
    }

    $_SESSION["admin_reviews_csrf"] = bin2hex(random_bytes(32));
}

$reviewsStmt = $pdo->query("
    SELECT
        reviews.id,
        reviews.user_id,
        reviews.product_id,
        reviews.rating,
        reviews.review_text,
        reviews.created_at,
        users.full_name,
        users.email,
        products.name AS product_name
    FROM reviews
    LEFT JOIN users
        ON users.id = reviews.user_id
    LEFT JOIN products
        ON products.id = reviews.product_id
    ORDER BY reviews.created_at DESC
");

$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);

$totalReviews = count($reviews);
$fiveStarReviews = 0;
$fourStarReviews = 0;
$threeStarReviews = 0;
$twoStarReviews = 0;
$oneStarReviews = 0;

foreach ($reviews as $review) {
    $rating = (int)$review["rating"];

    switch ($rating) {
        case 5:
            $fiveStarReviews++;
            break;
        case 4:
            $fourStarReviews++;
            break;
        case 3:
            $threeStarReviews++;
            break;
        case 2:
            $twoStarReviews++;
            break;
        case 1:
            $oneStarReviews++;
            break;
    }
}

$adminName = $_SESSION["username"] ?? "Administrator";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FROSTCORE - Admin Reviews</title>
    <link rel="stylesheet" href="../css/admin/reviews.css">
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
        <a href="products.php">PRODUCTS</a>
        <a href="reviews.php" class="active">REVIEWS</a>

        <div class="sidebar-title">WEBSITE</div>

        <a href="../products.php">VIEW STORE</a>
        <a href="../index.php">HOMEPAGE</a>
    </aside>

    <main class="admin-main">

        <div class="page-title">
            <h1>REVIEWS</h1>
            <p>View customer feedback for FROSTCORE products.</p>
        </div>

        <?php if ($successMessage !== ""): ?>
            <div class="message success-message">
                <?= e($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ""): ?>
            <div class="message error-message">
                <?= e($errorMessage) ?>
            </div>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">TOTAL REVIEWS</div>
                <div class="stat-value"><?= $totalReviews ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">5 STAR</div>
                <div class="stat-value"><?= $fiveStarReviews ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">4 STAR</div>
                <div class="stat-value"><?= $fourStarReviews ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-label">3 STAR</div>
                <div class="stat-value"><?= $threeStarReviews ?></div>
            </div>
        </section>

        <section class="reviews-card">

            <?php if (empty($reviews)): ?>

                <div class="empty-reviews">
                    <h2>NO REVIEWS YET</h2>
                    <p>
                        Customer reviews will appear here after customers submit reviews.
                    </p>
                </div>

            <?php else: ?>

                <div class="table-wrap">
                    <table class="reviews-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>CUSTOMER</th>
                                <th>PRODUCT</th>
                                <th>RATING</th>
                                <th>REVIEW</th>
                                <th>DATE</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($reviews as $review): ?>

                                <?php
                                $rating = max(
                                    1,
                                    min(5, (int)$review["rating"])
                                );
                                ?>

                                <tr>
                                    <td>
                                        <span class="review-id">
                                            #<?= (int)$review["id"] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="reviewer-name">
                                            <?= e($review["full_name"] ?? "Unknown Customer") ?>
                                        </div>

                                        <div class="reviewer-email">
                                            <?= e($review["email"] ?? "Unknown Email") ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="product-name">
                                            <?= e($review["product_name"] ?? "Unknown Product") ?>
                                        </div>

                                        <div class="product-id">
                                            Product ID: <?= (int)$review["product_id"] ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="rating">
                                            <span class="stars">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?= $i <= $rating ? "★" : "☆" ?>
                                                <?php endfor; ?>
                                            </span>

                                            <br>

                                            <?= $rating ?> / 5
                                        </div>
                                    </td>

                                    <td>
                                        <div class="review-text">
                                            <?= nl2br(e($review["review_text"])) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="review-date">
                                            <?= e(date("M d, Y", strtotime($review["created_at"]))) ?>
                                            <br>
                                            <?= e(date("h:i A", strtotime($review["created_at"]))) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <form
                                            method="POST"
                                            action="reviews.php"
                                            onsubmit="return confirm('Delete this review permanently?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION["admin_reviews_csrf"]) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >

                                            <input
                                                type="hidden"
                                                name="review_id"
                                                value="<?= (int)$review["id"] ?>"
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

            <?php endif; ?>

        </section>

        <div class="info-note">
            Reviews are connected directly to the registered customer and product.
            The current database does not contain an approval/status column, so this
            page provides viewing and deletion only.
        </div>

    </main>
</div>

</body>
</html>