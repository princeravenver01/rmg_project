<?php
// Session validation remains
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: index.php');
    exit;
}
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMG Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<div class="admin-layout">
    <!-- ===== Sidebar ===== -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <span class="logo-text">RMG Corp</span>
        </div>

        <nav class="sidebar-menu">
            <h3>MENU</h3>
            <ul>
                <li class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                    <a href="dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
                </li>
                <li class="<?php echo ($currentPage == 'staff_management.php' || $currentPage == 'staff_add.php') ? 'active' : ''; ?>">
                    <a href="staff_management.php"><i class="fa-solid fa-users"></i> Staff Management</a>
                </li>
                <li class="<?php echo ($currentPage == 'member_management.php') ? 'active' : ''; ?>">
    <a href="member_management.php"><i class="fa-solid fa-users"></i> Member Management</a>
</li>
<li class="<?php echo ($currentPage == 'tg_status_monitor.php') ? 'active' : ''; ?>">
    <a href="tg_status_monitor.php"><i class="fa-solid fa-clipboard-check"></i> TG Status Monitor</a>
</li>
                 <li class="<?php echo ($currentPage == 'product_management.php' || $currentPage == 'product_add.php') ? 'active' : ''; ?>">
    <a href="product_management.php"><i class="fa-solid fa-box-archive"></i> Products</a>
</li>
<li class="<?php echo ($currentPage == 'package_management.php' || $currentPage == 'package_add.php') ? 'active' : ''; ?>">
    <a href="package_management.php"><i class="fa-solid fa-boxes-packing"></i> Packages</a>
</li>
<li class="<?php echo ($currentPage == 'purchase_record.php') ? 'active' : ''; ?>">
    <a href="purchase_record.php"><i class="fa-solid fa-cash-register"></i> Generate Codes</a> <!-- Renamed for clarity -->
</li>
<li class="<?php echo ($currentPage == 'product_purchase.php') ? 'active' : ''; ?>">
    <a href="product_purchase.php"><i class="fa-solid fa-cash-register"></i> Product POS</a>
</li>
<li class="<?php echo ($currentPage == 'purchase_history.php') ? 'active' : ''; ?>">
    <a href="purchase_history.php"><i class="fa-solid fa-history"></i> Package Purchase History</a>
</li>
<li class="<?php echo ($currentPage == 'product_sale_history.php') ? 'active' : ''; ?>">
    <a href="product_sale_history.php"><i class="fa-solid fa-receipt"></i> Product Sale History</a>
</li>
<li class="<?php echo ($currentPage == 'activation_codes.php') ? 'active' : ''; ?>">
    <a href="activation_codes.php"><i class="fa-solid fa-key"></i> Activation Codes</a>
</li>
<li class="<?php echo ($currentPage == 'genealogy.php') ? 'active' : ''; ?>">
    <a href="genealogy.php"><i class="fa-solid fa-sitemap"></i> Genealogy Tree</a>
</li>
<li class="<?php echo ($currentPage == 'unilevel_tree.php') ? 'active' : ''; ?>">
    <a href="unilevel_tree.php"><i class="fa-solid fa-users-line"></i> Unilevel Tree (Sponsor)</a>
</li>
<li class="<?php echo ($currentPage == 'encashment_requests.php') ? 'active' : ''; ?>">
    <a href="encashment_requests.php"><i class="fa-solid fa-money-bill-transfer"></i> Encashments</a>
</li>
<li class="<?php echo ($currentPage == 'manage_gcs.php') ? 'active' : ''; ?>">
    <a href="manage_gcs.php"><i class="fa-solid fa-gift"></i> Gift Certificates</a>
</li>
<li class="<?php echo ($currentPage == 'leadership_bonus_history.php') ? 'active' : ''; ?>">
    <a href="leadership_bonus_history.php"><i class="fa-solid fa-sitemap"></i> Leadership Bonuses</a>
</li>
                 <li><a href="#"><i class="fa-solid fa-receipt"></i> Reports</a></li>
            </ul>

            <h3>GENERAL</h3>
            <ul>
                <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- ===== Main Content ===== -->
    <main class="main-content">
        <!-- ===== Top Bar ===== -->
        <header class="top-bar">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" placeholder="Search task...">
            </div>
            <div class="top-bar-right">
                <div class="user-profile">
                    <img src="https://i.pravatar.cc/150?u=<?php echo $_SESSION['user_id']; ?>" alt="User Avatar">
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <!-- In a real app, you would fetch the user's email from the DB -->
                        <span class="user-email">admin@rmg.com</span>
                    </div>
                </div>
            </div>
        </header>