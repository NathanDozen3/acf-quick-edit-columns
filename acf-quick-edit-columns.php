<?php
/**
 * Plugin Name: ACF Quick Edit Columns
 * Plugin URI: https://github.com/NathanDozen3/acf-quick-edit-columns
 * Description: Adds ACF fields as columns and Quick Edit fields for custom post types in the WordPress admin, with pre-populated values.
 * Version: 1.5.2
 * Author: Twelve Three Media
 * Author URI: https://www.digitalmarketingcompany.com/
 * License: GPL-2.0+
 * Text Domain: acf-quick-edit-columns
 */

declare(strict_types=1);

namespace AcfQuickEditColumns;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/inc/class-fieldvalueformatter.php';
require __DIR__ . '/inc/column-callbacks.php';
require __DIR__ . '/inc/functions.php';
require __DIR__ . '/inc/register-ajax.php';

/**
 * Register columns, Quick Edit, and JavaScript.
 */
function register_columns_and_quick_edit(): void
{
    if (!check_acf()) {
        return;
    }

    $cpt_fields = get_custom_post_types_and_acf_fields();

    foreach ($cpt_fields as $post_type => $fields) {
        // Add columns filter
        add_filter("manage_{$post_type}_posts_columns", function (array $columns) use ($fields): array {
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
        }, 10, 1);

        // Populate columns action
        add_action("manage_{$post_type}_posts_custom_column", function (string $column, int $post_id) use ($fields): void {
            if (isset($fields[$column])) {
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
        }, 10, 2);

        // Make columns sortable
        add_filter("manage_edit-{$post_type}_sortable_columns", function (array $columns) use ($fields): array {
            foreach ($fields as $column_key => $field_data) {
                $columns[$column_key] = $column_key;
            }
            return $columns;
        });

        // Add Quick Edit fields
        add_action('quick_edit_custom_box', function (string $column_name, string $screen_post_type) use ($post_type, $fields): void {
            static $number_of_quick_edit_fields_rendered;
            if (!isset($number_of_quick_edit_fields_rendered)) {
                $number_of_quick_edit_fields_rendered = 0;
            }
            $number_of_quick_edit_fields_rendered++;

            if ($number_of_quick_edit_fields_rendered === 1) {
                echo '<div style="clear: both;"></div>';
            }

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
        }, 10, 2);
    }

    // Enqueue JavaScript and CSS for pre-populating Quick Edit fields
    add_action('admin_enqueue_scripts', function (string $hook) use ($cpt_fields): void {
        if ($hook !== 'edit.php') {
            return;
        }
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
        if (!isset($cpt_fields[$post_type])) {
            return;
        }

        wp_enqueue_media(); // Enqueue media library scripts
        wp_enqueue_style(
            'acf-quick-edit',
            plugin_dir_url(__FILE__) . 'assets/acf-quick-edit.css',
            [],
            '1.0.1' // Updated version
        );
        wp_enqueue_script(
            'acf-quick-edit',
            plugin_dir_url(__FILE__) . 'assets/acf-quick-edit.js',
            ['jquery', 'inline-edit-post'],
            '1.0.4', // Updated version
            true
        );
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
    });

    // Save Quick Edit data
    foreach ($cpt_fields as $post_type => $fields) {
        add_action("save_post_{$post_type}", function (int $post_id, \WP_Post $post) use ($fields): void {
            try {
                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                    error_log("ACF Quick Edit Columns: Autosave detected for post {$post_id}, exiting");
                    return;
                }
                if (!current_user_can('edit_post', $post_id)) {
                    error_log("ACF Quick Edit Columns: User lacks permission to edit post {$post_id}");
                    return;
                }
                if (!isset($_POST['acf_quick_edit_nonce']) || !wp_verify_nonce($_POST['acf_quick_edit_nonce'], 'acf_quick_edit_nonce')) {
                    error_log("ACF Quick Edit Columns: Nonce verification failed for post {$post_id}, nonce: " . ($_POST['acf_quick_edit_nonce'] ?? 'missing'));
                    return;
                }

                error_log("ACF Quick Edit Columns: Saving Quick Edit data for post {$post_id}, post type {$post->post_type}");

                foreach ($fields as $column_key => $field) {
                    $field_name = $field['field_name'] ?? '';
                    $field_type = $field['type'] ?? '';
                    $input_name = "acf_{$field_name}";

                    if (empty($field_name) || empty($field_type)) {
                        error_log("ACF Quick Edit Columns: Invalid field data for column {$column_key} in post {$post_id}");
                        continue;
                    }

                    if (!isset($_POST[$input_name])) {
                        error_log("ACF Quick Edit Columns: Field {$field_name} not found in POST data for post {$post_id}");
                        continue;
                    }

                    $value = $_POST[$input_name];
                    $acf_field = acf_get_field($field_name);

                    if (!$acf_field) {
                        error_log("ACF Quick Edit Columns: ACF field {$field_name} not found for post {$post_id}");
                        continue;
                    }

                    // Clear empty values
                    if ($value === '' || ($field_type === 'select' && empty($value)) || ($field_type === 'checkbox' && empty($value))) {
                        $sanitized_value = in_array($field_type, ['checkbox', 'select', 'post_object', 'relationship', 'taxonomy']) ? [] : '';
                        error_log("ACF Quick Edit Columns: Clearing field {$field_name} (type: {$field_type}) for post {$post_id}");
                        update_field($field_name, $sanitized_value, $post_id);
                        continue;
                    }

                    // Sanitize based on field type
                    switch ($field_type) {
                        case 'text':
                        case 'radio':
                        case 'date_picker':
                            $sanitized_value = sanitize_text_field($value);
                            break;

                        case 'textarea':
                        case 'wysiwyg':
                            $sanitized_value = sanitize_textarea_field($value);
                            break;

                        case 'select':
                            $sanitized_value = !empty($acf_field['multiple'])
                                ? (is_array($value) ? array_map('sanitize_text_field', $value) : [sanitize_text_field($value)])
                                : sanitize_text_field($value);
                            break;

                        case 'checkbox':
                            $sanitized_value = is_array($value) ? array_map('sanitize_text_field', $value) : [sanitize_text_field($value)];
                            break;

                        case 'image':
                        case 'file':
                            $sanitized_value = absint($value);
                            break;

                        case 'url':
                            $sanitized_value = esc_url_raw($value);
                            break;

                        case 'email':
                            $sanitized_value = sanitize_email($value);
                            break;

                        case 'number':
                            $sanitized_value = is_numeric($value) ? floatval($value) : '';
                            break;

                        case 'true_false':
                            $sanitized_value = $value ? 1 : 0;
                            break;

                        case 'post_object':
                        case 'relationship':
                        case 'taxonomy':
                            $sanitized_value = is_array($value) ? array_map('absint', $value) : absint($value);
                            break;

                        default:
                            $sanitized_value = sanitize_text_field($value);
                            error_log("ACF Quick Edit Columns: Default sanitization for unknown field type {$field_type} for field {$field_name}");
                            break;
                    }

                    error_log("ACF Quick Edit Columns: Saving {$field_type} field {$field_name} for post {$post_id}, value: " . print_r($sanitized_value, true));
                    $result = update_field($field_name, $sanitized_value, $post_id);
                    error_log("ACF Quick Edit Columns: Save result for {$field_name}: " . ($result ? 'Success' : 'Failed'));
                }

                // Clear transient
                error_log("ACF Quick Edit Columns: Clearing transient after save for post {$post_id}");
                delete_transient('acf_quick_edit_columns_fields');
            } catch (\Exception $e) {
                error_log("ACF Quick Edit Columns: Fatal error in save logic for post {$post_id}: {$e->getMessage()}");
            }
        }, 10, 2);
    }
}
add_action('init', __NAMESPACE__ . '\\register_columns_and_quick_edit', 20);

/**
 * Load plugin text domain for translations.
 */
function load_textdomain(): void
{
    load_plugin_textdomain('acf-quick-edit-columns', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', __NAMESPACE__ . '\\load_textdomain');

function enqueue_scripts(): void {
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);
    wp_enqueue_script(
        'acf-quick-edit',
        plugin_dir_url(__FILE__) . 'assets/acf-quick-edit.js',
        ['jquery', 'inline-edit-post', 'media-editor', 'select2'],
        '1.1.2',
        true
    );
    wp_localize_script('acf-quick-edit', 'acfQuickEdit', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('acf_quick_edit_nonce'),
        'fields' => get_custom_post_types_and_acf_fields(),
    ]);
}
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts');