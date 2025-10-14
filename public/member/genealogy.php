<?php
session_start();
require_once '../../src/templates/member_header.php';
require_once '../../src/includes/db_connect.php';
?>

<style>
    /* Main Layout */
    .genealogy-container { text-align: center; overflow-x: auto; padding: 20px; background-color: #f0ebe5; }
    .genealogy-tree { display: inline-flex; flex-direction: column; align-items: center; }
    .genealogy-tree ul { display: flex; position: relative; padding-top: 50px; }
    .genealogy-tree li { display: flex; flex-direction: column; align-items: center; text-align: center; list-style-type: none; position: relative; padding: 0 10px; }
    /* Connector Lines */
    .genealogy-tree li::before, .genealogy-tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid #ccc; width: 50%; height: 50px; }
    .genealogy-tree li::after { right: auto; left: 50%; border-left: 2px solid #ccc; }
    .genealogy-tree li:only-child::after, .genealogy-tree li:only-child::before { display: none; }
    .genealogy-tree li:first-child::before, .genealogy-tree li:last-child::after { border: 0 none; }
    .genealogy-tree li:last-child::before { border-right: 2px solid #ccc; border-radius: 0 5px 0 0; }
    .genealogy-tree li:first-child::after { border-radius: 5px 0 0 0; }
    .tree-node-wrapper::before { content: ''; position: absolute; top: -50px; left: 50%; transform: translateX(-50%); border-left: 2px solid #ccc; width: 0; height: 50px; }
    .genealogy-tree > div > .tree-node-wrapper::before { display: none; }
    .tree-node-wrapper { position: relative; }
    /* Node Styling */
    .tree-node { display: flex; flex-direction: column; align-items: center; justify-content: space-between; width: 200px; min-height: 180px; background-color: #4a4a6e; border-radius: 12px; padding: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); color: white; text-decoration: none; cursor: pointer; transition: transform 0.2s; box-sizing: border-box; }
    .tree-node:hover { transform: translateY(-5px); }
    .node-icon { font-size: 32px; color: rgba(255,255,255,0.8); margin-bottom: 8px; }
    .node-details { text-align: center; } .node-name { font-weight: 600; font-size: 16px; }
    .node-username { font-size: 14px; color: #ccc; margin-bottom: 10px; }
    .node-tags { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; margin-bottom: 10px; }
    .tag { padding: 3px 8px; font-size: 10px; font-weight: 600; border-radius: 10px; text-transform: uppercase; }
    .tag-paid { background-color: #2980b9; } .tag-cd { background-color: #e74c3c; }
    .tag-fs { background-color: #f39c12; } .tag-active { background-color: #2ecc71; }
    .node-points { font-size: 12px; font-weight: 500; }
    .node-button { display: inline-block; padding: 8px 15px; border-radius: 20px; font-weight: 600; font-size: 12px; text-decoration: none; }
    .btn-register { background-color: #2ecc71; color: white; }
    .node-vacant { justify-content: center; } .node-vacant .node-icon { color: rgba(255,255,255,0.2); }
    .node-expand { position: absolute; bottom: -12px; left: 50%; transform: translateX(-50%); background-color: white; border: 1px solid #ccc; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10; }
    .progress-bar-container { background-color: #333; border-radius: 5px; height: 8px; width: 80%; margin: 5px auto 10px auto; overflow: hidden; border: 1px solid #777; }
    .progress-bar { height: 100%; background-color: #2ecc71; width: 0%; border-radius: 5px; transition: width 0.5s ease-in-out; }
    .node-icon[title="Go Up"] { animation: bounce 2s infinite; }
    @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-8px);} 60% {transform: translateY(-4px);} }
</style>

<div class="content-header"><h2>My Genealogy</h2></div>
<div class="card" style="background-color: #f0ebe5; border: none; padding: 0;">
    <div id="genealogy-container" class="genealogy-container"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const config = {
        initialUserId: <?php echo json_encode($_SESSION['user_id']); ?>,
        loggedInUserId: <?php echo json_encode($_SESSION['user_id']); ?>,
        apiUrl: 'api_get_member_genealogy.php',
        registerUrl: '../register.php'
    };
    const container = document.getElementById('genealogy-container');
    let currentTopNodeUplineId = null;

    window.addEventListener('popstate', (event) => {
        const userIdToLoad = (event.state && event.state.userId) ? event.state.userId : config.initialUserId;
        loadTree(userIdToLoad, false);
    });

    window.loadTree = function(userId, pushToHistory = true) {
        // Security check for member view
        if (userId < config.loggedInUserId) { userId = config.loggedInUserId; }

        container.innerHTML = '<p>Loading...</p>';
        if (pushToHistory) {
            history.pushState({ userId: userId }, '', `genealogy.php?user_id=${userId}`);
        }
        fetch(`${config.apiUrl}?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (data && !data.error) {
                    currentTopNodeUplineId = data.upline_id || null;
                    container.innerHTML = buildTreeHtml(data, 1);
                } else {
                    container.innerHTML = `<div class="card"><p style="color: red;">Failed to load tree.</p></div>`;
                }
            })
            .catch(err => console.error(err));
    };

    function buildTreeHtml(node, level) {
        if (!node) return '<li></li>';
        let html = '<li>';
        let nodeContent;
        const sponsorId = config.loggedInUserId;

        if (node.id) {
            let clickAction = `loadTree(${node.id})`;
            if (level === 1 && currentTopNodeUplineId && currentTopNodeUplineId >= config.loggedInUserId) {
                clickAction = `loadTree(${currentTopNodeUplineId})`;
            }

            let accountTag = '';
            let accountTypeUpper = node.account_type ? node.account_type.toUpperCase() : '';
            if (accountTypeUpper === 'PAID ACCOUNT' || accountTypeUpper === 'STANDARD') { accountTag = '<span class="tag tag-paid">Paid Account</span>'; } 
            else if (accountTypeUpper === 'CD ACCOUNT') { accountTag = '<span class="tag tag-cd">Credit Deduction</span>'; } 
            else if (accountTypeUpper === 'FS ACCOUNT') { accountTag = '<span class="tag tag-fs">Free Slot</span>'; }

            let progressBarHtml = '';
            if (node.account_type === 'CD Account' && node.package_price > 0) {
                const amount_paid = parseFloat(node.package_price) - parseFloat(node.debt_balance);
                const percent_paid = Math.min(100, (amount_paid / parseFloat(node.package_price)) * 100);
                progressBarHtml = `
                    <div class="progress-bar-container" title="₱${amount_paid.toFixed(2)} / ₱${parseFloat(node.package_price).toFixed(2)} Paid">
                        <div class="progress-bar" style="width: ${percent_paid}%;"></div>
                    </div>
                `;
            }

            nodeContent = `
                <div class="tree-node-wrapper">
                    <a class="tree-node" onclick="${clickAction}">
                        ${(level === 1 && currentTopNodeUplineId && currentTopNodeUplineId >= config.loggedInUserId) ? '<div class="node-icon" title="Go Up"><i class="fa fa-arrow-up"></i></div>' : '<div class="node-icon"><i class="fa-solid fa-user"></i></div>'}
                        <div class="node-details">
                            <div class="node-name">${node.name}</div>
                            <div class="node-username">[${node.username || 'N/A'}]</div>
                        </div>
                        <div class="node-tags">${accountTag}${node.is_active ? '<span class="tag tag-active">Active</span>' : ''}</div>
                        ${progressBarHtml}
                        <div class="node-points">${node.left_points} : LEFT - RIGHT : ${node.right_points}</div>
                    </a>
                    ${(node.has_children && level === 3) ? `<button class="node-expand" onclick="loadTree(${node.id})"><i class="fa-solid fa-chevron-down"></i></button>` : ''}
                </div>
            `;
        } else {
            nodeContent = `<div class="tree-node-wrapper"><a href="${config.registerUrl}?sponsor_id=${sponsorId}&upline_id=${node.upline_id}&pos=${node.position}" class="tree-node node-vacant" target="_blank"><div class="node-icon"><i class="fa-solid fa-plus"></i></div><span class="node-button btn-register">REGISTER HERE</span></a></div>`;
        }
        html += nodeContent;

        if (level < 3 && node.id) {
            let leftChild = node.children ? node.children.find(c => c.position === 'L') : undefined;
            let rightChild = node.children ? node.children.find(c => c.position === 'R') : undefined;
            let leftChildHtml = buildTreeHtml(leftChild || { sponsor_id: sponsorId, upline_id: node.id, position: 'L' }, level + 1);
            let rightChildHtml = buildTreeHtml(rightChild || { sponsor_id: sponsorId, upline_id: node.id, position: 'R' }, level + 1);
            html += `<ul>${leftChildHtml}${rightChildHtml}</ul>`;
        }
        
        html += '</li>';
        return (level === 1) ? `<div class="genealogy-tree">${html}</div>` : html;
    }
    
    history.replaceState({ userId: config.initialUserId }, '', `genealogy.php?user_id=${config.initialUserId}`);
    loadTree(config.initialUserId, false);
});
</script>

<?php require_once '../../src/templates/member_footer.php'; ?>