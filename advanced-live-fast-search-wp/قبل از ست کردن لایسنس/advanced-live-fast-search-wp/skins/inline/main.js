jQuery(function ($) {
  var engine = window.MFS.createEngine({ debounce: 180 });
  var $form = $('#search-by-json-form[data-mfs="inline"]');
  var $box = $("#search-by-json");
  var $overlay = $('[data-mfs-overlay="inline"]');
  var $bumped = $();

  if (!$form.length) return;

  /* Overlay روی body تا زیر هدر/overflow گیر نکند و تیره دیده شود */
  if ($overlay.length && $overlay.parent()[0] !== document.body) {
    $overlay.appendTo(document.body);
  }

  function bumpStack() {
    releaseStack();
    $form.add($form.parentsUntil("body")).each(function () {
      var cs = window.getComputedStyle(this);
      var needs =
        cs.position !== "static" ||
        this === $form[0] ||
        $(this).is("header, .header, .site-header, #header, .main-header, .sticky-header");
      if (!needs) return;
      var $el = $(this);
      if (!$el.attr("data-mfs-z")) {
        $el.attr("data-mfs-z", this.style.zIndex || "");
      }
      $el.addClass("mfs-inline-stack");
      $bumped = $bumped.add($el);
    });
  }

  function releaseStack() {
    $bumped.each(function () {
      var $el = $(this);
      var prev = $el.attr("data-mfs-z");
      $el.removeClass("mfs-inline-stack").removeAttr("data-mfs-z");
      if (prev === "") {
        this.style.zIndex = "";
      } else if (typeof prev !== "undefined" && prev !== null) {
        this.style.zIndex = prev;
      }
    });
    $bumped = $();
  }

  function openBody() {
    $form.addClass("mfs-inline-open");
    bumpStack();
    $("#fast-search-body").removeClass("fast-hide").addClass("fast-show");
    $overlay.removeAttr("hidden").addClass("showOverlay");
  }

  function closeBody() {
    $form.removeClass("mfs-inline-open");
    $("#fast-search-body").removeClass("fast-show").addClass("fast-hide");
    $overlay.removeClass("showOverlay");
    releaseStack();
    setTimeout(function () {
      if (!$overlay.hasClass("showOverlay")) {
        $overlay.attr("hidden", "");
      }
    }, 300);
  }

  $(document.body).on(
    "input focus",
    "#search-by-json-form[data-mfs='inline'] #fast-search-input",
    function () {
      var query = $.trim($(this).val());
      $box.find(".input .empty").toggleClass("d-none", !query);
      openBody();
      engine.search(query);
    }
  );

  $(document.body).on("click", "#search-by-json-form[data-mfs='inline'] .input .empty", function () {
    $form.find("#fast-search-input").val("").trigger("input");
  });

  $(document.body).on("click", "#search-by-json-form[data-mfs='inline'] .input .icon", function () {
    $form.trigger("submit");
  });

  $(document.body).on("click", '[data-mfs-overlay="inline"]', function () {
    closeBody();
    $form.find("#fast-search-input").val("");
    engine.search("");
  });

  $(document).on("mousedown", function (e) {
    if (!$form.hasClass("mfs-inline-open")) return;
    if ($box[0] && $box[0].contains(e.target)) return;
    if ($(e.target).closest('[data-mfs-overlay="inline"]').length) return;
    closeBody();
  });

  $(document).on("keyup", function (e) {
    if (e.key === "Escape") {
      closeBody();
    }
  });
});
