<?php

// ==================================================
// FROSTCORE ADMIN - REVIEWS
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
// HELPER FUNCTION
// ==================================================

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}


// ==================================================
// CSRF TOKEN
// ==================================================

if (
    empty($_SESSION["admin_reviews_csrf"])
) {

    $_SESSION["admin_reviews_csrf"] =
        bin2hex(random_bytes(32));

}


// ==================================================
// MESSAGES
// ==================================================

$successMessage = "";

$errorMessage = "";


// ==================================================
// DELETE REVIEW
// ==================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "delete"
) {

    $csrfToken =
        $_POST["csrf_token"] ?? "";


    // --------------------------------------------------
    // CSRF CHECK
    // --------------------------------------------------

    if (
        empty($csrfToken) ||
        !hash_equals(
            $_SESSION["admin_reviews_csrf"],
            $csrfToken
        )
    ) {

        $errorMessage =
            "Invalid request. Please refresh the page.";

    }

    else {

        $reviewId =
            filter_input(
                INPUT_POST,
                "review_id",
                FILTER_VALIDATE_INT
            );


        if (
            !$reviewId ||
            $reviewId <= 0
        ) {

            $errorMessage =
                "Invalid review.";

        }

        else {

            try {

                $deleteStmt = $pdo->prepare("
                    DELETE FROM reviews
                    WHERE id = ?
                    LIMIT 1
                ");


                $deleteStmt->execute([
                    $reviewId
                ]);


                if (
                    $deleteStmt->rowCount() === 1
                ) {

                    $successMessage =
                        "Review deleted successfully.";

                }

                else {

                    $errorMessage =
                        "Review not found.";

                }

            }
            catch (Throwable $e) {

                $errorMessage =
                    "Unable to delete the review.";

            }

        }

    }


    // --------------------------------------------------
    // Generate a new token
    // --------------------------------------------------

    $_SESSION["admin_reviews_csrf"] =
        bin2hex(random_bytes(32));

}


// ==================================================
// GET ALL REVIEWS
// ==================================================

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


$reviews =
    $reviewsStmt->fetchAll(
        PDO::FETCH_ASSOC
    );


// ==================================================
// STATISTICS
// ==================================================

$totalReviews =
    count($reviews);


$fiveStarReviews = 0;

$fourStarReviews = 0;

$threeStarReviews = 0;

$twoStarReviews = 0;

$oneStarReviews = 0;


foreach ($reviews as $review) {

    $rating =
        (int)$review["rating"];


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


// ==================================================
// ADMIN NAME
// ==================================================

$adminName =
    $_SESSION["username"] ??
    "Administrator";

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
        FROSTCORE - Admin Reviews
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

            padding:
                0 5%;

            display: flex;

            align-items: center;

            justify-content: space-between;

            background:
                #070D1C;

            border-bottom:
                1px solid #263452;

        }


        .admin-brand {

            display: flex;

            align-items: center;

            gap: 10px;

            color:
                #F4F7FF;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                18px;

            font-weight:
                700;

        }


        .admin-brand img {

            width:
                40px;

            height:
                40px;

            object-fit:
                contain;

        }


        .admin-header-right {

            display: flex;

            align-items: center;

            gap:
                18px;

        }


        .admin-name {

            color:
                #AAB5CA;

            font-size:
                10px;

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


        .success-message {

            color:
                #72E38A;

            background:
                rgba(114,227,138,0.08);

            border:
                1px solid
                rgba(114,227,138,0.35);

        }


        .error-message {

            color:
                #FF7F8F;

            background:
                rgba(255,127,143,0.08);

            border:
                1px solid
                rgba(255,127,143,0.35);

        }


        /* ==================================================
           STATISTICS
        ================================================== */

        .stats-grid {

            display:
                grid;

            grid-template-columns:
                repeat(4, 1fr);

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
           REVIEWS CARD
        ================================================== */

        .reviews-card {

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


        .reviews-table {

            width:
                100%;

            min-width:
                1000px;

            border-collapse:
                collapse;

        }


        .reviews-table th {

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


        .reviews-table td {

            padding:
                15px 12px;

            color:
                #AAB5CA;

            font-size:
                9px;

            vertical-align:
                top;

            border-bottom:
                1px solid
                rgba(38,52,82,0.7);

        }


        .reviews-table tr:last-child td {

            border-bottom:
                none;

        }


        /* ==================================================
           REVIEW ID
        ================================================== */

        .review-id {

            color:
                #68758D;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                8px;

        }


        /* ==================================================
           CUSTOMER
        ================================================== */

        .reviewer-name {

            color:
                #F4F7FF;

            font-weight:
                700;

            margin-bottom:
                4px;

        }


        .reviewer-email {

            color:
                #68758D;

            font-size:
                8px;

        }


        /* ==================================================
           PRODUCT
        ================================================== */

        .product-name {

            color:
                #4DBCF4;

            font-weight:
                700;

            margin-bottom:
                4px;

        }


        .product-id {

            color:
                #68758D;

            font-size:
                8px;

        }


        /* ==================================================
           RATING
        ================================================== */

        .rating {

            color:
                #FFD166;

            white-space:
                nowrap;

            font-weight:
                800;

        }


        .stars {

            letter-spacing:
                1px;

        }


        /* ==================================================
           REVIEW TEXT
        ================================================== */

        .review-text {

            max-width:
                400px;

            color:
                #F4F7FF;

            line-height:
                1.6;

            word-break:
                break-word;

        }


        /* ==================================================
           DATE
        ================================================== */

        .review-date {

            color:
                #AAB5CA;

            font-size:
                8px;

            line-height:
                1.5;

        }


        /* ==================================================
           DELETE
        ================================================== */

        .delete-button {

            padding:
                8px 11px;

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


        .delete-button:hover {

            background:
                rgba(255,127,143,0.08);

        }


        /* ==================================================
           EMPTY
        ================================================== */

        .empty-reviews {

            padding:
                70px 20px;

            text-align:
                center;

        }


        .empty-reviews h2 {

            margin:
                0 0 10px;

            font-family:
                Orbitron,
                sans-serif;

            font-size:
                17px;

        }


        .empty-reviews p {

            margin:
                0;

            color:
                #68758D;

            font-size:
                9px;

        }


        /* ==================================================
           INFO
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

        @media (max-width: 950px) {

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


            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 600px) {

            .stats-grid {

                grid-template-columns:
                    1fr;

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


        <a href="products.php">
            PRODUCTS
        </a>


        <a
            href="reviews.php"
            class="active"
        >
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
                REVIEWS
            </h1>


            <p>
                View customer feedback for FROSTCORE products.
            </p>

        </div>



        <!-- ==================================================
             MESSAGES
        ================================================== -->

        <?php if (
            $successMessage !== ""
        ): ?>

            <div
                class="
                    message
                    success-message
                "
            >

                <?= e(
                    $successMessage
                ) ?>

            </div>

        <?php endif; ?>


        <?php if (
            $errorMessage !== ""
        ): ?>

            <div
                class="
                    message
                    error-message
                "
            >

                <?= e(
                    $errorMessage
                ) ?>

            </div>

        <?php endif; ?>



        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-label">
                    TOTAL REVIEWS
                </div>

                <div class="stat-value">

                    <?= $totalReviews ?>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    5 STAR
                </div>

                <div class="stat-value">

                    <?= $fiveStarReviews ?>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    4 STAR
                </div>

                <div class="stat-value">

                    <?= $fourStarReviews ?>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    3 STAR
                </div>

                <div class="stat-value">

                    <?= $threeStarReviews ?>

                </div>

            </div>


        </section>



        <!-- ==================================================
             REVIEWS TABLE
        ================================================== -->

        <section class="reviews-card">


            <?php if (
                empty($reviews)
            ): ?>


                <div class="empty-reviews">

                    <h2>
                        NO REVIEWS YET
                    </h2>


                    <p>
                        Customer reviews will appear here after
                        customers submit reviews.
                    </p>

                </div>


            <?php else: ?>


                <div class="table-wrap">

                    <table class="reviews-table">


                        <thead>

                            <tr>

                                <th>
                                    ID
                                </th>

                                <th>
                                    CUSTOMER
                                </th>

                                <th>
                                    PRODUCT
                                </th>

                                <th>
                                    RATING
                                </th>

                                <th>
                                    REVIEW
                                </th>

                                <th>
                                    DATE
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $reviews
                                as $review
                            ): ?>


                                <?php

                                $rating =
                                    max(
                                        1,
                                        min(
                                            5,
                                            (int)$review["rating"]
                                        )
                                    );

                                ?>


                                <tr>


                                    <!-- ID -->

                                    <td>

                                        <span
                                            class="review-id"
                                        >

                                            #<?= (int)$review["id"] ?>

                                        </span>

                                    </td>



                                    <!-- CUSTOMER -->

                                    <td>

                                        <div
                                            class="reviewer-name"
                                        >

                                            <?= e(
                                                $review["full_name"]
                                                ?? "Unknown Customer"
                                            ) ?>

                                        </div>


                                        <div
                                            class="reviewer-email"
                                        >

                                            <?= e(
                                                $review["email"]
                                                ?? "Unknown Email"
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- PRODUCT -->

                                    <td>

                                        <div
                                            class="product-name"
                                        >

                                            <?= e(
                                                $review["product_name"]
                                                ?? "Unknown Product"
                                            ) ?>

                                        </div>


                                        <div
                                            class="product-id"
                                        >

                                            Product ID:

                                            <?= (int)$review["product_id"] ?>

                                        </div>

                                    </td>



                                    <!-- RATING -->

                                    <td>

                                        <div
                                            class="rating"
                                        >

                                            <span
                                                class="stars"
                                            >

                                                <?php for (
                                                    $i = 1;
                                                    $i <= 5;
                                                    $i++
                                                ): ?>

                                                    <?= $i <= $rating
                                                        ? "★"
                                                        : "☆" ?>

                                                <?php endfor; ?>

                                            </span>


                                            <br>


                                            <?= $rating ?>
                                            / 5

                                        </div>

                                    </td>



                                    <!-- REVIEW -->

                                    <td>

                                        <div
                                            class="review-text"
                                        >

                                            <?= nl2br(
                                                e(
                                                    $review["review_text"]
                                                )
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <div
                                            class="review-date"
                                        >

                                            <?= e(
                                                date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $review["created_at"]
                                                    )
                                                )
                                            ) ?>


                                            <br>


                                            <?= e(
                                                date(
                                                    "h:i A",
                                                    strtotime(
                                                        $review["created_at"]
                                                    )
                                                )
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- ACTION -->

                                    <td>


                                        <form
                                            method="POST"
                                            action="reviews.php"
                                            onsubmit="
                                                return confirm(
                                                    'Delete this review permanently?'
                                                );
                                            "
                                        >


                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e(
                                                    $_SESSION[
                                                        "admin_reviews_csrf"
                                                    ]
                                                ) ?>"
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



        <!-- ==================================================
             INFO
        ================================================== -->

        <div class="info-note">

            Reviews are connected directly to the registered
            customer and product. The current database does not
            contain an approval/status column, so this page provides
            viewing and deletion only.

        </div>


    </main>

</div>

</body>

</html>