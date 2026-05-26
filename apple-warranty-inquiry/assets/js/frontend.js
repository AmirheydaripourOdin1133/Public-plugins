(function () {
  'use strict';

  if (typeof appleWarranty === 'undefined') {
    return;
  }

  var NOTICE_AUTO_HIDE_MS = 5000;
  var NOTICE_ANIMATION_MS = 280;

  var form = document.getElementById('awi-form');
  if (!form) {
    return;
  }

  var serialInput = document.getElementById('awi-serial');
  var captchaInput = document.getElementById('awi-captcha');
  var captchaToken = document.getElementById('awi-captcha-token');
  var captchaImg = document.getElementById('awi-captcha-img');
  var refreshBtn = document.getElementById('awi-captcha-refresh');
  var submitBtn = document.getElementById('awi-submit');
  var btnLabel = submitBtn ? submitBtn.querySelector('.awi-btn__label') : null;
  var btnIcon = submitBtn ? submitBtn.querySelector('.awi-btn__icon') : null;
  var noticeEl = document.getElementById('awi-notice');
  var noticeText = document.getElementById('awi-notice-text');
  var noticeClose = document.getElementById('awi-notice-close');
  var resultWrap = document.getElementById('awi-result');
  var noticeTimer = null;
  var noticeHideTimer = null;

  function hideNotice() {
    if (!noticeEl) {
      return;
    }

    clearTimeout(noticeTimer);
    clearTimeout(noticeHideTimer);
    noticeTimer = null;
    noticeEl.classList.remove('awi-notice--visible');

    noticeHideTimer = window.setTimeout(function () {
      if (!noticeEl.classList.contains('awi-notice--visible')) {
        noticeEl.hidden = true;
        if (noticeText) {
          noticeText.textContent = '';
        }
      }
    }, NOTICE_ANIMATION_MS);
  }

  function showNotice(message) {
    if (!noticeEl || !noticeText) {
      return;
    }

    clearTimeout(noticeTimer);
    clearTimeout(noticeHideTimer);

    noticeText.textContent = message || appleWarranty.i18n.genericError;
    noticeEl.hidden = false;

    window.requestAnimationFrame(function () {
      noticeEl.classList.add('awi-notice--visible');
    });

    noticeEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    noticeTimer = window.setTimeout(hideNotice, NOTICE_AUTO_HIDE_MS);
  }

  function setLoading(loading) {
    if (!submitBtn) {
      return;
    }

    submitBtn.disabled = loading;
    submitBtn.classList.toggle('awi-btn--loading', loading);
    form.classList.toggle('awi-form--loading', loading);

    if (btnLabel) {
      btnLabel.textContent = loading
        ? appleWarranty.i18n.checking
        : appleWarranty.i18n.checkId;
    }

    if (btnIcon) {
      btnIcon.hidden = loading;
    }
  }

  function postFormData(action, extra) {
    var body = new FormData();
    body.append('action', action);
    body.append('nonce', appleWarranty.nonce);
    if (extra) {
      Object.keys(extra).forEach(function (key) {
        body.append(key, extra[key]);
      });
    }
    return fetch(appleWarranty.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body,
    }).then(function (res) {
      return res.json();
    });
  }

  function refreshCaptcha() {
    return postFormData('apple_warranty_captcha_refresh').then(function (json) {
      if (!json.success || !json.data) {
        return;
      }
      captchaToken.value = json.data.token;
      captchaImg.src = json.data.image_url;
      captchaInput.value = '';
    });
  }

  var RESULT_MEDIA_TIMEOUT_MS = 12000;

  function scrollToResult() {
    if (!resultWrap) {
      return;
    }

    window.requestAnimationFrame(function () {
      resultWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  function setResultLoading(loading) {
    if (!resultWrap) {
      return;
    }

    resultWrap.classList.toggle('awi-result-wrap--loading', loading);
    resultWrap.setAttribute('aria-busy', loading ? 'true' : 'false');
  }

  function revealResult(callback) {
    var productImg = resultWrap.querySelector('.awi-result__product-img');

    if (productImg) {
      productImg.classList.add('awi-result__product-img--loaded');
    }

    setResultLoading(false);
    resultWrap.classList.add('awi-result-wrap--ready');

    if (typeof callback === 'function') {
      callback();
    }
  }

  function waitForProductImage(src, callback) {
    if (!src) {
      callback();
      return;
    }

    var settled = false;
    var preload = new Image();

    function finish() {
      if (settled) {
        return;
      }
      settled = true;
      callback();
    }

    preload.onload = finish;
    preload.onerror = finish;
    preload.src = src;
    window.setTimeout(finish, RESULT_MEDIA_TIMEOUT_MS);
  }

  function showResult(html) {
    if (!resultWrap) {
      return;
    }

    resultWrap.classList.remove('awi-result-wrap--ready');
    resultWrap.innerHTML = html;
    resultWrap.hidden = false;

    var productImg = resultWrap.querySelector('.awi-result__product-img');
    var imageSrc = productImg ? productImg.getAttribute('src') : '';

    if (!imageSrc) {
      revealResult(scrollToResult);
      return;
    }

    setResultLoading(true);

    waitForProductImage(imageSrc, function () {
      revealResult(scrollToResult);
    });
  }

  if (noticeClose) {
    noticeClose.addEventListener('click', function (e) {
      e.preventDefault();
      hideNotice();
    });
  }

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function (e) {
      e.preventDefault();
      refreshCaptcha();
    });
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    hideNotice();

    var serial = serialInput ? serialInput.value.trim() : '';
    var captcha = captchaInput ? captchaInput.value.trim() : '';
    var token = captchaToken ? captchaToken.value : '';

    if (!serial) {
      showNotice(appleWarranty.i18n.serialRequired);
      return;
    }
    if (!captcha || !token) {
      showNotice(appleWarranty.i18n.captchaRequired);
      return;
    }

    setLoading(true);
    if (resultWrap) {
      setResultLoading(false);
      resultWrap.classList.remove('awi-result-wrap--ready');
      resultWrap.hidden = true;
      resultWrap.innerHTML = '';
    }

    postFormData('apple_warranty_lookup', {
      serial: serial,
      captcha: captcha,
      captcha_token: token,
    })
      .then(function (json) {
        setLoading(false);

        if (json.success && json.data && json.data.html) {
          showResult(json.data.html);
          refreshCaptcha();
          return;
        }

        var msg =
          (json.data && json.data.message) ||
          appleWarranty.i18n.genericError;
        showNotice(msg);
        refreshCaptcha();
      })
      .catch(function () {
        setLoading(false);
        showNotice(appleWarranty.i18n.genericError);
        refreshCaptcha();
      });
  });
})();
