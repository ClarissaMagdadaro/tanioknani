<?php
// Include the database connection
include '../components/connect.php';
include '../components/admin_header.php';

// Fetch all posts that are either not approved or pending (i.e., where approved = 0 and deleted = 0)
$query = $conn->prepare("SELECT * FROM posts WHERE deleted = 0 ORDER BY id DESC");
$query->execute();
$posts = $query->fetchAll(PDO::FETCH_ASSOC);

// Handle actions (approve, deny, delete) via AJAX
if (isset($_POST['action'])) {
    $post_id = $_POST['post_id'];
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $approve_post = $conn->prepare("UPDATE posts SET approved = 1 WHERE id = ?");
        $approve_post->execute([$post_id]);
    } elseif ($action == 'deny') {
        $deny_post = $conn->prepare("UPDATE posts SET approved = 0 WHERE id = ?");
        $deny_post->execute([$post_id]);
    } elseif ($action == 'delete') {
        $delete_post = $conn->prepare("UPDATE posts SET deleted = 1 WHERE id = ?");
        $delete_post->execute([$post_id]);
    }
    
    echo json_encode(['status' => 'success']);  // Send success response back
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Posts</title>
    <link rel="stylesheet" type="text/css" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <img src="../image/logo.png" alt="Logo" width="60">
        </div>
        <div class="right">
            <div class="bx bxs-user" id="user-btn"></div>
            <div class="toggle-btn"><i class="bx bx-menu"></i></div>
        </div>
    </header>

    <!-- Admin Content Area -->
    <div class="main-container">
        <section class="admin-posts">
            <h1>Manage Posts</h1>

            <?php if (!empty($posts)): ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post" id="post-<?php echo $post['id']; ?>">
                        <div class="post-header">
                            <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                            <p class="category">Category: <?php echo htmlspecialchars($post['category']); ?></p>
                        </div>

                        <div class="post-image">
                            <img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image">
                        </div>

                        <div class="post-content">
                            <p><?php echo htmlspecialchars($post['description']); ?></p>
                        </div>

                        <!-- Admin Post Actions: Approve, Deny, Delete -->
                        <div class="post-actions">
                            <?php if ($post['approved'] == 0): ?>
                                <button class="approve-btn" data-id="<?php echo $post['id']; ?>">Approve</button>
                            <?php else: ?>
                                <button class="approved-btn" disabled>Approved</button>
                            <?php endif; ?>

                            <button class="deny-btn" data-id="<?php echo $post['id']; ?>">Deny</button>
                            <button class="delete-btn" data-id="<?php echo $post['id']; ?>">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No posts available for approval.</p>
            <?php endif; ?>
        </section>
    </div>

    <script>
        // AJAX for approve, deny, delete actions
        $(document).on('click', '.approve-btn, .deny-btn, .delete-btn', function() {
            var action = $(this).hasClass('approve-btn') ? 'approve' : 
                         $(this).hasClass('deny-btn') ? 'deny' : 'delete';
            var post_id = $(this).data('id');
            
            $.ajax({
                url: '', // Current page
                method: 'POST',
                data: {
                    action: action,
                    post_id: post_id
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'success') {
                        // Update the post's status dynamically
                        if (action == 'approve') {
                            $('#post-' + post_id).find('.approve-btn').prop('disabled', true).text('Approved');
                        } else if (action == 'deny') {
                            $('#post-' + post_id).find('.deny-btn').prop('disabled', true);
                        } else if (action == 'delete') {
                            $('#post-' + post_id).remove(); // Remove the post from the list
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>

<style>
    /* Admin Action Buttons */
    button.approve-btn {
        background-color: green;
        color: white;
        padding: 5px 10px;
        border: none;
        cursor: pointer;
    }

    button.deny-btn {
        background-color: red;
        color: white;
        padding: 5px 10px;
        border: none;
        cursor: pointer;
    }

    button.delete-btn {
        background-color: gray;
        color: white;
        padding: 5px 10px;
        border: none;
        cursor: pointer;
    }

    button.approved-btn, button.deny-btn[disabled], button.delete-btn[disabled] {
        cursor: not-allowed;
    }
</style>
