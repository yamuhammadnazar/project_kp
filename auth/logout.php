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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
        }

        .logout-container {
            text-align: center;
            padding: 40px;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        h2 {
            color: #4a4a4a;
            margin-bottom: 10px;
            font-weight: 600;
        }

        p {
            color: #666;
            margin-bottom: 25px;
            font-weight: 300;
        }

        .loader {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }

        .loader div {
            position: absolute;
            top: 33px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: #764ba2;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }

        .loader div:nth-child(1) {
            left: 8px;
            animation: loader1 0.6s infinite;
        }

        .loader div:nth-child(2) {
            left: 8px;
            animation: loader2 0.6s infinite;
        }

        .loader div:nth-child(3) {
            left: 32px;
            animation: loader2 0.6s infinite;
        }

        .loader div:nth-child(4) {
            left: 56px;
            animation: loader3 0.6s infinite;
        }

        @keyframes loader1 {
            0% {
                transform: scale(0);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes loader3 {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(0);
            }
        }

        @keyframes loader2 {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(24px, 0);
            }
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1440 320" xmlns="http://www.w3.org/2000/svg"><path fill="%23764ba2" fill-opacity="0.2" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: 1440px 100px;
            animation: wave 10s linear infinite;
        }

        @keyframes wave {
            0% {
                background-position-x: 0;
            }

            100% {
                background-position-x: 1440px;
            }
        }

        .success-icon {
            display: none;
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background-color: #4CAF50;
            position: relative;
        }

        .success-icon:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%) rotate(45deg);
            width: 25px;
            height: 50px;
            border-right: 5px solid white;
            border-bottom: 5px solid white;
        }
    </style>
</head>

<body>
    <div class="logout-container animate__animated animate__fadeIn" id="logoutBox">
        <div class="wave"></div>
        <h2>Logging Out</h2>
        <div class="loader" id="loader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
        <div class="success-icon" id="successIcon"></div>
        <p id="logoutMessage">Please wait while we securely log you out...</p>
    </div>

    <script>
        // Animasi keluar dengan transisi yang lebih halus
        setTimeout(function () {
            const logoutBox = document.getElementById('logoutBox');
            const loader = document.getElementById('loader');
            const successIcon = document.getElementById('successIcon');
            const logoutMessage = document.getElementById('logoutMessage');

            // Sembunyikan loader dan tampilkan ikon sukses
            loader.style.display = 'none';
            successIcon.style.display = 'block';
            successIcon.classList.add('animate__animated', 'animate__zoomIn');

            // Ubah pesan
            logoutMessage.textContent = 'You have been successfully logged out!';
            logoutMessage.classList.add('animate__animated', 'animate__fadeIn');

            // Animasi keluar setelah menampilkan sukses
            setTimeout(function () {
                logoutBox.classList.remove('animate__fadeIn');
                logoutBox.classList.add('animate__fadeOutUp');

                // Redirect setelah animasi selesai
                setTimeout(function () {
                    window.location.href = "login.php";
                }, 1000);
            }, 1500);
        }, 2000);
    </script>
</body>

</html>
<?php session_destroy(); ?>