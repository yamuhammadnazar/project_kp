<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION['username'];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$query = "SELECT * FROM users WHERE 
          ((role = 'anggota' AND username LIKE '%$search%') 
          OR (role = 'admin' AND username = '$username')) 
          ORDER BY role ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Kelola Akun</title>
    
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
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
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
            padding: 1.5rem;
        }
        
        /* Table styling */
        .table {
            width: 100%;
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            color: #4e73df;
            border-top: none;
            background-color: #f8f9fc;
        }
        
        .table td, .table th {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        /* Badge styling */
        .badge {
            font-weight: 600;
            padding: 0.5em 0.75em;
            border-radius: 0.25rem;
        }
        
        .badge-admin {
            background-color: #4e73df;
            color: white;
        }
        
        .badge-anggota {
            background-color: #1cc88a;
            color: white;
        }
        
        /* Button styling */
        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1rem;
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
        
        .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }
        
        .btn-danger:hover {
            background-color: #d52a1a;
            border-color: #c9221a;
        }
        
        .btn-warning {
            background-color: #f6c23e;
            border-color: #f6c23e;
            color: #fff;
        }
        
        .btn-warning:hover {
            background-color: #f4b619;
            border-color: #f4b30d;
            color: #fff;
        }
        
        /* Search box styling */
        .search-box {
            margin-bottom: 1.5rem;
        }
        
        .search-box .form-control {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .search-box .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        /* Alert styling */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #e8f5e9;
            color: #1e7e34;
        }
        
        .alert-danger {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        /* Modal styling */
        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8f9fc;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .modal-title {
            font-weight: 600;
            color: #4e73df;
        }
        
        /* Animasi untuk card */
        @media (min-width: 992px) {
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            }
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
                <a href="../modules/tambah_tugas_anggota.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../modules/kelola_akun.php" class="list-group-item active">
                    <i class="bi bi-people"></i>
                    <span>Kelola Akun Anggota</span>
                </a>
                <a href="../auth/register.php" class="list-group-item">
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Kelola Akun Anggota</h1>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <i class="bi bi-people me-2"></i> Daftar Akun
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Search Box -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <form method="GET" action="" class="search-box d-flex">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="search" placeholder="Cari username..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Alert Messages -->
                            <?php if(isset($_SESSION['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nama Pengguna</th>
                                            <th>Peran</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if(mysqli_num_rows($result) > 0):
                                            while($row = mysqli_fetch_assoc($result)): 
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $row['role'] == 'admin' ? 'badge-admin' : 'badge-anggota'; ?>">
                                                    <?php echo ucfirst($row['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <button type="button" class="btn btn-primary btn-sm" 
                                                            onclick="openModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['username']); ?>')">
                                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                                    </button>
                                                    
                                                    <?php if($row['role'] == 'anggota'): ?>
                                                        <a href="hapus_akun.php?id=<?php echo $row['id']; ?>" 
                                                           class="btn btn-danger btn-sm"
                                                           onclick="return confirm('Yakin ingin menghapus akun ini?')">
                                                            <i class="bi bi-trash me-1"></i> Hapus
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                            endwhile; 
                                        else: 
                                        ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="bi bi-search fs-1 text-muted mb-3"></i>
                                                    <p class="text-muted">Tidak ada data yang ditemukan</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Modal Edit Username dan Reset Password -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Edit Username Form -->
                    <form action="../controllers/update_username.php" method="POST" class="mb-4">
                        <h6 class="mb-3 text-primary"><i class="bi bi-person-badge me-2"></i>Edit Username</h6>
                        <input type="hidden" id="userId" name="id">
                        <div class="mb-3">
                            <label for="newUsername" class="form-label">Username Baru</label>
                            <input type="text" class="form-control" id="newUsername" name="new_username" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Username
                        </button>
                    </form>
                    
                    <hr>
                    
                    <!-- Reset Password Form -->
                    <form action="../controllers/update_password_anggota.php" method="POST">
                        <h6 class="mb-3 text-warning"><i class="bi bi-key me-2"></i>Reset Password</h6>
                        <input type="hidden" id="userIdReset" name="id">
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-repeat me-1"></i> Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
            
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Modal functions
        function openModal(id, username) {
            const editModal = new bootstrap.Modal(document.getElementById('editModal'));
            document.getElementById('userId').value = id;
            document.getElementById('userIdReset').value = id;
            document.getElementById('newUsername').value = username;
            editModal.show();
        }
    </script>
</body>
</html>

