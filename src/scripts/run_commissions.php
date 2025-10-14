<?php
set_time_limit(300); 
echo "<pre>";
date_default_timezone_set('Asia/Manila'); // Set to Philippine Time
echo "--- Commission Engine Started: " . date('Y-m-d H:i:s') . " ---\n\n";

require_once __DIR__ . '/../includes/db_connect.php';

// --- Configuration ---
$points_per_pair = 50;
$max_pairs_per_cycle = 10;
$cycle_id = date('Y-m-d') . (date('H') < 12 ? '-AM' : '-PM');

// Fetch settings from the database
$settings_result = $mysqli->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$gift_certificate_amount = (float)($settings['gift_certificate_amount'] ?? 1000.00);

// --- Get all members ---
$sql_members = "SELECT u.id as user_id, u.total_pairs_earned, mp.carry_over_left, mp.carry_over_right 
                FROM users u
                LEFT JOIN member_profiles mp ON u.id = mp.user_id
                WHERE u.role = 'member'";
$members_result = $mysqli->query($sql_members);

if ($members_result->num_rows === 0) {
    die("No members found. Engine finished.\n");
}

while ($member = $members_result->fetch_assoc()) {
    $member_id = $member['user_id'];
    $total_pairs_earned_before_this_cycle = (int)$member['total_pairs_earned'];
    echo "Processing Member ID: $member_id...\n";

    // 1. Get NEW points earned this cycle
    $stmt_points = $mysqli->prepare("SELECT SUM(CASE WHEN position = 'L' THEN points ELSE 0 END) AS left_points, SUM(CASE WHEN position = 'R' THEN points ELSE 0 END) AS right_points FROM binary_points WHERE user_id = ? AND status = 'unprocessed'");
    $stmt_points->bind_param("i", $member_id);
    $stmt_points->execute();
    $points_data = $stmt_points->get_result()->fetch_assoc();
    $stmt_points->close();

    // 2. Add carry-over points from the PREVIOUS cycle
    $new_left_points = (int)($points_data['left_points'] ?? 0);
    $new_right_points = (int)($points_data['right_points'] ?? 0);
    $carry_over_left = (int)($member['carry_over_left'] ?? 0);
    $carry_over_right = (int)($member['carry_over_right'] ?? 0);
    
    $total_left_points = $new_left_points + $carry_over_left;
    $total_right_points = $new_right_points + $carry_over_right;
    
    echo "  - Carry-over: L=$carry_over_left, R=$carry_over_right | New Points: L=$new_left_points, R=$new_right_points\n";
    echo "  - Total Points for Cycle: Left=$total_left_points, Right=$total_right_points\n";

    // 3. Calculate Potential Pairs
    $potential_pairs = floor(min($total_left_points, $total_right_points) / $points_per_pair);
    
    if ($potential_pairs <= 0) {
        // Clear carry-over from DB, as it's now part of the main balance
        $mysqli->query("UPDATE member_profiles SET carry_over_left = 0, carry_over_right = 0 WHERE user_id = $member_id");
        echo "  - Not enough pairs to pay. Clearing carry-over and skipping.\n\n";
        continue;
    }

    $pairs_to_pay = min($potential_pairs, $max_pairs_per_cycle);
    $surplus_pairs = $potential_pairs - $pairs_to_pay;
    
    // 4. Calculate payout pair-by-pair and check for GC bonus
    $total_binary_payout = 0;
    $generated_gcs = 0;
    $pairs_paid_this_cycle = 0;
    
    for ($i = 1; $i <= $pairs_to_pay; $i++) {
        $current_total_pair_count = $total_pairs_earned_before_this_cycle + $i;
        
        $payout_for_this_pair = ($current_total_pair_count > 20) ? 600.00 : 500.00;
        $total_binary_payout += $payout_for_this_pair;
        
        if ($current_total_pair_count >= 25 && $current_total_pair_count % 5 === 0) {
            $generated_gcs++;
        }
        $pairs_paid_this_cycle++;
    }
    
    echo "  - Potential Pairs: $potential_pairs. Paying for $pairs_to_pay pairs. Total Payout: ₱" . number_format($total_binary_payout, 2) . "\n";

    // 5. THE FLUSH-OUT LOGIC
    $points_used_for_pairing = $pairs_to_pay * $points_per_pair;
    $points_from_surplus_pairs = $surplus_pairs * $points_per_pair;
    $points_retained = $points_from_surplus_pairs * 0.5; // 50% retained
    
    $remaining_left_points = $total_left_points - $points_used_for_pairing - $points_from_surplus_pairs;
    $remaining_right_points = $total_right_points - $points_used_for_pairing - $points_from_surplus_pairs;

    $new_carry_over_left = $remaining_left_points + ($total_left_points >= $total_right_points ? $points_retained : 0);
    $new_carry_over_right = $remaining_right_points + ($total_right_points > $total_left_points ? $points_retained : 0);

    if($surplus_pairs > 0) {
        echo "  - Surplus Pairs: $surplus_pairs ($points_from_surplus_pairs points each leg).\n";
        echo "  - 50% Retained: $points_retained points will be carried over to the strong leg.\n";
        echo "  - Next Cycle's Starting Points: Left=$new_carry_over_left, Right=$new_carry_over_right\n";
    }
    if ($surplus_pairs > 0) {
        echo "  - Flushing out $surplus_pairs surplus pairs.\n";
        
        // --- NEW: Log Company Gain from Flush Out ---
        // According to your 50/50 rule, half the value of surplus pairs is company gain.
        $flushed_value = ($surplus_pairs * $payout_per_pair) * 0.5; // Assuming 50% of the PAIR VALUE
        
        $stmt_gain = $mysqli->prepare("INSERT INTO company_gains (type, amount, source_user_id, cycle_id) VALUES ('flush_out', ?, ?, ?)");
        $stmt_gain->bind_param("dis", $flushed_value, $member_id, $cycle_id);
        $stmt_gain->execute();
        $stmt_gain->close();
        // --- END NEW ---
    }

    // --- 6. Start Transaction ---
    $mysqli->begin_transaction();
    try {
        $source_commission_id = null;
        if ($total_binary_payout > 0) {
            $stmt_comm = $mysqli->prepare("INSERT INTO commissions (user_id, type, amount, cycle_id) VALUES (?, 'binary_pair', ?, ?)");
            $stmt_comm->bind_param("ids", $member_id, $total_binary_payout, $cycle_id);
            $stmt_comm->execute();
            $source_commission_id = $mysqli->insert_id;
            $stmt_comm->close();

            $stmt_wallet = $mysqli->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + ?");
            $stmt_wallet->bind_param("idd", $member_id, $total_binary_payout, $total_binary_payout);
            $stmt_wallet->execute();
            $stmt_wallet->close();
            
            $upline_sponsors = [];
            $current_user_for_upline_search = $member_id;
            $safety_counter = 0;
            while (count($upline_sponsors) < 3 && $current_user_for_upline_search > 0 && $safety_counter < 10) {
                $stmt_upline = $mysqli->prepare("SELECT sponsor_id FROM genealogy_tree WHERE user_id = ?");
                $stmt_upline->bind_param("i", $current_user_for_upline_search);
                $stmt_upline->execute();
                if ($upline_row = $stmt_upline->get_result()->fetch_assoc()) {
                    $sponsor_id = $upline_row['sponsor_id'];
                    if ($sponsor_id > 0 && $sponsor_id != $current_user_for_upline_search) {
                        $upline_sponsors[] = $sponsor_id;
                        $current_user_for_upline_search = $sponsor_id;
                    } else { break; }
                } else { break; }
                $stmt_upline->close();
                $safety_counter++;
            }
            
            $leadership_rates = [0.50, 0.30, 0.20];
            foreach ($upline_sponsors as $level => $upline_id) {
                $bonus_amount = $total_binary_payout * $leadership_rates[$level];
                $bonus_type = 'leadership_l' . ($level + 1);
                echo "    - Paying Leadership Bonus ($bonus_type) of ₱$bonus_amount to Upline ID: $upline_id\n";
                $stmt_lb = $mysqli->prepare("INSERT INTO commissions (user_id, type, amount, source_user_id, cycle_id) VALUES (?, ?, ?, ?, ?)");
                $stmt_lb->bind_param("isids", $upline_id, $bonus_type, $bonus_amount, $member_id, $cycle_id);
                $stmt_lb->execute();
                $stmt_lb->close();
                $stmt_lb_wallet = $mysqli->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + ?");
                $stmt_lb_wallet->bind_param("idd", $upline_id, $bonus_amount, $bonus_amount);
                $stmt_lb_wallet->execute();
                $stmt_lb_wallet->close();
            }
        }

        if ($generated_gcs > 0) {
            $stmt_gc = $mysqli->prepare("INSERT INTO gift_certificates (code, user_id, amount, source_commission_id) VALUES (?, ?, ?, ?)");
            for ($j = 0; $j < $generated_gcs; $j++) {
                $gc_code = 'GC-' . strtoupper(bin2hex(random_bytes(6)));
                $stmt_gc->bind_param("sidi", $gc_code, $member_id, $gift_certificate_amount, $source_commission_id);
                $stmt_gc->execute();
            }
            $stmt_gc->close();
        }

        if ($pairs_paid_this_cycle > 0) {
            $stmt_update_pairs = $mysqli->prepare("UPDATE users SET total_pairs_earned = total_pairs_earned + ? WHERE id = ?");
            $stmt_update_pairs->bind_param("ii", $pairs_paid_this_cycle, $member_id);
            $stmt_update_pairs->execute();
            $stmt_update_pairs->close();
        }

        // Update carry-over for the NEXT cycle
        $stmt_carry = $mysqli->prepare("UPDATE member_profiles SET carry_over_left = ?, carry_over_right = ? WHERE user_id = ?");
        $stmt_carry->bind_param("ddi", $new_carry_over_left, $new_carry_over_right, $member_id);
        $stmt_carry->execute();
        $stmt_carry->close();

        // Mark ALL NEW points from this cycle as processed
        $stmt_update_points = $mysqli->prepare("UPDATE binary_points SET status = 'processed', cycle_id = ? WHERE user_id = ? AND status = 'unprocessed'");
        $stmt_update_points->bind_param("si", $cycle_id, $member_id);
        $stmt_update_points->execute();
        echo "  - Marked " . $stmt_update_points->affected_rows . " new point records as processed.\n";
        $stmt_update_points->close();
        
        $mysqli->commit();
        echo "  - Transaction committed successfully.\n\n";

    } catch (Exception $e) {
        $mysqli->rollback();
        echo "  - ERROR: Transaction failed. " . $e->getMessage() . "\n\n";
    }
}

echo "--- Commission Engine Finished ---";
echo "</pre>";
$mysqli->close();
?>