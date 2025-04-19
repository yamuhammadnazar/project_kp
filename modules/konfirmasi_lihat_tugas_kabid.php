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

// Cek apakah ada ID tugas
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../modules/daftar_tugas_kabid.php");
    exit();
}

$id_tugas = $_GET['id'];

// Ambil data tugas
$query = "SELECT t.*, u.username 
          FROM tugas_media t 
          JOIN users u ON t.penanggung_jawab = u.username 
          WHERE t.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_tugas);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: ../modules/daftar_tugas_kabid.php");
    exit();
}

$tugas = mysqli_fetch_assoc($result);

// Proses form jika ada POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'mark_seen') {
            // Update status dilihat menjadi 1 (sudah dilihat)
            $update_query = "UPDATE tugas_media SET dilihat_kabid = 1, waktu_dilihat_kabid = NOW() WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "i", $id_tugas);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['success_message'] = "Tugas berhasil ditandai sebagai sudah dilihat.";
                // Redirect ke daftar tugas jika parameter redirect=list ada
                if (isset($_GET['redirect']) && $_GET['redirect'] == 'list') {
                    header("Location: ../modules/daftar_tugas_kabid.php");
                    exit();
                }
                $success_message = "Tugas berhasil ditandai sebagai sudah dilihat.";
                // Update data tugas setelah perubahan
                $tugas['dilihat_kabid'] = 1;
                $tugas['waktu_dilihat_kabid'] = date('Y-m-d H:i:s');
            } else {
                $error_message = "Gagal menandai tugas: " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($update_stmt);
        } elseif ($action == 'mark_unseen') {
            // Update status dilihat menjadi 0 (belum dilihat)
            $update_query = "UPDATE tugas_media SET dilihat_kabid = 0, waktu_dilihat_kabid = NULL WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "i", $id_tugas);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $_SESSION['success_message'] = "Tugas berhasil ditandai sebagai belum dilihat.";
                // Redirect ke daftar tugas jika parameter redirect=list ada
                if (isset($_GET['redirect']) && $_GET['redirect'] == 'list') {
                    header("Location: ../modules/daftar_tugas_kabid.php");
                    exit();
                }
                $success_message = "Tugas berhasil ditandai sebagai belum dilihat.";
                // Update data tugas setelah perubahan
                $tugas['dilihat_kabid'] = 0;
                $tugas['waktu_dilihat_kabid'] = null;
            } else {
                $error_message = "Gagal mengubah status tugas: " . mysqli_error($conn);
            }
            
            mysqli_stmt_close($update_stmt);
        }
    }
}

// Fungsi untuk mendeteksi jenis link
function detectLinkType($url) {
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        return 'youtube';
    } elseif (strpos($url, 'instagram.com') !== false) {
        return 'instagram';
    } elseif (strpos($url, 'facebook.com') !== false || strpos($url, 'fb.com') !== false) {
        return 'facebook';
    } elseif (strpos($url, 'twitter.com') !== false || strpos($url, 'x.com') !== false) {
        return 'twitter';
    } elseif (strpos($url, 'tiktok.com') !== false) {
        return 'tiktok';
    } elseif (strpos($url, 'linkedin.com') !== false) {
        return 'linkedin';
    } elseif (strpos($url, 'drive.google.com') !== false) {
        return 'gdrive';
    } elseif (strpos($url, 'docs.google.com') !== false) {
        return 'gdocs';
    } elseif (strpos($url, 'sheets.google.com') !== false) {
        return 'gsheets';
    } elseif (strpos($url, 'slides.google.com') !== false) {
        return 'gslides';
    } else {
        return 'website';
    }
}

// Fungsi untuk mendapatkan embed code berdasarkan jenis link
function getEmbedCode($url, $type) {
    switch ($type) {
        case 'youtube':
            // Extract YouTube video ID
            $video_id = '';
            if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $matches)) {
                $video_id = $matches[1];
            } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
                $video_id = $matches[1];
            }
            
            if (!empty($video_id)) {
                return '<div class="ratio ratio-16x9">
                          <iframe src="https://www.youtube.com/embed/' . $video_id . '"
                                   title="YouTube video" allowfullscreen></iframe>
                        </div>';
            }
            break;
            
        case 'gdrive':
            // Convert Google Drive link to embed format
            if (strpos($url, '/file/d/') !== false) {
                preg_match('/\/file\/d\/([^\/]+)/', $url, $matches);
                $file_id = $matches[1] ?? '';
                
                if (!empty($file_id)) {
                    return '<div class="ratio ratio-16x9">
                              <iframe src="https://drive.google.com/file/d/' . $file_id . '/preview"
                                       allowfullscreen></iframe>
                            </div>';
                }
            } elseif (strpos($url, '/folder/') !== false) {
                return '<div class="alert alert-info">
                          <i class="bi bi-folder"></i> Ini adalah folder Google Drive. 
                          <a href="' . $url . '" target="_blank" class="btn btn-sm btn-primary">
                            Buka di Google Drive
                          </a>
                        </div>';
            }
            break;
            
        case 'gdocs':
        case 'gsheets':
        case 'gslides':
            // Convert Google Docs/Sheets/Slides link to embed
            if (preg_match('/\/d\/([^\/]+)/', $url, $matches)) {
                $doc_id = $matches[1];
                return '<div class="ratio ratio-16x9">
                          <iframe src="https://docs.google.com/document/d/' . $doc_id . '/preview"
                                   allowfullscreen></iframe>
                        </div>';
            }
            break;
            
        default:
            // For other types, just provide a link
            return '<div class="alert alert-info">
                      <i class="bi bi-link-45deg"></i> Link tidak dapat ditampilkan secara langsung.
                      <a href="' . $url . '" target="_blank" class="btn btn-sm btn-primary ms-2">
                        Buka Link
                      </a>
                    </div>';
    }
    
    // Default fallback
    return '<div class="alert alert-info">
              <i class="bi bi-link-45deg"></i> Link tidak dapat ditampilkan secara langsung.
              <a href="' . $url . '" target="_blank" class="btn btn-sm btn-primary ms-2">
                Buka Link
              </a>
            </div>';
}

// Siapkan embed code jika ada link
$embed_code = '';
if (!empty($tugas['link_drive'])) {
    $link_type = detectLinkType($tugas['link_drive']);
    $embed_code = getEmbedCode($tugas['link_drive'], $link_type);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Tugas - <?= htmlspecialchars($tugas['judul']) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Animate.css untuk animasi -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a472a;
            --secondary-color: #2d5a40;
            --accent-color: #5D9C59;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            padding-top: 20px;
            color: #333;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .btn {
            border-radius: 10px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-warning {
            color: #d39e00;
            border-color: var(--warning-color);
        }
        
        .btn-outline-warning:hover {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: #212529;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.5em 0.8em;
            border-radius: 8px;
            letter-spacing: 0.5px;
        }
        
        .bg-success {
            background-color: var(--success-color) !important;
        }
        
        .bg-warning {
            background-color: var(--warning-color) !important;
        }
        
        .bg-danger {
            background-color: var(--danger-color) !important;
        }
        
        .bg-info {
            background-color: var(--info-color) !important;
        }
        
          .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .bg-secondary {
            background-color: #6c757d !important;
        }
        
        .preview-container {
            border: none;
            border-radius: 15px;
            padding: 20px;
            background-color: white;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .preview-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .detail-item {
            margin-bottom: 20px;
            position: relative;
            padding-left: 10px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            display: block;
        }
        
        .detail-value {
            color: #333;
            font-weight: 500;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #d39e00;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }
        
        .status-card {
            border-radius: 15px;
            padding: 20px;
            background-color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .status-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: inline-block;
            padding: 15px;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .status-seen .status-icon {
            color: var(--success-color);
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .status-unseen .status-icon {
            color: var(--warning-color);
            background-color: rgba(255, 193, 7, 0.1);
        }
        
        .task-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark-color);
            position: relative;
            display: inline-block;
        }
        
        .task-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }
        
        .platform-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            margin-right: 10px;
            background-color: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }
        
        .platform-badge i {
            margin-right: 5px;
            font-size: 1rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .status-badge i {
            margin-right: 5px;
            font-size: 1rem;
        }
        
        .task-info {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }
        
        .task-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .task-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .task-meta-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .task-meta-item i {
            margin-right: 5px;
            font-size: 1rem;
        }
        
        .task-description {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            position: relative;
        }
        
        .task-description:before {
            content: '"';
            position: absolute;
            top: 10px;
            left: 10px;
            font-size: 2rem;
            color: rgba(0, 0, 0, 0.1);
            font-family: serif;
        }
        
        .action-btn {
            width: 100%;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .action-btn i {
            font-size: 1.1rem;
        }
        
        .preview-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--dark-color);
            position: relative;
            display: inline-block;
        }
        
        .preview-title:after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
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
                
                <div class="card animate__animated animate__fadeIn">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold">Detail Tugas</h5>
                        <div>
                            <a href="../modules/daftar_tugas_kabid.php?filter=belum_dilihat" class="btn btn-sm btn-outline-warning me-2">
                                <i class="bi bi-eye-slash"></i> Tugas Belum Dilihat
                            </a>
                            <a href="../modules/daftar_tugas_kabid.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="task-info">
                                    <h2 class="task-title"><?= htmlspecialchars($tugas['judul']) ?></h2>
                                    
                                    <div class="task-meta">
                                        <span class="platform-badge">
                                            <i class="bi bi-globe"></i> <?= htmlspecialchars($tugas['platform']) ?>
                                        </span>
                                        
                                        <?php
                                        $status_badge = '';
                                        switch ($tugas['status']) {
                                            case 'Belum Dikerjakan':
                                                $status_badge = '<span class="status-badge bg-secondary text-white"><i class="bi bi-clock"></i> Belum Dikerjakan</span>';
                                                break;
                                            case 'Sedang Dikerjakan':
                                                $status_badge = '<span class="status-badge bg-primary text-white"><i class="bi bi-gear"></i> Sedang Dikerjakan</span>';
                                                break;
                                            case 'Kirim':
                                                $status_badge = '<span class="status-badge bg-info text-white"><i class="bi bi-send"></i> Kirim</span>';
                                                break;
                                            case 'Selesai':
                                                $status_badge = '<span class="status-badge bg-success text-white"><i class="bi bi-check-circle"></i> Selesai</span>';
                                                break;
                                            case 'Revisi':
                                                $status_badge = '<span class="status-badge bg-warning text-dark"><i class="bi bi-pencil"></i> Revisi</span>';
                                                break;
                                            default:
                                                $status_badge = '<span class="status-badge bg-secondary text-white">' . htmlspecialchars($tugas['status']) . '</span>';
                                        }
                                        echo $status_badge;
                                        ?>
                                    </div>
                                    
                                    <!-- Preview link dipindahkan ke sini -->
                                    <?php if (!empty($tugas['link_drive'])): ?>
                                    <div class="preview-container animate__animated animate__fadeIn mt-4">
                                        <h5 class="preview-title">Preview Link</h5>
                                        <div class="mb-4">
                                            <?= $embed_code ?>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <a href="<?= htmlspecialchars($tugas['link_drive']) ?>" target="_blank" class="btn btn-primary action-btn">
                                                <i class="bi bi-box-arrow-up-right"></i> Buka Link di Tab Baru
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="detail-item">
                                                <span class="detail-label">Penanggung Jawab</span>
                                                <div class="detail-value d-flex align-items-center">
                                                    <i class="bi bi-person-circle me-2"></i>
                                                    <?= htmlspecialchars($tugas['penanggung_jawab']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="detail-item">
                                                <span class="detail-label">Tanggal Mulai</span>
                                                <div class="detail-value d-flex align-items-center">
                                                    <i class="bi bi-calendar-check me-2"></i>
                                                    <?= isset($tugas['tanggal_mulai']) ? date('d F Y', strtotime($tugas['tanggal_mulai'])) : 'Tidak ada tanggal' ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="detail-item">
                                                <span class="detail-label">Deadline</span>
                                                <div class="detail-value d-flex align-items-center">
                                                    <i class="bi bi-calendar-event me-2"></i>
                                                    <?php 
                                                    if (isset($tugas['deadline'])) {
                                                        $deadline_date = new DateTime($tugas['deadline']);
                                                        $today = new DateTime();
                                                        $interval = $today->diff($deadline_date);
                                                        $days_remaining = $interval->days;
                                                        
                                                        echo date('d F Y', strtotime($tugas['deadline']));
                                                        
                                                        if ($today > $deadline_date && $tugas['status'] != 'Selesai') {
                                                            echo ' <span class="badge bg-danger ms-2"><i class="bi bi-exclamation-triangle"></i> Terlewat</span>';
                                                        } elseif ($days_remaining <= 3 && $today <= $deadline_date && $tugas['status'] != 'Selesai') {
                                                            echo ' <span class="badge bg-warning ms-2"><i class="bi bi-exclamation-circle"></i> ' . $days_remaining . ' hari lagi</span>';
                                                        }
                                                    } else {
                                                        echo 'Tidak ada deadline';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($tugas['deskripsi'])): ?>
                                    <div class="detail-item mt-4">
                                        <span class="detail-label">Deskripsi</span>
                                        <div class="task-description">
                                            <?= nl2br(htmlspecialchars($tugas['deskripsi'])) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($tugas['catatan_admin'])): ?>
                                    <div class="detail-item mt-4">
                                        <span class="detail-label">Catatan Admin</span>
                                        <div class="task-description" style="background-color: rgba(23, 162, 184, 0.1);">
                                            <?= nl2br(htmlspecialchars($tugas['catatan_admin'])) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="status-card <?= $tugas['dilihat_kabid'] == 1 ? 'status-seen' : 'status-unseen' ?> animate__animated animate__fadeIn">
                                    <div class="text-center mb-4">
                                        <div class="status-icon">
                                            <i class="bi <?= $tugas['dilihat_kabid'] == 1 ? 'bi-eye-fill' : 'bi-eye-slash-fill' ?>"></i>
                                        </div>
                                        <h5 class="mt-3 mb-2"><?= $tugas['dilihat_kabid'] == 1 ? 'Sudah Dilihat' : 'Belum Dilihat' ?></h5>
                                        
                                        <?php if ($tugas['dilihat_kabid'] == 1 && !empty($tugas['waktu_dilihat_kabid'])): ?>
                                        <p class="text-muted small">
                                            <i class="bi bi-clock me-1"></i> 
                                            Dilihat pada <?= date('d F Y H:i', strtotime($tugas['waktu_dilihat_kabid'])) ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($tugas['dilihat_kabid'] == 0): ?>
                                        <p class="text-muted small">
                                            Tandai sudah dilihat untuk memindahkan ke daftar tugas yang sudah dikonfirmasi
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($tugas['dilihat_kabid'] == 1): ?>
                                    <form method="post" action="" class="mb-3">
                                        <input type="hidden" name="action" value="mark_unseen">
                                        <button type="submit" class="btn btn-outline-primary action-btn">
                                            <i class="bi bi-eye-slash"></i> Tandai Belum Dilihat
                                        </button>
                                    </form>
                                    
                                    <a href="../modules/daftar_tugas_kabid.php" class="btn btn-secondary action-btn">
                                        <i class="bi bi-list-check"></i> Kembali ke Daftar Tugas
                                    </a>
                                    <?php else: ?>
                                    <form method="post" action="" class="mb-3">
                                        <input type="hidden" name="action" value="mark_seen">
                                        <button type="submit" class="btn btn-primary action-btn">
                                            <i class="bi bi-eye"></i> Tandai Sudah Dilihat
                                        </button>
                                    </form>
                                    
                                    <form method="post" action="?redirect=list" class="mb-3">
                                        <input type="hidden" name="action" value="mark_seen">
                                        <button type="submit" class="btn btn-success action-btn">
                                            <i class="bi bi-check-circle"></i> Tandai & Kembali ke Daftar
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card mt-4 animate__animated animate__fadeIn">
                                    <div class="card-header">
                                        <h6 class="m-0 font-weight-bold">Informasi Tambahan</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="task-meta-item mb-3">
                                            <i class="bi bi-calendar-plus"></i>
                                            <span class="ms-2">Dibuat: 
                                                <?= isset($tugas['created_at']) ? date('d F Y', strtotime($tugas['created_at'])) : 'Tidak ada data' ?>
                                            </span>
                                        </div>
                                        
                                        <?php if (isset($tugas['updated_at'])): ?>
                                        <div class="task-meta-item mb-3">
                                            <i class="bi bi-calendar-check"></i>
                                            <span class="ms-2">Diperbarui: 
                                                <?= date('d F Y', strtotime($tugas['updated_at'])) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($tugas['waktu_selesai']) && !empty($tugas['waktu_selesai'])): ?>
                                        <div class="task-meta-item">
                                            <i class="bi bi-calendar-check-fill"></i>
                                            <span class="ms-2">Diselesaikan: 
                                                <?= date('d F Y', strtotime($tugas['waktu_selesai'])) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
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
    
    <script>
    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
    </script>
</body>
</html>
