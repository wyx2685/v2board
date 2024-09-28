<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطا در پرداخت</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v26.0.2/dist/font-face.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazir', sans-serif;
            background: linear-gradient(to right, #f85032, #e73827);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #fff;
            transition: background-color 0.3s;
        }

        body.dark-mode {
            background: linear-gradient(to right, #434343, #000000);
        }

        .payment-result {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            text-align: center;
            max-width: 80%;
            transition: background-color 0.3s, color 0.3s;
            animation: fadeIn 1s ease-in-out;
            color: #333;
        }

        .dark-mode .payment-result {
            background-color: rgba(68, 68, 68, 0.9);
            color: #f0f0f0;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
            color: #F44336;
            animation: bounce 1s infinite;
        }

        h1 {
            color: #F44336;
            margin-bottom: 10px;
            animation: fadeInDown 0.5s ease-in-out;
        }

        .dark-mode h1 {
            color: #FF7043;
        }

        p {
            font-size: 20px;
            margin-bottom: 5px;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .order-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .dark-mode .order-info {
            border-top: 1px solid #666;
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 20px;
            color: white;
            background: linear-gradient(to right, #F44336, #D32F2F);
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s, transform 0.3s;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .dark-mode .button {
            background: linear-gradient(to right, #FF7043, #D32F2F);
        }

        .button:hover {
            background: linear-gradient(to right, #D32F2F, #C62828);
            transform: translateY(-2px);
        }

        .toggle-dark-mode {
            position: absolute;
            top: 20px;
            right: 20px;
            cursor: pointer;
        }

        .progress-bar {
            width: 100%;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 20px;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .progress {
            height: 20px;
            background-color: #F44336;
            width: 0; /* شروع از 0 درصد */
            transform-origin: right;
            transition: width 0.1s ease; /* اضافه کردن transition */
        }

        .progress-percentage {
            margin-top: 10px;
            font-size: 16px;
            color: #333;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .dark-mode .progress-percentage {
            color: #f0f0f0;
        }

        .countdown-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            animation: fadeInUp 0.5s ease-in-out;
        }

        .countdown {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #F44336;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-left: 10px;
            animation: pulse 1s infinite;
        }

        .dark-mode .countdown {
            background-color: #FF7043;
        }

        @keyframes progress-animation {
            from {
                transform: scaleX(0);
            }
            to {
                transform: scaleX(1);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-30px);
            }
            60% {
                transform: translateY(-15px);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
    </style>
</head>
<body>
    <div class="toggle-dark-mode">
        <i class="fas fa-moon" aria-label="Toggle Dark Mode"></i>
    </div>
    <div class="payment-result" role="alert">
        <div class="icon" aria-hidden="true">✘</div>
        <h1>خطا در پرداخت</h1>
        <p>{{ $message }}</p>
        <a href="https://drmobjay.com/index2332.html#/dashboard/order" class="button" role="button">رفتن به سفارشات</a>
        <div class="progress-bar" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
            <div class="progress"></div>
        </div>
        <div class="progress-percentage" id="progress-percentage">در حال برگشت به سایت: ۰٪</div>
        <div class="countdown-wrapper">
            <div class="countdown" id="countdown" aria-live="polite">10</div>
            <p>ثانیه دیگر به صورت خودکار به صفحه هدایت می‌شوید</p>
        </div>
    </div>
    <script>
        const toggleDarkMode = document.querySelector('.toggle-dark-mode');
        const icon = toggleDarkMode.querySelector('i');

        toggleDarkMode.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            icon.classList.toggle('fa-moon');
            icon.classList.toggle('fa-sun');
        });

        // هدایت خودکار بعد از 10 ثانیه
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown === 0) {
                clearInterval(countdownInterval);
                window.location.href = "https://drmobjay.com/index2332.html#/dashboard/order";
            }
        }, 1000);

        // به‌روزرسانی درصد پیشرفت
        const progressPercentage = document.getElementById('progress-percentage');
        const progressBar = document.querySelector('.progress');
        let progressValue = 0;
        const updateProgressInterval = setInterval(() => {
            progressValue += 1; // افزایش 1 درصد در هر 0.1 ثانیه
            progressPercentage.textContent = `در حال برگشت به سایت: ${progressValue}%`;
            progressBar.style.width = `${progressValue}%`;
            if (progressValue === 100) {
                clearInterval(updateProgressInterval);
            }
        }, 100); // به‌روزرسانی هر 100 میلی‌ثانیه
    </script>
</body>
</html>
