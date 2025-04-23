<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION["username"];
$id = (int)$_GET['id'];
$query = "SELECT * FROM tugas_media WHERE id = $id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    // Jika data tidak ditemukan, redirect ke dashboard
    header("Location: ../dashboard/admin_dashboard.php?error=task_not_found");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    $action = $_POST['action'];
    
    if ($action == 'revisi') {
        $update_query = "UPDATE tugas_media SET 
                        catatan_admin = '$catatan',
                        status = 'Revisi',
                        link_drive = ''
                        WHERE id = $id";
        
        if(mysqli_query($conn, $update_query)) {
            header("Location: ../dashboard/admin_dashboard.php?message=revisi_success");
            exit();
        }
    } else if ($action == 'terima') {
        // Periksa apakah tugas ini dari anggota
        $check_query = "SELECT u.role FROM tugas_media t 
                        JOIN users u ON t.penanggung_jawab = u.username 
                        WHERE t.id = $id";
        $check_result = mysqli_query($conn, $check_query);
        
        $user_role = '';
        if ($check_result && mysqli_num_rows($check_result) > 0) {
            $user_data = mysqli_fetch_assoc($check_result);
            $user_role = $user_data['role'];
        }
        
        // Update status tugas menjadi Selesai
        $update_query = "UPDATE tugas_media SET 
                        catatan_admin = '$catatan',
                        status = 'Selesai'
                        WHERE id = $id";
        
        if(mysqli_query($conn, $update_query)) {
            // Jika tugas dari anggota, tandai untuk ditampilkan di dashboard kabid
            if ($user_role == 'anggota') {
                // Tambahkan flag atau update field yang menandakan tugas ini sudah diverifikasi admin
                // dan siap ditampilkan di dashboard kabid
                $verified_query = "UPDATE tugas_media SET
                                   verified_by_admin = 1
                                  WHERE id = $id";
                mysqli_query($conn, $verified_query);
                
                header("Location: ../dashboard/admin_dashboard.php?message=accept_success_to_kabid");
            } else {
                header("Location: ../dashboard/admin_dashboard.php?message=accept_success");
            }
            exit();
        }
    }
}

// Periksa penanggung jawab tugas
$check_query = "SELECT u.role FROM tugas_media t 
                JOIN users u ON t.penanggung_jawab = u.username 
                WHERE t.id = $id";
$check_result = mysqli_query($conn, $check_query);
$user_role = '';
$show_kabid_info = false;

if ($check_result && mysqli_num_rows($check_result) > 0) {
    $user_data = mysqli_fetch_assoc($check_result);
    $user_role = $user_data['role'];
    $show_kabid_info = ($user_role == 'anggota');
}

// Menentukan ikon platform
function getPlatformIcon($platform) {
    switch ($platform) {
        case 'Instagram': return 'bi-instagram';
        case 'Facebook': return 'bi-facebook';
        case 'Twitter': return 'bi-twitter';
        case 'TikTok': return 'bi-tiktok';
        case 'YouTube': return 'bi-youtube';
        case 'LinkedIn': return 'bi-linkedin';
        case 'Website': return 'bi-globe';
        default: return 'bi-question-circle';
    }
}

// Menentukan warna platform
function getPlatformColor($platform) {
    switch ($platform) {
        case 'Instagram': return '#E1306C';
        case 'Facebook': return '#4267B2';
        case 'Twitter': return '#1DA1F2';
        case 'TikTok': return '#000000';
        case 'YouTube': return '#FF0000';
        case 'LinkedIn': return '#0077B5';
        case 'Website': return '#27AE60';
        default: return '#6c757d';
    }
}

// Menghitung sisa hari
$deadline = new DateTime($data['deadline']);
$today = new DateTime();
$interval = $today->diff($deadline);
$daysRemaining = $deadline > $today ? $interval->days : -$interval->days;

// Menentukan status badge dan warna
function getStatusBadge($status) {
    switch ($status) {
        case 'Selesai':
            return '<span class="badge bg-success d-flex align-items-center justify-content-center"><i class="bi bi-check-circle me-1"></i>Selesai</span>';
        case 'Sedang Dikerjakan':
            return '<span class="badge bg-primary d-flex align-items-center justify-content-center"><i class="bi bi-hourglass-split me-1"></i>Sedang Dikerjakan</span>';
        case 'Revisi':
            return '<span class="badge bg-warning text-dark d-flex align-items-center justify-content-center"><i class="bi bi-pencil-square me-1"></i>Revisi</span>';
        case 'Belum Dikerjakan':
            return '<span class="badge bg-secondary d-flex align-items-center justify-content-center"><i class="bi bi-clock me-1"></i>Belum Dikerjakan</span>';
        default:
            return '<span class="badge bg-secondary d-flex align-items-center justify-content-center">' . $status . '</span>';
    }
}

$platformIcon = getPlatformIcon($data['platform']);
$platformColor = getPlatformColor($data['platform']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catatan Admin | Media Staff</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 80px;
            --topbar-height: 60px;
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
            --transition-speed: 0.3s;
            --border-radius: 12px;
            --card-shadow: 0 8px 24px rgba(149, 157, 165, 0.2);
        }
                
        body {
            font-family: 'Poppins', sans-serif;
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
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .card-header i {
            font-size: 1.2rem;
            margin-right: 0.75rem;
            color: var(--primary-color);
        }
        
        .task-info {
            background-color: #f8f9fc;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
        }
        
        .task-info:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transform: translateX(5px);
        }
        
        .task-info p {
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }
        
        .task-info p:last-child {
            margin-bottom: 0;
        }
        
        .task-info strong {
            color: var(--dark-color);
            min-width: 140px;
            display: inline-block;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            border: none;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%);
            border: none;
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #858796 0%, #60616f 100%);
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
        }
        
        .btn {
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            z-index: -1;
            transform: scale(0);
            opacity: 0;
            border-radius: var(--border-radius);
            transition: all 0.3s;
        }
        
        .btn:hover::after {
            transform: scale(1);
            opacity: 1;
        }
        
        .alert {
            border-radius: var(--border-radius);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
        }
        
        .alert-info {
            background-color: #e1f0fa;
            color: #0c5460;
        }
        
        .alert-info::before {
            background-color: #36b9cc;
        }
        
        .alert i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }
        
        textarea.form-control {
            min-height: 120px;
            border-radius: var(--border-radius);
            border: 1px solid #e3e6f0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s;
            resize: none;
        }
        
        textarea.form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
            transform: translateY(-3px);
        }
        
        .badge {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 30px;
        }
        
        .platform-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
            margin-left: 10px;
        }
        
        .platform-badge i {
            margin-right: 0.5rem;
            font-size: 1rem;
        }
        
        .deadline-indicator {
            display: flex;
            align-items: center;
            margin-left: 10px;
        }
        
        .deadline-indicator.urgent {
            color: var(--danger-color);
        }
        
        .deadline-indicator.warning {
            color: var(--warning-color);
        }
        
        .deadline-indicator.safe {
            color: var(--success-color);
        }
        
        .deadline-indicator i {
            margin-right: 0.5rem;
        }
        
        .task-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .task-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #5a5c69;
            margin: 0;
            flex-grow: 1;
        }
        
        .task-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #5a5c69;
        }
        
        .form-text {
            font-size: 0.85rem;
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-buttons .btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-buttons .btn i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s, transform 0.5s;
        }
        
        .animate-on-scroll.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Pulse animation for urgent tasks */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Tooltip styling */
        .custom-tooltip {
            position: relative;
            display: inline-block;
        }
        
        .custom-tooltip .tooltip-text {
            visibility: hidden;
            width: 120px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .custom-tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">Media Staff</div>
            <div class="list-group">
                <a href="../dashboard/admin_dashboard.php" class="list-group-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="../modules/daftar_tugas_admin.php" class="list-group-item active">
                    <i class="bi bi-list-task"></i>
                    <span>Daftar Tugas</span>
                </a>
                <a href="../modules/tambah_tugas_anggota.php" class="list-group-item">
                    <i class="bi bi-plus-circle"></i>
                    <span>Tambah Tugas</span>
                </a>
                <a href="../modules/kelola_akun.php" class="list-group-item">
                    <i class="bi bi-people"></i>
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
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 fw-bold animate__animated animate__fadeInLeft">Verifikasi Tugas</h1>
                        <a href="../dashboard/admin_dashboard.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm animate__animated animate__fadeInRight">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                        </a>
                    </div>

                    <div class="card animate-on-scroll">
                        <div class="card-header">
                            <i class="bi bi-clipboard-check"></i> Verifikasi Tugas Media
                        </div>
                        <div class="card-body">
                            <?php if($show_kabid_info): ?>
                                <div class="alert alert-info animate__animated animate__fadeIn">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Info:</strong> Jika Anda menerima tugas ini, tugas akan ditampilkan di dashboard Kepala Bidang.
                                </div>
                            <?php endif; ?>

                            <div class="task-header animate__animated animate__fadeIn">
                                <h4 class="task-title"><?php echo htmlspecialchars($data['judul']); ?></h4>
                                <div class="task-actions">
                                    <span class="platform-badge" style="background-color: <?php echo $platformColor; ?>">
                                        <i class="bi <?php echo $platformIcon; ?>"></i>
                                        <?php echo htmlspecialchars($data['platform']); ?>
                                    </span>
                                    <?php echo getStatusBadge($data['status']); ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="task-info animate-on-scroll">
                                        <div class="section-title">
                                            <i class="bi bi-info-circle"></i> Informasi Tugas
                                        </div>
                                        <p>
                                            <strong>Penanggung Jawab:</strong> 
                                            <span class="badge bg-primary">
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($data['penanggung_jawab']); ?>
                                            </span>
                                        </p>
                                        <p>
                                            <strong>Tanggal Mulai:</strong> 
                                            <span class="text-primary">
                                                <i class="bi bi-calendar-check me-1"></i>
                                                <?php echo date('d F Y', strtotime($data['tanggal_mulai'])); ?>
                                            </span>
                                        </p>
                                        <p>
                                            <strong>Deadline:</strong> 
                                            <span class="text-<?php echo $daysRemaining < 0 ? 'danger' : ($daysRemaining <= 2 ? 'warning' : 'success'); ?>">
                                                <i class="bi bi-calendar-event me-1"></i>
                                                <?php echo date('d F Y', strtotime($data['deadline'])); ?>
                                            </span>
                                            
                                            <span class="deadline-indicator <?php echo $daysRemaining < 0 ? 'urgent pulse' : ($daysRemaining <= 2 ? 'warning' : 'safe'); ?>">
                                                <i class="bi <?php echo $daysRemaining < 0 ? 'bi-exclamation-triangle-fill' : ($daysRemaining <= 2 ? 'bi-exclamation-circle' : 'bi-check-circle'); ?>"></i>
                                                <?php 
                                                    if($daysRemaining < 0) {
                                                        echo 'Terlambat ' . abs($daysRemaining) . ' hari';
                                                    } elseif($daysRemaining == 0) {
                                                        echo 'Hari ini';
                                                    } else {
                                                        echo $daysRemaining . ' hari lagi';
                                                    }
                                                ?>
                                            </span>
                                        </p>
                                        <p>
                                            <strong>Status:</strong> 
                                            <?php echo getStatusBadge($data['status']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6">
                                    <div class="task-info animate-on-scroll" style="animation-delay: 0.2s;">
                                        <div class="section-title">
                                            <i class="bi bi-file-earmark-text"></i> Detail Konten
                                        </div>
                                        <?php if(!empty($data['deskripsi'])): ?>
                                            <p>
                                                <strong>Deskripsi:</strong> 
                                                <span><?php echo nl2br(htmlspecialchars($data['deskripsi'])); ?></span>
                                            </p>
                                        <?php endif; ?>

                                        <?php if (!empty($data['link_drive'])): ?>
                                            <p>
                                                <strong>Link:</strong>
                                                <?php
                                                // Pastikan link memiliki format yang benar
                                                $link = trim($data['link_drive']);
                                                if (!empty($link) && !preg_match("~^(?:f|ht)tps?://~i", $link)) {
                                                    $link = "https://" . $link;
                                                }

                                                // Tentukan jenis link dan ikon yang sesuai
                                                $icon = "bi-link-45deg";
                                                $label = "Lihat File";

                                                if (strpos($link, 'drive.google.com') !== false) {
                                                    $icon = "bi-google";
                                                    $label = "Lihat Google Drive";
                                                } elseif (strpos($link, 'dropbox.com') !== false) {
                                                    $icon = "bi-dropbox";
                                                    $label = "Lihat Dropbox";
                                                } elseif (strpos($link, 'onedrive.live.com') !== false || strpos($link, 'sharepoint.com') !== false) {
                                                    $icon = "bi-microsoft";
                                                    $label = "Lihat OneDrive";
                                                } elseif (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false) {
                                                    $icon = "bi-youtube";
                                                    $label = "Lihat YouTube";
                                                } elseif (strpos($link, 'instagram.com') !== false) {
                                                    $icon = "bi-instagram";
                                                    $label = "Lihat Instagram";
                                                } elseif (strpos($link, 'facebook.com') !== false) {
                                                    $icon = "bi-facebook";
                                                    $label = "Lihat Facebook";
                                                } elseif (strpos($link, 'twitter.com') !== false || strpos($link, 'x.com') !== false) {
                                                    $icon = "bi-twitter";
                                                    $label = "Lihat Twitter";
                                                } elseif (strpos($link, 'tiktok.com') !== false) {
                                                    $icon = "bi-tiktok";
                                                    $label = "Lihat TikTok";
                                                } elseif (strpos($link, 'docs.google.com') !== false) {
                                                    $icon = "bi-file-earmark-text";
                                                    $label = "Lihat Google Docs";
                                                } elseif (strpos($link, 'sheets.google.com') !== false) {
                                                    $icon = "bi-file-earmark-spreadsheet";
                                                    $label = "Lihat Google Sheets";
                                                } elseif (strpos($link, 'slides.google.com') !== false) {
                                                    $icon = "bi-file-earmark-slides";
                                                    $label = "Lihat Google Slides";
                                                }
                                                ?>
                                                <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                                    <i class="bi <?php echo $icon; ?> me-1"></i> <?php echo $label; ?>
                                                </a>
                                            </p>
                                        <?php else: ?>
                                            <p>
                                                <strong>Link:</strong>
                                                <span class="text-muted fst-italic">Belum ada file yang diunggah</span>
                                            </p>
                                        <?php endif; ?>

                                        
                                     
                                    </div>
                                </div>
                            </div>

                            <form method="POST" class="mt-4 animate-on-scroll" style="animation-delay: 0.4s;">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-header bg-light">
                                        <i class="bi bi-pencil-square me-2"></i>
                                        <span class="fw-bold">Catatan Admin</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-4">
                                            <label for="catatan" class="form-label">Berikan catatan atau feedback:</label>
                                            <textarea class="form-control" id="catatan" name="catatan" rows="5" required><?php echo htmlspecialchars($data['catatan_admin'] ?? ''); ?></textarea>
                                            <div class="form-text text-muted">
                                                <i class="bi bi-info-circle me-1"></i> Catatan ini akan dilihat oleh anggota yang bertanggung jawab atas tugas ini.
                                            </div>
                                        </div>
                                        
                                        <div class="action-buttons">
                                            <button type="submit" name="action" value="terima" class="btn btn-success">
                                                <i class="bi bi-check-circle"></i> Terima Tugas
                                            </button>
                                            <button type="submit" name="action" value="revisi" class="btn btn-danger">
                                                <i class="bi bi-arrow-repeat"></i> Minta Revisi
                                            </button>
                                            <a href="../modules/daftar_tugas_admin.php" class="btn btn-secondary">
                                                <i class="bi bi-x-circle"></i> Batal
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay untuk mobile -->
    <div class="sidebar-overlay"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Menunggu dokumen selesai dimuat
        document.addEventListener('DOMContentLoaded', function() {
            // Menambahkan class 'loaded' ke body setelah halaman dimuat
            document.body.classList.add('loaded');
            
            // Toggle sidebar
            const menuToggle = document.getElementById('menu-toggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const pageContentWrapper = document.getElementById('page-content-wrapper');
            const sidebarOverlay = document.querySelector('.sidebar-overlay');
            
            // Fungsi untuk toggle sidebar
            function toggleSidebar() {
                if (window.innerWidth < 768) {
                    // Mobile behavior
                    sidebarWrapper.classList.toggle('show');
                    sidebarOverlay.classList.toggle('show');
                } else {
                    // Desktop behavior
                    sidebarWrapper.classList.toggle('collapsed');
                    pageContentWrapper.classList.toggle('expanded');
                }
            }
            
            // Event listener untuk tombol toggle
            menuToggle.addEventListener('click', toggleSidebar);
            
            // Event listener untuk overlay (hanya untuk mobile)
            sidebarOverlay.addEventListener('click', function() {
                sidebarWrapper.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
            
            // Menyesuaikan tampilan saat resize window
            window.addEventListener('resize', function() {
                if (window.innerWidth < 768) {
                    // Mobile view
                    sidebarWrapper.classList.remove('collapsed');
                    pageContentWrapper.classList.remove('expanded');
                    if (sidebarWrapper.classList.contains('show')) {
                        sidebarOverlay.classList.add('show');
                    }
                } else {
                    // Desktop view
                    sidebarOverlay.classList.remove('show');
                    sidebarWrapper.classList.remove('show');
                }
            });
            
            // Konfirmasi sebelum submit
            const form = document.querySelector('form');
            form.addEventListener('submit', function(event) {
                const action = event.submitter.value;
                
                if (action === 'revisi') {
                    if (!confirm('Apakah Anda yakin ingin meminta revisi untuk tugas ini?')) {
                        event.preventDefault();
                    }
                } else if (action === 'terima') {
                    if (!confirm('Apakah Anda yakin ingin menerima tugas ini?')) {
                        event.preventDefault();
                    }
                }
            });
            
            // Animasi scroll
            const animateElements = document.querySelectorAll('.animate-on-scroll');
            
            function checkScroll() {
                animateElements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const windowHeight = window.innerHeight;
                    
                    if (elementPosition < windowHeight - 50) {
                        element.classList.add('show');
                    }
                });
            }
            
            // Jalankan saat halaman dimuat
            checkScroll();
            
            // Jalankan saat scroll
            window.addEventListener('scroll', checkScroll);
        });
    </script>
</body>
</html>
