<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Query untuk mendapatkan daftar admin
$query_admin = "SELECT username FROM users WHERE role = 'admin'";
$result_admin = mysqli_query($conn, $query_admin);

// Simpan daftar admin dalam array untuk digunakan nanti jika "Semua Staf" dipilih
$admin_list = array();
while($row = mysqli_fetch_assoc($result_admin)) {
    $admin_list[] = $row['username'];
}

// Reset pointer hasil query untuk digunakan di dropdown
mysqli_data_seek($result_admin, 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = mysqli_real_escape_string($conn, $_POST["judul"]);
    $platform = mysqli_real_escape_string($conn, $_POST["platform"]);
    $deskripsi = mysqli_real_escape_string($conn, $_POST["deskripsi"]);
    $tanggal_mulai = $_POST["tanggal_mulai"];
    $deadline = $_POST["deadline"];
    $penanggung_jawab = $_POST["penanggung_jawab"];
    
    $success_count = 0;
    $error_messages = array();
    
    // Jika "Semua Staf" dipilih, buat tugas untuk setiap admin
    if ($penanggung_jawab === "semua_staf") {
        foreach ($admin_list as $admin) {
            $query = "INSERT INTO tugas_media (judul, platform, deskripsi, status, tanggal_mulai, deadline, penanggung_jawab, pemberi_tugas)
                       VALUES ('$judul', '$platform', '$deskripsi', 'Belum Dikerjakan', '$tanggal_mulai', '$deadline', '$admin', 'kabid')";
            
            if(mysqli_query($conn, $query)) {
                $success_count++;
            } else {
                $error_messages[] = "Error untuk $admin: " . mysqli_error($conn);
            }
        }
        
        if ($success_count == count($admin_list)) {
            $_SESSION['success'] = "Tugas berhasil ditambahkan untuk semua staf!";
        } else {
            $_SESSION['error'] = "Beberapa tugas gagal ditambahkan: " . implode(", ", $error_messages);
        }
    } else {
        // Jika admin tertentu dipilih, buat tugas hanya untuk admin tersebut
        $query = "INSERT INTO tugas_media (judul, platform, deskripsi, status, tanggal_mulai, deadline, penanggung_jawab, pemberi_tugas)
                   VALUES ('$judul', '$platform', '$deskripsi', 'Belum Dikerjakan', '$tanggal_mulai', '$deadline', '$penanggung_jawab', 'kabid')";
        
        if(mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Tugas berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    
    // Redirect untuk menghindari resubmission form
    header("Location: tambah_tugas_kabid.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Tugas</title>
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
        height: 100%;
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
    
    .success-message {
        color: white;
        text-align: center;
        margin-bottom: 15px;
        padding: 10px;
        background: #1cc88a;
        border-radius: 4px;
        animation: fadeIn 0.5s ease-in-out;
    }
    
    .error-message {
        color: white;
        text-align: center;
        margin-bottom: 15px;
        padding: 10px;
        background: #e74a3b;
        border-radius: 4px;
        animation: fadeIn 0.5s ease-in-out;
    }
    
    /* Form styling */
    .form-label {
        font-weight: 600;
        color: #5a5c69;
        margin-bottom: 0.5rem;
    }
    
    .form-control {
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(26, 71, 42, 0.25);
    }
    
    textarea.form-control {
        min-height: 120px;
    }
    
    .btn-primary {
        background-color: #1a472a;
        border-color: #1a472a;
    }
    
    .btn-primary:hover {
        background-color: #0f2c1a;
        border-color: #0f2c1a;
    }
    
    .all-staff-option {
        font-weight: bold;
        color: #1a472a;
    }
    
    /* Animasi */
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
            margin-left: var(--sidebar-collapsed-width);
        }
            
        .user-info .user-name {
            display: none;
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
                <a href="../modules/daftar_tugas_kabid.php" class="list-group-item">
                    <i class="bi bi-list-task"></i>
                <span>Daftar Tugas</span>
                <a href="../modules/tambah_tugas_kabid.php" class="list-group-item active">
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
                    <h1 class="h3 mb-4 text-gray-800 fade-in">Tambah Tugas Baru</h1>
                    
                    <!-- Menampilkan pesan sukses/error -->
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="row fade-in">
                            <div class="col-12">
                                <div class="success-message mb-4">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="row fade-in">
                            <div class="col-12">
                                <div class="error-message mb-4">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row fade-in">
                        <!-- Form Tambah Tugas -->
                        <div class="col-lg-7">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Form Tambah Tugas</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="mb-3">
                                            <label for="judul" class="form-label">Judul Tugas</label>
                                            <input type="text" class="form-control" id="judul" name="judul" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="platform" class="form-label">Kategori</label>
                                            <select class="form-select" id="platform" name="platform" required>
                                                <option value="" disabled selected>Pilih kategori</option>
                                                <option value="Pemberitahuan">Pemberitahuan</option>
                                                <option value="Tugas">Tugas</option>
                                                <option value="Laporan">Laporan</option>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="deskripsi" class="form-label">Deskripsi</label>
                                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required></textarea>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label for="deadline" class="form-label">Deadline</label>
                                                <input type="date" class="form-control" id="deadline" name="deadline" required>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                                            <select class="form-select" id="penanggung_jawab" name="penanggung_jawab" required>
                                                <option value="semua_staf" class="all-staff-option">
                                                    <i class="bi bi-people-fill"></i> Semua Staf
                                                </option>
                                                <?php mysqli_data_seek($result_admin, 0); ?>
                                                <?php while($row = mysqli_fetch_assoc($result_admin)): ?>
                                                    <option value="<?php echo htmlspecialchars($row['username']); ?>">
                                                        <?php echo htmlspecialchars($row['username']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-plus-circle-fill me-2"></i> Tambah Tugas
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                       <!-- Informasi dan Panduan -->
<div class="col-lg-5">
    <div class="card h-100">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold">Informasi & Panduan</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong>Catatan:</strong> Jika memilih "Semua Staf", tugas akan diberikan kepada setiap admin staf secara terpisah.
            </div>
            
            <h6 class="font-weight-bold mb-3">
                <i class="bi bi-list-check me-2"></i>Kategori Tugas:
            </h6>
            <div class="mb-4">
                <div class="d-flex align-items-center mb-2">
                    <!-- Badge dan ikon dihapus -->
                    <div>
                        <strong>Pemberitahuan</strong> - Informasi umum yang perlu diketahui oleh staf
                    </div>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <!-- Badge dan ikon dihapus -->
                    <div>
                        <strong>Tugas</strong> - Pekerjaan yang perlu diselesaikan oleh staf
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <!-- Badge dan ikon dihapus -->
                    <div>
                        <strong>Laporan</strong> - Permintaan laporan dari staf
                    </div>
                </div>
            </div>
            
            <h6 class="font-weight-bold mb-3">
                <i class="bi bi-lightbulb-fill me-2"></i>Tips Penugasan:
            </h6>
            <ul class="mb-4">
                <li class="mb-2">Berikan judul yang jelas dan spesifik</li>
                <li class="mb-2">Sertakan detail lengkap pada deskripsi tugas</li>
                <li class="mb-2">Tetapkan deadline yang realistis</li>
                <li>Pilih penanggung jawab yang sesuai dengan tugas</li>
            </ul>
            
            <div class="alert alert-warning">
                <i class="bi bi-calendar-check me-2"></i>
                <strong>Deadline:</strong> Pastikan memberikan waktu yang cukup untuk penyelesaian tugas.
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
            
            // Auto-hide messages after 5 seconds
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.style.display = 'none';
                    }, 500);
                }, 5000);
            });
            
            // Validasi tanggal
            const tanggalMulai = document.getElementById('tanggal_mulai');
            const deadline = document.getElementById('deadline');
            
            tanggalMulai.addEventListener('change', function() {
                deadline.min = this.value;
            });
            
            deadline.addEventListener('change', function() {
                if (tanggalMulai.value && this.value < tanggalMulai.value) {
                    alert('Deadline tidak boleh lebih awal dari tanggal mulai!');
                    this.value = tanggalMulai.value;
                }
            });
            
            // Set tanggal minimal hari ini
            const today = new Date().toISOString().split('T')[0];
            tanggalMulai.min = today;
            deadline.min = today;
        });
    </script>
</body>
</html>
