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
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/main.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/sidebar.js"></script>

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

    $(parent).find('.select2').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;

        var placeholder = $(this).data('placeholder') || 'Select option';
        var modalParent = $(this).closest('.modal');

        $(this).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            dropdownParent: modalParent.length ? modalParent : $('body')
        });
    });

    // ✅ Fix scroll-to-top for team assignment dropdowns
    $(parent).find('.select2-no-scroll').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) return;

        var placeholder = $(this).data('placeholder') || 'Select option';

        $(this).select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            dropdownParent: $('body'), // append to body to avoid positioning/scroll issues
            scrollAfterSelect: false
        });
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
                        height: 300,
                        toolbar: {
                            show: false
                        }
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
                        formatter: val => '৳ ' + val.toLocaleString()
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
                            formatter: val => '৳ ' + val.toLocaleString()
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
// Role 20 (Site Operator) — view only, hide create/edit/delete UI
// Roles 1, 3 are admins with approval rights — do NOT hide Action columns
$readOnlyRoles = [20];
if (isset($_SESSION['user']['role_id']) && in_array($_SESSION['user']['role_id'], $readOnlyRoles)): 
?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $ !== 'undefined') {
            $('a[href*="-edit"], a[href*="-create"]:not([href*="contractor-bill-create"]):not([href*="contractor-create"])').hide();
            $('.removeBtnCust, .btn-outline-danger').hide();
            $('td:has(a[href*="-edit"]), td:has(button[data-route*="-delete"])').hide();
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

        </body>

        </html>