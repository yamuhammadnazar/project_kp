<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "anggota") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];

// Filter bulan, tahun, dan status
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Base query
$base_query = "FROM tugas_media WHERE penanggung_jawab='$username'";

// Tambahkan filter jika ada
if (!empty($bulan) && !empty($tahun)) {
    $base_query .= " AND MONTH(tanggal_mulai) = '$bulan' AND YEAR(tanggal_mulai) = '$tahun'";
} elseif (!empty($bulan)) {
    $base_query .= " AND MONTH(tanggal_mulai) = '$bulan'";
} elseif (!empty($tahun)) {
    $base_query .= " AND YEAR(tanggal_mulai) = '$tahun'";
}

// Tambahkan filter status jika ada
if (!empty($status)) {
    $base_query .= " AND status = '$status'";
}

// Query untuk menghitung total data
$count_query = "SELECT COUNT(*) as total " . $base_query;
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];

// Query untuk mengambil data
$query = "SELECT * " . $base_query . " ORDER BY tanggal_mulai DESC";
$result = mysqli_query($conn, $query);

// Menghitung statistik
$stats_query = "SELECT 
    COUNT(*) as total_tugas,
    SUM(CASE WHEN status = 'Belum Dikerjakan' THEN 1 ELSE 0 END) as belum_dikerjakan,
    SUM(CASE WHEN status = 'Sedang Dikerjakan' THEN 1 ELSE 0 END) as sedang_dikerjakan,
    SUM(CASE WHEN status = 'Kirim' THEN 1 ELSE 0 END) as kirim,
    SUM(CASE WHEN status = 'Revisi' THEN 1 ELSE 0 END) as revisi,
    SUM(CASE WHEN status = 'Selesai' THEN 1 ELSE 0 END) as selesai
    " . $base_query;
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Menghitung tugas yang melewati deadline
$overdue_query = "SELECT COUNT(*) as total_overdue " . $base_query . " AND deadline < CURDATE() AND status != 'Selesai'";
$overdue_result = mysqli_query($conn, $overdue_query);
$overdue = mysqli_fetch_assoc($overdue_result)['total_overdue'];

// Menghitung tugas yang deadline-nya dalam 3 hari ke depan
$upcoming_query = "SELECT COUNT(*) as total_upcoming " . $base_query . " AND deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY) AND status != 'Selesai'";
$upcoming_result = mysqli_query($conn, $upcoming_query);
$upcoming = mysqli_fetch_assoc($upcoming_result)['total_upcoming'];

// Menghitung persentase penyelesaian tugas berdasarkan filter
$completion_percentage = 0;
if ($stats['total_tugas'] > 0) {
    $completion_percentage = round(($stats['selesai'] / $stats['total_tugas']) * 100);
}

// Statistik 1: Estimasi rata-rata waktu penyelesaian tugas (dalam hari)
$avg_completion_query = "SELECT AVG(DATEDIFF(deadline, tanggal_mulai)) as avg_days 
                         FROM tugas_media 
                         WHERE penanggung_jawab='$username' 
                         AND status='Selesai'";

// Tambahkan filter jika ada
if (!empty($bulan) && !empty($tahun)) {
    $avg_completion_query .= " AND MONTH(tanggal_mulai) = '$bulan' AND YEAR(tanggal_mulai) = '$tahun'";
} elseif (!empty($bulan)) {
    $avg_completion_query .= " AND MONTH(tanggal_mulai) = '$bulan'";
} elseif (!empty($tahun)) {
    $avg_completion_query .= " AND YEAR(tanggal_mulai) = '$tahun'";
}

$avg_completion_result = mysqli_query($conn, $avg_completion_query);
$avg_completion_days = mysqli_fetch_assoc($avg_completion_result)['avg_days'];
$avg_completion_days = $avg_completion_days ? round($avg_completion_days, 1) : 0;

// Statistik 2: Tugas terbanyak per platform
$platform_query = "SELECT platform, COUNT(*) as jumlah 
                   FROM tugas_media 
                   WHERE penanggung_jawab='$username'";

// Tambahkan filter jika ada
if (!empty($bulan) && !empty($tahun)) {
    $platform_query .= " AND MONTH(tanggal_mulai) = '$bulan' AND YEAR(tanggal_mulai) = '$tahun'";
} elseif (!empty($bulan)) {
    $platform_query .= " AND MONTH(tanggal_mulai) = '$bulan'";
} elseif (!empty($tahun)) {
    $platform_query .= " AND YEAR(tanggal_mulai) = '$tahun'";
}

if (!empty($status)) {
    $platform_query .= " AND status = '$status'";
}

$platform_query .= " GROUP BY platform ORDER BY jumlah DESC LIMIT 3";
$platform_result = mysqli_query($conn, $platform_query);

// Statistik 3: Tugas yang sering overdue
$overdue_detail_query = "SELECT judul, DATEDIFF(CURDATE(), deadline) as hari_terlambat 
                         FROM tugas_media 
                         WHERE penanggung_jawab='$username' 
                         AND deadline < CURDATE() 
                         AND status != 'Selesai'";

// Tambahkan filter jika ada
if (!empty($bulan) && !empty($tahun)) {
    $overdue_detail_query .= " AND MONTH(tanggal_mulai) = '$bulan' AND YEAR(tanggal_mulai) = '$tahun'";
} elseif (!empty($bulan)) {
    $overdue_detail_query .= " AND MONTH(tanggal_mulai) = '$bulan'";
} elseif (!empty($tahun)) {
    $overdue_detail_query .= " AND YEAR(tanggal_mulai) = '$tahun'";
}

$overdue_detail_query .= " ORDER BY hari_terlambat DESC LIMIT 5";
$overdue_detail_result = mysqli_query($conn, $overdue_detail_query);

// Ekspor ke CSV jika diminta
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set header untuk download file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_tugas_' . $username . '_' . date('Y-m-d') . '.csv');
    
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
        'Catatan'
    ]);
    
    // Reset pointer hasil query
    mysqli_data_seek($result, 0);
    
    // Tambahkan data
    $no = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $deadline_date = new DateTime($row['deadline']);
        $today = new DateTime();
        $interval = $today->diff($deadline_date);
        $days_remaining = $interval->days;
        $deadline_text = date('d/m/Y', strtotime($row['deadline']));
        
        if ($today > $deadline_date && $row['status'] != 'Selesai') {
            $deadline_text .= " (Terlewat)";
        } elseif ($days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
            $deadline_text .= " ($days_remaining hari lagi)";
        }
        
        $catatan = !empty($row['catatan_admin']) ? $row['catatan_admin'] : $row['catatan'];
        
        fputcsv($output, [
            $no++,
            $row['judul'],
            $row['platform'],
            $row['deskripsi'],
            $row['status'],
            date('d/m/Y', strtotime($row['tanggal_mulai'])),
            $deadline_text,
            $row['link_drive'],
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
    <title>Laporan Tugas Media</title>
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
        
        #content {
            padding: 1.5rem;
        }
        
        .topbar {
            height: var(--topbar-height);
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            display: flex;
            align-items: center;
            padding: 0 1rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .topbar .toggle-sidebar {
            background: none;
            border: none;
            color: #4e73df;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .topbar .toggle-sidebar:hover {
            color: #224abe;
        }
        
        .topbar .navbar-nav {
            display: flex;
            align-items: center;
            margin-left: auto;
        }
        
        .topbar .nav-item {
            position: relative;
        }
        
        .topbar .nav-link {
            color: #5a5c69;
            padding: 0.5rem 0.75rem;
            display: flex;
            align-items: center;
        }
        
        .topbar .nav-link:hover {
            color: #4e73df;
        }
        
        .topbar .nav-link .badge-counter {
            position: absolute;
            transform: scale(0.7);
            transform-origin: top right;
            right: 0.25rem;
            top: 0.25rem;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-header h6 {
            margin: 0;
            font-weight: 700;
            color: #4e73df;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        .table {
            color: #5a5c69;
        }
        
        .table th {
            font-weight: 700;
            background-color: #f8f9fc;
            border-top: none;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .badge {
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
        }
        
        .badge-primary {
            background-color: #4e73df;
        }
        
        .badge-success {
            background-color: #1cc88a;
        }
        
        .badge-warning {
            background-color: #f6c23e;
            color: #fff;
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
        
        .progress {
            height: 0.8rem;
            border-radius: 0.25rem;
            margin-top: 0.5rem;
            background-color: #eaecf4;
        }
        
        .progress-bar {
            background-color: #4e73df;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-success {
            background-color: #1cc88a;
            border-color: #1cc88a;
        }
        
        .btn-success:hover {
            background-color: #17a673;
            border-color: #169b6b;
        }
        
        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }
        
        .btn-info:hover {
            background-color: #2c9faf;
            border-color: #2a96a5;
        }
        
        .btn-warning {
            background-color: #f6c23e;
            border-color: #f6c23e;
            color: #fff;
        }
        
        .btn-warning:hover {
            background-color: #f4b619;
            border-color: #f4b30d;
            color: #fff;
        }
        
        .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }
        
        .btn-danger:hover {
            background-color: #e02d1b;
            border-color: #d52a1a;
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
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
        }
        
        .btn-icon-split .text {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
        }
        
        .stat-card {
            border-left: 0.25rem solid;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .stat-card-primary {
            border-left-color: #4e73df;
        }
        
        .stat-card-success {
            border-left-color: #1cc88a;
        }
        
        .stat-card-info {
            border-left-color: #36b9cc;
        }
        
        .stat-card-warning {
            border-left-color: #f6c23e;
        }
        
        .stat-card-danger {
            border-left-color: #e74a3b;
        }
        
        .stat-card .card-body {
            padding: 1rem;
        }
        
        .stat-card .text-xs {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #5a5c69;
        }
        
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #5a5c69;
            margin-bottom: 0;
        }
        
        .stat-card .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
        
        .dropdown-menu {
            font-size: 0.85rem;
            border: none;
            box-shadow: 0 0.5rem 1rem 0 rgba(0, 0, 0, 0.2);
        }
        
        .dropdown-item:active {
            background-color: #4e73df;
        }
        
        .filter-form .form-control {
            font-size: 0.85rem;
            border-radius: 0.35rem;
        }
        
        .filter-form .form-select {
            font-size: 0.85rem;
            border-radius: 0.35rem;
        }
        
        .filter-form .btn {
            font-size: 0.85rem;
            border-radius: 0.35rem;
        }
        
        @media (max-width: 768px) {
            #sidebar-wrapper {
                width: 0;
                overflow: hidden;
                position: fixed;
                height: 100%;
            }
            
            #sidebar-wrapper.show {
                width: var(--sidebar-width);
            }
            
            #content-wrapper {
                margin-left: 0;
            }
            
            .topbar .toggle-sidebar {
                display: block;
            }
        }
        
        /* Animasi untuk cards */
        .animate-card {
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Styling untuk status tugas */
        .status-belum {
            background-color: #e74a3b;
        }
        
        .status-sedang {
            background-color: #f6c23e;
        }
        
        .status-kirim {
            background-color: #36b9cc;
        }
        
        .status-revisi {
            background-color: #858796;
        }
        
        .status-selesai {
            background-color: #1cc88a;
        }
        
        /* Styling untuk deadline */
        .deadline-normal {
            color: #5a5c69;
        }
        
        .deadline-warning {
            color: #f6c23e;
            font-weight: bold;
        }
        
        .deadline-danger {
            color: #e74a3b;
            font-weight: bold;
        }
        
        /* Tooltip styling */
        .custom-tooltip {
            position: relative;
            display: inline-block;
            cursor: pointer;
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
        
        .custom-tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        
        .custom-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        /* Responsif untuk semua ukuran layar */
    @media (max-width: 1199px) {
        .stat-card .stat-value {
            font-size: 1.3rem;
        }
        
        .stat-card .stat-icon {
            font-size: 1.8rem;
        }
    }
    
    @media (max-width: 991px) {
        .card-header h6 {
            font-size: 0.9rem;
        }
        
        .table {
            font-size: 0.9rem;
        }
        
        .stat-card .text-xs {
            font-size: 0.65rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.2rem;
        }
    }
    
    @media (max-width: 768px) {
        #sidebar-wrapper {
            width: 0;
            overflow: hidden;
            position: fixed;
            height: 100%;
            z-index: 1050;
        }
        
        #sidebar-wrapper.show {
            width: var(--sidebar-width);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }
        
        #content-wrapper {
            margin-left: 0;
            width: 100%;
        }
        
        .topbar .toggle-sidebar {
            display: block;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .table-responsive {
            font-size: 0.85rem;
        }
        
        .stat-card {
            margin-bottom: 1rem;
        }
        
        .filter-form .col-md-3 {
            margin-bottom: 0.5rem;
        }
    }
    
    @media (max-width: 575px) {
        #content {
            padding: 1rem 0.5rem;
        }
        
        .card-header, .card-body {
            padding: 0.75rem;
        }
        
        .table {
            font-size: 0.8rem;
        }
        
        .stat-card .card-body {
            padding: 0.75rem;
        }
        
        .stat-card .text-xs {
            font-size: 0.6rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.1rem;
        }
        
        .stat-card .stat-icon {
            font-size: 1.5rem;
        }
        
        h1.h3 {
            font-size: 1.5rem;
        }
        
        .d-sm-flex {
            flex-direction: column;
        }
        
        .d-sm-flex .btn {
            margin-top: 0.5rem;
            width: 100%;
        }
        
        .filter-form .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
    
    /* Tambahan untuk tabel responsif */
    @media (max-width: 767px) {
        .table-responsive table {
            min-width: 650px;
        }
    }
    /* Tambahkan atau perbarui CSS untuk tombol ekspor */
    @media (max-width: 575px) {
        .d-sm-flex {
            display: flex !important;
            flex-direction: column;
            align-items: stretch !important;
        }
        
        .d-none.d-sm-inline-block {
            display: inline-block !important;
        }
        
        .d-sm-flex .btn-sm {
            margin-top: 0.5rem;
            width: 100%;
        }
    }
    </style>
</head>
<body>
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
        
        <!-- Page Content -->
        <div id="content-wrapper">
            <!-- Topbar -->
            <nav class="topbar">
                <button class="toggle-sidebar" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="navbar-nav ml-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-none d-lg-inline text-gray-600 small me-2"><?= $username ?></span>
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div id="content" class="animate-card">
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Laporan Tugas Anggota</h1>
                        <a href="?export=csv<?= !empty($bulan) ? '&bulan='.$bulan : '' ?><?= !empty($tahun) ? '&tahun='.$tahun : '' ?><?= !empty($status) ? '&status='.$status : '' ?>" class="btn btn-sm btn-primary shadow-sm">
                            <i class="bi bi-download text-white-50 me-1"></i> Ekspor CSV
                        </a>
                    </div>
                    
                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
                        </div>
                        <div class="card-body">
                            <form action="" method="get" class="row g-3 filter-form">
                                <div class="col-md-3">
                                    <label for="bulan" class="form-label">Bulan</label>
                                    <select class="form-select" id="bulan" name="bulan">
                                        <option value="">Semua Bulan</option>
                                        <?php foreach ($nama_bulan as $key => $value): ?>
                                            <option value="<?= $key ?>" <?= $bulan == $key ? 'selected' : '' ?>><?= $value ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="tahun" class="form-label">Tahun</label>
                                    <select class="form-select" id="tahun" name="tahun">
                                        <?php 
                                        $current_year = date('Y');
                                        for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                            echo "<option value='$y' " . ($tahun == $y ? 'selected' : '') . ">$y</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="Belum Dikerjakan" <?= $status == 'Belum Dikerjakan' ? 'selected' : '' ?>>Belum Dikerjakan</option>
                                        <option value="Sedang Dikerjakan" <?= $status == 'Sedang Dikerjakan' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                                        <option value="Kirim" <?= $status == 'Kirim' ? 'selected' : '' ?>>Kirim</option>
                                        <option value="Revisi" <?= $status == 'Revisi' ? 'selected' : '' ?>>Revisi</option>
                                        <option value="Selesai" <?= $status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-filter me-1"></i> Filter
                                    </button>
                                    <a href="../dashboard/anggota_dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-repeat me-1"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row">
                        <!-- Total Tugas -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2 stat-card stat-card-primary">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Tugas</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_tugas'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-clipboard-check fa-2x text-gray-300 stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tugas Selesai -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2 stat-card stat-card-success">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Tugas Selesai</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['selesai'] ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-check-circle fa-2x text-gray-300 stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tugas Melewati Deadline -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2 stat-card stat-card-danger">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Melewati Deadline</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $overdue ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-exclamation-triangle fa-2x text-gray-300 stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Deadline Mendekati -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2 stat-card stat-card-warning">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Deadline < 3 Hari</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $upcoming ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-clock-history fa-2x text-gray-300 stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Progres Penyelesaian Tugas</h6>
                                </div>
                                <div class="card-body">
                                    <h4 class="small font-weight-bold">Persentase Penyelesaian <span class="float-end"><?= $completion_percentage ?>%</span></h4>
                                    <div class="progress mb-4">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $completion_percentage ?>%" aria-valuenow="<?= $completion_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="mt-3 small">
                                        <span class="me-2"><i class="bi bi-circle-fill text-success"></i> Selesai: <?= $stats['selesai'] ?></span>
                                        <span class="me-2"><i class="bi bi-circle-fill text-info"></i> Kirim: <?= $stats['kirim'] ?></span>
                                        <span class="me-2"><i class="bi bi-circle-fill text-secondary"></i> Revisi: <?= $stats['revisi'] ?></span>
                                        <span class="me-2"><i class="bi bi-circle-fill text-warning"></i> Sedang Dikerjakan: <?= $stats['sedang_dikerjakan'] ?></span>
                                        <span class="me-2"><i class="bi bi-circle-fill text-danger"></i> Belum Dikerjakan: <?= $stats['belum_dikerjakan'] ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Statistics -->
                    <div class="row">
                        <!-- Rata-rata Waktu Penyelesaian -->
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Rata-rata Waktu Penyelesaian</h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center">
                                        <i class="bi bi-calendar-check text-primary" style="font-size: 3rem;"></i>
                                        <h4 class="mt-3"><?= $avg_completion_days ?> Hari</h4>
                                        <p class="text-muted small">Rata-rata waktu yang dibutuhkan untuk menyelesaikan tugas</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Platform Terbanyak -->
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Platform Terbanyak</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Platform</th>
                                                    <th>Jumlah</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($platform = mysqli_fetch_assoc($platform_result)): ?>
                                                <tr>
                                                    <td><?= $platform['platform'] ?></td>
                                                    <td><?= $platform['jumlah'] ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                                <?php if (mysqli_num_rows($platform_result) == 0): ?>
                                                <tr>
                                                    <td colspan="2" class="text-center">Tidak ada data</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tugas Terlambat -->
                        <div class="col-lg-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Tugas Terlambat</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Judul</th>
                                                    <th>Hari Terlambat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($overdue_detail = mysqli_fetch_assoc($overdue_detail_result)): ?>
                                                <tr>
                                                    <td><?= $overdue_detail['judul'] ?></td>
                                                    <td class="text-danger"><?= $overdue_detail['hari_terlambat'] ?> hari</td>
                                                </tr>
                                                <?php endwhile; ?>
                                                <?php if (mysqli_num_rows($overdue_detail_result) == 0): ?>
                                                <tr>
                                                    <td colspan="2" class="text-center">Tidak ada tugas terlambat</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Daftar Tugas -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Daftar Tugas Media</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Judul</th>
                                            <th>Platform</th>
                                            <th>Status</th>
                                            <th>Tanggal Mulai</th>
                                            <th>Deadline</th>
                                            <th>Catatan</th>
                                            </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if (mysqli_num_rows($result) > 0) {
                                            $no = 1;
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                // Format tanggal
                                                $tanggal_mulai = date('d/m/Y', strtotime($row['tanggal_mulai']));
                                                
                                                // Cek deadline
                                                $deadline_date = new DateTime($row['deadline']);
                                                $today = new DateTime();
                                                $interval = $today->diff($deadline_date);
                                                $days_remaining = $interval->days;
                                                $deadline_class = 'deadline-normal';
                                                $deadline_text = date('d/m/Y', strtotime($row['deadline']));
                                                
                                                if ($today > $deadline_date && $row['status'] != 'Selesai') {
                                                    $deadline_class = 'deadline-danger';
                                                    $deadline_text .= " (Terlewat)";
                                                } elseif ($days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                    $deadline_class = 'deadline-warning';
                                                    $deadline_text .= " ($days_remaining hari lagi)";
                                                }
                                                
                                                // Status badge class
                                                $status_class = '';
                                                switch ($row['status']) {
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
                                                
                                                $catatan = '';
                                                if (isset($row['catatan_admin']) && !empty($row['catatan_admin'])) {
                                                    $catatan = $row['catatan_admin'];
                                                } elseif (isset($row['catatan']) && !empty($row['catatan'])) {
                                                    $catatan = $row['catatan'];
                                                }
                                                $catatan_display = strlen($catatan) > 50 ? substr($catatan, 0, 50) . '...' : $catatan;
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <a href="detail_tugas.php?id=<?= $row['id'] ?>" class="text-primary font-weight-bold">
                                                    <?= $row['judul'] ?>
                                                </a>
                                            </td>
                                            <td><?= $row['platform'] ?></td>
                                            <td><span class="badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
                                            <td><?= $tanggal_mulai ?></td>
                                            <td class="<?= $deadline_class ?>"><?= $deadline_text ?></td>
                                            <td>
                                                <?php if (!empty($catatan)): ?>
                                                <div class="custom-tooltip">
                                                    <?= $catatan_display ?>
                                                    <span class="tooltip-text"><?= $catatan ?></span>
                                                </div>
                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php 
                                            }
                                        } else {
                                        ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada data tugas yang ditemukan</td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Media Anggota <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom scripts -->
<!-- Tambahkan atau perbarui bagian CSS berikut di dalam tag <style> -->
<style>
    /* Responsif untuk semua ukuran layar */
    @media (max-width: 1199px) {
        .stat-card .stat-value {
            font-size: 1.3rem;
        }
        
        .stat-card .stat-icon {
            font-size: 1.8rem;
        }
    }
    
    @media (max-width: 991px) {
        .card-header h6 {
            font-size: 0.9rem;
        }
        
        .table {
            font-size: 0.9rem;
        }
        
        .stat-card .text-xs {
            font-size: 0.65rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.2rem;
        }
    }
    
    @media (max-width: 768px) {
        #sidebar-wrapper {
            width: 0;
            overflow: hidden;
            position: fixed;
            height: 100%;
            z-index: 1050;
        }
        
        #sidebar-wrapper.show {
            width: var(--sidebar-width);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }
        
        #content-wrapper {
            margin-left: 0;
            width: 100%;
        }
        
        .topbar .toggle-sidebar {
            display: block;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .table-responsive {
            font-size: 0.85rem;
        }
        
        .stat-card {
            margin-bottom: 1rem;
        }
        
        .filter-form .col-md-3 {
            margin-bottom: 0.5rem;
        }
    }
    
    @media (max-width: 575px) {
        #content {
            padding: 1rem 0.5rem;
        }
        
        .card-header, .card-body {
            padding: 0.75rem;
        }
        
        .table {
            font-size: 0.8rem;
        }
        
        .stat-card .card-body {
            padding: 0.75rem;
        }
        
        .stat-card .text-xs {
            font-size: 0.6rem;
        }
        
        .stat-card .stat-value {
            font-size: 1.1rem;
        }
        
        .stat-card .stat-icon {
            font-size: 1.5rem;
        }
        
        h1.h3 {
            font-size: 1.5rem;
        }
        
        .d-sm-flex {
            flex-direction: column;
        }
        
        .d-sm-flex .btn {
            margin-top: 0.5rem;
            width: 100%;
        }
        
        .filter-form .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
    
    /* Tambahan untuk tabel responsif */
    @media (max-width: 767px) {
        .table-responsive table {
            min-width: 650px;
        }
    }
</style>

<!-- Perbarui script JavaScript di bagian bawah file -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarWrapper = document.getElementById('sidebar-wrapper');
        const contentWrapper = document.getElementById('content-wrapper');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                if (window.innerWidth < 768) {
                    sidebarWrapper.classList.toggle('show');
                    
                    // Tambahkan overlay saat sidebar terbuka di mobile
                    if (sidebarWrapper.classList.contains('show')) {
                        const overlay = document.createElement('div');
                        overlay.id = 'sidebar-overlay';
                        overlay.style.position = 'fixed';
                        overlay.style.top = '0';
                        overlay.style.left = '0';
                        overlay.style.width = '100%';
                        overlay.style.height = '100%';
                        overlay.style.backgroundColor = 'rgba(0,0,0,0.4)';
                        overlay.style.zIndex = '1040';
                        document.body.appendChild(overlay);
                        
                        overlay.addEventListener('click', function() {
                            sidebarWrapper.classList.remove('show');
                            document.body.removeChild(overlay);
                        });
                    } else {
                        const overlay = document.getElementById('sidebar-overlay');
                        if (overlay) {
                            document.body.removeChild(overlay);
                        }
                    }
                } else {
                    sidebarWrapper.classList.toggle('collapsed');
                    contentWrapper.classList.toggle('expanded');
                }
            });
        }
        
        // Responsive sidebar behavior
        function checkScreenSize() {
            if (window.innerWidth < 768) {
                sidebarWrapper.classList.remove('collapsed');
                contentWrapper.classList.remove('expanded');
                
                // Tutup sidebar saat resize ke mobile
                sidebarWrapper.classList.remove('show');
                const overlay = document.getElementById('sidebar-overlay');
                if (overlay) {
                    document.body.removeChild(overlay);
                }
            } else {
                sidebarWrapper.classList.remove('show');
            }
            
            // Sesuaikan tinggi tabel
            adjustTableHeight();
        }
        
        // Fungsi untuk menyesuaikan tinggi tabel
        function adjustTableHeight() {
            const tableContainers = document.querySelectorAll('.table-responsive');
            tableContainers.forEach(container => {
                if (window.innerWidth < 768) {
                    container.style.maxHeight = '400px';
                    container.style.overflowY = 'auto';
                } else {
                    container.style.maxHeight = 'none';
                }
            });
        }
        
        // Check on load
        checkScreenSize();
        
        // Check on resize
        window.addEventListener('resize', checkScreenSize);
        
        // Add fade-in effect to body
        document.body.classList.add('loaded');
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Tambahkan event listener untuk menutup sidebar saat klik link di mobile
        const sidebarLinks = document.querySelectorAll('#sidebar-wrapper .list-group-item');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768 && sidebarWrapper.classList.contains('show')) {
                    sidebarWrapper.classList.remove('show');
                    const overlay = document.getElementById('sidebar-overlay');
                    if (overlay) {
                        document.body.removeChild(overlay);
                    }
                }
            });
        });
        
        // Perbaikan untuk tampilan tabel di mobile
        const tables = document.querySelectorAll('.table');
        if (window.innerWidth < 576) {
            tables.forEach(table => {
                table.classList.add('table-sm');
            });
        }
    });
</script>

</body>
</html>
