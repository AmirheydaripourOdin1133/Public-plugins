jQuery(document).ready(function ($) {
  $(".edit-wholesale-price").on("click", function (e) {
    e.preventDefault();
    var $form = $(this).siblings(".wholesale-price-form");
    if ($form.is(":visible")) {
      $form.hide();
    } else {
      $form.show();
    }
  });

  $(".wholesale-price-form").on("submit", function (e) {
    e.preventDefault();
    var $form = $(this);
    var productId = $form.find('input[name="product_id"]').val();
    var newWholesalePrice = $form
      .find('input[name="new_wholesale_price"]')
      .val();

    $.ajax({
      url: wp_wholesale_edit.ajax_url,
      type: "POST",
      data: {
        action: "save_wholesale_price",
        nonce: wp_wholesale_edit.nonce,
        product_id: productId,
        new_wholesale_price: newWholesalePrice,
      },
      success: function (response) {
        if (response.success) {
          alert(response.data.message);
          $form.siblings(".wholesale-price").text(newWholesalePrice);
          $form.hide();
          $form
            .closest("tr")
            .find(".last-updated")
            .text(response.data.last_updated);
        } else {
          alert(response.data);
        }
      },
    });
  });
});
