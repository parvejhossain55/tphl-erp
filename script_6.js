
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


    });
