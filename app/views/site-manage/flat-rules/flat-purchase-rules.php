<div class="breadcrumb__btn my-4 text-align-end">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewRule">
        Add Rule
    </button>
</div>


<div class="row my-3">
    <div class="col-xxl-12">
        <div class="card__wrapper">
            <div class="table__wrapper table-responsive">
                <table class="table mb-20 multiple_tables">
                    <thead>
                        <tr class="table__title table__sort">
                            <th><input type="checkbox" id="selectall"></th>
                            <th>S/L</th>
                            <th>Rule Description</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody class="table__body">
                        <?php foreach ($rules as $index => $rule): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="selectedId" value="<?= $rule['id'] ?>">
                                </td>

                                <td>#<?= $index + 1 ?></td>

                                <td><?= htmlspecialchars($rule['rules_description']) ?></td>

                                <td>
                                    <?php if ($rule['status'] == 1): ?>
                                        <span class="bd-badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="bd-badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-10">
                                        <button class="table__icon edit"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRule"
                                            data-id="<?= $rule['id'] ?>"
                                            data-description="<?= htmlspecialchars($rule['rules_description']) ?>"
                                            data-status="<?= $rule['status'] ?>">
                                            <i class="fa-sharp fa-light fa-pen"></i>
                                        </button>

                                        <button class="table__icon delete removeBtnCust"
                                            data-id="<?= $rule['id'] ?>"
                                            data-action="delete-flat-purchase-rule">
                                            <i class="fa-regular fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>
<!-- add rules  -->
<div id="addNewRule" class="modal custom-modal fade">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add New Rule</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form id="flatRuleForm" action="<?= $GLOBALS['base_url'] ?>/store-flat-purchase-rule" method="POST">

                    <div id="ruleInputs">
                        <div class="from__input-box mb-2 d-flex align-items-center">
                            <input type="text" name="rules[]" class="form-control me-2" placeholder="Enter rule" required>
                            <button type="button" class="btn btn-success btn-add-rule">+</button>
                        </div>
                    </div>

                    <div class="form__input-title mt-3">
                        <label>Status</label>
                    </div>
                    <div class="radio d-flex gap-20 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" name="status" type="radio" value="1" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" name="status" type="radio" value="0">
                            <label class="form-check-label">Inactive</label>
                        </div>
                    </div>

                    <div class="submit__btn text-center mt-3">
                        <button class="btn btn-primary">Save Rules</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>
<!-- edit flat rules   -->
<div id="editRule" class="modal custom-modal fade">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Rule</h5>
                <button type="button" class="bd-btn-close" data-bs-dismiss="modal">
                    <i class="fa-solid fa-xmark-large"></i>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $GLOBALS['base_url'] ?>/update-flat-purchase-rule" method="POST">
                    <input type="hidden" name="id" id="edit_rule_id">

                    <div class="from__input-box mb-20">
                        <div class="form__input-title">
                            <label>Rule Description</label>
                        </div>
                        <textarea id="edit_rule_description" name="rules_description"
                            class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="radio d-flex gap-20">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" value="1" id="editRuleActive">
                            <label class="form-check-label">Active</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" value="0" id="editRuleInactive">
                            <label class="form-check-label">Inactive</label>
                        </div>
                    </div>

                    <div class="submit__btn text-center mt-30">
                        <button class="btn btn-primary">Update Rule</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<script>
 const base_url = "<?= $GLOBALS['base_url'] ?>";
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById('editRule');

    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;

        document.getElementById('edit_rule_id').value = btn.dataset.id;
        document.getElementById('edit_rule_description').value = btn.dataset.description;

        if (btn.dataset.status === "1") {
            document.getElementById('editRuleActive').checked = true;
        } else {
            document.getElementById('editRuleInactive').checked = true;
        }
    });
});



document.addEventListener("DOMContentLoaded", function () {

    const ruleContainer = document.getElementById('ruleInputs');

    ruleContainer.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('btn-add-rule')) {
            e.preventDefault();

            // Create new input row
            const div = document.createElement('div');
            div.className = 'from__input-box mb-2 d-flex align-items-center';

            div.innerHTML = `
                <input type="text" name="rules[]" class="form-control me-2" placeholder="Enter rule" required>
                <button type="button" class="btn btn-danger btn-remove-rule">-</button>
            `;

            ruleContainer.appendChild(div);
        }

        // Remove input field
        if (e.target && e.target.classList.contains('btn-remove-rule')) {
            e.preventDefault();
            e.target.parentElement.remove();
        }
    });

    // Optional: form submit handler to combine rules if needed
    document.getElementById('flatRuleForm').addEventListener('submit', function(e) {
        // All rules are already in array: rules[]
        // Backend will handle saving each rule individually
    });

});
</script>
