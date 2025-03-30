<?php
include '../auth/koneksi.php';

if (!isset($_SESSION["username"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = (int)$_POST['id'];
    $new_username = mysqli_real_escape_string($conn, $_POST['new_username']);
    
    // Ambil username lama
    $query_old = "SELECT username FROM users WHERE id = $id";
    $result_old = mysqli_query($conn, $query_old);
    $row = mysqli_fetch_assoc($result_old);
    $old_username = $row['username'];
    
    // Update username
    $query = "UPDATE users SET username = '$new_username' WHERE id = $id";
    
    if (mysqli_query($conn, $query)) {
        // Update penanggung_jawab di tabel tugas_media
        $update_tugas = "UPDATE tugas_media 
                        SET penanggung_jawab = '$new_username' 
                        WHERE penanggung_jawab = '$old_username'";
        mysqli_query($conn, $update_tugas);
        
        $_SESSION['success'] = "Username berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Gagal memperbarui username!";
    }
}

header("Location: ../modules/kelola_akun.php");
exit();
?>