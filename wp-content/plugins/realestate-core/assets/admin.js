jQuery(function ($) {
  // Gallery picker on the property edit screen (WordPress media library).
  $('.rec-gallery-pick').on('click', function () {
    var target = $('#' + $(this).data('for'));
    var preview = $('.rec-gallery-preview[data-for="' + $(this).data('for') + '"]');
    var frame = wp.media({ title: 'Choose property photos', button: { text: 'Use these photos' }, multiple: 'add', library: { type: 'image' } });

    frame.on('open', function () {
      var selection = frame.state().get('selection');
      (target.val() || '').split(',').filter(Boolean).forEach(function (id) {
        var att = wp.media.attachment(id);
        att.fetch();
        selection.add(att);
      });
    });

    frame.on('select', function () {
      var ids = [];
      preview.empty();
      frame.state().get('selection').each(function (att) {
        ids.push(att.id);
        var sizes = att.get('sizes') || {};
        var url = (sizes.thumbnail || sizes.full || {}).url || att.get('url');
        preview.append($('<img>').attr('src', url));
      });
      target.val(ids.join(','));
    });

    frame.open();
  });

  $('.rec-gallery-clear').on('click', function () {
    $('#' + $(this).data('for')).val('');
    $('.rec-gallery-preview[data-for="' + $(this).data('for') + '"]').empty();
  });
});
