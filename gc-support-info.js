jQuery(document).ready(function($) {
    // Add hover effects to buttons
    $('#gc-refresh-info, #gc-export-info').hover(
        function() {
            $(this).css('background-color', '#005a87');
        },
        function() {
            if (!$(this).prop('disabled')) {
                $(this).css('background-color', '#0073aa');
            }
        }
    );
    
    // Refresh button click handler
    $('#gc-refresh-info').on('click', function() {
        var button = $(this);
        var contentDiv = $('#gc-support-info-content');
        
        // Disable button and show loading state
        button.prop('disabled', true).text('Refreshing...').css('background-color', '#cccccc');
        contentDiv.css('opacity', '0.6');
        
        // Make AJAX request
        $.ajax({
            url: gc_support_info_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gc_refresh_support_info',
                nonce: gc_support_info_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    contentDiv.html(response.data);
                    contentDiv.css('opacity', '1');
                    // Update the timestamp
                    var now = new Date();
                    var timestamp = now.toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'}) + ' ' + 
                                  now.toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                    $('#gc-support-info-widget').find('p:last').text('Last refreshed: ' + timestamp);
                } else {
                    alert('Error refreshing data: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error refreshing data. Please try again.');
            },
            complete: function() {
                // Re-enable button and restore text
                button.prop('disabled', false).text('Refresh').css('background-color', '#0073aa');
                contentDiv.css('opacity', '1');
            }
        });
    });
    
    // Export JSON button click handler
    $('#gc-export-info').on('click', function() {
        var button = $(this);
        
        // Disable button and show loading state
        button.prop('disabled', true).text('Exporting...').css('background-color', '#cccccc');
        
        // Make AJAX request
        $.ajax({
            url: gc_support_info_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'gc_export_support_info',
                nonce: gc_support_info_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create and download JSON file
                    var data = JSON.stringify(response.data, null, 2);
                    var blob = new Blob([data], {type: 'application/json'});
                    var url = window.URL.createObjectURL(blob);
                    
                    // Create download link
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = 'support-info-' + new Date().toISOString().split('T')[0] + '.json';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                } else {
                    alert('Error exporting data: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error exporting data. Please try again.');
            },
            complete: function() {
                // Re-enable button and restore text
                button.prop('disabled', false).text('Export JSON').css('background-color', '#0073aa');
            }
        });
    });
});