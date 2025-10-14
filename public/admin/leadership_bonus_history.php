<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';

// Security check for admin role
if ($_SESSION['role'] !== 'admin') {
    exit('Access Denied');
}

// --- Build the SQL query ---
// This query joins the commissions table with the users table twice:
// 1. To get the name of the person who RECEIVED the bonus (Recipient).
// 2. To get the name of the person whose earnings TRIGGERED the bonus (Source).
$sql = "
    SELECT 
        c.id, 
        c.created_at, 
        c.type, 
        c.amount,
        c.cycle_id,
        recipient.id AS recipient_id,
        recipient.username AS recipient_username,
        source.id AS source_id,
        source.username AS source_username
    FROM commissions c
    JOIN users recipient ON c.user_id = recipient.id
    LEFT JOIN users source ON c.source_user_id = source.id
    WHERE c.type IN ('leadership_l1', 'leadership_l2', 'leadership_l3')
    ORDER BY c.id DESC
";

$result = $mysqli->query($sql);
?>

<div class="content-header">
    <h2>Leadership Bonus History</h2>
    <p>A detailed log of all L1, L2, and L3 leadership bonuses paid out.</p>
</div>

<div class="card">
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 12px; text-align: left; vertical-align: middle; font-size: 14px; }
        th { font-weight: 600; color: var(--text-muted); }
        .bonus-amount { color: #27ae60; font-weight: bold; }
        .user-link { text-decoration: none; color: #2980b9; font-weight: 500; }
        .user-link:hover { text-decoration: underline; }
    </style>
    <table>
        <thead>
            <tr>
                <th>Date Paid</th>
                <th>Recipient (Upline)</th>
                <th>Bonus Type</th>
                <th>Amount</th>
                <th>Triggered By (Downline)</th>
                <th>Cycle ID</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($bonus = $result->fetch_assoc()) {
                    // Make the bonus type more readable (e.g., "Leadership L1 (50%)")
                    $bonus_type_text = 'Unknown';
                    if ($bonus['type'] === 'leadership_l1') $bonus_type_text = 'Level 1 (50%)';
                    if ($bonus['type'] === 'leadership_l2') $bonus_type_text = 'Level 2 (30%)';
                    if ($bonus['type'] === 'leadership_l3') $bonus_type_text = 'Level 3 (20%)';

                    echo "<tr>";
                    echo "<td>" . date('M d, Y h:i A', strtotime($bonus['created_at'])) . "</td>";
                    // Link to the recipient's genealogy tree
                    echo '<td><a href="genealogy.php?user_id=' . $bonus['recipient_id'] . '" class="user-link">' . htmlspecialchars($bonus['recipient_username']) . '</a></td>';
                    echo "<td>" . $bonus_type_text . "</td>";
                    echo "<td class='bonus-amount'>₱" . number_format($bonus['amount'], 2) . "</td>";
                    // Link to the source member's genealogy tree
                    echo '<td><a href="genealogy.php?user_id=' . $bonus['source_id'] . '" class="user-link">' . htmlspecialchars($bonus['source_username']) . '</a></td>';
                    echo "<td>" . htmlspecialchars($bonus['cycle_id']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="6" style="text-align: center; padding: 20px;">No Leadership Bonuses have been paid out yet.</td></tr>';
            }
            $mysqli->close();
            ?>
        </tbody>
    </table>
</div>

<?php
require_once '../../src/templates/admin_footer.php';
?>