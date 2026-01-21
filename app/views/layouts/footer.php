        </div> <!-- page__body-wrapper end -->
        </div> <!-- page__full-wrapper end -->

        <!-- Back to top -->
        <div class="progress-wrap">
            <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
                <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
            </svg>
        </div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                $('.selectpicker').selectpicker();

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
                flatpickr("#dateofBirth", {
                    altInput: true,
                    altFormat: "F j, Y",
                    dateFormat: "Y-m-d",
                    defaultDate: "<?= !empty($employee['dob']) ? $employee['dob'] : '' ?>"
                });

                // Month picker for filter
                $("#attendanceMonth").flatpickr({
                    plugins: [
                        new monthSelectPlugin({
                            shorthand: true, // Jan, Feb
                            dateFormat: "Y-m", // backend format
                            altFormat: "F Y" // display format
                        })
                    ]
                });

                // Punch In / Out time
                $("#clockInTime").flatpickr({
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    defaultDate: "09:00",
                    time_24hr: true
                });

                $("#clockOutTime").flatpickr({
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    defaultDate: "18:00",
                    time_24hr: true
                });
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <script>
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: <?= json_encode($_SESSION['flash_success']); ?>,
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'swal2-toast'
                    }
                });
            </script>
        <?php unset($_SESSION['flash_success']);
        endif; ?>


        <?php if (!empty($_SESSION['flash_error'])): ?>
            <script>
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: <?= json_encode($_SESSION['flash_error']); ?>,
                    showConfirmButton: false,
                    timer: 1000000,
                    width: '320px'
                });
            </script>
        <?php unset($_SESSION['flash_error']);
        endif; ?>

        <!-- JS -->
        <!-- Footer Scripts -->

        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/calendar-activision.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/jquery-3.7.0.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/isotope.pkgd.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/bootstrap.bundle.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/magnific-popup.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/ajax-form.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/jquery.repeater.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/waypoints.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/dayjs.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/loader.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/jsvectormap.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/world-merc.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/swiper-bundle.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/popper.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/simplebar.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/simplebar-active.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/backtotop.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/smooth-scrollbar.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/cleave.min.js"></script>
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
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/steps-form.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/dropify.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/dropzone.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/tinymce.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/custom.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/typeahead.bundle.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/bloodhound.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/select2.full.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/jquery.timepicker.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/flatpickr.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/tagify.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/jquery-ui.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/sweetalert2.all.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/apexcharts.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/fullcalendar.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/plugins/ion.rangeSlider.min.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/custom-tagify.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/height-equal.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/custom-chart.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/rangeslider-script.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/jquery.barrating.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/rating-script.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/main.js"></script>
        <script src="<?= $GLOBALS['base_url'] ?>/assets/js/vendor/sidebar.js"></script>



        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.14.0-beta3/js/bootstrap-select.min.js"></script>


        <script>
            document.addEventListener("DOMContentLoaded", function() {

                const labels = <?= json_encode($flatSalesLabels) ?>;
                const series = <?= json_encode($flatSalesSeries) ?>;

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

                new ApexCharts(document.querySelector("#pieChart"), options).render();
            });
        </script>




        <script>
            document.addEventListener("DOMContentLoaded", function() {

                const salesData = {
                    week: {
                        labels: <?= json_encode(array_column($weeklySales, 'date')) ?>,
                        totals: <?= json_encode(array_column($weeklySales, 'total')) ?>
                    },
                    month: {
                        labels: <?= json_encode(array_column($monthlySales, 'date')) ?>,
                        totals: <?= json_encode(array_column($monthlySales, 'total')) ?>
                    },
                    year: {
                        labels: <?= json_encode(array_column($yearlySales, 'month')) ?>,
                        totals: <?= json_encode(array_column($yearlySales, 'total')) ?>
                    }
                };

                // 🔥 THIS PART YOU MISSED
                const activeBtn = document.querySelector('.nav-link.active');
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

                const chart = new ApexCharts(document.querySelector("#salesChart"), options);
                chart.render();

                // Tabs click
                document.querySelectorAll('.nav-link').forEach(button => {
                    button.addEventListener('click', function() {

                        document.querySelectorAll('.nav-link')
                            .forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');

                        const period = this.dataset.period;

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
                    });
                });

            });
        </script>



        </body>

        </html>