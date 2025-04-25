<?php

include '../auth/koneksi.php';


if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "anggota") {
    header("Location: ../auth/login.php");
    exit();
}


$username = $_SESSION["username"];


// Pagination setup
$limit = 10; // Jumlah item per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;


// Filter bulan, tahun, dan status
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : '';
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
$total_pages = ceil($total_records / $limit);


// Query untuk mengambil data dengan pagination
$query = "SELECT * " . $base_query . " ORDER BY tanggal_mulai DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Anggota</title>
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
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    border: none;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    margin-bottom: 1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
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

/* Perbaikan untuk tabel */
.table-responsive {
    overflow-x: auto;
}

.table {
    margin-bottom: 0;
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.875rem; /* Reduced font size */
}

.table th {
    background-color: #f8f9fc;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    padding: 0.6rem 0.5rem; /* Reduced padding */
    vertical-align: middle;
    border-bottom: 2px solid #e3e6f0;
    white-space: nowrap;
}

.table td {
    padding: 0.8rem 0.8rem; /* Reduced padding */
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
}

/* Mengatur lebar kolom - more compact */
.table th.col-judul, .table td.col-judul {
    min-width: 150px;
    max-width: 180px;
}

.table th.col-platform, .table td.col-platform {
    min-width: 80px;
    width: 8%;
}

.table th.col-deskripsi, .table td.col-deskripsi {
    min-width: 180px;
    max-width: 250px;
}

.table th.col-status, .table td.col-status {
    min-width: 100px;
    width: 8%;
}

.table th.col-tanggal, .table td.col-tanggal {
    min-width: 90px;
    width: 8%;
}

.table th.col-deadline, .table td.col-deadline {
    min-width: 90px;
    width: 8%;
}

.table th.col-link, .table td.col-link {
    min-width: 100px;
    width: 10%;
}

.table th.col-catatan, .table td.col-catatan {
    min-width: 130px;
    max-width: 180px;
}

.table th.col-aksi, .table td.col-aksi {
    min-width: 80px;
    width: 6%;
}

/* Menangani teks panjang */
.text-truncate-custom {
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: block;
}

.text-wrap-custom {
    white-space: normal;
    word-break: break-word;
    max-height: 3.6rem; /* sekitar 2 baris */
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Tooltip untuk teks yang terpotong */
.tooltip-inner {
    max-width: 300px;
    padding: 0.5rem 1rem;
    text-align: left;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.table tbody tr {
    transition: background-color 0.2s ease;
    line-height: 1.2; /* Reduced line height */
}

.table tbody tr:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

.status-badge {
    padding: 0.35rem 0.5rem; /* Smaller padding */
    border-radius: 0.25rem;
    font-weight: 600;
    font-size: 0.7rem; /* Smaller font */
    text-transform: uppercase;
    display: inline-block;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.status-badge:hover {
    transform: scale(1.05);
}

.status-belum {
    background-color: #ffebee;
    color: #c62828;
}

.status-proses {
    background-color: #fff3e0;
    color: #ef6c00;
}

.status-kirim {
    background-color: #e3f2fd;
    color: #1565c0;
}

.status-selesai {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.status-revisi {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.btn {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    white-space: nowrap;
}

.btn:after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 0;
    border-radius: 100%;
    transform: scale(1, 1) translate(-50%);
    transform-origin: 50% 50%;
}

.btn:focus:not(:active)::after {
    animation: ripple 1s ease-out;
}

/* Make buttons in table more compact */
.table .btn-sm {
    padding: 0.25rem 0.4rem;
    font-size: 0.75rem;
}

@keyframes ripple {
    0% {
        transform: scale(0, 0);
        opacity: 0.5;
    }
    100% {
        transform: scale(20, 20);
        opacity: 0;
    }
}

.btn-update {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-update:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(46, 89, 217, 0.2);
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
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.table-row-enter {
    opacity: 0;
    transform: translateY(10px);
}

.table-row-enter-active {
    opacity: 1;
    transform: translateY(0);
    transition: opacity 300ms, transform 300ms;
}


/* Zebra striping yang lebih halus */
.table-hover tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Styling untuk filter form */
.filter-form {
        background-color: white;
    border-radius: 0.35rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    transition: all 0.3s ease;
}

.filter-form:hover {
    box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
}

.filter-form .form-select {
    border-radius: 0.25rem;
    border: 1px solid #d1d3e2;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.filter-form .form-select:focus {
    border-color: #bac8f3;
    box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
}

.filter-form .btn-filter {
    background-color: #4e73df;
    border-color: #4e73df;
    color: white;
    transition: all 0.3s ease;
}

.filter-form .btn-filter:hover {
    background-color: #2e59d9;
    border-color: #2653d4;
    transform: translateY(-2px);
}

.filter-form .btn-reset {
    background-color: #f8f9fc;
    border-color: #d1d3e2;
    color: #6e707e;
    transition: all 0.3s ease;
}

.filter-form .btn-reset:hover {
    background-color: #e3e6f0;
    border-color: #cbd3e9;
}

/* Pagination styling */
.pagination {
    margin-top: 1rem;
    margin-bottom: 0;
    justify-content: center;
}

.pagination .page-item .page-link {
    color: #4e73df;
    border: 1px solid #e3e6f0;
    margin: 0 3px;
    min-width: 36px;
    text-align: center;
    transition: all 0.2s ease;
}

.pagination .page-item .page-link:hover {
    background-color: #eaecf4;
    border-color: #e3e6f0;
    color: #2e59d9;
}

.pagination .page-item.active .page-link {
    background-color: #4e73df;
    border-color: #4e73df;
    color: white;
}

.pagination .page-item.disabled .page-link {
    color: #b7b9cc;
}

/* Styling untuk badge deadline */
.badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.bg-danger {
    background-color: #dc3545 !important;
    color: white !important;
}

.bg-warning {
    background-color: #ffc107 !important;
}

/* Reduce card body padding to make content more compact */
.card-body {
    padding: 1rem;
}

/* Responsive styles */
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
    
    /* Penyesuaian tabel untuk mobile */
    .table th, .table td {
        padding: 0.5rem 0.3rem;
    }
    
    .text-truncate-custom {
        max-width: 150px;
    }
    
    /* Penyesuaian filter form untuk mobile */
    .filter-form .row {
        flex-direction: column;
    }
    
    .filter-form .col-md-3,
    .filter-form .col-md-2 {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .filter-form .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    /* Improve table responsiveness */
    .table-responsive {
        border: 0;
    }
    
    .table {
        display: block;
        width: 100%;
    }
    
    /* Make table scrollable horizontally */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Adjust column widths for mobile */
    .table th.col-judul, .table td.col-judul,
    .table th.col-platform, .table td.col-platform,
    .table th.col-deskripsi, .table td.col-deskripsi,
    .table th.col-status, .table td.col-status,
    .table th.col-tanggal, .table td.col-tanggal,
    .table th.col-deadline, .table td.col-deadline,
    .table th.col-link, .table td.col-link,
    .table th.col-catatan, .table td.col-catatan,
    .table th.col-aksi, .table td.col-aksi {
        min-width: auto;
        white-space: normal;
    }
    
    /* Adjust text truncation for mobile */
    .text-truncate-custom {
        max-width: 100px;
    }
    
    .text-wrap-custom {
        max-height: 3.6rem; /* about 2 lines */
        -webkit-line-clamp: 2;
    }
    
    /* Adjust filter form for mobile */
    .filter-form .row {
        margin-right: 0;
        margin-left: 0;
    }
    
    .filter-form .col-md-3,
    .filter-form .col-md-2 {
        padding-right: 5px;
        padding-left: 5px;
    }
    
    /* Improve sidebar behavior on mobile */
    #sidebar-wrapper {
        width: 250px;
        position: fixed;
        top: 0;
        left: -250px;
        height: 100%;
        z-index: 1050;
        transition: all 0.3s;
        box-shadow: 3px 0 5px rgba(0, 0, 0, 0.1);
    }
    
    #sidebar-wrapper.show {
        left: 0;
    }
    
    /* Add overlay when sidebar is open */
    .sidebar-overlay {
        display: none;
        position: fixed;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 1040;
        opacity: 0;
        transition: all 0.5s ease-in-out;
    }
    
    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }
    
    /* Adjust buttons for mobile */
    .btn {
        padding: 0.375rem 0.5rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
    }
    
    /* Adjust status badges for mobile */
    .status-badge {
        padding: 0.3rem 0.5rem;
        font-size: 0.7rem;
    }
    
    /* Adjust pagination for mobile */
    .pagination .page-item .page-link {
        padding: 0.3rem 0.6rem;
        min-width: 30px;
    }
    
    /* Improve topbar for mobile */
    .topbar {
        padding: 0 0.5rem;
    }
    
    .user-info .username {
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        display: inline-block;
    }
    
    /* Adjust card padding for mobile */
    .card-body {
        padding: 0.75rem;
    }
    
    .card-header {
        padding: 0.75rem;
    }
    
    /* Improve main content padding */
    .main-content {
        padding: 1rem 0.5rem;
    }
    
    /* Make filter buttons stack better on mobile */
    .filter-form .btn {
        margin-bottom: 0.5rem;
        width: 100%;
    }
    
    /* Adjust heading sizes for mobile */
    .h3 {
        font-size: 1.5rem;
    }
}

/* For very small screens */
@media (max-width: 576px) {
    .text-truncate-custom {
        max-width: 80px;
    }
    
    .table th, .table td {
        padding: 0.4rem 0.3rem;
        font-size: 0.8rem;
    }
    
    .status-badge {
        padding: 0.2rem 0.4rem;
        font-size: 0.65rem;
    }
    
    /* Further reduce button size */
    .btn-sm {
        padding: 0.2rem 0.3rem;
        font-size: 0.7rem;
    }
    
    /* Adjust badge size */
    .badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.3rem;
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
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800 animate_animated animate_fadeInDown">Daftar Tugas Anggota</h1>
                </div>
                
                <!-- Filter Form -->
                <div class="filter-form animate_animated animate_fadeInUp">
                    <form method="GET" action="" class="mb-0">
                        <div class="row align-items-center">
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label for="bulan" class="form-label small text-muted mb-1">Bulan</label>
                                <select class="form-select" id="bulan" name="bulan">
                                    <option value="">Semua Bulan</option>
                                    <option value="1" <?php echo $bulan == '1' ? 'selected' : ''; ?>>Januari</option>
                                    <option value="2" <?php echo $bulan == '2' ? 'selected' : ''; ?>>Februari</option>
                                    <option value="3" <?php echo $bulan == '3' ? 'selected' : ''; ?>>Maret</option>
                                    <option value="4" <?php echo $bulan == '4' ? 'selected' : ''; ?>>April</option>
                                    <option value="5" <?php echo $bulan == '5' ? 'selected' : ''; ?>>Mei</option>
                                    <option value="6" <?php echo $bulan == '6' ? 'selected' : ''; ?>>Juni</option>
                                    <option value="7" <?php echo $bulan == '7' ? 'selected' : ''; ?>>Juli</option>
                                    <option value="8" <?php echo $bulan == '8' ? 'selected' : ''; ?>>Agustus</option>
                                    <option value="9" <?php echo $bulan == '9' ? 'selected' : ''; ?>>September</option>
                                    <option value="10" <?php echo $bulan == '10' ? 'selected' : ''; ?>>Oktober</option>
                                    <option value="11" <?php echo $bulan == '11' ? 'selected' : ''; ?>>November</option>
                                    <option value="12" <?php echo $bulan == '12' ? 'selected' : ''; ?>>Desember</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2 mb-md-0">
                                <label for="tahun" class="form-label small text-muted mb-1">Tahun</label>
                                <select class="form-select" id="tahun" name="tahun">
                                    <option value="">Semua Tahun</option>
                                    <?php
                                    $current_year = date('Y');
                                    for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                        echo '<option value="' . $y . '" ' . ($tahun == $y ? 'selected' : '') . '>' . $y . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label for="status" class="form-label small text-muted mb-1">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="Belum Dikerjakan" <?php echo $status == 'Belum Dikerjakan' ? 'selected' : ''; ?>>Belum Dikerjakan</option>
                                    <option value="Sedang Dikerjakan" <?php echo $status == 'Sedang Dikerjakan' ? 'selected' : ''; ?>>Sedang Dikerjakan</option>
                                    <option value="Kirim" <?php echo $status == 'Kirim' ? 'selected' : ''; ?>>Kirim</option>
                                    <option value="Revisi" <?php echo $status == 'Revisi' ? 'selected' : ''; ?>>Revisi</option>
                                    <option value="Selesai" <?php echo $status == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end mb-2 mb-md-0">
                                <button type="submit" class="btn btn-filter">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="../modules/daftar_tugas_anggota.php" class="btn btn-reset">
                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                </a>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-12 d-flex justify-content-end">
                                <a href="../modules/tambah.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg"></i> Tambah Tugas
                                </a> 
                                </div>
                        </div>
                    </form>
                </div>
                
                <!-- Tugas Media Card -->
                <div class="card shadow animate_animated animate_fadeInUp">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Daftar Tugas Media</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="col-judul">Judul</th>
                                        <th class="col-platform">Platform</th>
                                        <th class="col-deskripsi">Deskripsi</th>
                                        <th class="col-status">Status</th>
                                        <th class="col-tanggal">Tanggal Mulai</th>
                                        <th class="col-deadline">Deadline</th>
                                        <th class="col-link">Link</th>
                                        <th class="col-catatan">Catatan</th>
                                        <th class="col-aksi">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            // Menentukan kelas status untuk badge
                                            $status_class = '';
                                            $row_status_class = '';
                                            
                                            switch ($row['status']) {
                                                case 'Belum Dikerjakan':
                                                    $status_class = 'status-belum';
                                                    $row_status_class = 'status-row-belum';
                                                    break;
                                                case 'Sedang Dikerjakan':
                                                    $status_class = 'status-proses';
                                                    $row_status_class = 'status-row-proses';
                                                    break;
                                                case 'Kirim':
                                                    $status_class = 'status-kirim';
                                                    $row_status_class = 'status-row-kirim';
                                                    break;
                                                case 'Revisi':
                                                    $status_class = 'status-revisi';
                                                    $row_status_class = 'status-row-revisi';
                                                    break;
                                                case 'Selesai':
                                                    $status_class = 'status-selesai';
                                                    $row_status_class = 'status-row-selesai';
                                                    break;
                                                default:
                                                    $status_class = '';
                                                    $row_status_class = '';
                                            }
                                            
                                            // Menentukan apakah deadline sudah dekat atau terlewat
                                            $deadline_class = '';
                                            $deadline_date = new DateTime($row['deadline']);
                                            $today = new DateTime();
                                            $interval = $today->diff($deadline_date);
                                            $days_remaining = $interval->days;
                                            
                                            if ($today > $deadline_date) {
                                                $deadline_class = 'deadline-danger';
                                            } elseif ($days_remaining <= 3) {
                                                $deadline_class = 'deadline-warning';
                                            }
                                            
                                            // Menggabungkan kelas untuk baris
                                            $row_class = $row_status_class . ' ' . $deadline_class;
                                            
                                            echo "<tr class='" . $row_class . "'>";
                                            echo "<td class='col-judul'><span class='text-truncate-custom' data-bs-toggle='tooltip' title='" . htmlspecialchars($row['judul']) . "'>" . htmlspecialchars($row['judul']) . "</span></td>";
                                            echo "<td class='col-platform'>" . htmlspecialchars($row['platform']) . "</td>";
                                            echo "<td class='col-deskripsi'><span class='text-wrap-custom' data-bs-toggle='tooltip' title='" . htmlspecialchars($row['deskripsi']) . "'>" . htmlspecialchars($row['deskripsi']) . "</span></td>";
                                            echo "<td class='col-status'><span class='status-badge " . $status_class . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                                            echo "<td class='col-tanggal'>" . date('d/m/Y', strtotime($row['tanggal_mulai'])) . "</td>";
                                            echo "<td class='col-deadline'>" . date('d/m/Y', strtotime($row['deadline']));
                                            
                                            // Tambahkan pengingat sisa hari
                                            $deadline_date = new DateTime($row['deadline']);
                                            $today = new DateTime();
                                            $interval = $today->diff($deadline_date);
                                            $days_remaining = $interval->days;

                                            if ($today > $deadline_date) {
                                                echo " <span class='badge bg-danger'>Terlewat</span>";
                                            } elseif ($days_remaining <= 3) {
                                                echo " <span class='badge bg-warning text-dark'>$days_remaining hari lagi</span>";
                                            }

                                            echo "</td>";
                                            // Link dengan kondisi

                                            if (!empty($row['link_drive'])) {
                                                // Deteksi jenis link untuk menampilkan ikon yang sesuai
                                                $icon = 'bi-link-45deg'; // ikon default
                                                $link_text = 'Lihat';
                                                
                                                // Deteksi jenis link berdasarkan domain
                                                if (strpos($row['link_drive'], 'drive.google.com') !== false) {
                                                    $icon = 'bi-google';
                                                    $link_text = 'Drive';
                                                } elseif (strpos($row['link_drive'], 'dropbox.com') !== false) {
                                                    $icon = 'bi-dropbox';
                                                    $link_text = 'Dropbox';
                                                } elseif (strpos($row['link_drive'], 'onedrive.live.com') !== false || strpos($row['link_drive'], 'sharepoint.com') !== false) {
                                                    $icon = 'bi-microsoft';
                                                    $link_text = 'OneDrive';
                                                } elseif (strpos($row['link_drive'], 'youtube.com') !== false || strpos($row['link_drive'], 'youtu.be') !== false) {
                                                    $icon = 'bi-youtube';
                                                    $link_text = 'YouTube';
                                                } elseif (strpos($row['link_drive'], 'instagram.com') !== false) {
                                                    $icon = 'bi-instagram';
                                                    $link_text = 'Instagram';
                                                } elseif (strpos($row['link_drive'], 'facebook.com') !== false || strpos($row['link_drive'], 'fb.com') !== false) {
                                                    $icon = 'bi-facebook';
                                                    $link_text = 'Facebook';
                                                } elseif (strpos($row['link_drive'], 'twitter.com') !== false || strpos($row['link_drive'], 'x.com') !== false) {
                                                    $icon = 'bi-twitter';
                                                    $link_text = 'Twitter';
                                                }
                                                
                                                echo "<td class='col-link'><a href='" . htmlspecialchars($row['link_drive']) . "' target='_blank' class='btn btn-sm btn-outline-primary'><i class='bi " . $icon . "'></i> " . $link_text . "</a></td>";
                                            } else {
                                                echo "<td class='col-link'><span class='text-muted'>Belum ada</span></td>";
                                            }

                                            // Catatan dengan kondisi
                                            if (!empty($row['catatan_admin'])) {
                                                echo "<td class='col-catatan'><span class='text-truncate-custom' data-bs-toggle='tooltip' title='" . htmlspecialchars($row['catatan_admin']) . "'>" . htmlspecialchars($row['catatan_admin']) . "</span></td>";
                                            } elseif (!empty($row['catatan'])) {
                                                echo "<td class='col-catatan'><span class='text-truncate-custom' data-bs-toggle='tooltip' title='" . htmlspecialchars($row['catatan']) . "'>" . htmlspecialchars($row['catatan']) . "</span></td>";
                                            } else {
                                                echo "<td class='col-catatan'><span class='text-muted'>-</span></td>";
                                            }
                                            if ($row['status'] == 'Selesai') {
                                                // Jika status Selesai, tampilkan tombol yang dinonaktifkan
                                                echo "<td class='col-aksi'><button class='btn btn-sm btn-secondary' disabled><i class='bi bi-lock'></i></button></td>";
                                            } else {
                                                // Jika status bukan Selesai, tampilkan tombol edit seperti biasa
                                                echo "<td class='col-aksi'><a href='../controllers/update_status_anggota.php?id=" . $row['id'] . "' class='btn btn-sm btn-update text-white'><i class='bi bi-pencil-square'></i></a></td>";
                                            }
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='9' class='text-center py-4'>Tidak ada data tugas yang ditemukan</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>&status=<?php echo $status; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1&bulan=' . $bulan . '&tahun=' . $tahun . '&status=' . $status . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                }
                                
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . (($i == $page) ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&bulan=' . $bulan . '&tahun=' . $tahun . '&status=' . $status . '">' . $i . '</a></li>';
                                }
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&bulan=' . $bulan . '&tahun=' . $tahun . '&status=' . $status . '">' . $total_pages . '</a></li>';
                                }
                                ?>
                                
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>&status=<?php echo $status; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    html: true
                });
            });
            
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
        });
    </script>
</body>
</html>