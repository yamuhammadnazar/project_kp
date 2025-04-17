<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Filter bulan, tahun, status, dan admin
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : '';
$admin = isset($_GET['admin']) ? $_GET['admin'] : '';
$platform = isset($_GET['platform']) ? $_GET['platform'] : '';

// Base query untuk tugas yang diberikan oleh kabid
$base_query = "FROM tugas_media t
                JOIN users u ON t.penanggung_jawab = u.username
                WHERE u.role = 'admin' AND t.pemberi_tugas = 'kabid'";

// Tambahkan filter jika ada
if (!empty($bulan) && !empty($tahun)) {
    $filter_date = " AND MONTH(t.tanggal_mulai) = '$bulan' AND YEAR(t.tanggal_mulai) = '$tahun'";
    $base_query .= $filter_date;
} elseif (!empty($bulan)) {
    $filter_date = " AND MONTH(t.tanggal_mulai) = '$bulan'";
    $base_query .= $filter_date;
} elseif (!empty($tahun)) {
    $filter_date = " AND YEAR(t.tanggal_mulai) = '$tahun'";
    $base_query .= $filter_date;
}

// Tambahkan filter status jika ada
if (!empty($status)) {
    $filter_status = " AND t.status = '$status'";
    $base_query .= $filter_status;
}

// Tambahkan filter admin jika ada
if (!empty($admin)) {
    $base_query .= " AND t.penanggung_jawab = '$admin'";
}

// Tambahkan filter platform jika ada
if (!empty($platform)) {
    $base_query .= " AND t.platform = '$platform'";
}

// Query untuk tugas yang diberikan oleh kabid
$query = "SELECT t.* " . $base_query . " ORDER BY t.tanggal_mulai DESC";
$result = mysqli_query($conn, $query);

// Menghitung total tugas yang diberikan oleh kabid
$count_query = "SELECT COUNT(*) as total " . $base_query;
$count_result = mysqli_query($conn, $count_query);
$total_tugas = mysqli_fetch_assoc($count_result)['total'];

// Mendapatkan daftar semua admin untuk filter
$admin_list_query = "SELECT DISTINCT username FROM users WHERE role = 'admin' ORDER BY username";
$admin_list_result = mysqli_query($conn, $admin_list_query);

// Mendapatkan daftar platform untuk filter
$platform_list_query = "SELECT DISTINCT platform FROM tugas_media ORDER BY platform";
$platform_list_result = mysqli_query($conn, $platform_list_query);

// Nama bulan dalam bahasa Indonesia
$nama_bulan = [
    '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
    '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
    '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Query untuk tugas anggota yang berstatus selesai
$anggota_selesai_query = "SELECT t.*, u.username 
                          FROM tugas_media t 
                          JOIN users u ON t.penanggung_jawab = u.username 
                          WHERE u.role = 'anggota' 
                          AND t.status = 'Selesai'
                          ORDER BY t.tanggal_mulai DESC 
                          LIMIT 10";
$anggota_selesai_result = mysqli_query($conn, $anggota_selesai_query);

// Ekspor ke CSV jika diminta
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set header untuk download file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=daftar_tugas_kabid_' . date('Y-m-d') . '.csv');
    
    // Buat file pointer untuk output
    $output = fopen('php://output', 'w');
    
    // Tambahkan BOM untuk UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header kolom dalam bahasa Indonesia
    fputcsv($output, [
        'No', 
        'Judul',
        'Platform',
        'Deskripsi',
        'Status',
        'Tanggal Mulai',
        'Deadline',
        'Link',
        'Penanggung Jawab',
        'Catatan'
    ]);
    
    // Reset pointer hasil query
    mysqli_data_seek($result, 0);
    
    // Tambahkan data
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $deadline_date = isset($row['deadline']) ? new DateTime($row['deadline']) : new DateTime();
        $today = new DateTime();
        $interval = $today->diff($deadline_date);
        $days_remaining = $interval->days;
        $deadline_text = isset($row['deadline']) ? date('d/m/Y', strtotime($row['deadline'])) : 'Tidak ada deadline';
        
        if (isset($row['deadline']) && $today > $deadline_date && $row['status'] != 'Selesai') {
            $deadline_text .= " (Terlewat)";
        } elseif (isset($row['deadline']) && $days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
            $deadline_text .= " ($days_remaining hari lagi)";
        }
        
        $catatan = !empty($row['catatan_admin']) ? $row['catatan_admin'] : '';
        
        fputcsv($output, [
            $no++,
            $row['judul'],
            $row['platform'],
            $row['deskripsi'],
            $row['status'],
            isset($row['tanggal_mulai']) ? date('d/m/Y', strtotime($row['tanggal_mulai'])) : 'Tidak ada tanggal',
            $deadline_text,
            isset($row['link_drive']) ? $row['link_drive'] : '',
            isset($row['penanggung_jawab']) ? $row['penanggung_jawab'] : '',
            $catatan
        ]);
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tugas - Kepala Bidang</title>
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
        color: #5a5c69;
    }
    
    .table thead th {
        background-color: #f8f9fc;
        border-bottom: 2px solid #e3e6f0;
        font-weight: 700;
        color: #6e707e;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-size: 0.8rem;
        padding: 0.75rem;
    }
    
    .table tbody tr:hover {
        background-color: rgba(240, 240, 250, 0.5);
    }
    
    .badge {
        font-weight: 600;
        font-size: 0.75rem;
        padding: 0.5em 0.8em;
        border-radius: 0.5rem;
    }
    
    .badge-success {
        background-color: #1cc88a;
        color: white;
    }
    
    .badge-warning {
        background-color: #f6c23e;
        color: white;
    }
    
    .badge-danger {
        background-color: #e74a3b;
        color: white;
    }
    
    .badge-info {
        background-color: #36b9cc;
        color: white;
    }
    
    .badge-primary {
        background-color: #4e73df;
        color: white;
    }
    
    .badge-secondary {
        background-color: #858796;
        color: white;
    }
    
    .btn-primary {
        background-color: #1a472a;
        border-color: #1a472a;
    }
    
    .btn-primary:hover {
        background-color: #2d5a40;
        border-color: #2d5a40;
    }
    
    .btn-outline-primary {
        color: #1a472a;
        border-color: #1a472a;
    }
    
    .btn-outline-primary:hover {
        background-color: #1a472a;
        border-color: #1a472a;
    }
    
    .filter-section {
        background-color: white;
        border-radius: 10px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    }
    
    .filter-section .form-group {
        margin-bottom: 1rem;
    }
    
    .filter-section label {
        font-weight: 600;
        color: #5a5c69;
        margin-bottom: 0.5rem;
    }
    
    .filter-section .form-control {
        border-radius: 0.5rem;
        border: 1px solid #d1d3e2;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .filter-section .form-control:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }
    
    .filter-section .btn {
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        font-weight: 600;
    }
    
    .pagination {
        margin-top: 1rem;
        justify-content: center;
    }
    
    .pagination .page-item .page-link {
        color: #1a472a;
        border: 1px solid #dddfeb;
        margin: 0 0.2rem;
        border-radius: 0.35rem;
        padding: 0.5rem 0.75rem;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #1a472a;
        border-color: #1a472a;
        color: white;
    }
    
    .pagination .page-item .page-link:hover {
        background-color: #eaecf4;
    }
    
    .pagination .page-item.disabled .page-link {
        color: #858796;
    }
    
    .deadline-approaching {
        color: #e74a3b;
        font-weight: 600;
    }
    
    .deadline-passed {
        color: #e74a3b;
        font-weight: 700;
    }
    
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    
    .status-pending {
        background-color: #f6c23e;
    }
    
    .status-progress {
        background-color: #4e73df;
    }
    
    .status-review {
        background-color: #36b9cc;
    }
    
    .status-completed {
        background-color: #1cc88a;
    }
    
    .status-rejected {
        background-color: #e74a3b;
    }
    
    @media (max-width: 768px) {
        :root {
            --sidebar-width: 0px;
            --sidebar-collapsed-width: 0px;
        }
            
        #sidebar-wrapper {
            width: 250px;
            left: -250px;
            transition: left var(--transition-speed) ease;
        }
            
        #sidebar-wrapper.show {
            left: 0;
        }
            
        #content-wrapper {
            margin-left: 0;
        }
            
        #content-wrapper.expanded {
            margin-left: 0;
        }
            
        .table-responsive {
            overflow-x: auto;
        }
            
        .filter-section .row > div {
            margin-bottom: 1rem;
        }
    }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Kepala Bidang Dashboard</div>
            <div class="list-group">
                <a href="dashboard_kabid.php" class="list-group-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="daftar_tugas_kabid.php" class="list-group-item active">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
                </a>
                <a href="tambah_tugas_kabid.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="laporan_kabid.php" class="list-group-item">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Laporan</span>
                </a>
                <a href="pengaturan_kabid.php" class="list-group-item">
                    <i class="bi bi-gear"></i>
                    <span>Pengaturan</span>
                </a>
                <a href="../auth/logout.php" class="list-group-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
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
                    <span class="user-name"><?php echo $_SESSION["username"]; ?></span>
                    <a href="../auth/logout.php" class="btn btn-sm btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </nav>
                    
            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Daftar Tugas</h1>
                                    
                    <!-- Filter Section -->
                    <div class="filter-section animate__animated animate__fadeIn">
                        <form method="GET" action="" class="mb-0">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="bulan">Bulan</label>
                                        <select class="form-select" id="bulan" name="bulan">
                                            <option value="">Semua Bulan</option>
                                            <?php foreach ($nama_bulan as $num => $nama) : ?>
                                                <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>>
                                                    <?= $nama ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="tahun">Tahun</label>
                                        <select class="form-select" id="tahun" name="tahun">
                                            <option value="">Semua Tahun</option>
                                            <?php
                                            $current_year = date('Y');
                                            for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                                echo "<option value='$y'" . ($tahun == $y ? ' selected' : '') . ">$y</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">Semua Status</option>
                                            <option value="Belum Dikerjakan" <?= $status == 'Belum Dikerjakan' ? 'selected' : '' ?>>Belum Dikerjakan</option>
                                            <option value="Sedang Dikerjakan" <?= $status == 'Sedang Dikerjakan' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                                            <option value="Menunggu Review" <?= $status == 'Menunggu Review' ? 'selected' : '' ?>>Menunggu Review</option>
                                            <option value="Selesai" <?= $status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                            <option value="Revisi" <?= $status == 'Revisi' ? 'selected' : '' ?>>Revisi</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="admin">Admin</label>
                                        <select class="form-select" id="admin" name="admin">
                                            <option value="">Semua Admin</option>
                                            <?php while ($admin_row = mysqli_fetch_assoc($admin_list_result)) : ?>
                                                <option value="<?= $admin_row['username'] ?>" <?= $admin == $admin_row['username'] ? 'selected' : '' ?>>
                                                    <?= $admin_row['username'] ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="platform">Platform</label>
                                        <select class="form-select" id="platform" name="platform">
                                            <option value="">Semua Platform</option>
                                            <?php while ($platform_row = mysqli_fetch_assoc($platform_list_result)) : ?>
                                                <option value="<?= $platform_row['platform'] ?>" <?= $platform == $platform_row['platform'] ? 'selected' : '' ?>>
                                                    <?= $platform_row['platform'] ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="d-block">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="bi bi-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                                    
                    <!-- Tugas Card -->
                    <div class="card animate__animated animate__fadeIn">
                        <div class="card-header">
                            <h5 class="m-0 font-weight-bold">Daftar Tugas (<?= $total_tugas ?> tugas)</h5>
                            <div>
                                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-file-earmark-excel"></i> Export CSV
                                </a>
                                <a href="tambah_tugas_kabid.php" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Tambah Tugas
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Judul</th>
                                            <th>Platform</th>
                                            <th>Status</th>
                                            <th>Tanggal Mulai</th>
                                            <th>Deadline</th>
                                            <th>Penanggung Jawab</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (mysqli_num_rows($result) > 0) {
                                            $no = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                $deadline_date = isset($row['deadline']) ? new DateTime($row['deadline']) : new DateTime();
                                                $today = new DateTime();
                                                $interval = $today->diff($deadline_date);
                                                $days_remaining = $interval->days;
                                                
                                                // Status badge
                                                $status_badge = '';
                                                switch ($row['status']) {
                                                    case 'Belum Dikerjakan':
                                                        $status_badge = '<span class="badge badge-secondary"><i class="bi bi-clock"></i> Belum Dikerjakan</span>';
                                                        break;
                                                    case 'Sedang Dikerjakan':
                                                        $status_badge = '<span class="badge badge-primary"><i class="bi bi-gear"></i> Sedang Dikerjakan</span>';
                                                        break;
                                                    case 'Menunggu Review':
                                                        $status_badge = '<span class="badge badge-info"><i class="bi bi-eye"></i> Menunggu Review</span>';
                                                        break;
                                                    case 'Selesai':
                                                        $status_badge = '<span class="badge badge-success"><i class="bi bi-check-circle"></i> Selesai</span>';
                                                        break;
                                                    case 'Revisi':
                                                        $status_badge = '<span class="badge badge-warning"><i class="bi bi-pencil"></i> Revisi</span>';
                                                        break;
                                                    default:
                                                        $status_badge = '<span class="badge badge-secondary">' . $row['status'] . '</span>';
                                                }
                                                
                                                // Deadline formatting
                                                $deadline_text = isset($row['deadline']) ? date('d/m/Y', strtotime($row['deadline'])) : 'Tidak ada deadline';
                                                $deadline_class = '';
                                                
                                                if (isset($row['deadline']) && $today > $deadline_date && $row['status'] != 'Selesai') {
                                                    $deadline_class = 'deadline-passed';
                                                    $deadline_text .= " <span class='badge badge-danger'><i class='bi bi-exclamation-triangle'></i> Terlewat</span>";
                                                } elseif (isset($row['deadline']) && $days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                    $deadline_class = 'deadline-approaching';
                                                    $deadline_text .= " <span class='badge badge-warning'><i class='bi bi-exclamation-circle'></i> $days_remaining hari lagi</span>";
                                                }
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['judul']) ?></strong>
                                                <?php if (!empty($row['deskripsi'])) : ?>
                                                    <br><small class="text-muted"><?= substr(htmlspecialchars($row['deskripsi']), 0, 50) . (strlen($row['deskripsi']) > 50 ? '...' : '') ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['platform']) ?></td>
                                            <td><?= $status_badge ?></td>
                                            <td><?= isset($row['tanggal_mulai']) ? date('d/m/Y', strtotime($row['tanggal_mulai'])) : 'Tidak ada tanggal' ?></td>
                                            <td class="<?= $deadline_class ?>"><?= $deadline_text ?></td>
                                            <td><?= htmlspecialchars($row['penanggung_jawab']) ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="detail_tugas.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit_tugas.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $row['id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="8" class="text-center">Tidak ada tugas yang ditemukan</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                   <!-- Tugas Anggota Selesai Card -->
<div class="card animate__animated animate__fadeIn">
    <div class="card-header">
        <h5 class="m-0 font-weight-bold">Tugas Anggota yang Telah Selesai</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Platform</th>
                        <th>Link</th>
                        <th>Penanggung Jawab</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($anggota_selesai_result) > 0) {
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($anggota_selesai_result)) {
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['judul']) ?></strong>
                            <?php if (!empty($row['deskripsi'])) : ?>
                                <br><small class="text-muted"><?= substr(htmlspecialchars($row['deskripsi']), 0, 50) . (strlen($row['deskripsi']) > 50 ? '...' : '') ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['platform']) ?></td>
                        <td>
                            <?php if (!empty($row['link_drive'])) : ?>
                                <a href="<?= htmlspecialchars($row['link_drive']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-link-45deg"></i> Lihat
                                </a>
                            <?php else : ?>
                                <span class="text-muted">Tidak ada link</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['penanggung_jawab']) ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="detail_tugas.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">Tidak ada tugas anggota yang selesai</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus tugas ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set body as loaded
        document.body.classList.add('loaded');
        
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarWrapper = document.getElementById('sidebar-wrapper');
        const contentWrapper = document.getElementById('content-wrapper');
        
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebarWrapper.classList.toggle('collapsed');
            contentWrapper.classList.toggle('expanded');
        });
        
        // Handle responsive sidebar
        function handleResize() {
            if (window.innerWidth < 768) {
                sidebarWrapper.classList.add('collapsed');
                contentWrapper.classList.add('expanded');
            } else {
                // You can choose to reset to default state or keep the current state
                // sidebarWrapper.classList.remove('collapsed');
                // contentWrapper.classList.remove('expanded');
            }
        }
        
        // Initial check
        handleResize();
        
        // Listen for window resize
        window.addEventListener('resize', handleResize);
    });
    
    // Delete confirmation
    function confirmDelete(id) {
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        confirmDeleteBtn.href = 'hapus_tugas.php?id=' + id;
        deleteModal.show();
    }
    </script>
</body>
</html>

