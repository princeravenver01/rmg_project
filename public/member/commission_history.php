<?php
session_start();
require_once '../../src/templates/member_header.php';
require_once '../../src/includes/db_connect.php';

$member_id = $_SESSION['user_id'];
?>

<div class="content-header">
    <h2>Commission History</h2>
    <p>A detailed breakdown of all your earnings.</p>
</div>

<div class="card">
    <style> /* Table Styles */
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 15px; text-align: left; vertical-align: middle; }
        th { font-weight: 600; font-size: 14px; color: var(--text-muted); }
        .commission-amount { color: #27ae60; font-weight: 600; }
    </style>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Commission Type</th>
                <th>Amount</th>
                <th>From Member</th>
                <th>Cycle ID</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // We use a LEFT JOIN to get the name of the member who triggered a bonus
            $sql = "SELECT c.*, u.name AS source_member_name 
                    FROM commissions c
                    LEFT JOIN users u ON c.source_user_id = u.id
                    WHERE c.user_id = ? 
                    ORDER BY c.id DESC";
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($commission = $result->fetch_assoc()) {
                    // Make the commission type more readable
                    $type_readable = ucwords(str_replace('_', ' ', $commission['type']));
                    
                    echo "<tr>";
                    echo "<td>" . date('M d, Y h:i A', strtotime($commission['created_at'])) . "</td>";
                    echo "<td>" . htmlspecialchars($type_readable) . "</td>";
                    echo "<td class='commission-amount'>+ ₱" . number_format($commission['amount'], 2) . "</td>";
                    echo "<td>" . ($commission['source_member_name'] ? htmlspecialchars($commission['source_member_name']) . ' (ID: ' . $commission['source_user_id'] . ')' : 'N/A') . "</td>";
                    echo "<td>" . htmlspecialchars($commission['cycle_id']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="5" style="text-align: center; padding: 20px;">You have not earned any commissions yet.</td></tr>';
            }
            $stmt->close();
            ?>
        </tbody>
    </table>
</div>

<?php
require_once '../../src/templates/member_footer.php';
$mysqli->close();
?>