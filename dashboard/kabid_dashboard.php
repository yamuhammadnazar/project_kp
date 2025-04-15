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
            color: rgba(255, 255, 255, 0.8);
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
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
        border-left: 4px solid white;
    }
    
    #sidebar-wrapper .list-group-item.active {
        background-color: rgba(255, 255, 255, 0.2);
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
        color: #6a0dad;
        font-size: 1.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    #sidebarToggle:hover {
        color: #8e44ad;
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
    
    .stat-card {
        border-left: 4px solid;
        border-radius: 10px;
    }
    
    .stat-card.primary {
        border-left-color: #8e44ad;
    }
    
    .stat-card.warning {
        border-left-color: #f6c23e;
    }
    
    .stat-card.danger {
        border-left-color: #e74a3b;
    }
    
    .stat-card.success {
        border-left-color: #1cc88a;
    }
    
    .stat-card.info {
        border-left-color: #36b9cc;
    }
    
    .stat-card .stat-icon {
        font-size: 2rem;
        opacity: 0.3;
    }
    
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #5a5c69;
    }
    
    .stat-card .stat-label {
        font-size: 0.875rem;
        color: #858796;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    
    .progress {
        height: 0.5rem;
        border-radius: 0.25rem;
        margin-top: 0.5rem;
    }
    
    .progress-bar {
        background-color: #8e44ad;
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
        padding: 1rem;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fc;
    }
    
    .table tbody td {
        vertical-align: middle;
        padding: 0.75rem 1rem;
    }
    
    .badge {
        font-weight: 600;
        padding: 0.35em 0.65em;
        border-radius: 0.25rem;
    }
    
    .badge-primary {
        background-color: #8e44ad;
    }
    
    .badge-warning {
        background-color: #f6c23e;
        color: #fff;
    }
    
    .badge-danger {
        background-color: #e74a3b;
    }
    
    .badge-success {
        background-color: #1cc88a;
    }
    
    .badge-info {
        background-color: #36b9cc;
    }
    
    .badge-secondary {
        background-color: #858796;
    }
    
    .btn-primary {
        background-color: #8e44ad;
        border-color: #8e44ad;
    }
    
    .btn-primary:hover {
        background-color: #7d3c98;
        border-color: #7d3c98;
    }
    
    .btn-outline-primary {
        color: #8e44ad;
        border-color: #8e44ad;
    }
    
    .btn-outline-primary:hover {
        background-color: #8e44ad;
        border-color: #8e44ad;
    }
    
    .filter-form {
        background-color: white;
        padding: 1rem;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        margin-bottom: 1.5rem;
    }
    
    .filter-form label {
        font-weight: 600;
        color: #5a5c69;
    }
    
    .filter-form .form-control, .filter-form .form-select {
        border-radius: 5px;
        border: 1px solid #d1d3e2;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .filter-form .form-control:focus, .filter-form .form-select:focus {
        border-color: #bac8f3;
        box-shadow: 0 0 0 0.25rem rgba(142, 68, 173, 0.25);
    }
    
    .filter-form .btn {
        padding: 0.5rem 1rem;
        font-weight: 600;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
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
    
    @media (max-width: 576px) {
        .card-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .card-header .btn {
            margin-top: 0.5rem;
            align-self: flex-end;
        }
        
        .filter-form .row {
            flex-direction: column;
        }
        
        .filter-form .col {
            margin-bottom: 1rem;
        }
    }
    
    /* Animasi */
    .fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Status warna */
    .status-belum {
        background-color: #e74a3b;
        color: white;
    }
    
    .status-sedang {
        background-color: #f6c23e;
        color: white;
    }
    
    .status-kirim {
        background-color: #36b9cc;
        color: white;
    }
    
    .status-revisi {
        background-color: #fd7e14;
        color: white;
    }
    
    .status-selesai {
        background-color: #1cc88a;
        color: white;
    }
    
    /* Deadline styling */
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
    
    /* Scrollbar styling */
    ::-webkit-scrollbar {
        width: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #8e44ad;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #7d3c98;
    }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Dashboard Kepala Bidang</div>
            <div class="list-group">
                <a href="kabid_dashboard.php" class="list-group-item active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="kabid_tugas.php" class="list-group-item">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
                </a>
                <a href="kabid_verifikasi.php" class="list-group-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Verifikasi Tugas</span>
                </a>
                <a href="kabid_laporan.php" class="list-group-item">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Laporan</span>
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
                    <span class="user-name"><?php echo $username; ?></span>
                    <a href="../auth/logout.php" class="btn btn-sm btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800 fade-in">Dashboard Kepala Bidang</h1>
                    
                    <!-- Filter Form -->
                    <div class="card filter-form fade-in">
                        <form method="GET" action="" class="row g-3 align-items-end">
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
                                    <option value="">Semua Tahun</option>
                                    <?php 
                                    $current_year = date('Y');
                                    for ($y = $current_year; $y >= $current_year - 5; $y--): 
                                    ?>
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
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2">
                                <a href="kabid_dashboard.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Statistics Cards -->
                    <div class="row fade-in">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card primary h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="stat-label">Total Tugas</div>
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
                            <div class="card stat-card success h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="stat-label">Tugas Selesai</div>
                                            <div class="stat-value"><?php echo $stats_admin['selesai']; ?></div>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['selesai'] / $stats_admin['total_tugas'] * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['selesai'] / $stats_admin['total_tugas'] * 100) : 0; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-check-circle stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card warning h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="stat-label">Deadline Dekat</div>
                                            <div class="stat-value"><?php echo $upcoming; ?></div>
                                            <div class="progress">
                                                <div class="progress-bar bg-warning" role="progressbar" 
                                                     style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($upcoming / $stats_admin['total_tugas'] * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($upcoming / $stats_admin['total_tugas'] * 100) : 0; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-alarm stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card stat-card danger h-100">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="stat-label">Melewati Deadline</div>
                                            <div class="stat-value"><?php echo $overdue; ?></div>
                                            <div class="progress">
                                                <div class="progress-bar bg-danger" role="progressbar" 
                                                     style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($overdue / $stats_admin['total_tugas'] * 100) : 0; ?>%" 
                                                     aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($overdue / $stats_admin['total_tugas'] * 100) : 0; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="bi bi-exclamation-triangle stat-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Overview -->
                    <div class="row fade-in">
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Progres Tugas Admin</h6>
                                    <a href="?export=csv<?php echo (!empty($bulan) ? '&bulan='.$bulan : '') . (!empty($tahun) ? '&tahun='.$tahun : '') . (!empty($status) ? '&status='.$status : '') . (!empty($admin) ? '&admin='.$admin : ''); ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-download"></i> Export CSV
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Status</th>
                                                    <th>Jumlah</th>
                                                    <th>Persentase</th>
                                                    <th>Progres</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><span class="badge status-belum">Belum Dikerjakan</span></td>
                                                    <td><?php echo $stats_admin['belum_dikerjakan']; ?></td>
                                                    <td><?php echo ($stats_admin['total_tugas'] > 0) ? round(($stats_admin['belum_dikerjakan'] / $stats_admin['total_tugas'] * 100), 1) : 0; ?>%</td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-danger" role="progressbar" 
                                                                 style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['belum_dikerjakan'] / $stats_admin['total_tugas'] * 100) : 0; ?>%" 
                                                                 aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['belum_dikerjakan'] / $stats_admin['total_tugas'] * 100) : 0; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge status-sedang">Sedang Dikerjakan</span></td>
                                                    <td><?php echo $stats_admin['sedang_dikerjakan']; ?></td>
                                                    <td><?php echo ($stats_admin['total_tugas'] > 0) ? round(($stats_admin['sedang_dikerjakan'] / $stats_admin['total_tugas'] * 100), 1) : 0; ?>%</td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                                 style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['sedang_dikerjakan'] / $stats_admin['total_tugas'] * 100) : 0; ?>%" 
                                                                 aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['sedang_dikerjakan'] / $stats_admin['total_tugas'] * 100) : 0; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge status-kirim">Kirim</span></td>
                                                    <td><?php echo $stats_admin['kirim']; ?></td>
                                                    <td><?php echo ($stats_admin['total_tugas'] > 0) ? round(($stats_admin['kirim'] / $stats_admin['total_tugas'] * 100), 1) : 0; ?>%</td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-info" role="progressbar" 
                                                                 style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['kirim'] / $stats_admin['total_tugas'] * 100) : 0; ?>%" 
                                                                 aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['kirim'] / $stats_admin['total_tugas'] * 100) : 0; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge status-revisi">Revisi</span></td>
                                                    <td><?php echo $stats_admin['revisi']; ?></td>
                                                    <td><?php echo ($stats_admin['total_tugas'] > 0) ? round(($stats_admin['revisi'] / $stats_admin['total_tugas'] * 100), 1) : 0; ?>%</td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                                 style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['revisi'] / $stats_admin['total_tugas'] * 100) : 0; ?>%" 
                                                                 aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['revisi'] / $stats_admin['total_tugas'] * 100) : 0; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><span class="badge status-selesai">Selesai</span></td>
                                                    <td><?php echo $stats_admin['selesai']; ?></td>
                                                    <td><?php echo ($stats_admin['total_tugas'] > 0) ? round(($stats_admin['selesai'] / $stats_admin['total_tugas'] * 100), 1) : 0; ?>%</td>
                                                    <td>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                 style="width: <?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['selesai'] / $stats_admin['total_tugas'] * 100) : 0; ?>%" 
                                                                 aria-valuenow="<?php echo ($stats_admin['total_tugas'] > 0) ? ($stats_admin['selesai'] / $stats_admin['total_tugas'] * 100) : 0; ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Distribusi Tugas Admin</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Admin</th>
                                                    <th>Jumlah Tugas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($admin_data as $admin_name => $jumlah_tugas): ?>
                                                <tr>
                                                    <td><?php echo $admin_name; ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $jumlah_tugas; ?></span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Tasks and Overdue Tasks -->
                    <div class="row fade-in">
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Tugas Terbaru</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Judul</th>
                                                    <th>Status</th>
                                                    <th>Deadline</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                mysqli_data_seek($result_admin, 0);
                                                $count = 0;
                                                while ($row = mysqli_fetch_assoc($result_admin)) {
                                                    if ($count >= 5) break;
                                                    
                                                    $deadline_date = isset($row['deadline']) ? new DateTime($row['deadline']) : new DateTime();
                                                    $today = new DateTime();
                                                    $interval = $today->diff($deadline_date);
                                                    $days_remaining = $interval->days;
                                                    $deadline_class = 'deadline-normal';
                                                    
                                                    if (isset($row['deadline'])) {
                                                        if ($today > $deadline_date && $row['status'] != 'Selesai') {
                                                            $deadline_class = 'deadline-danger';
                                                        } elseif ($days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                            $deadline_class = 'deadline-warning';
                                                        }
                                                    }
                                                    
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
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['judul']; ?></td>
                                                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $row['status']; ?></span></td>
                                                    <td class="<?php echo $deadline_class; ?>">
                                                        <?php 
                                                        if (isset($row['deadline'])) {
                                                            echo date('d/m/Y', strtotime($row['deadline']));
                                                            if ($today > $deadline_date && $row['status'] != 'Selesai') {
                                                                echo " <span class='text-danger'>(Terlewat)</span>";
                                                            } elseif ($days_remaining <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                                echo " <span class='text-warning'>($days_remaining hari lagi)</span>";
                                                            }
                                                        } else {
                                                            echo "Tidak ada deadline";
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    $count++;
                                                }
                                                
                                                if ($count == 0) {
                                                    echo "<tr><td colspan='3' class='text-center'>Tidak ada tugas yang ditemukan</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Tugas Melewati Deadline</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Judul</th>
                                                    <th>Admin</th>
                                                    <th>Terlambat</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                if (mysqli_num_rows($overdue_detail_result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($overdue_detail_result)) {
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['judul']; ?></td>
                                                    <td><?php echo $row['penanggung_jawab']; ?></td>
                                                    <td class="text-danger"><?php echo $row['hari_terlambat']; ?> hari</td>
                                                </tr>
                                                <?php 
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='3' class='text-center'>Tidak ada tugas yang melewati deadline</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Platform Statistics and Verified Tasks -->
                    <div class="row fade-in">
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Platform Terbanyak</h6>
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
                                                <?php 
                                                if (mysqli_num_rows($platform_result) > 0) {
                                                    while ($row = mysqli_fetch_assoc($platform_result)) {
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['platform']; ?></td>
                                                    <td><span class="badge bg-primary"><?php echo $row['jumlah']; ?></span></td>
                                                </tr>
                                                <?php 
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='2' class='text-center'>Tidak ada data platform</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="m-0 font-weight-bold">Tugas Anggota Terverifikasi</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Judul</th>
                                                    <th>Anggota</th>
                                                    <th>Tanggal Selesai</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $count = 0;
                                                if (mysqli_num_rows($result_anggota) > 0) {
                                                    while ($row = mysqli_fetch_assoc($result_anggota)) {
                                                        if ($count >= 5) break;
                                                ?>
                                                <tr>
                                                    <td><?php echo $row['judul']; ?></td>
                                                    <td><?php echo $row['penanggung_jawab']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($row['tanggal_selesai'])); ?></td>
                                                </tr>
                                                <?php 
                                                        $count++;
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='3' class='text-center'>Tidak ada tugas anggota yang terverifikasi</td></tr>";
                                                }
                                                ?>
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
        });
    </script>
</body>
</html>
