<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Periksa apakah tabel tugas sudah ada
$table_exists_query = "SHOW TABLES LIKE 'tugas'";
$table_exists_result = mysqli_query($conn, $table_exists_query);
$table_exists = mysqli_num_rows($table_exists_result) > 0;

// Query untuk mendapatkan daftar anggota
if ($table_exists) {
    // Jika tabel tugas ada, ambil data dengan jumlah tugas
    $query_anggota = "SELECT u.username, COUNT(t.id) as jumlah_tugas 
                     FROM users u 
                     LEFT JOIN tugas t ON u.username = t.penanggung_jawab AND t.status != 'Selesai'
                     WHERE u.role = 'anggota' 
                     GROUP BY u.username
                     ORDER BY jumlah_tugas ASC";
} else {
    // Jika tabel tugas belum ada, ambil hanya username
    $query_anggota = "SELECT username, 0 as jumlah_tugas FROM users WHERE role = 'anggota'";
}

$anggota_list_result = mysqli_query($conn, $query_anggota);

// Jika query gagal, gunakan query sederhana sebagai fallback
if (!$anggota_list_result) {
    $query_anggota_fallback = "SELECT username FROM users WHERE role = 'anggota'";
    $anggota_list_result = mysqli_query($conn, $query_anggota_fallback);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Tambah Tugas Baru</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Animate.css untuk animasi -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Custom CSS -->
    <style>
        /* CSS tetap sama seperti sebelumnya */
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --topbar-height: 60px;
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
            overflow-x: hidden;
            opacity: 0;
            transition: opacity 0.5s ease;
            min-height: 100vh;
        }
        
        body.loaded {
            opacity: 1;
        }
        
        #wrapper {
            display: flex;
            position: relative;
            min-height: 100vh;
        }
        
        #sidebar-wrapper {
            min-height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a5276 0%, #154360 100%);
            transition: all var(--transition-speed) ease;
            z-index: 1050;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            overflow-y: auto;
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
        
        #sidebar-wrapper .list-group {
            width: 100%;
            padding: 1rem 0;
        }
        
        #sidebar-wrapper .list-group-item {
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.5rem;
            border-radius: 0;
            display: flex;
            align-items: center;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }
        
        #sidebar-wrapper .list-group-item:before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: white;
            transform: translateX(-4px);
            transition: transform 0.2s;
        }
        
        #sidebar-wrapper .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        #sidebar-wrapper .list-group-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        #sidebar-wrapper .list-group-item.active:before {
            transform: translateX(0);
        }
        
        #sidebar-wrapper .list-group-item i {
            margin-right: 1rem;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            transition: margin var(--transition-speed);
        }
        
        #page-content-wrapper {
            width: 100%;
            margin-left: var(--sidebar-width);
            transition: margin var(--transition-speed) ease;
            flex: 1;
        }
        
        /* Overlay untuk mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
            transition: all 0.3s;
        }
        
        .sidebar-overlay.show {
            display: block;
        }
        
        /* Mobile sidebar behavior */
        @media (max-width: 767.98px) {
            #sidebar-wrapper {
                transform: translateX(-100%);
                width: 250px !important;
            }
            
            #sidebar-wrapper.show {
                transform: translateX(0);
            }
            
            #page-content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .topbar {
                padding-left: 1rem !important;
                padding-right: 1rem !important;
            }
            
            .content {
                padding: 1rem !important;
            }
            
            .card-body {
                padding: 1rem !important;
            }
            
            /* Stack form fields on mobile */
            .row > [class*="col-"] {
                margin-bottom: 0.5rem;
            }
        }
        
        /* Tablet behavior */
        @media (min-width: 768px) and (max-width: 991.98px) {
            #sidebar-wrapper {
                width: 200px;
            }
            
            #page-content-wrapper {
                margin-left: 200px;
            }
            
            #page-content-wrapper.expanded {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .content {
                padding: 1.25rem;
            }
        }
        
        /* Desktop sidebar behavior */
        @media (min-width: 768px) {
            #sidebar-wrapper {
                transform: translateX(0);
            }
            
            #sidebar-wrapper.collapsed {
                width: var(--sidebar-collapsed-width);
            }
            
            #sidebar-wrapper.collapsed .sidebar-heading {
                font-size: 0;
                padding: 1.2rem 0;
            }
            
            #sidebar-wrapper.collapsed .sidebar-heading::before {
                content: "MA";
                font-size: 1.2rem;
            }
            
            #sidebar-wrapper.collapsed .list-group-item span {
                display: none;
            }
            
            #sidebar-wrapper.collapsed .list-group-item i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            #page-content-wrapper.expanded {
                margin-left: var(--sidebar-collapsed-width);
            }
        }
        
        .topbar {
            height: var(--topbar-height);
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        
        .content {
            padding: 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        /* Form styling - more compact */
        .form-label {
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }
        
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #e3e6f0;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        textarea.form-control {
            min-height: 80px;
        }
        
        .btn {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
        }
        
        .btn-secondary:hover {
            background-color: #717384;
            border-color: #6b6d7d;
        }
        
        /* Compact form layout */
        .form-group {
            margin-bottom: 0.75rem;
        }
        
        .row {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }
        
        .row > [class*="col-"] {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }
        
        /* Fix untuk input date di Safari */
        input[type="date"] {
            -webkit-appearance: none;
            appearance: none;
        }
        
        /* Animasi untuk card */
        @media (min-width: 992px) {
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            }
        }
        
        /* Tabs styling */
        .nav-tabs {
            border-bottom: 1px solid #e3e6f0;
            margin-bottom: 1rem;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #858796;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 0;
            margin-right: 0.5rem;
        }
        
        .nav-tabs .nav-link:hover {
            color: #4e73df;
            border-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: #4e73df;
            background-color: transparent;
            border-bottom: 3px solid #4e73df;
        }
        
        /* Styling untuk badge jumlah tugas */
        .task-count {
            display: inline-block;
            background-color: #f8f9fc;
            color: #4e73df;
            border-radius: 10px;
            padding: 0.2rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Media Admin</div>
            <div class="list-group">
                <a href="../dashboard/admin_dashboard.php" class="list-group-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/daftar_tugas_admin.php" class="list-group-item">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
                </a>
                <a href="../modules/tambah_tugas_anggota.php" class="list-group-item active">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../modules/kelola_akun.php" class="list-group-item">
                    <i class="bi bi-people"></i>
                    <span>Kelola Akun Anggota</span>
                </a>
                <a href="../modules/ganti_password.php" class="list-group-item">
                    <i class="bi bi-key"></i>
                    <span>Ganti Password</span>
                </a>
                <a href="../auth/logout.php" class="list-group-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <div class="topbar">
                <button class="btn btn-link" id="menu-toggle">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <a class="dropdown-toggle text-decoration-none text-dark" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <span class="d-none d-sm-inline"><?php echo $username; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="../modules/profil.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <h1 class="h3 mb-0 text-gray-800">Tambah Tugas Baru</h1>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <i class="bi bi-plus-circle me-2"></i> Form Tambah Tugas
                        </div>
                        <div class="card-body">
                            <!-- Nav tabs untuk form yang lebih ringkas -->
                            <ul class="nav nav-tabs" id="taskTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                                        <i class="bi bi-info-circle me-1"></i> Informasi Dasar
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">
                                        <i class="bi bi-calendar-date me-1"></i> Jadwal
                                    </button>
                                </li>
                            </ul>

                            <form action="../controllers/proses_tambah_anggota.php" method="POST" class="mt-3">
                                <div class="tab-content" id="taskTabsContent">
                                    <!-- Tab 1: Informasi Dasar -->
                                    <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="judul" class="form-label">Judul Tugas</label>
                                                <input type="text" class="form-control" id="judul" name="judul" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="platform" class="form-label">Platform</label>
                                                <select class="form-select" id="platform" name="platform" required>
                                                    <option value="">Pilih Platform</option>
                                                    <option value="Instagram">Instagram</option>
                                                    <option value="Facebook">Facebook</option>
                                                    <option value="Twitter">Twitter</option>
                                                    <option value="TikTok">TikTok</option>
                                                    <option value="YouTube">YouTube</option>
                                                    <option value="LinkedIn">LinkedIn</option>
                                                    <option value="Website">Website</option>
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" required></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="penanggung_jawab" class="form-label">Penanggung Jawab</label>
                                                <select class="form-select" id="penanggung_jawab" name="penanggung_jawab" required>
                                                    <option value="">Pilih Penanggung Jawab</option>
                                                    <?php 
                                                    mysqli_data_seek($anggota_list_result, 0);
                                                    while($row = mysqli_fetch_assoc($anggota_list_result)):
                                                        // Jika tabel tugas ada, tampilkan jumlah tugas
                                                        if ($table_exists && isset($row['jumlah_tugas'])) {
                                                            $jumlah_tugas = intval($row['jumlah_tugas']);
                                                            $task_info = " <span class='task-count'>{$jumlah_tugas} tugas</span>";
                                                        } else {
                                                            $task_info = "";
                                                        }
                                                    ?>
                                                        <option value="<?php echo htmlspecialchars($row['username']); ?>">
                                                            <?php echo htmlspecialchars($row['username']) . $task_info; ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-select" id="status" name="status" required>
                                                    <option value="Belum Dikerjakan">Belum Dikerjakan</option>
                                                    <option value="Sedang Dikerjakan">Sedang Dikerjakan</option>
                                                    <option value="Kirim">Kirim</option>
                                                </select>
                                            </div>
                                            <div class="col-12 mt-3">
                                                <button type="button" class="btn btn-primary next-tab">
                                                    Lanjut <i class="bi bi-arrow-right ms-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tab 2: Detail Tambahan (Jadwal) -->
                                    <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="deadline" class="form-label">Deadline</label>
                                                <input type="date" class="form-control" id="deadline" name="deadline" required>
                                            </div>
                                            <!-- Field link_drive dihapus karena akan diisi oleh anggota -->
                                            <input type="hidden" name="pemberi_tugas" value="<?php echo $username; ?>">
                                            <input type="hidden" name="link_drive" value=""> <!-- Tetap kirim nilai kosong -->
                                            <div class="col-12 d-flex mt-3">
                                                <button type="button" class="btn btn-secondary prev-tab me-2">
                                                    <i class="bi bi-arrow-left me-1"></i> Kembali
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save me-1"></i> Simpan Tugas
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('loaded');
            
            const menuToggle = document.getElementById('menu-toggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const pageContentWrapper = document.getElementById('page-content-wrapper');
            
            // Tambahkan overlay untuk mobile
            const overlay = document.querySelector('.sidebar-overlay');
            
            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (window.innerWidth < 768) {
                    // Mobile behavior - show/hide dengan overlay
                    sidebarWrapper.classList.toggle('show');
                    overlay.classList.toggle('show');
                } else {
                    // Desktop behavior - collapse/expand
                    sidebarWrapper.classList.toggle('collapsed');
                    pageContentWrapper.classList.toggle('expanded');
                }
            });
            
            // Tutup sidebar saat overlay diklik (untuk mobile)
            overlay.addEventListener('click', function() {
                sidebarWrapper.classList.remove('show');
                overlay.classList.remove('show');
            });
            
            // Responsive behavior
            function checkWidth() {
                if (window.innerWidth < 768) {
                    // Reset untuk mobile view
                    sidebarWrapper.classList.remove('collapsed');
                    pageContentWrapper.classList.remove('expanded');
                    
                    // Jika sidebar sedang terbuka di mobile, tampilkan overlay
                    if (sidebarWrapper.classList.contains('show')) {
                        overlay.classList.add('show');
                    }
                } else {
                    // Reset untuk desktop view
                    overlay.classList.remove('show');
                    sidebarWrapper.classList.remove('show');
                }
            }
            
            // Initial check
            checkWidth();
            
            // Check on resize dengan throttling untuk performa
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(checkWidth, 100);
            });
            
            // Tab navigation
            const nextTabButtons = document.querySelectorAll('.next-tab');
            const prevTabButtons = document.querySelectorAll('.prev-tab');
            
            nextTabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const detailsTab = new bootstrap.Tab(document.getElementById('details-tab'));
                    detailsTab.show();
                });
            });
            
            prevTabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const infoTab = new bootstrap.Tab(document.getElementById('info-tab'));
                    infoTab.show();
                });
            });
            
            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const tanggalMulai = new Date(document.getElementById('tanggal_mulai').value);
                const deadline = new Date(document.getElementById('deadline').value);
                
                if (deadline < tanggalMulai) {
                    event.preventDefault();
                    alert('Deadline tidak boleh lebih awal dari tanggal mulai!');
                }
            });
            
            // Fix untuk input date di Safari
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.addEventListener('click', function() {
                    if (this.type === 'date' && /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream) {
                        this.type = 'date';
                        this.click();
                    }
                });
            });
        });
    </script>
</body>
</html>
