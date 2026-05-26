(function ($) {
  'use strict';

  var frame;

  $('#awi-select-image').on('click', function (e) {
    e.preventDefault();

    if (frame) {
      frame.open();
      return;
    }

    frame = wp.media({
      title: 'انتخاب تصویر',
      button: { text: 'استفاده از این تصویر' },
      multiple: false,
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      $('#awi-attachment-id').val(attachment.id);
      var url =
        attachment.sizes && attachment.sizes.thumbnail
          ? attachment.sizes.thumbnail.url
          : attachment.url;
      $('#awi-image-preview').html(
        '<img src="' + url + '" style="max-width:120px;height:auto;" alt="" />'
      );
    });

    frame.open();
  });

  $('#awi-remove-image').on('click', function (e) {
    e.preventDefault();
    $('#awi-attachment-id').val('');
    $('#awi-image-preview').empty();
  });

  function copyImageKey(text, onSuccess) {
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

    copyImageKey(String(key), function () {
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
})(jQuery);
