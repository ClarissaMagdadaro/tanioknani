<?php
include 'components/connect.php';

// Check if user is logged in
if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    header('location:web_login.php');
    exit();
}

if (isset($_POST['place_order'])) {
    // Sanitize and get POST values
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $number = filter_var($_POST['number'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $address = filter_var($_POST['flat'], FILTER_SANITIZE_STRING) . ', ' . 
               filter_var($_POST['street'], FILTER_SANITIZE_STRING) . ', ' . 
               filter_var($_POST['city'], FILTER_SANITIZE_STRING) . ', ' . 
               filter_var($_POST['country'], FILTER_SANITIZE_STRING) . ', ' . 
               filter_var($_POST['pin'], FILTER_SANITIZE_STRING);
    $address_type = filter_var($_POST['address_type'], FILTER_SANITIZE_STRING);
    $method = 'Cash on Delivery'; // Fixed to Cash on Delivery

    // Check if 'Save my details for next time' is checked
    if (isset($_POST['save_details']) && $_POST['save_details'] == 'on') {
        $update_user = $conn->prepare("UPDATE `users` SET name = ?, number = ?, email = ?, address = ?, address_type = ? WHERE id = ?");
        $update_user->execute([$name, $number, $email, $address, $address_type, $user_id]);
    }

    try {
        $conn->beginTransaction();

        // Handle single product checkout
        if (isset($_GET['get_id'])) {
            $get_product = $conn->prepare("SELECT * FROM `products` WHERE id = ? LIMIT 1");
            $get_product->execute([$_GET['get_id']]);

            // Debug: Check if product exists
            var_dump($get_product->rowCount()); // should return 1 if product is found
            if ($get_product->rowCount() > 0) {
                $fetch_p = $get_product->fetch(PDO::FETCH_ASSOC);
                $seller_id = $fetch_p['seller_id'];

                $insert_order = $conn->prepare("
                    INSERT INTO `orders` 
                    (user_id, seller_id, name, number, email, address, address_type, method, product_id, price, qty) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert_order->execute([ 
                    $user_id, $seller_id, $name, $number, $email, $address, $address_type, $method,
                    $fetch_p['id'], $fetch_p['price'], 1
                ]);
            } else {
                throw new Exception('Product not found.');
            }
        } else {
            // Handle cart checkout
            $verify_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $verify_cart->execute([$user_id]);

            // Debug: Check if cart is empty
            var_dump($verify_cart->rowCount()); // should return > 0 if there are items in the cart
            if ($verify_cart->rowCount() > 0) {
                while ($f_cart = $verify_cart->fetch(PDO::FETCH_ASSOC)) {
                    $s_products = $conn->prepare("SELECT * FROM `products` WHERE id = ? LIMIT 1");
                    $s_products->execute([$f_cart['product_id']]);

                    if ($s_products->rowCount() > 0) {
                        $f_product = $s_products->fetch(PDO::FETCH_ASSOC);
                        $seller_id = $f_product['seller_id'];

                        $insert_order = $conn->prepare("
                            INSERT INTO `orders` 
                            (user_id, seller_id, name, number, email, address, address_type, method, product_id, price, qty) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $insert_order->execute([ 
                            $user_id, $seller_id, $name, $number, $email, $address, $address_type, $method,
                            $f_product['id'], $f_cart['price'], $f_cart['qty']
                        ]);
                    } else {
                        throw new Exception('Product in cart not found.');
                    }
                }

                // Clear cart after successful order placement
                $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
                $delete_cart->execute([$user_id]);
            } else {
                throw new Exception('Cart is empty.');
            }
        }

        $conn->commit();
        header('location:order.php');
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $warning_msg[] = 'Error placing order: ' . $e->getMessage();
    }
}

// Fetch user details if the save option is checked for autofill
$user_details = [];
if (isset($user_id)) {
    $get_user_details = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
    $get_user_details->execute([$user_id]);
    if ($get_user_details->rowCount() > 0) {
        $user_details = $get_user_details->fetch(PDO::FETCH_ASSOC);
    }
}

// Split the address into parts for autofill
$address_parts = isset($user_details['address']) ? explode(',', $user_details['address']) : [];
$flat = isset($address_parts[0]) ? $address_parts[0] : '';
$street = isset($address_parts[1]) ? $address_parts[1] : '';
$city = isset($address_parts[2]) ? $address_parts[2] : '';
$country = isset($address_parts[3]) ? $address_parts[3] : '';
$pin = isset($address_parts[4]) ? $address_parts[4] : '';
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Figuras D' Arte - Checkout Page</title>
        <link rel="stylesheet" type="text/css" href="css/user_style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
        <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel='stylesheet'>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
    </head>
    <body>
        <?php include 'components/user_header.php'; ?>
        <div class="checkout">
            <div class="heading">
                <h1>Checkout Summary</h1>
                <img src="image/separator-img.png">
            </div>
            <div class="row">
                <form action="order.php" method="post" class="register">
                    <h3>Billing Details</h3>
                    <div class="flex">
                        <div class="box">
                            <div class="input-field">
                                <p>Your Name <span>*</span></p>
                                <input type="text" name="name" required maxlength="50" placeholder="Enter your name..." class="input" value="<?= isset($user_details['name']) ? $user_details['name'] : ''; ?>">
                            </div>
                            <div class="input-field">
                                <p>Contact Number <span>*</span></p>
                                <input type="tel" name="number" required placeholder="Enter your contact number..." class="input" value="<?= isset($user_details['number']) ? $user_details['number'] : ''; ?>" maxlength="12" inputmode="numeric">
                            </div>
                            <div class="input-field">
                                <p>Your Email <span>*</span></p>
                                <input type="email" name="email" required maxlength="50" placeholder="Enter your email..." class="input" value="<?= isset($user_details['email']) ? $user_details['email'] : ''; ?>">
                            </div>
                            <div class="input-field">
                                <p>Payment Method <span>*</span></p>
                                <select name="method" class="input" disabled>
                                    <option value="Cash on Delivery" selected>Cash on Delivery</option>
                                </select>
                            </div>
                            <div class="input-field">
                                <p>Address Type <span>*</span></p>
                                <select name="address_type" class="input">
                                    <option value="Home" <?= isset($user_details['address_type']) && $user_details['address_type'] == 'Home' ? 'selected' : ''; ?>>Home</option>
                                    <option value="Office" <?= isset($user_details['address_type']) && $user_details['address_type'] == 'Office' ? 'selected' : ''; ?>>Office</option>
                                </select>
                            </div>
                        </div>
                        <div class="box">
                            <div class="input-field">
                                <p>Address Line 01<span>*</span></p>
                                <input type="text" name="flat" required maxlength="50" placeholder="e.g. Flat, House no." class="input" value="<?= $flat; ?>">
                            </div>
                            <div class="input-field">
                                <p>Address Line 02<span>*</span></p>
                                <input type="text" name="street" required maxlength="50" placeholder="e.g. Street name" class="input" value="<?= $street; ?>">
                            </div>
                            <div class="input-field">
                                <p>City <span>*</span></p>
                                <input type="text" name="city" required maxlength="50" placeholder="e.g. Metro Manila" class="input" value="<?= $city; ?>">
                            </div>
                            <div class="input-field">
                                <p>Country <span>*</span></p>
                                <input type="text" name="country" required maxlength="50" placeholder="e.g. Philippines" class="input" value="<?= $country; ?>">
                            </div>
                            <div class="input-field">
                                <p>Postal Code <span>*</span></p>
                                <input type="text" name="pin" required maxlength="10" placeholder="e.g. 1234" class="input" value="<?= $pin; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="checkbox">
                        <input type="checkbox" name="save_details" <?= isset($user_details['name']) ? 'checked' : ''; ?>>
                        <label for="save_details">Save my details for next time</label>
                    </div>
                    <div class="btn-container">
                        <input type="submit" name="place_order" value="Place Order" class="btn">
                    </div>
                </form>
            </div>
        </div>
        <?php include 'components/footer.php'; ?>
    </body>
</html>
