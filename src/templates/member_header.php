<?php
session_start(); // Start session at the very top
require_once __DIR__ . '/../../src/includes/config.php'; // Include our new config file

// Session validation
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'member') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMG Member Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- === CORRECTED CSS PATH === -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/admin_style.css">
</head>
<body>

<div class="admin-layout">
    <!-- ===== Sidebar ===== -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fa-solid fa-user-check"></i></div>
            <span class="logo-text">Member Portal</span>
        </div>

        <nav class="sidebar-menu">
            <h3>MENU</h3>
            <ul>
            <li class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
    <a href="dashboard.php"><i class="fa-solid fa-table-columns"></i> Dashboard</a>
</li>
<li class="<?php echo ($currentPage == 'genealogy.php') ? 'active' : ''; ?>">
    <a href="genealogy.php"><i class="fa-solid fa-sitemap"></i> My Genealogy</a>
</li>
<li class="<?php echo ($currentPage == 'redundant_binary.php') ? 'active' : ''; ?>">
    <a href="redundant_binary.php"><i class="fa-solid fa-arrows-split-up-and-left"></i> Redundant Binary</a>
</li>
<li class="<?php echo ($currentPage == 'ewallet.php') ? 'active' : ''; ?>">
    <a href="ewallet.php"><i class="fa-solid fa-wallet"></i> My Wallet</a>
</li>
<li class="<?php echo ($currentPage == 'commission_history.php') ? 'active' : ''; ?>">
    <a href="commission_history.php"><i class="fa-solid fa-receipt"></i> Commission History</a>
</li>
<li class="<?php echo ($currentPage == 'my_team.php') ? 'active' : ''; ?>">
    <a href="my_team.php"><i class="fa-solid fa-users"></i> My Team</a>
</li>
            </ul>
            <h3>GENERAL</h3>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- ===== Main Content ===== -->
    <main class="main-content">
        <!-- ===== Top Bar ===== -->
        <header class="top-bar">
            <!-- Top bar content -->
             <div class="top-bar-right">
                <div class="user-profile">
                    <img src="https://i.pravatar.cc/150?u=<?php echo $_SESSION['user_id']; ?>" alt="User Avatar">
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <span class="user-email">Member ID: <?php echo $_SESSION['user_id']; ?></span>
                    </div>
                </div>
            </div>
        </header>