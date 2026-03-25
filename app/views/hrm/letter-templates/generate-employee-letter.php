<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <div class="card__title-wrap mb-20">
                <h5 class="card__heading-title">Generate Employee Letter</h5>
            </div>

            <!-- Generate Form -->
            <form id="generateLetterForm">
                <div class="card__wrapper mb-20">
                    <div class="row g-20 gy-20">

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="employee_id">Select Employee <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="employee_id" id="employee_id" required>
                                        <option value="">— Select Employee —</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <?php $designationShortName = !empty($emp['designation_name']) ? explode(' ', $emp['designation_name'])[0] : 'N/A'; ?>
                                            <option value="<?= $emp['id'] ?>" data-designation="<?= $emp['designation_id'] ?>"><?= $emp['full_name'] ?> (<?= htmlspecialchars($designationShortName) ?>)</option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="letter_type">Letter Type <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="letter_type" id="letter_type" required>
                                        <option value="">— Select Letter Type —</option>
                                        <option value="appointment">Appointment Letter</option>
                                        <option value="joining">Joining Letter</option>
                                        <option value="promotion">Promotion Letter</option>
                                        <option value="increment">Salary Increment Letter</option>
                                        <option value="experience">Experience Certificate</option>
                                        <option value="noc">No Objection Certificate (NOC)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 text-center mt-3">
                            <button type="button" class="btn btn-primary" id="btnFetchTemplate">Fetch Letter Template</button>
                        </div>

                    </div>
                </div>
            </form>

            <hr>

            <!-- Preview / Editor Area -->
            <div id="previewArea" style="display: none;">
                <h5 class="mb-3">Letter Preview & Edit</h5>
                <form action="<?= $GLOBALS['base_url'] ?>/save-generated-letter" method="POST" target="_blank">
                    <input type="hidden" name="employee_id" id="hidden_employee_id">
                    
                    <div class="from__input-box mb-3">
                        <div class="form__input-title">
                            <label>Subject</label>
                        </div>
                        <div class="form__input">
                            <input class="form-control" name="subject" id="preview_subject" type="text" required>
                        </div>
                    </div>

                    <div class="from__input-box mb-3">
                        <div class="form__input-title">
                            <label>Content</label>
                        </div>
                        <div class="form__input-box">
                            <textarea id="tinymce_simple_textarea" name="letter_body"></textarea>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-success" onclick="printLetter()">Print Letter</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('btnFetchTemplate').addEventListener('click', function() {
        const employeeId = document.getElementById('employee_id').value;
        const letterType = document.getElementById('letter_type').value;

        if (!employeeId || !letterType) {
            alert('Please select both Employee and Letter Type.');
            return;
        }

        const designationId = document.getElementById('employee_id').options[document.getElementById('employee_id').selectedIndex].getAttribute('data-designation');

        fetch(`<?= $GLOBALS['base_url'] ?>/fetch-employee-template?employee_id=${employeeId}&type=${letterType}&designation_id=${designationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('previewArea').style.display = 'block';
                    document.getElementById('hidden_employee_id').value = employeeId;
                    document.getElementById('preview_subject').value = data.subject;
                    
                    if (typeof tinymce !== 'undefined' && tinymce.get('tinymce_simple_textarea')) {
                        tinymce.get('tinymce_simple_textarea').setContent(data.body);
                    } else {
                        document.getElementById('tinymce_simple_textarea').value = data.body;
                    }
                } else {
                    alert(data.message || 'Error fetching template.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching the template.');
            });
    });

    function printLetter() {
        let printWindow = window.open('', '', 'height=600,width=800');
        let subject = document.getElementById('preview_subject').value;
        let body = '';
        
        if (typeof tinymce !== 'undefined' && tinymce.get('tinymce_simple_textarea')) {
            body = tinymce.get('tinymce_simple_textarea').getContent();
        } else {
            body = document.getElementById('tinymce_simple_textarea').value;
        }
        
        printWindow.document.write('<html><head><title>Print Letter</title>');
        printWindow.document.write('<style>body{font-family: Arial, sans-serif; padding: 40px;} .subject{font-weight: bold; margin-bottom: 20px;} @media print { button { display: none; } }</style>');
        printWindow.document.write('</head><body >');
        printWindow.document.write('<h2>' + subject + '</h2><hr>');
        printWindow.document.write(body);
        printWindow.document.write('<br><br><button onclick="window.print()">Print</button>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
    }
</script>
