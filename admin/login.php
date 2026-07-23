<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-950 text-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090b">
    <title>Admin Login - QR Cafe</title>
    <link rel="manifest" href="../manifest.json">
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
    <main class="w-full max-w-md mx-auto">
        <div class="bg-zinc-900/90 border border-zinc-800 rounded-3xl p-8 shadow-2xl space-y-5">
            <div class="text-center space-y-2">
                <div class="text-5xl">☕</div>
                <h1 class="text-xl font-black text-white">Manager Portal</h1>
                <p class="text-xs text-zinc-400">QR Cafe Management System</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs font-bold">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login-process.php" class="space-y-4">
                <div>
                    <label for="username" class="block text-xs font-bold text-zinc-300 mb-1.5">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500 transition-all">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-zinc-300 mb-1.5">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter password" required class="w-full h-12 bg-zinc-950 border border-zinc-800 rounded-2xl px-4 text-sm text-white placeholder-zinc-500 outline-none focus:border-amber-500 transition-all">
                </div>

                <button type="submit" class="h-12 w-full rounded-2xl bg-amber-500 text-zinc-950 font-black text-sm flex items-center justify-center active:scale-95 shadow-lg shadow-amber-500/20">
                    🔑 Login to Manager Panel
                </button>
            </form>

            <div class="p-3 rounded-xl bg-zinc-950 border border-zinc-800 text-center text-xs text-zinc-400">
                Default: <strong class="text-amber-400">admin</strong> / <strong class="text-amber-400">admin123</strong>
            </div>

            <div class="text-center pt-2">
                <a href="../menu.php" class="text-xs text-zinc-500 font-bold hover:text-amber-400">← Back to Menu</a>
            </div>
        </div>
    </main>
</body>
</html>
