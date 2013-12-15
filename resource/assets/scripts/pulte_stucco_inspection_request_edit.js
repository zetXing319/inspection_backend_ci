
function request() {
  location.href = "edit_inspection_requested.html";
}
  $("#frm_inspection_request").on('keyup keypress', function(e) {
    var keyCode = e.keyCode || e.which;
    if (keyCode === 13) {
      e.preventDefault();
      return false;
    }
  });
  function logout() {
  location.href = $("#basePath").val() + "user/logout";
  //    $("#frm_logout").submit();
}
jQuery(document).ready(function() {

 
   $('.date-picker').datepicker({
    autoclose: true,
    format: 'yyyy-mm-dd'
  });
  }); 
  $('form').bootstrapValidator({


      feedbackIcons: {
        valid: 'has-success',
        invalid: 'has-error',
        validating: ''
      },
      fields: {
       first_name: {
          validators: {
              notEmpty: {
                  message: 'Enter the Full Name',
              }
          }
        },
       
       email: {
                message: 'The email address is not valid',
                validators: {
                    // Send { email: 'its value', type: 'email' } to the back-end
                    remote: {
                        message: 'The email address is already exist',
                        url: 'emailexists',
                        data: {
                        type: 'POST'
                        }
                    }
                }
            },
            address: {
                validators: {
                    notEmpty: {
                        message: 'Enter the address'
                    },
                }
            },
            cell_phone: {
                validators: {
                    notEmpty: {
                        message: 'Enter the phone number'
                    },
                }
            },
            
         job_number: {
                message: 'The job number is not valid',
                validators: {
                   remote: {
                        message: 'The job number is already exist',
                        url: 'jobnumber_exists',
                        data: {
                        type: 'POST'
                        }
                    }
                }
            },

            community_name: {
                validators: {
                    notEmpty: {
                        message: 'Enter the community name'
                    },
                }
            },
             city: {
                validators: {
                    notEmpty: {
                        message: 'Enter the city name'
                    },
                }
            },
             zip: {
                validators: {
                    notEmpty: {
                        message: 'Enter the zip code'
                    },
                    stringLength: {
                        min: 3,
                        max: 6,
                        message: 'The zip must be more than 3 and less than 6 characters long'
                    },
                }
            },
             state: {
                validators: {
                    notEmpty: {
                        message: 'Enter the state'
                    },
                }
            },
             userfile: {
                validators: {
                    notEmpty: {
                        message: 'Enter the upload file'
                    },
                }
            },
            access_instructions: {
                validators: {
                    notEmpty: {
                        message: 'Enter the access instructions'
                    },
                }
            }
      }
    })
    .on('success.field.bv', function(e, data) {
      if (data.bv.isValid()) {
        data.bv.disableSubmitButtons(false);
        
      }
    });


