<?php
include '../components/connect.php';

// Ensure the admin is logged in
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
} else {
    header('location:admin_login.php');
    exit;
}

// Add New Message
if (isset($_POST['send_msg'])) {
    $receiver_id = $_POST['receiver_id'];
    $receiver_type = $_POST['receiver_type'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    // Sanitize input
    $receiver_id = filter_var($receiver_id, FILTER_SANITIZE_STRING);
    $receiver_type = filter_var($receiver_type, FILTER_SANITIZE_STRING);
    $subject = filter_var($subject, FILTER_SANITIZE_STRING);
    $message = filter_var($message, FILTER_SANITIZE_STRING);

    try {
        $insert_message = $conn->prepare("INSERT INTO `message` (admin_id, receiver_id, receiver_type, subject, message) VALUES (?, ?, ?, ?, ?)");
        $insert_message->execute([$admin_id, $receiver_id, $receiver_type, $subject, $message]);

        $success_msg[] = 'Message sent successfully!';
    } catch (PDOException $e) {
        $error_msg[] = 'Error: ' . $e->getMessage();
    }
}

// Reply to a Message
if (isset($_POST['reply_msg'])) {
    $message_id = $_POST['message_id'];
    $reply = $_POST['reply_message'];

    // Sanitize input
    $message_id = filter_var($message_id, FILTER_SANITIZE_STRING);
    $reply = filter_var($reply, FILTER_SANITIZE_STRING);

    try {
        $insert_reply = $conn->prepare("INSERT INTO `replies` (message_id, reply, reply_by) VALUES (?, ?, 'admin')");
        $insert_reply->execute([$message_id, $reply]);

        $success_msg[] = 'Reply sent successfully!';
    } catch (PDOException $e) {
        $error_msg[] = 'Error: ' . $e->getMessage();
    }
}

// Fetch users and sellers for messaging
$select_users = $conn->prepare("SELECT * FROM `users`");
$select_users->execute();

$select_sellers = $conn->prepare("SELECT * FROM `sellers`");
$select_sellers->execute();

// Fetch messages sent or received by the admin
$select_messages = $conn->prepare("SELECT * FROM `message` WHERE admin_id = ?");
$select_messages->execute([$admin_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Messaging</title>
    <link rel="stylesheet" type="text/css" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
</head>
<body>
    <div class="main-container">
        <?php include 'components/admin_header.php'; ?>

        <!-- New Message Form -->
        <section class="send-message-container">
            <div class="heading">
                <h1>Send Message</h1>
                <img src="../image/separator-img.png" alt="✦ . ⁺ . ✦ . ⁺ . ✦">
            </div>
            <form action="" method="post">
                <label for="receiver_type">Select Receiver Type:</label>
                <select name="receiver_type" required>
                    <option value="">Select Type</option>
                    <option value="user">User</option>
                    <option value="seller">Seller</option>
                </select>

                <label for="receiver_id">Select Receiver:</label>
                <select name="receiver_id" required>
                    <option value="">Select Receiver</option>
                    <?php while ($fetch_user = $select_users->fetch(PDO::FETCH_ASSOC)) { ?>
                        <option value="<?= $fetch_user['id']; ?>">User: <?= $fetch_user['name']; ?></option>
                    <?php } ?>
                    <?php while ($fetch_seller = $select_sellers->fetch(PDO::FETCH_ASSOC)) { ?>
                        <option value="<?= $fetch_seller['id']; ?>">Seller: <?= $fetch_seller['name']; ?></option>
                    <?php } ?>
                </select>

                <label for="subject">Subject:</label>
                <input type="text" name="subject" placeholder="Subject" required>

                <label for="message">Message:</label>
                <textarea name="message" placeholder="Write your message here..." required></textarea>

                <input type="submit" name="send_msg" value="Send Message" class="btn">
            </form>
        </section>

        <!-- Display Messages -->
        <section class="message-container">
            <div class="heading">
                <h1>Admin Messages</h1>
                <img src="../image/separator-img.png" alt="✦ . ⁺ . ✦ . ⁺ . ✦">
            </div>
            <div class="box-container">
                <?php
                if ($select_messages->rowCount() > 0) {
                    while ($fetch_message = $select_messages->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                        <div class="box">
                            <h3>To: <?= ucfirst($fetch_message['receiver_type']); ?> (ID: <?= $fetch_message['receiver_id']; ?>)</h3>
                            <h4><?= $fetch_message['subject']; ?></h4>
                            <p><?= $fetch_message['message']; ?></p>

                            <!-- Display Replies -->
                            <div class="replies-container">
                                <?php
                                $select_replies = $conn->prepare("SELECT * FROM `replies` WHERE message_id = ? ORDER BY date ASC");
                                $select_replies->execute([$fetch_message['id']]);
                                if ($select_replies->rowCount() > 0) {
                                    while ($fetch_reply = $select_replies->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<div class='reply'>";
                                        echo "<p><strong>Reply:</strong> " . $fetch_reply['reply'] . "</p>";
                                        echo "<p><em>" . $fetch_reply['date'] . "</em></p>";
                                        echo "</div>";
                                    }
                                }
                                ?>
                            </div>

                            <!-- Reply Form -->
                            <form action="" method="post">
                                <input type="hidden" name="message_id" value="<?= $fetch_message['id']; ?>">
                                <textarea name="reply_message" placeholder="Write your reply here..." required></textarea>
                                <input type="submit" name="reply_msg" value="Send Reply" class="btn">
                            </form>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>No messages found!</p>";
                }
                ?>
            </div>
        </section>
    </div>
</body>
</html>
