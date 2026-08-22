
    function escapeHtmlGlobal(text) {
        if (typeof text !== 'string') return text;
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    window.fetchNotes = function(leadId) {
        const tbody = $('#followUpNotesModalBody');
        
        tbody.html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');
        
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
            url: (typeof base_url !== 'undefined' ? base_url : window.base_url || "<?= $GLOBALS['base_url'] ?>") + '/get-follow-up-history/' + leadId,
            type: 'GET',
            success: function(rawResponse) {
                console.log("Raw follow up history response:", rawResponse);
                let response = [];
                try {
                    response = JSON.parse(rawResponse);
                } catch (e) {
                    console.error("Failed to parse follow up history:", e, rawResponse);
                    tbody.html('<tr><td colspan="10" class="text-danger text-center">Failed to load data (JSON parse error). Response: ' + escapeHtmlGlobal(rawResponse.substring(0, 100)) + '...</td></tr>');
                    return;
                }
                
                let html = '';
                if (Array.isArray(response) && response.length > 0) {
                    try {
                        response.forEach(function(item, index) {
                            html += `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${escapeHtmlGlobal(item.lead_name)}</td>
                                    <td>${escapeHtmlGlobal(item.project_name)}</td>
                                    <td>${escapeHtmlGlobal(item.flat_name)}</td>
                                    <td>${escapeHtmlGlobal(item.follow_up_date)}</td>
                                    <td>${escapeHtmlGlobal(item.follow_up_time)}</td>
                                    <td>${escapeHtmlGlobal(item.follow_up_type)}</td>
                                    <td>${escapeHtmlGlobal(item.discussion_note)}</td>
                                    <td>${escapeHtmlGlobal(item.next_follow_up_date)}</td>
                                    <td>${escapeHtmlGlobal(item.next_action)}</td>
                                </tr>
                            `;
                        });
                        tbody.html(html);
                    } catch (loopErr) {
                        console.error("Error formatting notes:", loopErr);
                        tbody.html('<tr><td colspan="10" class="text-danger text-center">Error displaying items.</td></tr>');
                    }
                } else {
                    html = '<tr><td colspan="10" class="text-center">No follow up notes found.</td></tr>';
                    tbody.html(html);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                tbody.html('<tr><td colspan="10" class="text-danger text-center">Failed to fetch notes. Please try again.</td></tr>');
            }
        });
    };
