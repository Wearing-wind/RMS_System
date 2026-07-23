<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Kitchen Display System (KDS) - QR Cafe</title>
    <link rel="manifest" href="manifest.json">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              amber: {
                500: '#f59e0b',
                600: '#d97706',
              }
            }
          }
        }
      }
    </script>
    <style>
        body { overscroll-behavior-y: contain; -webkit-tap-highlight-color: transparent; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes alertPulse {
            0% { box-shadow: 0 0 10px rgba(244, 63, 94, 0.4); transform: scale(1); }
            100% { box-shadow: 0 0 20px rgba(244, 63, 94, 0.8); transform: scale(1.03); }
        }
        .red-flash-badge { animation: alertPulse 1.2s infinite alternate; }
    </style>
</head>
<body class="min-h-full pb-24 md:pb-8 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">

    <!-- Top Header -->
    <header class="sticky top-0 z-40 bg-zinc-950/90 backdrop-blur-xl border-b border-zinc-800/80 px-4 py-3.5">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-2">
            <div class="flex items-center gap-3 font-black text-lg text-white">
                <span class="text-2xl">👨‍🍳</span>
                <div>
                    <h1 class="text-base md:text-lg font-black leading-tight">Kitchen Display System (KDS)</h1>
                    <p class="text-[10px] text-zinc-400 font-medium hidden sm:block">Live Kanban Monitor</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="kitchen-menu.php" class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-2xl bg-zinc-900 border border-zinc-800 text-xs font-bold text-amber-400">
                    📋 Today's Menu Catalog →
                </a>
                <!-- Sound & Haptic Alert Toggle -->
                <button id="soundToggleBtn" onclick="toggleSoundAlerts()" class="px-3 py-1.5 rounded-2xl bg-zinc-900 border border-zinc-800 text-xs font-extrabold text-zinc-300 flex items-center gap-1.5 active:scale-95 transition-all">
                    <span id="soundIcon">🔔</span>
                    <span id="soundLabel">Sound On</span>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 pt-4 space-y-4">
        
        <!-- Live Quick Stats Counter Cards (Mobile & Desktop Grid) -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-zinc-900/90 border border-rose-500/30 rounded-2xl p-3.5 text-center">
                <div class="text-xs font-bold text-rose-400">🆕 New Orders</div>
                <div id="statNewCount" class="text-2xl font-black text-rose-400 mt-0.5">0</div>
            </div>
            <div class="bg-zinc-900/90 border border-amber-500/30 rounded-2xl p-3.5 text-center">
                <div class="text-xs font-bold text-amber-400">🔥 In Prep</div>
                <div id="statPrepCount" class="text-2xl font-black text-amber-400 mt-0.5">0</div>
            </div>
            <div class="bg-zinc-900/90 border border-emerald-500/30 rounded-2xl p-3.5 text-center">
                <div class="text-xs font-bold text-emerald-400">✅ Ready for Pickup</div>
                <div id="statReadyCount" class="text-2xl font-black text-emerald-400 mt-0.5">0</div>
            </div>
        </div>

        <!-- Waiter Calls Carousel -->
        <div>
            <h4 class="text-xs font-extrabold text-zinc-400 mb-2 flex items-center gap-1.5 uppercase tracking-wider">
                <span>🔔</span> Waiter Call Alerts
            </h4>
            <div id="waiterCallsGrid" class="flex gap-2 overflow-x-auto no-scrollbar">
                <div class="text-xs text-zinc-500 italic py-1">No pending waiter calls</div>
            </div>
        </div>

        <!-- Mobile Navigation Tabs (Active, Completed, Rejected) - Hidden on md: screens -->
        <nav class="flex md:hidden gap-2 border-b border-zinc-800/80 pb-2">
            <button id="tabActiveBtn" onclick="switchTab('active')" class="px-4 py-2 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20">
                📋 Active (<span id="activeOrdersTabCount">0</span>)
            </button>
            <button id="tabCompletedBtn" onclick="switchTab('completed')" class="px-4 py-2 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-400">
                ✅ Served
            </button>
            <button id="tabRejectedBtn" onclick="switchTab('cancelled')" class="px-4 py-2 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-400">
                🚫 Rejected
            </button>
        </nav>

        <!-- MOBILE SINGLE-COLUMN FEED (Hidden on md: screens) -->
        <div id="mobileOrdersFeed" class="space-y-4 md:hidden mb-20">
            <div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 text-center text-zinc-500">
                Loading live kitchen stream...
            </div>
        </div>

        <!-- DESKTOP / TABLET MULTI-COLUMN KANBAN BOARD (Visible on md: & lg: screens) -->
        <div class="hidden md:grid md:grid-cols-3 gap-5 items-start">
            
            <!-- Column 1: New Orders -->
            <div class="bg-zinc-900/60 border border-rose-500/20 rounded-3xl p-4 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-rose-500/30">
                    <h3 class="font-black text-sm text-rose-400 flex items-center gap-1.5">
                        <span>🔴</span> New Orders
                    </h3>
                    <span id="kanbanNewCountBadge" class="bg-rose-500/20 text-rose-400 border border-rose-500/40 text-xs font-black px-2.5 py-0.5 rounded-full">0</span>
                </div>
                <div id="kanbanNewCol" class="space-y-4 min-h-[300px]">
                    <div class="text-center py-10 text-xs text-zinc-500">No new orders</div>
                </div>
            </div>

            <!-- Column 2: In Prep -->
            <div class="bg-zinc-900/60 border border-amber-500/20 rounded-3xl p-4 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-amber-500/30">
                    <h3 class="font-black text-sm text-amber-400 flex items-center gap-1.5">
                        <span>🔥</span> In Preparation
                    </h3>
                    <span id="kanbanPrepCountBadge" class="bg-amber-500/20 text-amber-400 border border-amber-500/40 text-xs font-black px-2.5 py-0.5 rounded-full">0</span>
                </div>
                <div id="kanbanPrepCol" class="space-y-4 min-h-[300px]">
                    <div class="text-center py-10 text-xs text-zinc-500">No orders in prep</div>
                </div>
            </div>

            <!-- Column 3: Ready for Delivery / Pickup -->
            <div class="bg-zinc-900/60 border border-emerald-500/20 rounded-3xl p-4 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-emerald-500/30">
                    <h3 class="font-black text-sm text-emerald-400 flex items-center gap-1.5">
                        <span>✅</span> Ready for Delivery
                    </h3>
                    <span id="kanbanReadyCountBadge" class="bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-xs font-black px-2.5 py-0.5 rounded-full">0</span>
                </div>
                <div id="kanbanReadyCol" class="space-y-4 min-h-[300px]">
                    <div class="text-center py-10 text-xs text-zinc-500">No orders ready</div>
                </div>
            </div>

        </div>

    </main>

    <!-- Order Rejection Reason Modal -->
    <div id="rejectOrderModal" class="fixed inset-0 z-50 flex items-end md:items-center justify-center opacity-0 pointer-events-none transition-all duration-300">
        <div class="absolute inset-0 bg-zinc-950/80 backdrop-blur-md" onclick="closeRejectModal()"></div>
        <div class="relative z-10 w-full max-w-md bg-zinc-900 border border-zinc-800 rounded-t-3xl md:rounded-3xl p-6 shadow-2xl translate-y-full md:translate-y-0 transition-transform duration-300">
            <button onclick="closeRejectModal()" class="absolute top-4 right-4 bg-zinc-800 text-zinc-400 w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm">✕</button>
            <h3 class="text-lg font-black text-rose-500 mb-2 flex items-center gap-2">
                <span>🚫</span> Reject Order #<span id="rejectModalOrderId">0</span>
            </h3>
            <p class="text-xs text-zinc-400 mb-4">Select or type a reason for rejecting this customer order.</p>

            <div class="space-y-2 mb-4">
                <label class="flex items-center gap-2.5 bg-zinc-950 border border-zinc-800 rounded-xl p-3 cursor-pointer text-xs font-bold text-zinc-300">
                    <input type="radio" name="reject_reason" value="Customer placed wrong order or quantity" checked class="accent-rose-500">
                    <span>Wrong quantity / customer error</span>
                </label>
                <label class="flex items-center gap-2.5 bg-zinc-950 border border-zinc-800 rounded-xl p-3 cursor-pointer text-xs font-bold text-zinc-300">
                    <input type="radio" name="reject_reason" value="Item out of stock / ingredient unavailable" class="accent-rose-500">
                    <span>Ingredient unavailable / out of stock</span>
                </label>
            </div>

            <input type="text" id="customRejectReason" placeholder="Type custom reason..." class="w-full bg-zinc-950 border border-zinc-800 rounded-xl p-3 text-xs text-white placeholder-zinc-500 mb-4 outline-none">

            <div class="flex gap-2">
                <button onclick="closeRejectModal()" class="w-1/3 h-11 rounded-xl bg-zinc-800 font-bold text-xs text-zinc-300">Cancel</button>
                <button onclick="confirmRejectOrder()" class="w-2/3 h-11 rounded-xl bg-rose-600 font-black text-xs text-white">Confirm Rejection</button>
            </div>
        </div>
    </div>

    <!-- Kitchen Navigation Bar (Mobile Only, Hidden on md: screens) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 max-w-md mx-auto bg-zinc-950/95 backdrop-blur-xl border-t border-zinc-800/80 flex justify-around items-center h-16 rounded-t-2xl px-2">
        <a href="kitchen-dashboard.php" class="flex flex-col items-center gap-0.5 text-amber-500 font-extrabold text-xs">
            <span class="text-lg">👨‍🍳</span>
            <span>KDS Stream</span>
        </a>
        <a href="kitchen-menu.php" class="flex flex-col items-center gap-0.5 text-zinc-400 font-bold text-xs">
            <span class="text-lg">📋</span>
            <span>Today's Menu</span>
        </a>
    </nav>

    <script src="js/modern.js"></script>
    <script>
        let currentTab = 'active';
        let pendingRejectOrderId = null;
        let lastSeenOrderIds = [];
        let isFirstLoad = true;
        let soundEnabled = true;

        function toggleSoundAlerts() {
            soundEnabled = !soundEnabled;
            document.getElementById('soundIcon').textContent = soundEnabled ? '🔔' : '🔕';
            document.getElementById('soundLabel').textContent = soundEnabled ? 'Sound On' : 'Sound Off';
            showToast(soundEnabled ? 'Audio alerts enabled' : 'Audio muted', soundEnabled ? 'info' : 'warning');
        }

        function toggleKdsItemCheck(el) {
            el.classList.toggle('opacity-40');
            el.classList.toggle('line-through');
            if (navigator.vibrate) navigator.vibrate(40);
        }

        function getElapsedBadge(createdDateStr) {
            const date = parseMySQLDate(createdDateStr);
            const minutes = Math.floor((new Date() - date) / 60000);
            if (minutes < 10) return `<span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-black text-[11px]">⏱️ ${minutes}m</span>`;
            if (minutes < 15) return `<span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-black text-[11px]">⏱️ ${minutes}m</span>`;
            return `<span class="px-2.5 py-1 rounded-full bg-rose-500/20 border border-rose-500 text-rose-400 font-black text-[11px] red-flash-badge">🚨 ${minutes}m LATE</span>`;
        }

        function switchTab(tab) {
            currentTab = tab;
            document.getElementById('tabActiveBtn').className = (tab === 'active') ? 'px-4 py-2 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20' : 'px-4 py-2 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-400';
            document.getElementById('tabCompletedBtn').className = (tab === 'completed') ? 'px-4 py-2 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20' : 'px-4 py-2 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-400';
            document.getElementById('tabRejectedBtn').className = (tab === 'cancelled') ? 'px-4 py-2 rounded-2xl font-black text-xs bg-amber-500 text-zinc-950 shadow-lg shadow-amber-500/20' : 'px-4 py-2 rounded-2xl font-bold text-xs bg-zinc-900 border border-zinc-800 text-zinc-400';
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
                        grid.innerHTML = '<div class="text-xs text-zinc-500 italic py-1">No pending waiter calls</div>';
                    } else {
                        grid.innerHTML = calls.map(c => `
                            <div class="bg-zinc-900 border border-amber-500/30 rounded-2xl p-2.5 px-3 flex items-center gap-3 shrink-0">
                                <div>
                                    <div class="font-black text-xs text-amber-400">Table ${c.table_number}</div>
                                    <div class="text-[10px] text-zinc-500">${getTimeAgo(c.created_at)}</div>
                                </div>
                                <button onclick="markWaiterServed(${c.id})" class="px-2.5 py-1 rounded-xl bg-emerald-500 text-zinc-950 font-black text-[11px]">✓ Served</button>
                            </div>
                        `).join('');
                    }
                });
        }

        function markWaiterServed(id) {
            fetch('api/call-waiter.php?id=' + id + '&action=serve', { method: 'POST' }).then(() => loadWaiterCalls());
        }

        function renderOrderCardHTML(o) {
            const items = o.items || [];
            const timeBadge = getElapsedBadge(o.created_at);

            return `
                <div class="bg-zinc-900/90 border border-zinc-800/90 rounded-3xl p-4 space-y-3">
                    <div class="flex justify-between items-start pb-3 border-b border-zinc-800">
                        <div>
                            <div class="font-black text-base text-white">Order #${o.id} • Table ${o.table_number}</div>
                            <div class="mt-1">${timeBadge}</div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 font-extrabold text-[10px] uppercase tracking-wider">${o.status}</span>
                    </div>

                    <div class="space-y-1.5">
                        ${items.map(i => `
                            <div onclick="toggleKdsItemCheck(this)" class="flex justify-between items-center p-2 rounded-xl bg-zinc-950/60 border border-zinc-800/40 cursor-pointer transition-all active:scale-[0.98]">
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-md border border-zinc-700 bg-zinc-800 flex items-center justify-center text-xs font-bold text-amber-400">✓</span>
                                    <span class="text-xs text-zinc-200"><strong class="text-amber-400">${i.quantity}x</strong> ${i.name}</span>
                                </div>
                                <span class="text-xs font-bold text-amber-400">Rs.${i.price * i.quantity}</span>
                            </div>
                        `).join('')}
                    </div>

                    ${o.notes ? `<div class="bg-amber-500/10 border border-amber-500/20 p-2.5 rounded-xl text-xs text-amber-300"><strong>📝 Notes:</strong> ${o.notes}</div>` : ''}

                    <div class="grid grid-cols-2 gap-2 pt-1">
                        ${o.status === 'new' ? `<button onclick="updateOrderStatus(${o.id}, 'preparing')" class="col-span-2 h-12 rounded-2xl bg-amber-500 text-zinc-950 font-black text-xs active:scale-95 shadow-lg shadow-amber-500/20">🔥 Start Prep</button>` : ''}
                        ${o.status === 'preparing' ? `<button onclick="updateOrderStatus(${o.id}, 'ready')" class="col-span-2 h-12 rounded-2xl bg-emerald-500 text-zinc-950 font-black text-xs active:scale-95 shadow-lg shadow-emerald-500/20">✅ Mark Ready</button>` : ''}
                        ${o.status === 'ready' ? `<button onclick="updateOrderStatus(${o.id}, 'completed')" class="col-span-2 h-12 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-zinc-950 font-black text-xs active:scale-95 shadow-lg shadow-amber-500/10">✔ Ready for Delivery / Served</button>` : ''}
                        ${o.status !== 'completed' && o.status !== 'cancelled' ? `<button onclick="openRejectModal(${o.id})" class="col-span-2 h-9 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 font-bold text-xs">❌ Reject Order</button>` : ''}
                    </div>
                </div>
            `;
        }

        function loadOrders() {
            fetch('api/orders.php?status=all')
                .then(r => r.json())
                .then(data => {
                    const allOrders = data.orders || [];
                    const activeOrders = allOrders.filter(o => o.status === 'new' || o.status === 'preparing' || o.status === 'ready');
                    const newOrders = allOrders.filter(o => o.status === 'new');
                    const prepOrders = allOrders.filter(o => o.status === 'preparing');
                    const readyOrders = allOrders.filter(o => o.status === 'ready');

                    document.getElementById('statNewCount').textContent = newOrders.length;
                    document.getElementById('statPrepCount').textContent = prepOrders.length;
                    document.getElementById('statReadyCount').textContent = readyOrders.length;

                    const activeTabCount = document.getElementById('activeOrdersTabCount');
                    if (activeTabCount) activeTabCount.textContent = activeOrders.length;

                    // Play audio notification on new order
                    const currentOrderIds = activeOrders.map(o => o.id);
                    if (!isFirstLoad && currentOrderIds.some(id => !lastSeenOrderIds.includes(id)) && newOrders.length > 0) {
                        if (soundEnabled) {
                            playSuccessChime();
                            if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
                        }
                        showToast('🔔 New order received in kitchen!', 'warning');
                    }
                    lastSeenOrderIds = currentOrderIds;
                    isFirstLoad = false;

                    // 1. Render Mobile Feed
                    const mobileFeed = document.getElementById('mobileOrdersFeed');
                    let filteredMobile = activeOrders;
                    if (currentTab === 'completed') filteredMobile = allOrders.filter(o => o.status === 'completed');
                    if (currentTab === 'cancelled') filteredMobile = allOrders.filter(o => o.status === 'cancelled');

                    if (filteredMobile.length === 0) {
                        mobileFeed.innerHTML = `<div class="bg-zinc-900 border border-zinc-800 rounded-3xl p-8 text-center text-zinc-500">
                            <div class="text-3xl mb-2">📋</div>
                            <h3 class="font-bold">No ${currentTab} orders</h3>
                        </div>`;
                    } else {
                        mobileFeed.innerHTML = filteredMobile.map(renderOrderCardHTML).join('');
                    }

                    // 2. Render Desktop Multi-Column Kanban Board
                    const newCol = document.getElementById('kanbanNewCol');
                    const prepCol = document.getElementById('kanbanPrepCol');
                    const readyCol = document.getElementById('kanbanReadyCol');

                    document.getElementById('kanbanNewCountBadge').textContent = newOrders.length;
                    document.getElementById('kanbanPrepCountBadge').textContent = prepOrders.length;
                    document.getElementById('kanbanReadyCountBadge').textContent = readyOrders.length;

                    if (newCol) newCol.innerHTML = newOrders.length > 0 ? newOrders.map(renderOrderCardHTML).join('') : '<div class="text-center py-10 text-xs text-zinc-500">No new orders</div>';
                    if (prepCol) prepCol.innerHTML = prepOrders.length > 0 ? prepOrders.map(renderOrderCardHTML).join('') : '<div class="text-center py-10 text-xs text-zinc-500">No orders in prep</div>';
                    if (readyCol) readyCol.innerHTML = readyOrders.length > 0 ? readyOrders.map(renderOrderCardHTML).join('') : '<div class="text-center py-10 text-xs text-zinc-500">No orders ready</div>';
                });
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
                    if (navigator.vibrate) navigator.vibrate(60);
                    showToast(`Order #${orderId} set to ${status}`, 'success');
                    loadOrders();
                }
            });
        }

        function openRejectModal(orderId) {
            pendingRejectOrderId = orderId;
            document.getElementById('rejectModalOrderId').textContent = orderId;
            document.getElementById('rejectOrderModal').classList.remove('opacity-0', 'pointer-events-none');
            document.getElementById('rejectOrderModal').children[1].classList.remove('translate-y-full', 'md:translate-y-0');
        }

        function closeRejectModal() {
            document.getElementById('rejectOrderModal').classList.add('opacity-0', 'pointer-events-none');
            document.getElementById('rejectOrderModal').children[1].classList.add('translate-y-full');
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

        document.addEventListener('DOMContentLoaded', () => {
            loadKitchenData();
            setInterval(loadKitchenData, 5000);
        });
    </script>
</body>
</html>
