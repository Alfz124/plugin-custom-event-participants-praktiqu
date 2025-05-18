// File: /woo-event-participants-advanced/woo-event-participants-advanced/assets/js/admin-script.js

jQuery(document).ready(function($) {
    // Handle dynamic behavior for the settings form
    $('#product-selection').change(function() {
        var selectedProduct = $(this).val();
        // Perform AJAX request to get the number of participant forms for the selected product
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_participant_forms',
                product_id: selectedProduct
            },
            success: function(response) {
                $('#participant-forms-container').html(response);
            }
        });
    });

    // Form validation can be added here if needed
});