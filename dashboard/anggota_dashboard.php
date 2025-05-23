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
    '12' => 'Desember'
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
    <link rel="stylesheet" href="../assets/css/anggota/main.css">
    <link rel="stylesheet" href="../assets/css/anggota/anggotadashboard.css">
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
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="d-none d-lg-inline text-gray-600 small me-2"><?= $username ?></span>
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profil.php"><i class="bi bi-person me-2"></i> Profil</a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../auth/logout.php"><i
                                        class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
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
                        <a href="?export=csv<?= !empty($bulan) ? '&bulan=' . $bulan : '' ?><?= !empty($tahun) ? '&tahun=' . $tahun : '' ?><?= !empty($status) ? '&status=' . $status : '' ?>"
                            class="btn btn-sm btn-primary shadow-sm">
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
                                            <option value="<?= $key ?>" <?= $bulan == $key ? 'selected' : '' ?>><?= $value ?>
                                            </option>
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
                                        <option value="Selesai" <?= $status == 'Selesai' ? 'selected' : '' ?>>Selesai
                                        </option>
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
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $stats['total_tugas'] ?>
                                            </div>
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
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['selesai'] ?>
                                            </div>
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
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $upcoming ?>
                                                    </div>
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
                                        <h4 class="small font-weight-bold">Persentase Penyelesaian <span
                                                class="float-end"><?= $completion_percentage ?>%</span></h4>
                                        <div class="progress mb-4">
                                            <div class="progress-bar bg-success" role="progressbar"
                                                style="width: <?= $completion_percentage ?>%"
                                                aria-valuenow="<?= $completion_percentage ?>" aria-valuemin="0"
                                                aria-valuemax="100"></div>
                                        </div>
                                        <div class="mt-3 small">
                                            <span class="me-2"><i class="bi bi-circle-fill text-success"></i> Selesai:
                                                <?= $stats['selesai'] ?></span>
                                            <span class="me-2"><i class="bi bi-circle-fill text-info"></i> Kirim:
                                                <?= $stats['kirim'] ?></span>
                                            <span class="me-2"><i class="bi bi-circle-fill text-secondary"></i> Revisi:
                                                <?= $stats['revisi'] ?></span>
                                            <span class="me-2"><i class="bi bi-circle-fill text-warning"></i> Sedang
                                                Dikerjakan: <?= $stats['sedang_dikerjakan'] ?></span>
                                            <span class="me-2"><i class="bi bi-circle-fill text-danger"></i> Belum
                                                Dikerjakan: <?= $stats['belum_dikerjakan'] ?></span>
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
                                            <p class="text-muted small">Rata-rata waktu yang dibutuhkan untuk
                                                menyelesaikan tugas</p>
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
                                                            <td class="text-danger"><?= $overdue_detail['hari_terlambat'] ?>
                                                                hari</td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                    <?php if (mysqli_num_rows($overdue_detail_result) == 0): ?>
                                                        <tr>
                                                            <td colspan="2" class="text-center">Tidak ada tugas terlambat
                                                            </td>
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
                                    <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                                        <thead>
                                            <tr class="small">
                                                <th>No</th>
                                                <th>Judul</th>
                                                <th>Platform</th>
                                                <th>Status</th>
                                                <th>Tanggal Mulai</th>
                                                <th>Deadline</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
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
                                                        <td class="fw-medium"><?= $row['judul'] ?></td>
                                                        <td><?= $row['platform'] ?></td>
                                                        <td><span
                                                                class="badge <?= $status_class ?>"><?= $row['status'] ?></span>
                                                        </td>
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
                                                    <td colspan="7" class="text-center">Tidak ada data tugas yang ditemukan
                                                    </td>
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

        <!-- Perbarui script JavaScript di bagian bawah file -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Toggle sidebar
                const sidebarToggle = document.getElementById('sidebarToggle');
                const sidebarWrapper = document.getElementById('sidebar-wrapper');
                const contentWrapper = document.getElementById('content-wrapper');

                // Fungsi untuk menutup sidebar dengan animasi
                function closeSidebar() {
                    if (sidebarWrapper.classList.contains('show')) {
                        // Fade out overlay dulu
                        const overlay = document.getElementById('sidebar-overlay');
                        if (overlay) {
                            overlay.style.opacity = '0';

                            // Tunggu animasi fade selesai baru hapus overlay
                            setTimeout(() => {
                                sidebarWrapper.classList.remove('show');
                                if (document.body.contains(overlay)) {
                                    document.body.removeChild(overlay);
                                }
                            }, 300);
                        } else {
                            sidebarWrapper.classList.remove('show');
                        }
                    }
                }

                if (sidebarToggle) {
                    sidebarToggle.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (window.innerWidth < 768) {
                            // Tambahkan overlay saat sidebar terbuka di mobile
                            if (!sidebarWrapper.classList.contains('show')) {
                                // Buka sidebar
                                sidebarWrapper.classList.add('show');

                                const overlay = document.createElement('div');
                                overlay.id = 'sidebar-overlay';
                                overlay.style.position = 'fixed';
                                overlay.style.top = '0';
                                overlay.style.left = '0';
                                overlay.style.width = '100%';
                                overlay.style.height = '100%';
                                overlay.style.backgroundColor = 'rgba(0,0,0,0.4)';
                                overlay.style.zIndex = '999';
                                overlay.style.opacity = '0';
                                overlay.style.transition = 'opacity 0.3s ease';
                                document.body.appendChild(overlay);

                                // Trigger reflow untuk memastikan transisi berjalan
                                overlay.offsetHeight;

                                // Fade in overlay
                                setTimeout(() => {
                                    overlay.style.opacity = '1';
                                }, 10);

                                overlay.addEventListener('click', function () {
                                    closeSidebar();
                                });
                            } else {
                                // Tutup sidebar
                                closeSidebar();
                            }
                        } else {
                            // Desktop behavior
                            sidebarWrapper.classList.toggle('collapsed');
                            contentWrapper.classList.toggle('expanded');
                        }
                    });
                }

                // Responsive sidebar behavior dengan debounce
                let resizeTimer;
                function checkScreenSize() {
                    if (window.innerWidth < 768) {
                        sidebarWrapper.classList.remove('collapsed');
                        contentWrapper.classList.remove('expanded');

                        // Tutup sidebar saat resize ke mobile
                        if (sidebarWrapper.classList.contains('show')) {
                            closeSidebar();
                        }
                    } else {
                        sidebarWrapper.classList.remove('show');
                        const overlay = document.getElementById('sidebar-overlay');
                        if (overlay) {
                            document.body.removeChild(overlay);
                        }
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
                            container.style.overflowX = 'auto';
                            container.style.WebkitOverflowScrolling = 'touch'; // Untuk iOS smooth scrolling
                        } else {
                            container.style.maxHeight = 'none';
                        }
                    });
                }

                // Check on load
                checkScreenSize();

                // Check on resize dengan debounce untuk performa lebih baik
                window.addEventListener('resize', function () {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(checkScreenSize, 100);
                });

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
                    link.addEventListener('click', function () {
                        if (window.innerWidth < 768 && sidebarWrapper.classList.contains('show')) {
                            closeSidebar();
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

                // Tambahkan class untuk animasi smooth pada sidebar
                sidebarWrapper.style.transition = 'width 0.3s ease-in-out, box-shadow 0.3s ease-in-out';
                contentWrapper.style.transition = 'margin-left 0.3s ease-in-out';
            });

        </script>

</body>

</html>