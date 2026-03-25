<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <div class="card__title-wrap mb-20 d-flex justify-content-between align-items-center">
                <h5 class="card__heading-title">Create Letter Template</h5>
                <a href="<?= $GLOBALS['base_url'] ?>/letter-templates" class="btn btn-secondary btn-sm">Back</a>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/store-letter-template" method="POST">

                <div class="card__wrapper mb-20">
                    <div class="row g-20 gy-20">

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="type">Letter Type <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="type" id="type" required>
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

                        <div class="col-lg-6">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="designation_id">Designation (Specific to) <span class="text-muted">Optional</span></label>
                                </div>
                                <div class="form__input">
                                    <select class="form-control" name="designation_id" id="designation_id">
                                        <option value="">— APPLIES TO ALL DESIGNATIONS —</option>
                                        <?php foreach ($designations as $designation): ?>
                                            <option value="<?= $designation['id'] ?>"><?= $designation['designation_name'] ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label for="subject">Letter Subject <span>*</span></label>
                                </div>
                                <div class="form__input">
                                    <input class="form-control" name="subject" id="subject" type="text" placeholder="e.g. Appointment Letter" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="from__input-box">
                                <div class="form__input-title">
                                    <label>Letter Body <span>*</span></label>
                                    <small class="text-muted d-block mb-2">Available Variables: {{employee_name}}, {{designation}}, {{department}}, {{joining_date}}, {{responsibilities}}</small>
                                </div>
                                <div class="from__input-box">
                                    <textarea id="tinymce_simple_textarea" name="body" required></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="submit__btn text-center">
                    <button class="btn btn-primary" type="submit">Save Template</button>
                </div>

            </form>
        </div>
    </div>
</div>
