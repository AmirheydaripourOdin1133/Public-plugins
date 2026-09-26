/* Skin: split-panel */
jQuery(function ($) {
  var $root = $('[data-mfs="split-panel"]');
  if (!$root.length) return;

  var $panel = $root.find("#fast-search-body");
  var $overlay = $('[data-mfs-overlay="split-panel"]');
  var $input = $root.find("#fast-search-input");
  var $clear = $root.find(".mfs-sp__clear");
  var esc = window.MFS.escapeHtml;

  var engine = window.MFS.createEngine({
    debounce: 180,
    body: $panel,
    termsListIsUl: true,
    termHtml: function (value) {
      return (
        '<li><a href="' +
        esc(value.link) +
        '" class="item"><span class="title">' +
        (value.title || "") +
        "</span></a></li>"
      );
    },
    itemHtml: function (value) {
      var img = value.img
        ? '<div class="img"><img src="' +
          esc(value.img) +
          '" rel="noindex" alt="" loading="lazy"></div>'
        : '<div class="img"></div>';
      var price = value.price_html
        ? '<div class="price">' +
          value.price_html +
          (value.sale > 0
            ? '<span class="discount">' + esc(value.sale) + "%</span>"
            : "") +
          "</div>"
        : "";
      return (
        '<a href="' +
        esc(value.link) +
        '" class="item">' +
        img +
        '<div class="title">' +
        (value.title || "") +
        "</div>" +
        price +
        "</a>"
      );
    },
  });

  function syncViewAll(query) {
    var q = $.trim(query || "");
    var href = q
      ? (window.location.origin || "") + "/?s=" + encodeURIComponent(q)
      : (window.location.origin || "") + "/";
    $panel.find("[data-mfs-view-all]").attr("href", href);
  }

  if ($overlay.length && $overlay.parent()[0] !== document.body) {
    $overlay.appendTo(document.body);
  }

  var $bumped = $();

  function bumpStack() {
    releaseStack();
    $root.add($root.parentsUntil("body")).each(function () {
      var cs = window.getComputedStyle(this);
      var needs =
        cs.position !== "static" ||
        this === $root[0] ||
        $(this).is("header, .header, .site-header, #header, .main-header, .sticky-header");
      if (!needs) return;
      var $el = $(this);
      if (!$el.attr("data-mfs-z")) {
        $el.attr("data-mfs-z", this.style.zIndex || "");
      }
      $el.addClass("mfs-sp-stack");
      $bumped = $bumped.add($el);
    });
  }

  function releaseStack() {
    $bumped.each(function () {
      var $el = $(this);
      var prev = $el.attr("data-mfs-z");
      $el.removeClass("mfs-sp-stack").removeAttr("data-mfs-z");
      if (prev === "") {
        this.style.zIndex = "";
      } else if (typeof prev !== "undefined" && prev !== null) {
        this.style.zIndex = prev;
      }
    });
    $bumped = $();
  }

  function openPanel() {
    if ($root.hasClass("is-open")) return;
    $root.addClass("is-open");
    bumpStack();
    $panel.removeAttr("hidden");
    $overlay.removeAttr("hidden");
    $input.attr("aria-expanded", "true");
    $(document.body).addClass("mfs-sp-lock");
    requestAnimationFrame(function () {
      $panel.addClass("is-open");
      $overlay.addClass("is-visible");
    });
    engine.loadData();
  }

  function closePanel() {
    if (!$root.hasClass("is-open")) return;
    $root.removeClass("is-open");
    $panel.removeClass("is-open");
    $overlay.removeClass("is-visible");
    $input.attr("aria-expanded", "false");
    $(document.body).removeClass("mfs-sp-lock");
    releaseStack();
    setTimeout(function () {
      if (!$root.hasClass("is-open")) {
        $panel.attr("hidden", "");
        $overlay.attr("hidden", "");
      }
    }, 250);
  }

  function resetSearch() {
    $input.val("");
    $clear.addClass("d-none");
    syncViewAll("");
    engine.search("");
  }

  $input.on("focus click", function () {
    openPanel();
  });

  $input.on("input", function () {
    var q = $.trim($(this).val());
    $clear.toggleClass("d-none", !q);
    openPanel();
    syncViewAll(q);
    engine.search(q);
  });

  $clear.on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();
    resetSearch();
    $input.trigger("focus");
  });

  $root.find(".mfs-sp__icon-search").on("click", function () {
    $root.find("#search-by-json-form").trigger("submit");
  });

  $overlay.on("click", function () {
    closePanel();
    resetSearch();
  });

  /* فقط کلیک بیرون از ویجت ببندد؛ اینپوت و پنل همیشه داخل root هستند */
  $(document).on("mousedown", function (e) {
    if (!$root.hasClass("is-open")) return;
    if ($root[0].contains(e.target)) return;
    if ($(e.target).closest("[data-mfs-overlay='split-panel']").length) return;
    closePanel();
  });

  $(document).on("keyup", function (e) {
    if (e.key === "Escape" && $root.hasClass("is-open")) {
      closePanel();
      resetSearch();
      $input.trigger("blur");
    }
  });

  $root.find("#search-by-json-form").on("submit", function (e) {
    var q = $.trim($input.val());
    if (!q) {
      e.preventDefault();
      openPanel();
    }
  });
});
