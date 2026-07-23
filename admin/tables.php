<?php
// Admin Tables & QR Code Management
require_once '../config.php';
requireAdminLogin();

$conn = getDBConnection();

if ($conn === null) {
    die("Database not connected. Please run setup.php first.");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $table_number = sanitize($_POST['table_number']);
        $check = $conn->query("SELECT id FROM tables WHERE table_number = '$table_number'");
        if ($check->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO tables (table_number) VALUES (?)");
            $stmt->bind_param("s", $table_number);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Table added successfully';
        } else {
            $_SESSION['error'] = 'Table number already exists';
        }
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM tables WHERE id = $id");
    $_SESSION['success'] = 'Table deleted successfully';
    header('Location: tables.php');
    exit;
}

$tables = $conn->query("SELECT * FROM tables ORDER BY table_number");
$conn->close();

$base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables & QR Codes - QR Restaurant Admin</title>
    <link rel="stylesheet" href="../css/modern.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f5f6fa; }
        .admin-header { background: linear-gradient(135deg, #ff6b35, #ff8c5a); padding: 15px 0; }
        .admin-header .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .admin-logo { color: white; font-size: 1.5rem; font-weight: bold; text-decoration: none; }
        .admin-nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .admin-nav a { color: rgba(255,255,255,0.8); text-decoration: none; padding: 10px 18px; border-radius: 25px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: rgba(255,255,255,0.2); color: white; }
        .admin-content { max-width: 1400px; margin: 30px auto; padding: 0 20px; }
        .admin-content h1 { color: #2d3436; margin-bottom: 20px; }
        .admin-content h2 { color: #2d3436; margin: 20px 0 15px; }
        .admin-content p { color: #636e72; margin-bottom: 20px; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-download { background: #3498db; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
        .form-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-card h2 { margin-top: 0; color: #2d3436; }
        .form-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; margin-bottom: 5px; color: #2d3436; font-weight: 600; font-size: 0.9rem; }
        .form-group input { padding: 10px; border: 2px solid #dfe6e9; border-radius: 8px; font-size: 1rem; }
        .form-group input:focus { outline: none; border-color: #ff6b35; }
        
        .page-layout { display: grid; grid-template-columns: 1fr 420px; gap: 30px; }
        @media (max-width: 1200px) { .page-layout { grid-template-columns: 1fr; } }
        
        .qr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .qr-card { background: white; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 3px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .qr-card:hover { transform: translateY(-5px); }
        .qr-card h3 { margin: 0 0 15px 0; color: #2d3436; }
        .qr-code { display: inline-block; margin-bottom: 15px; }
        .qr-link { font-size: 0.7rem; color: #ff6b35; word-break: break-all; margin-bottom: 15px; }
        .qr-actions { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        
        .preview-panel { 
            background: white; 
            border-radius: 15px; 
            padding: 25px; 
            box-shadow: 0 3px 15px rgba(0,0,0,0.1); 
            position: sticky; 
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        .preview-panel h3 { margin: 0 0 15px 0; color: #2d3436; }
        
        .control-section { margin-bottom: 18px; }
        .control-section h4 { margin: 0 0 8px 0; color: #2d3436; font-size: 0.9rem; font-weight: 600; }
        .size-inputs { display: flex; gap: 8px; align-items: center; margin-bottom: 10px; }
        .size-inputs input { width: 70px; padding: 8px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 0.85rem; }
        .size-inputs label { font-size: 0.8rem; color: #636e72; }
        .ratio-lock { display: flex; align-items: center; gap: 6px; cursor: pointer; font-size: 0.8rem; color: #636e72; }
        
        .preset-btns { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
        .preset-btn { padding: 5px 10px; font-size: 0.75rem; background: #f0f0f0; border: none; border-radius: 5px; cursor: pointer; }
        .preset-btn:hover { background: #e0e0e0; }
        .preset-btn.active { background: #ff6b35; color: white; }
        
        .scale-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .scale-btn { padding: 5px 12px; font-size: 0.75rem; background: #f0f0f0; border: none; border-radius: 15px; cursor: pointer; }
        .scale-btn:hover { background: #e0e0e0; }
        .scale-btn.active { background: #ff6b35; color: white; }
        
        .format-btns { display: flex; gap: 6px; }
        .format-btn { padding: 6px 12px; font-size: 0.8rem; background: #f0f0f0; border: none; border-radius: 5px; cursor: pointer; }
        .format-btn:hover { background: #e0e0e0; }
        .format-btn.active { background: #ff6b35; color: white; }
        
        .toggle-item { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .toggle-item input { width: auto; }
        .toggle-item label { font-size: 0.8rem; color: #636e72; }
        
        .slider-container { margin-bottom: 15px; }
        .slider-container input[type="range"] { width: 100%; }
        .slider-labels { display: flex; justify-content: space-between; font-size: 0.7rem; color: #999; }
        
        .download-btn { width: 100%; padding: 14px; font-size: 1rem; margin-top: 10px; }
        
        #previewContainer {
            margin-top: 15px;
            text-align: center;
            background: #f5f5f5;
            border-radius: 10px;
            padding: 15px;
            overflow: auto;
        }
        #previewCanvas { 
            max-width: 100%; 
            height: auto;
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        
        #downloadCanvas { display: none; }
        
        .info-msg { 
            background: #fff3cd; 
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 15px; 
            text-align: center;
            font-size: 0.85rem;
            color: #856404;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="container">
            <a href="index.php" class="admin-logo">🍽️ Admin Panel</a>
            <nav class="admin-nav">
                <a href="index.php">Dashboard</a>
                <a href="menu-items.php">Menu Items</a>
                <a href="categories.php">Categories</a>
                <a href="tables.php" class="active">Tables & QR</a>
                <a href="orders.php">Orders</a>
                <a href="payment-settings.php">Payment</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <section class="admin-content">
        <h1>Tables & QR Codes Management</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="page-layout">
            <div>
                <div class="form-card">
                    <h2>Add New Table</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Table Number</label>
                                <input type="text" name="table_number" required placeholder="e.g., 11">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Add Table</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <h2>QR Codes</h2>
                <p>Click "Preview & Download" on any table to customize and download.</p>
                
                <div class="qr-grid">
                    <?php while ($table = $tables->fetch_assoc()): ?>
                    <div class="qr-card">
                        <h3>Table <?php echo htmlspecialchars($table['table_number']); ?></h3>
                        <div class="qr-code" id="qr_<?php echo $table['id']; ?>"></div>
                        <p class="qr-link"><?php echo $base_url; ?>/menu.php?table=<?php echo $table['table_number']; ?></p>
                        <div class="qr-actions">
                            <button type="button" class="btn btn-sm btn-download" onclick="selectTable(<?php echo $table['id']; ?>, '<?php echo $base_url; ?>/menu.php?table=<?php echo $table['table_number']; ?>', 'Table <?php echo $table['table_number']; ?>')">
                                ⬇️ Preview & Download
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteTable(<?php echo $table['id']; ?>)">🗑️</button>
                        </div>
                    </div>
                    <script>
                        new QRCode(document.getElementById("qr_<?php echo $table['id']; ?>"), {
                            text: "<?php echo $base_url; ?>/menu.php?table=<?php echo $table['table_number']; ?>",
                            width: 90,
                            height: 90,
                            colorDark : "#000000",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.H
                        });
                    </script>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <div class="preview-panel">
                <h3>📥 Download QR Card</h3>
                <div id="selectPrompt" class="info-msg">
                    Select a table from the left to preview and download.
                </div>
                <div id="selectedInfo" style="display:none; background: #d4edda; padding: 8px; border-radius: 6px; margin-bottom: 15px; text-align: center;">
                    <strong id="selectedTableName" style="color: #155724;"></strong>
                </div>
                
                <div class="control-section">
                    <h4>📐 Size (Width × Height)</h4>
                    <div class="size-inputs">
                        <input type="number" id="canvasWidth" value="400" min="250" max="1500" onchange="updatePreview()">
                        <label>×</label>
                        <input type="number" id="canvasHeight" value="550" min="350" max="2000" onchange="updatePreview()">
                        <label>px</label>
                    </div>
                    <label class="ratio-lock">
                        <input type="checkbox" id="lockRatio" checked onchange="handleRatioLock()">
                        Maintain aspect ratio
                    </label>
                </div>
                
                <div class="control-section">
                    <h4>Preset Sizes</h4>
                    <div class="preset-btns">
                        <button type="button" class="preset-btn" onclick="setPreset(300, 400)">300×400</button>
                        <button type="button" class="preset-btn active" onclick="setPreset(400, 550)">400×550</button>
                        <button type="button" class="preset-btn" onclick="setPreset(450, 600)">450×600</button>
                        <button type="button" class="preset-btn" onclick="setPreset(500, 700)">500×700</button>
                        <button type="button" class="preset-btn" onclick="setPreset(600, 800)">600×800</button>
                    </div>
                </div>
                
                <div class="control-section">
                    <h4>📊 Scale</h4>
                    <div class="scale-btns">
                        <button type="button" class="scale-btn" onclick="setScale(0.5)">50%</button>
                        <button type="button" class="scale-btn" onclick="setScale(0.75)">75%</button>
                        <button type="button" class="scale-btn active" onclick="setScale(1)">100%</button>
                        <button type="button" class="scale-btn" onclick="setScale(1.25)">125%</button>
                        <button type="button" class="scale-btn" onclick="setScale(1.5)">150%</button>
                    </div>
                </div>
                
                <div class="control-section">
                    <h4>📁 Format</h4>
                    <div class="format-btns">
                        <button type="button" class="format-btn active" onclick="setFormat('png')">PNG</button>
                        <button type="button" class="format-btn" onclick="setFormat('jpg')">JPG</button>
                    </div>
                </div>
                
                <div class="control-section">
                    <h4>👁️ Show Sections</h4>
                    <div class="toggle-item">
                        <input type="checkbox" id="showHeader" checked onchange="updatePreview()">
                        <label>Restaurant Header</label>
                    </div>
                    <div class="toggle-item">
                        <input type="checkbox" id="showInstructions" checked onchange="updatePreview()">
                        <label>English Instructions</label>
                    </div>
                    <div class="toggle-item">
                        <input type="checkbox" id="showNepali" checked onchange="updatePreview()">
                        <label>Nepali Instructions</label>
                    </div>
                    <div class="toggle-item">
                        <input type="checkbox" id="showThankYou" checked onchange="updatePreview()">
                        <label>Thank You Message</label>
                    </div>
                </div>
                
                <div class="slider-container">
                    <h4>🔤 Font Size</h4>
                    <input type="range" id="fontSize" min="8" max="16" value="11" oninput="updatePreview()">
                    <div class="slider-labels"><span>Small</span><span id="fontSizeVal">11px</span><span>Large</span></div>
                </div>
                
                <div class="slider-container">
                    <h4>↕️ QR Code Size</h4>
                    <input type="range" id="qrScale" min="30" max="80" value="45" oninput="updatePreview()">
                    <div class="slider-labels"><span>Small</span><span id="qrScaleVal">45%</span><span>Large</span></div>
                </div>
                
                <button type="button" class="btn btn-download" onclick="downloadCard()">
                    ⬇️ Download QR Card
                </button>
                
                <div id="previewContainer">
                    <canvas id="previewCanvas"></canvas>
                </div>
            </div>
        </div>
    </section>
    
    <canvas id="downloadCanvas"></canvas>
    
    <script>
        let selectedTableId = null;
        let selectedUrl = '';
        let selectedTableName = 'Table';
        let currentFormat = 'png';
        let currentScale = 1;
        
        // Default aspect ratio
        const DEFAULT_RATIO = 550 / 400;
        
        function deleteTable(id) {
            if (confirm('Are you sure you want to delete this table?')) {
                window.location.href = 'tables.php?delete=' + id;
            }
        }
        
        function selectTable(tableId, url, name) {
            selectedTableId = tableId;
            selectedUrl = url;
            selectedTableName = name;
            
            document.getElementById('selectPrompt').style.display = 'none';
            document.getElementById('selectedInfo').style.display = 'block';
            document.getElementById('selectedTableName').textContent = name;
            
            // Generate QR for preview
            const qrContainer = document.getElementById('qr_preview');
            if (!qrContainer) {
                const div = document.createElement('div');
                div.id = 'qr_preview';
                div.style.display = 'none';
                document.body.appendChild(div);
            }
            document.getElementById('qr_preview').innerHTML = '';
            new QRCode(document.getElementById('qr_preview'), {
                text: url,
                width: 200,
                height: 200
            });
            
            setTimeout(updatePreview, 150);
        }
        
        function setPreset(w, h) {
            document.getElementById('canvasWidth').value = w;
            document.getElementById('canvasHeight').value = h;
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            updatePreview();
        }
        
        function setScale(scale) {
            currentScale = scale;
            document.querySelectorAll('.scale-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            updatePreview();
        }
        
        function setFormat(fmt) {
            currentFormat = fmt;
            document.querySelectorAll('.format-btn').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
        }
        
        function handleRatioLock() {
            if (document.getElementById('lockRatio').checked) {
                const w = parseInt(document.getElementById('canvasWidth').value);
                document.getElementById('canvasHeight').value = Math.round(w * DEFAULT_RATIO);
                updatePreview();
            }
        }
        
        // Update sliders display
        document.getElementById('canvasWidth').addEventListener('input', function() {
            if (document.getElementById('lockRatio').checked) {
                document.getElementById('canvasHeight').value = Math.round(this.value * DEFAULT_RATIO);
            }
            updatePreview();
        });
        
        document.getElementById('fontSize').addEventListener('input', function() {
            document.getElementById('fontSizeVal').textContent = this.value + 'px';
        });
        
        document.getElementById('qrScale').addEventListener('input', function() {
            document.getElementById('qrScaleVal').textContent = this.value + '%';
        });
        
        function updatePreview() {
            if (!selectedTableId) return;
            const qrEl = document.querySelector('#qr_preview img');
            if (!qrEl) return;
            generateCard(document.getElementById('previewCanvas'), qrEl.src);
        }
        
        function generateCard(canvas, qrSrc) {
            const baseW = parseInt(document.getElementById('canvasWidth').value);
            const baseH = parseInt(document.getElementById('canvasHeight').value);
            const scale = currentScale;
            
            const w = baseW * scale;
            const h = baseH * scale;
            
            canvas.width = w;
            canvas.height = h;
            
            const ctx = canvas.getContext('2d');
            const fontSize = parseInt(document.getElementById('fontSize').value) * scale;
            const qrPercent = parseInt(document.getElementById('qrScale').value) / 100;
            
            const showHeader = document.getElementById('showHeader').checked;
            const showEng = document.getElementById('showInstructions').checked;
            const showNep = document.getElementById('showNepali').checked;
            const showThank = document.getElementById('showThankYou').checked;
            
            // Safe margins
            const margin = 20 * scale;
            const contentW = w - (margin * 2);
            
            // Calculate dynamic height
            let y = 0;
            
            // Background
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, w, h);
            
            // Header
            if (showHeader) {
                const headerH = 60 * scale;
                ctx.fillStyle = '#ff6b35';
                ctx.fillRect(0, 0, w, headerH);
                ctx.fillStyle = '#ffffff';
                ctx.font = `bold ${20 * scale}px Segoe UI, Arial`;
                ctx.textAlign = 'center';
                ctx.fillText('🍽️ QR Restaurant', w/2, 38 * scale);
                y = headerH + (15 * scale);
            } else {
                y = margin;
            }
            
            // Table number
            ctx.fillStyle = '#2d3436';
            ctx.font = `bold ${24 * scale}px Segoe UI, Arial`;
            ctx.fillText(selectedTableName, w/2, y + (25 * scale));
            y += 35 * scale;
            
            // QR Code
            const qrSize = contentW * qrPercent;
            if (qrSrc) {
                const img = new Image();
                img.onload = function() {
                    const qrX = (w - qrSize) / 2;
                    ctx.drawImage(img, qrX, y, qrSize, qrSize);
                    
                    // Scan text
                    y += qrSize + (12 * scale);
                    ctx.fillStyle = '#636e72';
                    ctx.font = `${12 * scale}px Segoe UI, Arial`;
                    ctx.fillText('📱 Scan to Order', w/2, y + (12 * scale));
                    y += 25 * scale;
                    
                    // Divider
                    if (showEng || showNep) {
                        ctx.strokeStyle = '#dfe6e9';
                        ctx.lineWidth = 1 * scale;
                        ctx.beginPath();
                        ctx.moveTo(margin, y);
                        ctx.lineTo(w - margin, y);
                        ctx.stroke();
                        y += 15 * scale;
                    }
                    
                    // English
                    if (showEng) {
                        ctx.fillStyle = '#2d3436';
                        ctx.font = `bold ${fontSize * 1.1}px Segoe UI, Arial`;
                        ctx.textAlign = 'left';
                        ctx.fillText('HOW TO ORDER (ENGLISH):', margin, y);
                        y += (fontSize * 1.4);
                        
                        ctx.fillStyle = '#636e72';
                        ctx.font = `${fontSize}px Segoe UI, Arial`;
                        const engSteps = [
                            '1. Scan QR Code with phone camera',
                            '2. Menu opens automatically', 
                            '3. Browse & choose food items',
                            '4. Tap Add to Cart',
                            '5. Review cart & place order',
                            '6. Wait - food comes to your table'
                        ];
                        engSteps.forEach(step => {
                            ctx.fillText(step, margin + 4*scale, y);
                            y += (fontSize * 1.2);
                        });
                    }
                    
                    // Nepali
                    if (showNep) {
                        y += 8 * scale;
                        ctx.fillStyle = '#2d3436';
                        ctx.font = `bold ${fontSize * 1.1}px Segoe UI, Arial`;
                        ctx.fillText('अर्डर गर्ने तरिका (NEPALI):', margin, y);
                        y += (fontSize * 1.4);
                        
                        ctx.fillStyle = '#636e72';
                        ctx.font = `${fontSize}px Segoe UI, Arial`;
                        const nepSteps = [
                            '१. QR कोड स्क्यान गर्नुहोस्',
                            '२. मेनु स्वतः खुल्छ',
                            '३. खाना छान्नुहोस्',
                            '४. Add to Cart थिच्नुहोस्',
                            '५. अर्डर गर्नुहोस्',
                            '६. खाना टेबलमा आउँछ'
                        ];
                        nepSteps.forEach(step => {
                            ctx.fillText(step, margin + 4*scale, y);
                            y += (fontSize * 1.2);
                        });
                    }
                    
                    // Thank you
                    if (showThank) {
                        y += 10 * scale;
                        ctx.fillStyle = '#ff6b35';
                        ctx.font = `bold ${fontSize}px Segoe UI, Arial`;
                        ctx.textAlign = 'center';
                        ctx.fillText('Thank you for ordering with us! 🙏', w/2, y);
                    }
                };
                img.src = qrSrc;
            }
        }
        
        function downloadCard() {
            if (!selectedTableId) {
                alert('Please select a table first by clicking "Preview & Download"');
                return;
            }
            
            const qrEl = document.querySelector('#qr_preview img');
            if (!qrEl) {
                alert('Please wait for QR code to load');
                return;
            }
            
            const canvas = document.getElementById('downloadCanvas');
            generateCard(canvas, qrEl.src);
            
            const link = document.createElement('a');
            const filename = 'QR_' + selectedTableName.replace(' ', '_') + '.' + currentFormat;
            
            if (currentFormat === 'jpg') {
                link.download = filename;
                link.href = canvas.toDataURL('image/jpeg', 0.9);
            } else {
                link.download = filename;
                link.href = canvas.toDataURL('image/png');
            }
            link.click();
        }
    </script>
</body>
</html>
