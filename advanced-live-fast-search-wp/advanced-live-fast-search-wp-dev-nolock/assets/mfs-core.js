/**
 * MFS shared search core — data load, match, render helpers.
 * Skin scripts call window.MFS.createEngine(opts).
 */
(function (window, $) {
  "use strict";

  var MFS = (window.MFS = window.MFS || {});

  function getCfg() {
    return window.mfsConfig || {};
  }

  function fetchPayload() {
    var deferred = $.Deferred();
    var c = getCfg();
    var urls = [];
    if (c.file) urls.push(c.file);
    if (c.rest) urls.push(c.rest);
    if (!urls.length) urls.push("/wp-json/mfs/v1/data");

    (function attempt(i) {
      if (i >= urls.length) {
        deferred.reject();
        return;
      }
      $.getJSON(urls[i])
        .done(function (res) {
          if (res && (res.sections || res.terms)) {
            deferred.resolve(res);
          } else {
            attempt(i + 1);
          }
        })
        .fail(function () {
          attempt(i + 1);
        });
    })(0);

    return deferred.promise();
  }

  function buildRegex(query) {
    query = String(query).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    query = query
      .replace(/ی/g, "(ی|ي)")
      .replace(/ي/g, "(ی|ي)")
      .replace(/ک/g, "(ک|ك)")
      .replace(/ك/g, "(ک|ك)")
      .replace(/ا/g, "(ا|آ)")
      .replace(/آ/g, "(ا|آ)");
    return new RegExp(query, "i");
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function defaultItemHtml(value) {
    var img = value.img
      ? '<div class="img"><img src="' + escapeHtml(value.img) + '" rel="noindex" alt="" loading="lazy"></div>'
      : '<div class="img"></div>';
    var price = value.price_html
      ? '<div class="price">' +
        value.price_html +
        (value.sale > 0 ? '<span class="discount">' + escapeHtml(value.sale) + "%</span>" : "") +
        "</div>"
      : "";
    return (
      '<a href="' +
      escapeHtml(value.link) +
      '" class="item">' +
      img +
      '<div class="title">' +
      (value.title || "") +
      "</div>" +
      price +
      "</a>"
    );
  }

  function defaultTermHtml(value) {
    return (
      '<li><a href="' +
      escapeHtml(value.link) +
      '" class="item"><span class="title">' +
      (value.title || "") +
      "</span></a></li>"
    );
  }

  /**
   * @param {object} opts
   * @param {number} [opts.debounce=180]
   * @param {function} [opts.itemHtml]
   * @param {function} [opts.termHtml]
   * @param {string|jQuery} [opts.body] - results root (default #fast-search-body)
   * @param {boolean} [opts.termsListIsUl=false] - if true, .mfs-list itself is the <ul>
   */
  MFS.createEngine = function (opts) {
    opts = opts || {};
    var debounceMs = typeof opts.debounce === "number" ? opts.debounce : 180;
    var itemHtml = opts.itemHtml || defaultItemHtml;
    var termHtml = opts.termHtml || defaultTermHtml;
    var termsListIsUl = !!opts.termsListIsUl;
    var cacheData = null;
    var dataRequest = null;
    var timer = null;

    function $body() {
      return opts.body ? $(opts.body) : $("#fast-search-body");
    }

    function loadData() {
      if (cacheData) {
        return $.Deferred().resolve(cacheData).promise();
      }
      if (!dataRequest) {
        dataRequest = fetchPayload()
          .done(function (res) {
            cacheData = res;
          })
          .always(function () {
            dataRequest = null;
          });
      }
      return dataRequest;
    }

    function renderResults(query) {
      clearTimeout(timer);
      timer = setTimeout(function () {
        var regex = buildRegex(query);
        var found = 0;
        var cfg = getCfg();
        var $root = $body();

        $root.find(".mfs-section").addClass("d-none");

        $.each(cfg.terms || {}, function (key, conf) {
          var n = 0;
          var $section = $root.find('.mfs-section[data-section="' + key + '"]');
          if (!$section.length) return;
          var $list = termsListIsUl
            ? $section.find(".mfs-list").empty()
            : $section.find(".mfs-list ul").empty();
          if (!$list.length) {
            $list = $section.find(".mfs-list").empty();
          }
          var items = (cacheData && cacheData.terms && cacheData.terms[key]) || {};
          $.each(items, function (i, value) {
            if (value.title && value.title.search(regex) !== -1) {
              n++;
              $list.append(termHtml(value));
            }
            return n < (conf.limit || 10);
          });
          if (n) {
            $section.removeClass("d-none");
            found += n;
          }
        });

        $.each(cfg.sections || {}, function (key, conf) {
          var n = 0;
          var $section = $root.find('.mfs-section[data-section="' + key + '"]');
          if (!$section.length) return;
          var $list = $section.find(".mfs-list").empty();
          var items = (cacheData && cacheData.sections && cacheData.sections[key]) || {};
          $.each(items, function (i, value) {
            if (value.title && value.title.search(regex) !== -1) {
              n++;
              $list.append(itemHtml(value));
            }
            return n < (conf.limit || 15);
          });
          if (n) {
            $section.removeClass("d-none");
            found += n;
          }
        });

        if (found) {
          $root.find(".not-found").addClass("d-none").attr("hidden", true);
          $root.find(".is-search").removeClass("d-none").removeAttr("hidden");
        } else {
          $root.find(".is-search").addClass("d-none");
          $root.find(".not-found").removeClass("d-none").removeAttr("hidden");
        }
      }, debounceMs);
    }

    return {
      getCfg: getCfg,
      loadData: loadData,
      renderResults: renderResults,
      buildRegex: buildRegex,
      clearTimer: function () {
        clearTimeout(timer);
      },
      search: function (query) {
        query = $.trim(query || "");
        var $root = $body();
        $root.find(".not-found, .is-search").addClass("d-none");

        if (!query) {
          $root.find(".default").removeClass("d-none");
          return;
        }

        $root.find(".default").addClass("d-none");
        loadData()
          .done(function () {
            renderResults(query);
          })
          .fail(function () {
            $root.find(".not-found").removeClass("d-none").removeAttr("hidden");
          });
      },
    };
  };

  MFS.buildRegex = buildRegex;
  MFS.escapeHtml = escapeHtml;
})(window, jQuery);
