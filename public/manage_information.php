<?php
// Atur zona waktu
date_default_timezone_set('Asia/Jakarta');

// =========================================================================
// 1. SETUP DATABASE (MySQL / phpMyAdmin)
// =========================================================================
$db_host = '127.0.0.1';
$db_name = 'manajemen_ptnbl'; // Nama database kamu
$db_user = 'root';            // Username default XAMPP
$db_pass = '';                // Password kosong sesuai .env kamu

try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS catatan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tanggal DATETIME,
        isi_catatan TEXT,
        status VARCHAR(20) DEFAULT 'unhide'
    )");
} catch (PDOException $e) {
    die("Koneksi Gagal: " . $e->getMessage());
}

// =========================================================================
// 2. LOGIKA PROSES
// =========================================================================
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $stmt = $db->prepare("INSERT INTO catatan (tanggal, isi_catatan) VALUES (?, ?)");
        $stmt->execute([$_POST['tanggal'], $_POST['isi_catatan']]);
    } elseif ($action === 'update') {
        $stmt = $db->prepare("UPDATE catatan SET isi_catatan = ? WHERE id = ?");
        $stmt->execute([$_POST['isi_catatan'], $_POST['id']]);
    } elseif ($action === 'toggle_status') {
        $stmt = $db->prepare("UPDATE catatan SET status = CASE WHEN status = 'hide' THEN 'unhide' ELSE 'hide' END WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    } elseif ($action === 'delete') {
        $stmt = $db->prepare("DELETE FROM catatan WHERE id = ?");
        $stmt->execute([$_POST['id']]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM catatan WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}

$dataCatatan = $db->query("SELECT * FROM catatan ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Informasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #28a745;
            --dark-green: #218838;
            --light-green: #eafaf1;
        }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
        
        /* Header & Navigation */
        .header-area { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid var(--light-green); padding-bottom: 15px; }
        .btn-back { background: #6c757d; color: white; padding: 8px 15px; border-radius: 6px; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .btn-back:hover { background: #5a6268; }
        
        h2 { color: #2c3e50; margin: 0; font-size: 24px; }
        
        /* Form Styles */
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; }
        input[type="text"], textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 15px; transition: border 0.3s; }
        input[type="text"]:focus, textarea:focus { border-color: var(--primary-green); outline: none; box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1); }
        input[readonly] { background-color: #f8f9fa; cursor: not-allowed; color: #777; border-color: #eee; }
        
        /* Button Styles */
        button, .btn { padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary-green); color: white; }
        .btn-primary:hover { background: var(--dark-green); transform: translateY(-1px); }
        .btn-secondary { background: #e9ecef; color: #495057; text-decoration: none; }
        .btn-secondary:hover { background: #dee2e6; }

        /* Table Styles */
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 25px; border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
        th { background-color: var(--light-green); color: #155724; padding: 15px; text-align: left; font-weight: 600; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        
        /* Actions */
        .text-center { text-align: center; }
        .action-form { display: inline; }
        .action-btn { background: none; border: 1px solid transparent; font-size: 18px; cursor: pointer; padding: 6px; border-radius: 6px; transition: 0.2s; }
        .action-btn:hover { background: #f8f9fa; border-color: #ddd; }
        
        .icon-eye { color: #17a2b8; }
        .icon-edit { color: var(--primary-green); }
        .icon-trash { color: #dc3545; }
        
        .row-hidden td { background-color: #fdfdfe; color: #adb5bd; }
        .row-hidden .isi-teks { text-decoration: line-through; }
        .status-badge { font-size: 11px; padding: 2px 8px; border-radius: 12px; font-weight: bold; text-transform: uppercase; }
        .status-unhide { background: #d4edda; color: #155724; }
        .status-hide { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
        <a href="/beranda" class="btn-back">
            <i class="fa fa-arrow-left"></i> Kembali
        </a>
        <h2><?= $editData ? '✏️ Edit Informasi' : '📝 Buat Informasi Baru' ?></h2>
        <div style="width: 80px;"></div> </div>
    
    <form method="POST">
        <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
        <?php if($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Tanggal Pembuatan</label>
            <input type="text" name="tanggal" value="<?= $editData ? htmlspecialchars($editData['tanggal']) : date('Y-m-d H:i:s') ?>" readonly>
        </div>

        <div class="form-group">
            <label>Isi Informasi / Catatan</label>
            <textarea name="isi_catatan" rows="4" required placeholder="Masukkan detail informasi di sini..."><?= $editData ? htmlspecialchars($editData['isi_catatan']) : '' ?></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> <?= $editData ? 'Perbarui Informasi' : 'Simpan Informasi' ?>
            </button>
            <?php if($editData): ?>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Batal Edit</a>
            <?php endif; ?>
        </div>
    </form>

    <div style="margin-top: 40px;">
        <h3 style="color: #2c3e50; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
            <i class="fa fa-list-ul" style="color: var(--primary-green);"></i> Riwayat Informasi
        </h3>
        <table>
            <thead>
                <tr>
                    <th width="22%">Waktu</th>
                    <th width="53%">Detail Informasi</th>
                    <th width="25%" class="text-center">Opsi Kendali</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($dataCatatan) > 0): ?>
                    <?php foreach($dataCatatan as $row): ?>
                        <tr class="<?= $row['status'] === 'hide' ? 'row-hidden' : '' ?>">
                            <td style="font-size: 13px; color: #666; font-weight: 500;">
                                <?= date('d M Y', strtotime($row['tanggal'])) ?><br>
                                <span style="font-size: 11px;"><?= date('H:i', strtotime($row['tanggal'])) ?> WIB</span>
                            </td>
                            <td>
                                <div class="isi-teks"><?= nl2br(htmlspecialchars($row['isi_catatan'])) ?></div>
                                <div style="margin-top: 8px;">
                                    <span class="status-badge <?= $row['status'] === 'hide' ? 'status-hide' : 'status-unhide' ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="action-btn icon-eye" title="<?= $row['status'] === 'hide' ? 'Tampilkan' : 'Sembunyikan' ?>">
                                        <i class="fa <?= $row['status'] === 'hide' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                    </button>
                                </form>

                                <a href="?edit=<?= $row['id'] ?>" class="action-btn icon-edit" title="Edit Data">
                                    <i class="fa fa-pencil-alt"></i>
                                </a>

                                <form method="POST" class="action-form" onsubmit="return confirm('Hapus informasi ini secara permanen?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="action-btn icon-trash" title="Hapus Data">
                                        <i class="fa fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center" style="padding: 40px; color: #999;">
                            <i class="fa fa-folder-open" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>
                            Belum ada data informasi yang tersimpan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>