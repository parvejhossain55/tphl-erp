<?php
$page_title = "Create camera ";
$page = 'Add Camera ';
$show_breadcrumb = true;

?>
<div class="row">
    <div class="card__wrapper p-4 w-100">

        <h4 class="mb-4">Add New Camera </h4>
        <form action="<?= $GLOBALS['base_url'] ?>/store-camera" method="post">
            <div class="row">
                <div class="col-xxl-12">
                    <div class="row gy-10">
                        <div class="col-xl-12">
                            <div class="card__wrapper">
                                <div class="row gy-20">
                                    <div class="row">
                                        <div class="col-lg-6 col-12 mb-2">
                                            <label for="camera_name">Camera Title <span>*</span></label>
                                            <input class="form-control" name="camera_name" id="camera_name" type="text" required>
                                        </div>

                                        <div class="col-lg-6 col-12 mb-2">
                                            <label for="project_id">Project <span>*</span></label>
                                            <select class="form-control" name="project_id" id="project_id" required>
                                                <option value="">Select Project</option>
                                                <!-- Populate from database -->
                                                 <?php foreach($projects as $project): ?>
                                                <option value="<?= $project['id'] ?>"><?= $project['name'] ?></option>
                                                <?php endforeach;  ?>
                                            </select>
                                        </div>

                                        <div class="col-lg-6 col-12 mb-2">
                                            <label for="rtsp_url">RTSP URL <span>*</span></label>
                                            <input class="form-control" name="rtsp_url" id="rtsp_url" type="text" required>
                                        </div>

                                        <div class="col-lg-6 col-12 mb-2">
                                            <label for="status">Status <span>*</span></label>
                                            <select id="status" class="form-select" name="status">
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>

                                        <div class="submit__btn text-center mt-4">

                                        </div>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="submit__btn text-center mb-20">
                        <button class="btn btn-primary">Submit</button>
                    </div>
                </div>
        </form>
    </div>
</div>