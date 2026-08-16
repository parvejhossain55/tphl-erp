<?php 
// This file acts as the universal print pad layout.
// It relies on $siteSettings being available in the context (from master.php).
?>
<div class="universal-print-pad d-none">
    
    <!-- Left Border -->
    <div class="upp-left-border"></div>
    
    <!-- Right Border -->
    <div class="upp-right-border"></div>

    <!-- Background Watermark -->
    <div class="upp-watermark">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 600" preserveAspectRatio="xMidYMax meet">
            <g stroke="#cbd5e1" stroke-width="2" fill="none" opacity="0.4">
                <!-- Left Building -->
                <path d="M 0 600 L 0 350 L 150 250 L 150 600" />
                <path d="M 150 250 L 300 350 L 300 600" />
                <path d="M 0 380 L 150 280 L 300 380" />
                <path d="M 0 410 L 150 310 L 300 410" />
                <path d="M 0 440 L 150 340 L 300 440" />
                <path d="M 0 470 L 150 370 L 300 470" />
                <path d="M 0 500 L 150 400 L 300 500" />
                <path d="M 0 530 L 150 430 L 300 530" />
                <path d="M 0 560 L 150 460 L 300 560" />
                
                <path d="M 50 315 L 50 600" />
                <path d="M 100 280 L 100 600" />
                <path d="M 200 280 L 200 600" />
                <path d="M 250 315 L 250 600" />
                
                <!-- Middle Buildings -->
                <path d="M 400 600 L 400 480 L 450 480 L 450 600" />
                <path d="M 400 500 L 450 500" />
                <path d="M 400 520 L 450 520" />
                <path d="M 400 540 L 450 540" />
                <path d="M 400 560 L 450 560" />
                <path d="M 400 580 L 450 580" />

                <path d="M 470 600 L 470 420 L 550 420 L 550 600" />
                <path d="M 470 440 L 550 440" />
                <path d="M 470 460 L 550 460" />
                <path d="M 470 480 L 550 480" />
                <path d="M 470 500 L 550 500" />
                <path d="M 470 520 L 550 520" />
                <path d="M 470 540 L 550 540" />
                <path d="M 470 560 L 550 560" />
                <path d="M 470 580 L 550 580" />
                <path d="M 510 420 L 510 600" />

                <!-- Right Building -->
                <path d="M 650 600 L 650 350 L 750 200 L 750 600" />
                <path d="M 750 200 L 880 320 L 880 600" />
                <path d="M 880 320 L 980 400 L 980 600" />
                
                <path d="M 650 390 L 750 240 L 880 360 L 980 440" />
                <path d="M 650 430 L 750 280 L 880 400 L 980 480" />
                <path d="M 650 470 L 750 320 L 880 440 L 980 520" />
                <path d="M 650 510 L 750 360 L 880 480 L 980 560" />
                <path d="M 650 550 L 750 400 L 880 520 L 980 600" />
                <path d="M 650 590 L 750 440 L 880 560" />

                <path d="M 700 275 L 700 600" />
                <path d="M 800 245 L 800 600" />
                <path d="M 840 280 L 840 600" />
                <path d="M 930 360 L 930 600" />
            </g>
        </svg>
    </div>

    <!-- Header -->
    <div class="upp-header">
        <div class="text-center">
            <?php 
                $logoUrl = isset($siteSettings['white_logo']) && !empty($siteSettings['white_logo']) 
                           ? $GLOBALS['base_url'] . '/public/uploads/settings/' . $siteSettings['white_logo'] 
                           : $GLOBALS['base_url'] . '/public/assets/images/logo.png';
            ?>
            <img src="<?= $logoUrl ?>" alt="Logo">
        </div>
    </div>

    <!-- Footer -->
    <div class="upp-footer">
        <div class="upp-footer-content">
            <div class="upp-col">
                <div class="upp-col-header">
                    <span>CORPORATE OFFICE</span>
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="upp-col-body">
                    <?= nl2br(htmlspecialchars($siteSettings['address'] ?? '23/2, SEL HUQ SKY PARK, LEVEL-4, SHYMOLI, DHAKA.')) ?><br>
                    <?= htmlspecialchars($siteSettings['phone'] ?? '+880 1740 362 000') ?>
                </div>
            </div>
            
            <div class="upp-col">
                <div class="upp-col-header">
                    <span>SITE OFFICE</span>
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="upp-col-body">
                    2 & 3RD FLOOR, TOKIO PLAZA,<br>
                    KHAGAN BAZAR, ASHULIA, DHAKA.<br>
                    +880 1767 490 372
                </div>
            </div>

            <div class="upp-col upp-col-contact">
                <div class="contact-item">
                    <i class="fa-solid fa-globe"></i>
                    <span><?= htmlspecialchars($siteSettings['website_url'] ?? 'www.dpremiumhomes.com') ?></span>
                </div>
                <div class="contact-item">
                    <i class="fa-regular fa-envelope"></i>
                    <span><?= htmlspecialchars($siteSettings['email'] ?? 'thepremiumhomesltd@gmail.com') ?></span>
                </div>
                <div class="contact-item">
                    <i class="fa-solid fa-mobile-screen"></i>
                    <span><?= htmlspecialchars($siteSettings['alt_phone'] ?? '+880 1767 490 372') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    /* Hide specific non-print elements globally across all pages when this is active */
    .app-sidebar, .app-header, .page-header, .no-print, .main-footer, .btn-print, 
    [class*="theme"], [class*="mode-switch"], button.theme-toggle, .floating-btn, .dark-mode-toggler,
    .customizer, .settings-panel, .color-customizer, .theme-setting-wrapper {
        display: none !important;
    }

    /* Except for our print pad elements which might accidentally contain 'theme' in a class name */
    .universal-print-pad * {
        display: block;
    }

    /* Additional aggressive hide for the moon icon/floating settings */
    .setting-box, .theme-option, .color-scheme-toggle, .layout-setting, .floating-settings, [class*="moon"] {
        display: none !important;
    }

    /* Force background colors to print only on necessary elements to improve preview performance */
    body, .universal-print-pad, .upp-header, .upp-footer, .upp-left-border, .upp-right-border, .badge, .status-badge, .bg-light, .bg-gradient-primary, .bg-gradient-success {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    /* Setup Page Layout */
    @page {
        margin: 0;
        size: A4 portrait;
    }

    html, body {
        height: auto !important;
        min-height: 100% !important;
        overflow: visible !important;
        margin: 0 !important;
        padding: 0 !important;
        position: relative;
    }

    /* Set main content area to avoid overlapping header and footer */
    .app__slide-wrapper, .main-content {
        position: static !important;
        height: auto !important;
        min-height: 100% !important;
        overflow: visible !important;
        margin-left: 0 !important;
        margin-top: 150px !important; /* Space for Header */
        margin-bottom: 120px !important; /* Space for Footer */
        padding: 0 25px 0 40px !important; /* More padding on left to accommodate wide border */
        width: 100% !important;
        max-width: 100% !important;
        background: transparent !important;
        box-sizing: border-box !important;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
    
    .table-modern, .table {
        width: 100% !important;
        table-layout: auto !important;
        word-wrap: break-word !important;
    }

    .table-modern td, .table-modern th, .table td, .table th {
        white-space: normal !important;
        word-break: break-word !important;
    }

    /* Ensure card borders and shadows don't look ugly on print */
    .card {
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
        width: 100% !important;
    }

    /* The Pad Container */
    .universal-print-pad {
        display: block !important;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 100vh !important;
        z-index: -999; /* Stay behind the actual report content */
        pointer-events: none;
        background-color: transparent;
    }

    /* Left Border Line (Wide Dark Green) */
    .upp-left-border {
        position: absolute;
        top: 0;
        left: 0;
        width: 25px;
        height: 100vh !important;
        background-color: #11502e !important;
    }

    /* Right Gradient/Light Border */
    .upp-right-border {
        position: absolute;
        top: 0;
        right: 0;
        width: 20px;
        height: 100vh !important;
        background: rgba(174, 212, 187, 0.2) !important;
        background: linear-gradient(to right, rgba(174, 212, 187, 0) 0%, rgba(174, 212, 187, 0.5) 100%) !important;
    }

    /* Header */
    .upp-header {
        position: absolute;
        top: 0;
        left: 25px; /* Offset for wide left border */
        right: 0;
        height: 130px;
        background-color: white !important;
        border-bottom: 5px solid #11502e !important; /* 5px solid green line matching pad */
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    
    .upp-header img {
        max-height: 90px;
        max-width: 380px;
        object-fit: contain;
    }

    /* Footer */
    .upp-footer {
        position: absolute;
        bottom: 0;
        left: 25px;
        right: 0;
        height: 100px;
        background-color: #11502e !important; /* Dark Green matching left border */
        color: white !important;
        padding: 15px 30px !important;
        font-family: Arial, sans-serif !important;
        display: block !important;
    }

    .upp-footer-content {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        height: 100% !important;
    }

    .upp-col {
        flex: 1;
        font-size: 10px !important;
        line-height: 1.4 !important;
        display: block !important;
        color: white !important;
    }

    .upp-col-header {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-weight: bold !important;
        font-size: 11px !important;
        margin-bottom: 5px !important;
        border-bottom: 1px solid rgba(255,255,255,0.3) !important;
        padding-bottom: 4px !important;
    }

    .upp-col-header i {
        font-size: 14px !important;
        color: white !important;
    }

    .upp-col-body {
        opacity: 0.9 !important;
        color: white !important;
    }

    .upp-col-contact {
        display: flex !important;
        flex-direction: column !important;
        gap: 6px !important;
        border-left: 2px solid white !important;
        padding-left: 20px !important;
        flex: 0.8 !important;
    }

    .contact-item {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        color: white !important;
    }

    .contact-item i {
        width: 12px;
        text-align: center;
        font-size: 14px !important;
        color: white !important;
    }

    .contact-item span {
        color: white !important;
    }

    /* Watermark */
    .upp-watermark {
        position: absolute;
        bottom: 100px; /* Right above footer */
        left: 25px;
        right: 0;
        height: 500px;
        z-index: -1;
        overflow: hidden !important;
        display: flex !important;
        align-items: flex-end !important;
    }
    
    .upp-watermark svg {
        width: 100%;
        height: auto;
        opacity: 0.7 !important;
    }
}
</style>
