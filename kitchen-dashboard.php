<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Dashboard - QR Restaurant</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            padding-bottom: 30px;
        }
        
        .header-bar {
            background: linear-gradient(135deg, #0f0f23, #1a1a2e);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .header-title {
            color: white;
            font-size: 28px;
            font-weight: bold;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .stats-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .stat-badge {
            background: rgba(255,255,255,0.25);
            padding: 10px 20px;
            border-radius: 12px;
            text-align: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-badge .num {
            color: white;
            font-size: 24px;
            font-weight: bold;
            display: block;
        }
        
        .stat-badge .label {
            color: rgba(255,255,255,0.9);
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-refresh {
            background: white;
            color: #ff6b35;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .section-title {
            color: white;
            font-size: 22px;
            margin: 25px 20px 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
            font-weight: 600;
        }
        
        .waiter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            padding: 0 20px;
            margin-bottom: 25px;
        }
        
        .waiter-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .waiter-card .table-num {
            color: #ff6b35;
            font-size: 28px;
            font-weight: bold;
        }
        
        .waiter-card .table-label {
            color: #666;
            font-size: 12px;
            display: block;
        }
        
        .waiter-card .serve-btn {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 20px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 3px 10px rgba(255,107,53,0.4);
            transition: all 0.3s;
        }
        
        .waiter-card .serve-btn:hover {
            background: #e55a2b;
            transform: scale(1.05);
        }
        
        .no-calls-msg {
            color: white;
            padding: 20px;
            text-align: center;
            grid-column: 1 / -1;
            font-size: 16px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
        
        .orders-container {
            padding: 0 20px;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 25px rgba(0,0,0,0.15);
            transition: transform 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .order-header {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-header.status-new { background: #ff7675; }
        .order-header.status-preparing { background: #fdcb6e; color: #333; }
        .order-header.status-ready { background: #00b894; }
        .order-header.status-completed { background: #636e72; }
        
        .order-num {
            font-size: 20px;
            font-weight: bold;
            color: white;
        }
        
        .order-header.status-preparing .order-num { color: #333; }
        
        .order-table-badge {
            background: rgba(255,255,255,0.3);
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: bold;
        }
        
        .order-body {
            padding: 20px;
        }
        
        .order-items {
            max-height: 160px;
            overflow-y: auto;
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 10px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .order-item:last-child { border-bottom: none; }
        
        .item-qty {
            color: #ff6b35;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .item-price {
            color: #666;
            font-weight: 500;
        }
        
        .order-note {
            background: #fff9e6;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 15px;
            border-left: 4px solid #fdcb6e;
        }
        
        .order-time-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            color: #666;
            font-size: 14px;
        }
        
        .order-timer {
            background: #f5f6fa;
            padding: 6px 14px;
            border-radius: 15px;
            font-weight: bold;
            color: #ff6b35;
        }
        
        .action-btns {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }
        
        .action-btn {
            border: none;
            padding: 12px 8px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: all 0.3s;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .action-btn:hover { transform: scale(1.05); }
        .action-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        
        .btn-new { background: #ff7675; color: white; }
        .btn-preparing { background: #fdcb6e; color: #333; }
        .btn-ready { background: #55efc4; color: #333; }
        .btn-done { background: #00b894; color: white; }
        
        .btn-active {
            box-shadow: 0 0 0 3px rgba(0,0,0,0.3);
        }
        
        .loading-msg, .empty-msg, .error-msg {
            text-align: center;
            padding: 50px 20px;
            color: white;
            font-size: 18px;
            grid-column: 1 / -1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .error-msg {
            background: rgba(255,118,117,0.9);
            border-radius: 15px;
            margin: 20px;
        }
        
        @media (max-width: 600px) {
            .header-bar { flex-direction: column; text-align: center; }
            .orders-grid { grid-template-columns: 1fr; }
            .action-btns { grid-template-columns: repeat(2, 1fr); }
        }
        
        /* New order animation */
        .new-order-anim {
            animation: newOrderPulse 1.5s ease-in-out infinite;
        }
        
        @keyframes newOrderPulse {
            0%, 100% { 
                box-shadow: 0 6px 25px rgba(255, 107, 53, 0.3);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 6px 35px rgba(255, 107, 53, 0.6);
                transform: scale(1.02);
            }
        }
        
        .order-items-count {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header-bar">
        <div class="header-title">👨‍🍳 Kitchen Dashboard</div>
        <div class="stats-row">
            <div class="stat-badge">
                <span class="num" id="newCount">0</span>
                <span class="label">New</span>
            </div>
            <div class="stat-badge">
                <span class="num" id="prepCount">0</span>
                <span class="label">Preparing</span>
            </div>
            <div class="stat-badge">
                <span class="num" id="readyCount">0</span>
                <span class="label">Ready</span>
            </div>
        </div>
        <button class="btn-refresh" onclick="loadData()">🔄 Refresh</button>
    </div>

    <div class="section-title">🔔 Waiter Calls</div>
    <div class="waiter-grid" id="waiterGrid">
        <div class="no-calls-msg">Loading...</div>
    </div>

    <div class="section-title">📋 Active Orders</div>
    <div class="orders-container">
        <div class="orders-grid" id="ordersGrid">
            <div class="loading-msg">Loading orders...</div>
        </div>
    </div>

    <script>
        let lastOrderCount = 0;
        let lastOrderIds = [];
        
        function loadData() {
            loadWaiterCalls();
            loadOrders();
        }
        
        function loadWaiterCalls() {
            fetch('api/call-waiter.php')
                .then(r => r.json())
                .then(data => {
                    const grid = document.getElementById('waiterGrid');
                    const calls = data.calls || [];
                    if (calls.length === 0) {
                        grid.innerHTML = '<div class="no-calls-msg">No waiter calls</div>';
                    } else {
                        grid.innerHTML = calls.map(c => `
                            <div class="waiter-card">
                                <div>
                                    <span class="table-num">${c.table_number}</span>
                                    <span class="table-label">Table</span>
                                </div>
                                <button class="serve-btn" onclick="markServed(${c.id})">✓ Serve</button>
                            </div>
                        `).join('');
                    }
                })
                .catch(e => console.error(e));
        }
        
        function markServed(id) {
            fetch('api/call-waiter.php?id=' + id + '&action=serve', { method: 'POST' })
                .then(r => r.json())
                .then(() => loadWaiterCalls());
        }
        
        function loadOrders() {
            fetch('api/orders.php?status=all')
                .then(r => r.json())
                .then(data => {
                    const grid = document.getElementById('ordersGrid');
                    
                    if (data.error) {
                        grid.innerHTML = '<div class="error-msg">Error: ' + data.error + '</div>';
                        return;
                    }
                    
                    const orders = data.orders || [];
                    
                    // Check for new orders and play sound
                    const currentOrderIds = orders.map(o => o.id);
                    const newOrders = orders.filter(o => o.status === 'new');
                    
                    if (lastOrderCount > 0 && newOrders.length > 0) {
                        // Check if there are new orders that weren't there before
                        const newOrderIds = currentOrderIds.filter(id => !lastOrderIds.includes(id));
                        if (newOrderIds.length > 0) {
                            playNotificationSound();
                        }
                    }
                    
                    // Update tracking
                    lastOrderIds = currentOrderIds;
                    lastOrderCount = orders.length;
                    
                    document.getElementById('newCount').textContent = orders.filter(o => o.status === 'new').length;
                    document.getElementById('prepCount').textContent = orders.filter(o => o.status === 'preparing').length;
                    document.getElementById('readyCount').textContent = orders.filter(o => o.status === 'ready').length;
                    
                    if (orders.length === 0) {
                        grid.innerHTML = '<div class="empty-msg">No orders yet</div>';
                        return;
                    }
                    
                    grid.innerHTML = orders.map(o => {
                        const items = o.items || [];
                        const itemCount = items.reduce((sum, i) => sum + i.quantity, 0);
                        return `
                            <div class="order-card ${o.status === 'new' ? 'new-order-anim' : ''}">
                                <div class="order-header status-${o.status}">
                                    <span class="order-num">#${o.id}</span>
                                    <span class="order-table-badge">Table ${o.table_number}</span>
                                </div>
                                <div class="order-body">
                                    <div class="order-items-count">📦 ${itemCount} item${itemCount > 1 ? 's' : ''}</div>
                                    <div class="order-items">
                                        ${items.map(i => `
                                            <div class="order-item">
                                                <span><span class="item-qty">${i.quantity}x</span> ${i.name}</span>
                                                <span class="item-price">Rs.${i.price * i.quantity}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                    ${o.notes ? `<div class="order-note"><strong>📝 Note:</strong> ${o.notes}</div>` : ''}
                                    <div class="order-time-row">
                                        <span>🕐 ${getTimeAgo(o.created_at)}</span>
                                        <span class="order-timer" id="timer${o.id}">0:00</span>
                                    </div>
                                    <div class="action-btns">
                                        <button class="action-btn btn-new ${o.status === 'new' ? 'btn-active' : ''}" 
                                            onclick="updateStatus(${o.id}, 'new')" ${o.status === 'new' ? 'disabled' : ''}>🆕 New</button>
                                        <button class="action-btn btn-preparing ${o.status === 'preparing' ? 'btn-active' : ''}" 
                                            onclick="updateStatus(${o.id}, 'preparing')" ${o.status === 'preparing' ? 'disabled' : ''}>🔥 Prep</button>
                                        <button class="action-btn btn-ready ${o.status === 'ready' ? 'btn-active' : ''}" 
                                            onclick="updateStatus(${o.id}, 'ready')" ${o.status === 'ready' ? 'disabled' : ''}>✅ Ready</button>
                                        <button class="action-btn btn-done ${o.status === 'completed' ? 'btn-active' : ''}" 
                                            onclick="updateStatus(${o.id}, 'completed')" ${o.status === 'completed' ? 'disabled' : ''}>✔ Done</button>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                    
                    orders.forEach(o => startTimer(o.id, o.created_at));
                })
                .catch(e => {
                    document.getElementById('ordersGrid').innerHTML = '<div class="error-msg">Failed to load orders. Make sure database is set up.</div>';
                });
        }
        
        function updateStatus(id, status) {
            fetch('api/update-order.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({order_id: id, status: status})
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) loadOrders();
                else alert('Error updating order');
            });
        }
        
        function getTimeAgo(dateStr) {
            const diff = Math.floor((new Date() - new Date(dateStr)) / 1000);
            if (diff < 60) return diff + 's ago';
            if (diff < 3600) return Math.floor(diff/60) + 'm ago';
            return Math.floor(diff/3600) + 'h ago';
        }
        
        function startTimer(id, startTime) {
            const el = document.getElementById('timer' + id);
            if (!el) return;
            setInterval(() => {
                const diff = Math.floor((new Date() - new Date(startTime)) / 1000);
                el.textContent = Math.floor(diff/60) + ':' + (diff%60).toString().padStart(2,'0');
            }, 1000);
        }
        
        // Sound notification for new orders
        function playNotificationSound() {
            try {
                // Create a pleasant notification sound
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                // Play multiple tones for attention
                const playTone = (freq, delay) => {
                    setTimeout(() => {
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);
                        
                        oscillator.frequency.value = freq;
                        oscillator.type = 'sine';
                        gainNode.gain.value = 0.3;
                        
                        oscillator.start();
                        oscillator.stop(audioContext.currentTime + 0.3);
                    }, delay);
                };
                
                playTone(800, 0);
                playTone(1000, 200);
                playTone(800, 400);
                playTone(1200, 600);
            } catch(e) {
                console.log('Audio notification not supported');
            }
        }
        
        loadData();
        setInterval(loadData, 5000);
    </script>
</body>
</html>
