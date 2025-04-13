<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Filter bulan, tahun, dan status
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : '';
$admin = isset($_GET['admin']) ? $_GET['admin'] : '';

// Base query untuk tugas admin
$base_query_admin = "FROM tugas_media t JOIN users u ON t.penanggung_jawab = u.username WHERE u.role = 'admin'";

// Tambahkan filter jika ada
if (!empty($bulan) && !empty($tahun)) {
    $filter_date = " AND MONTH(t.tanggal_mulai) = '$bulan' AND YEAR(t.tanggal_mulai) = '$tahun'";
    $base_query_admin .= $filter_date;
} elseif (!empty($bulan)) {
    $filter_date = " AND MONTH(t.tanggal_mulai) = '$bulan'";
    $base_query_admin .= $filter_date;
} elseif (!empty($tahun)) {
    $filter_date = " AND YEAR(t.tanggal_mulai) = '$tahun'";
    $base_query_admin .= $filter_date;
}

// Tambahkan filter status jika ada
if (!empty($status)) {
    $filter_status = " AND t.status = '$status'";
    $base_query_admin .= $filter_status;
}

// Tambahkan filter admin jika ada
if (!empty($admin)) {
    $base_query_admin .= " AND t.penanggung_jawab = '$admin'";
}

// Query untuk tugas admin
$query_admin = "SELECT t.* " . $base_query_admin . " ORDER BY t.tanggal_mulai DESC";
$result_admin = mysqli_query($conn, $query_admin);

// Menghitung statistik tugas admin
$stats_query_admin = "SELECT 
    COUNT(*) as total_tugas,
    SUM(CASE WHEN t.status = 'Belum Dikerjakan' THEN 1 ELSE 0 END) as belum_dikerjakan,
    SUM(CASE WHEN t.status = 'Sedang Dikerjakan' THEN 1 ELSE 0 END) as sedang_dikerjakan,
    SUM(CASE WHEN t.status = 'Kirim' THEN 1 ELSE 0 END) as kirim,
    SUM(CASE WHEN t.status = 'Revisi' THEN 1 ELSE 0 END) as revisi,
    SUM(CASE WHEN t.status = 'Selesai' THEN 1 ELSE 0 END) as selesai
    " . $base_query_admin;
$stats_result_admin = mysqli_query($conn, $stats_query_admin);
$stats_admin = mysqli_fetch_assoc($stats_result_admin);

// Menghitung tugas yang melewati deadline (untuk tugas admin)
$overdue_query = "SELECT COUNT(*) as total_overdue
                  FROM tugas_media t
                  JOIN users u ON t.penanggung_jawab = u.username
                  WHERE t.deadline < CURDATE()
                  AND t.status != 'Selesai'
                  AND u.role = 'admin'";
$overdue_result = mysqli_query($conn, $overdue_query);
$overdue = mysqli_fetch_assoc($overdue_result)['total_overdue'];

// Menghitung tugas yang deadline-nya dalam 3 hari ke depan (untuk tugas admin)
$upcoming_query = "SELECT COUNT(*) as total_upcoming
                   FROM tugas_media t
                   JOIN users u ON t.penanggung_jawab = u.username
                   WHERE t.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
                   AND t.status != 'Selesai'
                   AND u.role = 'admin'";
$upcoming_result = mysqli_query($conn, $upcoming_query);
$upcoming = mysqli_fetch_assoc($upcoming_result)['total_upcoming'];

// Menghitung persentase penyelesaian tugas admin
$completion_percentage_admin = 0;
if ($stats_admin['total_tugas'] > 0) {
    $completion_percentage_admin = round(($stats_admin['selesai'] / $stats_admin['total_tugas']) * 100);
}

// Query untuk mendapatkan semua admin
$admin_query = "SELECT username FROM users WHERE role = 'admin'";
$admin_result = mysqli_query($conn, $admin_query);

// Array untuk menyimpan data admin dan jumlah tugas
$admin_data = array();

// Isi array dengan semua admin dan inisialisasi jumlah tugas dengan 0
while ($row = mysqli_fetch_assoc($admin_result)) {
    $admin_data[$row['username']] = 0;
}

// Query untuk mendapatkan jumlah tugas per admin
$tugas_query = "SELECT t.penanggung_jawab, COUNT(*) as jumlah_tugas
                FROM tugas_media t
                JOIN users u ON t.penanggung_jawab = u.username
                WHERE u.role = 'admin'
                GROUP BY t.penanggung_jawab";
$tugas_result = mysqli_query($conn, $tugas_query);

// Update jumlah tugas untuk admin yang memiliki tugas
while ($row = mysqli_fetch_assoc($tugas_result)) {
    if (isset($admin_data[$row['penanggung_jawab']])) {
        $admin_data[$row['penanggung_jawab']] = $row['jumlah_tugas'];
    }
}

// Statistik: Platform terbanyak (untuk tugas admin)
$platform_query = "SELECT t.platform, COUNT(*) as jumlah
                  FROM tugas_media t
                  JOIN users u ON t.penanggung_jawab = u.username
                  WHERE u.role = 'admin'
                  GROUP BY t.platform
                  ORDER BY jumlah DESC
                  LIMIT 3";
$platform_result = mysqli_query($conn, $platform_query);

// Statistik: Tugas yang sering overdue (untuk tugas admin)
$overdue_detail_query = "SELECT t.id, t.judul, t.penanggung_jawab, t.deadline, DATEDIFF(CURDATE(), t.deadline) as hari_terlambat
                        FROM tugas_media t
                        JOIN users u ON t.penanggung_jawab = u.username
                        WHERE t.deadline < CURDATE()
                        AND t.status != 'Selesai'
                        AND u.role = 'admin'
                        ORDER BY hari_terlambat DESC
                        LIMIT 5";
$overdue_detail_result = mysqli_query($conn, $overdue_detail_query);

// Mendapatkan daftar semua admin untuk filter
$admin_list_query = "SELECT DISTINCT username FROM users WHERE role = 'admin' ORDER BY username";
$admin_list_result = mysqli_query($conn, $admin_list_query);

// Query untuk tugas anggota yang sudah selesai dan diverifikasi oleh admin
$query_anggota = "SELECT t.* FROM tugas_media t
                  JOIN users u ON t.penanggung_jawab = u.username
                  WHERE u.role = 'anggota' AND t.status = 'Selesai' AND t.verified_by_admin = 1
                  ORDER BY t.tanggal_mulai DESC";
$result_anggota = mysqli_query($conn, $query_anggota);

// Ekspor ke CSV jika diminta
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set header untuk download file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_tugas_kabid_' . date('Y-m-d') . '.csv');
    
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
    mysqli_data_seek($result_admin, 0);
    
    // Tambahkan data
    $no = 1;
    while ($row = mysqli_fetch_assoc($result_admin)) {
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
        
        $catatan = !empty($row['catatan_kabid']) ? $row['catatan_kabid'] : (isset($row['catatan']) ? $row['catatan'] : '');
        
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
    <title>Dashboard Kepala Bidang</title>
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
            --primary-color: #8e44ad; /* Warna utama berbeda untuk Kabid (ungu) */
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
            background: linear-gradient(180deg, #6a0dad 0%, #8e44ad 100%); /* Warna sidebar untuk Kabid */
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
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            transition: all var(--transition-speed) ease;
        }
        
        #sidebar-wrapper .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        #sidebar-wrapper .list-group-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        #sidebar-wrapper .list-group-item i {
            margin-right: 1rem;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        
        #sidebar-wrapper.collapsed .list-group-item span {
            display: none;
        }
        
        #sidebar-wrapper.collapsed .list-group-item {
            text-align: center;
            padding: 0.8rem 0;
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
            z-index: 900;
        }
        
        #sidebarToggle {
            background: none;
            border: none;
            color: #6a0dad; /* Warna tombol toggle untuk Kabid */
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        #sidebarToggle:hover {
            color: #8e44ad; /* Warna hover tombol toggle untuk Kabid */
        }
        
        .user-info {
            margin-left: auto;
            display: flex;
            align-items: center;
        }
        
        .user-info .dropdown-toggle {
            background: none;
            border: none;
            color: #555;
            display: flex;
            align-items: center;
        }
        
        .user-info .dropdown-toggle:after {
            display: none;
        }
        
        .user-info .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #8e44ad; /* Warna avatar untuk Kabid */
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .user-info .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-right: 5px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
            padding: 0.5rem 0;
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
            font-size: 0.9rem;
        }
        
        .dropdown-item i {
            margin-right: 0.5rem;
            color: #8e44ad; /* Warna ikon dropdown untuk Kabid */
        }
        
        #content {
            padding: 1.5rem;
            flex-grow: 1;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.5rem;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease-in-out;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header:first-child {
            border-radius: 0.75rem 0.75rem 0 0;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .stat-card {
            border-left: 4px solid #8e44ad; /* Warna border untuk Kabid */
            border-radius: 0.75rem;
        }
        
        .stat-card .card-body {
            padding: 1rem;
        }
        
        .stat-card .stat-title {
            text-transform: uppercase;
            color: #888;
            font-size: 0.7rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0;
        }
        
        .stat-card .stat-icon {
            font-size: 2rem;
            color: #8e44ad; /* Warna ikon untuk Kabid */
            opacity: 0.3;
        }
        
        .progress {
            height: 0.5rem;
            border-radius: 1rem;
            margin-top: 0.5rem;
        }
        
        .progress-bar {
            background-color: #8e44ad; /* Warna progress bar untuk Kabid */
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            font-weight: 600;
            border-top: none;
            background-color: #f8f9fc;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .badge {
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            border-radius: 0.5rem;
        }
        
        .badge-primary {
            background-color: #8e44ad; /* Warna badge primary untuk Kabid */
        }
        
        .badge-success {
            background-color: #1cc88a;
        }
        
        .badge-warning {
            background-color: #f6c23e;
            color: #212529;
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
        
        .btn-primary {
            background-color: #8e44ad; /* Warna button primary untuk Kabid */
            border-color: #8e44ad; /* Warna border button primary untuk Kabid */
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: #6a0dad; /* Warna hover button primary untuk Kabid */
            border-color: #6a0dad; /* Warna hover border button primary untuk Kabid */
        }
        
        .btn-outline-primary {
            color: #8e44ad; /* Warna text button outline primary untuk Kabid */
            border-color: #8e44ad; /* Warna border button outline primary untuk Kabid */
        }
        
        .btn-outline-primary:hover, .btn-outline-primary:focus {
            background-color: #8e44ad; /* Warna hover button outline primary untuk Kabid */
            border-color: #8e44ad; /* Warna hover border button outline primary untuk Kabid */
        }
        
        .filter-form {
            background-color: white;
            border-radius: 0.75rem;
            padding: 1rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .filter-form label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #555;
        }
        
        .filter-form .form-control, .filter-form .form-select {
            border-radius: 0.5rem;
            font-size: 0.9rem;
            border: 1px solid #e3e6f0;
            padding: 0.5rem 1rem;
        }
        
        .filter-form .form-control:focus, .filter-form .form-select:focus {
            border-color: #8e44ad; /* Warna focus form untuk Kabid */
            box-shadow: 0 0 0 0.25rem rgba(142, 68, 173, 0.25); /* Warna shadow focus form untuk Kabid */
        }
        
        .btn-filter {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            border-radius: 0.5rem;
        }
        
        .status-badge {
            padding: 0.35em 0.65em;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-belum {
            background-color: #f8f9fc;
            color: #858796;
        }
        
        .status-sedang {
            background-color: #e0cffc;
            color: #6a0dad;
        }
        
        .status-kirim {
            background-color: #cfe2ff;
            color: #0d6efd;
        }
        
        .status-revisi {
            background-color: #fff3cd;
            color: #ffc107;
        }
        
        .status-selesai {
            background-color: #d1e7dd;
            color: #198754;
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
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            margin-right: 0.25rem;
        }
        
        .chart-container {
            position: relative;
            height: 15rem;
        }
        
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
                padding: 0.8rem 0;
            }
            
            #sidebar-wrapper .list-group-item i {
                margin-right: 0;
                font-size: 1.2rem;
            }
            
            #content-wrapper {
                margin-left: var(--sidebar-collapsed-width);
            }
            
            .stat-card .stat-icon {
                display: none;
            }
        }
        
        @media (max-width: 576px) {
            .user-info .user-name {
                display: none;
            }
            
            #topbar {
                padding: 0 1rem;
            }
            
            #content {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.25rem;
            }
        }
        
        /* Animasi */
        .animate__animated {
            animation-duration: 0.5s;
        }
        
        /* Tooltip */
        .tooltip {
            font-size: 0.8rem;
        }
        
        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #8e44ad; /* Warna scrollbar untuk Kabid */
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #6a0dad; /* Warna hover scrollbar untuk Kabid */
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Dashboard Kepala Bidang</div>
            <div class="list-group">
                <a href="#" class="list-group-item active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/tambah_tugas_kabid.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../auth/register_admin.php" class="list-group-item">
                    <i class="bi bi-person-plus"></i>
                    <span>Tambah Admin Staf</span>
                </a>
                <a href="../modules/kelola_admin.php" class="list-group-item">
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
                    <div class="dropdown">
                        <button class="dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?>
                            </div>
                            <span class="user-name"><?php echo $_SESSION["username"]; ?></span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Keluar</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div id="content">
                <h1 class="page-title animate__animated animate__fadeIn">Dashboard Kepala Bidang</h1>
                
                <!-- Filter Section -->
                <div class="filter-form animate__animated animate__fadeIn">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <label for="tahun" class="form-label">Tahun</label>
                            <select class="form-select" id="tahun" name="tahun">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($tahun == $y) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
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
                        <div class="col-md-2">
                            <label for="admin" class="form-label">Admin</label>
                            <select class="form-select" id="admin" name="admin">
                                <option value="">Semua Admin</option>
                                <?php 
                                mysqli_data_seek($admin_list_result, 0);
                                while ($admin_row = mysqli_fetch_assoc($admin_list_result)): 
                                ?>
                                    <option value="<?php echo $admin_row['username']; ?>" <?php echo ($admin == $admin_row['username']) ? 'selected' : ''; ?>>
                                        <?php echo $admin_row['username']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-filter me-2">
                                <i class="bi bi-filter"></i> Filter
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-filter me-2" onclick="window.location.href='kabid_dashboard.php'">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-success btn-filter">
                                <i class="bi bi-file-earmark-excel"></i> Export CSV
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Stats Cards -->
                <div class="row animate__animated animate__fadeIn">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="stat-title">TOTAL TUGAS ADMIN</div>
                                        <div class="stat-value"><?php echo $stats_admin['total_tugas']; ?></div>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clipboard-check stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="stat-title">TUGAS SELESAI</div>
                                        <div class="stat-value"><?php echo $stats_admin['selesai']; ?></div>
                                        <div class="progress">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $completion_percentage_admin; ?>%" aria-valuenow="<?php echo $completion_percentage_admin; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="small mt-2"><?php echo $completion_percentage_admin; ?>% Selesai</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-check-circle stat-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="stat-title">TUGAS TERLAMBAT</div>
                                        <div class="stat-value text-danger"><?php echo $overdue; ?></div>
                                        <div class="progress">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($overdue / $stats_admin['total_tugas'] * 100) : 0; ?>%" aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($overdue / $stats_admin['total_tugas'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="small mt-2 text-danger">Melewati Deadline</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-exclamation-triangle stat-icon text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card h-100">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <div class="stat-title">DEADLINE DEKAT</div>
                                        <div class="stat-value text-warning"><?php echo $upcoming; ?></div>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($upcoming / $stats_admin['total_tugas'] * 100) : 0; ?>%" aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($upcoming / $stats_admin['total_tugas'] * 100) : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="small mt-2 text-warning">3 Hari Mendatang</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-clock stat-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Tasks Section -->
                <div class="card shadow mb-4 animate__animated animate__fadeIn">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Tugas Admin</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="adminTasksTable">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Platform</th>
                                        <th>Status</th>
                                        <th>Deadline</th>
                                        <th>Link Drive</th>
                                        <th>Penanggung Jawab</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($result_admin) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result_admin)) { 
                                            // Menghitung sisa hari hingga deadline
                                            $deadline_date = isset($row['deadline']) ? new DateTime($row['deadline']) : new DateTime();
                                            $today = new DateTime();
                                            $interval = $today->diff($deadline_date);
                                            $days_remaining = $interval->days;
                                            
                                            // Menentukan class untuk status
                                            $status_class = '';
                                            switch($row['status']) {
                                                case 'Belum Dikerjakan':
                                                    $status_class = 'status-belum';
                                                    break;
                                                case 'Sedang Dikerjakan':
                                                    $status_class = 'status-sedang';
                                                    break;
                                                case 'Kirim':
                                                    $status_class = 'status-kirim';
                                                    break;
                                                case 'Revisi':
                                                    $status_class = 'status-revisi';
                                                    break;
                                                case 'Selesai':
                                                    $status_class = 'status-selesai';
                                                    break;
                                            }
                                            
                                            // Menentukan class untuk deadline
                                            $deadline_class = '';
                                            $deadline_text = isset($row['deadline']) ? date('d/m/Y', strtotime($row['deadline'])) : 'Tidak ada deadline';
                                            
                                            if (isset($row['deadline']) && $today > $deadline_date && $row['status'] != 'Selesai') {
                                                $deadline_class = 'deadline-warning';
                                                $deadline_text .= " (Terlewat)";
                                            } elseif (isset($row['deadline']) && $days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                $deadline_class = 'deadline-close';
                                                $deadline_text .= " ($days_remaining hari lagi)";
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                            <td><?php echo htmlspecialchars($row['platform']); ?></td>
                                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                            <td class="<?php echo $deadline_class; ?>"><?php echo $deadline_text; ?></td>
                                            <td>
                                                <?php if(!empty($row['link_drive'])): ?>
                                                    <a href="<?php echo htmlspecialchars($row['link_drive']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-link-45deg"></i> Lihat File
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum ada link</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                                            <td class="action-buttons">
                                                <a href="../modules/edit_tugas_kabid.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="../views/catatan_kabid.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-chat-left-text"></i>
                                                </a>
                                                <a href="../modules/hapus_tugas_kabid.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada tugas untuk admin</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Anggota Tasks Section -->
                <div class="card shadow mb-4 animate__animated animate__fadeIn">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Tugas Anggota (Selesai)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="anggotaTasksTable">
                                <thead>
                                    <tr>
                                        <th>Judul</th>
                                        <th>Platform</th>
                                        <th>Deskripsi</th>
                                        <th>Status</th>
                                        <th>Link Drive</th>
                                        <th>Penanggung Jawab</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($result_anggota) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result_anggota)) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                            <td><?php echo htmlspecialchars($row['platform']); ?></td>
                                            <td><?php echo htmlspecialchars($row['deskripsi']); ?></td>
                                            <td><span class="status-badge status-selesai"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                            <td>
                                                <?php if(!empty($row['link_drive'])): ?>
                                                    <a href="<?php echo htmlspecialchars($row['link_drive']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-link-45deg"></i> Lihat File
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum ada link</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                                            <td class="action-buttons">
                                                <a href="../modules/edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="../views/catatan_admin.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-chat-left-text"></i>
                                                </a>
                                                <a href="../modules/hapus_tugas.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada tugas selesai untuk anggota</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Stats Section -->
                <div class="row animate__animated animate__fadeIn">
                    <!-- Admin Distribution -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Distribusi Tugas Admin</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Admin</th>
                                                <th>Jumlah Tugas</th>
                                                <th>Persentase</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_tugas = array_sum($admin_data);
                                            foreach ($admin_data as $admin_name => $jumlah_tugas): 
                                                $percentage = ($total_tugas > 0) ? round(($jumlah_tugas / $total_tugas) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($admin_name); ?></td>
                                                <td><?php echo $jumlah_tugas; ?></td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $percentage; ?>%</div>
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
                    
                    <!-- Platform Stats -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold">Platform Terbanyak</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Platform</th>
                                                <th>Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($platform = mysqli_fetch_assoc($platform_result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($platform['platform']); ?></td>
                                                <td><?php echo $platform['jumlah']; ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Overdue Tasks Section -->
                <div class="card shadow mb-4 animate__animated animate__fadeIn">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Tugas Melewati Deadline</h6>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($overdue_detail_result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($overdue_detail_result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['judul']); ?></td>
                                            <td><?php echo htmlspecialchars($row['penanggung_jawab']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($row['deadline'])); ?></td>
                                            <td class="text-danger"><?php echo $row['hari_terlambat']; ?> hari</td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Tidak ada tugas yang melewati deadline</td>
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

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar-wrapper').classList.toggle('collapsed');
            document.getElementById('content-wrapper').classList.toggle('expanded');
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Set body as loaded
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('loaded');
        });
        
        // Responsive behavior for mobile
        function checkWidth() {
            if (window.innerWidth < 768) {
                document.getElementById('sidebar-wrapper').classList.add('collapsed');
                document.getElementById('content-wrapper').classList.add('expanded');
            } else {
                document.getElementById('sidebar-wrapper').classList.remove('collapsed');
                document.getElementById('content-wrapper').classList.remove('expanded');
            }
        }
        
        // Check width on page load
        checkWidth();
        
        // Check width on window resize
        window.addEventListener('resize', checkWidth);
    </script>
</body>
</html>
