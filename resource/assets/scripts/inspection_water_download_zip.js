$('#downloadZip').on('click', function () {
    var communityID = $('#communityID').val();
    if (communityID && communityID.length) {
        showLoading();

        $.ajax({
            type: "GET",
            url: $("#basePath").val() + 'api/create_zip/' + communityID,
            dataType: 'json',
            success: function (data) {
                hideLoading();
                if (data) {
                    if (data.code == 0) {
                        if (data.drainage_count > 0) {
                            window.open($("#basePath").val() + data.zip_url_1, '_blank');
                        }
                        if (data.lath_count > 0) {
                            window.open($("#basePath").val() + data.zip_url_2, '_blank');
                        }
                        showAlert("Successfully Downloaded, Drainage Plan: " + data.drainage_count + "    Lath: " + data.lath_count);
                    } else {
                        showAlert(data.message);
                    }
                } else {
                    showAlert('Generating Zip Failed');
                }
            },
            error: function () {
                hideLoading();
                showAlert(Message.SERVER_ERROR);
            },
            timeout: 864000000
        });
    } else {
        showAlert('Please input community id.');
    }
});