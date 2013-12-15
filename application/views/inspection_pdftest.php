<?php require 'common/variable.php'; ?>

<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en" class="no-js">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->
    <head>
        <?php require 'common/header.php'; ?>
    </head>

    <body class="page-header-fixed page-quick-sidebar-over-content">
        <!-- BEGIN HEADER -->
        <?php require 'common/topbar.php'; ?>
        <!-- END HEADER -->

        <div class="clearfix">
        </div>

        <!-- BEGIN CONTAINER -->
        <div class="page-container">

            <!-- BEGIN SIDEBAR -->
            <div class="page-sidebar-wrapper">
                <div class="page-sidebar navbar-collapse collapse">
                    <!-- BEGIN SIDEBAR MENU -->
                    <?php require 'common/sidebar.php'; ?>
                    <!-- END SIDEBAR MENU -->
                </div>
            </div>
            <!-- END SIDEBAR -->

            <!-- BEGIN CONTENT -->
            <div class="page-content-wrapper">
                <div class="page-content">

                    <!-- BEGIN PAGE HEADER-->
                    <div class="row inspection-page-header">
                        <div class="col-md-8 col-sm-7 col-xs-6 inspection-title">
                            <h3 class="page-title">
                                PDF TEST
                            </h3>
                        </div>
                        <div class="col-md-4 col-sm-5 col-xs-6 inspection-logo">
                            <img src="<?php echo LOGO_PATH; ?>" class="" alt="">
                        </div>
                    </div>
                    <hr>
                    <!-- END PAGE HEADER-->

                    <!-- BEGIN PAGE CONTENT -->

                    <div class="row page_content">
                        <textarea id='pdf_html' style='    width: 100%;    height: 500px;'
                                  >
                        </textarea>
                        <div id='pdf_link'>

                        </div>
                        <button id='pdf_download' type="button" class="btn btn-primary">Download</button>
                    </div>
                    <!-- END PAGE CONTENT -->

                </div>
            </div>
            <!-- END CONTENT -->

        </div>
        <!-- END CONTAINER -->

        <?php require 'common/footer.php'; ?>

        <div id="export_confirm_dialog" class="bootbox modal fade bootbox-confirm modal-overflow" tabindex="-1" role="dialog" aria-hidden="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="bootbox-close-button close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title">Enter Recipients</h4>
                    </div>
                    <div class="modal-body">
                        <h5 style="margin-top: 0; margin-bottom: 5px;">Comma Separated Email Addresses</h5>
                        <textarea class="form-control" id="recipients" style="resize: none; height: 150px;" placeholder="e.g: admin@inspections.e3bldg.com, support@inspections.e3bldg.com"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary">Ok</button>
                    </div>
                </div>
            </div>
        </div>

        <form id="form_move_detail" action="<?php echo $basePath; ?>inspection/detail.html" method="post">
            <input type="hidden" name="inspection_id" id="detail_id" value="">
        </form>

        <form id="form_move_edit" action="<?php echo $basePath; ?>inspection/edit.html" method="post">
            <input type="hidden" name="inspection_id" id="detail_id2" value="">
        </form>
<!--        <form id="form_move_edit" action="<?php echo $basePath; ?>inspection/edit.html" method="post">
            <input type="hidden" name="inspection_id" id="detail_id2" value="">
        </form>-->

        <script>
            jQuery(document).ready(function () {
                Metronic.init(); // init metronic core componets
                Layout.init(); // init layout

                $("#pdf_html").val('<html>\
<head>\
<style>\
.center {\
    text-align: center;\
    border: 3px solid green;\
}\
</style>\
</head>\
<body>\
<h2>Center Text</h2>\
<div class="center">\
  <p>This text is centered.</p>\
</div>\
</body>\
</html>\
');



                $("#pdf_download").click(function (e) {
                    var html = $("#pdf_html").val();
                    // alert($html);
                    // alert(atob($html));
                    // alert(btoa($html));

                    showLoading();
                    $.ajax({
                        type: "POST",
                        url: 'testPost',
                        data: {
                            mode: 2,
                            content: html
                        },
                        dataType: 'json',
                        success: function (data) {
                            hideLoading();
                            if (data.d != null) {
                                var base64 = encodeURIComponent(data.d);
                                base64 = data.d;
                                $.fileDownload($("#basePath").val() + "api/testFunc?mode=10&content=" + base64);
                            } else if (data.url != null) {
                                var pref = 'http://localhost:88/inspdev/';
                                
                                pref = 'https://inspdev.e3bldg.com/';
                                
                                html = "<a href='"+pref+data.url+"'>File Download</a>";
                                $("#pdf_link").html(html);
                            }
                            else{
                                showAlert("Failed to Download!");
                            }
                        },
                        error: function () {
                            hideLoading();
                            showAlert(Message.SERVER_ERROR);
                        }
                    });


                    return true;
                });
            });


        </script>
        <!-- END JAVASCRIPTS -->

        <script src="<?php echo $resPath; ?>assets/scripts/inspection_energy.js" type="text/javascript"></script>

    </body>

    <!-- END BODY -->
</html>
