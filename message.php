<?php
include 'components/connect.php';

// Ensure the user is logged in
if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
    header('location:login.php');
    exit;
}

// Add New Message to Seller
if (isset($_POST['send_msg'])) {
    $seller_id = $_POST['seller_id'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // Sanitize input
    $seller_id = filter_var($seller_id, FILTER_SANITIZE_STRING);
    $subject = filter_var($subject, FILTER_SANITIZE_STRING);
    $message = filter_var($message, FILTER_SANITIZE_STRING);
    
    // Insert the new message into the database
    try {
        $insert_message = $conn->prepare("INSERT INTO `message` (user_id, seller_id, subject, message) VALUES (?, ?, ?, ?)");
        $insert_message->execute([$user_id, $seller_id, $subject, $message]);

        $success_msg[] = 'Message sent to seller successfully!';
    } catch (PDOException $e) {
        $error_msg[] = 'Error: ' . $e->getMessage();
    }
}

// Delete Message
if (isset($_POST['delete_msg'])) {
    $delete_id = $_POST['delete_id'];
    $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);

    // Ensure the message belongs to the logged-in user
    $verify_delete = $conn->prepare("SELECT * FROM `message` WHERE id =? AND user_id = ?");
    $verify_delete->execute([$delete_id, $user_id]);

    if ($verify_delete->rowCount() > 0) {
        $delete_msg = $conn->prepare("DELETE FROM `message` WHERE id =? AND user_id = ?");
        $delete_msg->execute([$delete_id, $user_id]);

        $success_msg[] = 'Message deleted successfully';
    } else {
        $warning_msg[] = 'Message not found or already deleted';
    }
}

// Mark as Important
if (isset($_POST['mark_important'])) {
    $message_id = $_POST['message_id'];
    $message_id = filter_var($message_id, FILTER_SANITIZE_STRING);

    // Ensure the message belongs to the logged-in user
    $mark_important = $conn->prepare("UPDATE `message` SET is_important = 1 WHERE id = ? AND user_id = ?");
    $mark_important->execute([$message_id, $user_id]);

    $success_msg[] = 'Message marked as important';
}

// Reply functionality
if (isset($_POST['reply_msg'])) {
    $message_id = $_POST['message_id'];
    $message_id = filter_var($message_id, FILTER_SANITIZE_STRING);
    $reply = $_POST['reply_message'];
    $reply = filter_var($reply, FILTER_SANITIZE_STRING);

    // Insert reply into the `replies` table
    

    $success_msg[] = 'Reply sent successfully';
}

// Fetch available sellers (artists) to message
$select_sellers = $conn->prepare("SELECT * FROM `sellers`");
$select_sellers->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Dashboard - Private Messaging</title>
    <link rel="stylesheet" type="text/css" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
    <div class="main-container">
        <?php include 'components/user_header.php'; ?>
        
        <!-- New Message Form to Send Message to Seller -->
        <section class="send-message-container">
            <div class="heading">
                <h1>Send Message to Seller</h1>
                <img src="../image/separator-img.png" alt="✦ . ⁺ . ✦ . ⁺ . ✦">
            </div>
            <form action="" method="post">
                <label for="seller_id">Select Seller:</label>
                <select name="seller_id" required>
                    <option value="">Select Seller</option>
                    <?php while ($fetch_seller = $select_sellers->fetch(PDO::FETCH_ASSOC)) { ?>
                        <option value="<?= $fetch_seller['id']; ?>"><?= $fetch_seller['name']; ?></option>
                    <?php } ?>
                </select>

                <label for="subject">Subject:</label>
                <input type="text" name="subject" placeholder="Subject" required>

                <label for="message">Message:</label>
                <textarea name="message" placeholder="Write your message here..." required></textarea>

                <input type="submit" name="send_msg" value="Send Message" class="btn">
            </form>
        </section>

        <!-- Displaying User's Messages -->
        <section class="message-container">
            <div class="heading">
                <h1>Your Messages</h1>
                <img src="../image/separator-img.png" alt="✦ . ⁺ . ✦ . ⁺ . ✦">
            </div>
            <div class="box-container">
                <?php
                    // Fetch messages sent by the user
                    $select_message = $conn->prepare("SELECT * FROM `message` WHERE user_id = ?");
                    $select_message->execute([$user_id]);

                    if ($select_message->rowCount() > 0) {
                        while ($fetch_message = $select_message->fetch(PDO::FETCH_ASSOC)) {
                ?>
                <div class="box">
                    <h3 class="name"><?= $fetch_message['name']; ?></h3>
                    <h4><?= $fetch_message['subject']; ?></h4>
                    <p><?= $fetch_message['message']; ?></p>

                    <!-- Displaying the Replies -->
                    <div class="replies-container">
                        <?php
                            // Fetch replies for the message
                            $select_replies = $conn->prepare("SELECT * FROM `replies` WHERE message_id = ? ORDER BY date ASC");
                            $select_replies->execute([$fetch_message['id']]);
                            if ($select_replies->rowCount() > 0) {
                                while ($fetch_reply = $select_replies->fetch(PDO::FETCH_ASSOC)) {
                                    echo "<div class='reply'>";
                                    echo "<p><strong>Reply by Seller:</strong> " . $fetch_reply['reply'] . "</p>";
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

                    <!-- Mark as Important -->
                    <?php if ($fetch_message['is_important'] == 0): ?>
                    <form action="" method="post">
                        <input type="hidden" name="message_id" value="<?= $fetch_message['id']; ?>">
                        <input type="submit" name="mark_important" value="Mark as Important" class="btn">
                    </form>
                    <?php endif; ?>

                    <!-- Delete Message -->
                    <form action="" method="post">
                        <input type="hidden" name="delete_id" value="<?= $fetch_message['id']; ?>">
                                            <!-- Delete Message -->
                    <form action="" method="post">
                        <input type="hidden" name="delete_id" value="<?= $fetch_message['id']; ?>">
                        <input type="submit" name="delete_msg" value="Delete Message" class="btn">
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
