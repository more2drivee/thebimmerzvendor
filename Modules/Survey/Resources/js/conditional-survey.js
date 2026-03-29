$(document).ready(function(){
    // Load surveys on modal open
    $('#conditional_survey_modal').on('show.bs.modal', function(){
        var url = $(this).data('url-active-surveys');
        $.ajax({
            url: url,
            method: 'GET',
            success: function(response){
                $('#survey_select').empty().append('<option value="">' + $('#conditional_survey_modal').data('lang-select-survey') + '</option>');
                $.each(response.surveys, function(index, survey){
                    $('#survey_select').append('<option value="' + survey.id + '">' + survey.title + '</option>');
                });
            }
        });
    });

    // Load contacts based on condition
    $('#condition_select').on('change', function(){
        var condition = $(this).val();
        if(!condition) return;

        var url = $('#conditional_survey_modal').data('url-conditional-contacts');
        var csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: csrfToken,
                condition: condition
            },
            success: function(response){
                $('#contacts_count').text(response.count);
                $('#contacts_preview').show();
            },
            error: function(){
                toastr.error($('#conditional_survey_modal').data('lang-something-went-wrong'));
            }
        });
    });

    // Send conditional survey
    $('#send_conditional_survey').on('click', function(){
        var condition = $('#condition_select').val();
        var surveyId = $('#survey_select').val();
        var channel = $('#channel_select').val();

        if(!condition || !surveyId || !channel){
            toastr.error($('#conditional_survey_modal').data('lang-please-fill-all-fields'));
            return;
        }

        swal({
            title: $('#conditional_survey_modal').data('lang-are-you-sure'),
            text: $('#conditional_survey_modal').data('lang-send-survey-to-contacts'),
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(function(value){
            if(!value) return;

            var url = $('#conditional_survey_modal').data('url-send-conditional');
            var csrfToken = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: csrfToken,
                    condition: condition,
                    survey_id: surveyId,
                    channel: channel
                },
                success: function(response){
                    if(response.success){
                        toastr.success(response.message);
                        $('#conditional_survey_modal').modal('hide');
                    } else {
                        toastr.error(response.message || $('#conditional_survey_modal').data('lang-something-went-wrong'));
                    }
                },
                error: function(xhr){
                    var msg = $('#conditional_survey_modal').data('lang-something-went-wrong');
                    if(xhr.responseJSON && xhr.responseJSON.message){
                        msg = xhr.responseJSON.message;
                    }
                    toastr.error(msg);
                }
            });
        });
    });
});
