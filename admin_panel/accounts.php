<?php
session_start();
include '../components/connect.php';

// Fetch users and sellers from the database
$select_users = $conn->prepare("SELECT id, name AS username, profile_picture, status FROM users");
$select_users->execute();

$select_sellers = $conn->prepare("SELECT id, name AS username, profile_picture, status FROM sellers");
$select_sellers->execute();

// Function to handle approval, rejection, and deletion
function handleApprovalRejectionDeletion($type, $id, $action) {
    global $conn;
    $table = $type === 'user' ? 'users' : 'sellers';

    if ($action === 'approve') {
        $query = $conn->prepare("UPDATE $table SET status = 'approved' WHERE id = ?");
        $query->execute([$id]);
        return ucfirst($type) . ' approved successfully.';
    } elseif ($action === 'reject') {
        $query = $conn->prepare("UPDATE $table SET status = 'rejected' WHERE id = ?");
        $query->execute([$id]);
        return ucfirst($type) . ' rejected.';
    } elseif ($action === 'delete') {
        $query = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $query->execute([$id]);
        return ucfirst($type) . ' deleted successfully.';
    }
}

// Handle form submission for approval, rejection, and deletion
if (isset($_POST['action_type']) && isset($_POST['id'])) {
    $type = $_POST['type'];
    $id = $_POST['id'];
    $action = $_POST['action_type'];
    $msg = handleApprovalRejectionDeletion($type, $id, $action);

    // Store the message based on action
    if (strpos($msg, 'approved') !== false || strpos($msg, 'deleted') !== false) {
        $success_msg[] = $msg;
    } else {
        $warning_msg[] = $msg;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Figuras D Arte - Registered Users Page</title>
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
    <?php include '../components/admin_header.php'; ?>
    <div class="main-container"> 
        <section class="accounts">
            <div class="heading">
                <h1>Manage Accounts</h1>
            </div>

            <h2>Users</h2>
            <div class="user-container">
                <?php while ($user = $select_users->fetch(PDO::FETCH_ASSOC)) { ?>
                <div class="user-box">
                    <img 
                        src="<?= !empty($user['profile_picture']) 
                            ? '../uploaded_files/' . htmlspecialchars($user['profile_picture']) 
                            : '../uploaded_files/default.png'; ?>" 
                        alt="User Image" class="profile-img">
                    <p><?= isset($user['username']) ? htmlspecialchars($user['username']) : 'Unknown'; ?></p>
                    <p>Status: <?= htmlspecialchars($user['status'] ?? 'Unknown'); ?></p>
                    <form action="" method="post">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id'] ?? ''); ?>">
                        <input type="hidden" name="type" value="user">
                        <?php if (($user['status'] ?? '') === 'pending') { ?>
                            <button type="submit" name="action_type" value="approve" class="btn">Approve</button>
                            <button type="submit" name="action_type" value="reject" class="btn">Reject</button>
                        <?php } ?>
                        <button type="submit" name="action_type" value="delete" class="btn" onclick="return confirm('Delete this user?');">Delete</button>
                    </form>
                </div>
                <?php } ?>
            </div>

            <h2>Sellers</h2>
            <div class="seller-container">
                <?php while ($seller = $select_sellers->fetch(PDO::FETCH_ASSOC)) { ?>
                <div class="seller-box">
                    <img 
                        src="<?= !empty($seller['profile_picture']) 
                            ? '../uploaded_files/' . htmlspecialchars($seller['profile_picture']) 
                            : '../uploaded_files/default.png'; ?>" 
                        alt="Seller Image" class="profile-img">
                    <p><?= isset($seller['username']) ? htmlspecialchars($seller['username']) : 'Unknown'; ?></p>
                    <p>Status: <?= htmlspecialchars($seller['status'] ?? 'Unknown'); ?></p>
                    <form action="" method="post">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($seller['id'] ?? ''); ?>">
                        <input type="hidden" name="type" value="seller">
                        <?php if (($seller['status'] ?? '') === 'pending') { ?>
                            <button type="submit" name="action_type" value="approve" class="btn">Approve</button>
                            <button type="submit" name="action_type" value="reject" class="btn">Reject</button>
                        <?php } ?>
                        <button type="submit" name="action_type" value="delete" class="btn" onclick="return confirm('Delete this seller?');">Delete</button>
                    </form>
                </div>
                <?php } ?>
            </div>
        </section>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="../js/admin_script.js"></script>

    <?php include '../components/alert.php'; ?>
</body>
</html>