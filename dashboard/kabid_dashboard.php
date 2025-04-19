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
$admin_data = [];

// Isi array dengan semua admin dan inisialisasi jumlah tugas dengan 0
while ($row = mysqli_fetch_assoc($admin_result)) {
    $admin_data[$row['username']] = 0;
}

// Query untuk mendapatkan jumlah tugas per admin
$tugas_query = "SELECT t.penanggung_jawab, COUNT(*) as jumlah_tugas
                FROM tugas_media t
                JOIN users u ON t.penanggung_jawab = u.username
                WHERE u.role = 'admin'";

// Tambahkan filter yang sama seperti pada query utama
if (!empty($bulan) && !empty($tahun)) {
    $tugas_query .= " AND MONTH(t.tanggal_mulai) = '$bulan' AND YEAR(t.tanggal_mulai) = '$tahun'";
} elseif (!empty($bulan)) {
    $tugas_query .= " AND MONTH(t.tanggal_mulai) = '$bulan'";
} elseif (!empty($tahun)) {
    $tugas_query .= " AND YEAR(t.tanggal_mulai) = '$tahun'";
}

if (!empty($status)) {
    $tugas_query .= " AND t.status = '$status'";
}

if (!empty($admin)) {
    $tugas_query .= " AND t.penanggung_jawab = '$admin'";
}

$tugas_query .= " GROUP BY t.penanggung_jawab";

$tugas_result = mysqli_query($conn, $tugas_query);

// Update jumlah tugas untuk admin yang memiliki tugas
while ($row = mysqli_fetch_assoc($tugas_result)) {
    if (isset($admin_data[$row['penanggung_jawab']])) {
        $admin_data[$row['penanggung_jawab']] = $row['jumlah_tugas'];
    }
}

// Query untuk mendapatkan semua anggota
$anggota_query = "SELECT username FROM users WHERE role = 'anggota'";
$anggota_result = mysqli_query($conn, $anggota_query);

// Array untuk menyimpan data anggota dan jumlah tugas
$anggota_data = [];

// Isi array dengan semua anggota dan inisialisasi jumlah tugas dengan 0
while ($row = mysqli_fetch_assoc($anggota_result)) {
    $anggota_data[$row['username']] = 0;
}

// Query untuk mendapatkan jumlah tugas per anggota
$tugas_anggota_query = "SELECT t.penanggung_jawab, COUNT(*) as jumlah_tugas
                FROM tugas_media t
                JOIN users u ON t.penanggung_jawab = u.username
                WHERE u.role = 'anggota'";
if (!empty($bulan) && !empty($tahun)) {
    $tugas_anggota_query .= " AND MONTH(t.tanggal_mulai) = '$bulan' AND YEAR(t.tanggal_mulai) = '$tahun'";
} elseif (!empty($bulan)) {
    $tugas_anggota_query .= " AND MONTH(t.tanggal_mulai) = '$bulan'";
} elseif (!empty($tahun)) {
    $tugas_anggota_query .= " AND YEAR(t.tanggal_mulai) = '$tahun'";
}

if (!empty($status)) {
    $tugas_anggota_query .= " AND t.status = '$status'";
}

$tugas_anggota_query .= " GROUP BY t.penanggung_jawab";
$tugas_anggota_result = mysqli_query($conn, $tugas_anggota_query);

// Update jumlah tugas untuk anggota yang memiliki tugas
while ($row = mysqli_fetch_assoc($tugas_anggota_result)) {
    if (isset($anggota_data[$row['penanggung_jawab']])) {
        $anggota_data[$row['penanggung_jawab']] = $row['jumlah_tugas'];
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

// Ekspor ke CSV jika diminta
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Set header untuk download file CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_tugas_kabid_' . date('Y-m-d') . '.csv');

    // Buat file pointer untuk output
    $output = fopen('php://output', 'w');

    // Tambahkan BOM untuk UTF-8
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
        'Catatan',
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
            $catatan,
        ]);
    }

    fclose($output);
    exit;
}

// Nama bulan dalam bahasa Indonesia
$nama_bulan = [
    '1' => 'Januari',
    '2' => 'Februari',
    '3' => 'Maret',
    '4' => 'April',
    '5' => 'Mei',
    '6' => 'Juni',
    '7' => 'Juli',
    '8' => 'Agustus',
    '9' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember',
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
            --primary-color: rgb(25, 77, 51);
            /* Warna utama hijau gelap */
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
            background: linear-gradient(180deg, #1a472a 0%, #2d5a40 100%);
            /* Warna sidebar hijau gelap */
            transition: all var(--transition-speed) ease;
            z-index: 1000;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            /* Mencegah horizontal scroll */
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
            color: rgba(255, 255, 255, 0.9);
            /* Lebih terang untuk keterbacaan */
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
            color: #1a472a;
            /* Warna hijau gelap */
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        #sidebarToggle:hover {
            color: #2d5a40;
            /* Warna hijau medium */
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
            border-left-color: #1a472a;
            /* Hijau gelap */
        }

        .stat-card.warning {
            border-left-color: #b35900;
            /* Oranye gelap */
        }

        .stat-card.danger {
            border-left-color: #e74a3b;
        }

        .stat-card.success {
            border-left-color: #1cc88a;
        }

        .stat-card.info {
            border-left-color: #cc4b2c;
            /* Coral gelap */
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
            background-color: #1a472a;
            /* Hijau gelap */
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
            background-color: #1a472a;
            /* Hijau gelap */
        }

        .badge-warning {
            background-color: #b35900;
            /* Oranye gelap */
            color: #fff;
        }

        .badge-danger {
            background-color: #e74a3b;
        }

        .badge-success {
            background-color: #1cc88a;
        }

        .badge-info {
            background-color: #cc4b2c;
            /* Coral gelap */
        }

        .badge-secondary {
            background-color: #858796;
        }

        .btn-primary {
            background-color: #1a472a;
            /* Hijau gelap */
            border-color: #1a472a;
        }

        .btn-primary:hover {
            background-color: #0f2c1a;
            /* Hijau sangat gelap */
            border-color: #0f2c1a;
        }

        .btn-outline-primary {
            color: #1a472a;
            /* Hijau gelap */
            border-color: #1a472a;
        }

        .btn-outline-primary:hover {
            background-color: #1a472a;
            border-color: #1a472a;
            color: white;
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

        .filter-form .form-control,
        .filter-form .form-select {
            border-radius: 5px;
            border: 1px solid #d1d3e2;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .filter-form .form-control:focus,
        .filter-form .form-select:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(26, 71, 42, 0.25);
            /* Hijau gelap dengan opacity */
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
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Status warna */
        .status-belum {
            background-color: #e74a3b;
            color: white;
        }

        .status-sedang {
            background-color: #b35900;
            /* Oranye gelap */
            color: white;
        }

        .status-kirim {
            background-color: #cc4b2c;
            /* Coral gelap */
            color: white;
        }

        .status-revisi {
            background-color: #d9510c;
            /* Oranye kemerahan gelap */
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
            color: #b35900;
            /* Oranye gelap */
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
            background: #1a472a;
            /* Hijau gelap */
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #0f2c1a;
            /* Hijau sangat gelap */
        }
    </style>


</head>

<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Dashboard Kepala Bidang</div>
            <div class="list-group">
                <a href="../dashboard/kabid_dashboard" class="list-group-item active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/daftar_tugas_kabid.php" class="list-group-item">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
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
                        <div class="card-body">
                            <form method="GET" action="" class="row g-3 align-items-end">
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
                                        for ($y = $current_year; $y >= $current_year - 5; $y--):
                                            ?>
                                            <option value="<?php echo $y; ?>" <?php echo ($tahun == $y) ? 'selected' : ''; ?>>
                                                <?php echo $y; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        <option value="Belum Dikerjakan" <?php echo ($status == 'Belum Dikerjakan') ? 'selected' : ''; ?>>
                                            Belum Dikerjakan</option>
                                        <option value="Sedang Dikerjakan" <?php echo ($status == 'Sedang Dikerjakan') ? 'selected' : ''; ?>>
                                            Sedang Dikerjakan</option>
                                        <option value="Kirim" <?php echo ($status == 'Kirim') ? 'selected' : ''; ?>>Kirim
                                        </option>
                                        <option value="Revisi" <?php echo ($status == 'Revisi') ? 'selected' : ''; ?>>
                                            Revisi</option>
                                        <option value="Selesai" <?php echo ($status == 'Selesai') ? 'selected' : ''; ?>>
                                            Selesai</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary flex-grow-1">
                                            <i class="bi bi-filter"></i> Filter
                                        </button>
                                        <a href="../dashboard/kabid_dashboard.php"
                                            class="btn btn-outline-secondary flex-grow-1">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Distribusi Tugas Charts - Row -->
                    <div class="row fade-in">
                        <!-- Admin Chart -->
                        <div class="col-lg-6">
                            <div class="card mb-4 shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="bi bi-bar-chart-fill me-2"></i>Distribusi Tugas Admin
                                    </h6>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                            id="adminChartOptionsDropdown" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="bi bi-gear-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="adminChartOptionsDropdown">
                                            <li><a class="dropdown-item" href="#" id="sortAdminByName">Urutkan
                                                    berdasarkan Nama</a></li>
                                            <li><a class="dropdown-item" href="#" id="sortAdminByValue">Urutkan
                                                    berdasarkan Jumlah Tugas</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-bar" style="height: 350px;">
                                        <canvas id="distribusiTugasAdminChart"></canvas>
                                    </div>
                                    <hr>
                                    <div class="mt-3 text-center">
                                        <small class="text-muted">Klik pada nama admin untuk melihat detail
                                            tugas</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Anggota Chart -->
                        <div class="col-lg-6">
                            <div class="card mb-4 shadow-sm">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-info">
                                        <i class="bi bi-bar-chart-fill me-2"></i>Distribusi Tugas Anggota
                                    </h6>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                            id="anggotaChartOptionsDropdown" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i class="bi bi-gear-fill"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end"
                                            aria-labelledby="anggotaChartOptionsDropdown">
                                            <li><a class="dropdown-item" href="#" id="sortAnggotaByName">Urutkan
                                                    berdasarkan Nama</a></li>
                                            <li><a class="dropdown-item" href="#" id="sortAnggotaByValue">Urutkan
                                                    berdasarkan Jumlah Tugas</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-bar" style="height: 350px;">
                                        <canvas id="distribusiTugasAnggotaChart"></canvas>
                                    </div>
                                    <hr>
                                    <div class="mt-3 text-center">
                                        <small class="text-muted">Klik pada nama anggota untuk melihat detail
                                            tugas</small>
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
                                                    if ($count >= 5) {
                                                        break;
                                                    }

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
                                                        <td><span
                                                                class="badge                                                                           <?php echo $status_class; ?>"><?php echo $row['status']; ?></span>
                                                        </td>
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
                                                            <td class="text-danger"><?php echo $row['hari_terlambat']; ?> hari
                                                            </td>
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

                    <!-- Platform Statistics -->
                    <div class="row fade-in">
                        <div class="col-lg-12">
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
                                                            <td><span
                                                                    class="badge bg-primary"><?php echo $row['jumlah']; ?></span>
                                                            </td>
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
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Menandai body sudah dimuat
            document.body.classList.add('loaded');

            // Toggle sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const contentWrapper = document.getElementById('content-wrapper');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    sidebarWrapper.classList.toggle('collapsed');
                    contentWrapper.classList.toggle('expanded');
                });
            }

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

            // Chart code - memastikan ini dijalankan setelah DOM dimuat
            var adminData = <?php echo json_encode($admin_data); ?>;
            var anggotaData = <?php echo json_encode($anggota_data); ?>;

            // Persiapkan data untuk chart admin
            var adminChartData = [];
            for (var admin in adminData) {
                if (adminData.hasOwnProperty(admin)) {
                    adminChartData.push({
                        name: admin,
                        value: adminData[admin]
                    });
                }
            }

            // Persiapkan data untuk chart anggota
            var anggotaChartData = [];
            for (var anggota in anggotaData) {
                if (anggotaData.hasOwnProperty(anggota)) {
                    anggotaChartData.push({
                        name: anggota,
                        value: anggotaData[anggota]
                    });
                }
            }

            // Fungsi untuk membuat chart admin
            function createAdminChart(sortBy = 'name') {
                // Sort data
                adminChartData.sort((a, b) => {
                    if (sortBy === 'name') {
                        return a.name.localeCompare(b.name);
                    } else {
                        return b.value - a.value;
                    }
                });

                // Prepare chart data
                var labels = adminChartData.map(item => item.name);
                var values = adminChartData.map(item => item.value);

                // Pastikan elemen canvas ada sebelum membuat chart
                var ctx = document.getElementById('distribusiTugasAdminChart');
                if (ctx) {
                    // Destroy existing chart if it exists
                    if (window.adminBarChart) {
                        window.adminBarChart.destroy();
                    }

                    ctx = ctx.getContext('2d');
                    window.adminBarChart = new Chart(ctx, {
                        type: 'horizontalBar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Jumlah Tugas',
                                    backgroundColor: '#4e73df',
                                    hoverBackgroundColor: '#2e59d9',
                                    borderColor: '#4e73df',
                                    borderWidth: 1,
                                    data: values,
                                }
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 25,
                                    top: 25,
                                    bottom: 0
                                }
                            },
                            scales: {
                                xAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        precision: 0,
                                        fontColor: '#858796',
                                        fontStyle: 'bold'
                                    },
                                    gridLines: {
                                        display: false,
                                        drawBorder: false
                                    },
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Jumlah Tugas',
                                        fontColor: '#858796',
                                        fontSize: 12
                                    }
                                }],
                                yAxes: [{
                                    gridLines: {
                                        color: "rgb(234, 236, 244)",
                                        zeroLineColor: "rgb(234, 236, 244)",
                                        drawBorder: false,
                                        borderDash: [2],
                                        zeroLineBorderDash: [2]
                                    },
                                    ticks: {
                                        fontColor: '#858796'
                                    }
                                }],
                            },
                            legend: {
                                display: false
                            },
                            tooltips: {
                                titleMarginBottom: 10,
                                titleFontColor: '#6e707e',
                                titleFontSize: 14,
                                backgroundColor: "rgb(255,255,255)",
                                bodyFontColor: "#858796",
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                caretPadding: 10,
                                callbacks: {
                                    label: function (tooltipItem, chart) {
                                        return 'Admin: ' + tooltipItem.xLabel + ' tugas';
                                    }
                                }
                            },
                            animation: {
                                duration: 1000,
                                easing: 'easeOutQuart'
                            },
                            onClick: function (e, items) {
                                if (items.length > 0) {
                                    var index = items[0]._index;
                                    var username = labels[index];
                                    // Redirect to filtered tasks page
                                    window.location.href = '../modules/daftar_tugas_kabid.php?penanggung_jawab=' + encodeURIComponent(username);
                                }
                            }
                        }
                    });
                }
            }

            // Fungsi untuk membuat chart anggota
            function createAnggotaChart(sortBy = 'name') {
                // Sort data
                anggotaChartData.sort((a, b) => {
                    if (sortBy === 'name') {
                        return a.name.localeCompare(b.name);
                    } else {
                        return b.value - a.value;
                    }
                });

                // Prepare chart data
                var labels = anggotaChartData.map(item => item.name);
                var values = anggotaChartData.map(item => item.value);

                // Pastikan elemen canvas ada sebelum membuat chart
                var ctx = document.getElementById('distribusiTugasAnggotaChart');
                if (ctx) {
                    // Destroy existing chart if it exists
                    if (window.anggotaBarChart) {
                        window.anggotaBarChart.destroy();
                    }

                    ctx = ctx.getContext('2d');
                    window.anggotaBarChart = new Chart(ctx, {
                        type: 'horizontalBar',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Jumlah Tugas',
                                    backgroundColor: '#36b9cc',
                                    hoverBackgroundColor: '#2c9faf',
                                    borderColor: '#36b9cc',
                                    borderWidth: 1,
                                    data: values,
                                }
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 25,
                                    top: 25,
                                    bottom: 0
                                }
                            },
                            scales: {
                                xAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        precision: 0,
                                        fontColor: '#858796',
                                        fontStyle: 'bold'
                                    },
                                    gridLines: {
                                        display: false,
                                        drawBorder: false
                                    },
                                    scaleLabel: {
                                        display: true,
                                        labelString: 'Jumlah Tugas',
                                        fontColor: '#858796',
                                        fontSize: 12
                                    }
                                }],
                                yAxes: [{
                                    gridLines: {
                                        color: "rgb(234, 236, 244)",
                                        zeroLineColor: "rgb(234, 236, 244)",
                                        drawBorder: false,
                                        borderDash: [2],
                                        zeroLineBorderDash: [2]
                                    },
                                    ticks: {
                                        fontColor: '#858796'
                                    }
                                }],
                            },
                            legend: {
                                display: false
                            },
                            tooltips: {
                                titleMarginBottom: 10,
                                titleFontColor: '#6e707e',
                                titleFontSize: 14,
                                backgroundColor: "rgb(255,255,255)",
                                bodyFontColor: "#858796",
                                borderColor: '#dddfeb',
                                borderWidth: 1,
                                xPadding: 15,
                                yPadding: 15,
                                displayColors: false,
                                caretPadding: 10,
                                callbacks: {
                                    label: function (tooltipItem, chart) {
                                        return 'Anggota: ' + tooltipItem.xLabel + ' tugas';
                                    }
                                }
                            },
                            animation: {
                                duration: 1000,
                                easing: 'easeOutQuart'
                            },
                            onClick: function (e, items) {
                                if (items.length > 0) {
                                    var index = items[0]._index;
                                    var username = labels[index];
                                    // Redirect to filtered tasks page
                                    window.location.href = '../modules/daftar_tugas_kabid.php?penanggung_jawab=' + encodeURIComponent(username);
                                }
                            }
                        }
                    });
                }
            }

            // Create initial charts
            createAdminChart();
            createAnggotaChart();

            // Add event listeners for sorting options - Admin
            document.getElementById('sortAdminByName').addEventListener('click', function (e) {
                e.preventDefault();
                createAdminChart('name');
            });

            document.getElementById('sortAdminByValue').addEventListener('click', function (e) {
                e.preventDefault();
                createAdminChart('value');
            });

            // Add event listeners for sorting options - Anggota
            document.getElementById('sortAnggotaByName').addEventListener('click', function (e) {
                e.preventDefault();
                createAnggotaChart('name');
            });

            document.getElementById('sortAnggotaByValue').addEventListener('click', function (e) {
                e.preventDefault();
                createAnggotaChart('value');
            });
        });

    </script>
</body>

</html>