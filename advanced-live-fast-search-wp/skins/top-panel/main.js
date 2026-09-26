/* Skin: top-panel */
jQuery(function ($) {
  var engine = window.MFS.createEngine({
    debounce: 180,
    termsListIsUl: true,
    termHtml: function (value) {
      return (
        '<li><a href="' +
        window.MFS.escapeHtml(value.link) +
        '" class="mfs-term-chip"><span class="title">' +
        (value.title || "") +
        "</span></a></li>"
      );
    },
    itemHtml: function (value) {
      var img = value.img
        ? '<div class="img"><img src="' +
          window.MFS.escapeHtml(value.img) +
          '" rel="noindex" alt="" loading="lazy"></div>'
        : '<div class="img"></div>';
      var price = value.price_html
        ? '<div class="price">' +
          value.price_html +
          (value.sale > 0
            ? '<span class="discount">' + window.MFS.escapeHtml(value.sale) + "%</span>"
            : "") +
          "</div>"
        : "";
      return (
        '<li><a href="' +
        window.MFS.escapeHtml(value.link) +
        '" class="item">' +
        img +
        '<div class="title">' +
        (value.title || "") +
        "</div>" +
        price +
        "</a></li>"
      );
    },
  });

  function openPanel($panel) {
    $panel.removeAttr("hidden");
    $(document.body).find('[data-mfs-overlay="top-panel"]').removeAttr("hidden");
    requestAnimationFrame(function () {
      $panel.addClass("mfs-open");
    });
    $panel.find("#fast-search-input").trigger("focus");
    $(document.body).addClass("mfs-panel-lock");
    engine.loadData();
  }

  function closePanel($panel) {
    $panel.removeClass("mfs-open");
    $(document.body).removeClass("mfs-panel-lock");
    var $ov = $(document.body).find('[data-mfs-overlay="top-panel"]');
    setTimeout(function () {
      $panel.attr("hidden", "");
      $ov.attr("hidden", "");
    }, 250);
    $panel.find("#fast-search-input").val("");
    engine.search("");
  }

  $(document.body).on("click", ".search-js, .mfs-trigger", function (e) {
    if ($(e.target).closest('[data-mfs="top-panel"]').length) return;
    e.preventDefault();
    var $panel = $('[data-mfs="top-panel"]');
    if (!$panel.length) return;
    if ($panel.is("[hidden]")) {
      openPanel($panel);
    }
  });

  $(document.body).on("click", '[data-mfs="top-panel"] .mfs-panel-close', function () {
    closePanel($('[data-mfs="top-panel"]'));
  });

  $(document.body).on("click", '[data-mfs-overlay="top-panel"]', function () {
    closePanel($('[data-mfs="top-panel"]'));
  });

  $(document).on("keyup", function (e) {
    if (e.key === "Escape") {
      var $panel = $('[data-mfs="top-panel"]');
      if ($panel.length && !$panel.is("[hidden]")) closePanel($panel);
    }
  });

  $(document.body).on("input", '[data-mfs="top-panel"] #fast-search-input', function () {
    engine.search($.trim($(this).val()));
  });

  $(document.body).on("keypress", '[data-mfs="top-panel"] #fast-search-input', function (e) {
    if (e.which === 13) e.preventDefault();
  });
});
