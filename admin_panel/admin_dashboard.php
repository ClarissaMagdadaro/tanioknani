<?php
// Include the database connection file
include '../components/connect.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Figuras D Arte - Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
    <div class="main-container">
        <!-- Include the admin header -->
        <?php include '../components/admin_header.php'; ?>

        <section class="dashboard">
            <div class="heading">
                <h1>Dashboard</h1>
                <img src="../image/separator-img.png" alt="✦ . ⁺ . ✦ . ⁺ . ✦">
            </div>
            <div class="box-container">
                <div class="box">
                    <h3>Welcome!</h3>
                    <p><?= isset($fetch_profile['name']) ? htmlspecialchars($fetch_profile['name']) : 'Admin'; ?></p>
                    <a href="../admin_panel/update.php" class="btn">Update Profile</a>
                </div>

                <div class="box">
                    <?php
                    $select_message = $conn->prepare("SELECT * FROM `message`");
                    $select_message->execute();
                    $number_of_msg = $select_message->rowCount();
                    ?>
                    <h3><?= $number_of_msg; ?></h3>
                    <p>Unread Messages</p>
                    <a href="admin_message.php" class="btn">See Messages</a>
                </div>

                <div class="box">
                    <?php
                    $select_products = $conn->prepare("SELECT * FROM `products` WHERE admin_id = ?");
                    $select_products->execute([$admin_id]);
                    $number_of_products = $select_products->rowCount();
                    ?>
                    <h3><?= $number_of_products; ?></h3>
                    <p>Products Added</p>
                    <a href="add_products.php" class="btn">Add Product</a>
                </div>

                <!-- Manage Accounts -->
                <div class="box">
                    <?php
                    $select_accounts = $conn->prepare("SELECT * FROM `users`");
                    $select_accounts->execute();
                    $number_of_accounts = $select_accounts->rowCount();
                    ?>
                    <h3><?= $number_of_accounts; ?></h3>
                    <p>Manage Accounts</p>
                    <a href="accounts.php" class="btn">Manage Accounts</a>
                </div>

                <!-- Manage Posts -->
                <div class="box">
                    <?php
                    $select_posts = $conn->prepare("SELECT * FROM `posts`");
                    $select_posts->execute();
                    $number_of_posts = $select_posts->rowCount();
                    ?>
                    <h3><?= $number_of_posts; ?></h3>
                    <p>Manage Posts</p>
                    <a href="view_posts.php" class="btn">Manage Posts</a>
                </div>
            </div>
            
        </section>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="../js/admin_script.js"></script>

    <?php include '../components/alert.php'; ?>
</body>
</html>
