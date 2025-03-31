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
    <link rel="stylesheet" href="../assets/css/anggota/main.css">
    <link rel="stylesheet" href="../assets/css/anggota/daftar_tugas_anggota.css">
   
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
                                <a href="anggota_dashboard.php" class="btn btn-reset">
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