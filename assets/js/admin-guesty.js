jQuery(document).ready(function($) {
    $('#guesty-test-connection').on('click', function(e) {
        console.log('clicked here now')
        e.preventDefault();
        var $btn = $(this);
        var $notice = $('#guesty-test-connection-notice');
        $notice.remove();
        $btn.after('<span id="guesty-test-connection-spinner" class="spinner is-active" style="float:none;display:inline-block;vertical-align:middle;"></span>');
        $.post(guestyApi.ajax_url, {
            action: 'guesty_test_connection',
            _ajax_nonce: guestyApi.nonce
        }, function(response) {
            $('#guesty-test-connection-spinner').remove();
            // Stop the spinner once the response is received
            var cls = response.success ? 'notice-success' : 'notice-error';
            $btn.after('<div id="guesty-test-connection-notice" class="notice ' + cls + ' is-dismissible" style="margin-top:10px;"><p>' + response.data.message + '</p></div>');

            // Ensure spinner is stopped
            $('#guesty-test-connection-spinner').hide();
        });
    });

    $('#fetch-properties-button').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $notice = $('#fetch-properties-notice');
        $notice.remove();
        $btn.after('<span id="fetch-properties-spinner" class="spinner is-active" style="float:none;display:inline-block;vertical-align:middle;"></span>');
        $.post(guestyApi.ajax_url, {
            action: 'guesty_populate_properties',
            _ajax_nonce: guestyApi.nonce
        }, function(response) {
            $('#guesty-properties-spinner').hide();
            var cls = response.success ? 'notice-success' : 'notice-error';
            $btn.after('<div id="fetch-properties-notice" class="notice ' + cls + ' is-dismissible" style="margin-top:10px;"><p>' + response.data.message + '</p></div>');
        });
    });
});
