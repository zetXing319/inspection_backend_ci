function init() {
    if ($("#msg_alert").html() != '') {
        setTimeout(hideAlert, 2000);
    }
}

function scheduling() {
    location.href = $("#basePath").val() + "scheduling/energy";
}
function check_job_number_for_pulte_duct() {
    var v = $("#job_number").val();
    v = v.replace(/_/g, ""); //.replace(/X/g, "");
    showLoading();
    $.ajax({
        type: "POST",
        url: 'check_job_number_for_pulte_duct',
        data: {
            job_number: v
        },
        dataType: 'json',
        success: function (data) {
            hideLoading();
            if (data.exist_ins_inspection == 1) {
                submit_data();
//                showAlert("submit_data");
            } else if (data.exist_ins_building == 1) {
                submit_data();
//                showAlert("submit_data");
            } else {
                showAlert("Building not in Database");

            }
        },
        error: function () {

            hideLoading();
            showAlert(Message.SERVER_ERROR);
            //                $('form').bootstrapValidator('resetForm', false);
        }
    });
}

function submit_data() {

    //var p = $("input[name='field_manager']").val();
    var field_manager = $("#field_manager option:selected").text();
    var field_manager_id = $("#field_manager option:selected").val();
    
    var jur_name = $("#jur_id option:selected").text();
    var jur_id = $("#jur_id option:selected").val();
    
    var data = {
        id: $("#requested_id").val(),
        manager_id: field_manager_id,
        jur_id:jur_id,

        date_requested: $("#date_requested").val(),
        permit_number: $("#permit_number").val(),
        job_number: $("#job_number").val(),
        lot: $("#lot").val(),

        community: $("#community").val(),
        address: $("#address").val(),
        city: $("#city").val(),
        area: $("#area").val(),
        volume: $("#volume").val(),
        wall_area: $("#wall_area").val(),
        ceiling_area: $("#ceiling_area").val(),

        design_location: $("#design_location").val(),
        field_manager: field_manager,
        qn: $("#qn").val(),
        base_ach: $("#base_ach").val(),
        leakage_type: $("#leakage_type").val(),

        document_person: $("#document_person").val(),
        category: 4
    }
    var req_id = $("#requested_id").val();
    var fname = "update_duct_leakage_inspection_requested_pulte";
    if (req_id.length > 0) {
        fname = "update_duct_leakage_inspection_requested2_pulte";
    } else {
        fname = "update_duct_leakage_inspection_requested_pulte";
    }

    // return;

    showLoading();
    $.ajax({
        type: "POST",
        url: fname,
        data: data,
        dataType: 'json',
        success: function (data) {
            hideLoading();
            showAlert(data.err_msg);

            if (data.err_code == 0) {
                setTimeout(scheduling, 700);
            } else {
                $('form').bootstrapValidator('resetForm', false);
            }
        },
        error: function () {
            hideLoading();
            showAlert(Message.SERVER_ERROR);
            $('form').bootstrapValidator('resetForm', false);
        }
    });
}

function check_job_number() {
    var v = $("#job_number").val();
    v = v.replace(/_/g, ""); //.replace(/X/g, "");
    if (v == "" || v.length < 9) {

    } else {
        showLoading();
        $.ajax({
            type: "POST",
            url: 'get_field_manager_list_for_job_number',
            data: {
                job_number: v
            },
            dataType: 'json',
            success: function (data) {

                hideLoading();


                if (data.inspection != null) {
                    $("#lot").val(data.inspection.lot == null ? "" : data.inspection.lot);
                    $("#community").val(data.inspection.community == null ? "" : data.inspection.community);
                    $("#address").val(data.inspection.address == null ? "" : data.inspection.address);
                    $("#city").val(data.inspection.city == null ? "" : data.inspection.city);
                }
                if (data.building != null) {
                    $("#community").val(data.building.community_name == null ? "" : data.building.community_name);
                    $("#address").val(data.building.address == null ? "" : data.building.address);
                    $("#city").val(data.building.city == null ? "" : data.building.city);
                }

                if (data.err_code == 0) {
                    $("#field_manager").html("");
                    $("#field_manager").append('<option value="0">NONE</option>');

                    if (data.fm.has == 1) {
                        $.each(data.fm.list, function (index, row) {
                            $("#field_manager").append('<option ' + (data.fm.manager_id == row.id ? "selected" : "") + ' value="' + row.id + '">' + row.first_name + ' ' + row.last_name + '</option>');
                        });
                    }

                    if (data.inspection.status == 2 && data.inspection.type >= 4) {
                        //
                        showAlert("Job Number Completed before.");
                        $("#lot").val(data.inspection.lot == null ? "" : data.inspection.lot);
//                        $("#community").val(data.inspection.community == null ? "" : data.inspection.community);
                        $("#address").val(data.inspection.address == null ? "" : data.inspection.address);
                        $("#city").val(data.inspection.city == null ? "" : data.inspection.city);
                        $("#permit_number").val(data.inspection.permit_number == null ? "" : data.inspection.permit_number);
                        $("#area").val(data.inspection.area == null ? "" : data.inspection.area);
                        $("#ceiling_area").val(data.inspection.ceiling_area == null ? "" : data.inspection.ceiling_area);
                        $("#design_location").val(data.inspection.design_location == null ? "" : data.inspection.design_location);
                        $("#volume").val(data.inspection.volume == null ? "" : data.inspection.volume);
                        $("#wall_area").val(data.inspection.wall_area == null ? "" : data.inspection.wall_area);
                        $("#qn").val(data.inspection.qn == null ? "" : data.inspection.qn);
                        $("#document_person").val(data.inspection.document_person == null ? "" : data.inspection.document_person);

                        if(data.inspection_requested!=null){
                            var prev = $("#base_ach").val();
                            $("#base_ach").val(data.inspection_requested.base_ach == null ? prev : data.inspection_requested.base_ach);
                        }
                    }


                } else {
                    $("#field_manager").html("");
                    $("#field_manager").append('<option value="0">NONE</option>');
                    showAlert("Job Number Not Found");
                }


            },
            error: function () {

                hideLoading();
                showAlert(Message.SERVER_ERROR);
                //                $('form').bootstrapValidator('resetForm', false);
            }
        });
    }
}

jQuery(document).ready(function () {
    $('.date-picker').datepicker({
        autoclose: true,
        format: 'yyyy-mm-dd'
    });

    $('form').bootstrapValidator({
        feedbackIcons: {
            valid: 'has-success',
            invalid: 'has-error',
            validating: ''
        },
        fields: {
            date_requested: {
                validators: {
                    notEmpty: {
                        message: 'Select the date'
                    },
                }
            },
            permit_number: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Permit Number'
                    },
                }
            },
            job_number: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Job Number'
                    },
//                    greaterThan: {
//                        value: 1,
//                        message: 'Enter the Number greater than 1',
//                    }
                }
            },
            lot: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Lot'
                    },
                    numeric: {
                        message: 'Enter the Number',
                    },
                    greaterThan: {
                        value: 1,
                        message: 'Enter the Number greater than 1',
                    }
                }
            },
            community: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Community'
                    },
                }
            },
            address: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Address'
                    },
                }
            },
            city: {
                validators: {
                    notEmpty: {
                        message: 'Enter the City'
                    },
                }
            },
            area: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Area'
                    },
                    integer: {
                        message: 'Enter the Number',
                    },
                    greaterThan: {
                        value: 1,
                        message: 'Enter the Number greater than 1',
                    }
                }
            },
            volume: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Volume'
                    },
                    integer: {
                        message: 'Enter the Number',
                    },
                    greaterThan: {
                        value: 1,
                        message: 'Enter the Number greater than 1',
                    }
                }
            },
            wall_area: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Wall Area'
                    },
                    integer: {
                        message: 'Enter the Number',
                    },
                    greaterThan: {
                        value: 1,
                        message: 'Enter the Number greater than 1',
                    }
                }
            },
            ceiling_area: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Ceiling Area'
                    },
                    integer: {
                        message: 'Enter the Number',
                    },
                    greaterThan: {
                        value: 1,
                        message: 'Enter the Number greater than 1',
                    }
                }
            },
            // design_location: {
            //     validators: {
            //         notEmpty: {
            //             message: 'Enter the Design Location'
            //         },
            //     }
            // },
            // field_manager: {
            //   validators: {
            //     notEmpty: {
            //       message: 'Enter the Field Manager Email Address'
            //     },
            //     emailAddress: {
            //       message: 'Enter the Valid Email Address'
            //     },
            //   }
            // },
            // browser: {
            //   validators: {
            //     notEmpty: {
            //       message: 'Enter the Field Manager Email Address'
            //     },
            //     emailAddress: {
            //       message: 'Enter the Valid Email Address'
            //     },
            //   }
            // },
            qn: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Volume'
                    },
                    numeric: {
                        message: 'Enter the Number',
                    },
                    greaterThan: {
                        value: 0.01,
                        message: 'Enter the Number greater than 0.01',
                    },
                    lessThan: {
                        value: 0.99,
                        message: 'Enter the Number less than 0.99',
                    }
                }
            },
            base_ach: {
                validators: {
                    notEmpty: {
                        message: 'Enter the Ach50'
                    },
                    numeric: {
                        message: 'Enter the Number',
                    },
                }
            }
        }
    })
            .on('success.field.bv', function (e, data) {
                if (data.bv.isValid()) {
                    data.bv.disableSubmitButtons(false);
                }
            });

    $('form').on('submit', function (e) {
        if (e.isDefaultPrevented()) {
        } else {
            e.preventDefault();

            var v = $("#job_number").val();
            var addr = $("#address").val();
            var lot = $("#lot").val();

            bootbox.confirm({
                title: 'Are you sure?',
                message: 'An Inspection for this lot (' + lot + ') for this address (' + addr + ') for this job number(' + v + ') will be requested.<br>Please confirm:',
                buttons: {
                    'cancel': {
                        label: 'No',
                        className: 'btn-default'
                    },
                    'confirm': {
                        label: 'Yes',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if (result) {
                        check_job_number_for_pulte_duct();
                    } else {
                        $('form').bootstrapValidator('resetForm', false);
                    }
                }
            });
        }
    });

    $('#job_number').change(function (e) {
        check_job_number();
    });

    $('#leakage_type').change(function (e) {
        var leakage_type_id = $("#leakage_type option:selected").val();
        if(leakage_type_id == 0){
            $("#qn").prop('disabled', false);
        }else{
            $("#qn").prop('disabled', true);
            if(leakage_type_id == 1){
                $("#qn").val('0.120');
            }else if(leakage_type_id == 2){
                $("#qn").val('0.030');
            }
        }
    });

    init();
});
