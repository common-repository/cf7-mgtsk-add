jQuery(function ($) {
    $(document).ready(function ($) {
        console.log('I\'m mgtsk+cform WP plugin');
        $('[name="use_mgtsk"]').on('change', function () {
            var tableRow = $(this).parents('tr');
            $.ajax({
                type: "post",
                url: ajaxurl,
                data: {
                    action: 'setWpcf7Custom',
                    post_id: $(this).attr('data-id'),
                    field: 'use_mgtsk',
                    value: $(this).prop('checked')
                },
                success: function (response) {
                    console.log(response);
                    if (response.fields !== undefined) {
                        tableRow.find('.fields.column-fields').html(response.fields);
                    }

                }
            })
        });
    });
}(jQuery));