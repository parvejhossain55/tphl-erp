<!doctype html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Sign In TPHL</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-style-mode" content="1">
    <!-- Place favicon.ico in the root directory -->
    <link rel="shortcut icon" type="image/x-icon" href="assets/images/logo/tphl_logo.png">

    <!-- CSS here -->
    <link rel="stylesheet" href="assets/css/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/vendor/animate.css">
    <link rel="stylesheet" href="assets/css/plugins/apexcharts.css">
    <link rel="stylesheet" href="assets/css/plugins/jquery-jvectormap-2.0.5.css">
    <link rel="stylesheet" href="assets/css/plugins/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/vendor/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/vendor/icomoon.css">
    <link rel="stylesheet" href="assets/css/vendor/fontawesome-pro.css">
    <link rel="stylesheet" href="assets/css/vendor/rating.css">
    <link rel="stylesheet" href="assets/css/vendor/dropzone.css">
    <link rel="stylesheet" href="assets/css/plugins/dropify.min.css">
    <link rel="stylesheet" href="assets/css/vendor/spacing.css">
    <link rel="stylesheet" href="assets/css/plugins/datatables.min.css">
    <link rel="stylesheet" href="assets/css/plugins/buttons.bootstrap5.css">
    <link rel="stylesheet" href="assets/css/plugins/jquery.dataTables.min.css">
    <link rel="stylesheet" href="assets/css/plugins/select2.min.css">
    <link rel="stylesheet" href="assets/css/plugins/jquery.timepicker.css">
    <link rel="stylesheet" href="assets/css/plugins/tagify.css">
    <link rel="stylesheet" href="assets/css/plugins/flatpickr.min.css">
    <link rel="stylesheet" href="assets/css/plugins/jquery-ui.css">
    <link rel="stylesheet" href="assets/css/plugins/fullcalendar.min.css">
    <link rel="stylesheet" href="assets/css/plugins/ion.rangeSlider.min.css">
    <link rel="stylesheet" href="assets/css/plugins/simplebar.css">
    <link rel="stylesheet" href="assets/css/plugins/waves.min.css">
    <link rel="stylesheet" href="assets/css/plugins/nano.min.css">

    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --login-green: #0D3D2D;
            --login-green-dark: #082d21;
            --login-mint: #9fd5bd;
            --login-ink: #111715;
            --login-muted: #7d8582;
            --login-line: #d9dfdc;
            --login-gold: #FEE685;
        }

        body.login-page {
            min-height: 100vh;
            margin: 0;
            background: #f8f8f6;
            color: var(--login-ink);
            font-family: 'DM Sans', sans-serif;
        }

        .login-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(420px, 1.04fr) minmax(480px, 0.96fr);
        }

        .login-brand {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100vh;
            padding: 56px 9vw 56px 5vw;
            background: var(--login-green);
            color: #fff;
        }

        .login-brand::before,
        .login-brand::after {
            content: '';
            position: absolute;
            z-index: -1;
            width: 390px;
            height: 700px;
            right: -70px;
            bottom: -120px;
            border: 1px solid rgba(159, 213, 189, .22);
            transform: skewY(-30deg) rotate(22deg);
        }

        .login-brand::after {
            right: 90px;
            bottom: -150px;
            opacity: .55;
        }

        .login-brand::before {
            width: 265px;
            height: 420px;
            right: 76px;
            bottom: -22px;
            border-color: rgba(159, 213, 189, .2);
            transform: skewX(-27deg) rotate(0deg);
            clip-path: polygon(0 18%, 50% 0, 100% 18%, 100% 100%, 0 100%);
            background:
                linear-gradient(90deg, transparent 47%, rgba(159, 213, 189, .18) 47.4%, transparent 48%),
                linear-gradient(0deg, transparent 19%, rgba(159, 213, 189, .18) 19.4%, transparent 20%),
                repeating-linear-gradient(90deg, transparent 0 49px, rgba(159, 213, 189, .12) 50px 51px),
                repeating-linear-gradient(0deg, transparent 0 48px, rgba(159, 213, 189, .12) 49px 50px);
        }

        .login-brand::after {
            width: 205px;
            height: 330px;
            right: 250px;
            bottom: -40px;
            border-color: rgba(159, 213, 189, .13);
            transform: skewX(-27deg) rotate(0deg);
            clip-path: polygon(0 20%, 50% 0, 100% 20%, 100% 100%, 0 100%);
            background:
                linear-gradient(90deg, transparent 47%, rgba(159, 213, 189, .14) 47.4%, transparent 48%),
                repeating-linear-gradient(90deg, transparent 0 39px, rgba(159, 213, 189, .1) 40px 41px),
                repeating-linear-gradient(0deg, transparent 0 42px, rgba(159, 213, 189, .1) 43px 44px);
        }

        .login-brand-logo {
            width: min(290px, 72%);
            height: auto;
            object-fit: contain;
            object-position: left center;
        }

        .login-brand-copy {
            max-width: 590px;
            margin-top: auto;
            margin-bottom: 18vh;
        }

        .login-eyebrow {
            margin: 0 0 22px;
            color: var(--login-gold);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .login-brand h1 {
            max-width: 570px;
            margin: 0;
            color: #fff;
            font-family: 'Manrope', sans-serif;
            font-size: clamp(2.35rem, 3.35vw, 3.7rem);
            font-weight: 700;
            line-height: 1.08;
            letter-spacing: 0;
        }

        .login-brand-text {
            max-width: 470px;
            margin: 24px 0 0;
            color: rgba(255, 255, 255, .78);
            font-size: 15px;
            line-height: 1.65;
        }

        .login-brand-footer {
            display: flex;
            align-items: center;
            gap: 14px;
            color: rgba(255, 255, 255, .84);
            font-size: 12px;
        }

        .login-brand-footer::before {
            content: '';
            width: 48px;
            height: 2px;
            background: var(--login-gold);
        }

        .login-form-panel {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 48px 7vw;
            background: #fafaf8;
        }

        .login-theme-toggle {
            position: absolute;
            top: 30px;
            right: 34px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 13px;
            border: 1px solid #d8ddda;
            border-radius: 30px;
            background: #fff;
            color: #89908d;
            cursor: pointer;
        }

        .login-form-wrap {
            width: min(100%, 430px);
        }

        .login-form-wrap .login-kicker {
            display: block;
            margin: 0 0 18px;
            padding: 0;
            background: transparent;
            color: #c39b43;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .login-form-wrap h2 {
            margin: 0;
            color: var(--login-ink);
            font-family: 'Manrope', sans-serif;
            font-size: clamp(2.2rem, 3.3vw, 3.25rem);
            font-weight: 800;
            line-height: 1.08;
        }

        .login-form-wrap h2::after {
            display: none;
        }

        .login-subtitle {
            margin: 16px 0 42px;
            color: var(--login-muted);
            font-size: 15px;
        }

        .login-alert {
            margin: 0 0 22px;
            padding: 12px 14px;
            border-left: 3px solid #d45d5d;
            background: #fff0ef;
            color: #a33b3b;
            font-size: 13px;
        }

        .login-field {
            margin-bottom: 25px;
        }

        .login-field label {
            display: block;
            margin-bottom: 9px;
            color: #313936;
            font-size: 14px;
            font-weight: 700;
        }

        .login-input-wrap {
            position: relative;
        }

        .login-input-wrap input {
            width: 100%;
            height: 54px;
            padding: 0 48px 0 17px;
            border: 1px solid var(--login-line);
            border-radius: 5px;
            background: #fff;
            color: var(--login-ink);
            font: inherit;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .login-input-wrap input:focus {
            border-color: var(--login-green);
            box-shadow: 0 0 0 3px rgba(0, 99, 79, .1);
        }

        .login-input-icon,
        .login-password-toggle {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa19e;
        }

        .login-input-icon { left: 17px; }
        .login-input-wrap.has-icon input { padding-left: 48px; }
        .login-password-toggle {
            right: 17px;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
            z-index: 10;
        }

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 7px 0 29px;
            color: var(--login-muted);
            font-size: 13px;
        }

        .login-options a {
            color: #16876c;
            font-weight: 700;
            text-decoration: none;
        }

        .login-submit {
            width: 100%;
            min-height: 54px;
            border: 0;
            border-radius: 5px;
            background: var(--login-green);
            color: #fff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s;
        }

        .login-submit:hover { background: var(--login-green-dark); transform: translateY(-1px); }

        .login-help {
            margin: 28px 0 0;
            color: #8d9591;
            font-size: 13px;
            text-align: center;
        }

        .login-help a { color: #16876c; font-weight: 700; text-decoration: none; }

        .login-copyright {
            position: absolute;
            bottom: 29px;
            left: 0;
            right: 0;
            color: #a0a6a3;
            font-size: 12px;
            text-align: center;
        }

        body.login-night .login-form-panel {
            background: #18201d;
        }

        body.login-night .login-form-wrap h2,
        body.login-night .login-field label {
            color: #f4f7f5;
        }

        body.login-night .login-subtitle,
        body.login-night .login-help,
        body.login-night .login-copyright {
            color: #aab8b2;
        }

        body.login-night .login-input-wrap input {
            border-color: #3d4d46;
            background: #202b27;
            color: #f4f7f5;
        }

        body.login-night .login-theme-toggle {
            border-color: #3d4d46;
            background: #202b27;
        }

        @media (max-width: 900px) {
            .login-shell {
                display: block;
                min-height: 100svh;
            }

            .login-brand {
                min-height: 410px;
                padding: 34px 8vw 28px;
            }

            .login-brand-copy {
                margin: auto 0 32px;
            }

            .login-brand h1 { font-size: 2.35rem; }
            .login-brand-text { display: none; }
            .login-brand-footer { margin-top: 0; }

            .login-form-panel {
                min-height: 0;
                display: flex;
                flex-direction: column;
                align-items: stretch;
                padding: 78px 8vw 82px;
            }

            .login-form-wrap {
                width: 100%;
                max-width: 480px;
                margin: 0 auto;
            }

            .login-copyright {
                position: static;
                width: 100%;
                margin-top: 46px;
            }
        }

        @media (max-width: 520px) {
            .login-brand {
                min-height: 350px;
                padding: 28px 24px 25px;
            }

            .login-brand-logo { width: 210px; }
            .login-brand-copy { margin-bottom: 27px; }
            .login-eyebrow { margin-bottom: 16px; font-size: 10px; letter-spacing: .12em; }
            .login-brand h1 { max-width: 350px; font-size: 1.86rem; line-height: 1.12; }
            .login-brand-footer { gap: 9px; font-size: 11px; white-space: nowrap; }
            .login-brand-footer::before { width: 38px; }

            .login-form-panel {
                padding: 68px 24px 58px;
            }

            .login-theme-toggle {
                position: static;
                align-self: flex-end;
                margin: -46px 0 36px;
            }
            .login-form-wrap .login-kicker { margin-bottom: 15px; font-size: 9px; }
            .login-form-wrap h2 { font-size: 2.05rem; }
            .login-subtitle { margin: 15px 0 36px; font-size: 14px; }
            .login-field { margin-bottom: 22px; }
            .login-input-wrap input { width: 100%; height: 52px; font-size: 14px; }
            .login-options { margin: 5px 0 26px; }
            .login-submit { min-height: 52px; }
        }

        @media (max-width: 360px) {
            .login-brand { min-height: 330px; }
            .login-brand-logo { width: 185px; }
            .login-brand h1 { font-size: 1.7rem; }
            .login-brand-footer { font-size: 10px; }
            .login-form-panel { padding-inline: 20px; }
        }
    </style>
</head>


<body class="login-page">
    <main class="login-shell">
        <section class="login-brand" aria-label="The Premium Homes Ltd. brand">
            <img class="login-brand-logo" src="assets/images/logo/logo_white.png" alt="The Premium Homes Ltd.">
            <div class="login-brand-copy">
                <p class="login-eyebrow">Our digital transformation</p>
                <h1>Every login is a step toward working smarter.</h1>
                <p class="login-brand-text">We know change takes effort. Together, we are turning manual work into faster, simpler and more transparent operations.</p>
            </div>
            <div class="login-brand-footer">One Team <span>•</span> One System <span>•</span> One Better Future</div>
        </section>

        <section class="login-form-panel">
            <button class="login-theme-toggle" type="button" id="loginThemeToggle" aria-label="Toggle theme">
                <i class="fa-light fa-sun"></i><i class="fa-light fa-moon"></i>
            </button>
            <div class="login-form-wrap">
                <p class="login-kicker">TPHL enterprise resource planning</p>
                <h2>Welcome back</h2>
                <p class="login-subtitle">Continue building a smarter way of working.</p>

                <?php if (!empty($message)): ?>
                    <div class="login-alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= $GLOBALS['base_url'] ?>/admin-login">
                    <div class="login-field">
                        <label for="nameEmail">Email address</label>
                        <div class="login-input-wrap has-icon">
                            <i class="login-input-icon fa-light fa-envelope"></i>
                            <input name="email" id="nameEmail" type="email" placeholder="name@company.com" autocomplete="email" required>
                        </div>
                    </div>
                    <div class="login-field">
                        <label for="passwordInput">Password</label>
                        <div class="login-input-wrap has-icon">
                            <i class="login-input-icon fa-light fa-lock"></i>
                            <input type="password" name="password" id="passwordInput" placeholder="Enter your password" autocomplete="current-password" required>
                            <button class="login-password-toggle" type="button" id="loginPasswordToggle" aria-label="Show password"><i class="fa-light fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="login-options">
                        <label><input type="checkbox" id="remember-me"> <span>Remember me</span></label>
                        <a href="auth-forgot-password-basic.html">Forgot password?</a>
                    </div>
                    <button class="login-submit" type="submit">Sign in</button>
                </form>
                <p class="login-help">Need help? <a href="mailto:tech@dpremiumhomes.com">Contact IT Support</a></p>
            </div>
            <div class="login-copyright">© <?= date('Y') ?> The Premium Homes Ltd.</div>
        </section>
    </main>


    <!-- Back to top start -->
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
    </div>
    <!-- Back to top end -->

    <!-- JS here -->
    <script src="assets/js/vendor/jquery-3.7.0.js"></script>
    <script src="assets/js/vendor/isotope.pkgd.js"></script>
    <script src="assets/js/vendor/bootstrap.bundle.min.js"></script>
    <script src="assets/js/vendor/magnific-popup.min.js"></script>
    <script src="assets/js/vendor/ajax-form.js"></script>
    <script src="assets/js/vendor/jquery.repeater.js"></script>
    <script src="assets/js/plugins/waypoints.min.js"></script>
    <script src="assets/js/plugins/dayjs.min.js"></script>
    <script src="assets/js/plugins/loader.js"></script>
    <script src="assets/js/plugins/jsvectormap.min.js"></script>
    <script src="assets/js/plugins/world-merc.js"></script>
    <script src="assets/js/plugins/swiper-bundle.min.js"></script>
    <script src="assets/js/plugins/popper.min.js"></script>
    <script src="assets/js/plugins/simplebar.min.js"></script>
    <script src="assets/js/plugins/simplebar-active.js"></script>
    <script src="assets/js/plugins/backtotop.js"></script>
    <script src="assets/js/plugins/smooth-scrollbar.js"></script>
    <script src="assets/js/plugins/cleave.min.js"></script>
    <script src="assets/js/plugins/datatables.min.js"></script>
    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/plugins/dataTables.buttons.min.js"></script>
    <script src="assets/js/plugins/jszip.min.js"></script>
    <script src="assets/js/plugins/pdfmake.min.js"></script>
    <script src="assets/js/plugins/vfs_fonts.js"></script>
    <script src="assets/js/plugins/buttons.html5.min.js"></script>
    <script src="assets/js/plugins/buttons.print.min.js"></script>
    <script src="assets/js/plugins/buttons.colVis.min.js"></script>
    <script src="assets/js/plugins/steps-form.js"></script>
    <script src="assets/js/plugins/dropify.min.js"></script>
    <script src="assets/js/plugins/dropzone.js"></script>
    <script src="assets/js/plugins/tinymce.min.js"></script>
    <script src="assets/js/plugins/custom.js"></script>
    <script src="assets/js/plugins/typeahead.bundle.min.js"></script>
    <script src="assets/js/plugins/bloodhound.js"></script>
    <script src="assets/js/plugins/select2.full.min.js"></script>
    <script src="assets/js/plugins/jquery.timepicker.min.js"></script>
    <script src="assets/js/plugins/flatpickr.js"></script>
    <script src="assets/js/plugins/tagify.js"></script>
    <script src="assets/js/plugins/jquery-ui.min.js"></script>
    <script src="assets/js/plugins/sweetalert2.all.min.js"></script>
    <script src="assets/js/plugins/apexcharts.min.js"></script>
    <script src="assets/js/plugins/fullcalendar.min.js"></script>
    <script src="assets/js/plugins/ion.rangeSlider.min.js"></script>
    <script src="assets/js/vendor/custom-tagify.js"></script>
    <script src="assets/js/vendor/height-equal.js"></script>
    <script src="assets/js/vendor/custom-chart.js"></script>
    <script src="assets/js/vendor/rangeslider-script.js"></script>
    <script src="assets/js/vendor/jquery.barrating.js"></script>
    <script src="assets/js/vendor/rating-script.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/vendor/sidebar.js"></script>
    <script>
        (function () {
            var passwordInput = document.getElementById('passwordInput');
            var passwordToggle = document.getElementById('loginPasswordToggle');
            var themeToggle = document.getElementById('loginThemeToggle');

            if (passwordInput && passwordToggle) {
                passwordToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    if (type === 'text') {
                        passwordToggle.innerHTML = '<i class="fa-light fa-eye-slash"></i>';
                        passwordToggle.setAttribute('aria-label', 'Hide password');
                    } else {
                        passwordToggle.innerHTML = '<i class="fa-light fa-eye"></i>';
                        passwordToggle.setAttribute('aria-label', 'Show password');
                    }
                });
            }

            if (themeToggle) {
                themeToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.body.classList.toggle('login-night');
                });
            }
        }());
    </script>

</body>

</html>