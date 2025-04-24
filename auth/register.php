<?php
include 'koneksi.php';
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}
$username = $_SESSION["username"];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = mysqli_real_escape_string($conn, $_POST["username"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    // Tetapkan role sebagai "anggota" tanpa mengambil dari form
    $role = "anggota";
    
    $query = "INSERT INTO users (username, password, role) VALUES ('$new_username', '$password', '$role')";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "✅ Anggota baru berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "❌ Gagal menambahkan anggota: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Tambah Anggota Baru</title>
    
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
                content: "MS";
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
        
        /* Animasi untuk card */
        @media (min-width: 992px) {
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            }
        }
        
        /* Styling untuk form yang lebih lebar */
        .form-wide {
            max-width: 100%;
            padding: 1.5rem;
        }
        
        /* Styling untuk input group */
        .input-group-text {
            background-color: #f8f9fc;
            border-color: #e3e6f0;
        }
        
        /* Styling untuk role badge */
        .role-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background-color: #e8f4ff;
            color: #4e73df;
            font-weight: 600;
            margin-bottom: 1rem;
            border: 1px dashed #bac8f3;
        }
        
        /* Styling untuk cards berdampingan */
        .info-card, .form-card {
            height: 100%;
        }
        
        .info-card .card-body {
            height: calc(100% - 56px); /* Adjust for card header height */
        }
        
        /* Styling untuk info card content */
        .info-alert {
            padding: 0.75rem;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .info-alert h5 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .info-alert ul {
            padding-left: 1.25rem;
        }
        
        .info-alert li {
            margin-bottom: 0.35rem;
            line-height: 1.4;
        }
        
        /* Styling untuk form content */
        .form-card .card-body {
            padding: 1rem;
        }
        
        .form-card .role-badge {
            padding: 0.35rem 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
        }
        
        .form-card small.text-muted {
            font-size: 0.75rem;
        }
        ::placeholder {
            font-size: 0.8rem;
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
                <a href="../modules/daftar_tugas_admin.php" class="list-group-item">
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
                <a href="../auth/register.php" class="list-group-item active">
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
                        <h1 class="h3 mb-0 text-gray-800">Tambah Anggota Baru</h1>
                    </div>
                    
                    <div class="row">
                        <!-- Alert Messages - Moved outside the cards to be full width -->
                        <?php if(isset($_SESSION['success'])): ?>
                            <div class="col-12 mb-3">
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-check-circle me-2"></i>
                                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="col-12 mb-3">
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Informasi tambahan - now in a column -->
                        <div class="col-lg-5 mb-4">
                            <div class="card info-card h-100">
                                <div class="card-header d-flex align-items-center">
                                    <i class="bi bi-info-circle me-2"></i> Informasi
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info info-alert mb-0">
                                        <h5><i class="bi bi-lightbulb me-2"></i>Petunjuk Penambahan Anggota</h5>
                                        <ul class="mb-0">
                                            <li>Anggota yang ditambahkan akan memiliki akses ke dashboard anggota.</li>
                                            <li>Username harus unik dan tidak boleh sama dengan username yang sudah ada.</li>
                                            <li>Pastikan untuk memberitahu password kepada anggota baru.</li>
                                            <li>Anggota dapat mengubah password mereka sendiri melalui halaman profil.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Tambah Anggota - now in a column -->
                        <div class="col-lg-7 mb-4">
                            <div class="card form-card h-100">
                                <div class="card-header d-flex align-items-center">
                                    <i class="bi bi-person-plus me-2"></i> Form Tambah Anggota
                                </div>
                                <div class="card-body">
                                    <div class="role-badge">
                                        <i class="bi bi-shield-check me-2"></i> Peran: Anggota
                                    </div>
                                    
                                    <form method="POST" action="">
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-2">
                                                <label for="username" class="form-label">Username</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
                                                </div>
                                                <small class="text-muted">Username harus unik dan akan digunakan untuk login.</small>
                                            </div>
                                            
                                            <div class="col-md-6 mb-2">
                                                <label for="password" class="form-label">Password</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                </div>
                                                <small class="text-muted">Password minimal 6 karakter.</small>
                                            </div>
                                        </div>
                                        
                                        <!-- Input hidden untuk role -->
                                        <input type="hidden" name="role" value="anggota">
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-6 mb-2">
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-person-plus me-2"></i>Tambah Anggota
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <div class="d-grid">
                                                    <a href="../dashboard/admin_dashboard.php" class="btn btn-outline-secondary">
                                                        <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                                                    </a>
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
            
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle icon
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
            
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert.alert-success, .alert.alert-danger');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Form validation
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const password = document.getElementById('password').value;
                if (password.length < 6) {
                    event.preventDefault();
                    alert('Password harus minimal 6 karakter!');
                }
            });
        });
    </script>
</body>
</html>
