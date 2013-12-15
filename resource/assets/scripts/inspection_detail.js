
jQuery(document).ready(function () {
    showAlert($("#msg_alert").html());

    $("img.for-preview").on('click', function(e) {
        e.preventDefault();
        
        if ($(this).hasClass('signature')) {
            $.swipebox( [ {
                    href: $(this).attr('src')   ,
                    title : '',
            }], {
                afterOpen: function() {
                    $("#swipebox-container .slide.current").addClass('white-background');
                },
                afterClose: function() {
                    $("#swipebox-container .slide.current").removeClass('white-background');
                }
            });         
        } else if ($(this).hasClass('google-map')) {
            $.swipebox( [ {
                    href: $(this).attr('data-src')   ,
                    title : '',
            }]);         
        } else {
            $.swipebox( [ {
                    href: $(this).attr('src')   ,
                    title : '',
            }]);         
        }
    });

    $('.btn-reassign').on('click', function(){
        console.log("inspection_id:",$("#inspection_id").val(),$("#requested_id").val());
        bootbox.confirm({
            message: "Are you sure reassign?",
            buttons: {
                confirm: {
                    label: 'Yes',
                    className: 'btn-success'
                },
                cancel: {
                    label: 'No',
                    className: 'btn-danger'
                }
            },
            callback: function (result) {
                if(result){
                    $.ajax({
                        type:'post',
                        url: 'reassign_inspection',
                        data: {
                            inspection_id: $("#inspection_id").val(),
                            requested_id: $("#requested_id").val()
                        },
                        dataType: 'json',
                        success: function(data){
                            if(data.err_code == 0){
                                showAlert("Successfully Reassigned!");
                                setTimeout(function(){
                                    window.location.href = $('#basePath').val() + '/inspection/stucco.html';
                                },700);
                            }else{
                                showAlert('Failed to Reassign!');
                            }
                        },
                        error: function(err){
                            showAlert(Message.SERVER_ERROR);
                        }
                    })

                }

            }
        })

    });


    $("#btn_report").on('click', function(e) {
        e.preventDefault();
        $.fileDownload($("#basePath").val()+"api/export/inspection?id="+$(this).attr('data-id')+"&type=full");
    });

    $("#btn_report_pass").on('click', function(e) {
        e.preventDefault();
        $.fileDownload($("#basePath").val()+"api/export/inspection?id="+$(this).attr('data-id')+"&type=pass");
    });
    
});
