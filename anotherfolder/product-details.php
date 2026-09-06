<?php

// ==================================================
// FROSTCORE — PRODUCT DETAILS
// ==================================================

session_start();

require_once "database/config.php";


// ==================================================
// GET PRODUCT ID
// ==================================================

$product_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (
    !$product_id ||
    $product_id <= 0
) {

    http_response_code(404);
    die("Product not found.");

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


function money($value)
{
    return "₱" . number_format(
        (float)$value,
        2
    );
}


// ==================================================
// LOGIN / ROLE
// ==================================================

$isLoggedIn =
    !empty($_SESSION["user_id"]);

$userId =
    $isLoggedIn
        ? (int)$_SESSION["user_id"]
        : 0;

$userRole =
    $_SESSION["role"] ?? "";

$isAdmin =
    $userRole === "admin";


// ==================================================
// CSRF TOKEN
// ==================================================

if (
    empty($_SESSION["review_csrf_token"])
) {

    $_SESSION["review_csrf_token"] =
        bin2hex(
            random_bytes(32)
        );

}


// ==================================================
// REVIEW MESSAGES
// ==================================================

$reviewError =
    "";

$reviewSuccess =
    "";


// ==================================================
// GET PRODUCT
// ==================================================

$productStmt = $pdo->prepare("

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


$productStmt->execute([
    $product_id
]);


$product =
    $productStmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$product) {

    http_response_code(404);

    die("Product not found.");

}


// ==================================================
// PRODUCT DATA
// ==================================================

$product_name =
    $product["name"];

$product_category =
    $product["category"];

$product_description =
    $product["description"];

$product_price =
    $product["price"];

$product_rating =
    (float)$product["rating"];

$product_stock =
    (int)$product["stock"];


// ==================================================
// PRODUCT IMAGE
// ==================================================

$product_image =
    trim(
        (string)$product["image"]
    );


if (
    $product_image === "" ||
    !file_exists(
        __DIR__ . "/" . $product_image
    )
) {

    $product_image =
        "assets/fc1-cooler.svg";

}


// ==================================================
// CART COUNT
// ==================================================

$cart_count = 0;


if ($isLoggedIn) {

    $cartStmt = $pdo->prepare("

        SELECT
            COALESCE(
                SUM(quantity),
                0
            )

        FROM cart_items

        WHERE user_id = ?

    ");


    $cartStmt->execute([
        $userId
    ]);


    $cart_count =
        (int)$cartStmt->fetchColumn();

}


// ==================================================
// HANDLE REVIEW
// ==================================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["action"] ?? "") === "submit_review"
) {


    if (!$isLoggedIn) {

        $reviewError =
            "Please log in before submitting a review.";

    }

    elseif ($isAdmin) {

        $reviewError =
            "Administrator accounts cannot submit product reviews.";

    }

    elseif (
        empty($_POST["csrf_token"]) ||
        empty($_SESSION["review_csrf_token"]) ||
        !hash_equals(
            $_SESSION["review_csrf_token"],
            $_POST["csrf_token"]
        )
    ) {

        $reviewError =
            "Invalid request. Please refresh the page.";

    }

    else {


        $submittedRating =
            filter_var(
                $_POST["rating"] ?? null,
                FILTER_VALIDATE_INT
            );


        $submittedReview =
            trim(
                $_POST["review_text"] ?? ""
            );


        if (
            $submittedRating === false ||
            $submittedRating < 1 ||
            $submittedRating > 5
        ) {

            $reviewError =
                "Please select a rating from 1 to 5 stars.";

        }

        elseif (
            $submittedReview === ""
        ) {

            $reviewError =
                "Please write a review.";

        }

        elseif (
            strlen($submittedReview) > 1000
        ) {

            $reviewError =
                "Your review cannot exceed 1000 characters.";

        }

        else {


            // ----------------------------------------------
            // CUSTOMER MUST HAVE A COMPLETED ORDER
            // ----------------------------------------------

            $purchaseStmt = $pdo->prepare("

                SELECT
                    COUNT(*)

                FROM orders

                INNER JOIN order_items
                    ON order_items.order_id = orders.id

                WHERE orders.user_id = ?

                AND orders.status = 'Completed'

                AND order_items.product_id = ?

            ");


            $purchaseStmt->execute([
                $userId,
                $product_id
            ]);


            $completedPurchase =
                (int)$purchaseStmt->fetchColumn();


            if (
                $completedPurchase <= 0
            ) {

                $reviewError =
                    "You can only review this product after completing a purchase.";

            }

            else {


                // ------------------------------------------
                // CHECK EXISTING REVIEW
                // ------------------------------------------

                $existingReviewStmt = $pdo->prepare("

                    SELECT
                        id

                    FROM reviews

                    WHERE user_id = ?

                    AND product_id = ?

                    LIMIT 1

                ");


                $existingReviewStmt->execute([
                    $userId,
                    $product_id
                ]);


                $existingReview =
                    $existingReviewStmt->fetchColumn();


                if ($existingReview) {

                    $reviewError =
                        "You have already reviewed this product.";

                }

                else {


                    // --------------------------------------
                    // INSERT REVIEW
                    // --------------------------------------

                    try {

                        $insertReviewStmt =
                            $pdo->prepare("

                                INSERT INTO reviews
                                (
                                    user_id,
                                    product_id,
                                    rating,
                                    review_text
                                )

                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    ?
                                )

                            ");


                        $insertReviewStmt->execute([
                            $userId,
                            $product_id,
                            $submittedRating,
                            $submittedReview
                        ]);


                        $reviewSuccess =
                            "Your review has been submitted successfully.";


                        $_SESSION["review_csrf_token"] =
                            bin2hex(
                                random_bytes(32)
                            );

                    }

                    catch (
                        PDOException $e
                    ) {

                        if (
                            $e->getCode() === "23000"
                        ) {

                            $reviewError =
                                "You have already reviewed this product.";

                        }

                        else {

                            $reviewError =
                                "Unable to submit your review right now.";

                        }

                    }

                }

            }

        }

    }

}


// ==================================================
// REVIEW SUMMARY
// ==================================================

$reviewSummaryStmt =
    $pdo->prepare("

        SELECT

            COUNT(*) AS review_count,

            COALESCE(
                AVG(rating),
                0
            ) AS review_average

        FROM reviews

        WHERE product_id = ?

    ");


$reviewSummaryStmt->execute([
    $product_id
]);


$reviewSummary =
    $reviewSummaryStmt->fetch(
        PDO::FETCH_ASSOC
    );


$reviewCount =
    (int)(
        $reviewSummary["review_count"] ?? 0
    );


$reviewAverage =
    (float)(
        $reviewSummary["review_average"] ?? 0
    );


// ==================================================
// CURRENT USER REVIEW STATUS
// ==================================================

$hasReviewed = false;

$canReview = false;

$completedPurchase = false;


if (
    $isLoggedIn &&
    !$isAdmin
) {


    $purchaseCheckStmt =
        $pdo->prepare("

            SELECT
                COUNT(*)

            FROM orders

            INNER JOIN order_items
                ON order_items.order_id = orders.id

            WHERE orders.user_id = ?

            AND orders.status = 'Completed'

            AND order_items.product_id = ?

        ");


    $purchaseCheckStmt->execute([
        $userId,
        $product_id
    ]);


    $completedPurchase =
        (int)
        $purchaseCheckStmt
        ->fetchColumn() > 0;


    $reviewCheckStmt =
        $pdo->prepare("

            SELECT
                id

            FROM reviews

            WHERE user_id = ?

            AND product_id = ?

            LIMIT 1

        ");


    $reviewCheckStmt->execute([
        $userId,
        $product_id
    ]);


    $hasReviewed =
        (bool)
        $reviewCheckStmt
        ->fetchColumn();


    if (
        $completedPurchase &&
        !$hasReviewed
    ) {

        $canReview = true;

    }

}


// ==================================================
// GET ALL REVIEWS
// ==================================================

$reviewsStmt =
    $pdo->prepare("

        SELECT

            reviews.id,
            reviews.rating,
            reviews.review_text,
            reviews.created_at,

            users.full_name

        FROM reviews

        LEFT JOIN users
            ON users.id = reviews.user_id

        WHERE reviews.product_id = ?

        ORDER BY reviews.created_at DESC

    ");


$reviewsStmt->execute([
    $product_id
]);


$reviews =
    $reviewsStmt->fetchAll(
        PDO::FETCH_ASSOC
    );

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
        <?= e($product_name) ?> — FROSTCORE
    </title>


    <link
        rel="stylesheet"
        href="product-details.css"
    >


    <style>

        /* ==================================================
           REVIEW SUMMARY
        ================================================== */

        .review-summary {

            display: flex;

            align-items: center;

            gap: 15px;

            margin-bottom: 25px;

            padding: 15px;

            background: #081225;

            border: 1px solid #263452;

        }


        .review-summary-rating {

            color: #FFD166;

            font-size: 16px;

            font-weight: 800;

        }


        .review-summary-text {

            color: #68758D;

            font-size: 9px;

        }


        /* ==================================================
           REVIEW FORM
        ================================================== */

        .review-form {

            margin-bottom: 30px;

            padding: 22px;

            background: #111A31;

            border: 1px solid #263452;

        }


        .review-form h3 {

            margin: 0 0 18px;

            font-family:
                Orbitron,
                sans-serif;

            font-size: 14px;

        }


        .review-message {

            margin-bottom: 18px;

            padding: 12px 14px;

            font-size: 9px;

        }


        .review-success {

            color: #72E38A;

            background:
                rgba(
                    114,
                    227,
                    138,
                    0.07
                );

            border:
                1px solid
                rgba(
                    114,
                    227,
                    138,
                    0.35
                );

        }


        .review-error {

            color: #FF7F8F;

            background:
                rgba(
                    255,
                    127,
                    143,
                    0.07
                );

            border:
                1px solid
                rgba(
                    255,
                    127,
                    143,
                    0.35
                );

        }


        /* ==================================================
           STAR INPUT
        ================================================== */

        .rating-label {

            display: block;

            margin-bottom: 8px;

            color: #AAB5CA;

            font-size: 8px;

            font-weight: 800;

        }


        .star-selector {

            display: flex;

            flex-direction: row-reverse;

            justify-content: flex-end;

            width: max-content;

            margin-bottom: 20px;

        }


        .star-selector input {

            position: absolute;

            opacity: 0;

        }


        .star-selector label {

            padding-right: 5px;

            color: #263452;

            font-size: 30px;

            line-height: 1;

            cursor: pointer;

            transition:
                color .15s ease;

        }


        .star-selector label:hover,

        .star-selector label:hover ~ label,

        .star-selector input:checked ~ label {

            color: #FFD166;

        }


        /* ==================================================
           TEXTAREA
        ================================================== */

        .review-textarea-label {

            display: block;

            margin-bottom: 8px;

            color: #AAB5CA;

            font-size: 8px;

            font-weight: 800;

        }


        .review-textarea {

            width: 100%;

            min-height: 120px;

            padding: 12px;

            resize: vertical;

            background: #081225;

            color: #F4F7FF;

            border: 1px solid #263452;

            outline: none;

            font-family:
                Inter,
                Arial,
                sans-serif;

            font-size: 10px;

            line-height: 1.5;

        }


        .review-textarea:focus {

            border-color: #4DBCF4;

        }


        .review-submit {

            margin-top: 12px;

            padding: 11px 18px;

            color: #050A16;

            background: #4DBCF4;

            border: 1px solid #4DBCF4;

            font-size: 8px;

            font-weight: 800;

            cursor: pointer;

        }


        /* ==================================================
           REVIEW NOTICE
        ================================================== */

        .review-notice {

            margin-bottom: 25px;

            padding: 16px;

            color: #AAB5CA;

            background: #081225;

            border: 1px solid #263452;

            font-size: 9px;

            line-height: 1.6;

        }


        .review-notice a {

            color: #4DBCF4;

            font-weight: 800;

        }


        /* ==================================================
           REVIEW LIST
        ================================================== */

        .review-list {

            display: grid;

            gap: 15px;

        }


        .review-card {

            padding: 20px;

            background: #081225;

            border: 1px solid #263452;

        }


        .review-card-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 12px;

        }


        .reviewer-name {

            color: #F4F7FF;

            font-size: 10px;

            font-weight: 800;

        }


        .review-date {

            margin-top: 4px;

            color: #68758D;

            font-size: 8px;

        }


        .review-stars {

            color: #FFD166;

            font-size: 13px;

            letter-spacing: 1px;

        }


        .review-content {

            margin: 0;

            color: #AAB5CA;

            font-size: 10px;

            line-height: 1.7;

            word-break: break-word;

        }


        .review-empty {

            padding: 30px 20px;

            text-align: center;

            color: #68758D;

            background: #081225;

            border: 1px solid #263452;

            font-size: 9px;

        }


        @media (
            max-width: 600px
        ) {

            .review-summary {

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }


            .review-card-header {

                flex-direction:
                    column;

                align-items:
                    flex-start;

            }

        }

    </style>

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

<header class="site-header">


    <div class="brand">

        <a href="index.php">
            FROSTCORE
        </a>

    </div>



    <!-- ==================================================
         NAVIGATION
    ================================================== -->

    <nav class="main-nav">


        <a href="index.php">
            HOME
        </a>


        <a href="products.php">
            PRODUCTS
        </a>


        <?php if (
            $isLoggedIn &&
            !$isAdmin
        ): ?>

            <a href="my-orders.php">
                MY ORDERS
            </a>

        <?php endif; ?>


        <!-- BOTH CUSTOMER AND ADMIN -->

        <a href="about.php">
            ABOUT US
        </a>


        <!-- BOTH CUSTOMER AND ADMIN -->

        <a href="contact.php">
            CONTACT
        </a>


    </nav>



    <!-- ==================================================
         HEADER ACTIONS
    ================================================== -->

    <div class="header-actions">


        <!-- ADMIN ONLY -->

        <?php if ($isAdmin): ?>

            <a
                href="admin/dashboard.php"
                class="login-link"
                style="
                    color:#4DBCF4;
                    border:1px solid #4DBCF4;
                    padding:7px 10px;
                    font-size:8px;
                    font-weight:800;
                "
            >
                ADMIN
            </a>

        <?php endif; ?>


        <!-- LOGIN / LOGOUT -->

        <?php if ($isLoggedIn): ?>

            <a
                href="logout.php"
                class="login-link"
            >
                LOGOUT
            </a>

        <?php else: ?>

            <a
                href="login.php?redirect=<?= urlencode(
                    "product-details.php?id=" . $product_id
                ) ?>"
                class="login-link"
            >
                LOGIN
            </a>

        <?php endif; ?>


        <!-- CART -->

        <a
            href="cart.php"
            class="cart-link"
        >

            CART

            <span class="cart-count">
                <?= $cart_count ?>
            </span>

        </a>


    </div>

</header>



<!-- ==================================================
     MAIN
================================================== -->

<main class="product-details-page">


    <div class="product-back">

        <a href="products.php">
            ← BACK TO PRODUCTS
        </a>

    </div>



    <!-- ==================================================
         PRODUCT
    ================================================== -->

    <section class="product-details">


        <div class="product-details-image">

            <img
                src="<?= e($product_image) ?>"
                alt="<?= e($product_name) ?>"
            >

        </div>



        <div class="product-details-info">


            <p class="product-category">

                <?= e($product_category) ?>

            </p>


            <h1>

                <?= e($product_name) ?>

            </h1>


            <div class="product-rating">


                <span class="stars">


                    <?php

                    $rounded_rating =
                        (int)round(
                            $product_rating
                        );


                    for (
                        $i = 1;
                        $i <= 5;
                        $i++
                    ) {

                        echo
                            $i <= $rounded_rating
                                ? "★"
                                : "☆";

                    }

                    ?>


                </span>


                <span>

                    <?= number_format(
                        $product_rating,
                        1
                    ) ?>

                    / 5

                </span>


            </div>


            <div
                class="product-details-price"
            >

                <?= money(
                    $product_price
                ) ?>

            </div>


            <!-- STOCK -->

            <div class="product-stock">


                <?php if (
                    $product_stock > 0
                ): ?>

                    <span
                        class="stock-available"
                    >
                        IN STOCK
                    </span>


                    <span>

                        <?= $product_stock ?>

                        available

                    </span>


                <?php else: ?>

                    <span
                        class="stock-out"
                    >

                        OUT OF STOCK

                    </span>

                <?php endif; ?>


            </div>


            <!-- DESCRIPTION -->

            <div
                class="product-description"
            >

                <h3>
                    PRODUCT DESCRIPTION
                </h3>


                <p>

                    <?= nl2br(
                        e(
                            $product_description
                        )
                    ) ?>

                </p>

            </div>


            <!-- ADD TO CART -->

            <?php if (
                $product_stock > 0
            ): ?>


                <form
                    action="cart.php"
                    method="POST"
                    class="add-to-cart-form"
                >


                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= $product_id ?>"
                    >


                    <div
                        class="quantity-box"
                    >

                        <label
                            for="quantity"
                        >
                            QUANTITY
                        </label>


                        <input
                            type="number"
                            id="quantity"
                            name="quantity"
                            value="1"
                            min="1"
                            max="<?= $product_stock ?>"
                        >

                    </div>


                    <button
                        type="submit"
                        name="add_to_cart"
                        class="add-cart-button"
                    >
                        ADD TO CART
                    </button>


                </form>


            <?php else: ?>


                <button
                    type="button"
                    class="
                        add-cart-button
                        disabled
                    "
                    disabled
                >

                    OUT OF STOCK

                </button>


            <?php endif; ?>


        </div>


    </section>



    <!-- ==================================================
         SPECIFICATIONS
    ================================================== -->

    <section
        class="product-specifications"
    >


        <h2>
            PRODUCT SPECIFICATIONS
        </h2>


        <div class="spec-grid">


            <div class="spec-item">

                <span>
                    CATEGORY
                </span>


                <strong>

                    <?= e(
                        $product_category
                    ) ?>

                </strong>

            </div>


            <div class="spec-item">

                <span>
                    PRODUCT
                </span>


                <strong>

                    <?= e(
                        $product_name
                    ) ?>

                </strong>

            </div>


            <div class="spec-item">

                <span>
                    RATING
                </span>


                <strong>

                    <?= number_format(
                        $product_rating,
                        1
                    ) ?>

                    / 5

                </strong>

            </div>


            <div class="spec-item">

                <span>
                    AVAILABILITY
                </span>


                <strong>


                    <?php if (
                        $product_stock > 0
                    ): ?>

                        <?= $product_stock ?>

                        UNITS

                    <?php else: ?>

                        OUT OF STOCK

                    <?php endif; ?>


                </strong>

            </div>


        </div>

    </section>



    <!-- ==================================================
         CUSTOMER REVIEWS
    ================================================== -->

    <section
        class="product-reviews"
    >


        <h2>
            CUSTOMER REVIEWS
        </h2>



        <!-- REVIEW SUMMARY -->

        <div
            class="review-summary"
        >


            <div
                class="review-summary-rating"
            >


                <?php if (
                    $reviewCount > 0
                ): ?>


                    <?php

                    $reviewRounded =
                        (int)round(
                            $reviewAverage
                        );


                    for (
                        $i = 1;
                        $i <= 5;
                        $i++
                    ) {

                        echo
                            $i <= $reviewRounded
                                ? "★"
                                : "☆";

                    }

                    ?>


                    <?= number_format(
                        $reviewAverage,
                        1
                    ) ?>

                    / 5


                <?php else: ?>


                    NO REVIEWS


                <?php endif; ?>


            </div>


            <div
                class="review-summary-text"
            >

                <?= $reviewCount ?>

                <?= $reviewCount === 1
                    ? "customer review"
                    : "customer reviews"
                ?>

            </div>


        </div>



        <!-- SUCCESS -->

        <?php if (
            $reviewSuccess !== ""
        ): ?>


            <div
                class="
                    review-message
                    review-success
                "
            >

                <?= e(
                    $reviewSuccess
                ) ?>

            </div>


        <?php endif; ?>



        <!-- ERROR -->

        <?php if (
            $reviewError !== ""
        ): ?>


            <div
                class="
                    review-message
                    review-error
                "
            >

                <?= e(
                    $reviewError
                ) ?>

            </div>


        <?php endif; ?>



        <!-- ==================================================
             REVIEW FORM
        ================================================== -->

        <?php if (
            $canReview
        ): ?>


            <div
                class="review-form"
            >


                <h3>
                    WRITE A REVIEW
                </h3>


                <form
                    method="POST"
                    action="product-details.php?id=<?= $product_id ?>"
                >


                    <input
                        type="hidden"
                        name="action"
                        value="submit_review"
                    >


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(
                            $_SESSION[
                                "review_csrf_token"
                            ]
                        ) ?>"
                    >


                    <label
                        class="rating-label"
                    >

                        YOUR RATING

                    </label>


                    <div
                        class="star-selector"
                    >


                        <input
                            type="radio"
                            id="star5"
                            name="rating"
                            value="5"
                            required
                        >

                        <label
                            for="star5"
                            title="5 stars"
                        >
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star4"
                            name="rating"
                            value="4"
                        >

                        <label
                            for="star4"
                            title="4 stars"
                        >
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star3"
                            name="rating"
                            value="3"
                        >

                        <label
                            for="star3"
                            title="3 stars"
                        >
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star2"
                            name="rating"
                            value="2"
                        >

                        <label
                            for="star2"
                            title="2 stars"
                        >
                            ★
                        </label>


                        <input
                            type="radio"
                            id="star1"
                            name="rating"
                            value="1"
                        >

                        <label
                            for="star1"
                            title="1 star"
                        >
                            ★
                        </label>


                    </div>


                    <label
                        class="review-textarea-label"
                        for="review_text"
                    >

                        YOUR REVIEW

                    </label>


                    <textarea
                        id="review_text"
                        name="review_text"
                        class="review-textarea"
                        maxlength="1000"
                        placeholder="Tell us what you think about this product..."
                        required
                    ></textarea>


                    <button
                        type="submit"
                        class="review-submit"
                    >

                        SUBMIT REVIEW

                    </button>


                </form>


            </div>


        <?php elseif (
            !$isLoggedIn
        ): ?>


            <div
                class="review-notice"
            >

                Please

                <a
                    href="login.php?redirect=<?= urlencode(
                        "product-details.php?id=" . $product_id
                    ) ?>"
                >
                    LOGIN
                </a>

                to leave a review.

            </div>


        <?php elseif (
            $isAdmin
        ): ?>


            <div
                class="review-notice"
            >

                You are viewing this product
                as an administrator.

                Customer review submission
                is available only to customer
                accounts.

            </div>


        <?php elseif (
            $hasReviewed
        ): ?>


            <div
                class="review-notice"
            >

                ✓ You have already reviewed
                this product.

            </div>


        <?php elseif (
            !$completedPurchase
        ): ?>


            <div
                class="review-notice"
            >

                You can leave a review after
                you have purchased and completed
                an order containing this product.

            </div>


        <?php endif; ?>



        <!-- ==================================================
             EXISTING REVIEWS
        ================================================== -->

        <?php if (
            !empty($reviews)
        ): ?>


            <div class="review-list">


                <?php foreach (
                    $reviews
                    as $review
                ): ?>


                    <?php

                    $reviewRating =
                        max(
                            1,
                            min(
                                5,
                                (int)
                                $review["rating"]
                            )
                        );

                    ?>


                    <article
                        class="review-card"
                    >


                        <div
                            class="
                                review-card-header
                            "
                        >


                            <div>


                                <div
                                    class="
                                        reviewer-name
                                    "
                                >

                                    <?= e(
                                        $review[
                                            "full_name"
                                        ] ??
                                        "Customer"
                                    ) ?>

                                </div>


                                <div
                                    class="
                                        review-date
                                    "
                                >

                                    <?= e(
                                        date(
                                            "F d, Y · h:i A",
                                            strtotime(
                                                $review[
                                                    "created_at"
                                                ]
                                            )
                                        )
                                    ) ?>

                                </div>


                            </div>



                            <div
                                class="
                                    review-stars
                                "
                            >


                                <?php for (
                                    $i = 1;
                                    $i <= 5;
                                    $i++
                                ): ?>

                                    <?= $i <= $reviewRating
                                        ? "★"
                                        : "☆" ?>

                                <?php endfor; ?>


                            </div>


                        </div>



                        <p
                            class="review-content"
                        >

                            <?= nl2br(
                                e(
                                    $review[
                                        "review_text"
                                    ]
                                )
                            ) ?>

                        </p>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <div
                class="review-empty"
            >

                No customer reviews yet.

            </div>


        <?php endif; ?>


    </section>


</main>



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="site-footer">


    <div class="footer-brand">

        <h2>
            FROSTCORE
        </h2>


        <p>
            Advanced cooling solutions
            for gamers.
        </p>

    </div>



    <div class="footer-links">


        <a href="index.php">
            HOME
        </a>


        <a href="products.php">
            PRODUCTS
        </a>


        <?php if (
            $isLoggedIn &&
            !$isAdmin
        ): ?>

            <a href="my-orders.php">
                MY ORDERS
            </a>

        <?php endif; ?>


        <a href="about.php">
            ABOUT US
        </a>


        <a href="contact.php">
            CONTACT US
        </a>


        <?php if ($isAdmin): ?>

            <a href="admin/dashboard.php">
                ADMIN
            </a>

        <?php endif; ?>


    </div>


</footer>



<?php require_once "logout-popup.php"; ?>


<script src="script.js"></script>


</body>

</html>