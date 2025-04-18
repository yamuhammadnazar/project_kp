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
    <style>
        :root {
            --primary-color: #1a472a;
            --secondary-color: #2d5a40;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fc;
            padding-top: 20px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 700;
            color: #5a5c69;
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .badge {
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.5em 0.8em;
            border-radius: 0.5rem;
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
        
        .badge-primary {
            background-color: #4e73df;
        }
        
        .badge-secondary {
            background-color: #858796;
        }
        
        .preview-container {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
        }
        
        .detail-label {
            font-weight: 600;
            color: #5a5c69;
        }
        
        .detail-value {
            color: #333;
        }
        
        .embed-responsive {
            position: relative;
            display: block;
            width: 100%;
            padding: 0;
            overflow: hidden;
        }
        
        .embed-responsive::before {
            display: block;
            content: "";
            padding-top: 56.25%;
        }
        
        .embed-responsive iframe {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
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
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="detail-item">
                                    <span class="detail-label">Judul:</span> 
                                    <h4 class="detail-value"><?= htmlspecialchars($tugas['judul']) ?></h4>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Platform:</span>
                                    <span class="detail-value badge bg-info"><?= htmlspecialchars($tugas['platform']) ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Status:</span>
                                    <?php
                                    $status_badge = '';
                                    switch ($tugas['status']) {
                                        case 'Belum Dikerjakan':
                                            $status_badge = '<span class="badge bg-secondary"><i class="bi bi-clock"></i> Belum Dikerjakan</span>';
                                            break;
                                        case 'Sedang Dikerjakan':
                                            $status_badge = '<span class="badge bg-primary"><i class="bi bi-gear"></i> Sedang Dikerjakan</span>';
                                            break;
                                        case 'Kirim':
                                            $status_badge = '<span class="badge bg-info"><i class="bi bi-send"></i> Kirim</span>';
                                            break;
                                        case 'Selesai':
                                            $status_badge = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Selesai</span>';
                                            break;
                                        case 'Revisi':
                                            $status_badge = '<span class="badge bg-warning"><i class="bi bi-pencil"></i> Revisi</span>';
                                            break;
                                        default:
                                            $status_badge = '<span class="badge bg-secondary">' . htmlspecialchars($tugas['status']) . '</span>';
                                    }
                                    echo $status_badge;
                                    ?>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Penanggung Jawab:</span>
                                    <span class="detail-value"><?= htmlspecialchars($tugas['penanggung_jawab']) ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Tanggal Mulai:</span>
                                    <span class="detail-value">
                                        <?= isset($tugas['tanggal_mulai']) ? date('d F Y', strtotime($tugas['tanggal_mulai'])) : 'Tidak ada tanggal' ?>
                                    </span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label">Deadline:</span>
                                    <span class="detail-value">
                                        <?php 
                                        if (isset($tugas['deadline'])) {
                                            $deadline_date = new DateTime($tugas['deadline']);
                                            $today = new DateTime();
                                            $interval = $today->diff($deadline_date);
                                            $days_remaining = $interval->days;
                                            
                                            echo date('d F Y', strtotime($tugas['deadline']));
                                            
                                            if ($today > $deadline_date && $tugas['status'] != 'Selesai') {
                                                echo ' <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Terlewat</span>';
                                            } elseif ($days_remaining <= 3 && $today <= $deadline_date && $tugas['status'] != 'Selesai') {
                                                echo ' <span class="badge bg-warning"><i class="bi bi-exclamation-circle"></i> ' . $days_remaining . ' hari lagi</span>';
                                            }
                                        } else {
                                            echo 'Tidak ada deadline';
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($tugas['deskripsi'])): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Deskripsi:</span>
                                    <div class="detail-value mt-2">
                                        <?= nl2br(htmlspecialchars($tugas['deskripsi'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tugas['catatan_admin'])): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Catatan:</span>
                                    <div class="detail-value mt-2">
                                        <?= nl2br(htmlspecialchars($tugas['catatan_admin'])) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="m-0 font-weight-bold">Status Konfirmasi</h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($tugas['dilihat_kabid'] == 1): ?>
                                            <div class="alert alert-success mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <i class="bi bi-eye-fill fs-3"></i>
                                                    </div>
                                                    <div>
                                                        <strong>Sudah dilihat</strong>
                                                        <?php if (!empty($tugas['waktu_dilihat_kabid'])): ?>
                                                            <div class="small mt-1">
                                                                pada <?= date('d F Y H:i', strtotime($tugas['waktu_dilihat_kabid'])) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <form method="post" action="">
                                                <input type="hidden" name="action" value="mark_unseen">
                                                <button type="submit" class="btn btn-outline-primary w-100 mb-2">
                                                    <i class="bi bi-eye-slash"></i> Tandai Belum Dilihat
                                                </button>
                                            </form>
                                            <a href="../modules/daftar_tugas_kabid.php" class="btn btn-secondary w-100">
                                                <i class="bi bi-list-check"></i> Kembali ke Daftar Tugas
                                            </a>
                                        <?php else: ?>
                                            <div class="alert alert-warning mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <i class="bi bi-eye-slash-fill fs-3"></i>
                                                    </div>
                                                    <div>
                                                        <strong>Belum dilihat</strong>
                                                        <div class="small mt-1">
                                                            Tandai sudah dilihat untuk memindahkan ke daftar tugas yang sudah dikonfirmasi
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <form method="post" action="">
                                                <input type="hidden" name="action" value="mark_seen">
                                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                                    <i class="bi bi-eye"></i> Tandai Sudah Dilihat
                                                </button>
                                            </form>
                                            <form method="post" action="?redirect=list">
                                                <input type="hidden" name="action" value="mark_seen">
                                                <button type="submit" class="btn btn-success w-100">
                                                    <i class="bi bi-check-circle"></i> Tandai & Kembali ke Daftar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($tugas['link_drive'])): ?>
                        <div class="mt-4">
                            <h5 class="mb-3">Preview Link</h5>
                            <div class="preview-container">
                                <?= $embed_code ?>
                                
                                <div class="mt-3">
                                    <a href="<?= htmlspecialchars($tugas['link_drive']) ?>" target="_blank" class="btn btn-primary">
                                        <i class="bi bi-box-arrow-up-right"></i> Buka Link di Tab Baru
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
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
