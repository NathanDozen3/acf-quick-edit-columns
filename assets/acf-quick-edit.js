// assets/acf-quick-edit.js
(function($) {
	$(document).ready(function() {
		if (typeof inlineEditPost === 'undefined') {
			console.log('inlineEditPost is not defined. Quick Edit functionality may not work.');
			return;
		}

		// Intercept the default inlineEditPost.edit to inject ACF Quick Edit prefill logic
		var originalInlineEditPost = inlineEditPost.edit;
		inlineEditPost.edit = function(id) {
			var args = Array.prototype.slice.call(arguments),
				editRow, postId;

			// Call the original inlineEditPost.edit method
			originalInlineEditPost.apply(this, args);

			// Determine the post ID being edited
			postId = typeof(id) === 'object' ? parseInt(this.getId(id)) : id;
			editRow = $('#edit-' + postId);

			var fields = acfQuickEdit.fields;

			// For each ACF field in the current post type, fetch its value via AJAX and prefill the Quick Edit UI
			$.each(fields, function(columnKey, field) {
				var fieldName = field.field_name,
					fieldType = field.type,
					inputName = 'acf_' + fieldName,
					// Find the input(s) for this field in the Quick Edit row
					$input = editRow.find('[name="' + inputName + '"], [name="' + inputName + '[]"]');

				console.log('Field: ' + fieldName + ', Type: ' + fieldType + ', Inputs found: ' + $input.length);

				// AJAX request to get the current value for this field
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

							// Prefill logic for each supported field type
							if (fieldType === 'post_object') {
								// For post_object fields, dynamically populate the select with the current value(s)
								// and initialize Select2 for AJAX searching of posts
								// Remove all but the first (placeholder) option
								$input.find('option:not(:first)').remove();
								// Support both single and multiple post objects
								var values = Array.isArray(value) ? value : (value && value.id ? [value] : []);
								var selectedIds = values.map(item => item.id).filter(id => id);

								// Add options for each selected post
								values.forEach(function(item) {
									if (item.id && item.title) {
										$input.append($('<option>', {
											value: item.id,
											text: item.title,
											selected: true
										}));
									}
								});

								// Initialize Select2 for better UX if available
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

									// Set the selected value(s) after initializing Select2
									$input.val(selectedIds).trigger('change');
								} else {
									console.error('Select2 is not available for field: ' + fieldName);
								}
							} else if (fieldType === 'image') {
								// For image fields, set the hidden input value and update the preview UI
								// Prefill image ID and update preview UI
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
							} else if (fieldType === 'radio') {
								// For radio fields, check the correct radio input based on value
								// Set the checked radio button based on value
								$input.each(function() {
									if ($(this).val() == value) {
										$(this).prop('checked', true);
									} else {
										$(this).prop('checked', false);
									}
								});
							} else if (fieldType === 'true_false') {
								// For true_false (checkbox) fields, set checked state
								// Set the checkbox for true_false fields
								if (value === true || value === '1' || value === 1) {
									$input.prop('checked', true);
								} else {
									$input.prop('checked', false);
								}
							} else if (fieldType === 'wysiwyg' && $input.is('textarea')) {
								// For wysiwyg fields, set the raw HTML value in the textarea (no rich editor in Quick Edit)
								// Prefill WYSIWYG as raw HTML in textarea (no TinyMCE in Quick Edit)
								$input.val(value || '');
							} else {
								// Default: set value for text, number, email, url, etc.
								$input.val(value || '');
							}
						} else {
							console.log('Invalid response for field: ' + fieldName + ', response: ', response);
						}
					},
					error: function(xhr, status, error) {
						// Log AJAX errors for debugging
						console.log('AJAX error for ' + fieldName + ': ', error, xhr.responseText);
					}
				});
			});

			// Image field UI: handle media library selection and removal
			editRow.find('.acf-select-image').on('click', function(e) {
				e.preventDefault();
				var $button = $(this),
					$container = $button.closest('.acf-quick-edit-image'),
					$input = $container.find('.acf-image-id'),
					$preview = $container.find('.acf-image-preview img'),
					$filename = $container.find('.acf-image-filename'),
					$removeButton = $container.find('.acf-remove-image');

				// Open WordPress media frame
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

			// Image field: remove image on remove button click
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