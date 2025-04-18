<?php
include '../auth/koneksi.php';

// Cek session dan role
if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "kabid") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];
$error_message = "";
$success_message = "";

// Cek pesan sukses dari session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Cek pesan error dari session
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Pagination untuk tugas anggota
$items_per_page = 10;
$anggota_page = isset($_GET['anggota_page']) ? (int)$_GET['anggota_page'] : 1;
$anggota_offset = ($anggota_page - 1) * $items_per_page;

// Pagination untuk tugas admin
$admin_page = isset($_GET['admin_page']) ? (int)$_GET['admin_page'] : 1;
$admin_offset = ($admin_page - 1) * $items_per_page;

// Filter bulan, tahun, dan status jika ada
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : '';
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : '';
$admin = isset($_GET['admin']) ? $_GET['admin'] : '';

// Nama bulan dalam bahasa Indonesia
$nama_bulan = [
    '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
    '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
    '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Filter untuk tugas anggota
$filter_condition = "";
if (isset($_GET['filter']) && $_GET['filter'] == 'belum_dilihat') {
    $filter_condition .= " AND (t.dilihat_kabid = 0 OR t.dilihat_kabid IS NULL)";
}

// Tambahkan filter bulan dan tahun untuk tugas anggota
if (!empty($bulan)) {
    $filter_condition .= " AND MONTH(t.tanggal_mulai) = '$bulan'";
}
if (!empty($tahun)) {
    $filter_condition .= " AND YEAR(t.tanggal_mulai) = '$tahun'";
}

// Filter untuk tugas admin
$admin_filter_condition = "";
if (!empty($admin)) {
    $admin_filter_condition .= " AND t.penanggung_jawab = '$admin'";
}
if (!empty($status)) {
    $admin_filter_condition .= " AND t.status = '$status'";
}

// Query untuk menghitung total tugas anggota yang selesai
$count_anggota_query = "SELECT COUNT(*) as total 
                        FROM tugas_media t 
                        JOIN users u ON t.penanggung_jawab = u.username 
                        WHERE u.role = 'anggota' 
                        AND t.status = 'Selesai'" . $filter_condition;
$count_anggota_result = mysqli_query($conn, $count_anggota_query);
$count_anggota_row = mysqli_fetch_assoc($count_anggota_result);
$anggota_total_pages = ceil($count_anggota_row['total'] / $items_per_page);

// Query untuk tugas anggota yang berstatus selesai dengan pagination
$anggota_selesai_query = "SELECT t.*, u.username 
                         FROM tugas_media t 
                         JOIN users u ON t.penanggung_jawab = u.username 
                         WHERE u.role = 'anggota' 
                         AND t.status = 'Selesai'" . $filter_condition . " 
                         ORDER BY COALESCE(t.dilihat_kabid, 0) ASC, t.tanggal_mulai DESC 
                         LIMIT $anggota_offset, $items_per_page";
$anggota_selesai_result = mysqli_query($conn, $anggota_selesai_query);

// Query untuk menghitung total tugas yang diberikan kabid kepada admin
$count_admin_query = "SELECT COUNT(*) as total 
                     FROM tugas_media t 
                     JOIN users u ON t.penanggung_jawab = u.username 
                     WHERE u.role = 'admin' 
                     AND t.pemberi_tugas = 'kabid'" . $admin_filter_condition;
$count_admin_result = mysqli_query($conn, $count_admin_query);
$count_admin_row = mysqli_fetch_assoc($count_admin_result);
$admin_total_pages = ceil($count_admin_row['total'] / $items_per_page);

// Query untuk tugas yang diberikan kabid kepada admin dengan pagination
$admin_tugas_query = "SELECT t.*, u.username 
                     FROM tugas_media t 
                     JOIN users u ON t.penanggung_jawab = u.username 
                     WHERE u.role = 'admin' 
                     AND t.pemberi_tugas = 'kabid'" . $admin_filter_condition . " 
                     ORDER BY t.tanggal_mulai DESC 
                     LIMIT $admin_offset, $items_per_page";
$admin_tugas_result = mysqli_query($conn, $admin_tugas_query);

// Hitung jumlah tugas yang belum dilihat
$belum_dilihat_query = "SELECT COUNT(*) as total 
                        FROM tugas_media t 
                        JOIN users u ON t.penanggung_jawab = u.username 
                        WHERE u.role = 'anggota' 
                        AND t.status = 'Selesai' 
                        AND (t.dilihat_kabid = 0 OR t.dilihat_kabid IS NULL)";
$belum_dilihat_result = mysqli_query($conn, $belum_dilihat_query);
$belum_dilihat_row = mysqli_fetch_assoc($belum_dilihat_result);
$jumlah_belum_dilihat = $belum_dilihat_row['total'];
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
    
    /* Tab styling */
    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 600;
        padding: 0.75rem 1.25rem;
        border-radius: 0;
        border: none;
        border-bottom: 3px solid transparent;
    }
    
    .nav-tabs .nav-link.active {
        color: #1a472a;
        background-color: transparent;
        border-bottom: 3px solid #1a472a;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        border-bottom: 3px solid #ddd;
    }
    
    /* Card styling */
    .card {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        margin-bottom: 1.5rem;
    }
    
    .card-header {
        background-color: #f8f9fc;
        border-bottom: 1px solid #e3e6f0;
        padding: 1rem 1.25rem;
    }
    
    .btn-add-task {
        background-color: #1a472a;
        color: white;
        border: none;
        padding: 0.375rem 0.75rem;
        font-size: 0.9rem;
        border-radius: 0.25rem;
        transition: all 0.2s ease;
    }
    
    .btn-add-task:hover {
        background-color: #0f2c1a;
        color: white;
    }
    
    .filter-btn {
        position: relative;
    }
    
    .notification-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        font-size: 0.7rem;
    }
    
    /* Responsiveness */
    @media (max-width: 768px) {
        #sidebar-wrapper {
            width: var(--sidebar-collapsed-width);
        }
                
         #sidebar-wrapper.collapsed .sidebar-heading {
            font-size: 0;
            padding: 1.2rem 0;
        }
                
        #sidebar-wrapper.collapsed .sidebar-heading::before {
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
    
    /* Scrollbar styling */
    ::-webkit-scrollbar {
        width: 8px;
    }
        
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
        
    ::-webkit-scrollbar-thumb {
        background: #1a472a; /* Hijau gelap */
        border-radius: 4px;
    }
        
    ::-webkit-scrollbar-thumb:hover {
        background: #0f2c1a; /* Hijau sangat gelap */
    }
        /* Add this to your existing style section */
    .btn-group .btn {
        margin-right: 3px;
    }

    .btn-group .btn:last-child {
        margin-right: 0;
    }

    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Dashboard Kepala Bidang</div>
            <div class="list-group">
                <a href="../dashboard/kabid_dashboard.php" class="list-group-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/daftar_tugas_kabid.php" class="list-group-item active">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
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
                    <span class="user-name"><?php echo $username; ?></span>
                    <a href="../auth/logout.php" class="btn btn-sm btn-logout">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </nav>
                
            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Daftar Tugas</h1>
                    
                    <!-- Alert untuk pesan sukses atau error -->
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> <?= $success_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Navigasi Tab -->
                    <ul class="nav nav-tabs mb-4" id="taskTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="anggota-tab" data-bs-toggle="tab" data-bs-target="#anggota-tasks" type="button" role="tab" aria-controls="anggota-tasks" aria-selected="true">
                                <i class="bi bi-check-circle me-1"></i> Tugas Anggota Selesai
                                <?php if ($jumlah_belum_dilihat > 0): ?>
                                <span class="badge bg-danger ms-2"><?= $jumlah_belum_dilihat ?></span>
                                <?php endif; ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-tasks" type="button" role="tab" aria-controls="admin-tasks" aria-selected="false">
                                <i class="bi bi-list-task me-1"></i> Tugas untuk Admin
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="taskTabsContent">
                        <!-- Tab Tugas Anggota -->
                        <div class="tab-pane fade show active" id="anggota-tasks" role="tabpanel" aria-labelledby="anggota-tab">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="m-0 font-weight-bold">Daftar Tugas Anggota Selesai</h5>
                                <div class="d-flex">
                                    <!-- Form filter -->
                                    <form class="d-flex me-2" method="GET">
                                        <input type="hidden" name="active_tab" value="anggota">
                                        <select name="bulan" class="form-select form-select-sm me-2">
                                            <option value="">Semua Bulan</option>
                                            <?php foreach($nama_bulan as $key => $bulan_name): ?>
                                                <option value="<?= $key ?>" <?= $bulan == $key ? 'selected' : '' ?>><?= $bulan_name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="tahun" class="form-select form-select-sm me-2">
                                            <?php for($y = date('Y'); $y >= date('Y')-2; $y--): ?>
                                                <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-funnel"></i> Filter
                                        </button>
                                    </form>
                                    
                                    <!-- Tombol yang sudah ada -->
                                    <?php if ($jumlah_belum_dilihat > 0): ?>
                                    <a href="?filter=belum_dilihat" class="btn btn-outline-warning filter-btn me-2">
                                        <i class="bi bi-eye-slash"></i> Belum Dilihat
                                        <span class="badge bg-danger notification-badge"><?= $jumlah_belum_dilihat ?></span>
                                    </a>
                                    <?php endif; ?>
                                    <a href="?filter=" class="btn btn-outline-primary">
                                        <i class="bi bi-list-check"></i> Semua Tugas
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
                                                    <th>Link</th>
                                                    <th>Penanggung Jawab</th>
                                                    <th>Status Dilihat</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                
                                                <?php
                                                if (mysqli_num_rows($anggota_selesai_result) > 0) {
                                                    $no = ($anggota_page - 1) * $items_per_page + 1;
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
                                                    <!-- Kolom status dilihat -->
                                                    <td>
                                                        <?php if (isset($row['dilihat_kabid']) && $row['dilihat_kabid'] == 1): ?>
                                                            <span class="badge bg-success"><i class="bi bi-eye-fill"></i> Sudah Dilihat</span>
                                                            <?php if (!empty($row['waktu_dilihat_kabid'])): ?>
                                                                <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($row['waktu_dilihat_kabid'])) ?></small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning"><i class="bi bi-eye-slash-fill"></i> Belum Dilihat</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="../modules/konfirmasi_lihat_tugas_kabid.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i> Lihat Detail
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="7" class="text-center py-4"><i class="bi bi-inbox me-2"></i>Tidak ada tugas anggota yang selesai</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination untuk tugas anggota -->
                                    <?php if ($anggota_total_pages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($anggota_page > 1): ?>
                                            <li class="page-item">
<a class="page-link" href="?anggota_page=<?= $i ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?><?= !empty($bulan) ? '&bulan='.$bulan : '' ?><?= !empty($tahun) ? '&tahun='.$tahun : '' ?>">

                                            </li>
                                            <li class="page-item">
<a class="page-link" href="?anggota_page=<?= $i ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?><?= !empty($bulan) ? '&bulan='.$bulan : '' ?><?= !empty($tahun) ? '&tahun='.$tahun : '' ?>">

                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $start_page = max(1, $anggota_page - 2);
                                            $end_page = min($anggota_total_pages, $anggota_page + 2);
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++): 
                                            ?>
                                            <li class="page-item <?= $i == $anggota_page ? 'active' : '' ?>">
<a class="page-link" href="?anggota_page=<?= $i ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?><?= !empty($bulan) ? '&bulan='.$bulan : '' ?><?= !empty($tahun) ? '&tahun='.$tahun : '' ?>">

                                            </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($anggota_page < $anggota_total_pages): ?>
                                            <li class="page-item">
<a class="page-link" href="?anggota_page=<?= $i ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?><?= !empty($bulan) ? '&bulan='.$bulan : '' ?><?= !empty($tahun) ? '&tahun='.$tahun : '' ?>">

                                            </li>
                                            <li class="page-item">
<a class="page-link" href="?anggota_page=<?= $i ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?><?= !empty($bulan) ? '&bulan='.$bulan : '' ?><?= !empty($tahun) ? '&tahun='.$tahun : '' ?>">

                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Tugas Admin -->
                        <div class="tab-pane fade" id="admin-tasks" role="tabpanel" aria-labelledby="admin-tab">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="m-0 font-weight-bold">Tugas untuk Admin</h5>
                                    <div class="d-flex align-items-center">
                                        <!-- Form filter -->
                                        <form class="d-flex me-2 align-items-center" method="GET">
                                            <input type="hidden" name="admin_page" value="1">
                                            <input type="hidden" name="active_tab" value="admin">
                                            <select name="admin" class="form-select form-select-sm me-2">
                                                <option value="">Semua Admin</option>
                                                <?php 
                                                // Ambil daftar admin
                                                $admin_users_query = "SELECT DISTINCT username FROM users WHERE role = 'admin'";
                                                $admin_users_result = mysqli_query($conn, $admin_users_query);
                                                while($admin_user = mysqli_fetch_assoc($admin_users_result)): 
                                                ?>
                                                    <option value="<?= $admin_user['username'] ?>" <?= $admin == $admin_user['username'] ? 'selected' : '' ?>><?= $admin_user['username'] ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                            <select name="status" class="form-select form-select-sm me-2">
                                                <option value="">Semua Status</option>
                                                <option value="Belum Dikerjakan" <?= $status == 'Belum Dikerjakan' ? 'selected' : '' ?>>Belum Dikerjakan</option>
                                                <option value="Sedang Dikerjakan" <?= $status == 'Sedang Dikerjakan' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                                                <option value="Kirim" <?= $status == 'Kirim' ? 'selected' : '' ?>>Kirim</option>
                                                <option value="Revisi" <?= $status == 'Revisi' ? 'selected' : '' ?>>Revisi</option>
                                                <option value="Selesai" <?= $status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-funnel"></i> Filter
                                            </button>
                                        </form>
                                                                                
                                        <!-- Tombol yang sudah ada -->
                                        <a href="tambah_tugas_kabid.php" class="btn btn-add-task">
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
                                                    <th>Deadline</th>
                                                    <th>Penanggung Jawab</th>
                                                    <th>Status</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                if (mysqli_num_rows($admin_tugas_result) > 0) {
                                                    $no = ($admin_page - 1) * $items_per_page + 1;
                                                    while ($row = mysqli_fetch_assoc($admin_tugas_result)) {
                                                        // Menentukan kelas untuk deadline
                                                        $deadline_class = '';
                                                        $deadline_badge = '';
                                                        
                                                        $deadline_date = new DateTime($row['deadline']);
                                                        $today = new DateTime();
                                                        $interval = $today->diff($deadline_date);
                                                        
                                                        if ($today > $deadline_date && $row['status'] != 'Selesai') {
                                                            $deadline_class = 'text-danger fw-bold';
                                                            $deadline_badge = '<span class="badge bg-danger">Terlambat</span>';
                                                        } elseif ($interval->days <= 3 && $today <= $deadline_date && $row['status'] != 'Selesai') {
                                                            $deadline_class = 'text-warning fw-bold';
                                                            $deadline_badge = '<span class="badge bg-warning text-dark">Segera</span>';
                                                        }
                                                        
                                                        // Menentukan kelas untuk status
                                                        $status_class = '';
                                                        switch ($row['status']) {
                                                            case 'Belum Dikerjakan':
                                                                $status_class = 'bg-danger';
                                                                break;
                                                            case 'Sedang Dikerjakan':
                                                                $status_class = 'bg-warning text-dark';
                                                                break;
                                                            case 'Kirim':
                                                                $status_class = 'bg-info text-dark';
                                                                break;
                                                            case 'Revisi':
                                                                $status_class = 'bg-primary';
                                                                break;
                                                            case 'Selesai':
                                                                $status_class = 'bg-success';
                                                                break;
                                                            default:
                                                                $status_class = 'bg-secondary';
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
                                                    <td class="<?= $deadline_class ?>">
                                                        <?= date('d/m/Y', strtotime($row['deadline'])) ?>
                                                        <?= $deadline_badge ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['penanggung_jawab']) ?></td>
                                                    <td><span class="badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="detail_tugas_kabid.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="edit_tugas_kabid.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <a href="hapus_tugas.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus tugas ini?')">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="7" class="text-center py-4"><i class="bi bi-inbox me-2"></i>Tidak ada tugas untuk admin</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination untuk tugas admin -->
                                    <?php if ($admin_total_pages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($admin_page > 1): ?>
                                            <li class="page-item">
<a class="page-link" href="?admin_page=<?= $i ?><?= !empty($admin) ? '&admin='.$admin : '' ?><?= !empty($status) ? '&status='.$status : '' ?>">

                                            </li>
                                            <li class="page-item">
<a class="page-link" href="?admin_page=<?= $i ?><?= !empty($admin) ? '&admin='.$admin : '' ?><?= !empty($status) ? '&status='.$status : '' ?>">

                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $start_page = max(1, $admin_page - 2);
                                            $end_page = min($admin_total_pages, $admin_page + 2);
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++): 
                                            ?>
                                            <li class="page-item <?= $i == $admin_page ? 'active' : '' ?>">
<a class="page-link" href="?admin_page=<?= $i ?><?= !empty($admin) ? '&admin='.$admin : '' ?><?= !empty($status) ? '&status='.$status : '' ?>">

                                            </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($admin_page < $admin_total_pages): ?>
                                            <li class="page-item">
<a class="page-link" href="?admin_page=<?= $i ?><?= !empty($admin) ? '&admin='.$admin : '' ?><?= !empty($status) ? '&status='.$status : '' ?>">

                                            </li>
                                            <li class="page-item">
<a class="page-link" href="?admin_page=<?= $i ?><?= !empty($admin) ? '&admin='.$admin : '' ?><?= !empty($status) ? '&status='.$status : '' ?>">

                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                    <?php endif; ?>
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
            
            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
            
            // Activate tab based on URL hash
            const url = new URL(window.location.href);
            const hash = url.hash;
            if (hash) {
                const tab = document.querySelector(`[data-bs-target="${hash}"]`);
                if (tab) {
                    const bsTab = new bootstrap.Tab(tab);
                    bsTab.show();
                }
            }
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('active_tab');
            if (activeTab === 'admin') {
                const adminTab = document.querySelector('#admin-tab');
                if (adminTab) {
                    const bsTab = new bootstrap.Tab(adminTab);
                    bsTab.show();
                }
            }
                    });
    </script>
</body>
</html>
