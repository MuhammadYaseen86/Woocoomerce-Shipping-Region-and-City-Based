jQuery(document).ready(function($) {
    
    if( $("select#billing_city") ) {
    		$("select#billing_city").trigger("change",true);
    		$("select#billing_city").select2();
    }
    
    $('select#billing_state, select#billing_city').change(function() {
        var selected_state = $('select#billing_state').val();
        var selected_city = $('select#billing_city').val();
        // var country = $(this).closest('form').find('input[name="shipping_country"]').val();
        var country = $('select#billing_country').val();
        console.log('Selected State: ' + selected_state);
        console.log('Selected City: ' + selected_city);
        console.log('Country: ' + country);
    
        // Perform an AJAX request to get the updated shipping cost based on the selected state
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajax_url,
            data: {
                action: 'get_updated_shipping_cost',
                selected_state: selected_state,
                selected_city: selected_city,
                country: country,
            },
            success: function(response) {
                // Update the shipping cost on the checkout page
                console.log('Updated Shipping Cost: ' + response.updated_shipping_cost);
                $('.shipping__list_item .woocommerce-Price-amount bdi').html(response.updated_shipping_cost);
                $('body').trigger('update_checkout');
            }
        });
    });

});
