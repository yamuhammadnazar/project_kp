<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Filter bulan, tahun, dan status
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : '';
$anggota = isset($_GET['anggota']) ? $_GET['anggota'] : '';

// Base query untuk tugas dari kabid
$base_query_kabid = "FROM tugas_media WHERE penanggung_jawab = '$username' AND pemberi_tugas = 'kabid'";

// Base query untuk tugas anggota
$base_query_anggota = "FROM tugas_media WHERE pemberi_tugas = 'admin'";

// Tambahkan filter jika ada
if (!empty($bulan) && !empty($tahun)) {
    $filter_date = " AND MONTH(tanggal_mulai) = '$bulan' AND YEAR(tanggal_mulai) = '$tahun'";
    $base_query_kabid .= $filter_date;
    $base_query_anggota .= $filter_date;
} elseif (!empty($bulan)) {
    $filter_date = " AND MONTH(tanggal_mulai) = '$bulan'";
    $base_query_kabid .= $filter_date;
    $base_query_anggota .= $filter_date;
} elseif (!empty($tahun)) {
    $filter_date = " AND YEAR(tanggal_mulai) = '$tahun'";
    $base_query_kabid .= $filter_date;
    $base_query_anggota .= $filter_date;
}

// Tambahkan filter status jika ada
if (!empty($status)) {
    $filter_status = " AND status = '$status'";
    $base_query_kabid .= $filter_status;
    $base_query_anggota .= $filter_status;
}

// Tambahkan filter anggota jika ada
if (!empty($anggota)) {
    $base_query_anggota .= " AND penanggung_jawab = '$anggota'";
}

// Query untuk tugas dari kabid
$query_kabid = "SELECT * " . $base_query_kabid . " ORDER BY tanggal_mulai DESC";
$result_kabid = mysqli_query($conn, $query_kabid);

// Query untuk tugas anggota
$query_anggota = "SELECT * " . $base_query_anggota . " ORDER BY tanggal_mulai DESC";
$result_anggota = mysqli_query($conn, $query_anggota);

// Menghitung statistik tugas kabid
$stats_query_kabid = "SELECT 
    COUNT(*) as total_tugas,
    SUM(CASE WHEN status = 'Belum Dikerjakan' THEN 1 ELSE 0 END) as belum_dikerjakan,
    SUM(CASE WHEN status = 'Sedang Dikerjakan' THEN 1 ELSE 0 END) as sedang_dikerjakan,
    SUM(CASE WHEN status = 'Kirim' THEN 1 ELSE 0 END) as kirim,
    SUM(CASE WHEN status = 'Revisi' THEN 1 ELSE 0 END) as revisi,
    SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai
    " . $base_query_kabid;
$stats_result_kabid = mysqli_query($conn, $stats_query_kabid);
$stats_kabid = mysqli_fetch_assoc($stats_result_kabid);

// Menghitung statistik tugas anggota
$stats_query_anggota = "SELECT 
    COUNT(*) as total_tugas,
    SUM(CASE WHEN status = 'Belum Dikerjakan' THEN 1 ELSE 0 END) as belum_dikerjakan,
    SUM(CASE WHEN status = 'Sedang Dikerjakan' THEN 1 ELSE 0 END) as sedang_dikerjakan,
    SUM(CASE WHEN status = 'Kirim' THEN 1 ELSE 0 END) as kirim,
    SUM(CASE WHEN status = 'Revisi' THEN 1 ELSE 0 END) as revisi,
    SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai
    " . $base_query_anggota;
$stats_result_anggota = mysqli_query($conn, $stats_query_anggota);
$stats_anggota = mysqli_fetch_assoc($stats_result_anggota);

// Menghitung tugas yang melewati deadline
$overdue_query = "SELECT COUNT(*) as total_overdue FROM tugas_media WHERE deadline < CURDATE() AND status != 'Selesai'";
$overdue_result = mysqli_query($conn, $overdue_query);
$overdue = mysqli_fetch_assoc($overdue_result)['total_overdue'];

// Menghitung tugas yang deadline-nya dalam 3 hari ke depan
$upcoming_query = "SELECT COUNT(*) as total_upcoming FROM tugas_media WHERE deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND status != 'Selesai'";
$upcoming_result = mysqli_query($conn, $upcoming_query);
$upcoming = mysqli_fetch_assoc($upcoming_result)['total_upcoming'];

// Menghitung persentase penyelesaian tugas anggota
$completion_percentage_anggota = 0;
if ($stats_anggota['total_tugas'] > 0) {
    $completion_percentage_anggota = round(($stats_anggota['selesai'] / $stats_anggota['total_tugas']) * 100);
}

// Statistik: Anggota dengan tugas terbanyak
$anggota_query = "SELECT penanggung_jawab, COUNT(*) as jumlah_tugas 
                 FROM tugas_media 
                 WHERE pemberi_tugas = 'admin'
                 GROUP BY penanggung_jawab 
                 ORDER BY jumlah_tugas DESC 
                 LIMIT 5";
$anggota_result = mysqli_query($conn, $anggota_query);

// Statistik: Platform terbanyak
$platform_query = "SELECT platform, COUNT(*) as jumlah 
                  FROM tugas_media 
                  GROUP BY platform 
                  ORDER BY jumlah DESC 
                  LIMIT 3";
$platform_result = mysqli_query($conn, $platform_query);

// Statistik: Tugas yang sering overdue
$overdue_detail_query = "SELECT id, judul, penanggung_jawab, deadline, DATEDIFF(CURDATE(), deadline) as hari_terlambat 
                        FROM tugas_media 
                        WHERE deadline < CURDATE() 
                        AND status != 'Selesai'
                        ORDER BY hari_terlambat DESC 
                        LIMIT 5";
$overdue_detail_result = mysqli_query($conn, $overdue_detail_query);

// Mendapatkan daftar semua anggota untuk filter
$anggota_list_query = "SELECT DISTINCT username FROM users WHERE role = 'anggota' ORDER BY username";
$anggota_list_result = mysqli_query($conn, $anggota_list_query);

// Ekspor ke CSV jika diminta
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set header untuk download file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_tugas_admin_' . date('Y-m-d') . '.csv');
    
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
    mysqli_data_seek($result_anggota, 0);
    
    // Tambahkan data
    $no = 1;
    while ($row = mysqli_fetch_assoc($result_anggota)) {
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
        
        $catatan = !empty($row['catatan_admin']) ? $row['catatan_admin'] : (isset($row['catatan']) ? $row['catatan'] : '');
        
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

// Nama bulan dalam bahasa Indonesia
$nama_bulan = [
    '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
    '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
    '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Animate.css untuk animasi -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin/main.css">
    <style>
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h6 {
            margin: 0;
            font-weight: 700;
            color: #4e73df;
        }
        
        .card-header .card-tools {
            display: flex;
            align-items: center;
        }
        
        .card-header .card-tools .btn {
            margin-left: 0.5rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .stats-card {
            border-left: 0.25rem solid;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .stats-card .card-body {
            padding: 1.25rem;
        }
        
        .stats-card .card-title {
            text-transform: uppercase;
            color: #4e73df;
            font-weight: 700;
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .stats-card .card-value {
            color: #5a5c69;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0;
        }
        
        .stats-card .card-icon {
            position: absolute;
            top: 50%;
            right: 1.25rem;
            transform: translateY(-50%);
            font-size: 2rem;
            opacity: 0.3;
        }
        
        .stats-card.primary {
            border-left-color: #4e73df;
        }
        
        .stats-card.primary .card-title {
            color: #4e73df;
        }
        
        .stats-card.success {
            border-left-color: #1cc88a;
        }
        
        .stats-card.success .card-title {
            color: #1cc88a;
        }
        
        .stats-card.info {
            border-left-color: #36b9cc;
        }
        
        .stats-card.info .card-title {
            color: #36b9cc;
        }
        
        .stats-card.warning {
            border-left-color: #f6c23e;
        }
        
        .stats-card.warning .card-title {
            color: #f6c23e;
        }
        
        .stats-card.danger {
            border-left-color: #e74a3b;
        }
        
        .stats-card.danger .card-title {
            color: #e74a3b;
        }
        
        .progress {
            height: 0.5rem;
            margin-top: 0.5rem;
            border-radius: 0.25rem;
            background-color: #eaecf4;
        }
        
        .progress-bar {
            border-radius: 0.25rem;
        }
        
        .table {
            color: #5a5c69;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            font-weight: 700;
            color: #4e73df;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }
        
        .badge-primary {
            background-color: #4e73df;
        }
        
        .badge-success {
            background-color: #1cc88a;
        }
        
        .badge-info {
            background-color: #36b9cc;
        }
        
        .badge-warning {
            background-color: #f6c23e;
            color: #fff;
        }
        
        .badge-danger {
            background-color: #e74a3b;
        }
        
        .badge-secondary {
            background-color: #858796;
        }
        
        .btn-circle {
            border-radius: 100%;
            height: 2.5rem;
            width: 2.5rem;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-circle.btn-sm {
            height: 1.8rem;
            width: 1.8rem;
            font-size: 0.75rem;
        }
        
        .btn-icon-split {
            display: inline-flex;
            align-items: stretch;
            overflow: hidden;
        }
        
        .btn-icon-split .icon {
            background: rgba(0, 0, 0, 0.15);
            display: inline-block;
            padding: 0.375rem 0.75rem;
        }
        
        .btn-icon-split .text {
            display: inline-block;
            padding: 0.375rem 0.75rem;
        }
        
        .filter-form {
            background-color: #fff;
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .deadline-warning {
            color: #e74a3b;
            font-weight: 600;
        }
        
        .deadline-close {
            color: #f6c23e;
            font-weight: 600;
        }
        
        .action-buttons .btn {
            margin-right: 0.25rem;
        }
        
        .action-buttons .btn:last-child {
            margin-right: 0;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .dropdown-menu {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
        }
        
        .dropdown-item:active {
            background-color: #4e73df;
        }
        
        .dropdown-item i {
            margin-right: 0.5rem;
            width: 1rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            #sidebar-wrapper {
                width: 0;
                transform: translateX(-100%);
            }
            
            #sidebar-wrapper.show {
                width: var(--sidebar-width);
                transform: translateX(0);
            }
            
            #content-wrapper {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            .stats-card .card-icon {
                display: none;
            }
            
            .topbar .toggle-sidebar {
                display: block;
            }
        }
        
        /* Animasi untuk elemen */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Tooltip styling */
        .custom-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .custom-tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .custom-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Perbaikan untuk sidebar */
        #sidebar-wrapper::-webkit-scrollbar {
            width: 5px;
        }
        
        #sidebar-wrapper::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }
        
        #sidebar-wrapper::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        #sidebar-wrapper::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        /* Perbaikan untuk navbar collapsed */
        #sidebar-wrapper.collapsed .list-group-item {
            padding: 0.8rem 0;
            justify-content: center;
        }
        
        /* Overlay untuk mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        
        @media (max-width: 768px) {
            .sidebar-overlay.show {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Media Admin</div>
            <div class="list-group">
                <a href="#" class="list-group-item active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/tambah_tugas_anggota.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../views/tugas_anggota.php" class="list-group-item">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
                </a>
                <a href="../views/tugas_kabid.php" class="list-group-item">
                    <i class="bi bi-briefcase"></i>
                    <span>Tugas dari Kabid</span>
                </a>
                <a href="../views/laporan.php" class="list-group-item">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Laporan</span>
                </a>
                <a href="../views/profil.php" class="list-group-item">
                    <i class="bi bi-person"></i>
                    <span>Profil</span>
                </a>
                <a href="../auth/logout.php" class="list-group-item">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Content Wrapper -->
        <div id="content-wrapper">
            <!-- Topbar -->
            <div class="topbar">
                <button id="sidebarToggle" class="toggle-sidebar">
                    <i class="bi bi-list"></i>
                </button>
                
                <div class="topbar-divider"></div>
                
                <div class="d-flex align-items-center">
                    <a href="?export=csv" class="btn btn-sm btn-success me-2">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
                    </a>
                </div>
                
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                    <span class="user-role badge bg-primary">Admin</span>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="content">
                <h1 class="page-title fade-in">Dashboard Admin</h1>
                
                <!-- Statistik Utama -->
                <div class="row fade-in">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card primary h-100">
                            <div class="card-body">
                                <div class="card-title">Total Tugas</div>
                                <div class="card-value"><?php echo $stats_anggota['total_tugas']; ?></div>
                                <i class="bi bi-clipboard-check card-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card success h-100">
                            <div class="card-body">
                                <div class="card-title">Tugas Selesai</div>
                                <div class="card-value"><?php echo $stats_anggota['selesai']; ?></div>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_percentage_anggota; ?>%" aria-valuenow="<?php echo $completion_percentage_anggota; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="small mt-1"><?php echo $completion_percentage_anggota; ?>% selesai</div>
                                <i class="bi bi-check-circle card-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card warning h-100">
                            <div class="card-body">
                                <div class="card-title">Deadline Dekat</div>
                                <div class="card-value"><?php echo $upcoming; ?></div>
                                <div class="small mt-1">Dalam 3 hari ke depan</div>
                                <i class="bi bi-clock-history card-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stats-card danger h-100">
                            <div class="card-body">
                                <div class="card-title">Tugas Terlambat</div>
                                <div class="card-value"><?php echo $overdue; ?></div>
                                <div class="small mt-1">Melewati deadline</div>
                                <i class="bi bi-exclamation-triangle card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Form -->
                <div class="card mb-4 fade-in">
                    <div class="card-header">
                        <h6><i class="bi bi-funnel me-1"></i> Filter Tugas</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label for="bulan" class="form-label">Bulan</label>
                                <select class="form-select" id="bulan" name="bulan">
                                    <option value="">Semua Bulan</option>
                                    <?php foreach ($nama_bulan as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $bulan == $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="tahun" class="form-label">Tahun</label>
                                <select class="form-select" id="tahun" name="tahun">
                                    <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="Belum Dikerjakan" <?php echo $status == 'Belum Dikerjakan' ? 'selected' : ''; ?>>Belum Dikerjakan</option>
                                    <option value="Sedang Dikerjakan" <?php echo $status == 'Sedang Dikerjakan' ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                    <option value="Kirim" <?php echo $status == 'Kirim' ? 'selected' : ''; ?>>Kirim</option>
                                    <option value="Revisi" <?php echo $status == 'Revisi' ? 'selected' : ''; ?>>Revisi</option>
                                    <option value="Selesai" <?php echo $status == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="anggota" class="form-label">Anggota</label>
                                <select class="form-select" id="anggota" name="anggota">
                                    <option value="">Semua Anggota</option>
                                    <?php 
                                    // Reset pointer hasil query
                                    mysqli_data_seek($anggota_list_result, 0);
                                    while ($row = mysqli_fetch_assoc($anggota_list_result)): 
                                    ?>
                                        <option value="<?php echo $row['username']; ?>" <?php echo $anggota == $row['username'] ? 'selected' : ''; ?>>
                                            <?php echo $row['username']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tugas dari Kabid -->
                <div class="card mb-4 fade-in">
                    <div class="card-header">
                        <h6><i class="bi bi-briefcase me-1"></i> Tugas dari Kepala Bidang</h6>
                        <div class="card-tools">
                            <span class="badge bg-primary"><?php echo mysqli_num_rows($result_kabid); ?> tugas</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Platform</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Deadline</th>
                                        <th>Link Drive</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($result_kabid) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result_kabid)): ?>
                                            <?php
                                                $deadline_date = isset($row['deadline']) ? new DateTime($row['deadline']) : null;
                                                $today = new DateTime();
                                                $deadline_class = '';
                                                $deadline_text = isset($row['deadline']) ? date('d-m-Y', strtotime($row['deadline'])) : 'Tidak ada deadline';
                                                
                                                if ($deadline_date) {
                                                    $interval = $today->diff($deadline_date);
                                                    $days_remaining = $interval->days;
                                                    
                                                    if ($today > $deadline_date && $row['status'] != 'Selesai') {
                                                        $deadline_class = 'deadline-warning';
                                                        $deadline_text .= " (Terlewat)";
                                                    } elseif ($days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                        $deadline_class = 'deadline-close';
                                                        $deadline_text .= " ($days_remaining hari lagi)";
                                                    }
                                                }
                                                
                                                // Status badge
                                                $status_badge = '';
                                                switch ($row['status']) {
                                                    case 'Belum Dikerjakan':
                                                        $status_badge = 'bg-secondary';
                                                        break;
                                                    case 'Sedang Dikerjakan':
                                                        $status_badge = 'bg-info';
                                                        break;
                                                    case 'Kirim':
                                                        $status_badge = 'bg-primary';
                                                        break;
                                                    case 'Revisi':
                                                        $status_badge = 'bg-warning';
                                                        break;
                                                    case 'Selesai':
                                                        $status_badge = 'bg-success';
                                                        break;
                                                    default:
                                                        $status_badge = 'bg-secondary';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                                <td><?php echo htmlspecialchars($row['platform']); ?></td>
                                                <td><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                                                <td><span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                                <td><?php echo isset($row['tanggal_mulai']) ? date('d-m-Y', strtotime($row['tanggal_mulai'])) : 'Tidak ada tanggal'; ?></td>
                                                <td class="<?php echo $deadline_class; ?>"><?php echo $deadline_text; ?></td>
                                                <td>
                                                    <?php if (isset($row['link_drive']) && $row['link_drive']): ?>
                                                        <a href="<?php echo htmlspecialchars($row['link_drive']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-link-45deg"></i> Lihat File
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum ada link</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="../modules/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="../views/catatan_admin.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-chat-left-text"></i>
                                                        </a>
                                                        <a href="../modules/hapus_tugas.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                                                                        <td colspan="8" class="text-center">Tidak ada tugas dari Kepala Bidang</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tugas Anggota -->
                <div class="card mb-4 fade-in">
                    <div class="card-header">
                        <h6><i class="bi bi-people me-1"></i> Tugas Anggota</h6>
                        <div class="card-tools">
                            <a href="../modules/tambah_tugas_anggota.php" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg"></i> Tambah Tugas
                            </a>
                            <span class="badge bg-primary ms-2"><?php echo mysqli_num_rows($result_anggota); ?> tugas</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Platform</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Tanggal Mulai</th>
                                        <th>Deadline</th>
                                        <th>Link Drive</th>
                                        <th>Penanggung Jawab</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($result_anggota) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result_anggota)): ?>
                                            <?php
                                                $deadline_date = isset($row['deadline']) ? new DateTime($row['deadline']) : null;
                                                $today = new DateTime();
                                                $deadline_class = '';
                                                $deadline_text = isset($row['deadline']) ? date('d-m-Y', strtotime($row['deadline'])) : 'Tidak ada deadline';
                                                
                                                if ($deadline_date) {
                                                    $interval = $today->diff($deadline_date);
                                                    $days_remaining = $interval->days;
                                                    
                                                    if ($today > $deadline_date && $row['status'] != 'Selesai') {
                                                        $deadline_class = 'deadline-warning';
                                                        $deadline_text .= " (Terlewat)";
                                                    } elseif ($days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                        $deadline_class = 'deadline-close';
                                                        $deadline_text .= " ($days_remaining hari lagi)";
                                                    }
                                                }
                                                
                                                // Status badge
                                                $status_badge = '';
                                                switch ($row['status']) {
                                                    case 'Belum Dikerjakan':
                                                        $status_badge = 'bg-secondary';
                                                        break;
                                                    case 'Sedang Dikerjakan':
                                                        $status_badge = 'bg-info';
                                                        break;
                                                    case 'Kirim':
                                                        $status_badge = 'bg-primary';
                                                        break;
                                                    case 'Revisi':
                                                        $status_badge = 'bg-warning';
                                                        break;
                                                    case 'Selesai':
                                                        $status_badge = 'bg-success';
                                                        break;
                                                    default:
                                                        $status_badge = 'bg-secondary';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                                <td><?php echo htmlspecialchars($row['platform']); ?></td>
                                                <td><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                                                <td><span class="badge <?php echo $status_badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                                <td><?php echo isset($row['tanggal_mulai']) ? date('d-m-Y', strtotime($row['tanggal_mulai'])) : 'Tidak ada tanggal'; ?></td>
                                                <td class="<?php echo $deadline_class; ?>"><?php echo $deadline_text; ?></td>
                                                <td>
                                                    <?php if (isset($row['link_drive']) && $row['link_drive']): ?>
                                                        <a href="<?php echo htmlspecialchars($row['link_drive']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-link-45deg"></i> Lihat File
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum ada link</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <a href="../modules/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="../views/catatan_admin.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-chat-left-text"></i>
                                                        </a>
                                                        <a href="../modules/hapus_tugas.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">Tidak ada tugas anggota</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Statistik Tambahan -->
                <div class="row fade-in">
                    <!-- Statistik Anggota -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="bi bi-bar-chart me-1"></i> Anggota dengan Tugas Terbanyak</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Anggota</th>
                                                <th>Jumlah Tugas</th>
                                                <th>Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            mysqli_data_seek($anggota_result, 0);
                                            if (mysqli_num_rows($anggota_result) > 0):
                                                while ($row = mysqli_fetch_assoc($anggota_result)): 
                                                    // Hitung persentase tugas selesai untuk anggota ini
                                                    $anggota_name = $row['penanggung_jawab'];
                                                    $anggota_stats_query = "SELECT 
                                                        COUNT(*) as total,
                                                        SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai
                                                        FROM tugas_media 
                                                        WHERE penanggung_jawab = '$anggota_name'";
                                                    $anggota_stats_result = mysqli_query($conn, $anggota_stats_query);
                                                    $anggota_stats = mysqli_fetch_assoc($anggota_stats_result);
                                                    
                                                    $completion_percentage = 0;
                                                    if ($anggota_stats['total'] > 0) {
                                                        $completion_percentage = round(($anggota_stats['selesai'] / $anggota_stats['total']) * 100);
                                                    }
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                                                    <td><?php echo $row['jumlah_tugas']; ?></td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_percentage; ?>%" aria-valuenow="<?php echo $completion_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="small mt-1"><?php echo $completion_percentage; ?>% selesai</div>
                                                    </td>
                                                </tr>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">Tidak ada data anggota</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistik Platform -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="bi bi-pie-chart me-1"></i> Platform Terbanyak</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Platform</th>
                                                <th>Jumlah Tugas</th>
                                                <th>Persentase</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            mysqli_data_seek($platform_result, 0);
                                            $total_platform_query = "SELECT COUNT(*) as total FROM tugas_media";
                                            $total_platform_result = mysqli_query($conn, $total_platform_query);
                                            $total_platform = mysqli_fetch_assoc($total_platform_result)['total'];
                                            
                                            if (mysqli_num_rows($platform_result) > 0):
                                                while ($row = mysqli_fetch_assoc($platform_result)): 
                                                    $platform_percentage = 0;
                                                    if ($total_platform > 0) {
                                                        $platform_percentage = round(($row['jumlah'] / $total_platform) * 100);
                                                    }
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['platform']); ?></td>
                                                    <td><?php echo $row['jumlah']; ?></td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $platform_percentage; ?>%" aria-valuenow="<?php echo $platform_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                        <div class="small mt-1"><?php echo $platform_percentage; ?>%</div>
                                                    </td>
                                                </tr>
                                            <?php 
                                                endwhile;
                                            else:
                                            ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">Tidak ada data platform</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tugas Terlambat -->
                    <div class="col-lg-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h6><i class="bi bi-exclamation-triangle me-1"></i> Tugas yang Melewati Deadline</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Judul</th>
                                                <th>Penanggung Jawab</th>
                                                <th>Deadline</th>
                                                <th>Terlambat</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            mysqli_data_seek($overdue_detail_result, 0);
                                            if (mysqli_num_rows($overdue_detail_result) > 0):
                                                while ($row = mysqli_fetch_assoc($overdue_detail_result)): 
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                                    <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                                                    <td class="deadline-warning"><?php echo isset($row['deadline']) ? date('d-m-Y', strtotime($row['deadline'])) : 'Tidak ada deadline'; ?></td>
                                                    <td><?php echo $row['hari_terlambat']; ?> hari</td>
                                                    <td>
                                                        <a href="../modules/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                    </td>
                                                </tr
                                                </tr>
                                            <?php 
                                                endwhile; 
                                            else:
                                            ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">Tidak ada tugas yang melewati deadline</td>
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
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const contentWrapper = document.getElementById('content-wrapper');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Periksa ukuran layar
                if (window.innerWidth < 768) {
                    // Untuk mobile: tampilkan sidebar penuh dengan overlay
                    sidebarWrapper.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                } else {
                    // Untuk desktop: toggle collapsed state
                    sidebarWrapper.classList.toggle('collapsed');
                    contentWrapper.classList.toggle('collapsed');
                }
            });
            
            // Tutup sidebar saat overlay diklik (untuk mobile)
            sidebarOverlay.addEventListener('click', function() {
                sidebarWrapper.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
            
            // Responsive sidebar
            function checkWidth() {
                if (window.innerWidth < 768) {
                    // Mobile view
                    sidebarWrapper.classList.remove('collapsed');
                    contentWrapper.classList.remove('collapsed');
                    
                    // Jika sidebar terbuka di mobile, tambahkan overlay
                    if (sidebarWrapper.classList.contains('show')) {
                        sidebarOverlay.classList.add('show');
                    }
                } else {
                    // Desktop view
                    sidebarOverlay.classList.remove('show');
                    sidebarWrapper.classList.remove('show');
                    
                    // Jika sebelumnya dalam mode collapsed, pertahankan
                    if (localStorage.getItem('sidebarState') === 'collapsed') {
                        sidebarWrapper.classList.add('collapsed');
                        contentWrapper.classList.add('collapsed');
                    }
                }
            }
            
            // Simpan state sidebar di localStorage
            function saveSidebarState() {
                if (sidebarWrapper.classList.contains('collapsed')) {
                    localStorage.setItem('sidebarState', 'collapsed');
                } else {
                    localStorage.setItem('sidebarState', 'expanded');
                }
            }
            
            // Restore sidebar state dari localStorage
            function restoreSidebarState() {
                if (localStorage.getItem('sidebarState') === 'collapsed') {
                    sidebarWrapper.classList.add('collapsed');
                    contentWrapper.classList.add('collapsed');
                }
            }
            
            // Initial check
            checkWidth();
            restoreSidebarState();
            
            // Check on resize
            window.addEventListener('resize', checkWidth);
            
            // Save state when toggled
            sidebarToggle.addEventListener('click', saveSidebarState);
            
            // Set body as loaded
            document.body.classList.add('loaded');
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>