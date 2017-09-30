jQuery(document).ready(function ($) {

    if ($('#billing_country').length) {
        if ($('#billing_country').val() == "IT") {
            $('#billing_CF_field').show().addClass('validate-required');
            $('#billing_iva_field').show();
        }

        $("#billing_country").click(function () {
            $(this).select();
        });

        $('#billing_country').on('select', function () {

            //e.preventDefault();

           // alert();

            var country = $(this).val();
            if (country == 'IT')
                $('#billing_CF_field').find('abbr').show();
            else
                $('#billing_CF_field').find('abbr').hide();

            var data = {
                action: 'set_woocf',
                country: country,
            }

            jQuery.post(woocf_params.ajaxurl, data, function (response) {
                //console.log(response)

                if (response != "IT") {

                    $('#billing_CF_field').hide().removeClass('validate-required');
                    $('#billing_iva_field').hide();
                }
                else {
                    $('#billing_CF_field').show().addClass('validate-required');
                    $('#billing_iva_field').show();
                }

            })

        })

    }

})