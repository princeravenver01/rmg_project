<?php
session_start();
require_once '../../src/templates/member_header.php';
require_once '../../src/includes/db_connect.php';

$member_id = $_SESSION['user_id'];
?>

<div class="content-header">
    <h2>My Team Report</h2>
    <p>A list of members you have personally sponsored.</p>
</div>

<div class="card">
    <style> /* Table Styles */
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid var(--border-color); padding: 15px; text-align: left; vertical-align: middle; }
        th { font-weight: 600; font-size: 14px; color: var(--text-muted); }
    </style>
    <table>
        <thead>
            <tr>
                <th>Member ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Date Joined</th>
                <th>Position</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // SQL query to find all users sponsored by the logged-in member
            $sql = "SELECT u.id, u.name, u.email, u.created_at, gt.position 
                    FROM users u
                    JOIN genealogy_tree gt ON u.id = gt.user_id
                    WHERE gt.sponsor_id = ? 
                    ORDER BY u.id DESC";
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while ($team_member = $result->fetch_assoc()) {
                    $position_text = ($team_member['position'] === 'L') ? 'Left Leg' : 'Right Leg';
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($team_member['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($team_member['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($team_member['email']) . "</td>";
                    echo "<td>" . date('M d, Y', strtotime($team_member['created_at'])) . "</td>";
                    echo "<td>" . $position_text . "</td>";
                    echo "</tr>";
                }
            } else {
                echo '<tr><td colspan="5" style="text-align: center; padding: 20px;">You have not sponsored any members yet.</td></tr>';
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