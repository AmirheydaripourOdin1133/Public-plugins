jQuery(function ($) {
  var STORAGE_KEY = "mfs-active-tab";

  function activateTab(id) {
    var $tab = $('.mfs-tabs .mfs-tab[data-tab="' + id + '"]');
    if (!$tab.length) return;

    $(".mfs-tabs .mfs-tab").removeClass("is-active");
    $tab.addClass("is-active");

    $(".mfs-panel").removeClass("is-active");
    $("#mfs-tab-" + id).addClass("is-active");

    $(".mfs-savebar").toggleClass("is-hidden", id === "help");
  }

  $(".mfs-tabs").on("click", ".mfs-tab", function (e) {
    e.preventDefault();
    var id = $(this).data("tab");
    activateTab(id);
    window.localStorage && localStorage.setItem(STORAGE_KEY, id);
    if (window.history && window.history.replaceState) {
      history.replaceState(null, "", "#" + id);
    }
  });

  (function () {
    var hash = window.location.hash.replace("#", "");
    var saved = window.localStorage ? localStorage.getItem(STORAGE_KEY) : null;
    if (hash && $('.mfs-tabs .mfs-tab[data-tab="' + hash + '"]').length) {
      activateTab(hash);
    } else if (saved) {
      activateTab(saved);
    }
  })();

  function request(route, $button) {
    $button.prop("disabled", true);
    $(".mfs-wrap .spinner").addClass("is-active");

    $.ajax({
      url: mfsAdmin.rest + route,
      method: "POST",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", mfsAdmin.nonce);
      }
    })
      .done(function () {
        window.location.reload();
      })
      .fail(function () {
        $button.prop("disabled", false);
        $(".mfs-wrap .spinner").removeClass("is-active");
        window.alert(mfsAdmin.error);
      });
  }

  $("#mfs-rebuild").on("click", function () {
    request("/rebuild", $(this));
  });

  $("#mfs-clear").on("click", function () {
    if (window.confirm("کش جستجو پاک شود؟")) {
      request("/clear", $(this));
    }
  });

  $("#mfs-copy-shortcode").on("click", function () {
    var code = $("#mfs-shortcode-code").text();
    var $btn = $(this);
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(code).then(function () {
        $btn.text("کپی شد");
        setTimeout(function () {
          $btn.text("کپی");
        }, 1500);
      });
    }
  });
});
