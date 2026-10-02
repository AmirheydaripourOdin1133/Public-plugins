jQuery(function ($) {
  function modalItemHtml(value) {
    var img = value.img
      ? '<div class="img"><img src="' +
        String(value.img).replace(/"/g, "&quot;") +
        '" alt="" loading="lazy"></div>'
      : '<div class="img"></div>';
    var price = value.price_html
      ? '<div class="price">' +
        value.price_html +
        (value.sale > 0 ? '<span class="discount">' + value.sale + "%</span>" : "") +
        "</div>"
      : "";

    return (
      '<a href="' +
      String(value.link || "#").replace(/"/g, "&quot;") +
      '" class="item">' +
      img +
      '<div class="mfs-item-body"><div class="title">' +
      (value.title || "") +
      "</div>" +
      price +
      "</div></a>"
    );
  }

  var engine = window.MFS.createEngine({ debounce: 180, itemHtml: modalItemHtml });

  function openModal() {
    $(".search-body-box").addClass("open");
    $("#fast-search-body").removeClass("fast-hide").addClass("fast-show");
    setTimeout(function () {
      $("#fast-search-input").trigger("focus");
    }, 50);
  }

  function closeModal() {
    $("#fast-search-body").removeClass("fast-show").addClass("fast-hide");
    $(".search-body-box").removeClass("open");
    $("#fast-search-input").val("");
    engine.search("");
  }

  $(document.body).on("input", "#fast-search-input", function () {
    var query = $.trim($(this).val());
    $("#search-by-json .input .empty").toggleClass("d-none", !query);
    engine.search(query);
  });

  $(document.body).on("click", "#search-by-json .input .empty", function () {
    $("#fast-search-input").val("").trigger("input");
  });

  $(document.body).on("keypress", "#fast-search-input", function (e) {
    if (e.which === 13) e.preventDefault();
  });

  $(document.body).on("click", ".btn-search.search-js, .search-js, .mfs-trigger", function (e) {
    if ($(e.target).closest(".search-body-box").length) return;
    e.preventDefault();
    openModal();
  });

  $(document.body).on("click", ".search-body-box .close", function () {
    closeModal();
  });

  $(document).on("keyup", function (e) {
    if (e.key === "Escape" && $(".search-body-box.open").length) {
      closeModal();
    }
  });

  $(document).on("click", function (e) {
    if (!$(".search-body-box.open").length) return;
    var box = document.getElementById("search-by-json");
    if (box && box.contains(e.target)) return;
    if ($(e.target).closest(".search-js, .mfs-trigger, .search-body-box .close").length) return;
    if ($(e.target).closest(".search-body-box").length && !$(e.target).closest("#search-by-json").length) {
      closeModal();
    }
  });
});
