<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get the current user's username
$current_username = $_SESSION["username"];

// Check if the current user has the 'anggota' role
$check_role_query = "SELECT role FROM users WHERE username = '$current_username'";
$role_result = mysqli_query($conn, $check_role_query);
$user_role = mysqli_fetch_assoc($role_result)['role'];

// Redirect admin to admin dashboard if they try to access anggota pages
if ($user_role === 'admin') {
    header("Location: ../dashboard/admin_dashboard.php");
    exit();
}

// Get list of anggota users
$query = "SELECT username FROM users WHERE role='anggota'";
$result = mysqli_query($conn, $query);

// Get list of admin users
$admin_query = "SELECT username FROM users WHERE role='admin'";
$admin_result = mysqli_query($conn, $admin_query);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = mysqli_real_escape_string($conn, $_POST["judul"]);
    $platform = mysqli_real_escape_string($conn, $_POST["platform"]);
    $deskripsi = mysqli_real_escape_string($conn, $_POST["deskripsi"]);
    $status = 'Belum Dikerjakan';
    $tanggal_mulai = mysqli_real_escape_string($conn, $_POST["tanggal_mulai"]);
    $deadline = mysqli_real_escape_string($conn, $_POST["deadline"]);
    
    // Anggota is always the penanggung_jawab for their own tasks
    $penanggung_jawab = $current_username;
    
    // Get the admin who assigned the task
    $pemberi_tugas = mysqli_real_escape_string($conn, $_POST["pemberi_tugas"]);

    $query = "INSERT INTO tugas_media (judul, platform, deskripsi, status, tanggal_mulai, deadline, penanggung_jawab, pemberi_tugas) 
              VALUES ('$judul', '$platform', '$deskripsi', '$status', '$tanggal_mulai', '$deadline', '$penanggung_jawab', '$pemberi_tugas')";
    
    if (mysqli_query($conn, $query)) {
        // Redirect to anggota_dashboard.php after successful insertion
        header("Location: ../dashboard/anggota_dashboard.php?success=1");
        exit();
    } else {
        $error_message = "❌ Gagal menambahkan tugas!";
    }
}

// Return URL is always anggota dashboard since only anggota can access this page
$return_url = "../dashboard/anggota_dashboard.php";
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
    <link rel="stylesheet" href="../assets/css/anggota/main.css">
    <link rel="stylesheet" href="../assets/css/anggota/tambah.css">
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
            <h1 class="h3 mb-0 text-gray-800 animate__animated animate__fadeInDown">Tambah Tugas Baru</h1>
        </div>
        
        <div class="card animate__animated animate__fadeInUp">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Form Tambah Tugas</h6>
            </div>
            <div class="card-body">
                <?php if(isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="judul" class="form-label">Judul</label>
                        <input type="text" class="form-control" id="judul" name="judul" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="platform" class="form-label">Platform</label>
                        <select class="form-select" id="platform" name="platform" required>
                            <option value="" disabled selected>Pilih platform</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Twitter">Twitter</option>
                            <option value="TikTok">TikTok</option>
                            <option value="YouTube">YouTube</option>
                            <option value="LinkedIn">LinkedIn</option>
                            <option value="Website">Website</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">Deadline</label>
                            <input type="date" class="form-control" id="deadline" name="deadline" required>
                        </div>
                    </div>
                    
                    <!-- Hidden field for penanggung_jawab -->
                    <input type="hidden" name="penanggung_jawab" value="<?php echo htmlspecialchars($current_username); ?>">
                    
                    <div class="mb-4">
                        <label class="form-label">Penanggung Jawab</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_username); ?>" disabled>
                        <small class="text-muted">Anda akan ditugaskan sebagai penanggung jawab untuk tugas ini.</small>
                    </div>
                    
                    <!-- Field untuk memilih admin pemberi tugas -->
                    <div class="mb-4">
                        <label for="pemberi_tugas" class="form-label">Admin Pemberi Tugas</label>
                        <select class="form-select" id="pemberi_tugas" name="pemberi_tugas" required>
                            <option value="" disabled selected>Pilih admin pemberi tugas</option>
                            <?php 
                            mysqli_data_seek($admin_result, 0); // Reset result pointer
                            while ($admin = mysqli_fetch_assoc($admin_result)) { 
                            ?>
                                <option value="<?php echo htmlspecialchars($admin['username']); ?>">
                                    <?php echo htmlspecialchars($admin['username']); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <small class="text-muted">Pilih admin yang memberikan tugas ini kepada Anda.</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i> Tambah Tugas
                        </button>
                        <a href="<?php echo $return_url; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </form>
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
            
            // Add tooltip functionality for collapsed sidebar
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Update tooltips when sidebar state changes
            function updateTooltips() {
                if (sidebarWrapper.classList.contains('collapsed')) {
                    document.querySelectorAll('#sidebar-wrapper .list-group-item').forEach(item => {
                        item.setAttribute('data-bs-toggle', 'tooltip');
                        item.setAttribute('data-bs-placement', 'right');
                        item.setAttribute('title', item.querySelector('span').textContent);
                    });
                    tooltipList.forEach(tooltip => tooltip.dispose());
                    const newTooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    newTooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                } else {
                    document.querySelectorAll('#sidebar-wrapper .list-group-item').forEach(item => {
                        item.removeAttribute('data-bs-toggle');
                        item.removeAttribute('data-bs-placement');
                        item.removeAttribute('title');
                    });
                    tooltipList.forEach(tooltip => tooltip.dispose());
                }
            }
            
            // Initial tooltip setup
            updateTooltips();
            
            // Update tooltips when sidebar state changes
            sidebarToggle.addEventListener('click', function() {
                setTimeout(updateTooltips, 300); // Wait for transition to complete
            });
            
            // Efek ripple pada tombol
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    let x = e.clientX - e.target.getBoundingClientRect().left;
                    let y = e.clientY - e.target.getBoundingClientRect().top;
                    
                    let ripple = document.createElement('span');
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            // Validasi form
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const deadline = new Date(document.getElementById('deadline').value);
                const tanggalMulai = new Date(document.getElementById('tanggal_mulai').value);
                
                if (deadline < tanggalMulai) {
                    event.preventDefault();
                    alert('Deadline tidak boleh lebih awal dari tanggal mulai!');
                }
            });
            
            // Set minimum date untuk deadline
            const tanggalMulaiInput = document.getElementById('tanggal_mulai');
            const deadlineInput = document.getElementById('deadline');
            
            tanggalMulaiInput.addEventListener('change', function() {
                deadlineInput.min = this.value;
            });
            
            // Set initial min date for deadline
            deadlineInput.min = tanggalMulaiInput.value;
        });
    </script>
    <script>
    // Tangani transisi halaman dengan loading overlay
    document.addEventListener('DOMContentLoaded', function() {
        // Tampilkan loading overlay saat link diklik
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
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'flex';
                    loadingOverlay.style.opacity = '1';
                }
            });
        });
    });
</script>
<script>
    // Tangani transisi halaman dengan loading overlay
document.addEventListener('DOMContentLoaded', function() {
    // Tampilkan loading overlay saat link diklik
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
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
                loadingOverlay.style.opacity = '1';
            }
        });
    });
});

</script>
</body>
</html>

