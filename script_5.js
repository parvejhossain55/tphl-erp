
document.addEventListener('DOMContentLoaded', function () {
    $(document).on('change', '.last-action-select-ajax', function() {
        const selectEl = $(this);
        const leadId = selectEl.data('lead-id');
        const action = selectEl.val();
        const url = (typeof base_url !== 'undefined' ? base_url : window.base_url || "<?= $GLOBALS['base_url'] ?>") + '/update-last_leadAction/' + leadId;

        const formData = new URLSearchParams();
        formData.append('last_lead_action', action);
        formData.append('ajax', '1');

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Remove existing classes starting with 'action-'
                selectEl.removeClass(function (index, className) {
                    return (className.match(/(^|\s)action-\S+/g) || []).join(' ');
                });
                // Add new action class
                const classVal = action.replace(' ', '_');
                selectEl.addClass('action-' + (classVal || 'not_called'));

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: data.message || 'Action updated successfully!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                }
            } else {
                throw new Error(data.message || 'Update failed');
            }
        })
        .catch(err => {
            console.error('Error updating action:', err);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed to update action!',
                    text: err.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
            setTimeout(() => location.reload(), 1500);
        });
    });
});
