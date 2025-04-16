<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];
$query = "SELECT * FROM users WHERE role = 'admin' ORDER BY username ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Admin</title>
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
    
    .table {
        width: 100%;
        margin-bottom: 0;
    }
    
    .table th {
        font-weight: 600;
        background-color: #f8f9fc;
        color: #5a5c69;
        border-bottom-width: 1px;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .btn-edit {
        background-color: #4e73df;
        color: white;
        border: none;
        padding: 0.375rem 0.75rem;
        border-radius: 0.25rem;
        transition: all 0.2s;
    }
    
    .btn-edit:hover {
        background-color: #2e59d9;
        color: white;
    }
    
    .btn-delete {
        background-color: #e74a3b;
        color: white;
        border: none;
        padding: 0.375rem 0.75rem;
        border-radius: 0.25rem;
        transition: all 0.2s;
    }
    
    .btn-delete:hover {
        background-color: #be2617;
        color: white;
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
    
    /* Modal styling */
    .modal-content {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.5rem 2rem 0 rgba(0, 0, 0, 0.2);
    }
    
    .modal-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    
    .modal-title {
        color: #5a5c69;
        font-weight: 700;
    }
    
    .modal-footer {
        border-top: 1px solid #e3e6f0;
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
                <a href="../modules/tambah_tugas_kabid.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../modules/daftar_tugas_kabid.php" class="list-group-item">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
                <a href="../auth/register_admin.php" class="list-group-item">
                    <i class="bi bi-person-plus"></i>
                    <span>Tambah Admin Staf</span>
                </a>
                <a href="../modules/kelola_admin.php" class="list-group-item active">
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
                    <h1 class="h3 mb-4 text-gray-800 fade-in">Kelola Admin Staf</h1>
                    
                    <!-- Menampilkan pesan sukses/error -->
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="row fade-in">
                            <div class="col-12">
                                <div class="success-message mb-4"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="row fade-in">
                            <div class="col-12">
                                <div class="error-message mb-4"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row fade-in">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Daftar Admin Staf</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Nama Pengguna</th>
                                                    <th class="text-center">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(mysqli_num_rows($result) > 0): ?>
                                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                                            <td class="text-center">
                                                                <button type="button" class="btn btn-edit btn-sm me-2" 
                                                                        onclick="openModal(<?php echo $row['id']; ?>, '<?php echo $row['username']; ?>')">
                                                                    <i class="bi bi-pencil-square"></i> Edit
                                                                </button>
                                                                <a href="../controllers/hapus_admin.php?id=<?php echo $row['id']; ?>" 
                                                                   class="btn btn-delete btn-sm"
                                                                   onclick="return confirm('Yakin ingin menghapus admin ini?')">
                                                                    <i class="bi bi-trash"></i> Hapus
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center">Tidak ada admin staf yang ditemukan</td>
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
        </div>
    </div>
    
    <!-- Modal Edit Username dan Reset Password -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Admin Staf</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form Edit Username -->
                    <h6 class="mb-3">Edit Username</h6>
                    <form action="../controllers/update_username_admin.php" method="POST">
                        <input type="hidden" id="userId" name="id">
                        <div class="mb-3">
                            <label for="newUsername" class="form-label">Username Baru</label>
                            <input type="text" class="form-control" id="newUsername" name="new_username" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Simpan Username
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <!-- Form Reset Password -->
                    <h6 class="mb-3">Reset Password</h6>
                    <form action="../controllers/update_password_admin.php" method="POST">
                        <input type="hidden" id="userIdReset" name="id">
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-key"></i> Reset Password
                        </button>
                    </form>
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
