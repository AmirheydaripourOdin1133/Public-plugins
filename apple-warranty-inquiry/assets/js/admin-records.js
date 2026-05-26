(function ($) {
  'use strict';

  $('#cb-select-all-1, #cb-select-all-2').on('change', function () {
    var checked = this.checked;
    $('input[name="record_ids[]"]').prop('checked', checked);
    $('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
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
