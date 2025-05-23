<?php
/**
 * Plugin Name: ACF Quick Edit Columns
 * Plugin URI: https://github.com/NathanDozen3/acf-quick-edit-columns
 * Description: Adds ACF fields as columns and Quick Edit fields for custom post types in the WordPress admin, with pre-populated values.
 * Version: 1.5.1
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

/**
 * Check if ACF is active.
 *
 * @return bool True if ACF is active, false otherwise.
 */
function check_acf(): bool
{
    if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
        add_action('admin_notices', function (): void {
            echo '<div class="error"><p>' . esc_html__('ACF Quick Edit Columns requires Advanced Custom Fields to be installed and activated.', 'acf-quick-edit-columns') . '</p></div>';
        });
        return false;
    }
    return true;
}

/**
 * Get all custom post types and their ACF fields, excluding flexible content fields.
 *
 * @return array<string, array<string, array{label: string, field_name: string, type: string}>> Array of CPTs and their ACF fields.
 */
function get_custom_post_types_and_acf_fields(): array
{
    $custom_post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
    $cpt_fields = [];

    foreach ($custom_post_types as $cpt) {
        $field_groups = acf_get_field_groups(['post_type' => $cpt->name]);
        $fields = [];

        foreach ($field_groups as $group) {
            $group_fields = acf_get_fields($group['key']);
            if ($group_fields) {
                foreach ($group_fields as $field) {
                    // Skip flexible content fields
                    if ($field['type'] === 'flexible_content') {
                        error_log('ACF Quick Edit Columns: Skipping flexible content field: ' . $field['name']);
                        continue;
                    }
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
            error_log('ACF Quick Edit Columns: Fields for CPT ' . $cpt->name . ': ' . print_r($fields, true));
        }
    }

    error_log('ACF Quick Edit Columns: All CPT fields: ' . print_r($cpt_fields, true));
    return $cpt_fields;
}

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
        add_filter("manage_{$post_type}_posts_columns", function (array $columns) use ($post_type, $fields): array {
            error_log("ACF Quick Edit Columns: Running manage_{$post_type}_posts_columns filter");
            $new_columns = [];
            foreach ($columns as $key => $value) {
                $new_columns[$key] = $value;
                if ($key === 'title') {
                    foreach ($fields as $column_key => $field_data) {
                        $new_columns[$column_key] = __($field_data['label'], 'acf-quick-edit-columns');
                    }
                }
            }
            error_log("ACF Quick Edit Columns: Columns for {$post_type}: " . print_r($new_columns, true));
            return $new_columns;
        });

        // Populate columns action
        add_action("manage_{$post_type}_posts_custom_column", function (string $column, int $post_id) use ($post_type, $fields): void {
            error_log("ACF Quick Edit Columns: Running manage_{$post_type}_posts_custom_column for column {$column}, post {$post_id}");
            if (isset($fields[$column])) {
                $field_name = $fields[$column]['field_name'];
                $field_type = $fields[$column]['type'];
                $value = get_field($field_name, $post_id);
                error_log("ACF Quick Edit Columns: Field {$field_name} for post {$post_id}: " . print_r($value, true));
                if ($field_type === 'image' && is_array($value)) {
                    if (!empty($value['url'])) {
                        echo '<img src="' . esc_url($value['url']) . '" style="max-width: 50px; height: auto;" alt="' . esc_attr($value['title'] ?: 'Image') . '" data-image-id="' . esc_attr($value['ID']) . '">';
                    } else {
                        echo esc_html__('—', 'acf-quick-edit-columns');
                    }
                } elseif (is_array($value)) {
                    echo esc_html(implode(', ', array_map('strval', $value)) ?: '—');
                } else {
                    echo esc_html($value ?: '—');
                }
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
            if ($screen_post_type !== $post_type) {
                return;
            }
            error_log("ACF Quick Edit Columns: Running quick_edit_custom_box for column {$column_name}, post type {$post_type}");
            if (isset($fields[$column_name])) {
                $field_name = $fields[$column_name]['field_name'];
                $field_label = $fields[$column_name]['label'];
                $field_type = $fields[$column_name]['type'];
                $field = acf_get_field($field_name);
                ?>
                <fieldset class="inline-edit-col-right">
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
                            <?php else : ?>
                                <input type="text" name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit" value="">
                            <?php endif; ?>
                        </label>
                    </div>
                </fieldset>
                <?php
            }
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
        add_action('save_post_' . $post_type, function (int $post_id, \WP_Post $post) use ($fields): void {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            if (!isset($_POST['acf_quick_edit_nonce']) || !wp_verify_nonce($_POST['acf_quick_edit_nonce'], 'acf_quick_edit_nonce')) {
                return;
            }

            error_log("ACF Quick Edit Columns: Saving Quick Edit data for post {$post_id}, post type {$post->post_type}");
            foreach ($fields as $column_key => $field) {
                $field_name = $field['field_name'];
                $input_name = "acf_{$field_name}";
                if (array_key_exists($input_name, $_POST)) {
                    $field_type = $field['type'];
                    $acf_field = acf_get_field($field_name);
                    $value = $_POST[$input_name];

                    if ($value === '' || ($field_type === 'select' && empty($value)) || ($field_type === 'checkbox' && empty($value))) {
                        error_log("ACF Quick Edit Columns: Clearing field {$field_name} for post {$post_id}");
                        update_field($field_name, $field_type === 'checkbox' ? [] : '', $post_id);
                    } else {
                        if ($field_type === 'image') {
                            $sanitized_value = absint($value);
                            error_log("ACF Quick Edit Columns: Saving image field {$field_name} with ID: {$sanitized_value}");
                            update_field($field_name, $sanitized_value, $post_id);
                        } elseif ($field_type === 'select') {
                            if (!empty($acf_field['multiple'])) {
                                $sanitized_value = array_map('sanitize_text_field', is_array($value) ? $value : [$value]);
                            } else {
                                $sanitized_value = sanitize_text_field($value);
                            }
                        } elseif ($field_type === 'checkbox') {
                            $sanitized_value = array_map('sanitize_text_field', is_array($value) ? $value : [$value]);
                        } elseif (in_array($field_type, ['textarea', 'wysiwyg'], true)) {
                            $sanitized_value = sanitize_textarea_field($value);
                        } else {
                            $sanitized_value = sanitize_text_field($value);
                        }
                        error_log("ACF Quick Edit Columns: Saving field {$field_name} with value: " . print_r($sanitized_value, true));
                        update_field($field_name, $sanitized_value, $post_id);
                    }
                } else {
                    error_log("ACF Quick Edit Columns: Field {$field_name} not updated (unset)");
                }
            }
        }, 10, 2);
    }

    // AJAX handler for fetching image field data
    add_action('wp_ajax_acf_quick_edit_get_image', function (): void {
        check_ajax_referer('acf_quick_edit_nonce', 'nonce');
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $field_name = isset($_POST['field_name']) ? sanitize_text_field($_POST['field_name']) : '';

        if (!$post_id || !$field_name) {
            wp_send_json_error(['message' => 'Invalid post ID or field name']);
        }

        $image = get_field($field_name, $post_id);
        if ($image && is_array($image)) {
            wp_send_json_success([
                'id' => $image['ID'] ?? '',
                'url' => $image['url'] ?? '',
                'title' => $image['title'] ?? $image['filename'] ?? '',
            ]);
        } else {
            wp_send_json_success(['id' => '', 'url' => '', 'title' => '']);
        }
    });
}

/**
 * Load plugin text domain for translations.
 */
function load_textdomain(): void
{
    load_plugin_textdomain('acf-quick-edit-columns', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

add_action('init', __NAMESPACE__ . '\\register_columns_and_quick_edit', 20);
add_action('plugins_loaded', __NAMESPACE__ . '\\load_textdomain');