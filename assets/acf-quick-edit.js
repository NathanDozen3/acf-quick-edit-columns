(function($) {
    $(document).on('click', '.editinline', function() {
        var post_id = $(this).closest('tr').attr('id').replace('post-', '');
        var $row = $('#post-' + post_id);

        $.each(acfQuickEdit.fields, function(column_key, field_data) {
            var field_name = field_data.field_name;
            var field_type = field_data.type;
            var $cell = $row.find('td.column-' + column_key);
            var value = $cell.text().trim() !== '—' ? $cell.text().trim() : '';

            if (field_type === 'image') {
                var $quick_edit = $('#edit-' + post_id).find('.acf-quick-edit-image[data-field="' + field_name + '"]');
                var $preview = $quick_edit.find('.acf-image-preview img');
                var $filename = $quick_edit.find('.acf-image-filename');
                var $input = $quick_edit.find('.acf-image-id');
                var $remove = $quick_edit.find('.acf-remove-image');

                // Fetch image data via AJAX
                $.ajax({
                    url: acfQuickEdit.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'acf_quick_edit_get_image',
                        post_id: post_id,
                        field_name: field_name,
                        nonce: acfQuickEdit.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.id) {
                            $input.val(response.data.id);
                            $preview.attr('src', response.data.url).show();
                            $filename.text(response.data.title || 'Image selected');
                            $remove.show();
                        } else {
                            $input.val('');
                            $preview.hide();
                            $filename.text('');
                            $remove.hide();
                        }
                    },
                    error: function() {
                        $input.val('');
                        $preview.hide();
                        $filename.text('');
                        $remove.hide();
                    }
                });
            } else if (field_type === 'select') {
                var $select = $('#edit-' + post_id).find('select[name="acf_' + field_name + '"], select[name="acf_' + field_name + '[]"]');
                if (value) {
                    var values = value.split(', ');
                    $select.val(values);
                } else {
                    $select.val('');
                }
            } else if (field_type === 'checkbox') {
                var values = value ? value.split(', ') : [];
                $('#edit-' + post_id).find('input[name="acf_' + field_name + '[]"]').each(function() {
                    $(this).prop('checked', values.includes($(this).val()));
                });
            } else {
                var $input = $('#edit-' + post_id).find('input[name="acf_' + field_name + '"], textarea[name="acf_' + field_name + '"]');
                $input.val(value);
            }
        });

        // Add nonce to the form
        var $nonce = $('#edit-' + post_id).find('input[name="acf_quick_edit_nonce"]');
        if ($nonce.length === 0) {
            $('#edit-' + post_id).append('<input type="hidden" name="acf_quick_edit_nonce" value="' + acfQuickEdit.nonce + '">');
        }
    });

    // Initialize media library for image fields
    $(document).on('click', '.acf-select-image', function() {
        var $button = $(this);
        var $container = $button.closest('.acf-quick-edit-image');
        var $preview = $container.find('.acf-image-preview img');
        var $filename = $container.find('.acf-image-filename');
        var $input = $container.find('.acf-image-id');
        var $remove = $container.find('.acf-remove-image');

        var media = wp.media({
            title: 'Select Image',
            multiple: false,
            library: {
                type: 'image'
            }
        });

        media.on('select', function() {
            var attachment = media.state().get('selection').first().toJSON();
            $input.val(attachment.id);
            $preview.attr('src', attachment.url).show();
            $filename.text(attachment.title || attachment.filename);
            $remove.show();
        });

        media.open();
    });

    // Handle image removal
    $(document).on('click', '.acf-remove-image', function() {
        var $button = $(this);
        var $container = $button.closest('.acf-quick-edit-image');
        var $preview = $container.find('.acf-image-preview img');
        var $filename = $container.find('.acf-image-filename');
        var $input = $container.find('.acf-image-id');
        var $remove = $container.find('.acf-remove-image');

        $input.val('');
        $preview.hide();
        $filename.text('');
        $remove.hide();
    });
})(jQuery);