<?php
include '../auth/koneksi.php';
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "anggota") {
    header("Location: ../auth/login.php");
    exit();
}
$username = $_SESSION["username"];
$id = (int) $_GET['id'];
$query = "SELECT * FROM tugas_media WHERE id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status Tugas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Animate.css untuk animasi -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
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
            background: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            transition: all var(--transition-speed) cubic-bezier(0.25, 0.8, 0.25, 1);
            z-index: 1000;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        #sidebar-wrapper.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        #sidebar-wrapper .sidebar-heading {
            padding: 1rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: white;
            text-align: center;
            transition: all var(--transition-speed) ease;
        }

        #sidebar-wrapper.collapsed .sidebar-heading {
            font-size: 0;
        }

        #sidebar-wrapper.collapsed .sidebar-heading::before {
            content: "MA";
            font-size: 1.2rem;
        }

        #sidebar-wrapper .list-group {
            width: var(--sidebar-width);
        }

        #sidebar-wrapper.collapsed .list-group {
            width: var(--sidebar-collapsed-width);
        }

        #sidebar-wrapper .list-group-item {
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            border-radius: 0;
            display: flex;
            align-items: center;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        #sidebar-wrapper .list-group-item:before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background-color: white;
            transform: translateX(-3px);
            transition: transform 0.2s;
        }

        #sidebar-wrapper .list-group-item:hover:before {
            transform: translateX(0);
        }

        #sidebar-wrapper .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
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
            width: 20px;
            text-align: center;
            transition: all var(--transition-speed) ease;
        }

        #sidebar-wrapper.collapsed .list-group-item span {
            display: none;
        }

        #sidebar-wrapper.collapsed .list-group-item i {
            margin-right: 0;
            font-size: 1.2rem;
        }

        #content-wrapper {
            flex: 1;
            min-width: 0;
            background-color: #f8f9fc;
            transition: all var(--transition-speed) ease;
        }

        .topbar {
            height: var(--topbar-height);
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            display: flex;
            align-items: center;
            padding: 0 1rem;
            transition: all var(--transition-speed) ease;
        }

        .topbar .navbar-brand {
            display: none;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            color: #4e73df;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            transition: transform 0.2s ease;
        }

        .toggle-sidebar:hover {
            transform: rotate(90deg);
        }

        .user-info {
            margin-left: auto;
            display: flex;
            align-items: center;
        }

        .user-info .username {
            margin-right: 1rem;
            font-weight: 600;
            position: relative;
            padding-bottom: 2px;
        }

        .user-info .username:after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #4e73df;
            transition: width 0.3s ease;
        }

        .user-info .username:hover:after {
            width: 100%;
        }

        .main-content {
            padding: 1.5rem;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .form-label {
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
        }

        .form-control,
        .form-select {
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        textarea.form-control {
            min-height: 120px;
        }

        .btn {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            padding: 0.6rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(46, 89, 217, 0.2);
        }

        .btn-secondary {
            background-color: #858796;
            border-color: #858796;
        }

        .btn-secondary:hover {
            background-color: #717384;
            border-color: #6c757d;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(78, 115, 223, 0.1);
            border-radius: 50%;
            border-top-color: #4e73df;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Tab styling */
        .nav-tabs {
            border-bottom: 1px solid #e3e6f0;
            margin-bottom: 0.8rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #858796;
            font-weight: 600;
            padding: 0.6rem 0.8rem;
            border-radius: 0;
            margin-right: 0.5rem;
            font-size: 0.85rem;
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

        .tab-content {
            padding: 0.5rem 0;
        }

        .mb-3 {
            margin-bottom: 0.8rem !important;
        }

        .card-body {
            padding: 1rem;
        }

        .row {
            margin-left: -0.5rem;
            margin-right: -0.5rem;
        }

        .col-md-6,
        .col-md-12,
        .col-12 {
            padding-left: 0.5rem;
            padding-right: 0.5rem;
        }

        .text-muted {
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            #sidebar-wrapper {
                position: fixed;
                left: -250px;
                height: 100%;
                transition: left var(--transition-speed) ease;
            }

            #sidebar-wrapper.show {
                left: 0;
            }

            #content-wrapper {
                width: 100%;
            }

            .topbar .navbar-brand {
                display: block;
            }

            .toggle-sidebar:hover {
                transform: none;
            }

            .main-content {
                padding: 0.8rem;
            }

            .card-body {
                padding: 0.8rem;
            }

            .form-control,
            .form-select {
                padding: 0.5rem 0.7rem;
            }

            .btn {
                padding: 0.5rem 0.8rem;
            }
        }
    </style>
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
                    <span class="username"><?php echo htmlspecialchars($username); ?></span>
                    <a href="../auth/logout.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div class="main-content">  
                <div class="card shadow animate__animated animate__fadeInUp">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Edit Tugas</h6>
                    </div>
                    <div class="card-body">
                        <form action="../controllers/proses_update_anggota.php" method="POST">
                            <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
                            
                            <!-- Tab Navigation -->
                            <ul class="nav nav-tabs" id="taskTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab"
                                        data-bs-target="#info" type="button" role="tab" aria-controls="info"
                                        aria-selected="true">
                                        <i class="bi bi-info-circle me-1"></i> Informasi Dasar
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="jadwal-tab" data-bs-toggle="tab" data-bs-target="#jadwal"
                                        type="button" role="tab" aria-controls="jadwal" aria-selected="false">
                                        <i class="bi bi-calendar-date me-1"></i> Jadwal & Admin
                                    </button>
                                </li>
                            </ul>
                            
                            <!-- Tab Content -->
                            <div class="tab-content" id="myTabContent">
                                <!-- Tab 1: Informasi Tugas -->
                                <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                                    <div class="mb-3">
                                        <label for="judul" class="form-label">Judul</label>
                                        <input type="text" class="form-control" id="judul" name="judul" value="<?php echo isset($data['judul']) ? htmlspecialchars($data['judul']) : ''; ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="platform" class="form-label">Platform</label>
                                        <select class="form-select" id="platform" name="platform" required>
                                            <option value="Instagram" <?php echo (isset($data['platform']) && $data['platform'] == 'Instagram') ? 'selected' : ''; ?>>Instagram</option>
                                            <option value="Facebook" <?php echo (isset($data['platform']) && $data['platform'] == 'Facebook') ? 'selected' : ''; ?>>Facebook</option>
                                            <option value="Twitter" <?php echo (isset($data['platform']) && $data['platform'] == 'Twitter') ? 'selected' : ''; ?>>Twitter</option>
                                            <option value="TikTok" <?php echo (isset($data['platform']) && $data['platform'] == 'TikTok') ? 'selected' : ''; ?>>TikTok</option>
                                            <option value="YouTube" <?php echo (isset($data['platform']) && $data['platform'] == 'YouTube') ? 'selected' : ''; ?>>YouTube</option>
                                            <option value="LinkedIn" <?php echo (isset($data['platform']) && $data['platform'] == 'LinkedIn') ? 'selected' : ''; ?>>LinkedIn</option>
                                            <option value="Website" <?php echo (isset($data['platform']) && $data['platform'] == 'Website') ? 'selected' : ''; ?>>Website</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="deskripsi" class="form-label">Deskripsi</label>
                                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required><?php echo isset($data['deskripsi']) ? htmlspecialchars($data['deskripsi']) : ''; ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="link_drive" class="form-label">Link Drive</label>
                                        <input type="text" class="form-control" id="link_drive" name="link_drive" value="<?php echo isset($data['link_drive']) ? htmlspecialchars($data['link_drive']) : ''; ?>" placeholder="Masukkan link Google Drive atau platform lainnya">
                                        <div class="form-text text-muted">
                                            <i class="bi bi-info-circle"></i> Masukkan link Google Drive, Dropbox, atau platform lain yang berisi file tugas Anda.
                                        </div>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <button type="button" class="btn btn-primary next-tab" data-next="jadwal-tab">
                                            Lanjut <i class="bi bi-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Tab 2: Jadwal & Status -->
                                <div class="tab-pane fade" id="jadwal" role="tabpanel" aria-labelledby="jadwal-tab">
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="Belum Dikerjakan" <?php echo (isset($data['status']) && $data['status'] == 'Belum Dikerjakan') ? 'selected' : ''; ?>>Belum Dikerjakan</option>
                                            <option value="Sedang Dikerjakan" <?php echo (isset($data['status']) && $data['status'] == 'Sedang Dikerjakan') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                            <option value="Kirim" <?php echo (isset($data['status']) && $data['status'] == 'Kirim') ? 'selected' : ''; ?>>Kirim</option>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo isset($data['tanggal_mulai']) ? $data['tanggal_mulai'] : ''; ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="deadline" class="form-label">Deadline</label>
                                            <input type="date" class="form-control" id="deadline" name="deadline" value="<?php echo isset($data['deadline']) ? $data['deadline'] : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex mt-3">
                                        <button type="button" class="btn btn-secondary prev-tab me-2" data-prev="info-tab">
                                            <i class="bi bi-arrow-left me-1"></i> Kembali
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i> Update Tugas
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const contentWrapper = document.getElementById('content-wrapper');
            
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebarWrapper.classList.toggle('collapsed');
                
                if (window.innerWidth < 768) {
                    sidebarWrapper.classList.toggle('show');
                }
            });
            
            // Responsive behavior
            function handleResize() {
                if (window.innerWidth < 768) {
                    sidebarWrapper.classList.remove('collapsed');
                    sidebarWrapper.classList.remove('show');
                } else {
                    sidebarWrapper.classList.remove('show');
                }
            }
            
            window.addEventListener('resize', handleResize);
            handleResize();
            
            // Sembunyikan loading overlay setelah halaman dimuat
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.style.opacity = '0';
            setTimeout(function() {
                loadingOverlay.style.display = 'none';
                document.body.classList.add('loaded');
            }, 500);
            
            // Tab navigation
            const nextTabButtons = document.querySelectorAll('.next-tab');
            const prevTabButtons = document.querySelectorAll('.prev-tab');
            
            nextTabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Validate current tab fields
                    const currentTab = this.closest('.tab-pane');
                    const inputs = currentTab.querySelectorAll('input[required], select[required], textarea[required]');
                    let isValid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            isValid = false;
                            input.classList.add('is-invalid');
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });
                    
                    if (!isValid) {
                        Swal.fire({
                            title: 'Oops!',
                            text: 'Harap isi semua field yang diperlukan',
                            icon: 'warning',
                            confirmButtonColor: '#4e73df'
                        });
                        return;
                    }
                    
                    // Move to next tab
                    const nextTabId = this.getAttribute('data-next');
                    const nextTab = document.getElementById(nextTabId);
                    const tab = new bootstrap.Tab(nextTab);
                    tab.show();
                });
            });
            
            prevTabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const prevTabId = this.getAttribute('data-prev');
                    const prevTab = document.getElementById(prevTabId);
                    const tab = new bootstrap.Tab(prevTab);
                    tab.show();
                });
            });
            
            // Validasi form
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const deadline = new Date(document.getElementById('deadline').value);
                const tanggalMulai = new Date(document.getElementById('tanggal_mulai').value);
                
                if (deadline < tanggalMulai) {
                    event.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Deadline tidak boleh lebih awal dari tanggal mulai!',
                        icon: 'error',
                        confirmButtonColor: '#4e73df'
                    });
                }
            });
            
            // Set minimum date untuk deadline
            const tanggalMulaiInput = document.getElementById('tanggal_mulai');
            const deadlineInput = document.getElementById('deadline');
            
            tanggalMulaiInput.addEventListener('change', function() {
                deadlineInput.min = this.value;
            });
            
            // Set initial min date for deadline
            if (tanggalMulaiInput.value) {
                deadlineInput.min = tanggalMulaiInput.value;
            }
            
            // Highlight fields with validation errors
            const formInputs = document.querySelectorAll('input, select, textarea');
            formInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.classList.remove('is-invalid');
                    }
                });
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
