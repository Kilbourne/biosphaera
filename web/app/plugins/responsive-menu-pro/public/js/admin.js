   jQuery(document).ready(function ($) {

        $('.colourPicker').wpColorPicker( );

        var custom_uploader;

        $('.RMImageButton').click(function (e) {

            e.preventDefault();
            window.imgFor = $(this).attr('for');

            //If the uploader object has already been created, reopen the dialog
            if (custom_uploader) {

                custom_uploader.open();
                return;
            }

            //Extend the wp.media object
            custom_uploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Image',
                button: {
                    text: 'Choose Image',
                    id: 'test'
                },
                multiple: false
            });

            //When a file is selected, grab the URL and set it as the text field's value
            custom_uploader.on('select', function () {

                attachment = custom_uploader.state().get('selection').first().toJSON();

                $('#' + window.imgFor).val(attachment.url);

            });

            //Open the uploader dialog
            custom_uploader.open();

        });

        $('.tab', $('.nav-tabs')).on('click', function () {
            $('.tab', $('.nav-tabs')).each(function () {
                $(this).removeClass('active');
                var hide = $(this).attr('data-show-tab');
                $(hide).hide();
            });

            $(this).addClass('active');
            var show = $(this).attr('data-show-tab');
            $(show).show();
        });
        $('.tab', $('.nav-tabs')).first().trigger('click');


    });


