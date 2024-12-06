<?php
// Include the database connection
include 'components/connect.php';

// Check if the user is logged in via cookies or session
if (!isset($_COOKIE['user_id']) || empty($_COOKIE['user_id'])) {
    echo "You must be logged in to view posts.";
    exit();
}

$user_id = $_COOKIE['user_id']; // Logged-in user ID

// Fetch approved and non-deleted posts along with the seller's name
$query = $conn->prepare("SELECT posts.*, users.name AS seller_name FROM posts JOIN users ON posts.user_id = users.id WHERE posts.approved = 1 AND posts.deleted = 0 ORDER BY posts.id DESC");
$query->execute();
$posts = $query->fetchAll(PDO::FETCH_ASSOC);

// Handle like functionality
if (isset($_POST['like'])) {
    $post_id = $_POST['post_id'];

    // Check if the user has already liked the post
    $check_like = $conn->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
    $check_like->execute([$post_id, $user_id]);

    if ($check_like->rowCount() == 0) {
        // If not liked, insert into likes table
        $insert_like = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $insert_like->execute([$post_id, $user_id]);
    } else {
        // If already liked, remove like (unlike)
        $remove_like = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $remove_like->execute([$post_id, $user_id]);
    }
}

// Handle comment functionality
if (isset($_POST['comment'])) {
    $post_id = $_POST['post_id'];
    $comment = htmlspecialchars($_POST['comment_text']);
    
    // Insert the comment into the database
    $insert_comment = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $insert_comment->execute([$post_id, $user_id, $comment]);
}

// Fetch the like counts and comments for each post
$like_counts = [];
$comment_counts = [];
foreach ($posts as $post) {
    $post_id = $post['id'];

    // Get the like count for each post
    $like_count = $conn->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $like_count->execute([$post_id]);
    $like_counts[$post_id] = $like_count->fetchColumn();

    // Get the comment count for each post
    $comment_count = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
    $comment_count->execute([$post_id]);
    $comment_counts[$post_id] = $comment_count->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Posts</title>
    <link rel="stylesheet" type="text/css" href="css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>

<!-- Include the user header here -->
<?php include 'components/user_header.php'; ?>

<!-- Main Content Area -->
<div class="main-container">
    <section class="feed">
        <h1>Post Feed</h1>

        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                        <p class="category">Category: <?php echo htmlspecialchars($post['category']); ?></p>
                        <p class="seller-name">Posted by: <?php echo htmlspecialchars($post['seller_name']); ?></p>
                    </div>

                    <div class="post-image">
                        <?php if (!empty($post['image'])): ?>
                            <!-- Display the uploaded image -->
                            <img src="../uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image">
                        <?php endif; ?>
                    </div>

                    <div class="post-content">
                        <p><?php echo htmlspecialchars($post['description']); ?></p>
                    </div>

                    <?php
                    // Fetch the like count and check if the user has liked the post
                    $like_count = $like_counts[$post['id']];
                    $check_like = $conn->prepare("SELECT * FROM likes WHERE post_id = ? AND user_id = ?");
                    $check_like->execute([$post['id'], $user_id]);
                    $has_liked = $check_like->rowCount() > 0;
                    ?>

                    <div class="post-actions">
                        <form method="POST" class="like-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <button type="submit" name="like" class="like-btn">
                                <?php if ($has_liked): ?>
                                    <i class="fa fa-thumbs-down"></i> Unlike
                                <?php else: ?>
                                    <i class="fa fa-thumbs-up"></i> Like
                                <?php endif; ?>
                            </button>
                            <span class="like-count"><?php echo $like_count; ?> Likes</span>
                        </form>

                        <button class="comment-toggle">
                            <i class="fa fa-comment"></i> Comment
                        </button>
                    </div>

                    <div class="comment-section">
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="text" name="comment_text" placeholder="Write a comment..." required>
                            <button type="submit" name="comment">Post</button>
                        </form>

                        <div class="comments">
                            <?php
                            // Fetch comments with user details
                            $comments = $conn->prepare("SELECT comments.*, users.name AS commenter_name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY created_at DESC");
                            $comments->execute([$post['id']]);
                            $all_comments = $comments->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($all_comments as $comment) {
                                // Display each comment
                                echo "<p><strong>" . htmlspecialchars($comment['commenter_name']) . ":</strong> " . htmlspecialchars($comment['comment']) . "</p>";
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Show status for pending or deleted posts -->
                    <?php if ($post['approved'] == 0 && $post['deleted'] == 0): ?>
                        <p class="status-message">This post is pending approval.</p>
                    <?php elseif ($post['approved'] == 0 && $post['deleted'] == 1): ?>
                        <p class="status-message">This post has been deleted.</p>
                    <?php elseif ($post['approved'] == 1 && $post['deleted'] == 1): ?>
                        <p class="status-message">This post has been deleted.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No posts available.</p>
        <?php endif; ?>
    </section>
</div>

<script>
    // Toggle comment sections
    document.querySelectorAll('.comment-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const commentSection = button.parentElement.nextElementSibling;
            commentSection.style.display = commentSection.style.display === 'block' ? 'none' : 'block';
        });
    });
</script>

</body>
</html>
