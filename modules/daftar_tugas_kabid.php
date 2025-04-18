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

// Filter untuk tugas belum dilihat
$filter_condition = "";
if (isset($_GET['filter']) && $_GET['filter'] == 'belum_dilihat') {
    $filter_condition = " AND (t.dilihat_kabid = 0 OR t.dilihat_kabid IS NULL)";
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
                     AND t.pemberi_tugas = 'kabid'";
$count_admin_result = mysqli_query($conn, $count_admin_query);
$count_admin_row = mysqli_fetch_assoc($count_admin_result);
$admin_total_pages = ceil($count_admin_row['total'] / $items_per_page);

// Query untuk tugas yang diberikan kabid kepada admin dengan pagination
$admin_tugas_query = "SELECT t.*, u.username 
                     FROM tugas_media t 
                     JOIN users u ON t.penanggung_jawab = u.username 
                     WHERE u.role = 'admin' 
                     AND t.pemberi_tugas = 'kabid' 
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
    <title>Daftar Tugas Kabid</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="dashboard-header text-center">
            <h2 class="dashboard-title">Dashboard Kepala Bidang</h2>
            <p class="dashboard-subtitle">Monitoring dan Manajemen Tugas</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-11">
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
                                <div>
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
                                            <a class="page-link" href="?anggota_page=1<?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?>" aria-label="First">
                                                <span aria-hidden="true">&laquo;&laquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?anggota_page=<?= $anggota_page - 1 ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start_page = max(1, $anggota_page - 2);
                                        $end_page = min($anggota_total_pages, $anggota_page + 2);
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++):
                                         ?>
                                        <li class="page-item <?= $i == $anggota_page ? 'active' : '' ?>">
                                            <a class="page-link" href="?anggota_page=<?= $i ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($anggota_page < $anggota_total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?anggota_page=<?= $anggota_page + 1 ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?anggota_page=<?= $anggota_total_pages ?><?= isset($_GET['filter']) ? '&filter='.$_GET['filter'] : '' ?>" aria-label="Last">
                                                <span aria-hidden="true">&raquo;&raquo;</span>
                                            </a>
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
                                <a href="tambah_tugas_admin.php" class="btn btn-add-task">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Tugas Baru
                                </a>
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
                                                    $deadline = new DateTime($row['deadline']);
                                                    $today = new DateTime();
                                                    $interval = $today->diff($deadline);
                                                    $deadline_class = '';
                                                    $deadline_badge = '';
                                                    
                                                    if ($deadline < $today) {
                                                        $deadline_class = 'text-danger fw-bold';
                                                        $deadline_badge = '<span class="badge bg-danger ms-1">Terlambat</span>';
                                                    } elseif ($interval->days <= 2) {
                                                        $deadline_class = 'text-warning fw-bold';
                                                        $deadline_badge = '<span class="badge bg-warning ms-1">Segera</span>';
                                                    }
                                                    
                                                    // Menentukan badge untuk status
                                                    $status_badge = '';
                                                    switch ($row['status']) {
                                                        case 'Belum Dikerjakan':
                                                            $status_badge = 'bg-secondary';
                                                            break;
                                                        case 'Sedang Dikerjakan':
                                                            $status_badge = 'bg-primary';
                                                            break;
                                                        case 'Kirim':
                                                            $status_badge = 'bg-info';
                                                            break;
                                                        case 'Revisi':
                                                            $status_badge = 'bg-danger';
                                                            break;
                                                        case 'Selesai':
                                                            $status_badge = 'bg-success';
                                                            break;
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
                                                    <?= date('d M Y', strtotime($row['deadline'])) ?>
                                                    <?= $deadline_badge ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['penanggung_jawab']) ?></td>
                                                <td>
                                                    <span class="badge status-badge <?= $status_badge ?>"><?= $row['status'] ?></span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="../views/catatan_kabid.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="../modules/edit_tugas_kabid.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                                onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['judul'])) ?>')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
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
                                            <a class="page-link" href="?admin_page=1" aria-label="First">
                                                <span aria-hidden="true">&laquo;&laquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?admin_page=<?= $admin_page - 1 ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start_page = max(1, $admin_page - 2);
                                        $end_page = min($admin_total_pages, $admin_page + 2);
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++):
                                         ?>
                                        <li class="page-item <?= $i == $admin_page ? 'active' : '' ?>">
                                            <a class="page-link" href="?admin_page=<?= $i ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($admin_page < $admin_total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?admin_page=<?= $admin_page + 1 ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                        <li class="page-item">
                                            <a class="page-link" href="?admin_page=<?= $admin_total_pages ?>" aria-label="Last">
                                                <span aria-hidden="true">&raquo;&raquo;</span>
                                            </a>
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
    
    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-center">Apakah Anda yakin ingin menghapus tugas:</p>
                    <p class="text-center fw-bold" id="taskTitle"></p>
                    <p class="text-danger text-center"><small><i class="bi bi-exclamation-triangle-fill"></i> Tindakan ini tidak dapat dibatalkan.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="deleteTaskBtn" class="btn btn-danger">Hapus</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
