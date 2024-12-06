<?php
include 'components/connect.php';

if (isset($_GET['seller_id'])) {
    $seller_id = filter_var($_GET['seller_id'], FILTER_SANITIZE_NUMBER_INT);

    // Fetch the seller's details
    $select_seller = $conn->prepare("SELECT * FROM `sellers` WHERE id = ?");
    $select_seller->execute([$seller_id]);
    if ($select_seller->rowCount() > 0) {
        $seller = $select_seller->fetch(PDO::FETCH_ASSOC);
    } else {
        echo "<script>alert('Seller not found.'); window.location.href='artists.php';</script>";
        exit();
    }

    // Fetch the seller's products
    $select_products = $conn->prepare("SELECT * FROM `products` WHERE seller_id = ? AND status = 'active'");
    $select_products->execute([$seller_id]);

    // Fetch average rating
    $select_avg_rating = $conn->prepare("SELECT AVG(rating) AS avg_rating FROM `reviews` WHERE seller_id = ?");
    $select_avg_rating->execute([$seller_id]);
    $avg_rating = $select_avg_rating->fetch(PDO::FETCH_ASSOC)['avg_rating'];

} else {
    echo "<script>alert('Invalid seller ID.'); window.location.href='artists.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Figuras D Arte - <?= $seller['name']; ?>'s Profile</title>
    <link rel="stylesheet" type="text/css" href="css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body>
<div class="main-container">
    <?php include 'components/user_header.php'; ?>

    <section class="profile">
        <div class="heading">
            <h1><?= $seller['name']; ?>'s Profile</h1>
        </div>

        <div class="profile-info">
            <img src="uploaded_files/<?= $seller['image']; ?>" alt="Seller Image" class="profile-image">
            <h3>Email: <?= $seller['email']; ?></h3>
            <p>Average Rating: <?= $avg_rating ? number_format($avg_rating, 1) : 'No ratings yet'; ?></p>
        </div>

        <div class="products">
            <h2>Products</h2>
            <div class="box-container">
                <?php
                if ($select_products->rowCount() > 0) {
                    while ($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                        <div class="box">
                            <img src="uploaded_files/<?= $fetch_product['image']; ?>" alt="<?= $fetch_product['name']; ?>" class="image">
                            <div class="content">
                                <h3 class="name"><?= $fetch_product['name']; ?></h3>
                                <a href="view_page.php?pid=<?= $fetch_product['id']; ?>" class="btn">View Product</a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p>No products available from this seller.</p>';
                }
                ?>
            </div>
        </div>
    </section>
</div>
</body>
</html>
