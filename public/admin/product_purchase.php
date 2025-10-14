<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') { exit('Access Denied'); }
$products_result = $mysqli->query("SELECT id, name, member_price, points_value, barcode FROM products WHERE stock_quantity > 0 ORDER BY name ASC");
$products = [];
while ($row = $products_result->fetch_assoc()) { $products[] = $row; }
?>

<!-- =================================================================== -->
<!--                  FINAL, STABLE & CORRECT POS CSS                    -->
<!-- =================================================================== -->
<style>
    .pos-container { display: flex; gap: 30px; align-items: flex-start; }
    .pos-left { flex: 3; } .pos-right { flex: 2; position: sticky; top: 20px; }
    
    .tab-buttons { display: flex; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
    .tab-button { padding: 10px 20px; cursor: pointer; border: none; background: none; font-weight: 500; color: #888; position: relative; }
    .tab-button.active { color: var(--primary-color); font-weight: 600; }
    .tab-button.active::after { content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 2px; background: var(--primary-color); }
    
    .tab-content { display: none; } .tab-content.active { display: block; }

    .search-results-wrapper { position: relative; }
    #search-results {
        position: absolute; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; width: 100%;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1); z-index: 100;
    }
    .search-result-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
    .search-result-item:last-child { border-bottom: none; }
    .search-result-item:hover { background-color: #f0f0f0; }

    /* --- FINAL CART CSS --- */
    .cart-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .cart-table th, .cart-table td { padding: 12px 8px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .cart-table th { text-align: left; font-weight: 600; color: #888; font-size: 14px; }
    .cart-table .item-details { font-weight: 600; }
    .cart-table .item-qty input { width: 60px; text-align: center; padding: 5px; border-radius: 5px; border: 1px solid #ccc; }
    .cart-table .item-total { font-weight: 600; text-align: right; }
    .cart-table .item-remove button { background: none; border: none; color: #e74c3c; cursor: pointer; font-size: 18px; }
    
    /* Other styles */
    .summary-card { background: #f9f9f9; padding: 20px; border-radius: 8px; }
    .summary-line { display: flex; justify-content: space-between; padding: 8px 0; font-size: 16px; }
    .summary-line.total { font-size: 20px; font-weight: 700; border-top: 2px solid #ccc; margin-top: 10px; }
    .member-verification { margin-top: 15px; font-weight: 600; text-align: center; padding: 8px; border-radius: 5px; }
    .member-valid { color: #155724; background-color: #d4edda; }
    .member-invalid { color: #721c24; background-color: #f8d7da; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
    .form-group input, .form-group select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border-color); box-sizing: border-box; }
</style>

<div class="content-header"><h2>Point of Sale</h2></div>

<div class="pos-container">
    <form action="handle_product_purchase.php" method="POST" id="purchase-form" target="_blank" style="display: contents;">
        <!-- Left Side -->
        <div class="pos-left card">
            <div class="tab-buttons">
                <button type="button" class="tab-button active" data-tab="direct-sale">Direct Sale</button>
                <button type="button" class="tab-button" data-tab="order-code">Order Code</button>
                <button type="button" class="tab-button" data-tab="gc-code">Gift Certificate</button>
            </div>

            <div id="direct-sale" class="tab-content active">
                <div class="form-group">
                    <label for="product-search">Search Product / Scan Barcode</label>
                    <div class="search-results-wrapper">
                        <input type="text" id="product-search" placeholder="Start typing product name...">
                        <div id="search-results"></div>
                    </div>
                </div>
                <hr>
                <h4>Cart</h4>
                <div id="cart-container"><p style="text-align:center; color:#888;">Cart is empty.</p></div>
            </div>
            <div id="order-code" class="tab-content"><p>Feature coming soon.</p></div>
            <div id="gc-code" class="tab-content"><p>Feature coming soon.</p></div>
        </div>

        <!-- Right Side -->
        <div class="pos-right">
            <div class="card summary-card">
                <div class="form-group">
                    <label for="member-id">Customer</label>
                    <input type="number" id="member-id" name="member_id" placeholder="Enter Member ID..." required>
                    <div id="member-verification" style="display:none;"></div>
                </div>
                <hr>
                <h4>Summary</h4>
                <div id="summary">
                    <div class="summary-line"><span>Subtotal:</span><span id="subtotal-amount">₱0.00</span></div>
                    <div class="summary-line"><span>VAT (12%):</span><span id="vat-amount">₱0.00</span></div>
                    <div class="summary-line total"><span>Total:</span><span id="total-amount">₱0.00</span></div>
                    <div class="summary-line"><span>Points:</span><span id="total-points">0</span></div>
                </div>
                <hr>
                <div class="form-group">
                    <label for="payment_method">Payment</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="Cash">Cash</option>
                        <option value="E-Wallet">Deduct from Member's E-Wallet</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
                <div id="tender-section" class="form-group">
                    <label for="cash-tendered">Cash Tendered</label>
                    <input type="number" id="cash-tendered" name="cash_tendered" placeholder="0.00">
                    <div class="summary-line" style="font-size:16px;"><span>Change:</span><span id="change-due">₱0.00</span></div>
                </div>
                <button type="submit" id="process-sale-btn" class="btn btn-primary" style="width:100%; margin-top:20px;">Process Sale & Print Receipt</button>
            </div>
        </div>
    </form>
</div>


<!-- =================================================================== -->
<!--                FINAL, COMPLETE, & CORRECTED SCRIPT                  -->
<!-- =================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productsData = <?php echo json_encode($products); ?>;
    let cart = [];
    let grandTotal = 0;
    const VAT_RATE = 0.12;

    const purchaseForm = document.getElementById('purchase-form');
    const cartContainer = document.getElementById('cart-container');
    const productSearchInput = document.getElementById('product-search');
    const searchResultsContainer = document.getElementById('search-results');
    const memberIdInput = document.getElementById('member-id');
    const memberVerificationDiv = document.getElementById('member-verification');
    const paymentMethodSelect = document.getElementById('payment_method');
    const tenderSection = document.getElementById('tender-section');
    const cashTenderedInput = document.getElementById('cash-tendered');
    const changeDueSpan = document.getElementById('change-due');

    function renderCart() {
        if (cart.length === 0) {
            cartContainer.innerHTML = '<p style="text-align:center; color:#888;">Cart is empty.</p>';
        } else {
            let tableHTML = '<table class="cart-table"><thead><tr><th>Product</th><th>Qty</th><th style="text-align:right;">Total</th><th></th></tr></thead><tbody>';
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.qty;
                // THIS IS THE CRITICAL FIX: The hidden inputs are now part of the table row's data
                tableHTML += `
                    <tr>
                        <td>
                            <div class="item-details">${item.name}</div>
                            <small style="color:#777;">₱${item.price.toFixed(2)}</small>
                            <input type="hidden" name="product_ids[]" value="${item.id}">
                        </td>
                        <td class="item-qty">
                            <input type="number" name="quantities[]" class="item-qty-input" data-index="${index}" value="${item.qty}" min="1">
                        </td>
                        <td class="item-total">₱${itemTotal.toFixed(2)}</td>
                        <td class="item-remove">
                            <button type="button" class="item-remove-btn" data-index="${index}">&times;</button>
                        </td>
                    </tr>
                `;
            });
            tableHTML += '</tbody></table>';
            cartContainer.innerHTML = tableHTML;
        }
        updateSummary();
    }

    function addToCart(productId) {
        const product = productsData.find(p => p.id == productId);
        if (!product) return;
        const existingItem = cart.find(item => item.id == productId);
        if (existingItem) {
            existingItem.qty = parseInt(existingItem.qty) + 1;
        } else {
            cart.push({ id: product.id, name: product.name, price: parseFloat(product.member_price), points: parseInt(product.points_value), qty: 1 });
        }
        renderCart();
    }
    
    function updateSummary() {
        let subtotal = 0, totalPoints = 0;
        cart.forEach(item => {
            subtotal += item.price * item.qty;
            totalPoints += item.points * item.qty;
        });
        const vatAmount = subtotal - (subtotal / (1 + VAT_RATE));
        grandTotal = subtotal;
        document.getElementById('subtotal-amount').textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById('vat-amount').textContent = `₱${vatAmount.toFixed(2)}`;
        document.getElementById('total-amount').textContent = `₱${grandTotal.toFixed(2)}`;
        document.getElementById('total-points').textContent = totalPoints;
        updateChange();
    }
    
    function updateChange() {
        const cashTendered = parseFloat(cashTenderedInput.value) || 0;
        const change = cashTendered - grandTotal;
        changeDueSpan.textContent = `₱${change >= 0 ? change.toFixed(2) : '0.00'}`;
    }

    productSearchInput.addEventListener('keyup', (e) => {
        searchResultsContainer.innerHTML = '';
        const query = e.target.value.toLowerCase();
        if (query.length < 2) return;
        const results = productsData.filter(p => p.name.toLowerCase().includes(query));
        results.slice(0, 5).forEach(product => {
            const itemEl = document.createElement('div');
            itemEl.className = 'search-result-item';
            itemEl.textContent = product.name;
            itemEl.onclick = () => {
                addToCart(product.id);
                productSearchInput.value = '';
                searchResultsContainer.innerHTML = '';
            };
            searchResultsContainer.appendChild(itemEl);
        });
    });

    document.addEventListener('click', (e) => { if (!e.target.closest('.search-results-wrapper')) { searchResultsContainer.innerHTML = ''; } });
    
    cartContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('item-remove-btn')) {
            cart.splice(e.target.dataset.index, 1);
            renderCart();
        }
    });
    cartContainer.addEventListener('change', (e) => {
        if (e.target.classList.contains('item-qty-input')) {
            cart[e.target.dataset.index].qty = parseInt(e.target.value);
            renderCart();
        }
    });
    
    let debounceTimer;
    memberIdInput.addEventListener('keyup', () => {
        clearTimeout(debounceTimer);
        memberVerificationDiv.style.display = 'none';
        debounceTimer = setTimeout(() => {
            const memberId = memberIdInput.value;
            if (!memberId) { memberVerificationDiv.innerHTML = ''; return; }
            fetch(`api_check_member.php?id=${memberId}`)
                .then(res => res.json())
                .then(data => {
                    memberVerificationDiv.style.display = 'block';
                    if (data.exists) {
                        memberVerificationDiv.className = 'member-verification member-valid';
                        memberVerificationDiv.textContent = `✓ ${data.name}`;
                    } else {
                        memberVerificationDiv.className = 'member-verification member-invalid';
                        memberVerificationDiv.textContent = '✗ Member not found';
                    }
                });
        }, 500);
    });

    cashTenderedInput.addEventListener('keyup', updateChange);
    paymentMethodSelect.addEventListener('change', () => { tenderSection.style.display = paymentMethodSelect.value === 'Cash' ? 'block' : 'none'; updateChange(); });

    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.tab-button, .tab-content').forEach(el => el.classList.remove('active'));
            button.classList.add('active');
            document.getElementById(button.dataset.tab).classList.add('active');
        });
    });

    purchaseForm.addEventListener('submit', function(event) {
        const processSaleBtn = this.querySelector('button[type="submit"]');
        if (!memberIdInput.value || !document.getElementById('member-verification').classList.contains('member-valid')) {
            alert('Please enter a valid and verified Member ID.');
            event.preventDefault(); return;
        }
        if (cart.length === 0) {
            alert('The cart is empty.');
            event.preventDefault(); return;
        }
        processSaleBtn.textContent = 'Processing...';
        processSaleBtn.disabled = true;
        setTimeout(() => {
            processSaleBtn.textContent = 'Process Sale & Print Receipt';
            processSaleBtn.disabled = false;
        }, 4000);
    });
    
    renderCart();
});
</script>