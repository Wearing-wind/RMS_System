<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>QR Cafe - Mobile Portal</title>
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
    </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 font-sans antialiased selection:bg-amber-500 selection:text-zinc-950">
    <main class="w-full max-w-md mx-auto text-center">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-8 shadow-2xl space-y-6">
            <div class="text-6xl">☕</div>
            <div>
                <h1 class="text-2xl font-black text-white">QR Cafe & Dining</h1>
                <p class="text-xs text-zinc-400 mt-1">Digital Mobile Ordering System</p>
            </div>

            <div class="space-y-3 pt-2">
                <a href="menu.php?table=1" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                    📱 Open Customer Mobile App (Table 1)
                </a>
                <a href="kitchen-dashboard.php" class="h-12 w-full rounded-2xl bg-zinc-950 border border-zinc-800 text-amber-400 font-bold text-sm flex items-center justify-center active:scale-95">
                    👨‍🍳 Kitchen Display (KDS)
                </a>
                <a href="admin/login.php" class="inline-block text-xs font-bold text-zinc-500 hover:text-amber-400 pt-2">
                    🔑 Admin / Manager Login →
                </a>
            </div>
        </div>
    </main>
</body>
</html>
