<?php
session_start();
require_once '../src/includes/db_connect.php';

// --- PHP LOGIC TO GET AND VALIDATE REFERRAL DATA ---
$sponsor_id = isset($_GET['sponsor_id']) ? (int)$_GET['sponsor_id'] : 0;
$upline_id = isset($_GET['upline_id']) ? (int)$_GET['upline_id'] : 0;
$position_get = isset($_GET['pos']) ? $_GET['pos'] : '';
$error_message = '';
$sponsor_name = 'N/A';
if ($sponsor_id <= 0 || $upline_id <= 0 || ($position_get !== 'L' && $position_get !== 'R')) {
    $error_message = "Invalid or expired referral link. Please ask your sponsor for a new one.";
} else {
    $stmt_check = $mysqli->prepare("SELECT id FROM genealogy_tree WHERE upline_id = ? AND position = ?");
    $stmt_check->bind_param("is", $upline_id, $position_get);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $error_message = "This position in the network has already been filled. Please ask your sponsor for a new link.";
    }
    $stmt_check->close();
    if(empty($error_message)) {
        $stmt_sponsor = $mysqli->prepare("SELECT name FROM users WHERE id = ?");
        $stmt_sponsor->bind_param("i", $sponsor_id);
        $stmt_sponsor->execute();
        $result = $stmt_sponsor->get_result();
        if ($row = $result->fetch_assoc()) {
            $sponsor_name = $row['name'];
        }
        $stmt_sponsor->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMG Member Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/public_style.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        #tribute-grace-section { border: 1px solid #27ae60; background: #f0fff8; padding: 20px; border-radius: 8px; margin-top: 20px; }
        #tg-eligibility-status { margin-top: 15px; font-weight: 600; text-align: center; padding: 10px; border-radius: 5px; }
        .tg-eligible { color: #155724; background-color: #d4edda; }
        .tg-ineligible { color: #721c24; background-color: #f8d7da; }
        .beneficiary-entry { border: 1px dashed #ccc; padding: 15px; margin-top: 15px; border-radius: 5px; position: relative; }
        .beneficiary-entry h5 { margin-top: 0; }
        .beneficiary-entry .remove-ben-btn { position: absolute; top: 10px; right: 10px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-weight: bold; line-height: 25px; text-align: center; }
        .tg-fieldset { border: 1px solid #ccc; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .tg-fieldset legend { font-weight: 600; padding: 0 10px; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Member Registration</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php else: ?>
            <div class="alert alert-info" style="margin-bottom: 20px;">Sponsored by: <strong><?php echo htmlspecialchars($sponsor_name); ?></strong></div>

            <div id="step-1">
                <h4>Step 1: Validate Activation Code</h4>
                <div id="step-1-error" class="alert alert-danger" style="display:none;"></div>
                <div class="form-group">
                    <label for="activation_code">Activation Code</label>
                    <input type="text" id="activation_code" placeholder="Enter code or use scanner" required>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="button" id="start-scanner-btn" style="flex: 1; padding: 10px; background: #555; color: white; border: none; border-radius: 5px; cursor: pointer;"><i class="fa-solid fa-camera"></i> Scan QR</button>
                        <button type="button" id="upload-qr-btn" style="flex: 1; padding: 10px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;"><i class="fa-solid fa-upload"></i> Upload QR</button>
                    </div>
                    <input type="file" id="qr-input-file" accept="image/*" style="display: none;">
                    <div id="qr-reader" style="width: 100%; max-width: 500px; margin-top: 15px; border-radius: 8px; overflow: hidden; display: none;"></div>
                </div>
                <button type="button" id="validate-code-btn" class="btn">Next Step</button>
            </div>

            <form action="handle_registration.php" method="POST" id="step-2" style="display:none;">
                <input type="hidden" name="sponsor_id" value="<?php echo $sponsor_id; ?>">
                <input type="hidden" name="upline_id" value="<?php echo $upline_id; ?>">
                <input type="hidden" name="position" value="<?php echo $position_get; ?>">
                <input type="hidden" id="final_activation_code" name="activation_code" value="">

                <h4>Step 2: Personal Information</h4>
                <div class="form-group">
                    <label for="name">Full Name (First Name Last Name)</label>
                    <input type="text" id="name" name="name" required placeholder="e.g., Juan Dela Cruz">
                    <div id="tg-eligibility-status" style="display:none;"></div>
                </div>

                <div id="tribute-grace-section" style="display:none;">
                    <fieldset class="tg-fieldset">
                        <legend>Tribute Grace Details</legend>
                        <p><small>Please complete your information for the Tribute Grace policy.</small></p>
                        <div class="form-group"><label for="middleName">Middle Name (Optional)</label><input type="text" id="middleName" name="tg_middleName"></div>
                        <div class="form-group"><label for="suffix">Suffix (e.g. Jr., III) (Optional)</label><input type="text" id="suffix" name="tg_suffix"></div>
                        <div class="form-group"><label for="birthdate">Birthdate</label><input type="date" id="birthdate" name="tg_birthdate" required></div>
                        <div class="form-group"><label for="gender">Gender</label><select id="gender" name="tg_gender" required><option value="">-- Select --</option><option value="male">Male</option><option value="female">Female</option></select></div>
                        <div class="form-group"><label for="maritalStatus">Marital Status</label><select id="maritalStatus" name="tg_maritalStatus" required><option value="">-- Select --</option><option value="single">Single</option><option value="married">Married</option><option value="widowed">Widowed</option><option value="divorced">Divorced</option></select></div>
                        <div class="form-group"><label for="contactNumber1">Contact Number</label><input type="tel" id="contactNumber1" name="tg_contactNumber1" required pattern="09[0-9]{9}" placeholder="09xxxxxxxxx"></div>
                    </fieldset>
                    <fieldset class="tg-fieldset">
                        <legend>Residential Address</legend>
                        <div class="form-group"><label>Province</label><select id="province" name="tg_province" required><option value="">-- Select Province --</option></select></div>
                        <div class="form-group"><label>City/Municipality</label><select id="cityMunicipal" name="tg_cityMunicipal" required disabled><option value="">-- Select City/Municipality --</option></select></div>
                        <div class="form-group"><label>Barangay</label><select id="barangay" name="tg_barangay" required disabled><option value="">-- Select Barangay --</option></select></div>
                        <div class="form-group"><label>Street</label><input type="text" id="street" name="tg_street" required></div>
                        <div class="form-group"><label>Lot/Block/House No.</label><input type="text" id="lotBlock" name="tg_lotBlock" required></div>
                    </fieldset>
                    <fieldset class="tg-fieldset">
                        <legend>Beneficiaries (Up to 3)</legend>
                        <div id="beneficiary-fields"></div>
                        <button type="button" id="add-beneficiary-btn" class="btn" style="background:#555; width:auto; padding: 8px 15px; margin-top: 10px;">+ Add Beneficiary</button>
                    </fieldset>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label for="username">RMG Username</label>
                    <input type="text" id="username" name="username" required>
                    <div id="username-validation-msg" style="margin-top: 5px; font-size: 14px; font-weight: 500;"></div>
                </div>
                <div class="form-group">
                    <label for="email">RMG Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="password">Password (min. 8 characters)</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" class="btn">Complete Registration</button>
                <button type="button" id="back-btn" class="btn" style="background: #777; margin-top: 10px;">Go Back</button>
            </form>
        <?php endif; ?>
    </div>
</body>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const step1Div = document.getElementById('step-1');
    const step2Form = document.getElementById('step-2');
    const activationCodeInput = document.getElementById('activation_code');
    const validateCodeBtn = document.getElementById('validate-code-btn');
    const backBtn = document.getElementById('back-btn');
    const step1ErrorDiv = document.getElementById('step-1-error');
    const startScannerBtn = document.getElementById('start-scanner-btn');
    const uploadQrBtn = document.getElementById('upload-qr-btn');
    const qrFileInput = document.getElementById('qr-input-file');
    const qrReaderDiv = document.getElementById('qr-reader');
    let html5QrCode = null;

    const nameInput = document.getElementById('name');
    const tgSection = document.getElementById('tribute-grace-section');
    const tgStatusDiv = document.getElementById('tg-eligibility-status');
    const beneficiaryFieldsDiv = document.getElementById('beneficiary-fields');
    const addBeneficiaryBtn = document.getElementById('add-beneficiary-btn');
    let tgEligibilityDebounce;

    const usernameInput = document.getElementById('username');
    const usernameValidationMsg = document.getElementById('username-validation-msg');
    let usernameDebounceTimer;

    // This is the function to toggle the 'required' attribute on inputs
    function setTGFieldsRequired(isRequired) {
        const fields = tgSection.querySelectorAll('input, select');
        fields.forEach(field => {
            // Check if it's not a button or other non-input element
            if (field.type !== 'button') {
                field.required = isRequired;
            }
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        activationCodeInput.value = decodedText;
        if (html5QrCode && html5QrCode.isScanning) {
            html5QrCode.stop().then(() => {
                qrReaderDiv.style.display = 'none';
                startScannerBtn.style.display = 'block';
                uploadQrBtn.style.display = 'block';
            }).catch(err => console.error("Scanner stop failed:", err));
        }
        alert(`Code "${decodedText}" has been entered.`);
    }

    function onScanFailure(error) { /* Quiet failure */ }

    startScannerBtn.addEventListener('click', () => {
        startScannerBtn.style.display = 'none';
        uploadQrBtn.style.display = 'none';
        qrReaderDiv.style.display = 'block';
        if (!html5QrCode) { html5QrCode = new Html5Qrcode("qr-reader"); }
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
            .catch(err => {
                alert("Error starting QR Scanner. Please grant camera permission.");
                startScannerBtn.style.display = 'block';
                uploadQrBtn.style.display = 'block';
                qrReaderDiv.style.display = 'none';
            });
    });

    uploadQrBtn.addEventListener('click', () => { qrFileInput.click(); });

    qrFileInput.addEventListener('change', e => {
        if (e.target.files.length === 0) return;
        const imageFile = e.target.files[0];
        const fileScanner = new Html5Qrcode("qr-reader");
        fileScanner.scanFile(imageFile, true)
            .then(onScanSuccess)
            .catch(err => { alert(`Error scanning file. Please use a clear QR code image.`); });
    });

    validateCodeBtn.addEventListener('click', () => {
        const code = activationCodeInput.value.trim();
        if (!code) {
            step1ErrorDiv.textContent = 'Please enter an activation code.';
            step1ErrorDiv.style.display = 'block';
            return;
        }
        validateCodeBtn.textContent = 'Validating...';
        validateCodeBtn.disabled = true;
        const formData = new FormData();
        formData.append('activation_code', code);
        fetch('api_validate_code.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.valid) {
                    document.getElementById('final_activation_code').value = code;
                    step1Div.style.display = 'none';
                    step2Form.style.display = 'block';
                } else {
                    step1ErrorDiv.textContent = data.message;
                    step1ErrorDiv.style.display = 'block';
                }
            })
            .catch(error => {
                step1ErrorDiv.textContent = 'An unexpected error occurred.';
                step1ErrorDiv.style.display = 'block';
            })
            .finally(() => {
                validateCodeBtn.textContent = 'Next Step';
                validateCodeBtn.disabled = false;
            });
    });

    backBtn.addEventListener('click', () => {
        step2Form.style.display = 'none';
        step1Div.style.display = 'block';
        step1ErrorDiv.style.display = 'none';
    });

    nameInput.addEventListener('keyup', () => {
        clearTimeout(tgEligibilityDebounce);
        tgEligibilityDebounce = setTimeout(() => {
            const fullName = nameInput.value.trim();
            if (fullName.split(' ').length < 2) {
                tgSection.style.display = 'none';
                tgStatusDiv.style.display = 'none';
                setTGFieldsRequired(false); // --- CRITICAL FIX ---
                return;
            }
            const nameParts = fullName.split(' ');
            const firstName = nameParts[0];
            const lastName = nameParts[nameParts.length - 1];
            tgStatusDiv.textContent = 'Checking eligibility...';
            tgStatusDiv.className = '';
            tgStatusDiv.style.display = 'block';
            const formData = new FormData();
            formData.append('firstName', firstName);
            formData.append('lastName', lastName);
            fetch('api_check_tg_eligibility.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    tgStatusDiv.textContent = data.reason;
                    if (data.eligible) {
                        tgStatusDiv.className = 'tg-eligible';
                        tgSection.style.display = 'block';
                        setTGFieldsRequired(true); // --- CRITICAL FIX ---
                    } else {
                        tgStatusDiv.className = 'tg-ineligible';
                        tgSection.style.display = 'none';
                        beneficiaryFieldsDiv.innerHTML = '';
                        setTGFieldsRequired(false); // --- CRITICAL FIX ---
                    }
                });
        }, 800);
    });
    
    let beneficiaryIdCounter = 0;
    addBeneficiaryBtn.addEventListener('click', () => {
        const currentCount = beneficiaryFieldsDiv.getElementsByClassName('beneficiary-entry').length;
        if (currentCount >= 3) {
            alert('You can add a maximum of 3 beneficiaries.');
            return;
        }
        beneficiaryIdCounter++;
        const uniqueId = `ben-entry-${beneficiaryIdCounter}`;
        const beneficiaryHTML = `
            <div class="beneficiary-entry" id="${uniqueId}">
                <h5>Beneficiary #${currentCount + 1}</h5>
                <button type="button" class="remove-ben-btn" onclick="document.getElementById('${uniqueId}').remove();">&times;</button>
                <div class="form-group"><label>First Name</label><input type="text" name="beneficiary[${beneficiaryIdCounter}][firstName]" required></div>
                <div class="form-group"><label>Last Name</label><input type="text" name="beneficiary[${beneficiaryIdCounter}][lastName]" required></div>
                <div class="form-group"><label>Relation</label><input type="text" name="beneficiary[${beneficiaryIdCounter}][relation]" required></div>
                <div class="form-group"><label>Birthdate</label><input type="date" name="beneficiary[${beneficiaryIdCounter}][birthdate]" required></div>
            </div>
        `;
        beneficiaryFieldsDiv.insertAdjacentHTML('beforeend', beneficiaryHTML);
    });

    usernameInput.addEventListener('keyup', () => {
        clearTimeout(usernameDebounceTimer);
        const username = usernameInput.value;
        if (username.length < 4) {
            usernameValidationMsg.textContent = 'Username must be at least 4 characters.';
            usernameValidationMsg.style.color = 'red';
            return;
        }
        usernameDebounceTimer = setTimeout(() => {
            const formData = new FormData();
            formData.append('username', username);
            fetch('api_check_username.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        usernameValidationMsg.textContent = '✓ Username is available!';
                        usernameValidationMsg.style.color = 'green';
                    } else {
                        usernameValidationMsg.textContent = '✗ Username is already taken.';
                        usernameValidationMsg.style.color = 'red';
                    }
                });
        }, 500);
    });

    function setupAddressDropdowns(prefix, provinceElId, cityElId, barangayElId) {
        const provinceEl = document.getElementById(provinceElId);
        const cityEl = document.getElementById(cityElId);
        const barangayEl = document.getElementById(barangayElId);
        if (!provinceEl || !cityEl || !barangayEl) return;
        
        const populateDropdown = (el, data, valKey, textKey, prompt) => {
            el.innerHTML = `<option value="">${prompt}</option>`;
            if(data) data.forEach(item => { el.innerHTML += `<option value="${item[valKey]}">${item[textKey]}</option>`; });
        };

        fetch('get_locations.php?type=provinces')
            .then(res => res.json())
            .then(data => populateDropdown(provinceEl, data, 'provCode', 'provDesc', '-- Select Province --'));
        
        provinceEl.addEventListener('change', () => {
            cityEl.innerHTML = '<option value="">-- Select City --</option>';
            barangayEl.innerHTML = '<option value="">-- Select Barangay --</option>';
            cityEl.disabled = true; barangayEl.disabled = true;
            if (!provinceEl.value) return;
            cityEl.disabled = false;
            fetch(`get_locations.php?type=cities&prov_code=${provinceEl.value}`)
                .then(res => res.json())
                .then(data => populateDropdown(cityEl, data, 'citymunCode', 'citymunDesc', '-- Select City --'));
        });

        cityEl.addEventListener('change', () => {
            barangayEl.innerHTML = '<option value="">-- Select Barangay --</option>';
            barangayEl.disabled = true;
            if (!cityEl.value) return;
            barangayEl.disabled = false;
            fetch(`get_locations.php?type=barangays&city_code=${cityEl.value}`)
                .then(res => res.json())
                .then(data => populateDropdown(barangayEl, data, 'brgyCode', 'brgyDesc', '-- Select Barangay --'));
        });
    }
    
    setupAddressDropdowns('tg_', 'province', 'cityMunicipal', 'barangay');
});
</script>
</html>