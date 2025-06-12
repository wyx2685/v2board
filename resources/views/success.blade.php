<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت موفق</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/irancell-font@v2.0.0/dist/font-face.css" rel="stylesheet" type="text/css" />
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Irancell','Tahoma',sans-serif;background:radial-gradient(circle at center,#e8f5e8 0%,#c8e6c9 50%,#a5d6a7 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:15px;position:relative;}
        body.dark-mode{background:radial-gradient(circle at center,#2e7d32 0%,#388e3c 50%,#43a047 100%);}
        .receipt-container{background:linear-gradient(to bottom,#fff 0%,#fff 70%,#f8fffe 100%);width:100%;max-width:340px;position:relative;box-shadow:0 15px 35px rgba(76,175,80,0.15),0 5px 15px rgba(76,175,80,0.1);transform:rotate(1deg);transition:all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);animation:receiptFloat 0.8s ease-out;border-radius:8px 8px 0 0;}
        .receipt-container:hover{transform:rotate(0deg) scale(1.02);}
        body.dark-mode .receipt-container{background:linear-gradient(to bottom,#43a047 0%,#43a047 70%,#388e3c 100%);color:#fff;}
        .receipt-header{background:linear-gradient(135deg,#4caf50 0%,#388e3c 100%);color:#fff;padding:20px;text-align:center;position:relative;border-radius:8px 8px 0 0;}
        .receipt-header::after{content:'';position:absolute;bottom:-10px;left:0;right:0;height:10px;background:radial-gradient(circle at 5px 0,transparent 5px,#4caf50 5px),linear-gradient(to right,#4caf50,#388e3c);background-size:10px 10px,100% 10px;}
        .store-name{font-size:14px;font-weight:600;margin-bottom:5px;opacity:0.9;}
        .success-title{font-size:20px;font-weight:700;margin:8px 0;animation:titleGlow 2s infinite alternate;}
        .receipt-number{font-size:10px;opacity:0.8;margin-top:5px;font-family:'Courier New',monospace;}
        .receipt-body{padding:20px;background:#fff;position:relative;}
        body.dark-mode .receipt-body{background:#43a047;}
        .receipt-body::before{content:'';position:absolute;left:12px;top:0;bottom:0;width:2px;background:repeating-linear-gradient(to bottom,#4caf50 0px,#4caf50 8px,transparent 8px,transparent 16px);opacity:0.3;}
        .success-icon{font-size:40px;margin:15px 0;color:#4caf50;animation:successPulse 2s infinite;text-align:center;}
        .success-message{background:linear-gradient(135deg,#e8f5e8 0%,#c8e6c9 100%);border:2px solid #a5d6a7;border-radius:8px;padding:15px;margin:15px 0;font-size:13px;color:#2e7d32;text-align:center;font-weight:600;position:relative;overflow:hidden;}
        .success-message::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.4),transparent);animation:shimmer 3s infinite;}
        body.dark-mode .success-message{background:linear-gradient(135deg,#66bb6a 0%,#4caf50 100%);border-color:#81c784;color:#fff;}
        .receipt-section{margin:15px 0;padding:12px 0;border-bottom:1px dashed #ddd;}
        .receipt-section:last-child{border-bottom:none;}
        body.dark-mode .receipt-section{border-bottom-color:rgba(255,255,255,0.3);}
        .receipt-item{display:flex;justify-content:space-between;align-items:center;margin:8px 0;font-size:13px;line-height:1.4;}
        .receipt-label{font-weight:600;color:#2e7d32;display:flex;align-items:center;}
        .receipt-label::before{content:'●';color:#4caf50;margin-left:6px;font-size:6px;}
        body.dark-mode .receipt-label{color:#fff;}
        .receipt-value{color:#616161;font-weight:500;}
        body.dark-mode .receipt-value{color:rgba(255,255,255,0.9);}
        .success-status{color:#4caf50 !important;font-weight:700;animation:statusBlink 2s infinite;}
        .order-info{background:rgba(76,175,80,0.05);border-radius:6px;padding:12px;margin:15px 0;border:1px solid rgba(76,175,80,0.2);}
        body.dark-mode .order-info{background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);}
        .activation-section{text-align:center;margin:20px 0;padding:15px;background:rgba(76,175,80,0.08);border-radius:8px;border:1px dashed #4caf50;}
        body.dark-mode .activation-section{background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.3);}
        .progress-bar{width:100%;height:6px;background:#e8f5e8;border-radius:3px;margin:10px 0;overflow:hidden;}
        body.dark-mode .progress-bar{background:rgba(255,255,255,0.2);}
        .progress-fill{height:100%;background:linear-gradient(90deg,#4caf50 0%,#66bb6a 50%,#4caf50 100%);width:0%;transition:width 0.3s;border-radius:3px;}
        .progress-text{font-size:12px;color:#4caf50;font-weight:600;margin:8px 0;}
        body.dark-mode .progress-text{color:#fff;}
        .countdown-section{display:flex;align-items:center;justify-content:center;gap:8px;margin:15px 0;}
        .countdown-number{background:linear-gradient(135deg,#4caf50 0%,#388e3c 100%);color:#fff;width:35px;height:35px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;animation:countdownPulse 1s infinite;}
        .countdown-text{font-size:12px;color:#4caf50;font-weight:600;}
        body.dark-mode .countdown-text{color:#fff;}
        .receipt-footer{background:linear-gradient(to bottom,#fff 0%,#f8fffe 100%);padding:20px;position:relative;border-radius:0 0 8px 8px;}
        body.dark-mode .receipt-footer{background:linear-gradient(to bottom,#43a047 0%,#388e3c 100%);}
        .receipt-button{background:linear-gradient(135deg,#2196f3 0%,#1976d2 100%);color:#fff;border:none;padding:12px 25px;border-radius:8px;font-size:14px;cursor:pointer;text-decoration:none;display:block;text-align:center;font-family:'Irancell',sans-serif;font-weight:700;transition:all 0.3s;position:relative;overflow:hidden;}
        .receipt-button::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);transition:left 0.5s;}
        .receipt-button:hover::before{left:100%;}
        .receipt-button:hover{transform:translateY(-2px);}
        .receipt-torn{position:absolute;bottom:-6px;left:0;right:0;height:6px;background:radial-gradient(circle at 6px 0,transparent 6px,#fff 6px),linear-gradient(to right,#fff,#f8fffe);background-size:12px 6px,100% 6px;}
        body.dark-mode .receipt-torn{background:radial-gradient(circle at 6px 0,transparent 6px,#388e3c 6px),linear-gradient(to right,#388e3c,#43a047);background-size:12px 6px,100% 6px;}
        .dark-toggle{position:fixed;top:20px;left:20px;background:rgba(255,255,255,0.9);border:none;border-radius:50%;width:40px;height:40px;font-size:16px;cursor:pointer;transition:all 0.3s;z-index:100;}
        body.dark-mode .dark-toggle{background:rgba(67,160,71,0.9);color:#fff;}
        .dark-toggle:hover{transform:scale(1.1);}
        @keyframes receiptFloat{0%{transform:translateY(-100px) rotate(5deg);opacity:0;}60%{transform:translateY(10px) rotate(-1deg);opacity:0.8;}100%{transform:translateY(0) rotate(1deg);opacity:1;}}
        @keyframes titleGlow{0%{text-shadow:0 1px 2px rgba(0,0,0,0.1);}100%{text-shadow:0 1px 2px rgba(0,0,0,0.1),0 0 8px rgba(255,255,255,0.4);}}
        @keyframes successPulse{0%,100%{transform:scale(1);opacity:1;}50%{transform:scale(1.06);opacity:0.9;}}
        @keyframes statusBlink{0%,70%{opacity:1;}85%{opacity:0.7;}100%{opacity:1;}}
        @keyframes shimmer{0%{left:-100%;}100%{left:100%;}}
        @keyframes countdownPulse{0%,100%{transform:scale(1);}50%{transform:scale(1.05);}}
        @media (max-width:480px){.receipt-container{max-width:300px;transform:rotate(0.5deg);}.receipt-header{padding:15px;}.receipt-body{padding:15px;}.success-title{font-size:18px;}.success-icon{font-size:36px;}.dark-toggle{top:15px;left:15px;width:36px;height:36px;font-size:14px;}}
        @media (max-width:320px){.receipt-container{max-width:280px;}.receipt-header{padding:12px;}.receipt-body{padding:12px;}.receipt-button{padding:10px 20px;font-size:13px;}}
    </style>
</head>
<body>
    <button class="dark-toggle" onclick="toggleDarkMode()" aria-label="تغییر حالت">🌙</button>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="store-name">سیستم پرداخت آنلاین</div>
            <h1 class="success-title">پرداخت موفقیت‌آمیز</h1>
            <div class="receipt-number">شماره: #SUC-<span id="receiptNumber"></span></div>
        </div>
        <div class="receipt-body">
            <div class="success-icon">✅</div>
            <div class="success-message">
                🎉 تبریک! پرداخت شما با موفقیت انجام شد
                <br>سرویس شما به زودی فعال خواهد شد
            </div>
            <div class="receipt-section">
                <div class="receipt-item">
                    <span class="receipt-label">تاریخ</span>
                    <span class="receipt-value" id="currentDate"></span>
                </div>
                <div class="receipt-item">
                    <span class="receipt-label">زمان</span>
                    <span class="receipt-value" id="currentTime"></span>
                </div>
                <div class="receipt-item">
                    <span class="receipt-label">وضعیت</span>
                    <span class="receipt-value success-status">موفق</span>
                </div>
                <div class="receipt-item">
                    <span class="receipt-label">کد تأیید</span>
                    <span class="receipt-value" id="confirmationCode"></span>
                </div>
            </div>
            <div class="order-info" id="orderInfo">
                {!! $orderInfo !!}
            </div>
            <div class="activation-section">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">در حال فعال‌سازی: ۰٪</div>
                <div class="countdown-section">
                    <span class="countdown-number" id="countdown">5</span>
                    <span class="countdown-text">ثانیه تا انتقال</span>
                </div>
            </div>
        </div>
        <div class="receipt-footer">
            <a href="https://drmobjay.com/index2332.html#/dashboard" class="receipt-button">🏠 رفتن به داشبورد</a>
            <div class="receipt-torn"></div>
        </div>
    </div>
    <script>
        function generateReceiptNumber(){return Date.now().toString().slice(-6)+Math.floor(Math.random()*1000).toString().padStart(3,'0');}
        function generateConfirmationCode(){return Math.floor(Math.random()*900000+100000).toString();}
        function updateDateTime(){const now=new Date();try{const date=now.toLocaleDateString('fa-IR',{year:'numeric',month:'2-digit',day:'2-digit'});const time=now.toLocaleTimeString('fa-IR',{hour:'2-digit',minute:'2-digit'});document.getElementById('currentDate').textContent=date;document.getElementById('currentTime').textContent=time;}catch(e){const date=now.getFullYear()+'/'+(now.getMonth()+1).toString().padStart(2,'0')+'/'+now.getDate().toString().padStart(2,'0');const time=now.getHours().toString().padStart(2,'0')+':'+now.getMinutes().toString().padStart(2,'0');document.getElementById('currentDate').textContent=date;document.getElementById('currentTime').textContent=time;}}
        document.getElementById('receiptNumber').textContent=generateReceiptNumber();document.getElementById('confirmationCode').textContent=generateConfirmationCode();updateDateTime();
        let countdown=5,progress=0;const countdownEl=document.getElementById('countdown'),progressEl=document.getElementById('progressFill'),progressTextEl=document.getElementById('progressText');
        function updateCountdown(){countdown--;countdownEl.textContent=countdown;progress+=20;progressEl.style.width=progress+'%';progressTextEl.textContent='در حال فعال‌سازی: '+progress+'٪';if(countdown<=0)window.location.href='https://drmobjay.com/index2332.html#/dashboard';}
        const timer=setInterval(updateCountdown,1000);
        function toggleDarkMode(){document.body.classList.toggle('dark-mode');const toggle=document.querySelector('.dark-toggle');toggle.textContent=document.body.classList.contains('dark-mode')?'☀️':'🌙';try{localStorage.setItem('darkMode',document.body.classList.contains('dark-mode'));}catch(e){}}
        function loadUserPreferences(){try{const isDarkMode=localStorage.getItem('darkMode')==='true';if(isDarkMode||(window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches&&!localStorage.getItem('darkMode'))){document.body.classList.add('dark-mode');document.querySelector('.dark-toggle').textContent='☀️';}}catch(e){}}
        loadUserPreferences();
        const link=document.createElement('link');link.rel='prefetch';link.href='https://drmobjay.com/index2332.html#/dashboard';document.head.appendChild(link);
        document.addEventListener('keydown',function(e){if(e.key==='Escape')window.location.href='https://drmobjay.com/index2332.html#/dashboard';if(e.key==='d'||e.key==='D')toggleDarkMode();});
        document.addEventListener('contextmenu',function(e){e.preventDefault();});
    </script>
</body>
</html>
