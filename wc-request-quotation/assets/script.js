jQuery(function ($) {
  const $notice = $("#noticFormsMy");

  /** شناسه وریشن معتبر (۰ و رشتهٔ خالی = محصول ساده). */
  function parseVariationId(val) {
    const id = parseInt(String(val ?? "").replace(/\D/g, ""), 10);
    return id > 0 ? id : 0;
  }

  if (typeof wc_rq_ajax !== "undefined" && wc_rq_ajax.support_label) {
    $("#wc-rq-support-label").text(wc_rq_ajax.support_label);
  }

  // تابع نمایش پیام خطا
  function showError(msg) {
    $notice.text(msg).css({
      color: "red",
      "font-size": "14px",
      "margin-top": "5px",
    });
  }

  /** قبل از باز شدن پاپ‌آپ از مودال RSC — قیمت جمع نهایی است، نه data-price جدول. */
  $(document).on("click", ".rsc-vt-modal__quotation", function () {
    $("#wc-rq-price-from-vartable").val("0");
  });

  // فقط اعداد برای تلفن و کد ملی / اقتصادی
  $("#wc-rq-phone, #wc-rq-national-id").on("input", function () {
    this.value = this.value.replace(/\D/g, "");
  });

  // اعتبارسنجی طول شماره تماس
  $("#wc-rq-phone").on("blur", function () {
    if (this.value.length < 11) {
      showError("شماره تماس باید حداقل ۱۱ رقم باشد");
      this.focus();
    } else {
      $notice.text("");
    }
  });

  // وقتی روی دکمه درخواست پیش‌فاکتور کلیک شد
  $(".wc-rq-btn").on("click", function () {
    const $form = $(this).closest("form");
    const variationId = parseVariationId(
      $form.find('input[name="variation_id"]').val()
    );
    const productId =
      parseInt(
        String(
          $form.find('input[name="product_id"]').val() || $(this).data("id")
        ).replace(/\D/g, ""),
        10
      ) || 0;
    $("#wc-rq-is-variable").val(variationId > 0 ? 1 : 0);

    const $row = $(this).closest("tr");
    let rawPrice = $(this).data("price");
    let fromVartable = 0;
    if ($row.length) {
      const rowPrice = $row.data("price");
      if (rowPrice !== undefined && rowPrice !== null && rowPrice !== "") {
        rawPrice = rowPrice;
        if (variationId > 0) {
          fromVartable = 1;
        }
      }
    }
    const baseName = $(this).data("name");

    let fullName = baseName;
    const attributesInput = $form.find(
      'input[name="form_vartable_attribute_json"]'
    );

    if (attributesInput.length && attributesInput.val()) {
      try {
        const attrs = JSON.parse(attributesInput.val());
        const attributesText = Object.entries(attrs)
          .map(([, val]) => val)
          .join(" | ");
        fullName = `${baseName} (${attributesText})`;
      } catch (e) {
        console.warn("Invalid attributes JSON");
      }
    }

    // ذخیره شناسه‌ها و قیمت — منبع: جدول/دکمه وریشن (نیاز به اصلاح /10 در صورت لازم)
    $("#wc-rq-product-id")
      .val(variationId > 0 ? variationId : productId)
      .data("original-id", productId)
      .data("variation-id", variationId > 0 ? variationId : "");
    $("#wc-rq-product-name").val(fullName);
    $("#wc-rq-product-price").val(rawPrice);
    $("#wc-rq-price-from-vartable").val(String(fromVartable));
    $("#wc-rq-qty").val(1);
    $("#wc-rq-needs-support").prop("checked", false);

    $("#wc-rq-popup-overlay").fadeIn();
  });

  $("#wc-rq-cancel").on("click", function () {
    $("#wc-rq-popup-overlay").fadeOut();
  });

  // دکمه + تعداد
  $(document).on("click", "#wc-rq-qty-plus", function () {
    let qty = parseInt($("#wc-rq-qty").val()) || 1;
    $("#wc-rq-qty").val(qty + 1);
  });

  // دکمه - تعداد
  $(document).on("click", "#wc-rq-qty-minus", function () {
    let qty = parseInt($("#wc-rq-qty").val()) || 1;
    if (qty > 1) {
      $("#wc-rq-qty").val(qty - 1);
    }
  });

  // ارسال فرم
  $("#wc-rq-submit").on("click", function (e) {
    e.preventDefault();

    const name = $("#wc-rq-name").val().trim();
    const phone = $("#wc-rq-phone").val().trim();
    const company = $("#wc-rq-company").val().trim();
    const national_id = $("#wc-rq-national-id").val().trim();
    const qty = parseInt($("#wc-rq-qty").val(), 10) || 1;
    const product_id = $("#wc-rq-product-id").val();
    const product_name = $("#wc-rq-product-name").val();
    const is_variable = parseInt($("#wc-rq-is-variable").val(), 10) || 0;
    const needs_support = $("#wc-rq-needs-support").is(":checked") ? 1 : 0;

    let raw_price = $("#wc-rq-product-price").val();
    let clean_price = parseInt(String(raw_price).replace(/[^\d]/g, ""), 10);

    const priceFromVartable =
      String($("#wc-rq-price-from-vartable").val()) === "1";

    // فقط قیمت data-price جدول وریشن — نه محصول ساده و نه جمع RSC
    if (priceFromVartable && clean_price > 1000 && clean_price % 10 === 0) {
      clean_price = clean_price / 10;
    }

    // اعتبارسنجی
    if (!name || !phone) {
      showError("لطفاً همه فیلدهای ضروری را تکمیل کنید.");
      return;
    }
    if (phone.length < 11) {
      showError("شماره تماس باید حداقل ۱۱ رقم باشد");
      return;
    }
    if (!clean_price || clean_price <= 0) {
      showError("قیمت محصول معتبر نیست. صفحه را رفرش کنید.");
      return;
    }

    $notice.text("");
    $("#wc-rq-submit").prop("disabled", true).text("در حال ارسال...");

    $.post(
      wc_rq_ajax.ajax_url,
      {
        action: "wc_rq_submit_form",
        nonce: wc_rq_ajax.nonce,
        name,
        phone,
        company,
        national_id,
        is_variable,
        needs_support,
        qty,
        product_id,
        product_name,
        product_price: clean_price,
        price_from_vartable: priceFromVartable ? 1 : 0,
        website: jQuery("#wc-rq-website").val(),
      },
      function (response) {
        if (response.success) {
          $notice.css("color", "green").text("درخواست با موفقیت ثبت شد.");
          if (response.data?.pdf_url) {
            window.open(response.data.pdf_url, "_blank");
          }
          $("#wc-rq-popup-overlay").fadeOut();
        } else {
          showError(response.data?.message || "ارسال ناموفق.");
        }
        $("#wc-rq-submit").prop("disabled", false).html(
          '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15.238V3.213" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="m7.375 10.994 3.966 3.966a.937.937 0 0 0 1.318 0l3.966-3.966" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.75 13.85v4.625a2.313 2.313 0 0 0 2.313 2.313h13.874a2.313 2.313 0 0 0 2.313-2.313V13.85" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg> ثبت درخواست و دریافت پیش‌فاکتور'
        );
      }
    ).fail(function (xhr) {
      let msg = "خطای سرور یا اتصال.";
      if (xhr && xhr.responseText) {
        try {
          const parsed = JSON.parse(xhr.responseText);
          if (parsed.data && parsed.data.message) {
            msg = parsed.data.message;
          }
        } catch (e) {
          /* پاسخ غیر JSON */
        }
      }
      showError(msg);
      $("#wc-rq-submit").prop("disabled", false).html(
        '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15.238V3.213" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"/><path d="m7.375 10.994 3.966 3.966a.937.937 0 0 0 1.318 0l3.966-3.966" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.75 13.85v4.625a2.313 2.313 0 0 0 2.313 2.313h13.874a2.313 2.313 0 0 0 2.313-2.313V13.85" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg> ثبت درخواست و دریافت پیش‌فاکتور'
      );
    });
  });
});
