<?php
/**
 * Main plugin file for ACF Quick Edit Columns.
 *
 * Loads all core modules, sets up admin hooks, and initializes plugin features.
 *
 * @package   AcfQuickEditColumns
 * @author    Nathan Johnson
 * @copyright 2024 Nathan Johnson
 * @license   GPL-2.0-or-later
 * @since     1.0.0
 */

/**
 * Plugin Name: ACF Quick Edit Columns
 * Plugin URI: https://github.com/NathanDozen3/acf-quick-edit-columns
 * Description: Adds ACF fields as columns and Quick Edit fields for custom post types in the WordPress admin, with pre-populated values.
 * Version: 1.6.0
 * Author: Twelve Three Media
 * Author URI: https://www.digitalmarketingcompany.com/
 * License: GPL-2.0+
 * Text Domain: acf-quick-edit-columns
 */

declare(strict_types=1);

namespace AcfQuickEditColumns;

// Exit if accessed directly
if (!defined('ABSPATH')) {
	// Prevent direct access for security.
	exit;
}

// Define plugin version constant for easy reference
if (!defined('ACF_QEC_VERSION')) {
    define('ACF_QEC_VERSION', '1.6.0');
}

// Load core plugin modules
require __DIR__ . '/inc/class-fieldvalueformatter.php'; // Field value formatting for columns and AJAX
require __DIR__ . '/inc/column-callbacks.php';          // Output callbacks for admin columns
require __DIR__ . '/inc/functions.php';                 // Utility and column management functions
require __DIR__ . '/inc/quickedit-callbacks.php';       // Quick Edit field rendering callbacks
require __DIR__ . '/inc/register-ajax.php';             // AJAX handlers for Quick Edit prefill

/**
 * Add a filter to make ACF fields sortable in the admin edit screen for all custom post types.
 */
function add_sortable_columns_filter_for_all_custom_post_types(): void {
	if ( ! is_admin() ) {
		return;
	}
	foreach ( get_custom_post_types() as $post_type ) {
		add_filter(
			"manage_edit-{$post_type->name}_sortable_columns",
			__NAMESPACE__ . '\\manage_edit_sortable_columns'
		);
	}
}
add_action('init', __NAMESPACE__ . '\\add_sortable_columns_filter_for_all_custom_post_types', 20);

/**
 * Get all public custom post types.
 *
 * @param array<string, mixed> $columns Array of columns to modify.
 * @return array<\WP_Post_Type> Array of public custom post type objects.
 */
function manage_edit_sortable_columns( array $columns ): array {
	$post_type = get_sanitized_post_type();
	foreach( array_keys( get_acf_fields_by_post_type( $post_type ) ) as $key) {
		$columns[$key] = $key;
	}
	return $columns;
}

/**
 * Load plugin text domain for translations.
 */
function load_textdomain(): void {
	load_plugin_textdomain('acf-quick-edit-columns', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', __NAMESPACE__ . '\\load_textdomain');

/**
 * Enqueue admin scripts and styles for Quick Edit UI and ACF field support.
 *
 * @since 1.0.0
 */
function enqueue_scripts( string $hook ): void {
	if ($hook !== 'edit.php') {
		return;
	}

	wp_enqueue_media(); // Enqueue media library scripts
	wp_enqueue_style(
		'acf-quick-edit',
		plugin_dir_url(__FILE__) . 'assets/acf-quick-edit.css',
		[],
		ACF_QEC_VERSION
	);
	
	wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0');
	wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);

	wp_enqueue_script(
		'acf-quick-edit',
		plugin_dir_url(__FILE__) . 'assets/acf-quick-edit.js',
		['jquery', 'inline-edit-post', 'media-editor', 'select2'],
		ACF_QEC_VERSION,
		true
	);

	$cpt_fields = get_custom_post_types_and_acf_fields();
	$post_type = get_sanitized_post_type();
	if (!isset($cpt_fields[$post_type])) {
		return;
	}
	$field_data = [];
	foreach ($cpt_fields[$post_type] as $column_key => $field) {
		$field_data[$column_key] = [
			'field_name' => $field['field_name'],
			'type' => $field['type'],
		];
	}
	wp_localize_script('acf-quick-edit', 'acfQuickEdit', [
		'fields' => $field_data,
		'nonce' => wp_create_nonce('acf_quick_edit_nonce'),
		'ajaxurl' => admin_url('admin-ajax.php'),
	]);
}
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts');
