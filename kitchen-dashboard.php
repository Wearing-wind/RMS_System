<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kitchen Dashboard - QR Cafe</title>
    <link rel="stylesheet" href="css/spatial.css">
    <style>
        body { background: var(--bg-canvas-alt); }
    </style>
</head>
<body class="kitchen-body">

    <!-- Header Bar -->
    <header class="kitchen-header">
        <div class="kitchen-title" style="font-family: var(--font-sans); font-size: 1.3rem;">
            <span>👨‍🍳</span> Cafe Kitchen Control
        </div>

        <div class="kitchen-stats">
            <div class="stat-pill new" style="background: rgba(244, 63, 94, 0.15); border: 1px solid var(--danger);">
                <span class="num" id="statNewCount" style="color: #fda4af;">0</span>
                <span class="label">New</span>
            </div>
            <div class="stat-pill prep" style="background: rgba(245, 158, 11, 0.15); border: 1px solid var(--warning);">
                <span class="num" id="statPrepCount" style="color: #fde68a;">0</span>
                <span class="label">Prep</span>
            </div>
            <div class="stat-pill ready" style="background: rgba(34, 197, 94, 0.15); border: 1px solid var(--success);">
                <span class="num" id="statReadyCount" style="color: #4ade80;">0</span>
                <span class="label">Ready</span>
            </div>
        </div>

        <button class="cart-btn" style="background: rgba(255,255,255,0.08); font-size: 0.85rem;" onclick="loadKitchenData()">
            🔄 Refresh
        </button>
    </header>

    <!-- Waiter Calls Section -->
    <div class="container" style="max-width: 1200px; margin-top: 16px; margin-bottom: 16px;">
        <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--text-secondary); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
            <span>🔔</span> Table Waiter Calls
        </h3>
        <div id="waiterCallsGrid" style="display: flex; gap: 10px; overflow-x: auto; white-space: nowrap; padding-bottom: 6px; scrollbar-width: none;">
            <div style="color: var(--text-muted); font-size: 0.85rem;">No active waiter calls</div>
        </div>
    </div>

    <!-- Active / Completed / Rejected Tabs (HORIZONTAL ROW FOR MOBILE LAYOUT) -->
    <div class="container" style="max-width: 1200px;">
        <div style="display: flex; flex-direction: row; gap: 10px; overflow-x: auto; white-space: nowrap; flex-wrap: nowrap; border-bottom: 1px solid var(--glass-border); padding-bottom: 10px; scrollbar-width: none;">
            <button class="category-btn active" id="tabActiveBtn" style="flex-shrink: 0;" onclick="switchTab('active')">
                📋 Active Orders (<span id="activeOrdersTabCount">0</span>)
            </button>
            <button class="category-btn" id="tabCompletedBtn" style="flex-shrink: 0;" onclick="switchTab('completed')">
                ✅ Completed Orders
            </button>
            <button class="category-btn" id="tabRejectedBtn" style="flex-shrink: 0;" onclick="switchTab('cancelled')">
                🚫 Rejected Orders
            </button>
        </div>
    </div>

    <!-- Orders Grid -->
    <main style="display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 14px; padding: 16px; max-width: 1200px; margin: 0 auto;" id="kitchenOrdersGrid">
        <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px;">
            Loading kitchen orders...
        </div>
    </main>

    <!-- Order Rejection Reason Modal -->
    <div class="spatial-modal" id="rejectOrderModal">
        <div class="spatial-modal-overlay" onclick="closeRejectModal()"></div>
        <div class="spatial-modal-content">
            <button class="spatial-modal-close" onclick="closeRejectModal()">✕</button>
            <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--danger); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                <span>🚫</span> Reject Order #<span id="rejectModalOrderId">0</span>
            </h3>
            <p style="color: var(--text-muted); font-size: 0.82rem; margin-bottom: 16px;">
                Select a reason for rejecting this order. The customer will see this message on their tracking screen.
            </p>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 700; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 6px;">Common Rejection Reasons:</label>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label class="custom-radio" style="width: 100%;">
                        <input type="radio" name="reject_reason" value="Customer accidentally placed wrong order or quantity" checked>
                        <span class="radio-label" style="width: 100%;">Customer placed wrong quantity / order</span>
                    </label>
                    <label class="custom-radio" style="width: 100%;">
                        <input type="radio" name="reject_reason" value="Item out of stock / ingredient unavailable">
                        <span class="radio-label" style="width: 100%;">Item out of stock / ingredient unavailable</span>
                    </label>
                    <label class="custom-radio" style="width: 100%;">
                        <input type="radio" name="reject_reason" value="Kitchen at max capacity / closing soon">
                        <span class="radio-label" style="width: 100%;">Kitchen busy / closing soon</span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="customRejectReason" style="display: block; font-weight: 700; font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 4px;">Custom Reason (Optional):</label>
                <input type="text" id="customRejectReason" placeholder="Type custom explanation..." style="width: 100%; background: rgba(14, 11, 8, 0.8); border: 1px solid var(--glass-border); border-radius: var(--radius-sm); padding: 10px; color: white; font-family: inherit; font-size: 0.88rem; outline: none;">
            </div>

            <div style="display: flex; gap: 10px;">
                <button onclick="closeRejectModal()" class="add-to-cart-btn" style="flex: 1; background: rgba(255,255,255,0.1); color: white;">Cancel</button>
                <button onclick="confirmRejectOrder()" class="checkout-btn" style="flex: 2; background: var(--danger); color: white; font-weight: 800; padding: 10px;">Confirm Rejection</button>
            </div>
        </div>
    </div>

    <script src="js/modern.js"></script>
    <script>
        let currentTab = 'active';
        let pendingRejectOrderId = null;
        let lastSeenOrderIds = [];
        let isFirstLoad = true;

        function switchTab(tab) {
            currentTab = tab;
            document.getElementById('tabActiveBtn').classList.toggle('active', tab === 'active');
            document.getElementById('tabCompletedBtn').classList.toggle('active', tab === 'completed');
            document.getElementById('tabRejectedBtn').classList.toggle('active', tab === 'cancelled');
            loadKitchenData();
        }

        function loadKitchenData() {
            loadWaiterCalls();
            loadOrders();
        }

        function loadWaiterCalls() {
            fetch('api/call-waiter.php')
                .then(r => r.json())
                .then(data => {
                    const grid = document.getElementById('waiterCallsGrid');
                    const calls = data.calls || [];
                    if (calls.length === 0) {
                        grid.innerHTML = '<div style="color: var(--text-muted); font-size: 0.85rem;">No active waiter calls</div>';
                    } else {
                        grid.innerHTML = calls.map(c => `
                            <div class="spatial-card" style="padding: 10px 16px; display: flex; align-items: center; gap: 14px; min-width: 180px; flex-shrink: 0;">
                                <div>
                                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary);">Table ${c.table_number}</div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">${getTimeAgo(c.created_at)}</div>
                                </div>
                                <button onclick="markWaiterServed(${c.id})" class="add-to-cart-btn" style="padding: 4px 12px; font-size: 0.75rem; background: var(--success); color: white;">
                                    ✓ Served
                                </button>
                            </div>
                        `).join('');
                    }
                })
                .catch(err => console.error(err));
        }

        function markWaiterServed(id) {
            fetch('api/call-waiter.php?id=' + id + '&action=serve', { method: 'POST' })
                .then(r => r.json())
                .then(() => loadWaiterCalls());
        }

        function loadOrders() {
            fetch('api/orders.php?status=' + currentTab)
                .then(r => r.json())
                .then(data => {
                    const grid = document.getElementById('kitchenOrdersGrid');
                    const orders = data.orders || [];

                    if (currentTab === 'active') {
                        const newCount = orders.filter(o => o.status === 'new').length;
                        const prepCount = orders.filter(o => o.status === 'preparing').length;
                        const readyCount = orders.filter(o => o.status === 'ready').length;

                        document.getElementById('statNewCount').textContent = newCount;
                        document.getElementById('statPrepCount').textContent = prepCount;
                        document.getElementById('statReadyCount').textContent = readyCount;
                        document.getElementById('activeOrdersTabCount').textContent = orders.length;

                        const currentOrderIds = orders.map(o => o.id);
                        if (!isFirstLoad) {
                            const newOrderArrived = currentOrderIds.some(id => !lastSeenOrderIds.includes(id));
                            if (newOrderArrived && newCount > 0) {
                                playSuccessChime();
                                showToast('🔔 New order received in kitchen!', 'warning');
                            }
                        }
                        lastSeenOrderIds = currentOrderIds;
                        isFirstLoad = false;
                    }

                    if (orders.length === 0) {
                        grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px;">
                            <div style="font-size: 2.8rem; margin-bottom: 8px;">📋</div>
                            <h3>${currentTab === 'cancelled' ? 'No cancelled orders found' : currentTab === 'completed' ? 'No completed orders yet' : 'No active orders'}</h3>
                        </div>`;
                        return;
                    }

                    grid.innerHTML = orders.map(o => {
                        const isNew = (o.status === 'new');
                        const items = o.items || [];
                        const itemCount = items.reduce((sum, i) => sum + i.quantity, 0);

                        return `
                            <div class="spatial-card" style="padding: 16px; display: flex; flex-direction: column;">
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--glass-border); padding-bottom: 8px; margin-bottom: 10px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 1.05rem; color: var(--text-primary);">Order #${o.id}</div>
                                        <div style="font-size: 0.76rem; color: var(--text-muted);">📍 Table ${o.table_number} • ${getTimeAgo(o.created_at)}</div>
                                    </div>
                                    <span style="font-weight: 800; font-size: 0.72rem; text-transform: uppercase; padding: 2px 8px; border-radius: var(--radius-pill); background: rgba(198, 124, 78, 0.2); color: var(--primary); border: 1px solid var(--primary);">${o.status}</span>
                                </div>

                                <div style="flex: 1; display: flex; flex-direction: column;">
                                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px;">
                                        📦 ${itemCount} items ${o.payment_method === 'cash' ? '<span style="color:#4ade80;">[CASH REQUEST]</span>' : ''}
                                    </div>
                                    <div style="max-height: 160px; overflow-y: auto; margin-bottom: 12px;">
                                        ${items.map(i => `
                                            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; padding: 3px 0; border-bottom: 1px solid rgba(255,255,255,0.03);">
                                                <span><strong style="color: var(--primary);">${i.quantity}x</strong> ${i.name}</span>
                                                <span style="color: var(--primary); font-weight: 700;">Rs.${i.price * i.quantity}</span>
                                            </div>
                                        `).join('')}
                                    </div>

                                    ${o.notes ? `<div style="background: rgba(198, 124, 78, 0.1); border: 1px solid var(--glass-border); padding: 8px; border-radius: var(--radius-sm); font-size: 0.78rem; color: #fde68a; margin-bottom: 12px;"><strong>📝 Notes:</strong> ${o.notes}</div>` : ''}

                                    ${currentTab === 'active' ? `
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; margin-top: auto;">
                                            <button class="add-to-cart-btn" style="background: rgba(245, 158, 11, 0.2); color: #fde68a; border: 1px solid var(--warning);" onclick="updateOrderStatus(${o.id}, 'preparing')">🔥 Prep</button>
                                            <button class="add-to-cart-btn" style="background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid var(--success);" onclick="updateOrderStatus(${o.id}, 'ready')">✅ Ready</button>
                                            <button class="checkout-btn" style="grid-column: span 2; padding: 8px; font-size: 0.85rem;" onclick="updateOrderStatus(${o.id}, 'completed')">✔ Served / Done</button>
                                            <button class="add-to-cart-btn" style="grid-column: span 2; background: rgba(244, 63, 94, 0.2); color: #fda4af; border: 1px solid var(--danger);" onclick="openRejectModal(${o.id})">❌ Reject / Cancel Order</button>
                                        </div>
                                    ` : currentTab === 'completed' ? `
                                        <div style="font-size: 0.8rem; color: var(--success); font-weight: 700; text-align: center; margin-top: auto;">
                                            ✓ Completed & Served
                                        </div>
                                    ` : `
                                        <div style="font-size: 0.8rem; color: var(--danger); font-weight: 600; text-align: center; margin-top: auto;">
                                            Order Rejected
                                        </div>
                                    `}
                                </div>
                            </div>
                        `;
                    }).join('');
                })
                .catch(err => console.error(err));
        }

        function updateOrderStatus(orderId, status, reason = '') {
            fetch('api/update-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: status, reason: reason })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(`Order #${orderId} set to ${status}`, 'success');
                    loadOrders();
                } else {
                    showToast(data.message || 'Error updating order', 'error');
                }
            })
            .catch(err => console.error(err));
        }

        function openRejectModal(orderId) {
            pendingRejectOrderId = orderId;
            document.getElementById('rejectModalOrderId').textContent = orderId;
            document.getElementById('rejectOrderModal').classList.add('active');
        }

        function closeRejectModal() {
            document.getElementById('rejectOrderModal').classList.remove('active');
            pendingRejectOrderId = null;
        }

        function confirmRejectOrder() {
            if (!pendingRejectOrderId) return;
            const selectedRadio = document.querySelector('input[name="reject_reason"]:checked');
            const customInput = document.getElementById('customRejectReason').value.trim();
            const reason = customInput || (selectedRadio ? selectedRadio.value : 'Cancelled by kitchen');

            updateOrderStatus(pendingRejectOrderId, 'cancelled', reason);
            closeRejectModal();
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadKitchenData();
            setInterval(loadKitchenData, 5000);
        });
    </script>
</body>
</html>
