<?php
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && $_SESSION['role'] === 'member') {
    header('Location: member/dashboard.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RMG Member Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/public_style.css">
</head>
<body>
    <div class="form-container" style="max-width: 450px;">
        <h2>Member Portal Login</h2>
        <?php
        if (isset($_SESSION['error'])) {
            // Display the error message in a styled div
            echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            // Unset the error message so it doesn't show again on refresh
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="handle_member_login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>