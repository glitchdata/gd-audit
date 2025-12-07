(function($){
    $(document).ready(function(){
        $('#gd-audit-validate-license').on('click', function(){
            var licenseKey = $('#gd-audit-license-key').val();
            var $button = $(this);
            $button.prop('disabled', true).text('Validating...');
            $.post(ajaxurl, {
                action: 'gd_audit_validate_license',
                license_key: licenseKey
            }, function(response){
                alert(response.data ? response.data.message : 'No response from server.');
                $button.prop('disabled', false).text('Validate License');
            }).fail(function(){
                alert('Validation failed. Please try again.');
                $button.prop('disabled', false).text('Validate License');
            });
        });
    });
})(jQuery);
