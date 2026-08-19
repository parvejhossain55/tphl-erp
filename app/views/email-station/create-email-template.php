<?php
$page_title = "Create Email Template";
$page = 'Add Email Template';
$show_breadcrumb = true;

?>


<div class="row">
    <div class="card__wrapper">
        <div class="col-xxl-12 col-xl-12 col-lg-12">

            <div class="card__title-wrap mb-20">
                <h5 class="card__heading-title">Add New Email Template</h5>
            </div>

            <form action="<?= $GLOBALS['base_url'] ?>/store-email-template" method="POST" enctype="multipart/form-data">
                <div class="card__wrapper mb-20">
                    <div class="col-lg-12 mb-2">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="template_name">Template Name <span>*</span></label>
                            </div>
                            <div classqA="form__input">
                                <input class="form-control" name="template_name" id="template_name" type="text">
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 mb-2">
                        <div class="from__input-box">
                            <div class="form__input-title">
                                <label for="subject">Subject <span>*</span></label>
                            </div>
                            <div classqA="form__input">
                                <input class="form-control" name="subject" id="subject" type="text">
                            </div>
                        </div>
                    </div>

                    <div class="from__input-box">
                        <div class="form__input-title">
                            <label>Body <span>*</span></label>
                        </div>
                        <div class="from__input-box">
                            <textarea id="tinymce_simple_textarea" name="body"></textarea>
                        </div>
                    </div>
                    <div class="submit__btn text-center">
                        <button class="btn btn-primary">Submit</button>
                    </div>
            </form>
        </div>
    </div>
</div>