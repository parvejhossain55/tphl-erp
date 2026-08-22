
    const assignUserBtn = document.getElementById('assignUserBtn');
    if (assignUserBtn) {
        assignUserBtn.addEventListener('click', function() {
            const userId = document.getElementById('assignUserSelect').value;
            const selectedLeads = [];

            document.querySelectorAll('.selectedId:checked').forEach(cb => {
                const tr = cb.closest('tr');
                if (tr) selectedLeads.push(tr.dataset.id || tr.querySelector('.selectedId').dataset.id);
            });

            // If nothing selected, just return
            if (!userId || selectedLeads.length === 0) {
                return; // no alert
            }

            // Create a form dynamically and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= $base_url ?>/assign-multiple-leads';

            const inputUser = document.createElement('input');
            inputUser.type = 'hidden';
            inputUser.name = 'user_id';
            inputUser.value = userId;
            form.appendChild(inputUser);

            const inputLeads = document.createElement('input');
            inputLeads.type = 'hidden';
            inputLeads.name = 'lead_ids';
            inputLeads.value = selectedLeads.join(',');
            form.appendChild(inputLeads);

            document.body.appendChild(form);
            form.submit();
        });
    }
