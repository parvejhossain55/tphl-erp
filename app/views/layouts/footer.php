        </div> <!-- page__body-wrapper end -->
        </div> <!-- page__full-wrapper end -->

        <!-- Back to top -->
        <div class="progress-wrap">
            <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
                <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
            </svg>
        </div>


        <!-- SweetAlert2 loaded locally below -->
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <script>
                window.addEventListener('load', () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: <?= json_encode($_SESSION['flash_success']); ?>,
                        showConfirmButton: false,
                        timer: 20000,
                        timerProgressBar: true,
                        backdrop: false,
                        customClass: {
                            popup: 'swal2-toast'
                        },
                        didOpen: (toast) => {
                            const icon = toast.querySelector('.swal2-icon');
                            if (icon) {
                                icon.style.cursor = 'pointer';
                                icon.querySelectorAll('*').forEach(el => el.style.cursor = 'pointer');
                            }
                            toast.addEventListener('click', (e) => {
                                if (e.target.closest('.swal2-icon')) {
                                    Swal.close();
                                }
                            });
                        }
                    });
                });
            </script>
        <?php unset($_SESSION['flash_success']);
        endif; ?>


        <?php if (!empty($_SESSION['flash_error'])): ?>
            <script>
                window.addEventListener('load', () => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: <?= json_encode($_SESSION['flash_error']); ?>,
                        showConfirmButton: false,
                        timer: 20000,
                        width: '320px',
                        backdrop: false,
                        didOpen: (toast) => {
                            const icon = toast.querySelector('.swal2-icon');
                            if (icon) {
                                icon.style.cursor = 'pointer';
                                icon.querySelectorAll('*').forEach(el => el.style.cursor = 'pointer');
                            }
                            toast.addEventListener('click', (e) => {
                                if (e.target.closest('.swal2-icon')) {
                                    Swal.close();
                                }
                            });
                        }
                    });
                });
            </script>
        <?php unset($_SESSION['flash_error']);
        endif; ?>
        <!-- JS -->
        <!-- Footer Scripts -->

        <!-- ✅ base_url: set only if not already defined by page (avoids conflict with const base_url in views) -->
        <script>
            window.base_url = window.base_url || "<?= $GLOBALS['base_url'] ?>";
        </script>

        <!-- ✅ jQuery FIRST (MOST IMPORTANT) -->
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/jquery-3.7.0.js"></script>

        <!-- ✅ Core JS -->
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/bootstrap.bundle.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/popper.min.js"></script>

        <!-- ✅ Select2 (RIGHT AFTER jQuery) -->
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/select2.full.min.js"></script>

        <!-- ✅ Other plugins -->
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/calendar-activision.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/isotope.pkgd.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/magnific-popup.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/ajax-form.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/jquery.repeater.js"></script>

        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/waypoints.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/dayjs.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/loader.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/jsvectormap.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/world-merc.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/swiper-bundle.min.js"></script>

        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/simplebar.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/simplebar-active.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/backtotop.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/smooth-scrollbar.js"></script>

        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/cleave.min.js"></script>

        <!-- ✅ Datatable -->
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/datatables.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/jquery.dataTables.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/dataTables.bootstrap5.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/dataTables.buttons.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/jszip.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/pdfmake.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/vfs_fonts.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/buttons.html5.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/buttons.print.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/buttons.colVis.min.js"></script>

        <!-- ✅ Forms & UI -->
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/steps-form.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/dropify.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/dropzone.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/tinymce.min.js"></script>

        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/typeahead.bundle.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/bloodhound.js"></script>

        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/jquery.timepicker.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/flatpickr.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/tagify.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/jquery-ui.min.js"></script>

        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/sweetalert2.all.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/apexcharts.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/fullcalendar.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/ion.rangeSlider.min.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>
        <!-- ✅ Custom scripts LAST -->
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/custom.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/custom-tagify.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/height-equal.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/custom-chart.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/rangeslider-script.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/jquery.barrating.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/rating-script.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/main.js?v=1.0.1"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/sidebar.js?v=<?= defined('BASE_PATH') ? @filemtime(BASE_PATH . '/public/assets/js/vendor/sidebar.js') : '2.0.1' ?>"></script>

        <!-- ✅ Global Delete Handler & Client-Side Error Logging -->
        <script>
        (function() {
            var baseUrl = (typeof base_url !== 'undefined' ? base_url : '') || window.base_url || '/TPHL-ERP';

            // Core Log Function
            function sendClientLog(type, message, details) {
                fetch(baseUrl + '/log-client-debug', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        url: window.location.href,
                        type: type,
                        message: message,
                        details: details
                    })
                }).catch(function(err) {
                    console.warn('Failed to send debug log to server:', err);
                });
            }

            // 1. Capture Global JS Errors
            window.addEventListener('error', function(e) {
                sendClientLog('js-error', e.message || 'Unhandled JS Error', {
                    filename: e.filename || '',
                    lineno: e.lineno || 0,
                    colno: e.colno || 0,
                    stack: e.error ? e.error.stack : null
                });
            });

            window.addEventListener('unhandledrejection', function(e) {
                sendClientLog('js-promise-rejection', e.reason ? (e.reason.message || String(e.reason)) : 'Unhandled rejection', {
                    stack: e.reason ? e.reason.stack : null
                });
            });

            // 2. Capture Phase Click Listener (Intercept click immediately before it can be stopped by other scripts)
            window.addEventListener('click', function(e) {
                var btn = e.target.closest('.removeBtnCust');
                if (!btn) return;

                // Intercept immediately
                e.preventDefault();
                e.stopPropagation();

                var itemId   = btn.getAttribute('data-id');
                var itemName = btn.getAttribute('data-name') || 'this item';
                var action   = btn.getAttribute('data-action');

                sendClientLog('click-capture', 'Delete button clicked (Intercepted in Capture Phase)', {
                    itemId: itemId,
                    itemName: itemName,
                    action: action,
                    classes: btn.className,
                    tagName: btn.tagName,
                    swalLoaded: typeof Swal !== 'undefined',
                    jqueryLoaded: typeof $ !== 'undefined'
                });

                if (!action) {
                    sendClientLog('error-delete', 'Deletion failed: data-action attribute is missing on the button!', {
                        itemId: itemId,
                        itemName: itemName
                    });
                    return;
                }

                // Ensure Swal is loaded
                if (typeof Swal === 'undefined') {
                    var errMsg = 'SweetAlert2 (Swal) is undefined! Cannot open delete modal.';
                    console.error(errMsg);
                    sendClientLog('error-delete', errMsg, {
                        itemId: itemId,
                        action: action
                    });
                    alert('Error: SweetAlert2 is not loaded. Cannot delete.');
                    return;
                }

                sendClientLog('info-delete', 'Triggering Swal.fire confirmation modal (Capture Phase)', {
                    itemId: itemId,
                    itemName: itemName,
                    action: action
                });

                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You are about to delete "' + itemName + '". This action cannot be undone!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        sendClientLog('info-delete', 'Deletion confirmed by user. Submitting form...', {
                            itemId: itemId,
                            action: action,
                            postUrl: baseUrl + '/' + action
                        });

                        // Special AJAX handling for DM executive dashboard delete
                        // to preserve current page position
                        if (action === 'delete-lead-permanent') {
                            var fd = new FormData();
                            fd.append('id',   itemId);
                            fd.append('ajax', '1');
                            var curPage = (typeof dmCurrentPage !== 'undefined') ? dmCurrentPage : 1;
                            fd.append('redirect_page', curPage);
                            fetch(baseUrl + '/' + action, { method: 'POST', body: fd })
                                .then(function(r) { return r.json(); })
                                .then(function(data) {
                                    if (data.ok && typeof dmLoadLeads === 'function') {
                                        dmLoadLeads(curPage);
                                    } else if (!data.ok) {
                                        alert(data.message || 'Delete failed.');
                                    }
                                })
                                .catch(function() { alert('Delete failed. Please try again.'); });
                            return;
                        }

                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = baseUrl + '/' + action;
                        var input = document.createElement('input');
                        input.type  = 'hidden';
                        input.name  = 'id';
                        input.value = itemId;
                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    } else {
                        sendClientLog('info-delete', 'Deletion cancelled by user', {
                            itemId: itemId
                        });
                    }
                }).catch(function(swalErr) {
                    sendClientLog('error-delete', 'Swal.fire encountered an error: ' + swalErr.message, {
                        stack: swalErr.stack
                    });
                });
            }, true); // true = capture phase
        })();
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof $ !== 'undefined' && $.fn.selectpicker) {
                    $('.selectpicker').selectpicker();
                }
            });

            function startCountdown() {
                document.querySelectorAll("[id^='timer-']").forEach(function(el) {
                    const deadline = el.getAttribute("data-deadline");
                    if (!deadline) {
                        el.innerHTML = "No Deadline";
                        return;
                    }

                    const targetTime = new Date(deadline).getTime();

                    const timer = setInterval(function() {
                        const now = new Date().getTime();
                        const distance = targetTime - now;

                        if (distance <= 0) {
                            el.innerHTML = "Expired";
                            el.classList.remove("bg-danger");
                            el.classList.add("bg-secondary");
                            clearInterval(timer);
                            return;
                        }

                        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        el.innerHTML = days + "d " + hours + "h " + minutes + "m " + seconds + "s ";
                    }, 1000);
                });
            }
            startCountdown();
            $(document).ready(function() {
                // Date of Birth picker
                if (document.querySelector("#dateofBirth")) {
                    flatpickr("#dateofBirth", {
                        altInput: true,
                        altFormat: "F j, Y",
                        dateFormat: "Y-m-d",
                        defaultDate: "<?= !empty($employee['dob']) ? $employee['dob'] : '' ?>"
                    });
                }

                // Month picker for filter
                if (document.querySelector("#attendanceMonth")) {
                    $("#attendanceMonth").flatpickr({
                        plugins: [
                            new monthSelectPlugin({
                                shorthand: true, // Jan, Feb
                                dateFormat: "Y-m", // backend format
                                altFormat: "F Y" // display format
                            })
                        ]
                    });
                }

                // Punch In / Out time
                if (document.querySelector("#clockInTime")) {
                    $("#clockInTime").flatpickr({
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "H:i",
                        defaultDate: "09:00",
                        time_24hr: true
                    });
                }

                if (document.querySelector("#clockOutTime")) {
                    $("#clockOutTime").flatpickr({
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "H:i",
                        defaultDate: "18:00",
                        time_24hr: true
                    });
                }
            });
        </script>

        <!-- ✅ Select2 Init -->
        <script>
            function initSelect2(parent = document) {
    if (!$.fn.select2) return;

    $(parent).find('select.select2').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;

        var hasEmptyOption = $(this).find('option[value=""]').length > 0;
        var placeholder = $(this).data('placeholder');
        var modalParent = $(this).closest('.modal');

        var opts = {
            width: '100%',
            dropdownParent: modalParent.length ? modalParent : $('body')
        };
        if (placeholder || hasEmptyOption) {
            opts.placeholder = placeholder || 'Select option';
            opts.allowClear = hasEmptyOption;
        }

        $(this).select2(opts);
    });

    // ✅ Fix scroll-to-top for team assignment dropdowns
    $(parent).find('select.select2-no-scroll').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;

        var hasEmptyOption = $(this).find('option[value=""]').length > 0;
        var placeholder = $(this).data('placeholder');
        var modalParent = $(this).closest('.modal');

        var opts = {
            width: '100%',
            dropdownParent: modalParent.length ? modalParent : $('body'),
            scrollAfterSelect: false
        };
        if (placeholder || hasEmptyOption) {
            opts.placeholder = placeholder || 'Select option';
            opts.allowClear = hasEmptyOption;
        }

        $(this).select2(opts);
    });
}

// ✅ Global: Prevent Select2 from scrolling page to top when search input gets focus
$(document).on('select2:open', function () {
    var scrollY = $(window).scrollTop();
    var scrollX = $(window).scrollLeft();

    // Use setTimeout(0) so scroll restores AFTER browser processes the focus event
    setTimeout(function () {
        $(window).scrollTop(scrollY);
        $(window).scrollLeft(scrollX);
    }, 0);
});
         

            /**
             * Auto init on page load
             */
            $(document).ready(function() {
                initSelect2();
            });
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const labels = <?= json_encode($flatSalesLabels ?? []) ?>;
                const series = <?= json_encode($flatSalesSeries ?? []) ?>;

                const options = {
                    series: series,
                    chart: {
                        type: 'donut',
                        height: 260
                    },

                    labels: labels,

                    plotOptions: {
                        pie: {
                            donut: {
                                size: '68%',
                                labels: {
                                    show: true,

                                    name: {
                                        show: true,
                                        fontSize: '12px',
                                        offsetY: -6,
                                        color: '#63666bff'

                                    },

                                    value: {
                                        show: true,
                                        fontSize: '16px',
                                        fontWeight: 600,
                                        offsetY: 6,
                                        formatter: val => val + ' Flats'
                                    },

                                    total: {
                                        show: true,
                                        label: 'Total',
                                        fontSize: '13px',
                                        color: '#7c7f85ff',
                                        formatter: function(w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        }
                                    }
                                }
                            }
                        }
                    },

                    stroke: {
                        width: 2,
                        colors: ['#fff']
                    },

                    fill: {
                        type: 'gradient',
                        gradient: {
                            shade: 'light',
                            type: 'radial',
                            shadeIntensity: 0.4,
                            gradientToColors: [
                                '#9f00fb',
                                '#13684aff',
                                '#da950cff',
                                '#FF4560',
                                '#775DD0'
                            ],
                            opacityFrom: 1,
                            opacityTo: 1,
                            stops: [70, 100]
                        }
                    },

                    colors: [
                        'rgb(252, 72, 171)',
                        '#12976bff',
                        '#fecc6a',
                        '#ff8698',
                        '#a796e0'
                    ],

                    legend: {
                        position: 'right',
                        labels: {
                            colors: '#707174ff'
                        },
                        formatter: function(seriesName, opts) {
                            return `${seriesName} (${opts.w.globals.series[opts.seriesIndex]})`;
                        }
                    },

                    tooltip: {
                        y: {
                            formatter: val => val + ' Flats'
                        }
                    },

                    responsive: [{
                            breakpoint: 1200, // Laptop
                            options: {
                                dataLabels: {
                                    enabled: true //  hide %
                                },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '72%',
                                            labels: {
                                                name: {
                                                    fontSize: '11px'
                                                },
                                                value: {
                                                    fontSize: '14px'
                                                },
                                                total: {
                                                    fontSize: '12px'
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        },
                        {
                            breakpoint: 768, // Tablet & Mobile
                            options: {
                                dataLabels: {
                                    enabled: false // hide %
                                },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '78%',
                                            labels: {
                                                show: true //  hide center text
                                            }
                                        }
                                    }
                                },
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    ]

                };

                const pieChartEl = document.querySelector("#pieChart");
                if (pieChartEl) {
                    new ApexCharts(pieChartEl, options).render();
                }
            });
        </script>


        <!-- sales overview chart -->

        <script>
            document.addEventListener("DOMContentLoaded", function() {

                const salesData = {
                    week: {
                        labels: <?= json_encode(array_column($weeklySales  ?? [], 'date')) ?>,
                        totals: <?= json_encode(array_column($weeklySales  ?? [], 'total')) ?>
                    },
                    month: {
                        labels: <?= json_encode(array_column($monthlySales ?? [], 'date')) ?>,
                        totals: <?= json_encode(array_column($monthlySales ?? [], 'total')) ?>
                    },
                    year: {
                        labels: <?= json_encode(array_column($yearlySales  ?? [], 'month')) ?>,
                        totals: <?= json_encode(array_column($yearlySales  ?? [], 'total')) ?>
                    }
                };

                // 🔥 THIS PART YOU MISSED (Restricted to Booking Overview tab wrapper)
                const salesChartElForInit = document.querySelector("#salesChart");
                const activeBtn = salesChartElForInit ? salesChartElForInit.closest('.card__wrapper').querySelector('.nav-link.active') : null;
                const defaultPeriod = activeBtn ? activeBtn.dataset.period : 'week';

                const colors = ['#1a6e40ff', '#23bb95ff'];

                function getAlternatingColors(length) {
                    return Array.from({
                        length
                    }, (_, i) => colors[i % colors.length]);
                }

                let options = {
                    chart: {
                        type: 'bar',
                        height: 350,
                        toolbar: {
                            show: false
                        }
                    },
                    legend: {
                        show: false
                    },

                    plotOptions: {
                        bar: {
                            distributed: true,
                            borderRadius: 4,
                            columnWidth: '40%'
                        }
                    },

                    dataLabels: {
                        enabled: defaultPeriod !== 'month',
                        formatter: val => val.toLocaleString()
                    },

                    series: [{
                        name: 'Sales',
                        data: salesData[defaultPeriod].totals
                    }],

                    xaxis: {
                        categories: salesData[defaultPeriod].labels,
                        labels: {
                            style: {
                                colors: getAlternatingColors(salesData[defaultPeriod].labels.length),
                                fontSize: '12px',
                                fontWeight: 500
                            }
                        }
                    },

                    yaxis: {
                        labels: {
                            style: {
                                colors: '#1a6e40',
                                fontSize: '12px',
                                fontWeight: 700
                            }
                        }
                    },

                    colors: getAlternatingColors(salesData[defaultPeriod].totals.length),

                    tooltip: {
                        y: {
                            formatter: val => val.toLocaleString()
                        }
                    }
                };

                const salesChartEl = document.querySelector("#salesChart");
                if (salesChartEl) {
                    const chart = new ApexCharts(salesChartEl, options);
                    chart.render();

                    // Tabs click (Restricted to Booking Overview tabs)
                    const bookingTabs = salesChartEl.closest('.card__wrapper').querySelectorAll('.nav-link');
                    bookingTabs.forEach(button => {
                        button.addEventListener('click', function() {

                            bookingTabs.forEach(btn => btn.classList.remove('active'));
                            this.classList.add('active');

                            const period = this.dataset.period;

                            if (salesData[period]) {
                                chart.updateOptions({
                                    series: [{
                                        data: salesData[period].totals
                                    }],
                                    xaxis: {
                                        categories: salesData[period].labels
                                    },
                                    colors: getAlternatingColors(salesData[period].totals.length),
                                    dataLabels: {
                                        enabled: period !== 'month'
                                    }
                                });
                            }
                        });
                    });
                }

            });
        </script>




<?php 
// Role 20 (Site Operator) & Role 32 (Admin Operation) — view only, hide create/edit/delete UI
// Roles 1, 3 are admins with approval rights — do NOT hide Action columns
$readOnlyRoles = [20, 32];
if (isset($_SESSION['user']['role_id']) && in_array($_SESSION['user']['role_id'], $readOnlyRoles)): 
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $ !== 'undefined') {
            $('a[href*="-edit"], a[href*="-create"]:not([href*="contractor-bill-create"]):not([href*="contractor-create"])').hide();
            $('.removeBtnCust, .btn-outline-danger').hide();
            // $('td:has(a[href*="-edit"]), td:has(button[data-route*="-delete"])').hide();
            $("button:contains('Add New'), a:contains('Add New')").hide();
        }
    });
</script>
<?php endif; ?>

<?php if (isset($_SESSION['activity_log_id'])): ?>
<!-- User Activity Tracking Heartbeat -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let isActive = true;

        // Function to check if the tab is visible
        function isTabVisible() {
            return document.visibilityState === 'visible';
        }

        // Track user activity (mouse movement, key down, scroll, click, touch)
        const activityEvents = ['mousemove', 'keydown', 'scroll', 'click', 'touchstart'];
        function setActivityTrue() {
            isActive = true;
        }

        activityEvents.forEach(function(event) {
            window.addEventListener(event, setActivityTrue, { passive: true });
        });

        // Ping the server every 1 minute (60,000 milliseconds) if active and tab is visible
        setInterval(function() {
            if (isActive && isTabVisible()) {
                if (typeof $ !== 'undefined') {
                    $.ajax({
                        url: (window.base_url || '') + '/update-activity',
                        type: 'POST',
                        success: function(response) {
                            console.log("Activity heartbeat updated successfully.");
                        },
                        error: function(xhr) {
                            console.error("Activity heartbeat failed: ", xhr.responseText);
                        }
                    });
                } else {
                    // Fallback to vanilla fetch if jQuery isn't loaded
                    fetch((window.base_url || '') + '/update-activity', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Activity heartbeat updated successfully (fetch).");
                    })
                    .catch(err => {
                        console.error("Activity heartbeat failed (fetch): ", err);
                    });
                }
                
                // Reset active flag until next interaction
                isActive = false;
            }
        }, 60000);

        // Reset activity status when user returns to the tab
        document.addEventListener('visibilitychange', function() {
            if (isTabVisible()) {
                isActive = true;
            }
        });
    });
</script>
<?php endif; ?>

<!-- Daily Meal Notice Widget & Modal for Enrolled Employees -->
<?php require_once __DIR__ . '/meal_daily_modal.php'; ?>

<!-- Role-based Approval & Disbursement Reminder Modal -->
<?php require_once __DIR__ . '/approval_reminder_modal.php'; ?>

<script>
    // Smart Back Button logic for All Approval Requests
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('ref') === 'all_approvals') {
            const backButtons = Array.from(document.querySelectorAll('a')).filter(a => {
                const text = a.textContent.trim().toLowerCase();
                const html = a.innerHTML.toLowerCase();
                return text === 'back' || text.startsWith('back to') || text === '← back' || html.includes('arrow-left') || a.classList.contains('btn-back');
            });
            backButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (window.history.length > 1 && document.referrer.includes('all-approval-requests')) {
                        window.history.back();
                    } else if (document.referrer && document.referrer.includes('all-approval-requests')) {
                        window.location.href = document.referrer;
                    } else {
                        window.location.href = (window.base_url || '') + '/all-approval-requests';
                    }
                });
            });
        }
    });

    // Responsive sidebar dismiss and overlay handler for tablet/mobile
    document.addEventListener("DOMContentLoaded", function() {
        function closeMobileSidebar() {
            var sidebar = document.querySelector('.app-sidebar');
            var overlay = document.querySelector('.app__offcanvas-overlay');
            if (sidebar) {
                sidebar.classList.remove('close_sidebar');
            }
            if (overlay) {
                overlay.classList.remove('overlay-open');
            }
        }

        var overlay = document.querySelector('.app__offcanvas-overlay');
        if (overlay) {
            overlay.addEventListener('click', closeMobileSidebar);
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMobileSidebar();
        });

        if (window.innerWidth <= 1199) {
            var menuLinks = document.querySelectorAll('.app-sidebar .sidebar__menu-item:not(.has-sub > a)');
            menuLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    setTimeout(closeMobileSidebar, 150);
                });
            });
        }
    });
</script>

<?php if (!empty($_SESSION['tipsoi_check_reminders_on_load'])): ?>
    <?php unset($_SESSION['tipsoi_check_reminders_on_load']); ?>
    <script>
        // Non-blocking background trigger for Tipsoi pending reminders upon portal login
        (function() {
            setTimeout(function() {
                var baseUrl = window.base_url || '<?= $GLOBALS['base_url'] ?? '' ?>';
                if (!baseUrl && window.location.pathname.includes('/TPHL-ERP/')) {
                    baseUrl = '/TPHL-ERP';
                }
                var url = baseUrl + '/api/tipsoi/trigger-login-reminder';
                fetch(url, { credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d && d.success) {
                            console.log('[Tipsoi Reminder] Background check completed:', d.data);
                        }
                    })
                    .catch(function(err) {
                        console.warn('[Tipsoi Reminder] Background check error:', err);
                    });
            }, 1500);
        })();
    </script>
<?php endif; ?>

<?php if (!empty($_SESSION['user']['id'])): ?>
    <style>
        #tphlNotifCenterOverlay {
            position: fixed;
            inset: 0;
            z-index: 1095;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        #tphlNotifCenterOverlay.is-open {
            display: flex;
            animation: tphlNotifFadeIn 0.2s ease-out;
        }
        #tphlNotifToastStack {
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-width: 520px;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
        }
        .tphl-notif-toast {
            background: #ffffff;
            border: 1px solid #d1e7dd;
            border-top: 5px solid #006837;
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            padding: 24px 26px;
            position: relative;
            animation: tphlNotifPopIn 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .tphl-notif-toast-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }
        .tphl-notif-toast-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: #ecfdf5;
            color: #006837;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 22px;
        }
        .tphl-notif-toast-close {
            position: absolute;
            top: 14px;
            right: 16px;
            background: #f1f5f9;
            border: none;
            color: #64748b;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 18px;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .tphl-notif-toast-close:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .tphl-notif-toast-msg {
            font-size: 15px;
            color: #1e293b;
            line-height: 1.55;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 13px 15px;
            margin-bottom: 18px;
            word-break: break-word;
        }
        .tphl-notif-toast-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }
        .tphl-notif-btn-primary {
            background: #006837;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 9px 18px;
            font-size: 13.5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: background 0.15s ease;
        }
        .tphl-notif-btn-primary:hover {
            background: #087F5B;
        }
        .tphl-notif-btn-outline {
            background: #ffffff;
            color: #006837;
            border: 1px solid #006837;
            border-radius: 8px;
            padding: 9px 15px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .tphl-notif-btn-outline:hover {
            background: #ecfdf5;
            color: #006837;
        }
        .tphl-notif-btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .tphl-notif-btn-secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        @keyframes tphlNotifFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        @keyframes tphlNotifPopIn {
            from { opacity: 0; transform: scale(0.92) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
    <div id="tphlNotifCenterOverlay" role="dialog" aria-modal="true" aria-live="polite">
        <div id="tphlNotifToastStack"></div>
    </div>
    <script>
        (function() {
            const baseUrl = window.base_url || <?= json_encode($GLOBALS['base_url'] ?? '') ?>;
            const userId  = <?= (int)$_SESSION['user']['id'] ?>;
            const storageKey = 'tphl_last_seen_notif_id_' + userId;
            const overlay = document.getElementById('tphlNotifCenterOverlay');
            const stack = document.getElementById('tphlNotifToastStack');
            if (!overlay || !stack || !userId) return;

            const actionLabels = {
                task_assigned:      { label: 'New Task Assigned',     icon: 'fa-user-plus',     color: '#006837', bg: '#ecfdf5' },
                task_reassigned:    { label: 'Task Reassigned',       icon: 'fa-people-arrows', color: '#087F5B', bg: '#ecfdf5' },
                collaborator_added: { label: 'Added as Collaborator', icon: 'fa-users',         color: '#006837', bg: '#ecfdf5' },
                task_mentioned:     { label: 'Mentioned You',         icon: 'fa-at',            color: '#087F5B', bg: '#ecfdf5' },
                task_accepted:      { label: 'Task Accepted',         icon: 'fa-handshake',     color: '#006837', bg: '#ecfdf5' },
                status_changed:     { label: 'Status Updated',        icon: 'fa-arrows-rotate', color: '#087F5B', bg: '#ecfdf5' },
                progress_updated:   { label: 'Progress Updated',      icon: 'fa-chart-line',    color: '#006837', bg: '#ecfdf5' },
                subtask_updated:    { label: 'Checklist Updated',     icon: 'fa-list-check',    color: '#087F5B', bg: '#ecfdf5' },
                task_completed:     { label: 'Task Completed',        icon: 'fa-circle-check',  color: '#006837', bg: '#dcfce7' },
                task_rated:         { label: 'Task Rated',            icon: 'fa-star',          color: '#006837', bg: '#ecfdf5' },
                task_comment:       { label: 'New Comment',           icon: 'fa-comment-dots',  color: '#087F5B', bg: '#ecfdf5' },
                task_edited:        { label: 'Task Updated',          icon: 'fa-pen-to-square', color: '#006837', bg: '#ecfdf5' },
                task_deleted:       { label: 'Task Deleted',          icon: 'fa-trash-can',     color: '#dc2626', bg: '#fef2f2' }
            };

            function escHtml(s) {
                return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
            }

            function getLastSeenId() {
                try {
                    const v = parseInt(localStorage.getItem(storageKey) || '0', 10);
                    return isNaN(v) ? 0 : v;
                } catch (e) { return 0; }
            }

            function setLastSeenId(id) {
                try {
                    if (id > getLastSeenId()) localStorage.setItem(storageKey, String(id));
                } catch (e) {}
            }

            function updateSidebarBadge(count) {
                const c = Math.max(0, parseInt(count || 0, 10));
                ['topbarNotificationBadge', 'sidebarNotificationBadge'].forEach(id => {
                    const badge = document.getElementById(id);
                    if (badge) {
                        badge.textContent = c;
                        badge.style.display = c > 0 ? '' : 'none';
                    }
                });
            }

            function extractTaskId(item) {
                let tid = parseInt(item.task_id || 0, 10);
                if (!tid) {
                    const m = String(item.message || '').match(/\bT-(\d+)\b/i);
                    if (m) tid = parseInt(m[1], 10);
                }
                return tid;
            }

            function syncOverlayVisibility() {
                if (stack.children.length > 0) {
                    overlay.classList.add('is-open');
                } else {
                    overlay.classList.remove('is-open');
                }
            }

            function closeAllToasts() {
                stack.innerHTML = '';
                syncOverlayVisibility();
            }

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) closeAllToasts();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeAllToasts();
            });

            function showToast(item) {
                const actKey = String(item.action_type || '').toLowerCase();
                const isDeleted = (actKey === 'task_deleted') || (item.task_exists !== undefined && parseInt(item.task_exists, 10) === 0);
                const meta = actionLabels[actKey] || { label: 'New Notification', icon: 'fa-bell', color: '#006837', bg: '#ecfdf5' };
                const taskId = isDeleted ? 0 : extractTaskId(item);
                const targetHref = taskId > 0
                    ? (baseUrl + '/my-work-dashboard?task_id=' + encodeURIComponent(taskId))
                    : (baseUrl + '/notifications');

                const toast = document.createElement('div');
                toast.className = 'tphl-notif-toast';
                toast.style.borderTopColor = meta.color;
                toast.innerHTML =
                    '<button type="button" class="tphl-notif-toast-close" title="Close">&times;</button>' +
                    '<div class="tphl-notif-toast-header">' +
                        '<div class="tphl-notif-toast-icon" style="background:' + meta.bg + ';color:' + meta.color + ';">' +
                            '<i class="fa-solid ' + meta.icon + '"></i>' +
                        '</div>' +
                        '<div style="min-width:0;padding-right:26px;">' +
                            '<div style="font-size:17px;font-weight:700;color:#0f172a;line-height:1.25;">' + escHtml(meta.label) + '</div>' +
                            '<div style="font-size:12.5px;color:#64748b;margin-top:2px;">' +
                                (item.actor_name ? 'From <strong style="color:#334155;">' + escHtml(item.actor_name) + '</strong>' : 'Task Notification') +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="tphl-notif-toast-msg">' + escHtml(item.message || '') + '</div>' +
                    '<div class="tphl-notif-toast-actions">' +
                        '<button type="button" class="tphl-notif-btn-secondary tphl-notif-dismiss">' + (isDeleted ? 'Close' : 'Dismiss') + '</button>' +
                        '<button type="button" class="tphl-notif-btn-outline tphl-notif-mark-read">' +
                            '<i class="fa-solid fa-check"></i> Mark as Read' +
                        '</button>' +
                        (isDeleted ? '' : (
                            '<button type="button" class="tphl-notif-btn-primary tphl-notif-open" style="background:' + meta.color + ';">' +
                                '<i class="fa-solid fa-arrow-up-right-from-square"></i> ' +
                                (taskId > 0 ? 'View Task Details' : 'Open Notifications') +
                            '</button>'
                        )) +
                    '</div>';

                function removeToast(markRead) {
                    if (markRead) {
                        const fd = new FormData();
                        fd.append('id', String(item.id));
                        fetch(baseUrl + '/notifications/mark-read', { method: 'POST', body: fd, keepalive: true })
                            .then(r => r.ok ? r.json() : null)
                            .then(d => {
                                if (d && d.status === 'success') {
                                    updateSidebarBadge(d.unread_count);
                                    const pageBadge = document.getElementById('notifPageUnreadBadge');
                                    if (pageBadge) {
                                        const c = Math.max(0, parseInt(d.unread_count || 0, 10));
                                        pageBadge.textContent = c + ' Unread';
                                        pageBadge.style.display = c > 0 ? '' : 'none';
                                    }
                                    const pillBadge = document.getElementById('pillUnreadCount');
                                    if (pillBadge) pillBadge.textContent = Math.max(0, parseInt(d.unread_count || 0, 10));
                                }
                            })
                            .catch(() => {});
                    }
                    toast.style.opacity = '0';
                    toast.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        toast.remove();
                        syncOverlayVisibility();
                    }, 180);
                }

                toast.querySelector('.tphl-notif-toast-close').addEventListener('click', () => removeToast(false));
                toast.querySelector('.tphl-notif-dismiss').addEventListener('click', () => removeToast(false));
                toast.querySelector('.tphl-notif-mark-read').addEventListener('click', () => removeToast(true));
                const openBtn = toast.querySelector('.tphl-notif-open');
                if (openBtn) {
                    openBtn.addEventListener('click', function() {
                        const fd = new FormData();
                        fd.append('id', String(item.id));
                        fetch(baseUrl + '/notifications/mark-read', { method: 'POST', body: fd, keepalive: true }).catch(() => {});
                        window.location.href = targetHref;
                    });
                }

                stack.appendChild(toast);
                syncOverlayVisibility();
            }

            let isPolling = false;
            function pollNotifications() {
                if (isPolling || document.hidden) return;
                isPolling = true;
                fetch(baseUrl + '/notifications/unread-count', {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data || data.status !== 'success') return;
                    updateSidebarBadge(data.unread_count);

                    const latest = Array.isArray(data.latest) ? data.latest : [];
                    if (!latest.length) return;

                    const lastSeen = getLastSeenId();
                    const unseen = latest
                        .filter(it => parseInt(it.id || 0, 10) > lastSeen)
                        .sort((a, b) => parseInt(a.id, 10) - parseInt(b.id, 10));

                    if (unseen.length) {
                        const maxId = parseInt(unseen[unseen.length - 1].id, 10);
                        setLastSeenId(maxId);
                        unseen.slice(-3).forEach(showToast);
                    }
                })
                .catch(() => {})
                .finally(() => { isPolling = false; });
            }

            setTimeout(pollNotifications, 1000);
            setInterval(pollNotifications, 15000);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) pollNotifications();
            });
        })();
    </script>
<?php endif; ?>

        </body>

        </html>