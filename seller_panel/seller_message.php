<?php
    include '../components/connect.php';

    if (isset($_COOKIE['seller_id'])) {
        $seller_id = $_COOKIE['seller_id'];
    } else {
        $seller_id = '';
        header('location:login.php');
    }

    // Delete Message
    if (isset($_POST['delete_msg'])) {
        $delete_id = $_POST['delete_id'];
        $delete_id = filter_var($delete_id, FILTER_SANITIZE_STRING);

        $verify_delete = $conn->prepare("SELECT * FROM `message` WHERE id =?");
        $verify_delete->execute([$delete_id]);

        if ($verify_delete->rowCount() > 0) {
            $delete_msg = $conn->prepare("DELETE FROM `message` WHERE id =?");
            $delete_msg->execute([$delete_id]);

            $success_msg[] = 'Message deleted successfully';
        } else {
            $warning_msg[] = 'Message already deleted';
        }
    }

    // Mark as Important
    if (isset($_POST['mark_important'])) {
        $message_id = $_POST['message_id'];
        $message_id = filter_var($message_id, FILTER_SANITIZE_STRING);

        // Update the message's status to important
        $mark_important = $conn->prepare("UPDATE `message` SET is_important = 1 WHERE id = ?");
        $mark_important->execute([$message_id]);

        $success_msg[] = 'Message marked as important';
    }

    // Reply functionality
    if (isset($_POST['reply_msg'])) {
        $message_id = $_POST['message_id'];
        $message_id = filter_var($message_id, FILTER_SANITIZE_STRING);
        $reply = $_POST['reply_message'];
        $reply = filter_var($reply, FILTER_SANITIZE_STRING);

        // Insert reply into the `replies` table
        $insert_reply = $conn->prepare("INSERT INTO `replies` (message_id, reply, seller_id) VALUES (?, ?, ?)");
        $insert_reply->execute([$message_id, $reply, $seller_id]);

        $success_msg[] = 'Reply sent successfully';
    }
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Figuras D Arte - Admin Dashboard Page</title>
    <link rel="stylesheet" type="text/css" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
    <div class="main-container">
        <?php include '../components/seller_header.php'; ?>
        <section class="message-container">
            <div class="heading">
                <h1>Unread Messages</h1>
                <img src="../image/separator-img.png" alt="✦ . ⁺ . ✦ . ⁺ . ✦">
            </div>
            <div class="box-container">
                <?php
                    $select_message = $conn->prepare("SELECT * FROM `message`");
                    $select_message->execute();
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
                    <?php else: ?>
                    <p class="important">This message is marked as important</p>
                    <?php endif; ?>

                    <!-- Delete Message -->
                    <form action="" method="post">
                        <input type="hidden" name="delete_id" value="<?= $fetch_message['id']; ?>">
                        <input type="submit" name="delete_msg" value="Delete Message" class="btn" onclick="return confirm('Delete this message?');">
                    </form>
                </div>
                <?php
                        }
                    } else {
                        echo '
                            <div class="empty">
                                <p>No Unread Messages</p>
                            </div>
                        ';
                    }
                ?>
            </div>
        </section>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="../js/admin_script.js"></script>

    <?php include '../components/alert.php'; ?>
</body>
</html>
