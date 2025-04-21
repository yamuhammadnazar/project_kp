<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];
$id = (int)$_GET['id'];
$query = "SELECT * FROM tugas_media WHERE id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tugas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --topbar-height: 60px;
            --primary-color: rgb(79, 136, 235);
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
            background: linear-gradient(180deg, #1a5276 0%, #154360 100%);
            transition: all var(--transition-speed) ease;
            z-index: 1000;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            overflow-y: auto;
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
            content: "MS";
            font-size: 1.2rem;
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
        
        #sidebar-wrapper.collapsed .list-group-item span {
            display: none;
        }
        
        #sidebar-wrapper.collapsed .list-group-item i {
            margin-right: 0;
            font-size: 1.2rem;
        }
        
        #page-content-wrapper {
            width: 100%;
            margin-left: var(--sidebar-width);
            transition: margin var(--transition-speed) ease;
        }
        
        #wrapper.toggled #page-content-wrapper {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        /* Overlay untuk mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
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
                position: fixed;
                top: 0;
                left: 0;
                width: 250px !important;
                height: 100%;
                z-index: 1000;
                transition: transform 0.3s ease;
            }
            
            #sidebar-wrapper.show {
                transform: translateX(0);
            }
            
            #page-content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
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
            
            #page-content-wrapper {
                margin-left: var(--sidebar-width);
                transition: margin var(--transition-speed) ease;
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
            z-index: 999;
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
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #4e73df;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        textarea.form-control {
            min-height: 120px;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .btn-secondary:hover {
            background-color: #717384;
            border-color: #6b6d7d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Media Staff</div>
            <div class="list-group">
                <a href="../dashboard/admin_dashboard.php" class="list-group-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/daftar_tugas_admin.php" class="list-group-item active">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
                </a>
                <a href="../modules/tambah_tugas_anggota.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../modules/kelola_akun.php" class="list-group-item">
                    <i class="bi bi-people"></i>
                    <span>Kelola Akun Anggota</span>
                </a>
                <a href="../auth/register.php" class="list-group-item">
                    <i class="bi bi-person-plus"></i>
                    <span>Tambah Anggota</span>
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
                    <i class="bi bi-list"></i>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    <div class="dropdown">
                        <a class="dropdown-toggle text-decoration-none text-dark" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <span><?php echo $username; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Edit Tugas</h1>
                        <a href="../modules/daftar_tugas_admin.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Tugas
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-pencil-square me-1"></i> Form Edit Tugas
                        </div>
                        <div class="card-body">
                            <?php if(isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger">
                                    <?php 
                                        echo $_SESSION['error'];
                                        unset($_SESSION['error']);
                                    ?>
                                </div>
                            <?php endif; ?>

                             <form action="../controllers/proses_edit.php" method="POST">
                                <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                                
                                <div class="mb-3">
                                    <label for="judul" class="form-label">Judul</label>
                                    <input type="text" class="form-control" id="judul" name="judul" value="<?php echo htmlspecialchars($data['judul']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="platform" class="form-label">Platform</label>
                                    <select class="form-select" id="platform" name="platform" required>
                                        <option value="Instagram" <?php echo ($data['platform'] == 'Instagram') ? 'selected' : ''; ?>>Instagram</option>
                                        <option value="Facebook" <?php echo ($data['platform'] == 'Facebook') ? 'selected' : ''; ?>>Facebook</option>
                                        <option value="Twitter" <?php echo ($data['platform'] == 'Twitter') ? 'selected' : ''; ?>>Twitter</option>
                                        <option value="TikTok" <?php echo ($data['platform'] == 'TikTok') ? 'selected' : ''; ?>>TikTok</option>
                                        <option value="YouTube" <?php echo ($data['platform'] == 'YouTube') ? 'selected' : ''; ?>>YouTube</option>
                                        <option value="LinkedIn" <?php echo ($data['platform'] == 'LinkedIn') ? 'selected' : ''; ?>>LinkedIn</option>
                                        <option value="Website" <?php echo ($data['platform'] == 'Website') ? 'selected' : ''; ?>>Website</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required><?php echo htmlspecialchars($data['deskripsi']); ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Belum Dikerjakan" <?php echo ($data['status'] == 'Belum Dikerjakan') ? 'selected' : ''; ?>>Belum Dikerjakan</option>
                                        <option value="Sedang Dikerjakan" <?php echo ($data['status'] == 'Sedang Dikerjakan') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                        <option value="Selesai" <?php echo ($data['status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $data['tanggal_mulai']; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="deadline" class="form-label">Deadline</label>
                                        <input type="date" class="form-control" id="deadline" name="deadline" value="<?php echo $data['deadline']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="link_drive" class="form-label">Link Drive</label>
                                    <input type="text" class="form-control" id="link_drive" name="link_drive" value="<?php echo htmlspecialchars($data['link_drive']); ?>" placeholder="Masukkan link Google Drive">
                                </div>
                                
                                <div class="d-flex mt-4">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-save me-1"></i> Update Tugas
                                    </button>
                                    <a href="../modules/daftar_tugas_admin.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-1"></i> Batal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay untuk mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Menunggu dokumen selesai dimuat
        document.addEventListener('DOMContentLoaded', function() {
            // Menambahkan class 'loaded' ke body setelah halaman dimuat
            document.body.classList.add('loaded');
            
            // Toggle sidebar
            const menuToggle = document.getElementById('menu-toggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const pageContentWrapper = document.getElementById('page-content-wrapper');
            const sidebarOverlay = document.querySelector('.sidebar-overlay');
            
            // Fungsi untuk toggle sidebar
            function toggleSidebar() {
                if (window.innerWidth < 768) {
                    // Mobile behavior
                    sidebarWrapper.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                } else {
                    // Desktop behavior
                    sidebarWrapper.classList.toggle('collapsed');
                    pageContentWrapper.classList.toggle('expanded');
                }
            }
            
            // Event listener untuk tombol toggle
            menuToggle.addEventListener('click', toggleSidebar);
            
            // Event listener untuk overlay (hanya untuk mobile)
            sidebarOverlay.addEventListener('click', function() {
                sidebarWrapper.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
            
            // Menyesuaikan tampilan saat resize window
            window.addEventListener('resize', function() {
                if (window.innerWidth < 768) {
                    // Mobile view
                    sidebarWrapper.classList.remove('collapsed');
                    pageContentWrapper.classList.remove('expanded');
                    if (sidebarWrapper.classList.contains('show')) {
                        sidebarOverlay.classList.add('show');
                    }
                } else {
                    // Desktop view
                    sidebarOverlay.classList.remove('show');
                    sidebarWrapper.classList.remove('show');
                }
            });
            
            // Validasi form sebelum submit
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const tanggalMulai = new Date(document.getElementById('tanggal_mulai').value);
                const deadline = new Date(document.getElementById('deadline').value);
                
                if (deadline < tanggalMulai) {
                    event.preventDefault();
                    alert('Deadline tidak boleh lebih awal dari tanggal mulai!');
                }
            });
        });
    </script>
</body>
</html>
