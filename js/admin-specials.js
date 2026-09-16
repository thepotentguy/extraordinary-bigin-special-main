jQuery(function ($) {
  'use strict';
  var frame;

  $('#es-add-gallery-images').on('click', function (event) {
    event.preventDefault();
    frame = wp.media({
      title: 'Select offer images',
      button: { text: 'Add to offer gallery' },
      multiple: true,
      library: { type: 'image' }
    });

    frame.on('select', function () {
      frame.state().get('selection').each(function (attachment) {
        var image = attachment.toJSON();
        if ($('#es-gallery-preview [data-id="' + image.id + '"]').length) return;
        var src = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url;
        $('#es-gallery-preview').append(
          '<div class="es-gallery-image" data-id="' + image.id + '">' +
          '<img src="' + src + '" alt="">' +
          '<button type="button" class="button-link-delete es-remove-gallery-image">Remove</button>' +
          '<input type="hidden" name="gallery[]" value="' + image.id + '">' +
          '</div>'
        );
      });
    });
    frame.open();
  });

  $(document).on('click', '.es-remove-gallery-image', function () {
    $(this).closest('.es-gallery-image').remove();
  });
});
