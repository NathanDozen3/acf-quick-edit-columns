<?php
/**
 * Utility and column management functions for ACF Quick Edit Columns.
 *
 * Handles ACF/SCF compatibility, CPT/field discovery, column registration, Quick Edit rendering, and save logic.
 *
 * @package   AcfQuickEditColumns
 * @author    Nathan Johnson
 * @copyright 2024 Nathan Johnson
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */
declare(strict_types=1);
namespace AcfQuickEditColumns;

/**
 * Check if ACF (or SCF) is active and available for use.
 *
 * @return bool True if ACF or SCF is active, false otherwise.
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
 * @see get_acf_fields_by_post_type()
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
 * Add ACF fields as columns in the posts list table for a given post type.
 *
 * @param array<string, string> $columns Existing columns.
 * @param string $post_type The post type slug.
 * @return array<string, string> Modified columns with ACF fields added.
 * @since 1.0.0
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
add_filter('manage_posts_columns', __NAMESPACE__ . '\\manage_posts_columns', 10, 2);

/**
 * Output ACF field value for custom columns in posts/pages list table.
 *
 * Uses the appropriate output callback for each field type (see column-callbacks.php).
 *
 * @param string $column The column key.
 * @param int $post_id The post ID.
 * @return void
 * @since 1.0.0
 */
function manage_pages_custom_column(string $column, int $post_id): void {
	// Get all ACF fields for the current post type
	$fields = get_acf_fields_by_post_type(get_post_type($post_id));
	if (!isset($fields[$column])) {
		// Not an ACF field column, skip
		return;
	}
	$field_name = $fields[$column]['field_name'];
	$field_type = $fields[$column]['type'];
	$output = get_field($field_name, $post_id);

	/**
	 * Use a dynamic filter to allow custom output formatting for each field type.
	 * Example: 'acf_quick_edit_columns_text', 'acf_quick_edit_columns_image', etc.
	 *
	 * @param mixed  $output     The raw field value.
	 * @param int    $post_id    The post ID.
	 * @param string $field_name The ACF field name.
	 * @param string $field_type The ACF field type.
	 */
	$filtered = apply_filters("acf_quick_edit_columns_{$field_type}", $output, $post_id, $field_name, $field_type);

	// For image and wysiwyg fields, allow safe HTML output; otherwise, escape for HTML
	if ($field_type === 'image' || $field_type === 'wysiwyg') {
		echo wp_kses_post($filtered);
	} else {
		echo esc_html($filtered);
	}
}
add_action('manage_pages_custom_column', __NAMESPACE__ . '\\manage_pages_custom_column', 10, 2);
add_action('manage_posts_custom_column', __NAMESPACE__ . '\\manage_pages_custom_column', 10, 2);

/**
 * Render the ACF fields in the Quick Edit box for the current column.
 *
 * Uses a dynamic filter to allow custom rendering for each field type.
 * Example: 'acf_quick_edit_render_field_text', 'acf_quick_edit_render_field_image', etc.
 *
 * @param string $column_name The column name.
 * @param string $screen_post_type The post type of the current screen.
 * @return void
 * @since 1.0.0
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
	if (!$field) {
		return;
	}
	?>
	<fieldset class="inline-edit-col-<?php echo esc_attr($number_of_quick_edit_fields_rendered % 2 === 0 ? 'right' : 'left'); ?>">
		<div class="inline-edit-col">
			<label>
				<span class="title"><?php echo esc_html($field_label); ?></span>
				<?php
				/**
				 * Fires to render the Quick Edit field for a specific ACF field type.
				 *
				 * Dynamic action: "acf_quick_edit_field_{$field_type}"
				 *
				 * @param array  $field       The ACF field array.
				 * @param string $field_label The field label.
				 * @param string $field_name  The field name (meta key).
				 *
				 * Example usage for custom field type:
				 *   add_action('acf_quick_edit_field_my_custom_type', function($field, $field_label, $field_name) { ... }, 10, 3);
				 */
				do_action("acf_quick_edit_field_{$field_type}", $field, $field_label, $field_name);
				?>
			</label>
			<input type="hidden" name="acf_quick_edit_nonce" value="<?php echo esc_attr(wp_create_nonce('acf_quick_edit_nonce')); ?>">
		</div>
	</fieldset>
	<?php
}
add_action('quick_edit_custom_box', __NAMESPACE__ . '\\quick_edit_custom_box', 10, 2);

/**
 * Get core ACF field types supported for Quick Edit.
 *
 * @return array
 */
function get_core_quick_edit_field_types(): array {
	return [
		'text',
		'textarea',
		'wysiwyg',
		'select',
		'checkbox',
		'radio',
		'true_false',
		'image',
		// 'file',
		// 'gallery',
		// 'date_picker',
		// 'datetime_picker',
		// 'time_picker',
		'number',
		'email',
		'url',
		// 'oembed',
		'password',
		'post_object',
		// 'relationship',
		// 'page_link',
		// 'user',
		// 'taxonomy',
		// 'google_map',
		// 'color_picker',
		// 'repeater',
		// 'group',
		// 'clone',
	];
}

/**
 * Get supported ACF field types for Quick Edit.
 *
 * @return array
 */
function get_supported_quick_edit_field_types(): array {
	/**
	 * Allow extensions to add or modify the supported ACF field types for Quick Edit.
	 *
	 * @param array $types
	 */
	return apply_filters('acf_quick_edit_supported_field_types', get_core_quick_edit_field_types());
}

/**
 * Render a Quick Edit field, allowing extensions for custom field types.
 *
 * @param string $field_type
 * @param array $field
 * @param int $post_id
 * @return string|null
 * @see quickedit-callbacks.php for rendering callbacks
 */
function render_quick_edit_field($field_type, $field, $post_id) {
	// Allow custom rendering for each field type via dynamic filter
	return apply_filters("acf_quick_edit_render_field_{$field_type}", null, $field, $post_id);
}

/**
 * Allow custom sanitization of Quick Edit field values by type.
 *
 * Uses a dynamic filter for each field type, e.g. 'acf_quick_edit_sanitize_field_text'.
 *
 * @param string $field_type
 * @param mixed $value
 * @param array $field
 * @param int $post_id
 * @return mixed
 */
function sanitize_quick_edit_field_value($field_type, $value, $field, $post_id) {
	// Allow custom sanitization for each field type via dynamic filter
	return apply_filters("acf_quick_edit_sanitize_field_{$field_type}", $value, $field, $post_id);
}

/**
 * Sanitize core ACF field types for Quick Edit (fallback if no custom filter is present).
 *
 * @param string $field_type
 * @param mixed $value
 * @param array $field
 * @return mixed
 */
function sanitize_core_quick_edit_field_value($field_type, $value, $field) {
	switch ($field_type) {
		case 'text':
		case 'color_picker':
		case 'date_picker':
		case 'datetime_picker':
		case 'time_picker':
		case 'page_link':
		case 'user':
		case 'post_object':
		case 'radio':
			return sanitize_text_field($value);
		case 'textarea':
		case 'wysiwyg':
			return wp_kses_post($value);
		case 'select':
			if (!empty($field['multiple'])) {
				return is_array($value) ? array_map('sanitize_text_field', $value) : [];
			}
			return sanitize_text_field($value);
		case 'checkbox':
			return is_array($value) ? array_map('sanitize_text_field', $value) : [];
		case 'true_false':
			return $value ? 1 : 0;
		case 'image':
		case 'file':
			return absint($value);
		case 'gallery':
			return is_array($value) ? array_map('absint', $value) : [];
		case 'relationship':
			return is_array($value) ? array_map('absint', $value) : [];
		case 'taxonomy':
			return is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
		case 'group':
		case 'repeater':
		case 'clone':
			// For complex types, rely on ACF's own sanitization.
			return $value;
		default:
			return $value;
	}
}

/**
 * Save Quick Edit data for ACF fields, using array_key_exists to allow clearing values.
 *
 * @param int $post_id The post ID.
 * @param \WP_Post $post The post object.
 * @return void
 * @since 1.0.0
 */
function save_post( int $post_id, \WP_Post $post ): void {
	// Only run on main save, not autosave or revision
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	// Check user capability before any nonce logic
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// Verify Quick Edit nonce if present (only process if Quick Edit is used)
	if ( isset( $_POST['acf_quick_edit_nonce'] ) && ! wp_verify_nonce( $_POST['acf_quick_edit_nonce'], 'acf_quick_edit_nonce' ) ) {
		// Invalid nonce, do not process
		return;
	}
	// Get all ACF fields for this post type
	$fields = get_acf_fields_by_post_type( $post->post_type );
	if ( empty( $fields ) ) {
		return;
	}
	// Loop through all fields and save if present in $_POST
	foreach ( $fields as $column => $field ) {
		$field_name = $field['field_name'];
		$field_type = $field['type'];
		$input_key = 'acf_' . $field_name;
		if ( array_key_exists( $input_key, $_POST ) ) {
			// Use dynamic filter to allow custom sanitization per field type
			$value = sanitize_quick_edit_field_value( $field_type, $_POST[ $input_key ], $field, $post_id );
			// Save the value using update_field (ACF) or update_post_meta (fallback)
			if ( function_exists( 'update_field' ) ) {
				update_field( $field_name, $value, $post_id );
			} else {
				update_post_meta( $post_id, $field_name, $value );
			}
		}
	}
}
add_action('save_post', __NAMESPACE__ . '\\save_post', 10, 2);