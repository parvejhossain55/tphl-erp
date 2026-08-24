
document.addEventListener('DOMContentLoaded', function () {
    var filterEl = document.getElementById('dupMemberFilter');
    if (!filterEl) return;
    filterEl.addEventListener('change', function () {
        var selected = this.value.toLowerCase();
        var anyVisible = false;
        document.querySelectorAll('.dup-group').forEach(function (grp) {
            var members = (grp.getAttribute('data-members') || '').toLowerCase();
            var show = !selected || members.includes(selected);
            grp.style.display = show ? '' : 'none';
            if (show) anyVisible = true;
        });
        var noRes = document.getElementById('dupNoResult');
        if (noRes) noRes.classList.toggle('d-none', anyVisible);
    });
});

// Global function for assigning duplicate lead group
window.handleReassignGroup = function (btn, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    var targetUserId = btn.getAttribute('data-user-id');
    var targetUserName = btn.getAttribute('data-user-name');
    var phoneOrEmail = btn.getAttribute('data-phone-or-email');
    var leadName = btn.getAttribute('data-lead-name');

    Swal.fire({
        title: 'Assign Duplicate Group',
        text: 'Are you sure you want to assign all duplicate leads for "' + leadName + '" (' + phoneOrEmail + ') to ' + targetUserName + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Assign All',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            var baseUrl = window.base_url || '<?= $GLOBALS['base_url'] ?>';
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = baseUrl + '/reassign-duplicate-group';

            var input1 = document.createElement('input');
            input1.type = 'hidden';
            input1.name = 'target_user_id';
            input1.value = targetUserId;
            form.appendChild(input1);

            var input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'phone_or_email';
            input2.value = phoneOrEmail;
            form.appendChild(input2);

            document.body.appendChild(form);
            form.submit();
        }
    });
};
