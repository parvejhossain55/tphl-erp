
    const selectAll = document.getElementById('selectAllLeads');
    const assignCard = document.getElementById('assignCard');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.selectedId');
            checkboxes.forEach(cb => cb.checked = this.checked);

            if (this.checked) {
                if (assignCard) assignCard.style.display = 'flex';
            } else {
                const anyChecked = Array.from(document.querySelectorAll('.selectedId')).some(c => c.checked);
                if (assignCard) assignCard.style.display = anyChecked ? 'flex' : 'none';
            }
        });
    }

    document.querySelectorAll('.selectedId').forEach(cb => {
        cb.addEventListener('change', function() {
            const anyChecked = Array.from(document.querySelectorAll('.selectedId')).some(c => c.checked);
            if (assignCard) assignCard.style.display = anyChecked ? 'flex' : 'none';

            if (selectAll) {
                const allChecked = Array.from(document.querySelectorAll('.selectedId')).every(c => c.checked);
                selectAll.checked = allChecked;
            }
        });
    });

    // --- Interactive Column AJAX Logic ---
    // Call status update
    document.querySelectorAll('.call-status').forEach(select => {
        select.addEventListener('change', function() {
            fetch(base_url + '/update-call-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'lead_id=' + this.dataset.id + '&call_status=' + this.value
            });
        });
    });

    // Communication type update
    document.querySelectorAll('.communication-status').forEach(select => {
        select.addEventListener('change', function() {
            fetch(base_url + '/update-communication-type', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'lead_id=' + this.dataset.id + '&communication_type=' + this.value
            });
        });
    });

    // lead status
    document.querySelectorAll('.lead-status').forEach(select => {
        select.addEventListener('change', function() {

            fetch(base_url + '/update-lead-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'lead_id=' + this.dataset.id + '&lead_status=' + encodeURIComponent(this.value)
                })
                .then(res => res.json())
                .then(data => {
                    console.log(data);
                })
                .catch(err => {
                    console.error(err);
                });

        });
    });

    // Save notes via button
    document.querySelectorAll('.note-save-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const leadId = this.dataset.id;
            const textarea = this.parentElement.querySelector('.call-note');
            saveNote(leadId, textarea.value, textarea);
        });
    });

    // Save notes via Enter key
    document.querySelectorAll('.call-note').forEach(textarea => {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                saveNote(this.dataset.id, this.value, this);
            }
        });
    });

    function saveNote(leadId, note, el) {
        const url = (typeof base_url !== 'undefined' ? base_url : window.base_url || "<?= $GLOBALS['base_url'] ?>") + '/update-call-notes';
        fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'lead_id=' + encodeURIComponent(leadId) + '&call_note=' + encodeURIComponent(note) + '&ajax=1'
            })
            .then(res => {
                if (res.status === 401) {
                    // Session expired
                    return res.json().then(data => {
                        throw new Error(data.message || 'Session expired. Please login again.');
                    });
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    el.style.borderColor = '#198754'; // success hint
                    setTimeout(() => el.style.borderColor = '#ddd', 800);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Note saved successfully!',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                } else {
                    throw new Error(data.error || 'Server returned failure');
                }
            })
            .catch(err => {
                console.error('Error saving note:', err);
                el.style.borderColor = '#dc3545'; // error hint

                const isSessionErr = err.message && (err.message.toLowerCase().includes('session') || err.message.toLowerCase().includes('login'));

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: isSessionErr ? 'warning' : 'error',
                        title: isSessionErr ? 'Session Expired!' : 'Failed to save note!',
                        text: err.message,
                        toast: !isSessionErr,
                        position: isSessionErr ? 'center' : 'top-end',
                        showConfirmButton: isSessionErr,
                        confirmButtonText: isSessionErr ? 'Login Again' : undefined,
                        timer: isSessionErr ? undefined : 3000,
                        timerProgressBar: !isSessionErr
                    }).then(result => {
                        if (isSessionErr && result.isConfirmed) {
                            window.location.href = (typeof base_url !== 'undefined' ? base_url : '') + '/admin-login';
                        }
                    });
                }
            });
    }
