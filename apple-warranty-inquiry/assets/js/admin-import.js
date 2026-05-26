(function () {
  'use strict';

  var panel = document.getElementById('awi-import-panel');
  var loadingEl = document.getElementById('awi-import-loading');
  var loadingText = document.getElementById('awi-import-loading-text');
  var fileInput = document.getElementById('awi-csv-file');
  var fileNameEl = document.getElementById('awi-file-name');
  var previewForm = document.getElementById('awi-import-preview-form');
  var finalForm = document.getElementById('awi-import-final-form');

  if (!panel) {
    return;
  }

  var i18n = (window.appleWarrantyAdmin && window.appleWarrantyAdmin.import) || {};

  function setLoading(active, message) {
    panel.classList.toggle('awi-import-panel--loading', active);

    if (loadingEl) {
      loadingEl.hidden = !active;
      loadingEl.setAttribute('aria-hidden', active ? 'false' : 'true');
    }

    if (loadingText && message) {
      loadingText.textContent = message;
    }
  }

  function setButtonLoading(btn, active) {
    if (!btn) {
      return;
    }

    btn.classList.toggle('is-loading', active);
    btn.disabled = active;
  }

  if (fileInput && fileNameEl) {
    fileInput.addEventListener('change', function () {
      var file = fileInput.files && fileInput.files[0];
      fileNameEl.textContent = file ? file.name : '';
    });
  }

  if (previewForm) {
    previewForm.addEventListener('submit', function () {
      if (!fileInput || !fileInput.files || !fileInput.files.length) {
        return;
      }

      setLoading(true, i18n.previewing || 'در حال آماده‌سازی پیش‌نمایش…');
      setButtonLoading(document.getElementById('awi-preview-submit'), true);
    });
  }

  if (finalForm) {
    finalForm.addEventListener('submit', function () {
      setLoading(true, i18n.importing || 'در حال درون‌ریزی داده‌ها…');
      setButtonLoading(document.getElementById('awi-import-submit'), true);
    });
  }
})();
