<!-- admin_header.php -->

<header class="admin-header">
    <div class="logo">
        <a href="dashboard.php">Figuras D Arte</a>
    </div>
    <div class="nav-links">
             <ul>
                <li><a href="admin_panel/dashboard.php"><i class="bx bxs-home-smile"></i>Dashboard</a></li>
                <li><a href="view_product.php"><i class="bx bxs-food-menu"></i>View Product</a></li>
                <li><a href="view_posts.php"><i class="bx bxs-food-menu"></i>View Post</a></li>
                <li><a href="admin_accounts.php"><i class="bx bxs-user-detail"></i>Accounts</a></li>
                <li><a href="../components/admin_logout.php" onclick="return confirm('Logout');"><i class="bx bx-log-out"></i>Log Out</a></li>
            </ul>
    </div>
</header>

<!-- Styles (optional) -->
<style>
    .admin-header {
        background-color: #333;
        padding: 10px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .admin-header .logo a {
        color: white;
        text-decoration: none;
        font-size: 24px;
        font-weight: bold;
    }

    .admin-header .nav-links ul {
        list-style: none;
        display: flex;
        gap: 20px;
    }

    .admin-header .nav-links a {
        color: white;
        text-decoration: none;
        font-size: 18px;
    }

    .admin-header .nav-links a:hover {
        text-decoration: underline;
    }
</style>
