<?php
include 'components/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

if (isset($_GET['pid'])) {
    $pid = filter_var($_GET['pid'], FILTER_SANITIZE_NUMBER_INT);

    // Fetch product details
    $select_product = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
    $select_product->execute([$pid]);
    $product = $select_product->fetch(PDO::FETCH_ASSOC);

    // Check if the user has purchased this product
    $check_purchase = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ? AND product_id = ?");
    $check_purchase->execute([$user_id, $pid]);
    $purchased = $check_purchase->rowCount() > 0;

    // Fetch product reviews
    $select_reviews = $conn->prepare("SELECT r.*, u.name AS user_name FROM `reviews` r JOIN `users` u ON r.user_id = u.id WHERE product_id = ? ORDER BY r.created_at DESC");
    $select_reviews->execute([$pid]);

    // Handle review submission
    if (isset($_POST['submit_review']) && $purchased) {
        $rating = filter_var($_POST['rating'], FILTER_SANITIZE_NUMBER_INT);
        $review = filter_var($_POST['review'], FILTER_SANITIZE_STRING);

        // Check if user already reviewed the product
        $check_review = $conn->prepare("SELECT * FROM `reviews` WHERE user_id = ? AND product_id = ?");
        $check_review->execute([$user_id, $pid]);

        if ($check_review->rowCount() > 0) {
            echo "<script>alert('You have already reviewed this product.');</script>";
        } else {
            $insert_review = $conn->prepare("INSERT INTO `reviews` (user_id, product_id, rating, review) VALUES (?, ?, ?, ?)");
            $insert_review->execute([$user_id, $pid, $rating, $review]);
            echo "<script>alert('Review submitted successfully!'); location.reload();</script>";
        }
    }
} else {
    echo "<script>alert('Invalid product ID.'); window.location.href='shop.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Detail</title>
    <link rel="stylesheet" type="text/css" href="css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body>
    <?php include 'components/user_header.php'; ?>

    <section class="view_page">
        <div class="product-detail">
            <h1><?= $product['name']; ?></h1>
            <img src="uploaded_files/<?= $product['image']; ?>" alt="<?= $product['name']; ?>">
            <p>Price: ₱<?= number_format($product['price'], 2); ?></p>
            <p><?= $product['product_detail']; ?></p>
        </div>

        <div class="review-section">
            <h2>Leave a Review</h2>
            <?php if ($user_id && $purchased): ?>
                <form action="" method="post">
                    <label>Rating:</label>
                    <select name="rating" required>
                        <option value="1">1 - Poor</option>
                        <option value="2">2 - Fair</option>
                        <option value="3">3 - Good</option>
                        <option value="4">4 - Very Good</option>
                        <option value="5">5 - Excellent</option>
                    </select>
                    <label>Review:</label>
                    <textarea name="review" rows="4" required></textarea>
                    <button type="submit" name="submit_review" class="btn">Submit Review</button>
                </form>
            <?php elseif (!$purchased): ?>
                <p>You can only review products you have purchased.</p>
            <?php else: ?>
                <p>Please <a href="login.php">log in</a> to leave a review.</p>
            <?php endif; ?>
        </div>

        <div class="reviews">
            <h2>Reviews</h2>
            <?php
            if ($select_reviews->rowCount() > 0) {
                while ($review = $select_reviews->fetch(PDO::FETCH_ASSOC)) {
                    echo "<div class='review'>";
                    echo "<p><strong>{$review['user_name']}</strong> rated {$review['rating']}/5</p>";
                    echo "<p>{$review['review']}</p>";
                    echo "<p><small>{$review['created_at']}</small></p>";
                    echo "</div>";
                }
            } else {
                echo '<p>No reviews yet.</p>';
            }
            ?>
        </div>
    </section>
</body>
</html>
<style>
.review-section, .reviews {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.review-section h2, .reviews h2 {
    margin-bottom: 10px;
}

.review {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.review p {
    margin: 5px 0;
}
</style>
