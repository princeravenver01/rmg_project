<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php'; // RMG DB
if ($_SESSION['role'] !== 'admin') { exit('Access Denied'); }

// --- Connect to the Tribute Grace Database ---
$tg_conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, 'kasikas_official');
if ($tg_conn->connect_error) {
    die("<div class='card'><p>Error: Could not connect to the Tribute Grace database.</p></div>");
}

// Query to get all RMG users flagged as TG members, and then check their status in the TG database
$sql = "
    SELECT u.id, u.name, u.username, u.email
    FROM rmg_database.users u 
    WHERE u.is_tg_member = 1
    ORDER BY u.id DESC
";
$rmg_users_with_tg = $mysqli->query($sql);

$member_statuses = [];
if ($rmg_users_with_tg) {
    $stmt_tg_status = $tg_conn->prepare("SELECT status, date_approved FROM policies WHERE email = ? LIMIT 1");
    
    while ($rmg_user = $rmg_users_with_tg->fetch_assoc()) {
        $stmt_tg_status->bind_param("s", $rmg_user['email']);
        $stmt_tg_status->execute();
        $tg_result = $stmt_tg_status->get_result();
        
        $status_info = ['status' => 'Not Found', 'date_approved' => null];
        if ($tg_policy = $tg_result->fetch_assoc()) {
            $status_info['status'] = $tg_policy['status'];
            $status_info['date_approved'] = $tg_policy['date_approved'];
        }
        
        $member_statuses[] = [
            'rmg_user' => $rmg_user,
            'tg_status' => $status_info
        ];
    }
    $stmt_tg_status->close();
}

$tg_conn->close();
$mysqli->close();
?>

<div class="content-header">
    <h2>Tribute Grace Policy Status Monitor</h2>
    <p>View the status of TG policies for your RMG members.</p>
</div>

<div class="card">
    <style> /* ... table styles ... */ </style>
    <table>
        <thead>
            <tr>
                <th>RMG Member</th>
                <th>TG Policy Status</th>
                <th>Date Approved (TG)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($member_statuses as $member): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($member['rmg_user']['name']); ?></strong><br>
                        <small>[<?php echo htmlspecialchars($member['rmg_user']['username']); ?>]</small>
                    </td>
                    <td>
                        <?php 
                            $status = $member['tg_status']['status'];
                            $color = 'grey';
                            if ($status === 'pending') $color = '#f39c12';
                            if ($status === 'approved') $color = '#2ecc71';
                            if ($status === 'disapproved') $color = '#e74c3c';
                            echo '<span style="color:white; background-color:'.$color.'; padding: 4px 8px; border-radius: 5px; font-weight:bold;">' . ucfirst($status) . '</span>';
                        ?>
                    </td>
                    <td>
                        <?php echo $member['tg_status']['date_approved'] ? date('M d, Y', strtotime($member['tg_status']['date_approved'])) : 'N/A'; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($member_statuses)): ?>
                <tr><td colspan="3" style="text-align: center;">No RMG members are currently registered with Tribute Grace.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once '../../src/templates/admin_footer.php'; ?>