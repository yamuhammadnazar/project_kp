<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"])) {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Determine the return URL based on user role
$return_url = "../dashboard/anggota_dashboard.php";
if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin") {
    $return_url = "../dashboard/admin_dashboard.php";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = password_hash($_POST["new_password"], PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password='$new_password' WHERE username='$username'";
    if (mysqli_query($conn, $query)) {
        $success_message = "✅ Password berhasil diubah!";
    } else {
        $error_message = "❌ Gagal mengubah password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Animate.css untuk animasi -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/anggota/main.css">
    <link rel="stylesheet" href="../assets/css/anggota/profile.css">
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
    <div class="sidebar-heading">Media Anggota</div>
    <div class="list-group">
                <a href="../dashboard/anggota_dashboard.php" class="list-group-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/daftar_tugas_anggota.php" class="list-group-item">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Daftar Tugas</span>
                </a>
                <a href="../modules/tambah.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../views/profile.php" class="list-group-item">
                    <i class="bi bi-key"></i>
                    <span>Ganti Password</span>
                </a>
                <a href="../auth/logout.php" class="list-group-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar</span>
                </a>
    </div>
</div>

        
        <!-- Content Wrapper -->
        <div id="content-wrapper">
            <!-- Topbar -->
            <nav class="topbar">
                <button id="sidebarToggle" class="toggle-sidebar">
                    <i class="bi bi-list"></i>
                </button>
                <a class="navbar-brand" href="#">Media Anggota</a>
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                    <a href="../auth/logout.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800 animate__animated animate__fadeInDown">Ganti Password</h1>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card animate__animated animate__fadeInUp">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Form Ganti Password</h6>
                            </div>
                            <div class="card-body">
                                <?php if(isset($success_message)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?php echo $success_message; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($error_message)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <?php echo $error_message; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" id="passwordForm">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Password Baru</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength mt-2" id="passwordStrength"></div>
                                        <div class="password-feedback" id="passwordFeedback"></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="invalid-feedback" id="passwordMatchFeedback">
                                            Password tidak cocok
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <i class="bi bi-key me-2"></i> Ubah Password
                                        </button>
                                        <a href="<?php echo $return_url; ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-2"></i> Kembali ke Dashboard
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menampilkan loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Fungsi untuk menghilangkan loading overlay
            function hideLoading() {
                loadingOverlay.style.opacity = '0';
                setTimeout(() => {
                    loadingOverlay.style.display = 'none';
                    document.body.classList.add('loaded');
                }, 500);
            }
            
            // Sembunyikan loading setelah halaman dimuat
            window.addEventListener('load', function() {
                setTimeout(hideLoading, 500);
            });
            
            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const contentWrapper = document.getElementById('content-wrapper');
            
            // Check for saved sidebar state
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'collapsed') {
                sidebarWrapper.classList.add('collapsed');
            }
            
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth < 768) {
                    // Mobile view - show/hide sidebar
                    sidebarWrapper.classList.toggle('show');
                } else {
                    // Desktop view - collapse/expand sidebar
                    sidebarWrapper.classList.toggle('collapsed');
                    
                    // Save sidebar state
                    if (sidebarWrapper.classList.contains('collapsed')) {
                        localStorage.setItem('sidebarState', 'collapsed');
                    } else {
                        localStorage.setItem('sidebarState', 'expanded');
                    }
                }
            });
            
            // Handle responsive behavior
            function handleResize() {
                if (window.innerWidth < 768) {
                    sidebarWrapper.classList.remove('collapsed');
                    sidebarWrapper.classList.remove('show');
                    localStorage.removeItem('sidebarState');
                } else {
                    // Restore sidebar state on desktop
                    const savedState = localStorage.getItem('sidebarState');
                    if (savedState === 'collapsed') {
                        sidebarWrapper.classList.add('collapsed');
                    } else {
                        sidebarWrapper.classList.remove('collapsed');
                    }
                }
            }
            
            // Initial check
            handleResize();
            
            // Listen for window resize
            window.addEventListener('resize', handleResize);
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 768 && 
                    !sidebarWrapper.contains(event.target) && 
                    !sidebarToggle.contains(event.target) &&
                    sidebarWrapper.classList.contains('show')) {
                    sidebarWrapper.classList.remove('show');
                }
            });
            
            // Toggle password visibility
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('new_password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle eye icon
                this.querySelector('i').classList.toggle('bi-eye');
                this.querySelector('i').classList.toggle('bi-eye-slash');
            });
            
            // Password strength checker
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordFeedback = document.getElementById('passwordFeedback');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let feedback = '';
                
                if (password.length >= 8) {
                    strength += 1;
                } else {
                    feedback = 'Password harus minimal 8 karakter';
                }
                
                if (password.match(/[A-Z]/)) {
                    strength += 1;
                } else if (password.length > 0) {
                    feedback = 'Tambahkan huruf kapital';
                }
                
                if (password.match(/[0-9]/)) {
                    strength += 1;
                } else if (password.length > 0) {
                    feedback = 'Tambahkan angka';
                }
                
                if (password.match(/[^A-Za-z0-9]/)) {
                    strength += 1;
                } else if (password.length > 0) {
                    feedback = 'Tambahkan karakter khusus';
                }
                
                // Update UI based on strength
                passwordStrength.className = 'password-strength';
                if (password.length === 0) {
                    passwordStrength.style.width = '0';
                    passwordFeedback.textContent = '';
                } else if (strength < 2) {
                    passwordStrength.classList.add('password-strength-weak');
                    passwordFeedback.textContent = feedback || 'Password lemah';
                    passwordFeedback.style.color = '#f44336';
                } else if (strength < 4) {
                    passwordStrength.classList.add('password-strength-medium');
                    passwordFeedback.textContent = feedback || 'Password sedang';
                    passwordFeedback.style.color = '#ff9800';
                } else {
                    passwordStrength.classList.add('password-strength-strong');
                    passwordFeedback.textContent = 'Password kuat';
                    passwordFeedback.style.color = '#4caf50';
                }
            });
            
            // Password confirmation validation
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatchFeedback = document.getElementById('passwordMatchFeedback');
            const submitBtn = document.getElementById('submitBtn');
            
            function checkPasswordMatch() {
                if (confirmPassword.value === '') {
                    confirmPassword.classList.remove('is-invalid');
                    return;
                }
                
                if (passwordInput.value === confirmPassword.value) {
                    confirmPassword.classList.remove('is-invalid');
                    confirmPassword.classList.add('is-valid');
                    submitBtn.disabled = false;
                } else {
                    confirmPassword.classList.remove('is-valid');
                    confirmPassword.classList.add('is-invalid');
                    submitBtn.disabled = true;
                }
            }
            
            confirmPassword.addEventListener('input', checkPasswordMatch);
            passwordInput.addEventListener('input', function() {
                if (confirmPassword.value !== '') {
                    checkPasswordMatch();
                }
            });
            
            // Form validation
            const passwordForm = document.getElementById('passwordForm');
            
            passwordForm.addEventListener('submit', function(event) {
                if (passwordInput.value !== confirmPassword.value) {
                    event.preventDefault();
                    confirmPassword.classList.add('is-invalid');
                }
                
                if (passwordInput.value.length < 8) {
                    event.preventDefault();
                    passwordFeedback.textContent = 'Password harus minimal 8 karakter';
                    passwordFeedback.style.color = '#f44336';
                }
            });
            
            // Tangani transisi halaman dengan loading overlay
            document.querySelectorAll('a').forEach(link => {
                // Abaikan link yang tidak mengarah ke halaman lain
                if (!link.getAttribute('href') || 
                    link.getAttribute('href').startsWith('#') || 
                    link.getAttribute('target') === '_blank' ||
                    link.getAttribute('href').startsWith('javascript:')) {
                    return;
                }
                
                link.addEventListener('click', function(e) {
                    // Tampilkan loading overlay
                    loadingOverlay.style.display = 'flex';
                    loadingOverlay.style.opacity = '1';
                });
            });
        });
    </script>
</body>
</html>

