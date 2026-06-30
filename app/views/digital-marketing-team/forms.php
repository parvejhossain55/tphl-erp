<div class="app__slide-wrapper">
    <!-- Topbar/Breadcrumb -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="page-title text-dark fw-bold mb-0">TPHL Forms</h4>
            </div>
        </div>
    </div>

    <!-- Forms List -->
    <div class="row" id="formsListContainer">
        <!-- Forms will be injected here via JS -->
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const apiUrl = "<?= $api_url ?>";
        const apiKey = "<?= $api_key ?>";
        const baseUrl = "<?= $GLOBALS['base_url'] ?>";

        function fetchForms() {
            fetch(`${apiUrl}/forms`, {
                method: "GET",
                headers: {
                    "X-API-KEY": apiKey,
                    "Accept": "application/json"
                }
            })
            .then(res => {
                if(res.status === 401) {
                    throw new Error("Unauthorized: API Key is invalid or missing.");
                }
                return res.json();
            })
            .then(data => {
                if (data.success && data.forms) {
                    renderForms(data.forms);
                } else {
                    document.getElementById('formsListContainer').innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load forms.</div></div>';
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('formsListContainer').innerHTML = `<div class="col-12"><div class="alert alert-danger">${err.message}</div></div>`;
            });
        }

        function renderForms(forms) {
            const container = document.getElementById('formsListContainer');
            container.innerHTML = '';

            if (forms.length === 0) {
                container.innerHTML = '<div class="col-12"><div class="alert alert-info">No forms found.</div></div>';
                return;
            }

            forms.forEach(form => {
                const formCard = document.createElement('div');
                formCard.className = 'col-md-4 mb-3';
                formCard.innerHTML = `
                    <div class="card shadow-sm border-0 h-100" style="border-radius:12px; cursor:pointer;" onclick="window.location.href='${baseUrl}/forms/${form.id}/responses'">
                        <div class="card-body py-4 px-4 d-flex align-items-center gap-3">
                            <div style="width:48px;height:48px;background:linear-gradient(135deg,#e3f2fd,#bbdefb);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-file-earmark-text text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark">${form.campaign_name || form.name || 'Untitled Form'}</h6>
                                <span class="text-muted small">ID: ${form.id}</span>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(formCard);
            });
        }

        fetchForms();
    });
</script>
