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
                        Inspection Details
                    </h3>
                </div>
                <div class="col-md-4 col-sm-5 col-xs-6 inspection-logo">
                    <img src="<?php echo LOGO_PATH; ?>" class="" alt="">
                </div>
            </div>
                    <!--<hr>-->
                    <div class="page-bar">
                        <ul class="page-breadcrumb">
                           <li>
                                Inspections
                                <i class="fa fa-angle-right"></i>
                            </li>
                            <?php
                             if ($inspection['type'] == '1')
                             {
                                ?>
                            <li>
                                <a href="<?php echo $basePath; ?>inspection/water_intrusion.html">Water Intrusion</a>
                                <i class="fa fa-angle-right"></i>
                            </li>
                             <?php
                             }
                             if ($inspection['type'] == '5')
                             {
                                ?>
                            <li>
                                <a href="<?php echo $basePath; ?>inspection/stucco.html">Stucco Intrusion</a>
                                <i class="fa fa-angle-right"></i>
                            </li>
                        <?php
                        }
                         ?>
                            <li>
                                Details
                            </li>
                            <li>
                            </li>
                        </ul>
                    </div>
                    <!-- END PAGE HEADER-->

                    <!-- BEGIN PAGE CONTENT -->
                    <div class="row page_content">
                        <div class="col-md-10">

                            <div class="row">
                                <h4 style="color: red;" id="msg_alert" ></h4>
                            </div>

                            <div class="row margin-bottom-10">
                                <div class="col-md-9">
                                    <h3 style="margin-top: 5px;">
                                        <?php
                                        if ($inspection['type'] == '1')
                                            echo "Drainage Plane Inspection";
                                        if ($inspection['type'] == '2')
                                            echo "Lath Inspection";
                                        if ($inspection['type'] == '5')
                                            echo "Stucco Inspection";
                                        ?>

                                        <a href="" class="btn btn-danger" style="margin-left: 32px;" id="btn_report" data-id="<?php echo $inspection['idd']; ?>"><i class="fa fa-file-pdf-o"></i> Generate Report(Full)</a>
                                        <?php  if ($inspection['type'] == '5')   { ?>
                                            <a class="btn btn-danger" href="<?php echo base_url().'api/ReportGenerateHtml?id='.$inspection['idd'].'&type=full' ?>" style="margin-left: 32px;" download><i class="fa fa fa-html5"></i> Generate Report html</a>
                                        <?php } ?>

                                        <?php

                                        if ($inspection['type'] != '5')
                                        {
                                            ?>


                                            <a href="" class="btn btn-danger" style="margin-left: 32px;" id="btn_report_pass" data-id="<?php echo $inspection['idd']; ?>"><i class="fa fa-file-pdf-o"></i> Generate Report(Without Pass)</a>
                                            <?php
                                        }
                                        ?>
                                    </h3>
                                </div>
                                <div class="col-md-3" style="float:right">
                                        <?php if($inspection['type'] == '5') {?>
                                            <button class="btn btn-warning btn-reassign" style="float:right">Reassign</button>
                                        <?php }?>
                                </div>
                            </div>

                            <div class="row margin-bottom-10">

                                <div class="col-md-6">
                                    <div class="portlet box blue-steel">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                &nbsp; Basic Information  
                                            </div>
                                            <div class="tools">
                                                <a href="javascript:;" class="collapse">
                                                </a>
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <form class="form-inline" action="#" method="post">
                                                <?php if ($inspection['type'] == '5')
                                                 {
                                                 ?>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Homeowner Name :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['first_name']; ?></label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Email :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['email']; ?></label>
                                                    </div>
                                                </div>
                                                 <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Phone Number :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['cell_phone']; ?></label>
                                                    </div>
                                                </div>
                                                 <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Access Instructions :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['access_instructions']; ?></label>
                                                    </div>
                                                </div>
                                               <?php
                                                } ?>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Job Number :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['job_number']; ?></label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Community :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value">
                                                        <?php
                                                            echo $inspection['community_name'];
                                                            ?></label>
                                                        
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">LOT# :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['lot']; ?></label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Address :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['address']; ?></label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Date of Inspection :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['start_date']; ?></label>
                                                    </div>
                                                </div>
                                                <?php if ($inspection['type'] == '5')
                                                 {
                                                 ?>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Inspection Date Range Start :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['start_date_requested']; ?></label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Inspection Date Range End :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['end_date_requested']; ?></label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Close Escrow Date :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['close_escrow_date']; ?></label>
                                                    </div>
                                                </div>
                                                <?php
                                                }
                                                ?>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Inspector Initials :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"> <?php if ($inspection['type'] == '5')
                                                           {
                                                              echo $inspection['int_inspection']; 
                                                            }else{
                                                                echo $inspection['initials']; }?>
                                                            </label>
                                                    </div>
                                                </div>

                                                <?php if (isset($region)) { ?>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Region :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $region; ?></label>
                                                    </div>
                                                </div>
                                                <?php } ?>

                                                <?php if (isset($field_manager)) { ?>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Field Manager :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $field_manager['first_name'] . " " . $field_manager['last_name']; ?></label>
                                                    </div>
                                                </div>
                                                <?php } ?>

                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">House Ready :</label>
                                                    <div class="col-md-6" style="padding-top: 3px;">
                                                        <?php
                                                        if ($inspection['house_ready'] == '1') {
                                                            echo "<label class='label label-danger'>Yes</label>";
                                                        } else {
                                                            echo "<label class='label label-default'>No</label>";
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Location :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value">
                                                            <?php
                                                            if ($inspection['latitude'] == '-1' && $inspection['longitude'] == '-1' && $inspection['accuracy'] == '-1') {
                                                                echo "Not Captured";
                                                            } else {
                                                                echo $inspection['latitude'] . ", " . $inspection['longitude'] . ", Accuracy: " . $inspection['accuracy'] . "m";
                                                            }
                                                            ?>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4"></label>
                                                    <div class="col-md-6">
                                                            <?php
                                                            if ($inspection['latitude'] == '-1' && $inspection['longitude'] == '-1' && $inspection['accuracy'] == '-1') {
                                                            } else {
                                                            ?>
                                                            <img class="img-responsive for-preview google-map" 
                                                            data-src="https://maps.googleapis.com/maps/api/staticmap?center=<?php echo $inspection['latitude'];?>+<?php echo $inspection['longitude'];?>&zoom=15&scale=false&size=750x750&maptype=roadmap&format=jpg&visual_refresh=true&key=<?php echo GOOGLE_MAP_KEY;?>"  
                                                            src="https://maps.googleapis.com/maps/api/staticmap?center=<?php echo $inspection['latitude'];?>+<?php echo $inspection['longitude'];?>&zoom=16&scale=false&size=300x300&maptype=roadmap&format=jpg&visual_refresh=true&key=<?php echo GOOGLE_MAP_KEY;?>" alt="Google Map">
                                                            <?php
                                                            }
                                                            ?>
                                                        </label>
                                                    </div>
                                                </div>

                                                 <?php  if ($inspection['type'] == '5') { ?>
                                                  <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Front Site :</label>
                                                    <div class="col-md-6" <?php echo $inspection['image_front_building'] == "" ? "style='padding-top: 3px;'" : ""; ?>>
                                                        <?php
                                                        if ($inspection['image_front_building'] == '') {
                                                            echo "<label class='label label-warning'>No Image</label>";
                                                        } else {

                                                             $uploaddir = 'resource/upload/stucco/front/';
                                                            echo "<img src='" .$basePath. $uploaddir . $inspection['image_front_building'] . "' class='for-preview' style='max-width: 250px;'>";
                                                        
                                                    }?>
                                                    </div>
                                                </div>


                                                         <?php } else { ?> 


                                              <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Front Building :</label>
                                                    <div class="col-md-6" <?php echo $inspection['image_front_building'] == "" ? "style='padding-top: 3px;'" : ""; ?>>
                                                        <?php
                                                        if ($inspection['image_front_building'] == '') {
                                                            echo "<label class='label label-warning'>No Image</label>";
                                                        } else {

                                                             $uploaddir = 'resource/upload/stucco/front/';
                                                            echo "<img src='".$inspection['image_front_building'] . "' class='for-preview' style='max-width: 250px;'>";
                                                        
                                                    }
                                                        ?>
                                                    </div>
                                                </div>


                                                 <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Front Side :</label>

                                                    <div class="col-md-6" <?php echo trim($inspection['image_front_building_2']) == "" ? "style='padding-top: 3px;'" : ""; ?>>
                                                        <?php
                                                        if (trim($inspection['image_front_building_2']) == '') {
                                                            echo "<label class='label label-warning'>No Image</label>";
                                                         } else {
                                                           
                                                            echo "<img src='" . trim($inspection['image_front_building_2']) . "' class='for-preview' style='max-width: 250px;'>";
                                                       
                                                        }
                                                        ?>
                                                    </div>
                                                </div>

                                                         <?php } ?>
                                             
                                               
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Right Side :</label>
                                                   <div class="col-md-6" <?php echo trim($inspection['image_right_building']) == "" ? "style='padding-top: 3px;'" : ""; ?>>
                                                        <?php
                                                        if (trim($inspection['image_right_building']) == '') {
                                                            echo "<label class='label label-warning'>No Image</label>";
                                                        } else {

                                                             if ($inspection['type'] == '5')
                                                           {
                                                            $uploaddir = 'resource/upload/stucco/right/';
                                                            
                                                            echo "<img src='".$basePath. $uploaddir . trim($inspection['image_right_building']) . "' class='for-preview' style='max-width: 250px;'>";
                                                            }
                                                            else{
                                                            echo "<img src='" . trim($inspection['image_right_building']) . "' class='for-preview' style='max-width: 250px;'>";
                                                        }
                                                    }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Left Side :</label>
                                                   <div class="col-md-6" <?php echo trim($inspection['image_left_building']) == "" ? "style='padding-top: 3px;'" : ""; ?>>
                                                        <?php
                                                        if (trim($inspection['image_left_building']) == '') {
                                                            echo "<label class='label label-warning'>No Image</label>";
                                                        } else {

                                                            if ($inspection['type'] == '5')
                                                           {
                                                            $uploaddir = 'resource/upload/stucco/left/';
                                                            
                                                            echo "<img src='".$basePath. $uploaddir . trim($inspection['image_left_building']) . "' class='for-preview' style='max-width: 250px;'>";
                                                            }
                                                            else{
                                                            echo "<img src='" . trim($inspection['image_left_building']) . "' class='for-preview' style='max-width: 250px;'>";
                                                        }
                                                    }
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Back Side :</label>
                                                    <div class="col-md-6" <?php echo trim($inspection['image_back_building']) == "" ? "style='padding-top: 3px;'" : ""; ?>>
                                                        <?php
                                                        if (trim($inspection['image_back_building']) == '') {
                                                            echo "<label class='label label-warning'>No Image</label>";
                                                        } else {

                                                            if ($inspection['type'] == '5')
                                                           {
                                                            $uploaddir = 'resource/upload/stucco/back/';
                                                            
                                                            echo "<img src='".$basePath. $uploaddir . trim($inspection['image_back_building']) . "' class='for-preview' style='max-width: 250px;'>";
                                                            }
                                                            else{
                                                            echo "<img src='" . trim($inspection['image_back_building']) . "' class='for-preview' style='max-width: 250px;'>";
                                                        }
                                                    }
                                                        ?>
                                                    </div>
                                                </div>

                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="portlet box blue-hoki">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                &nbsp; Additional Information
                                            </div>
                                            <div class="tools">
                                                <a href="javascript:;" class="collapse">
                                                </a>
                                            </div>
                                        </div>
                                        <div class="portlet-body">
                                            <form class="form-inline" action="#" method="post">
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Overall Commments :</label>
                                                    <div class="col-md-6">
                                                        <p style="padding-top: 3px; font-weight: bold;"><?php echo $inspection['overall_comments']; ?></label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Result :</label>
                                                    <div class="col-md-6" style="padding-top: 3px;">
                                                        <?php
                                                        $cls = "label-default";
                                                        if ($inspection['result_code']=='1')
                                                            $cls = "label-primary";
                                                        if ($inspection['result_code']=='2')
                                                            $cls = "label-warning";
                                                        if ($inspection['result_code']=='3')
                                                            $cls = "label-danger";
                                                        ?>
                                                        <label class="label <?php echo $cls; ?>"><?php echo $inspection['result_name']; ?></label>
                                                    </div>
                                                </div>

                                             

                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Date of Inspection :</label>
                                                    <div class="col-md-6">
                                                        <label class="control-label label-value"><?php echo $inspection['end_date']; ?></label>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Signature :</label>
                                                    <div class="col-md-6">
                                                        <?php
                                                           
                                                       
                                                         if ($inspection['type'] == '5')
                                                           {
                                                             $uploaddir = 'resource/upload/stucco/signature/';
                                                             if(!empty($inspection['image_signature'])){
                                                             echo "<img src='" .$basePath. $uploaddir . $inspection['image_signature'] . "' class='for-preview signature' style='max-width: 150px;'>";
                                                               }
                                                            }
                                                            else{
                                                            echo "<img src='" . $inspection['image_signature'] . "' class='for-preview signature' style='max-width: 150px;'>";
                                                        }
                                                    
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="row margin-bottom-10">
                                                    <label class="control-label text-right col-md-4">Recipient Emails :</label>
                                                    <div class="col-md-6">
                                                        <?php foreach ($emails as $row) { ?>
                                                        <label class="control-label label-value"><?php echo $row['email']; ?></label><br>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>


                                    <?php if (isset($comments) && is_array($comments) && count($comments)>0) { ?>
                                    <div class="portlet box blue-hoki">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                &nbsp; Comments
                                            </div>
                                            <div class="tools">
                                                <a href="javascript:;" class="expand">
                                                </a>
                                            </div>
                                        </div>
                                        <div class="portlet-body" style="display: none;">
                                            <ul style="font-size: 15px; line-height: 32px; margin-bottom: 0;">
                                                <?php foreach ($comments as $row) { ?>
                                                <li>
                                                    <?php 
                                                        if ($inspection['type'] == '1' && $row['no'] == '13'){
                                                            echo "<span>Failed drainage plane inspection ONLY for missing windows. Proceed to Lath on areas not affected by missing windows. </span>"
                                                            . "&nbsp;&nbsp;<span style='color: #306DBD;'>*Use online link for Special Window Inspection</span>"
                                                                    . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style=' color: red;' href='$checklist_online_link'>$checklist_online_link</a>";
                                                            
                                                        }else if ($inspection['type'] == '1' && $row['no'] == '14'){
                                                            echo "<span>Failed drainage plane inspection for items other than missing windows. All non-window failures must be corrected and reinspected prior to proceeding to Lath.</span></span>"
                                                            . "&nbsp;&nbsp;<span style='color: #306DBD;'>*Use regular inspection request on the web portal to schedule reinspection.</span>";
                                                                    
                                                            
                                                        }else if ($inspection['type'] == '2' && $row['no'] == '11'){
                                                            echo "<span>Failed lath inspection ONLY on the basis of missing windows. OK to proceed to Lath on areas not affected by missing windows.</span></span>"
                                                            . "&nbsp;&nbsp;<span style='color: #306DBD;'>*Use online link for Special Window Inspection</span>"
                                                                    . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a style=' color: red;    ' href='$checklist_online_link'>$checklist_online_link</a>";
                                                        }else if ($inspection['type'] == '2' && $row['no'] == '12'){
                                                            echo "<span>Failed lath inspection on the basis of items other than missing windows. All non-window related items must be corrected and reinspected prior to proceeding to stucco. </span></span>"
                                                            . "&nbsp;&nbsp;<span style='color: #306DBD;'>*Use regular inspection request on the web portal to schedule reinspection.</span>";
                                                        }else{
                                                            echo $row['comment_name'];
                                                        }
                                                         
                                                    ?></li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <?php } ?>

                                </div>

                            </div>

                            <div class="row">


<?php 

if ($inspection['type']==5)
{

$inspection_id = $inspection['idd'];
 $sql = "select a.*, u.email, c2.name as result_name,
                (select count(*) from ins_inspection d where replace(d.job_number,'-','')=replace(a.job_number,'-','') and type=1 and (d.result_code=1 or d.result_code=2)) as pass_drg_cnt
                from ins_code c2, ins_inspection a
                left join ins_user u on a.user_id=u.id where a.id='" . $inspection_id . "' and c2.kind='rst' and c2.code=a.result_code ";
        $inspection = $this->utility_model->get__by_sql($sql);


          $title = "STUCCO INSPECTION REPORT";

            $sql_request="SELECT ir.* FROM ins_inspection_requested as ir inner join ins_inspection as ii On ir.id=ii.requested_id WHERE ir.id='".$inspection['requested_id']."'";
            $inspection_requested= $this->utility_model->get__by_sql($sql_request);

            $sql_images="SELECT img.* FROM ins_inspection_requested as ir inner join ins_inspection_images as img On  ir.id=img.requested_id where ir.id='".$inspection['requested_id']."'";
            $inspection_images= $this->utility_model->get__by_sql($sql_images);

?>


<div class="col-md-12">
    <div class="portlet box green-jungle">
        <div class="portlet-title">
            <div class="caption">
            &nbsp; Checklist - Interior
            </div>
            <div class="tools">
            <a href="javascript:;" class="expand">
            </a>
            </div>
        </div>
        <div class="portlet-body" style="display: none;">
            <table class="checklist" style="width: 100%; " border="1" >
                <thead>
                <tr>
                    <th style="width: 60%;">CheckPoint</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>

                <tr>
                    <td class="title"> Was an interior inspection performed?</td>
                    <td class="status text-center">
            <?php
          if(!empty($inspection['int_inspection'] == 1)) { 
            echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php 
          $image_int_inspection=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_int_inspection'));
          
        if(count($image_int_inspection)>0) {
           echo $newHtml ="<div><ul class='gridImage'>";
            foreach($image_int_inspection as $item){
                     if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
              echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }
           ?>
             
                    </td>
                </tr>

                  <tr>
                    <td class="title"> If home was built before June 1st, 2013, does it appear the stucco exterior has been re-painted?
                        
                    </td>
                   <td class="status text-center">
                       <?php
                          if($inspection['stucco_exterior'] == 1) {                          
                            echo $newHtml =" <label class='no'>No</label>";
                         }else if($inspection['stucco_exterior'] == 2) {
                            echo $newHtml =" <label class='yesy'>Yes</label>";
                         } if($inspection['stucco_exterior'] == 4) {
                            echo $newHtml =" <label class='not_varify'>Cannot Verify</label>";
                         } else{
                            echo $newHtml =" <label class='not_null'>N/A</label>";
                         }
                         ?>
                   </td>
               </tr> 
                </tbody>
                </table>
        </div>
    </div>
</div>


<div class="col-md-12">
    <div class="portlet box green-jungle">
        <div class="portlet-title">
            <div class="caption">
            &nbsp; Checklist - Front
            </div>
            <div class="tools">
            <a href="javascript:;" class="expand">
            </a>
            </div>
        </div>
        <div class="portlet-body" style="display: none;">
            <table class="checklist" style="width: 100%; " border="1" >
                <thead>
                <tr>
                    <th style="width: 60%;">CheckPoint</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>

                <tr>
                    <td class="title"> 1. Any cracks in stucco over wood frame that are greater than or equal to [1/16]'?
                          <br><span style='color:red;font-size:15px;'>[If Escrow is < 07.01.16 then 1/8', if >= 07.01.16 then 1/16']</span>
                    </td>
                   <td class="status text-center">
            <?php
                  if(!empty($inspection['check_front_building1'] == 1)) { 
                     echo $newHtml =" <label class='yesy'>Yes</label>";
                 }else{
                    echo $newHtml =" <label class='no'>No</label>";
                 }
             ?>
          <br>
          <?php       
         $image_front_building1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_front_building1'));
        if(count($image_front_building1)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_front_building1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
          echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>

                <tr>
                    <td class="title"> 2.  Any cracks in stucco over wood frame that are potentially excessive?
                          <br><span style='color:red;font-size:15px;'>[Include this excessive crack item only if Escrow >= 07.01.16]</span>
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_front_building2'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_front_building2=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_front_building2'));
        if(count($image_front_building2)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_front_building2 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
              echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>
                 <tr>
                    <td class="title"> If Yes, approximately how many cracks per 10 linear feet?
                        
                    </td>
                   <td class="status text-center">
                    <?php echo $inspection_images['text_front_building2']; ?>
                   </td>
               </tr> 
                <tr>
                    <td class="title"> 3. <b> A</b>.Any observed delamination in stucco on either frame or CMU block?
                          
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_front_building3'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_front_building3=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_front_building3'));
        if(count($image_front_building3)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_front_building3 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
             echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>
                   <tr>
                    <td class="title"> <b> B</b>. If Yes, is there a Weep Screed Condition adjacent to the delamination
                        
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_front_building3_1'] == 1)) {
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_front_building3_1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_front_building3_1'));
        if(count($image_front_building3_1)>0) {
             echo  $newHtml ="<div><ul class='gridImage'>";
               foreach($image_front_building3_1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
               echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>

                <tr>
                    <td class="title"> 4<b> A</b>.Any observed water intrusion into the home?       
                    </td>
                   <td class="status text-center">
                    <?php
                  if(!empty($inspection['check_front_building4'] == 1)) { 
                     echo $newHtml =" <label class='yesy'>Yes</label>";
                 }else{
                    echo $newHtml =" <label class='no'>No</label>";
                 }
                 ?>
          <br>
          <?php       
         $image_front_building4=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_front_building4'));
        if(count($image_front_building4)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_front_building4 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
             echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr> 

                   <tr>
                    <td class="title"> If Yes, what evidence of intrusion?
                        
                    </td>
                   <td class="status text-center">
                    <?php echo $inspection_images['text_front_building4']; ?>
                   </td>
               </tr> 
                 </tr>
                   <tr>
                    <td class="title"> <b> B</b>. If Yes, is there a Weep Screed Condition adjacent to the water intrusion?
                        
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_front_building4_1'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_front_building4_1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_front_building4_1'));
        if(count($image_front_building4_1)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_front_building4_1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
         echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr> 
               

                </tbody>
                </table>
        </div>
    </div>
</div>


<div class="col-md-12">
    <div class="portlet box green-jungle">
        <div class="portlet-title">
            <div class="caption">
            &nbsp; Checklist - Right
            </div>
            <div class="tools">
            <a href="javascript:;" class="expand">
            </a>
            </div>
        </div>
        <div class="portlet-body" style="display: none;">
            <table class="checklist" style="width: 100%; " border="1" >
                <thead>
                <tr>
                    <th style="width: 60%;">CheckPoint</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>

                <tr>
                    <td class="title"> 5.Any cracks in stucco over wood frame that are greater than or equal to [1/16]'?
                          <br><span style='color:red;font-size:15px;'>[If Escrow is < 07.01.16 then 1/8', if >= 07.01.16 then 1/16'] </span>
                    </td>
                   <td class="status text-center">
            <?php
                  if(!empty($inspection['check_right_building1'] == 1)) { 
                     echo $newHtml =" <label class='yesy'>Yes</label>";
                 }else{
                    echo $newHtml =" <label class='no'>No</label>";
                 }
             ?>
          <br>
          <?php       
         $image_right_building1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_right_building1'));
        if(count($image_right_building1)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_right_building1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
          echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>

                <tr>
                    <td class="title"> 6.  Any cracks in stucco over wood frame that are potentially excessive? 
                          <br><span style='color:red;font-size:15px;'>[Include this excessive crack item only if Escrow >= 07.01.16] </span>
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_right_building2'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_right_building2=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_right_building2'));
        if(count($image_right_building2)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_right_building2 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
              echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>
                 <tr>
                    <td class="title">If Yes, approximately how many cracks per 10 linear feet?
                        
                    </td>
                   <td class="status text-center">
                    <?php echo $inspection_images['text_right_building2']; ?>
                   </td>
               </tr> 
                <tr>
                    <td class="title"> 7. <b> A</b>.Any observed delamination in stucco on either frame or CMU block? 
                          
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_right_building3'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_right_building3=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_right_building3'));
        if(count($image_right_building3)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_right_building3 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
             echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>
                   <tr>
                    <td class="title"> <b> B</b>. If Yes, is there a Weep Screed Condition adjacent to the delamination
                        
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_right_building3_1'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_right_building3_1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_right_building3_1'));
        if(count($image_right_building3_1)>0) {
             echo  $newHtml ="<div><ul class='gridImage'>";
               foreach($image_right_building3_1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
               echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>

                <tr>
                    <td class="title"> 8<b> A</b>.Any observed water intrusion into the home?       
                    </td>
                   <td class="status text-center">
                    <?php
                  if(!empty($inspection['check_right_building4'] == 1)) { 
                     echo $newHtml =" <label class='yesy'>Yes</label>";
                 }else{
                    echo $newHtml =" <label class='no'>No</label>";
                 }
                 ?>
          <br>
          <?php       
         $image_right_building4=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_right_building4'));
        if(count($image_right_building4)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_right_building4 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
             echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr> 

                   <tr>
                    <td class="title"> if Yes, what evidence of intrusion?
                        
                    </td>
                   <td class="status text-center">
                    <?php echo $inspection_images['text_right_building4']; ?>
                   </td>
               </tr> 
                 </tr>
                   <tr>
                    <td class="title"> <b> B</b>. If Yes, is there a Weep Screed Condition adjacent to the water intrusion?
                        
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_right_building4_1'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_right_building4_1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_right_building4_1'));
        if(count($image_right_building4_1)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_right_building4_1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
         echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='rightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr> 
               

                </tbody>
                </table>
        </div>
    </div>
</div>

<div class="col-md-12">
    <div class="portlet box green-jungle">
        <div class="portlet-title">
            <div class="caption">
            &nbsp; Checklist - Back
            </div>
            <div class="tools">
            <a href="javascript:;" class="expand">
            </a>
            </div>
        </div>
        <div class="portlet-body" style="display: none;">
            <table class="checklist" style="width: 100%; " border="1" >
                <thead>
                <tr>
                    <th style="width: 60%;">CheckPoint</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>

                <tr>
                    <td class="title"> 9.Any cracks in stucco over wood frame that are greater than or equal to [1/16]'?
                          <br><span style='color:red;font-size:15px;'>[If Escrow is < 07.01.16 then 1/8', if >= 07.01.16 then 1/16'] </span>
                    </td>
                   <td class="status text-center">
            <?php
                  if(!empty($inspection['check_back_building1'] == 1)) { 
                     echo $newHtml =" <label class='yesy'>Yes</label>";
                 }else{
                    echo $newHtml =" <label class='no'>No</label>";
                 }
             ?>
          <br>
          <?php       
         $image_back_building1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_back_building1'));
        if(count($image_back_building1)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_back_building1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
          echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='backSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>

                <tr>
                    <td class="title"> 10.  Any cracks in stucco over wood frame that are potentially excessive? 
                          <br><span style='color:red;font-size:15px;'>[Include this excessive crack item only if Escrow >= 07.01.16] </span>
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_back_building2'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_back_building2=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_back_building2'));
        if(count($image_back_building2)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_back_building2 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
              echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='backSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>
                 <tr>
                    <td class="title">If Yes, approximately how many cracks per 10 linear feet?
                        
                    </td>
                   <td class="status text-center">
                    <?php echo $inspection_images['text_back_building2']; ?>
                   </td>
               </tr> 
                <tr>
                    <td class="title"> 11. <b> A</b>.Any observed delamination in stucco on either frame or CMU block? 
                          
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_back_building3'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_back_building3=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_back_building3'));
        if(count($image_back_building3)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_back_building3 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
             echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='backSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>
                   <tr>
                    <td class="title"> <b> B</b>. If Yes, is there a Weep Screed Condition adjacent to the delamination
                        
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_back_building3'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_back_building3=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_back_building3_1'));
        if(count($image_back_building3)>0) {
             echo  $newHtml ="<div><ul class='gridImage'>";
               foreach($image_back_building3 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
               echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='backSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>

                <tr>
                    <td class="title"> 12<b> A</b>.Any observed water intrusion into the home?       
                    </td>
                   <td class="status text-center">
                    <?php
                  if(!empty($inspection['check_back_building4'] == 1)) { 
                     echo $newHtml =" <label class='yesy'>Yes</label>";
                 }else{
                    echo $newHtml =" <label class='no'>No</label>";
                 }
                 ?>
          <br>
          <?php       
         $image_back_building4=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_back_building4'));
        if(count($image_back_building4)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_back_building4 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
             echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='backSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr> 

                   <tr>
                    <td class="title"> if Yes, what evidence of intrusion?
                        
                    </td>
                   <td class="status text-center">
                    <?php echo $inspection_images['text_back_building4']; ?>
                   </td>
               </tr> 
                 </tr>
                   <tr>
                    <td class="title"> <b> B</b>. If Yes, is there a Weep Screed Condition adjacent to the water intrusion?
                        
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_back_building4_1'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_back_building4_1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_back_building4_1'));
        if(count($image_back_building4_1)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_back_building4_1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
         echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='backSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr> 
               

                </tbody>
                </table>
        </div>
    </div>
</div>


<div class="col-md-12">
    <div class="portlet box green-jungle">
        <div class="portlet-title">
            <div class="caption">
            &nbsp; Checklist - Left
            </div>
            <div class="tools">
            <a href="javascript:;" class="expand">
            </a>
            </div>
        </div>
        <div class="portlet-body" style="display: none;">
            <table class="checklist" style="width: 100%; " border="1" >
                <thead>
                <tr>
                    <th style="width: 60%;">CheckPoint</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>

                <tr>
                    <td class="title"> 13.Any cracks in stucco over wood frame that are greater than or equal to [1/16]'?
                          <br><span style='color:red;font-size:15px;'>[If Escrow is < 07.01.16 then 1/8', if >= 07.01.16 then 1/16'] </span>
                    </td>
                   <td class="status text-center">
            <?php
                  if(!empty($inspection['check_left_building1'] == 1)) { 
                     echo $newHtml =" <label class='yesy'>Yes</label>";
                 }else{
                    echo $newHtml =" <label class='no'>No</label>";
                 }
             ?>
          <br>
          <?php       
         $image_left_building1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_left_building1'));
        if(count($image_left_building1)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_left_building1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
          echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='RightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>

                <tr>
                    <td class="title"> 14.  Any cracks in stucco over wood frame that are potentially excessive? 
                          <br><span style='color:red;font-size:15px;'>[Include this excessive crack item only if Escrow >= 07.01.16] </span>
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_left_building2'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_left_building2=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_left_building2'));
        if(count($image_left_building2)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_left_building2 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
              echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='RightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>
                 <tr>
                    <td class="title">If Yes, approximately how many cracks per 10 linear feet?
                        
                    </td>
                   <td class="status text-center">
                    <?php echo $inspection_images['text_left_building2']; ?>
                   </td>
               </tr> 
                <tr>
                    <td class="title"> 15. <b> A</b>.Any observed delamination in stucco on either frame or CMU block? 
                          
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_left_building3'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_left_building3=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_left_building3'));
        if(count($image_left_building3)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_left_building3 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
             echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='RightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>
                   <tr>
                    <td class="title"> <b> B</b>. If Yes, is there a Weep Screed Condition adjacent to the delamination
                        
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_left_building3_1'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>

          <?php       
         $image_left_building3_1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_left_building3_1'));
        if(count($image_left_building3_1)>0) {
             echo  $newHtml ="<div><ul class='gridImage'>";
               foreach($image_left_building3_1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
               echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='RightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr>

                <tr>
                    <td class="title"> 16<b> A</b>.Any observed water intrusion into the home?       
                    </td>
                   <td class="status text-center">
                    <?php
                  if(!empty($inspection['check_left_building4'] == 1)) { 
                     echo $newHtml =" <label class='yesy'>Yes</label>";
                 }else{
                    echo $newHtml =" <label class='no'>No</label>";
                 }
                 ?>
          <br>
          <?php       
         $image_left_building4=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_left_building4'));
        if(count($image_left_building4)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_left_building4 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
             echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='RightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr> 

                   <tr>
                    <td class="title"> if Yes, what evidence of intrusion?
                        
                    </td>
                   <td class="status text-center">
                    <?php echo $inspection_images['text_left_building4']; ?>
                   </td>
               </tr> 
                 </tr>
                   <tr>
                    <td class="title"> <b> B</b>. If Yes, is there a Weep Screed Condition adjacent to the water intrusion?
                        
                    </td>
                   <td class="status text-center">
            <?php
          if(!empty($inspection['check_left_building4_1'] == 1)) { 
             echo $newHtml =" <label class='yesy'>Yes</label>";
         }else{
            echo $newHtml =" <label class='no'>No</label>";
         }
         ?>
          <br>
          <?php       
         $image_left_building4_1=$this->utility_model->getImageList('ins_stucco_image', array('inspection_id'=>$inspection_id,'stucco_label'=>'image_left_building4_1'));
        if(count($image_left_building4_1)>0) {
          echo  $newHtml ="<div><ul class='gridImage'>";
            foreach($image_left_building4_1 as $item){
                if($item['stucco_check'] == 1){ $status="<label class='yesy ImageYes'>Yes</label>"; } else{ $status="<label class='Noy ImageNo'>No</label>"; }
         echo  $newHtml ="<li>
                <div class='section_for_image'> <div class='leftSection'>  <img class='for-preview' src='".base_url().$item['upload_link'].$item['stucco_value_src']."'></div>
                 <div class='RightSection'>
                    <span>status:".$status."</span>
                    <span>".$item['stucco_comments']."</span>
                 </div></div>
                </li>";
              }
            echo  $newHtml ="</ul></div>";
           }         
           ?>
                </td>
                </tr> 
               

                </tbody>
                </table>
        </div>
    </div>
</div>

<?php } ?>
                                <?php
                                    foreach ($locations as $location) {
                                ?>

                                <div class="col-md-12">
                                    <div class="portlet box green-jungle">
                                        <div class="portlet-title">
                                            <div class="caption">
                                                &nbsp; Checklist - <?php echo $location['name']; ?>
                                            </div>
                                            <div class="tools">
                                                <a href="javascript:;" class="expand">
                                                </a>
                                            </div>
                                        </div>
                                        <div class="portlet-body" style="display: none;">
                                            <table class="checklist" style="width: 100%; " border="1" >
                                                <thead>
                                                    <tr>
                                                        <th style="width: 75%;">CheckPoint</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                            <?php
                                                foreach ($location['checklist'] as $checklist) {
                                            ?>

                                                <tr>
                                                    <td class="title"><?php echo $checklist['name'] ?></td>
                                                    <td class="status text-center">
                                                        <?php
                                                        $cls = "label-default";
                                                        if ($checklist['status']=='1')
                                                            $cls = "label-primary";
                                                        if ($checklist['status']=='2')
                                                            $cls = "label-danger";
                                                        if ($checklist['status']=='3')
                                                            $cls = "label-warning";
                                                        if ($checklist['status']=='4')
                                                            $cls = "label-info";
                                                        if ($checklist['status']=='5')
                                                            $cls = "label-info";

                                                        ?>
                                                        <label class="label <?php echo $cls; ?>" style="font-size: 20px;"><?php echo $checklist['status_name'] ?></label> <br>

                                                        <?php if ($checklist['status']==2 && $checklist['primary_photo']!="") { ?>
                                                        <br>
                                                        <img class="for-preview" src="<?php echo $checklist['primary_photo']; ?>" alt="" style="max-width: 200px;">
                                                        <?php } ?>

                                                        <?php if ($checklist['status']==2 && $checklist['secondary_photo']!="") { ?>
                                                        <br>
                                                        <img class="for-preview" src="<?php echo $checklist['secondary_photo']; ?>" alt="" style="max-width: 200px;">
                                                        <?php } ?>

                                                        <?php if ($checklist['status']==2 || $checklist['status']==3) { ?>
                                                        <p style="padding-top: 10px; "><?php echo $checklist['description']; ?></label>
                                                        <?php } ?>
                                                    </td>
                                                </tr>

                                            <?php
                                                }
                                            ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                    }
                                ?>

                            </div>

                        </div>
                    </div>
                    <!-- END PAGE CONTENT -->

                </div>
            </div>
            <!-- END CONTENT -->

        </div>
        <input type="hidden" id="inspection_id" value="<?php echo $inspection_id ?>">
        <input type="hidden" id="requested_id" value="<?php echo $inspection['requested_id']?>">

        <!-- END CONTAINER -->

        <form id="form_move_list" action="<?php echo $basePath; ?>inspection/energy.html" method="post">
        </form>


        <?php require 'common/footer.php'; ?>
        <script src="<?php echo $resPath; ?>assets/plugins/jquery-crop/script/jquery.mousewheel.min.js" type="text/javascript"></script>

        <script>
            jQuery(document).ready(function () {
                Metronic.init(); // init metronic core componets
                Layout.init(); // init layout
            });
        </script>
        <!-- END JAVASCRIPTS -->

        <script src="<?php echo $resPath; ?>assets/scripts/inspection_detail.js" type="text/javascript"></script>

    </body>


<style>
   .section_for_image {
    width: 300px;
}
.leftSection img {
    max-width: 171px;
}

.leftSection {
    float: left;
}

.rightSection, .backSection, .RightSection {
    float: left;
}
ul.gridImage {
  list-style-type: none;
}
.rightSection span, .backSection span, .RightSection span {
    clear: both;
    display: block;
    text-align: left;
    margin-left: 6px;
} 

label.yesy.ImageYes {
    background-color: #428bca;
    font-weight: 300;
    padding: 1px 6px 1px 6px;
    color: #fff;
    font-family: "Open Sans", sans-serif;
}

label.Noy.ImageNo {
    background-color: #5bc0de;
    font-weight: 300;
    padding: 1px 6px 1px 6px;
    color: #fff;
    font-family: "Open Sans", sans-serif;
}


label.no {
     background-color: #5bc0de;
    font-weight: 300;
    padding: 3px 8px 3px 8px;
    color: #fff;
    font-family: "Open Sans", sans-serif;
}
label.yesy {
     background-color: #428bca;
    font-weight: 300;
    padding: 3px 8px 3px 8px;
    color: #fff;
    font-family: "Open Sans", sans-serif;
}
label.yesy {
     background-color: #428bca;
    font-weight: 300;
    padding: 3px 8px 3px 8px;
    color: #fff;
    font-family: "Open Sans", sans-serif;
}
label.not_null {
     background-color: #c6c6c6;
    font-weight: 300;
    padding: 3px 8px 3px 8px;
    color: #fff;
    font-family: "Open Sans", sans-serif;
}
label.not_varify {
     background-color: #89c4f4;
    font-weight: 300;
    padding: 3px 8px 3px 8px;
    color: #fff;
    font-family: "Open Sans", sans-serif;
}


</style>

    <!-- END BODY -->
</html>
