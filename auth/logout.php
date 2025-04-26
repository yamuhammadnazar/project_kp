<?php session_start(); ?>
<!DOCTYPE html>
<html>

<head>
    <title>Logging Out</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #ffffff;
            overflow: hidden;
        }

        .logout-container {
            text-align: center;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            position: relative;
        }

        h2 {
            color: #333333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        p {
            color: #666666;
            margin-bottom: 25px;
            font-weight: 300;
        }

        .spinner {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: #4e73df;
            animation: spin 0.8s ease-in-out infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .checkmark {
            display: none;
            width: 50px;
            height: 50px;
            margin: 0 auto 20px;
            position: relative;
        }

        .checkmark__circle {
            width: 50px;
            height: 50px;
            stroke-width: 2;
            stroke: #4e73df;
            stroke-miterlimit: 10;
            fill: none;
            animation: stroke 0.4s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }

        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            stroke: #4e73df;
            stroke-width: 3;
            animation: stroke 0.2s cubic-bezier(0.65, 0, 0.45, 1) 0.5s forwards;
        }

        @keyframes stroke {
            100% {
                stroke-dashoffset: 0;
            }
        }

        .btn-login {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }

        .btn-login.show {
            opacity: 1;
            transform: translateY(0);
        }

        .btn-login:hover {
            background-color: #2e59d9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 89, 217, 0.2);
        }

        /* Modal Popup Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-container {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 350px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: all 0.3s ease;
            text-align: center;
        }

        .modal-overlay.active .modal-container {
            transform: translateY(0);
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .modal-text {
            margin-bottom: 20px;
            color: #666;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background-color: #4e73df;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 89, 217, 0.2);
        }

        .btn-secondary {
            background-color: #f8f9fc;
            color: #5a5c69;
            border: 1px solid #d1d3e2;
        }

        .btn-secondary:hover {
            background-color: #eaecf4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <!-- Modal Popup untuk Konfirmasi -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-container animate__animated animate__fadeIn">
            <div class="modal-title">Konfirmasi Logout</div>
            <div class="modal-text">Apakah Anda yakin ingin keluar dari sistem?</div>
            <div class="modal-buttons">
                <button class="btn btn-primary" id="confirmLogout">Ya, Keluar</button>
                <button class="btn btn-secondary" id="cancelLogout">Batal</button>
            </div>
        </div>
    </div>

    <div class="logout-container animate__animated animate__fadeIn" id="logoutBox">
        <h2>Logging Out</h2>
        <div class="spinner" id="spinner"></div>
        <svg class="checkmark" id="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
        </svg>
        <p id="logoutMessage">Please wait while we securely log you out...</p>
        <a href="login.php" class="btn-login" id="loginBtn">Return to Login</a>
    </div>

    <script>
        // Tampilkan modal konfirmasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('logoutModal');
            const logoutBox = document.getElementById('logoutBox');

            // Sembunyikan logout box dulu
            logoutBox.style.display = 'none';

            // Tampilkan modal
            setTimeout(function () {
                modal.classList.add('active');
            }, 100);

            // Tombol batal
            document.getElementById('cancelLogout').addEventListener('click', function () {
                modal.classList.remove('active');
                // Kembali ke halaman sebelumnya
                setTimeout(function () {
                    window.history.back();
                }, 300);
            });

            // Tombol konfirmasi logout
            document.getElementById('confirmLogout').addEventListener('click', function () {
                // Sembunyikan modal
                modal.classList.remove('active');

                // Tampilkan logout box
                setTimeout(function () {
                    logoutBox.style.display = 'block';

                    // Animasi logout dengan transisi yang lebih cepat
                    setTimeout(function () {
                        const spinner = document.getElementById('spinner');
                        const checkmark = document.getElementById('checkmark');
                        const logoutMessage = document.getElementById('logoutMessage');
                        const loginBtn = document.getElementById('loginBtn');

                        // Sembunyikan spinner dan tampilkan checkmark
                        spinner.style.display = 'none';
                        checkmark.style.display = 'block';

                        // Ubah pesan
                        logoutMessage.textContent = 'You have been successfully logged out!';
                        logoutMessage.classList.add('animate__animated', 'animate__fadeIn');

                        // Tampilkan tombol login
                        setTimeout(function () {
                            loginBtn.classList.add('show');
                        }, 300);

                        // Redirect otomatis setelah beberapa detik
                        setTimeout(function () {
                            window.location.href = "login.php";
                        }, 3000);

                        // Proses logout di server
                        fetch('logout_process.php')
                            .then(response => response.text())
                            .then(data => console.log(data))
                            .catch(error => console.error('Error:', error));

                    }, 1200);
                }, 300);
            });
        });
    </script>
</body>

</html>
<?php
// Jangan destroy session di sini, tapi di logout_process.php
?>