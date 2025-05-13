jQuery(document).ready(function($) {
    $(document).on('click', '.editinline', function() {
        var post_id = $(this).closest('tr').attr('id').replace('post-', '');

        // دریافت قیمت عمده قبلی
        var wholesale_price = $('#wholesale_price_' + post_id).text();

        // انتقال قیمت عمده قبلی به فیلد Quick Edit
        $('input[name="_wholesale_price"]').val(wholesale_price);
    });
});