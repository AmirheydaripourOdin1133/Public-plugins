(function ($) {
  'use strict';

  $('#cb-select-all-1, #cb-select-all-2').on('change', function () {
    var checked = this.checked;
    $('input[name="record_ids[]"]').prop('checked', checked);
    $('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
  });

  function copyToClipboard(text, onSuccess) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(onSuccess).catch(function () {
        fallbackCopy(text, onSuccess);
      });
      return;
    }
    fallbackCopy(text, onSuccess);
  }

  function fallbackCopy(text, onSuccess) {
    var $temp = $('<textarea readonly dir="ltr"></textarea>');
    $temp.val(text).css({ position: 'fixed', left: '-9999px' });
    $('body').append($temp);
    $temp[0].select();
    try {
      document.execCommand('copy');
      onSuccess();
    } catch (e) {
      /* user can copy manually */
    }
    $temp.remove();
  }

  $(document).on('click', '.awi-image-key-copy', function (e) {
    e.preventDefault();
    var $btn = $(this);
    var key = $btn.data('key');
    if (!key) {
      return;
    }

    var copiedLabel = $btn.data('copied') || 'کپی شد!';

    copyToClipboard(String(key), function () {
      $btn.addClass('awi-image-key-copy--done');
      var $code = $btn.find('code');
      var original = $code.text();
      $code.text(copiedLabel);
      window.setTimeout(function () {
        $code.text(original);
        $btn.removeClass('awi-image-key-copy--done');
      }, 1600);
    });
  });

  $('#awi-copy-shortcode').on('click', function () {
    var $btn = $(this);
    var $input = $('#awi-shortcode-value');
    var $feedback = $('#awi-copy-feedback');
    var text = $input.val();
    var copiedLabel = $btn.data('copied') || 'کپی شد!';

    function showOk() {
      $feedback.text(copiedLabel).removeAttr('hidden');
      $btn.addClass('awi-copied');
      window.setTimeout(function () {
        $feedback.attr('hidden', 'hidden').text('');
        $btn.removeClass('awi-copied');
      }, 2000);
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(showOk).catch(function () {
        $input.trigger('focus').trigger('select');
        try {
          document.execCommand('copy');
          showOk();
        } catch (e) {
          /* manual copy */
        }
      });
      return;
    }

    $input.trigger('focus').trigger('select');
    try {
      document.execCommand('copy');
      showOk();
    } catch (e) {
      /* manual copy */
    }
  });
})(jQuery);
