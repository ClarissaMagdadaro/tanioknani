<?php
// Start the session
session_start();

// Database connection to 'arte_db'
try {
    // Replace 'root' and '' with your actual database credentials
    $conn = new PDO('mysql:host=localhost;dbname=arte_db', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // To throw errors for debugging
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if form is submitted
if (isset($_POST['submit'])) {
    // Retrieve and sanitize input data
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = trim($_POST['pass']);

    // Prepare SQL query to check if admin exists
    $select_admin = $conn->prepare("SELECT * FROM `admins` WHERE email = ?");
    $select_admin->execute([$email]);
    $row = $select_admin->fetch(PDO::FETCH_ASSOC);

    // Check if admin exists and verify password
    if ($row) {
        if (password_verify($pass, $row['password'])) {
            // Password is correct, start session
            $_SESSION['admin_id'] = $row['id'];

            // Redirect to the admin dashboard
            header('Location: admin_panel/admin_dashboard.php');
            exit();
        } else {
            // Password is incorrect
            $warning_msg[] = 'Incorrect Password';
        }
    } else {
        // Email is not found
        $warning_msg[] = 'Email not found';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Figuras D Arte - Admin Login</title>
    <link rel="stylesheet" type="text/css" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body>
    <!-- Login Form -->
    <div class="form-container">
        <form action="" method="post" enctype="multipart/form-data" class="login">
            <h3>Login Now</h3>

            <div class="input-field">
                <p>Your Email <span>*</span></p>
                <input type="email" name="email" placeholder="Email..." maxlength="50" required class="box">
            </div>

            <div class="input-field">
                <p>Your Password <span>*</span></p>
                <input type="password" name="pass" placeholder="Password..." maxlength="50" required class="box">
            </div>
            <input type="submit" name="submit" value="Login" class="btn">
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="../js/script.js"></script>

    <?php
    // Show warning message if login failed
    if (isset($warning_msg)) {
        foreach ($warning_msg as $msg) {
            echo "<script>swal('Error', '$msg', 'error');</script>";
        }
    }
    ?>
</body>
</html>
