<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') { exit('Access Denied'); }
$packages_result = $mysqli->query("SELECT id, name FROM packages WHERE is_active = 1 ORDER BY name ASC");
?>

<style>
    .form-wizard { max-width: 600px; margin: 0 auto; }
    .step-header { font-size: 14px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 600; }
    .form-group { margin-bottom: 25px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box; }
    #sale-id-group { display: none; }
    /* This class will be added via JS to make the dropdown LOOK disabled */
    select.locked {
        background-color: #e9ecef; /* Standard disabled color */
        pointer-events: none; /* Prevents clicks */
    }
</style>

<div class="content-header"><h2>Generate Package Activation Codes</h2></div>

<div class="card form-wizard">
    <?php
    if (isset($_SESSION['message'])) {
        $alert_class = ($_SESSION['msg_type'] === 'success') ? 'background-color: #d4edda; color: #155724;' : 'background-color: #f8d7da; color: #721c24;';
        echo '<div class="alert" style="padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; ' . $alert_class . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message'], $_SESSION['msg_type']);
    }
    ?>

    <form action="handle_purchase.php" method="POST">
        <div class="form-group">
            <div class="step-header">Step 1: Select Package</div>
            <select id="package_id" name="package_id" required>
                <option value="">-- Choose a Package --</option>
                <?php while ($package = $packages_result->fetch_assoc()): ?>
                    <option value="<?php echo $package['id']; ?>"><?php echo htmlspecialchars($package['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <div class="step-header">Step 2: Select Account Type</div>
            <select id="account_type" name="account_type" required>
                <option value="Paid Account">Paid Account (Requires Sale ID)</option>
                <option value="FS Account">FS Account (Free Slot)</option>
                <option value="CD Account">CD Account (Credit Deduction)</option>
            </select>
        </div>
        
        <div class="form-group" id="sale-id-group">
            <label for="sale_id">POS Sale ID</label>
            <input type="text" id="sale_id" name="sale_id" placeholder="Enter the Sale ID from the POS receipt">
            <small id="sale-id-validation-msg" style="margin-top: 5px; display: block; height: 1em;"></small>
        </div>

        <div class="form-group">
            <div class="step-header">Step 3: Select Number of Heads</div>
            <!-- This is the dropdown the user SEES -->
            <select id="num_heads_display" required>
                <option value="1">1 Head</option>
                <option value="3">3 Heads</option>
                <option value="7">7 Heads</option>
            </select>
            <!-- This is the hidden input that gets SUBMITTED -->
            <input type="hidden" id="num_heads" name="num_heads" value="1">
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary" style="width: 100%;">Generate Code(s)</button>
        </div>
    </form>
</div>

<!-- =================================================================== -->
<!--               FINAL, DEFINITIVE JAVASCRIPT                          -->
<!-- =================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const accountTypeSelect = document.getElementById('account_type');
    const saleIdGroup = document.getElementById('sale-id-group');
    const saleIdInput = document.getElementById('sale_id');
    const saleIdMsg = document.getElementById('sale-id-validation-msg');
    const numHeadsDisplay = document.getElementById('num_heads_display');
    const numHeadsHidden = document.getElementById('num_heads');
    let debounceTimer;

    function handleAccountTypeChange() {
        if (accountTypeSelect.value === 'Paid Account') {
            saleIdGroup.style.display = 'block';
            saleIdInput.required = true;
            numHeadsDisplay.classList.add('locked');
            validateSaleId(); // Re-validate if needed
        } else {
            saleIdGroup.style.display = 'none';
            saleIdInput.required = false;
            saleIdInput.value = '';
            saleIdMsg.textContent = '';
            numHeadsDisplay.classList.remove('locked');
            numHeadsDisplay.value = '1';
            numHeadsHidden.value = '1';
        }
    }

    function validateSaleId() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const saleId = saleIdInput.value.trim();
            if (accountTypeSelect.value !== 'Paid Account' || !saleId) {
                numHeadsDisplay.classList.add('locked');
                saleIdMsg.textContent = '';
                return;
            }

            saleIdMsg.textContent = 'Checking...';
            saleIdMsg.style.color = '#888';

            fetch(`api_get_sale_details.php?sale_id=${encodeURIComponent(saleId)}`)
                .then(response => response.json())
                .then(data => {
                    saleIdMsg.textContent = data.message;
                    if (data.valid) {
                        saleIdMsg.style.color = 'green';
                        numHeadsDisplay.value = data.heads;
                        numHeadsHidden.value = data.heads;
                        numHeadsDisplay.classList.add('locked');

                    } else {
                        saleIdMsg.style.color = 'red';
                        numHeadsDisplay.classList.add('locked');
                        numHeadsDisplay.value = '1';
                        numHeadsHidden.value = '1';
                    }
                });
        }, 500);
    }
    
    // Syncs the hidden input when manually changing for FS/CD accounts
    numHeadsDisplay.addEventListener('change', function() {
        if (!this.classList.contains('locked')) {
            numHeadsHidden.value = this.value;
        }
    });
    
    accountTypeSelect.addEventListener('change', handleAccountTypeChange);
    saleIdInput.addEventListener('keyup', validateSaleId);
    
    handleAccountTypeChange(); // Initial setup on page load
});
</script>

<?php require_once '../../src/templates/admin_footer.php'; ?>