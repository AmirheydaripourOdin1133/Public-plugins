jQuery(function ($) {
  var $called = $('input[name="rq_support_called"]');
  var $wrap = $(".rq-support-report-wrap");

  function toggleReport() {
    if (!$wrap.length) {
      return;
    }
    if ($called.is(":checked")) {
      $wrap.removeClass("rq-support-report-wrap--hidden").slideDown(150);
    } else {
      $wrap.addClass("rq-support-report-wrap--hidden").slideUp(150);
    }
  }

  $called.on("change", toggleReport);
  toggleReport();
});
