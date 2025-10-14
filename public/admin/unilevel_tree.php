<?php
session_start();
require_once '../../src/templates/admin_header.php';
require_once '../../src/includes/db_connect.php';
if ($_SESSION['role'] !== 'admin') { exit('Access Denied'); }
?>

<!-- =================================================================== -->
<!--                  NEW CSS FOR UNILEVEL LIST VIEW                     -->
<!-- =================================================================== -->
<style>
    .unilevel-tree {
        list-style: none;
        padding-left: 20px;
    }
    .unilevel-tree li {
        margin-bottom: 10px;
        position: relative;
    }
    .node-content {
        display: flex;
        align-items: center;
        gap: 15px;
        background-color: #fff;
        padding: 10px 15px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #eee;
    }
    .node-level {
        font-size: 12px;
        font-weight: 700;
        background: #eee;
        color: #555;
        padding: 5px 8px;
        border-radius: 5px;
        min-width: 50px;
        text-align: center;
    }
    .node-info {
        font-size: 14px;
    }
    .node-info strong {
        font-size: 16px;
        color: #333;
    }
    .node-info small {
        color: #777;
    }
    .node-directs {
        margin-left: auto; /* Pushes to the right */
        font-size: 14px;
        font-weight: 500;
        color: #888;
    }
</style>

<div class="content-header">
    <h2>Unilevel Tree (Sponsorship View)</h2>
</div>

<div class="card">
    <div class="search-bar" style="max-width: 400px; margin-bottom: 20px;">
        <form action="unilevel_tree.php" method="GET">
            <input type="text" name="username" placeholder="Search by Username to start tree...">
        </form>
    </div>
    
    <div id="unilevel-container">
        <p>Loading unilevel tree...</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('unilevel-container');
    
    // Get starting user ID from URL, or default to Corp Account
    const urlParams = new URLSearchParams(window.location.search);
    let startUserId = urlParams.get('user_id');
    if (!startUserId) {
        // If a username search is performed, we need a way to get the ID.
        // For simplicity, we'll start with the root ID.
        startUserId = <?php echo json_encode(isset($_GET['user_id']) ? (int)$_GET['user_id'] : 20); ?>;
    }

    function loadUnilevel(userId) {
        container.innerHTML = '<p>Loading...</p>';
        fetch(`api_get_unilevel.php?user_id=${userId}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = `<p style="color:red;">${data.error}</p>`;
                } else {
                    container.innerHTML = buildUnilevelHtml(data);
                }
            })
            .catch(err => console.error(err));
    }

    function buildUnilevelHtml(node) {
        // Main container is just the root node's content
        let rootHtml = `
            <div class="node-content">
                <div class="node-level">ROOT</div>
                <div class="node-info">
                    <strong>${node.name}</strong><br>
                    <small>[${node.username}]</small>
                </div>
                <div class="node-directs">${node.direct_count} Directs</div>
            </div>
        `;

        // Recursively build the list for children
        function buildChildrenList(children) {
            if (!children || children.length === 0) {
                return '';
            }
            let listHtml = '<ul class="unilevel-tree">';
            children.forEach(child => {
                listHtml += `
                    <li>
                        <div class="node-content">
                            <div class="node-level">Level ${child.level}</div>
                            <div class="node-info">
                                <strong>${child.name}</strong><br>
                                <small>[${child.username}] - Joined: ${new Date(child.created_at).toLocaleDateString()}</small>
                            </div>
                            <div class="node-directs">${child.direct_count} Directs</div>
                        </div>
                        ${buildChildrenList(child.children)}
                    </li>
                `;
            });
            listHtml += '</ul>';
            return listHtml;
        }

        return rootHtml + buildChildrenList(node.children);
    }

    loadUnilevel(startUserId);
});
</script>

<?php require_once '../../src/templates/admin_footer.php'; ?>