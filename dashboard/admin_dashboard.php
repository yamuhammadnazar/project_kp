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

// Query untuk mendapatkan semua anggota
$anggota_query = "SELECT username FROM users WHERE role = 'anggota'";
$anggota_result = mysqli_query($conn, $anggota_query);

// Array untuk menyimpan data anggota dan jumlah tugas
$anggota_data = array();

// Isi array dengan semua anggota dan inisialisasi jumlah tugas dengan 0
while ($row = mysqli_fetch_assoc($anggota_result)) {
    $anggota_data[$row['username']] = 0;
}

// Query untuk mendapatkan jumlah tugas per anggota
$tugas_query = "SELECT penanggung_jawab, COUNT(*) as jumlah_tugas 
                FROM tugas_media 
                GROUP BY penanggung_jawab";
$tugas_result = mysqli_query($conn, $tugas_query);

// Update jumlah tugas untuk anggota yang memiliki tugas
while ($row = mysqli_fetch_assoc($tugas_result)) {
    if (isset($anggota_data[$row['penanggung_jawab']])) {
        $anggota_data[$row['penanggung_jawab']] = $row['jumlah_tugas'];
    }
}

// Debugging - cetak data untuk melihat apa yang kita dapatkan
// echo "<pre>"; print_r($anggota_data); echo "</pre>";

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
    <title>Dashboard Staff</title>
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
            --primary-color:rgb(79, 136, 235);;
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
        /* CSS tambahan untuk responsivitas */
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
    
    .chart-container {
        height: 200px;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .row {
        margin-right: -0.5rem;
        margin-left: -0.5rem;
    }
    
    .col-xl-3, .col-xl-4, .col-xl-6, .col-xl-8, .col-md-6 {
        padding-right: 0.5rem;
        padding-left: 0.5rem;
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
        
        .stat-card {
            border-left: 4px solid;
            border-radius: 8px;
        }
        
        .stat-card.primary {
            border-left-color: #4e73df;
        }
        
        .stat-card.success {
            border-left-color: #1cc88a;
        }
        
        .stat-card.warning {
            border-left-color: #f6c23e;
        }
        
        .stat-card.danger {
            border-left-color: #e74a3b;
        }
        
        .stat-card .card-body {
            padding: 1.25rem;
        }
        
        .stat-card .stat-title {
            text-transform: uppercase;
            color: #4e73df;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-card .stat-value {
            color: #5a5c69;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .stat-card .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            margin-top: 1rem;
        }
        
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-top: none;
            background-color: #f8f9fc;
            color: #6e707e;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
        }
        
        .badge-primary {
            background-color: #4e73df;
        }
        
        .badge-success {
            background-color: #1cc88a;
        }
        
        .badge-warning {
            background-color: #f6c23e;
        }
        
        .badge-danger {
            background-color: #e74a3b;
        }
        
        .badge-info {
            background-color: #36b9cc;
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
        
        .filter-form .form-group {
            margin-bottom: 0;
        }
        
        /* Tambahan CSS untuk chart container */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            
            #sidebar-wrapper {
                width: 0;
                overflow: hidden;
            }
            
            #sidebar-wrapper.collapsed {
                width: var(--sidebar-width);
            }
            
            #page-content-wrapper {
                margin-left: 0;
            }
            
            #wrapper.toggled #page-content-wrapper {
                margin-left: 0;
            }
            
            .topbar {
                padding: 0 1rem;
            }
            
            .content {
                padding: 1rem;
            }
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <!-- Sidebar -->
<div id="sidebar-wrapper">
    <div class="sidebar-heading">Media Staff</div>
    <div class="list-group">
        <a href="admin_dashboard.php" class="list-group-item active">
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
            <i class="bi bi-people"></i> <!-- Ikon yang lebih sesuai untuk kelola akun -->
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
                    <h1 class="h3 mb-4 text-gray-800">Dashboard Staff</h1>
                    
                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="bi bi-funnel me-1"></i> Filter Data
                        </div>
                        <div class="card-body">
                            <form method="get" action="" class="row g-3 filter-form">
                                <div class="col-md-3">
                                    <label for="bulan" class="form-label">Bulan</label>
                                    <select class="form-select" id="bulan" name="bulan">
                                        <option value="">Semua Bulan</option>
                                        <?php foreach ($nama_bulan as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo ($bulan == $key) ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="tahun" class="form-label">Tahun</label>
                                    <select class="form-select" id="tahun" name="tahun">
                                        <option value="">Semua Tahun</option>
                                        <?php
                                        $current_year = date('Y');
                                        for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                            echo "<option value=\"$y\"" . (($tahun == $y) ? ' selected' : '') . ">$y</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="Belum Dikerjakan" <?php echo ($status == 'Belum Dikerjakan') ? 'selected' : ''; ?>>Belum Dikerjakan</option>
                                        <option value="Sedang Dikerjakan" <?php echo ($status == 'Sedang Dikerjakan') ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                        <option value="Kirim" <?php echo ($status == 'Kirim') ? 'selected' : ''; ?>>Kirim</option>
                                        <option value="Revisi" <?php echo ($status == 'Revisi') ? 'selected' : ''; ?>>Revisi</option>
                                        <option value="Selesai" <?php echo ($status == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="anggota" class="form-label">Anggota</label>
                                    <select class="form-select" id="anggota" name="anggota">
                                        <option value="">Semua Anggota</option>
                                        <?php 
                                        mysqli_data_seek($anggota_list_result, 0);
                                        while ($row = mysqli_fetch_assoc($anggota_list_result)): ?>
                                            <option value="<?php echo $row['username']; ?>" <?php echo ($anggota == $row['username']) ? 'selected' : ''; ?>>
                                                <?php echo $row['username']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-12 mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search me-1"></i> Filter
                                    </button>
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-repeat me-1"></i> Reset
                                    </a>
                                    <a href="?<?php echo http_build_query($_GET); ?>&export=csv" class="btn btn-success float-end">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Ekspor CSV
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Statistik Kartu -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card primary h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="stat-title">Total Tugas</div>
                                            <div class="stat-value"><?php echo $stats_anggota['total_tugas']; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-clipboard-check stat-icon text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card success h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="stat-title">Tugas Selesai</div>
                                            <div class="stat-value"><?php echo $stats_anggota['selesai']; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-check-circle stat-icon text-success"></i>
                                        </div>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_percentage_anggota; ?>%" aria-valuenow="<?php echo $completion_percentage_anggota; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="small mt-2"><?php echo $completion_percentage_anggota; ?>% selesai</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card warning h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="stat-title">Deadline Dekat</div>
                                            <div class="stat-value"><?php echo $upcoming; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-clock stat-icon text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="small mt-2">Tugas dengan deadline 3 hari ke depan</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card danger h-100">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <div class="stat-title">Melewati Deadline</div>
                                            <div class="stat-value"><?php echo $overdue; ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-exclamation-triangle stat-icon text-danger"></i>
                                        </div>
                                    </div>
                                    <div class="small mt-2">Tugas yang belum selesai dan melewati deadline</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik dan Statistik -->
                    <div class="row">
                        <!-- Status Tugas -->
                        <div class="col-xl-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-pie-chart me-1"></i> Status Tugas
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="statusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Anggota dengan Tugas Terbanyak -->
                        <div class="col-xl-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-people me-1"></i> Anggota dengan Tugas Terbanyak
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="anggotaChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Platform Terbanyak -->
                        <div class="col-xl-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-display me-1"></i> Platform Terbanyak
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Platform</th>
                                                    <th>Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($platform_result)): ?>
                                                <tr>
                                                    <td><?php echo $row['platform']; ?></td>
                                                    <td><span class="badge bg-primary"><?php echo $row['jumlah']; ?></span></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tugas Melewati Deadline -->
                        <div class="col-xl-8 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Tugas Melewati Deadline
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
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
                                                <?php if (mysqli_num_rows($overdue_detail_result) > 0): ?>
                                                    <?php while ($row = mysqli_fetch_assoc($overdue_detail_result)): ?>
                                                    <tr>
                                                        <td><?php echo $row['judul']; ?></td>
                                                        <td><?php echo $row['penanggung_jawab']; ?></td>
                                                        <td><?php echo date('d/m/Y', strtotime($row['deadline'])); ?></td>
                                                        <td><span class="badge bg-danger"><?php echo $row['hari_terlambat']; ?> hari</span></td>
                                                        <td>
                                                        <a href="../modules/tambah_catatan.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Tambah Catatan">
                                                            <i class="bi bi-chat-square-text"></i>
                                                        </a>
                                                        </td>
                                                    </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
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

                    <!-- Daftar Tugas Terbaru -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="bi bi-list-task me-1"></i> Daftar Tugas Terbaru
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Judul</th>
                                            <th>Platform</th>
                                            <th>Penanggung Jawab</th>
                                            <th>Deadline</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $count = 0;
                                        mysqli_data_seek($result_anggota, 0);
                                        while ($row = mysqli_fetch_assoc($result_anggota)) {
                                            if ($count >= 5) break; // Hanya tampilkan 5 tugas terbaru
                                            $count++;
                                            
                                            $status_class = '';
                                            switch ($row['status']) {
                                                case 'Belum Dikerjakan':
                                                    $status_class = 'bg-secondary';
                                                    break;
                                                case 'Sedang Dikerjakan':
                                                    $status_class = 'bg-primary';
                                                    break;
                                                case 'Kirim':
                                                    $status_class = 'bg-info';
                                                    break;
                                                case 'Revisi':
                                                    $status_class = 'bg-warning';
                                                    break;
                                                case 'Selesai':
                                                    $status_class = 'bg-success';
                                                    break;
                                                default:
                                                    $status_class = 'bg-secondary';
                                            }
                                            
                                            $deadline_date = isset($row['deadline']) ? new DateTime($row['deadline']) : new DateTime();
                                            $today = new DateTime();
                                            $deadline_class = '';
                                            
                                            if (isset($row['deadline'])) {
                                                if ($today > $deadline_date && $row['status'] != 'Selesai') {
                                                    $deadline_class = 'text-danger fw-bold';
                                                } elseif ($today->diff($deadline_date)->days <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                    $deadline_class = 'text-warning fw-bold';
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo $row['judul']; ?></td>
                                            <td><?php echo $row['platform']; ?></td>
                                            <td><?php echo $row['penanggung_jawab']; ?></td>
                                            <td class="<?php echo $deadline_class; ?>">
                                                <?php 
                                                    if (isset($row['deadline'])) {
                                                        echo date('d/m/Y', strtotime($row['deadline']));
                                                        if ($today > $deadline_date && $row['status'] != 'Selesai') {
                                                            echo ' <i class="bi bi-exclamation-circle text-danger" title="Melewati deadline"></i>';
                                                        } elseif ($today->diff($deadline_date)->days <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                            echo ' <i class="bi bi-clock text-warning" title="Deadline dekat"></i>';
                                                        }
                                                    } else {
                                                        echo 'Tidak ada deadline';
                                                    }
                                                ?>
                                            </td>
                                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span></td>
                                            <td>
                                                <a href="../modules/edit_tugas.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit Tugas">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="../modules/tambah_catatan.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Tambah Catatan">
                                                    <i class="bi bi-chat-square-text"></i>
                                                </a>
                                            </td>

                                        </tr>
                                        <?php } ?>
                                        
                                        <?php if ($count == 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada tugas yang ditemukan</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <a href="tugas.php" class="btn btn-primary">Lihat Semua Tugas</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Perbaikan JavaScript untuk responsivitas sidebar
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.add('loaded');
    
    const menuToggle = document.getElementById('menu-toggle');
    const sidebarWrapper = document.getElementById('sidebar-wrapper');
    const pageContentWrapper = document.getElementById('page-content-wrapper');
    
    // Tambahkan overlay untuk mobile
    const overlay = document.createElement('div');
    overlay.id = 'sidebarOverlay';
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
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
    
    // Check on resize
    window.addEventListener('resize', checkWidth);
            
            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Belum Dikerjakan', 'Sedang Dikerjakan', 'Kirim', 'Revisi', 'Selesai'],
                    datasets: [{
                        data: [
                            <?php echo $stats_anggota['belum_dikerjakan']; ?>,
                            <?php echo $stats_anggota['sedang_dikerjakan']; ?>,
                            <?php echo $stats_anggota['kirim']; ?>,
                            <?php echo $stats_anggota['revisi']; ?>,
                            <?php echo $stats_anggota['selesai']; ?>
                        ],
                        backgroundColor: [
                            '#858796', // Belum Dikerjakan - abu-abu
                            '#4e73df', // Sedang Dikerjakan - biru
                            '#36b9cc', // Kirim - cyan
                            '#f6c23e', // Revisi - kuning
                            '#1cc88a'  // Selesai - hijau
                        ],
                        hoverBackgroundColor: [
                            '#717380',
                            '#2e59d9',
                            '#2c9faf',
                            '#dda20a',
                            '#17a673'
                        ],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            displayColors: false,
                            caretPadding: 10,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '70%',
                    elements: {
                        arc: {
                            borderWidth: 0
                        }
                    }
                }
            });

            // Anggota Chart
            const anggotaCtx = document.getElementById('anggotaChart').getContext('2d');
            const anggotaChart = new Chart(anggotaCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php
                        foreach ($anggota_data as $username => $jumlah) {
                            echo "'" . $username . "', ";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Jumlah Tugas',
                        data: [
                            <?php
                            foreach ($anggota_data as $jumlah) {
                                echo $jumlah . ", ";
                            }
                            ?>
                        ],
                        backgroundColor: '#4e73df',
                        borderColor: '#4e73df',
                        borderWidth: 1,
                        borderRadius: 5,
                        maxBarThickness: 50
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    layout: {
                        padding: {
                            top: 10,
                            bottom: 10
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: "rgba(0, 0, 0, 0.05)",
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            titleFontSize: 14,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            padding: 15,
                            displayColors: false,
                            caretPadding: 10,
                        }
                    }
                }
            });

        });
    </script>
</body>
</html>

