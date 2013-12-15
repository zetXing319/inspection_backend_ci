
<?php
    // Fetch the year, month and day
    $year = date(Y);
    $month = date(m);
    $day = date(d);

    // Merge them into a string accepted by the input field
    $date_string = "$year-$month-$day";
    $date_string1 = "$year-$month-$day";
    
?>
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
                <?php echo $page_title; ?>
            </h3>
                </div>
                <div class="col-md-4 col-sm-5 col-xs-6 inspection-logo">
                    <img src="<?php echo LOGO_PATH; ?>" class="" alt="">
                </div>
            </div>
            <hr>
            <!-- END PAGE HEADER-->

            <!-- BEGIN PAGE CONTENT -->
            <?php
              $check_flag1 = false;   //  when user is field manager of drain or lath and inspection requested is assigned status
              $readonly_str1 = "";
              if (isset($inspection['status']) && $user_permission == 2 && $user_builder == 1) {
                $status = $inspection['status'];
                if ($status == 1) { //  assigned one
                  $check_flag1 = true;
                }
              }
              if ($check_flag1) {
                $readonly_str1 = 'readonly';
              }
             ?>
            <div class="row page_content profile-page">
                <div class="col-md-8 col-sm-8">
                    <form action="<?php echo base_url(); ?>inspection/add_pulte_stucco_inspection_request" method="post" data-toggle="validator" role="form" id="frm_inspection_request" enctype="multipart/form-data">
                        <div class="row margin-bottom-10" >
                            <h4 style="color: red;" id="msg_alert"><?php echo $message;?></h4>
                        </div>

                         <div class="form-group">
                     <?php if( $error = $this->session->flashdata('success')): ?>
        <div class="form-group">
              <div class="input-icon">
        <div class="alert alert-dismissible alert-success" id="successMeta">
          <?php echo $error; ?>
        </div>
             </div>
       </div>
                 <?php endif; ?>
     </div> 
    <?php            
    if ($user_permission != 5) {
        ?>
                        <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="date_requested">Requested Date :</label>
                            <div class="col-md-5">
                                <input type="text" readonly placeholder="" id="date_requested_get_endrange" name="date_requested"   class="form-control no-readonly"  maxlength="10"  value="<?php if($inspection['requested_at']){echo $inspection['requested_at'];}else{echo "$date_string"; }?>">
                                 <input type="hidden" id="id" name="id" class="form-control" value="<?php echo $inspection['id'];?>">
                                  <input type="hidden" id="date_requested1" name="date_requested1" class="form-control" value="<?php echo $inspection['requested_at'];?>">
                                  <input type="hidden" id="inspectorid" name="inspectorid" class="form-control" value="<?php echo $inspection['inspector_id'];?>">
                                   
                            </div>
                        </div>
 <?php }else{
    ?>
   <input type="hidden" readonly placeholder="" id="date_requested" name="date_requested"   class="form-control no-readonly"  maxlength="10"  value="<?php echo date('Y-m-d');?>">
   <input type="hidden" id="id" name="id" class="form-control" value="<?php echo $inspection['id'];?>">
   <input type="hidden" id="inspectorid" name="inspectorid" class="form-control" value="<?php echo $inspection['inspector_id'];?>">
   <input type="hidden"  name="claims_rep" value="5">
    <?php

 } ?>
                         <div class="row margin-bottom-10 form-group">
                                <label class="control-label col-md-3" for="first_name">Full Name :</label>
                                <div class="col-md-5">
                                <input type="text" placeholder="" id="first_name" name="first_name" class="form-control"  maxlength="100" value="<?php echo $inspection['first_name'];?>">
                                </div>
                            </div>
                              <?php if($inspection['email']==""){
                        ?>
                             <div class="row margin-bottom-10 form-group">
                                <label class="control-label col-md-3" for="email">Email :</label>
                                <div class="col-md-5">
                                    <input type="email"   placeholder="" id="email" name="email" class="form-control"  maxlength="100" value="<?php echo $inspection['email'];?>">
                                </div>
                            </div>
                             <?php
                           }else
                           {
                            ?>
                            <div class="row margin-bottom-10 form-group">
                                <label class="control-label col-md-3" for="email1">Email :</label>
                                <div class="col-md-5">
                                    <input type="email" readonly  placeholder="" id="email1" name="email1" class="form-control"  maxlength="100" value="<?php echo $inspection['email'];?>">
                                </div>
                            </div>
                            <?php
                           }
                           ?>
                          
                            <div class="row margin-bottom-10 form-group">
                                <label class="control-label col-md-3" for="cell_phone">Phone Number :</label>
                                <div class="col-md-5">
                                    <input type="Number" placeholder="" id="cell_phone" name="cell_phone" class="form-control"  minlength="10" value="<?php echo $inspection['cell_phone'];?>">
                                </div>
                            </div>
                            <?php            
        if($user_permission != 5) {
        if($inspection['job_number']==""){
                        ?>
                           <div class="row margin-bottom-10 form-group for-model-home-hidden">
                            <label class="control-label col-md-3" for="job_number">Job Number :</label>
                            <div class="col-md-5">
                                <input <?php echo $check_flag1?'readonly':'' ?> type="text" placeholder="xxxx-xxx-xx" readonly data-mask="" id="job_number" name="job_number" class="form-control input-custom"  maxlength="11" value="<?php echo $inspection['job_number'];?>">
                            </div>
                        </div>
                       <?php
                           }else
                           {
                            ?>
                            <div class="row margin-bottom-10 form-group for-model-home-hidden">
                            <label class="control-label col-md-3" for="job_number1">Job Number :</label>
                            <div class="col-md-5">
                                <input <?php echo $check_flag1?'readonly':'' ?> type="text" placeholder="xxxx-xxx-xx" data-mask="" id="job_number1" readonly name="job_number1" class="form-control"  maxlength="11" value="<?php echo $inspection['job_number'];?>">
                            </div>
                        </div>
                            <?php
                           }
                       }else{
                        ?>
                            <input type="hidden" " name="job_number" class="form-control" value="<?php echo time()?>">
                            <?php
                           }
                           ?>
                       
                        <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="community_name">Community Name :</label>
                           <div class="col-md-5">
                                <input type="text" placeholder="" id="community_name" name="community_name" class="form-control" value="<?php echo $inspection['community_name'];?>">
                                <input type="hidden" placeholder="" id="community_id" name="community_id" class="form-control" value="<?php echo $inspection['community_id'];?>">
                            </div>
                        </div>
                        
                        <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="address">Address :</label>
                            <div class="col-md-5">
                                <input type="text" placeholder="" id="address" name="address" class="form-control" value="<?php echo $inspection['address'];?>">
                                
                            </div>
                        </div>
                        <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="city">City :</label>
                            <div class="col-md-5">
                                <input type="text" placeholder="" id="city" name="city" class="form-control"  value="<?php echo $inspection['city']; ?>">
                            </div>
                        </div>
                        <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="details">Zip :</label>
                            <div class="col-md-5">
                                <input type="text" placeholder="" id="zip" name="zip" class="form-control" value="<?php echo $inspection['zip']; ?>">
                            </div>
                        </div>
                         <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="details">State :</label>
                            <div class="col-md-5">
                                <input type="text" placeholder="" id="state" name="state" class="form-control" value="<?php echo $inspection['state']; ?>">
                            </div>
                        </div>
                     
                       
                          <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="start_date_requested">Inspection Date Range Start:</label>
                            <div class="col-md-5">
                                <input type="text" placeholder="" id="start_date_requested" name="start_date_requested" readonly  class="form-control date-picker1 no-readonly"  maxlength="10"  value="<?php if($inspection['start_date_requested']){echo $inspection['start_date_requested'];}?>">
                            </div>
                        </div>
                        <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="end_date_requested">Inspection Date Range End :</label>
                            <div class="col-md-5">
                                <input type="text" placeholder="" id="end_date_requested" name="end_date_requested" readonly  class="form-control date-picker1 no-readonly"  maxlength="10"  value="<?php if($inspection['end_date_requested']){echo $inspection['end_date_requested'];}?>">
                            </div>
                        </div>
                         <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="close_escrow_date">Close Escrow Date:</label>
                            <div class="col-md-5">
                                <input type="text" readonly placeholder="" id="close_escrow_date" name="close_escrow_date"   class="form-control no-readonly form_datetime"  maxlength="10"  value="<?php if($inspection['close_escrow_date']){echo $inspection['close_escrow_date'];}else{echo "$date_string"; }?>">
                            </div>
                        </div>
                       <?php if($inspection['upload_file']==""){
                        ?>

                        <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="userfile">Upload File :</label>
                            <div class="col-md-5">
                                <input type="file"  name="userfile"  class="form-control" value="<?php echo $inspection['upload_file']; ?>">

                            
                            </div>
                        </div>
                        <?php
                           }
                           ?>
                          <div class="row margin-bottom-10 form-group">
                            <label class="control-label col-md-3" for="access_instructions">Access Instructions:</label>
                            <div class="col-md-5">
                           <input type="text" placeholder="" id="access_instructions" name="access_instructions" class="form-control" value="<?php echo $inspection['access_instructions']; ?>">
                        </div>
                    </div>

   

                        <div class="row margin-bottom-20 form-group">
                            <label class="control-label col-md-3"></label>
                            <div class="col-md-5">
                                <button type="submit" class="btn btn-warning">Submit</button>
                            </div>

                            <input type="hidden" id="inspector_id" name="inspector_id" value="">
                            <input type="hidden" id="requested_id" name="requested_id" value="<?php echo $inspection['id']; ?>">
                        </div>
                    </form>
                </div>
            </div>
            <!-- END PAGE CONTENT -->

        </div>
    </div>
    <!-- END CONTENT -->

</div>
<!-- END CONTAINER -->

<?php require 'common/footer.php'; ?>

<div id="job_confirm_dialog" class="bootbox modal fade bootbox-confirm modal-overflow" tabindex="-1" role="dialog" aria-hidden="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="bootbox-close-button close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title">Confirm</h4>
            </div>
            <div class="modal-body">
                <div class="bootbox-body"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default yellow">Change Job Number</button>
                <button type="button" class="btn btn-primary">Edit</button>
                <button type="button" class="btn btn-danger green">Continue</button>
            </div>
        </div>
    </div>
</div>

<!-- <script type="text/javascript">
  document.getElementById('job_number').addEventListener('blur', function (e) {
  var x = e.target.value.replace(/\D/g, '').match(/(\d{4})(\d{3})(\d{2})/);
  e.target.value =  x[1]+ '-' + x[2] + '-' + x[3];
});</script> -->
 <!-- <script src="http://avpass-v2.avtechusa.com/assets/js/cleave.js"></script>
<script src="http://avpass-v2.avtechusa.com/assets/js/cleave.min.js"></script> -->
<!-- <script src="http://avpass-v2.avtechusa.com/assets/js/cleave-phone.i18n.js"></script> -->
<script src="<?php echo base_url().'resource/assets/plugins/cleave.js'?>" type="text/javascript"></script>

<script>
  


// custom
var cleaveCustom = new Cleave('.input-custom', {
    numericOnly: true,
    blocks: [4, 3, 2],
    delimiter: '-',

});


</script>

<script>
    jQuery(document).ready(function () {
        Metronic.init(); // init metronic core componets
        Layout.init(); // init layout
    });
</script>
<!-- END JAVASCRIPTS -->


</body>

<!-- END BODY -->
</html>

<!--///////// calender date ////////////-->

<script>
CKEDITOR.replace('description', {
   toolbarStartupExpanded : true,
   removePlugins: 'sourcearea',
   removeButtons: 'Source,Save,Print,Preview,Find,About,Maximize,ShowBlocks,image,table',

   /* removePlugins: 'toolbar',
   allowedContent: 'p h1 h2 strong em; a[!href]; img[!src,width,height];',
              height:250,
     toolbar: [
     { name: 'paragraph', groups: ['list', 'indent', 'blocks', 'align', 'bidi']},                
    { name: 'basicstyles', items: [ 'Bold', 'Italic' ] }
  ]
  */
    });

       </script>
<script>
    CKEDITOR.instances.description.on('change', function() {   

  var value = CKEDITOR.instances['description'].getData();
    $("#descriptionValallow").val(value);
    $("#description").val(value);
    if(value.length>0){
     $("#descriptionValallow-error").text(''); 
    } else{
       $("#descriptionValallow-error").text('This field is required'); 
    }
    
    });
  </script>
<script>

/***************************************************************
 calender  date
****************************************************************/

$(document).ready(function(){
    $("#date_requested").datepicker({
        minDate: 0,
        maxDate: "+60D",
        numberOfMonths: 1,
       
    });
     
});

/***************************************************************
 calender  date
****************************************************************/
 </script>
   <script>
     setTimeout(function() {
  $('#successMeta').fadeOut('fast');

}, 3000); // <-- time in milliseconds
</script>



<?php if(!empty($inspection['start_date_requested']))  { ?>
<script>
 
$(function(){

        var dateToday = new Date();
        ///// previous and next date
        var yrRange = dateToday.getFullYear() + ":" + (dateToday.getFullYear() + 100);
        var startDateTextBox1 = $('#start_date_requested');
        var endDateTextBox1 = $('#end_date_requested');
        var strdDate=$('#start_date_requested').val();
        var edDate=$('#end_date_requested').val();
        $.timepicker.dateRange(
        startDateTextBox1,
        endDateTextBox1,
        {   
          changeMonth: true,
          changeYear: true,
          //yearRange: yrRange,
          minDate: new Date(strdDate),
          maxDate: new Date(edDate),
      //    minInterval: (1000*60*60*24*1), // 1day
          dateFormat: 'yy-mm-dd', 
          //timeFormat: 'HH:mm',
          start: {}, // start picker options
          end: {
             onSelect: function (selectedDate) {
                            $("#date_requested_get_endrange").val(selectedDate);
                        }  
          } // end picker options    
            
        }
        );

});

    </script>

<?php } else{ ?>
<script>
$(function(){
           var dateToday = new Date();
            ///// previous and next date
            var yrRange = dateToday.getFullYear() + ":" + (dateToday.getFullYear() + 100);
            var startDateTextBox1 = $('#start_date_requested');
            var endDateTextBox1 = $('#end_date_requested');

            $.timepicker.dateRange(
            startDateTextBox1,
            endDateTextBox1,
            {   
              changeMonth: true,
              changeYear: true,
              yearRange: yrRange,
            //  maxDate: dateToday,
              minInterval: (1000*60*60*24*1), // 1day
              dateFormat: 'yy-mm-dd', 
              //timeFormat: 'HH:mm',
              start: {}, // start picker options
              end: {
                onSelect: function (selectedDate) {
                            $("#date_requested_get_endrange").val(selectedDate);
                        } 
              } // end picker options         
            }
            );
});

    </script>
  <?php }?>

  <script>
     setTimeout(function() {
  $('#successMeta').fadeOut('fast');
}, 3000); // <-- time in milliseconds
    </script>

  <script src="<?php echo base_url().'resource/assets/plugins/jquery-ui.js'?>" type="text/javascript"></script>
  <script src="<?php echo base_url().'resource/assets/plugins/jquery-ui-timepicker-addon.js'?>" type="text/javascript"></script>
 
<script src="<?php echo $resPath;?>assets/scripts/pulte_stucco_inspection_request_edit.js" type="text/javascript"></script>
<script type="text/javascript">
    $(".form_datetime1").datepicker({format: 'yyyy-mm-dd'});
</script>


<script>
    $(function() {
        $( "#close_escrow_date" ).datepicker({
                     //set the default date to Jan 1st 1990
            changeMonth: true,
            changeYear: true,
                    yearRange: '1950:2150', //set the range of years
                    dateFormat: 'yy-mm-dd' //set the format of the date
        });
    });
</script>


 <script type="text/javascript">
        $(document).ready(function(){
            $( "#community_name" ).autocomplete({

              source:"<?php echo base_url(); ?>inspection/get_autocomplete/?",
              minLength: 0,
              select: function(event, ui) {
               // $('#searchval').val(ui.item.value);
             //  alert(ui.item.data);
              $('#city').val("");
              $('#address').val("");
              $('#community_id').val("");
              $('#job_number').val("");
              $('#state').val("");
              $('#zip').val("");
               load_data(ui.item.data);
           
            }
            });

            $("#address").autocomplete({
       //  source:"<?php echo base_url(); ?>inspection/get_autocompleteJobByaddress/?",
           source: function(request, response) {
                    $.ajax({
                      url: "<?php echo base_url(); ?>inspection/get_autocompleteJobByaddress/",
                           dataType: "json",
                      data: {
                        term : request.term,
                        job_number : $('#job_number').val()
                      },
                      success: function(data) {
                        response(data);
                      }
                    });
                  },
                  minLength: 0,
              select: function(event, ui) {
             $('#address').val(ui.item.value);          
            }
            }).focus(function () {
              $("#address").click();
              $(this).autocomplete("search");
           });

function load_data(query)
 {
  $.ajax({
   url:"<?php echo base_url(); ?>inspection/fetch",
   method:"POST",
   dataType: "json",
   cache: false,  
   data:{query:query},
   success:function(data){
    $('#result').html(data);
    console.log(data.address);
     $('#city').val(data.city);
   //  $('#address').val(data[0].address);
     $('#community_id').val(data.community_id);
     $('#job_number').val(data.job_number);
     $('#state').val(data.state);
     $('#zip').val(data.zip);
       $('#address').trigger("focus"); //or "click", at least one should work
       $('#address').trigger("click");
   }
  });
 }


        });
 </script>

