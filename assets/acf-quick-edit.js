// assets/acf-quick-edit.js
(function($) {
    $(document).ready(function() {
        if (typeof inlineEditPost === 'undefined') {
            console.log('inlineEditPost is not defined. Quick Edit functionality may not work.');
            return;
        }

        var originalInlineEditPost = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            var args = Array.prototype.slice.call(arguments),
                editRow, postId;

            originalInlineEditPost.apply(this, args);

            postId = typeof(id) === 'object' ? parseInt(this.getId(id)) : id;
            editRow = $('#edit-' + postId);

            var fields = acfQuickEdit.fields;

            $.each(fields, function(columnKey, field) {
                var fieldName = field.field_name,
                    fieldType = field.type,
                    inputName = 'acf_' + fieldName,
                    $input = editRow.find('[name="' + inputName + '"], [name="' + inputName + '[]"]');

                console.log('Field: ' + fieldName + ', Type: ' + fieldType + ', Inputs found: ' + $input.length);

                $.ajax({
                    url: acfQuickEdit.ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'acf_quick_edit_get_field',
                        post_id: postId,
                        field_name: fieldName,
                        nonce: acfQuickEdit.nonce
                    },
                    success: function(response) {
                        console.log('AJAX response for ' + fieldName + ': ', response);
                        if (response.success && response.data && 'value' in response.data) {
                            var value = response.data.value;
                            console.log('Setting value for ' + fieldName + ' (type: ' + fieldType + '): ', value);

                            if (fieldType === 'post_object') {
                                $input.find('option:not(:first)').remove();
                                var values = Array.isArray(value) ? value : (value && value.id ? [value] : []);
                                var selectedIds = values.map(item => item.id).filter(id => id);

                                values.forEach(function(item) {
                                    if (item.id && item.title) {
                                        $input.append($('<option>', {
                                            value: item.id,
                                            text: item.title,
                                            selected: true
                                        }));
                                    }
                                });

                                if (typeof $.fn.select2 === 'function') {
                                    var nonce = $input.data('nonce') || acfQuickEdit.nonce;
                                    console.log('Using nonce for Select2: ' + nonce);
                                    $input.select2({
                                        ajax: {
                                            url: acfQuickEdit.ajaxurl,
                                            dataType: 'json',
                                            delay: 250,
                                            data: function(params) {
                                                return {
                                                    action: 'acf_quick_edit_search_posts',
                                                    field_name: fieldName,
                                                    s: params.term || '',
                                                    nonce: nonce
                                                };
                                            },
                                            processResults: function(data) {
                                                console.log('Select2 AJAX response for ' + fieldName + ': ', data);
                                                if (data.success && data.data && data.data.results) {
                                                    return {
                                                        results: data.data.results
                                                    };
                                                }
                                                return { results: [] };
                                            },
                                            cache: true
                                        },
                                        minimumInputLength: 0,
                                        placeholder: 'Select',
                                        allowClear: true
                                    });

                                    $input.val(selectedIds).trigger('change');
                                } else {
                                    console.error('Select2 is not available for field: ' + fieldName);
                                }
                            } else if (fieldType === 'image') {
                                if (value && value.id) {
                                    $input.val(value.id);
                                    var $preview = $input.siblings('.acf-image-preview').find('img');
                                    $preview.attr('src', value.url).show();
                                    $input.siblings('.acf-image-filename').text(value.title || '');
                                    $input.siblings('.acf-remove-image').show();
                                } else {
                                    $input.val('');
                                    $input.siblings('.acf-image-preview').find('img').hide();
                                    $input.siblings('.acf-image-filename').text('');
                                    $input.siblings('.acf-remove-image').hide();
                                }
                            } else {
                                $input.val(value || '');
                            }
                        } else {
                            console.log('Invalid response for field: ' + fieldName + ', response: ', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error for ' + fieldName + ': ', error, xhr.responseText);
                    }
                });
            });

            editRow.find('.acf-select-image').on('click', function(e) {
                e.preventDefault();
                var $button = $(this),
                    $container = $button.closest('.acf-quick-edit-image'),
                    $input = $container.find('.acf-image-id'),
                    $preview = $container.find('.acf-image-preview img'),
                    $filename = $container.find('.acf-image-filename'),
                    $removeButton = $container.find('.acf-remove-image');

                var frame = wp.media({
                    title: 'Select Image',
                    multiple: false,
                    library: { type: 'image' },
                    button: { text: 'Select Image' }
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.id);
                    $preview.attr('src', attachment.url).show();
                    $filename.text(attachment.title || attachment.filename);
                    $removeButton.show();
                });

                frame.open();
            });

            editRow.find('.acf-remove-image').on('click', function(e) {
                e.preventDefault();
                var $button = $(this),
                    $container = $button.closest('.acf-quick-edit-image'),
                    $input = $container.find('.acf-image-id'),
                    $preview = $container.find('.acf-image-preview img'),
                    $filename = $container.find('.acf-image-filename');

                $input.val('');
                $preview.hide();
                $filename.text('');
                $button.hide();
            });
        };
    });
})(jQuery);