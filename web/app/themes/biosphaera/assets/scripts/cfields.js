(function($) {
    function cfieldsRequired() {
        return $('#billing_invoice_type').val() === 'Fattura';
    }

    function getCfields() {
        return $('#billing_company_field,#billing_company_country_field,#billing_company_city_field,#billing_company_state_field,#billing_company_postcode_field,#billing_company_address_field')
    }

    function handleCfield() {
        var fields = getCfields();
        if (cfieldsRequired()) {
            fields.show(function() {
                var label = $(this).children('label');
                if (label && !label.find('abbr.required')) label.append(' <abbr class="required" title="required">*</abbr>');
                $(this).addClass("validate-required");
                $(this).find('input').val("");
            });
        } else {
            fields.hide(function() {
                $(this).removeClass("validate-required");
                $(this).removeClass("woocommerce-validated");
                $(this).find('input').val("no");
            });
        }
    }
    handleCfield();
    $('#billing_invoice_type').change(handleCfield);
})(window.jQuery);
