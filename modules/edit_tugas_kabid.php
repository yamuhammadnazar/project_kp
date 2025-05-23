<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Cek apakah ada ID tugas yang dikirim
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../modules/daftar_tugas_kabid.php");
    exit();
}

$id_tugas = $_GET['id'];

// Ambil data tugas berdasarkan ID
$query = "SELECT * FROM tugas_media WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_tugas);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: ../modules/daftar_tugas_kabid.php");
    exit();
}

$tugas = mysqli_fetch_assoc($result);

// Ambil daftar admin untuk dropdown
$admin_query = "SELECT username FROM users WHERE role = 'admin' ORDER BY username";
$admin_result = mysqli_query($conn, $admin_query);

// Proses form jika disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = $_POST['judul'];
    $platform = $_POST['platform']; // Ini sekarang adalah kategori
    $deskripsi = $_POST['deskripsi'];
    $tanggal_mulai = $_POST['tanggal_mulai'];
    $deadline = $_POST['deadline'];
    $penanggung_jawab = $_POST['penanggung_jawab'];
    $status = $_POST['status'];
    
    // Gunakan nilai yang sudah ada di database untuk field yang dihapus
    $link_drive = $tugas['link_drive'];

    // Update data tugas
    $update_query = "UPDATE tugas_media SET 
                judul = ?, 
                platform = ?, 
                deskripsi = ?, 
                tanggal_mulai = ?, 
                deadline = ?, 
                link_drive = ?, 
                penanggung_jawab = ?, 
                status = ? 
                WHERE id = ?";
    
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param(
    $update_stmt, 
    "ssssssssi",
    $judul, 
    $platform, 
    $deskripsi, 
    $tanggal_mulai, 
    $deadline, 
    $link_drive, 
    $penanggung_jawab, 
    $status, 
    $id_tugas
);
    
    if (mysqli_stmt_execute($update_stmt)) {
        // Redirect ke halaman daftar tugas dengan pesan sukses
        header("Location: ../modules/daftar_tugas_kabid.php?success=1");
        exit();
    } else {
        $error_message = "Gagal mengupdate tugas: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tugas - Kepala Bidang</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Animate.css untuk animasi -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <style>
    :root {
        --sidebar-width: 280px;
        --sidebar-collapsed-width: 80px;
        --topbar-height: 60px;
        --primary-color: rgb(25, 77, 51); /* Warna utama hijau gelap */
        --secondary-color: #f8f9fc;
        --transition-speed: 0.3s;
    }
    
    body {
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background-color: #f8f9fc;
        overflow-x: hidden;
        opacity: 0;
        transition: opacity 0.5s ease;
    }
    
    body.loaded {
        opacity: 1;
    }
    
    #wrapper {
        display: flex;
    }
    
    #sidebar-wrapper {
        min-height: 100vh;
        width: var(--sidebar-width);
        background: linear-gradient(180deg, #1a472a 0%, #2d5a40 100%); /* Warna sidebar hijau gelap */
        transition: all var(--transition-speed) ease;
        z-index: 1000;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        position: fixed;
        left: 0;
        top: 0;
        height: 100%;
        overflow-y: auto;
        overflow-x: hidden; /* Mencegah horizontal scroll */
    }
    
    #sidebar-wrapper.collapsed {
        width: var(--sidebar-collapsed-width);
    }
    
    #sidebar-wrapper .sidebar-heading {
        padding: 1.2rem 1rem;
        font-size: 1.2rem;
        font-weight: 700;
        color: white;
        text-align: center;
        transition: all var(--transition-speed) ease;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        height: var(--topbar-height);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    #sidebar-wrapper.collapsed .sidebar-heading {
        font-size: 0;
        padding: 1.2rem 0;
    }
    
    #sidebar-wrapper.collapsed .sidebar-heading::before {
        content: "KB";
        font-size: 1.2rem;
    }
    
    #sidebar-wrapper .list-group {
        width: 100%;
        padding: 1rem 0;
    }
    
    #sidebar-wrapper .list-group-item {
        border: none;
        background: transparent;
        color: rgba(255, 255, 255, 0.9); /* Lebih terang untuk keterbacaan */
        padding: 0.8rem 1.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
        margin-bottom: 0.2rem;
    }
    
    #sidebar-wrapper .list-group-item:hover {
        background-color: rgba(255, 255, 255, 0.15);
        color: white;
        border-left: 4px solid white;
    }
    
    #sidebar-wrapper .list-group-item.active {
        background-color: rgba(255, 255, 255, 0.25);
        color: white;
        border-left: 4px solid white;
    }
    
    #sidebar-wrapper .list-group-item i {
        margin-right: 1rem;
        font-size: 1.1rem;
        min-width: 20px;
        text-align: center;
    }
    
    #sidebar-wrapper.collapsed .list-group-item span {
        display: none;
    }
    
    #sidebar-wrapper.collapsed .list-group-item {
        text-align: center;
        padding: 0.8rem;
        justify-content: center;
    }
    
    #sidebar-wrapper.collapsed .list-group-item i {
        margin-right: 0;
        font-size: 1.2rem;
    }
    
    #content-wrapper {
        width: 100%;
        margin-left: var(--sidebar-width);
        transition: all var(--transition-speed) ease;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    #content-wrapper.expanded {
        margin-left: var(--sidebar-collapsed-width);
    }
    
    #topbar {
        height: var(--topbar-height);
        background-color: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        padding: 0 1.5rem;
        position: sticky;
        top: 0;
        z-index: 999;
    }
    
    #sidebarToggle {
        background-color: transparent;
        border: none;
        color: #1a472a; /* Warna hijau gelap */
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    #sidebarToggle:hover {
        color: #2d5a40; /* Warna hijau medium */
    }
    
    .user-info {
        margin-left: auto;
        display: flex;
        align-items: center;
    }
    
    .user-info .user-name {
        margin-right: 1rem;
        font-weight: 600;
        color: #333;
    }
    
    .user-info .btn-logout {
        background-color: #f8f9fc;
        border: 1px solid #ddd;
        color: #333;
        transition: all 0.2s ease;
    }
    
    .user-info .btn-logout:hover {
        background-color: #f1f1f1;
        color: #e74a3b;
    }
    
    .content {
        padding: 1.5rem;
        flex-grow: 1;
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        margin-bottom: 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .card-header {
        background-color: white;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.25rem;
        font-weight: 700;
        color: #5a5c69;
        border-top-left-radius: 10px !important;
        border-top-right-radius: 10px !important;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-body {
        padding: 1.25rem;
    }
    
    .btn-primary {
        background-color: #1a472a; /* Hijau gelap */
        border-color: #1a472a;
    }
    
    .btn-primary:hover {
        background-color: #0f2c1a; /* Hijau sangat gelap */
        border-color: #0f2c1a;
    }
    
    .btn-outline-primary {
        color: #1a472a; /* Hijau gelap */
        border-color: #1a472a;
    }
    
    .btn-outline-primary:hover {
        background-color: #1a472a;
        border-color: #1a472a;
        color: white;
    }
    
    /* Form styling */
    .form-label {
        font-weight: 600;
        color: #5a5c69;
    }
    
    .form-control, .form-select {
        border-radius: 5px;
        border: 1px solid #d1d3e2;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(26, 71, 42, 0.25); /* Hijau gelap dengan opacity */
    }
    
    /* Status badges */
    .badge-belum {
        background-color: #e74a3b;
        color: white;
    }
    
    .badge-sedang {
        background-color: #b35900; /* Oranye gelap */
        color: white;
    }
    
    .badge-kirim {
        background-color: #cc4b2c; /* Coral gelap */
        color: white;
    }
    
    .badge-revisi {
        background-color: #d9510c; /* Oranye kemerahan gelap */
        color: white;
    }
    
    .badge-selesai {
        background-color: #1cc88a;
        color: white;
    }
    
    /* Fade-in animation */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Responsiveness */
    @media (max-width: 768px) {
        #sidebar-wrapper {
            width: var(--sidebar-collapsed-width);
        }
        
        #sidebar-wrapper .sidebar-heading {
            font-size: 0;
            padding: 1.2rem 0;
        }
        
        #sidebar-wrapper .sidebar-heading::before {
            content: "KB";
            font-size: 1.2rem;
        }
        
        #sidebar-wrapper .list-group-item span {
            display: none;
        }
        
        #sidebar-wrapper .list-group-item {
            text-align: center;
            padding: 0.8rem;
            justify-content: center;
        }
        
        #sidebar-wrapper .list-group-item i {
            margin-right: 0;
            font-size: 1.2rem;
        }
        
        #content-wrapper {
            margin-left: var(
--sidebar-collapsed-width);
        }
        
        .user-info .user-name {
            display: none;
        }
    }
    
    @media (max-width: 576px) {
        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .card-header .btn {
            margin-top: 0.5rem;
            align-self: flex-end;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
    }
    
    /* Scrollbar styling */
    ::-webkit-scrollbar {
        width: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #1a472a; /* Hijau gelap */
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #0f2c1a; /* Hijau sangat gelap */
    }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Dashboard Kepala Bidang</div>
            <div class="list-group">
                <a href="../dashboard/kabid_dashboard.php" class="list-group-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/daftar_tugas_kabid.php" class="list-group-item active">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
                </a>
                <a href="../modules/tambah_tugas_kabid.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../auth/register_admin.php" class="list-group-item">
                    <i class="bi bi-person-plus"></i>
                    <span>Tambah Admin Staf</span>
                </a>
                <a href="../modules/kelola_admin.php" class="list-group-item">
                    <i class="bi bi-people"></i>
                    <span>Kelola Admin Staff</span>
                </a>
                <a href="../auth/logout.php" class="list-group-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </div>
        
        <!-- Content Wrapper -->
        <div id="content-wrapper">
            <!-- Top Navigation -->
            <nav id="topbar">
                <button id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="user-info">
                    <span class="user-name"><?php echo $username; ?></span>
                    <a href="../auth/logout.php" class="btn btn-sm btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800 fade-in">Edit Tugas</h1>
                    
                    <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger fade-in" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card fade-in">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Form Edit Tugas</h6>
                            <a href="../modules/daftar_tugas_kabid.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Kembali ke Daftar Tugas
                            </a>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="judul" class="form-label">Judul Tugas</label>
                                            <input type="text" class="form-control" id="judul" name="judul" value="<?php echo htmlspecialchars($tugas['judul']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="platform" class="form-label">Kategori</label>
                                            <select class="form-select" id="platform" name="platform" required>
                                                <option value="">Pilih Kategori</option>
                                                <option value="Pemberitahuan" <?php echo ($tugas['platform'] == 'Pemberitahuan') ? 'selected' : ''; ?>>Pemberitahuan</option>
                                                <option value="Tugas" <?php echo ($tugas['platform'] == 'Tugas') ? 'selected' : ''; ?>>Tugas</option>
                                                <option value="Laporan" <?php echo ($tugas['platform'] == 'Laporan') ? 'selected' : ''; ?>>Laporan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo htmlspecialchars($tugas['deskripsi']); ?></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $tugas['tanggal_mulai']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="deadline" class="form-label">Deadline</label>
                                            <input type="date" class="form-control" id="deadline" name="deadline" value="<?php echo $tugas['deadline']; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                                            <select class="form-select" id="penanggung_jawab" name="penanggung_jawab" required>
                                                <option value="">Pilih Admin</option>
                                                <?php while ($admin = mysqli_fetch_assoc($admin_result)): ?>
                                                <option value="<?php echo $admin['username']; ?>" <?php echo ($tugas['penanggung_jawab'] == $admin['username']) ? 'selected' : ''; ?>>
                                                    <?php echo $admin['username']; ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="Belum Dikerjakan" <?php echo ($tugas['status'] == 'Belum Dikerjakan') ? 'selected' : ''; ?>>Belum Dikerjakan</option>
                                                <option value="Sedang Dikerjakan" <?php echo ($tugas['status'] == 'Sedang Dikerjakan') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                                <option value="Kirim" <?php echo ($tugas['status'] == 'Kirim') ? 'selected' : ''; ?>>Kirim</option>
                                                <option value="Revisi" <?php echo ($tugas['status'] == 'Revisi') ? 'selected' : ''; ?>>Revisi</option>
                                                <option value="Selesai" <?php echo ($tugas['status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="../modules/daftar_tugas_kabid.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menandai body sudah dimuat
            document.body.classList.add('loaded');
            
            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const contentWrapper = document.getElementById('content-wrapper');
            
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebarWrapper.classList.toggle('collapsed');
                contentWrapper.classList.toggle('expanded');
            });
            
            // Responsive behavior
            function checkWidth() {
                if (window.innerWidth < 768) {
                    sidebarWrapper.classList.add('collapsed');
                    contentWrapper.classList.add('expanded');
                } else {
                    sidebarWrapper.classList.remove('collapsed');
                    contentWrapper.classList.remove('expanded');
                }
            }
            
            // Check on load
            checkWidth();
            
            // Check on resize
            window.addEventListener('resize', checkWidth);
        });
    </script>
</body>
</html>
