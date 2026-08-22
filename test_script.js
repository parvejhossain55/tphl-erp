    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('click', '.duplicate-badge-click', function() {
            const duplicates = $(this).data('duplicates');
            const currentLeadId = $(this).data('current-lead-id');
            let html = '';
            if (duplicates && duplicates.length > 0) {
                duplicates.forEach(function(item) {
                    const isCurrent = (item.id == currentLeadId) ? 'table-warning fw-bold' : '';
                    const currentBadge = (item.id == currentLeadId) ? ' <span class="badge bg-warning text-dark ms-1">Current</span>' : '';
                    html += `
                        <tr class="${isCurrent}">
                            <td>#${item.id}${currentBadge}</td>
                            <td>${escapeHtml(item.full_name)}</td>
                            <td>${item.created_at}</td>
                            <td>${escapeHtml(item.project_name)}</td>
                            <td><span class="badge bg-secondary">${escapeHtml(item.last_lead_action)}</span></td>
                            <td><span class="badge bg-info">${escapeHtml(item.lead_type)}</span></td>
                        </tr>
                    `;
                });
            } else {
                html = '<tr><td colspan="6" class="text-center">No duplicate details found.</td></tr>';
            }
            $('#duplicateLeadsModalBody').html(html);
            $('#duplicateLeadsModal').modal('show');
        });

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }


    // View Follow Up Notes
    $(document).on('click', '.view-notes-btn', function(e) {
        e.preventDefault();
        const leadId = $(this).data('id');
        const tbody = $('#followUpNotesModalBody');
        
        tbody.html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');
        
        // Try Bootstrap 5 native first, fallback to jQuery
        try {
            const modalEl = document.getElementById('followUpNotesModal');
            if (typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                $('#followUpNotesModal').modal('show');
            }
        } catch (err) {
            $('#followUpNotesModal').modal('show');
        }
        
        $.ajax({
            url: '<?= $GLOBALS['base_url'] ?>/get-follow-up-history/' + leadId,
            type: 'GET',
            dataType: 'text', // force text to see exactly what we get
            success: function(rawResponse) {
                console.log("Raw follow up history response:", rawResponse);
                let response = [];
                try {
                    response = JSON.parse(rawResponse);
                } catch (e) {
                    console.error("JSON parse error:", e);
                    tbody.html('<tr><td colspan="10" class="text-danger text-center">Failed to load data (JSON parse error). Response: ' + rawResponse.substring(0, 100) + '</td></tr>');
                    return;
                }
                
                let html = '';
                if (Array.isArray(response) && response.length > 0) {
                    try {
                        response.forEach(function(item, index) {
                            const escapeStr = (str) => {
                                if (str === null || str === undefined) return '';
                                return str.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
                            };
                            
                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${escapeStr(item.lead_name)}</td>
                                    <td>${escapeStr(item.project_name)}</td>
                                    <td>${escapeStr(item.flat_name)}</td>
                                    <td>${escapeStr(item.follow_up_date)}</td>
                                    <td>${escapeStr(item.follow_up_time)}</td>
                                    <td>${escapeStr(item.follow_up_type)}</td>
                                    <td>${escapeStr(item.discussion_note)}</td>
                                    <td>${escapeStr(item.next_follow_up_date)}</td>
                                    <td>${escapeStr(item.next_action)}</td>
                                </tr>
                            `;
                        });
                        tbody.html(html);
                    } catch (loopErr) {
                        tbody.html('<tr><td colspan="10" class="text-danger text-center">Error parsing items: ' + loopErr.message + '</td></tr>');
                    }
                } else {
                    html = '<tr><td colspan="10" class="text-center">No follow up notes found.</td></tr>';
                    tbody.html(html);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
                tbody.html('<tr><td colspan="10" class="text-danger text-center">Network error while fetching data. Status: ' + xhr.status + '</td></tr>');
            }
        });
    });
}); // end DOMContentLoaded
</script>


