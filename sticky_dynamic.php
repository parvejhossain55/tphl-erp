<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// 1. Remove FixedHeader CDNs
$content = preg_replace('/<link rel="stylesheet" href="[^"]*fixedHeader[^"]*">\s*/is', '', $content);
$content = preg_replace('/<script src="[^"]*fixedHeader[^"]*"><\/script>\s*/is', '', $content);

// 2. Remove fixedHeader property from DataTable initialization
$content = preg_replace('/fixedHeader:\s*\{.*?\},/is', '', $content);

// 3. Inject dynamic Sticky CSS logic immediately after DataTable initialization
$scriptToInject = "
            // Dynamic Sticky Header Calculation
            setTimeout(function() {
                var nav = document.querySelector('.header') || document.querySelector('.navbar') || document.querySelector('header') || document.getElementById('header');
                var navHeight = nav ? nav.offsetHeight : 60;
                
                var style = document.createElement('style');
                style.innerHTML = `
                    .dataTables_scrollHead {
                        position: sticky !important;
                        top: ${navHeight}px !important;
                        z-index: 1020 !important;
                        background-color: #fff !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                    }
                    .dataTables_scrollHead table {
                        background-color: #fff !important;
                    }
                `;
                document.head.appendChild(style);
                
                // Force all wrappers to allow sticky inheritance
                var parent = document.querySelector('.dataTables_scrollHead');
                while(parent && parent.tagName !== 'BODY') {
                    if (parent.classList.contains('card-body') || parent.classList.contains('card') || parent.classList.contains('table__wrapper')) {
                        parent.style.overflow = 'visible';
                    }
                    parent = parent.parentElement;
                }
            }, 300);
";

// Insert right after var table = $('#workspaceTable').DataTable({ ... }); 
// To be safe, I'll insert it right before the calculateFooterTotals function definition
$searchBlock = "function calculateFooterTotals() {";
$content = str_replace($searchBlock, $scriptToInject . "\n    " . $searchBlock, $content);

file_put_contents($file, $content);
echo "Dynamic CSS Sticky Header deployed.";
