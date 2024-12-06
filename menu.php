<?php
include 'components/connect.php';

// Assume user is logged in
if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = null; // Set to null if user is not logged in
}

include 'components/add_wishlist.php';
include 'components/add_cart.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Figuras D' Arte - Shop Page</title>
    <link rel="stylesheet" type="text/css" href="css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'components/user_header.php'; ?>
<div class="products">
    <div class="heading">
        <h1>Our Merchandise</h1>
        <img src="image/separator-img.png">
    </div>
    <div class="box-container">
        <?php
        $select_products = $conn->prepare("SELECT * FROM `products` WHERE status = ?");
        $select_products->execute(['active']);

        if ($select_products->rowCount() > 0) {
            while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <form action="" method="post" class="box <?php if ($fetch_products['stock'] == 0) { echo "disabled"; } ?>">
            <img src="uploaded_files/<?= $fetch_products['image']; ?>" class="image">
            
            <?php if ($fetch_products['stock'] > 9) { ?>
                <span class="stock" style="color: green;">In Stock</span>
            <?php } elseif ($fetch_products['stock'] == 0) { ?>
                <span class="stock" style="color: red;">Out of Stock</span>
            <?php } else { ?>
                <span class="stock" style="color: red;">Hurry, only <?= $fetch_products['stock']; ?></span>
            <?php } ?>
            <div class="content">
                <div class="button">
                    <div><h3 class="name"><?= $fetch_products['name']; ?></h3></div>
                    <div>
                        <!-- Add to Cart and Wishlist buttons -->
                        <?php if ($fetch_products['bidding_enabled'] == 0) { ?>
                            <button type="submit" name="add_to_cart"><i class="bx bx-cart"></i></button>
                            <button type="submit" name="add_to_wishlist"><i class="bx bx-heart"></i></button>
                            <a href="view_page.php?pid=<?= $fetch_products['id'] ?>" class="bx bxs-show"></a>
                        <?php } ?>
                    </div>
                </div>

                <!-- Only display price if bidding is not enabled -->
                <?php if ($fetch_products['bidding_enabled'] == 0) { ?>
                    <p class="price">Price ₱<?= $fetch_products['price']; ?></p>
                <?php } ?>

                <input type="hidden" name="product_id" value="<?= $fetch_products['id'] ?>">

                <!-- Buy Now Button: Only show if bidding is not enabled -->
                <?php if ($fetch_products['bidding_enabled'] == 0) { ?>
                    <div class="flex-btn">
                        <a href="checkout.php?get_id=<?= $fetch_products['id'] ?>" class="btn">Buy Now</a>
                        <input type="number" name="qty" required min="1" value="1" max="<?= $fetch_products['stock']; ?>" maxlength="2" class="qty box">
                    </div>
                <?php } ?>

                <!-- Bidding Section: Only shown if bidding is enabled -->
                <?php if ($fetch_products['bidding_enabled'] == 1) { ?>
                <div class="bidding-section">
                    <?php
                    // Get the highest bid
                    $highest_bid_query = $conn->prepare("SELECT MAX(bid_amount) AS highest_bid FROM bids WHERE product_id = ?");
                    $highest_bid_query->execute([$fetch_products['id']]);
                    $highest_bid = $highest_bid_query->fetchColumn();
                    ?>
                    <p>Highest Bid: ₱<?= $highest_bid ?? $fetch_products['starting_price']; ?></p>
                    <form action="" method="POST">
                        <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">
                        <label for="bid_amount">Your Bid (₱):</label>
                        <input type="number" name="bid_amount" id="bid_amount" min="<?= ($highest_bid ?? $fetch_products['starting_price']) + 1; ?>" required>
                        <button type="submit" name="place_bid" class="btn">Place Bid</button>
                    </form>
                </div>
                <?php } ?>
            </div>
        </form>
        <?php
            }
        } else {
            echo '
                <div class="empty">
                    <p>No Products Added Yet!</p>
                </div>
            ';
        }
        ?>
    </div>
</div>
<?php include 'components/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src="js/user_script.js"></script>
<?php include 'components/alert.php'; ?>
</body>
</html>