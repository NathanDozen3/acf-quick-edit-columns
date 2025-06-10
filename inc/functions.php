<?php
declare(strict_types=1);
namespace AcfQuickEditColumns;

/**
 * Check if ACF is active.
 *
 * @return bool True if ACF is active, false otherwise.
 */
function check_acf(): bool {
	if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
		add_action('admin_notices', function (): void {
			echo '<div class="error"><p>' . esc_html__('ACF Quick Edit Columns requires Advanced Custom Fields to be installed and activated.', 'acf-quick-edit-columns') . '</p></div>';
		});
		return false;
	}
	return true;
}

/**
 * Get the sanitized post type from the request.
 *
 * @return string The sanitized post type.
 */
function get_sanitized_post_type() {
	$post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
	return $post_type;
}

/**
 * Get all custom post types and their ACF fields, excluding flexible content fields.
 *
 * @return array<string, array<string, array{label: string, field_name: string, type: string}>> Array of CPTs and their ACF fields.
 */
function get_custom_post_types_and_acf_fields(): array {
	if (!check_acf()) {
		return [];
	}

	$custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
	$cpt_fields = [];

	foreach ($custom_post_types as $cpt) {
		$field_groups = acf_get_field_groups(['post_type' => $cpt->name]);
		$fields = [];

		foreach ($field_groups as $group) {
			$group_fields = acf_get_fields($group['key']);
			if ($group_fields) {
				foreach ($group_fields as $field) {
					$column_key = 'acf_' . $field['name'];
					$fields[$column_key] = [
						'label' => $field['label'],
						'field_name' => $field['name'],
						'type' => $field['type'],
					];
				}
			}
		}

		if (!empty($fields)) {
			$cpt_fields[$cpt->name] = $fields;
		}
	}
	return $cpt_fields;
}

/**
 * Get all public custom post types.
 *
 * @return array<string, \WP_Post_Type> Array of public custom post types.
 */
function get_custom_post_types() {
	return get_post_types(['public' => true, '_builtin' => false], 'objects');
}

/**
 * Get ACF fields for a specific post type, excluding flexible content fields.
 *
 * @param string $post_type The post type slug.
 * @return array<string, array{label: string, field_name: string, type: string}> Array of ACF fields for the post type.
 */
function get_acf_fields_by_post_type( string $post_type ): array {
	if ( ! check_acf() ) {
		return [];
	}

	$field_groups = acf_get_field_groups( [ 'post_type' => $post_type ] );
	$fields = [];

	foreach ( $field_groups as $group ) {
		$group_fields = acf_get_fields( $group['key'] );
		if ( $group_fields ) {
			foreach ( $group_fields as $field ) {
				$column_key = 'acf_' . $field['name'];
				$fields[ $column_key ] = [
					'label'      => $field['label'],
					'field_name' => $field['name'],
					'type'       => $field['type'],
				];
			}
		}
	}

	return $fields;
}

/**
 * Add ACF fields as columns in the posts list table.
 *
 * @param array<string, string> $columns Existing columns.
 * @param string $post_type The post type slug.
 * @return array<string, string> Modified columns with ACF fields added.
 */
function manage_posts_columns(array $columns, string $post_type): array {
	$fields = get_acf_fields_by_post_type($post_type);
	if (empty($fields)) {
		return $columns;
	}
	$new_columns = [];
	foreach ($columns as $key => $value) {
		$new_columns[$key] = $value;
		if ($key === 'title') {
			foreach ($fields as $column_key => $field_data) {
				$new_columns[$column_key] = __($field_data['label'], 'acf-quick-edit-columns');
			}
		}
	}
	return $new_columns;
}
add_filter('manage_posts_columns', 'AcfQuickEditColumns\manage_posts_columns', 10, 2);

/**
 * Add ACF fields as columns in the pages list table.
 *
 * @param array<string, string> $columns Existing columns.
 * @param int $post_id The post ID.
 * @return array<string, string> Modified columns with ACF fields added.
 */
function manage_pages_custom_column(string $column, int $post_id): void {
	$fields = get_acf_fields_by_post_type(get_post_type($post_id));
	if (!isset($fields[$column])) {
		return;
	}
	$field_name = $fields[$column]['field_name'];
	$field_type = $fields[$column]['type'];
	$output = get_field( $field_name, $post_id );

	/**
	 * Filter the output for ACF Quick Edit columns.
	 * 
	 * @param string $output The default output.
	 * @param int $post_id The post ID.
	 * @param string $field_name The field name.
	 * @param string $field_type The field type.
	 * @return string The formatted output.
	 */
	echo apply_filters( "acf_quick_edit_columns_{$field_type}", $output, $post_id, $field_name, $field_type );
}
add_action('manage_pages_custom_column', 'AcfQuickEditColumns\manage_pages_custom_column', 10, 2);
add_action('manage_posts_custom_column', 'AcfQuickEditColumns\manage_pages_custom_column', 10, 2);

/**
 * Render the ACF fields in the Quick Edit box.
 *
 * @param string $column_name The column name.
 * @param string $screen_post_type The post type of the current screen.
 */
function quick_edit_custom_box(string $column_name, string $screen_post_type): void {
	static $number_of_quick_edit_fields_rendered;
	if (!isset($number_of_quick_edit_fields_rendered)) {
		$number_of_quick_edit_fields_rendered = 0;
	}
	$number_of_quick_edit_fields_rendered++;

	if ($number_of_quick_edit_fields_rendered === 1) {
		echo '<div style="clear: both;"></div>';
	}

	$post_type = get_sanitized_post_type();
	$fields = get_acf_fields_by_post_type($post_type);

	if ($screen_post_type !== $post_type || !isset($fields[$column_name])) {
		return;
	}

	$field_name = $fields[$column_name]['field_name'];
	$field_label = $fields[$column_name]['label'];
	$field_type = $fields[$column_name]['type'];
	$field = acf_get_field($field_name);
	?>
	<fieldset class="inline-edit-col-<?php echo $number_of_quick_edit_fields_rendered % 2 === 0 ? 'right' : 'left'; ?>">
		<div class="inline-edit-col">
			<label>
				<span class="title"><?php echo esc_html($field_label); ?></span>
				<?php if (in_array($field_type, ['textarea', 'wysiwyg'], true)) : ?>
					<textarea name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit"></textarea>
				<?php elseif ($field_type === 'select') : ?>
					<select name="acf_<?php echo esc_attr($field_name); ?><?php echo !empty($field['multiple']) ? '[]' : ''; ?>" class="acf-quick-edit" <?php echo !empty($field['multiple']) ? 'multiple' : ''; ?>>
						<?php if (empty($field['multiple'])) : ?>
							<option value=""><?php esc_html_e('None', 'acf-quick-edit-columns'); ?></option>
						<?php endif; ?>
						<?php foreach ($field['choices'] as $value => $label) : ?>
							<option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
				<?php elseif ($field_type === 'checkbox') : ?>
					<div class="acf-quick-edit-checkboxes acf-checkbox-grid">
						<?php foreach ($field['choices'] as $value => $label) : ?>
							<label class="acf-checkbox">
								<input type="checkbox" name="acf_<?php echo esc_attr($field_name); ?>[]" value="<?php echo esc_attr($value); ?>" class="acf-quick-edit">
								<span class="acf-checkbox-label"><?php echo esc_html($label); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				<?php elseif ($field_type === 'image') : ?>
					<div class="acf-quick-edit-image" data-field="<?php echo esc_attr($field_name); ?>">
						<div class="acf-image-preview" style="margin-bottom: 10px;">
							<img src="" alt="" style="max-width: 100px; height: auto; display: none;">
						</div>
						<p class="acf-image-filename" style="margin: 0 0 10px; font-size: 12px;"></p>
						<input type="hidden" name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit acf-image-id" value="">
						<button type="button" class="button acf-select-image"><?php esc_html_e('Select Image', 'acf-quick-edit-columns'); ?></button>
						<button type="button" class="button acf-remove-image" style="display: none;"><?php esc_html_e('Remove', 'acf-quick-edit-columns'); ?></button>
					</div>
				<?php elseif ($field_type === 'post_object') : ?>
					<select name="acf_<?php echo esc_attr($field_name); ?><?php echo !empty($field['multiple']) ? '[]' : ''; ?>"
							class="acf-quick-edit-query acf-post-object"
							<?php echo !empty($field['multiple']) ? 'multiple' : ''; ?>>
						<option value="">Select</option>
					</select>
				<?php else : ?>
					<input type="text" name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit" value="">
				<?php endif; ?>
			</label>
			<input type="hidden" name="acf_quick_edit_nonce" value="<?php echo esc_attr(wp_create_nonce('acf_quick_edit_nonce')); ?>">
		</div>
	</fieldset>
	<?php
}
add_action('quick_edit_custom_box', 'AcfQuickEditColumns\quick_edit_custom_box', 10, 2);