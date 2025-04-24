<?php
include '../auth/koneksi.php';

// Cek session
if (!isset($_SESSION["username"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Query dengan JOIN
$query = "SELECT t.*, u.username as pemberi_username 
          FROM tugas_media t
          LEFT JOIN users u ON t.pemberi_tugas_id = u.id
          ORDER BY t.deadline ASC";
          
$result = mysqli_query($conn, $query);

$username = $_SESSION["username"];

// Query untuk mendapatkan tugas yang diberikan oleh admin yang login
$query_tugas_diberikan = "SELECT t.*, u.username as pemberi_username 
                          FROM tugas_media t
                          LEFT JOIN users u ON t.pemberi_tugas_id = u.id
                          WHERE t.pemberi_tugas = 'admin' 
                          ORDER BY t.deadline ASC";
$result_tugas_diberikan = mysqli_query($conn, $query_tugas_diberikan);

// Query untuk mendapatkan tugas yang diterima dari kabid
$query_tugas_dari_kabid = "SELECT t.*, u.username as pemberi_username 
                           FROM tugas_media t
                           LEFT JOIN users u ON t.pemberi_tugas_id = u.id
                           WHERE t.pemberi_tugas = 'kabid' 
                           ORDER BY t.deadline ASC";
$result_tugas_dari_kabid = mysqli_query($conn, $query_tugas_dari_kabid);

// Hitung jumlah tugas
$jumlah_tugas_diberikan = mysqli_num_rows($result_tugas_diberikan);
$jumlah_tugas_dari_kabid = mysqli_num_rows($result_tugas_dari_kabid);
$total_tugas = $jumlah_tugas_diberikan + $jumlah_tugas_dari_kabid;

// Fungsi untuk menampilkan status dengan badge
function getBadgeClass($status) {
    switch ($status) {
        case 'Belum Dikerjakan':
            return 'bg-secondary';
        case 'Sedang Dikerjakan':
            return 'bg-primary';
        case 'Kirim':
            return 'bg-info';
        case 'Revisi':
            return 'bg-warning';
        case 'Selesai':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

// Fungsi untuk menghitung sisa hari
function hitungSisaHari($deadline) {
    $today = new DateTime();
    $deadline_date = new DateTime($deadline);
    $interval = $today->diff($deadline_date);
    
    if ($today > $deadline_date) {
        return '<span class="text-danger">Terlambat ' . $interval->days . ' hari</span>';
    } else {
        return $interval->days . ' hari';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Daftar Tugas Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Animate.css untuk animasi -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
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
        
        /* Styling untuk badge status */
        .badge {
            font-weight: 600;
            padding: 0.5em 0.75em;
            border-radius: 6px;
        }
        
        /* Styling untuk tombol aksi */
        .btn-action {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 4px;
            margin-right: 0.25rem;
        }
        
        /* Styling untuk DataTables */
        .dataTables_wrapper {
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .dataTables_length,
        .dataTables_filter {
            margin-bottom: 1rem;
        }
        
        .dataTables_info,
        .dataTables_paginate {
            margin-top: 1rem;
        }
        
        .page-link {
            color: var(--primary-color);
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        /* Styling untuk tab */
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
        
        /* Styling untuk statistik */
        .stats-card {
            border-left: 4px solid;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card-primary {
            border-left-color: #4e73df;
        }
        
        .stats-card-success {
            border-left-color: #1cc88a;
        }
        
        .stats-card-warning {
            border-left-color: #f6c23e;
        }
        
        .stats-card-danger {
            border-left-color: #e74a3b;
        }
        
                .stats-card .card-body {
            padding: 1rem;
        }
        
        .stats-card .stats-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
        
        .stats-card .stats-title {
            text-transform: uppercase;
            font-size: 0.7rem;
            font-weight: 700;
            color: #858796;
        }
        
        .stats-card .stats-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #5a5c69;
        }
        
        /* Styling untuk deadline */
        .deadline-indicator {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
                
        .deadline-near {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .deadline-today {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .deadline-safe {
            background-color: #d4edda;
            color: #155724;
        }
        
        /* Animasi untuk card */
        @media (min-width: 992px) {
            .card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            }
        }
        
        /* Modifikasi untuk tabel - font lebih kecil */
        .table {
            font-size: 0.85rem;
        }
        
        /* Modifikasi untuk tombol aksi - sejajar horizontal */
        .action-buttons {
            display: flex;
            flex-direction: row;
            gap: 5px;
            justify-content: flex-start;
            white-space: nowrap;
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
                        <h1 class="h3 mb-0 text-gray-800">Daftar Tugas</h1>
                        <a href="../modules/tambah_tugas_anggota.php" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Tugas Baru
                        </a>
                    </div>
                    
                    <!-- Statistik Tugas -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card stats-card-primary h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="stats-title">Total Tugas</div>
                                            <div class="stats-number"><?php echo $total_tugas; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-clipboard-check stats-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card stats-card-success h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="stats-title">Tugas Diberikan</div>
                                            <div class="stats-number"><?php echo $jumlah_tugas_diberikan; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-arrow-up-circle stats-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card stats-card-warning h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="stats-title">Tugas dari Kabid</div>
                                            <div class="stats-number"><?php echo $jumlah_tugas_dari_kabid; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-arrow-down-circle stats-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php
                        // Hitung tugas yang mendekati deadline (3 hari atau kurang)
                        $query_deadline = "SELECT COUNT(*) as count FROM tugas_media 
                                           WHERE DATEDIFF(deadline, CURDATE()) BETWEEN 0 AND 3 
                                           AND status != 'Selesai'";
                        $result_deadline = mysqli_query($conn, $query_deadline);
                        $row_deadline = mysqli_fetch_assoc($result_deadline);
                        $tugas_mendekati_deadline = $row_deadline['count'];
                        ?>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stats-card stats-card-danger h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="stats-title">Mendekati Deadline</div>
                                            <div class="stats-number"><?php echo $tugas_mendekati_deadline; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-alarm stats-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tab untuk kategori tugas -->
                    <ul class="nav nav-tabs" id="taskTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                                <i class="bi bi-collection me-1"></i> Semua Tugas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="given-tab" data-bs-toggle="tab" data-bs-target="#given" type="button" role="tab" aria-controls="given" aria-selected="false">
                                <i class="bi bi-arrow-up-circle me-1"></i> Tugas Diberikan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="received-tab" data-bs-toggle="tab" data-bs-target="#received" type="button" role="tab" aria-controls="received" aria-selected="false">
                                <i class="bi bi-arrow-down-circle me-1"></i> Tugas dari Kabid
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="taskTabsContent">
    <!-- Tab Semua Tugas -->
    <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
        <div class="card">
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="allTasksTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Platform</th>
                                <th>Penanggung Jawab</th>
                                <th>Status</th>
                                <th>Deadline</th>
                                <th>Sisa Waktu</th>
                                <th>Pemberi Tugas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Gabungkan kedua hasil query
                            $all_tasks = array();
                            
                            // Reset pointer hasil query
                            mysqli_data_seek($result_tugas_diberikan, 0);
                            mysqli_data_seek($result_tugas_dari_kabid, 0);
                            
                            // Tambahkan tugas yang diberikan admin
                            while ($row = mysqli_fetch_assoc($result_tugas_diberikan)) {
                                $all_tasks[] = $row;
                            }
                            
                            // Tambahkan tugas dari kabid
                            while ($row = mysqli_fetch_assoc($result_tugas_dari_kabid)) {
                                $all_tasks[] = $row;
                            }
                            
                            // Urutkan berdasarkan deadline
                            usort($all_tasks, function($a, $b) {
                                return strtotime($a['deadline']) - strtotime($b['deadline']);
                            });
                            
                            $no = 1;
                            foreach ($all_tasks as $task):
                                $badge_class = getBadgeClass($task['status']);
                                $sisa_hari = hitungSisaHari($task['deadline']);
                                
                                // Tampilkan username pemberi tugas dari hasil JOIN
                                if ($task['pemberi_tugas'] == 'admin') {
                                    $pemberi = isset($task['pemberi_username']) ? htmlspecialchars($task['pemberi_username']) : 'Admin';
                                } else {
                                    $pemberi = 'Kabid';
                                }
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($task['judul']); ?></td>
                                <td><?php echo htmlspecialchars($task['platform']); ?></td>
                                <td><?php echo htmlspecialchars($task['penanggung_jawab']); ?></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $task['status']; ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($task['deadline'])); ?></td>
                                <td><?php echo $sisa_hari; ?></td>
                                <td><?php echo $pemberi; ?></td>
                                <td>
                                    <div class="action-buttons">
                                                                                <a href="../modules/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-primary btn-action">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="../views/catatan_admin.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info btn-action">
                                            <i class="bi bi-chat-left-text"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger btn-action btn-delete" data-id="<?php echo $task['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
                        
                        <!-- Tab Tugas Diberikan -->
                        <div class="tab-pane fade" id="given" role="tabpanel" aria-labelledby="given-tab">
                            <div class="card">
                                <div class="card-body">
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="givenTasksTable">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Judul</th>
                                                    <th>Platform</th>
                                                    <th>Penanggung Jawab</th>
                                                    <th>Status</th>
                                                    <th>Deadline</th>
                                                    <th>Sisa Waktu</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                mysqli_data_seek($result_tugas_diberikan, 0);
                                                $no = 1;
                                                while ($row = mysqli_fetch_assoc($result_tugas_diberikan)):
                                                    $badge_class = getBadgeClass($row['status']);
                                                    $sisa_hari = hitungSisaHari($row['deadline']);
                                                ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['platform']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span></td>
                                                    <td><?php echo date('d/m/Y', strtotime($row['deadline'])); ?></td>
                                                    <td><?php echo $sisa_hari; ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="../modules/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary btn-action">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <a href="../views/catatan_admin.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info btn-action">
                                                                <i class="bi bi-chat-left-text"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger btn-action btn-delete" data-id="<?php echo $row['id']; ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Tugas dari Kabid -->
                        <div class="tab-pane fade" id="received" role="tabpanel" aria-labelledby="received-tab">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="receivedTasksTable">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Judul</th>
                                                    <th>Platform</th>
                                                    <th>Penanggung Jawab</th>
                                                    <th>Status</th>
                                                    <th>Deadline</th>
                                                    <th>Sisa Waktu</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                mysqli_data_seek($result_tugas_dari_kabid, 0);
                                                $no = 1;
                                                while ($row = mysqli_fetch_assoc($result_tugas_dari_kabid)):
                                                    $badge_class = getBadgeClass($row['status']);
                                                    $sisa_hari = hitungSisaHari($row['deadline']);
                                                ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['platform']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                                                    <td><span class="badge <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span></td>
                                                    <td><?php echo date('d/m/Y', strtotime($row['deadline'])); ?></td>
                                                    <td><?php echo $sisa_hari; ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="../modules/edit_tugas.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary btn-action">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <a href="../modules/catatan_tugas.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info btn-action">
                                                                <i class="bi bi-chat-left-text"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger btn-action btn-delete" data-id="<?php echo $row['id']; ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
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
    
    <!-- Overlay for mobile -->
    <div class="sidebar-overlay"></div>
    
    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus tugas ini? Tindakan ini tidak dapat dibatalkan.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Hapus</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi DataTables
            $('#allTasksTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
                },
                order: [[5, 'asc']], // Urutkan berdasarkan deadline
                columnDefs: [
                    { responsivePriority: 1, targets: [1, 4, 8] }, // Kolom yang selalu ditampilkan
                    { responsivePriority: 2, targets: [3, 5] },
                    { responsivePriority: 3, targets: [2, 6, 7] }
                ]
            });
            
            $('#givenTasksTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
                },
                order: [[5, 'asc']], // Urutkan berdasarkan deadline
                columnDefs: [
                    { responsivePriority: 1, targets: [1, 4, 7] }, // Kolom yang selalu ditampilkan
                    { responsivePriority: 2, targets: [3, 5] },
                    { responsivePriority: 3, targets: [2, 6] }
                ]
            });
            
            $('#receivedTasksTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json',
                },
                order: [[5, 'asc']], // Urutkan berdasarkan deadline
                columnDefs: [
                    { responsivePriority: 1, targets: [1, 4, 7] }, // Kolom yang selalu ditampilkan
                    { responsivePriority: 2, targets: [3, 5] },
                    { responsivePriority: 3, targets: [2, 6] }
                ]
            });
            
            // Cek apakah ada pesan sukses dari session
            const successMessage = document.querySelector('.alert-success');
            if (successMessage) {
                const message = successMessage.textContent.trim();
                successMessage.remove(); // Hapus alert default
                
                Swal.fire({
                    title: 'Berhasil!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4e73df'
                });
            }
            
            // Cek apakah ada pesan error dari session
            const errorMessage = document.querySelector('.alert-danger');
            if (errorMessage) {
                const message = errorMessage.textContent.trim();
                errorMessage.remove(); // Hapus alert default
                
                Swal.fire({
                    title: 'Error!',
                    text: message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#4e73df'
                });
            }
            
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
            
                        // Konfirmasi hapus tugas
            const deleteButtons = document.querySelectorAll('.btn-delete');
            const confirmDeleteButton = document.getElementById('confirmDelete');
            
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const taskId = this.getAttribute('data-id');
                    
                    // Gunakan SweetAlert2 untuk konfirmasi
                    Swal.fire({
                        title: 'Konfirmasi Hapus',
                        text: "Apakah Anda yakin ingin menghapus tugas ini? Tindakan ini tidak dapat dibatalkan.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect ke halaman hapus dengan ID tugas
                            window.location.href = '../controllers/hapus_tugas.php?id=' + taskId;
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
