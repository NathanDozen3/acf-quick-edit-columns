<?php
/*
Plugin Name: ACF Quick Edit Columns
Description: Adds ACF fields as columns and Quick Edit fields for custom post types in the WordPress admin, with pre-populated values.
Version: 1.1.0
Author: Your Name
License: GPL-2.0+
Text Domain: acf-quick-edit-columns
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if ACF is active
function acf_qec_check_acf() {
    if (!function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__('ACF Quick Edit Columns requires Advanced Custom Fields to be installed and activated.', 'acf-quick-edit-columns') . '</p></div>';
        });
        return false;
    }
    return true;
}

// Get all custom post types and their ACF fields
function acf_qec_get_custom_post_types_and_acf_fields() {
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
                        'type' => $field['type']
                    ];
                }
            }
        }

        if (!empty($fields)) {
            $cpt_fields[$cpt->name] = $fields;
            error_log('ACF QEC: Fields for CPT ' . $cpt->name . ': ' . print_r($fields, true));
        }
    }

    error_log('ACF QEC: All CPT fields: ' . print_r($cpt_fields, true));
    return $cpt_fields;
}

// Register columns, Quick Edit, and JavaScript
function acf_qec_register_columns_and_quick_edit() {
    if (!acf_qec_check_acf()) {
        return;
    }

    $cpt_fields = acf_qec_get_custom_post_types_and_acf_fields();

    foreach ($cpt_fields as $post_type => $fields) {
        // Add columns filter
        add_filter("manage_{$post_type}_posts_columns", function($columns) use ($post_type, $fields) {
            error_log("ACF QEC: Running manage_{$post_type}_posts_columns filter");
            $new_columns = [];
            foreach ($columns as $key => $value) {
                $new_columns[$key] = $value;
                if ($key === 'title') {
                    foreach ($fields as $column_key => $field_data) {
                        $new_columns[$column_key] = __($field_data['label'], 'acf-quick-edit-columns');
                    }
                }
            }
            error_log("ACF QEC: Columns for {$post_type}: " . print_r($new_columns, true));
            return $new_columns;
        });

        // Populate columns action
        add_action("manage_{$post_type}_posts_custom_column", function($column, $post_id) use ($post_type, $fields) {
            error_log("ACF QEC: Running manage_{$post_type}_posts_custom_column for column {$column}, post {$post_id}");
            if (isset($fields[$column])) {
                $field_name = $fields[$column]['field_name'];
                $value = get_field($field_name, $post_id);
                error_log("ACF QEC: Field {$field_name} for post {$post_id}: " . print_r($value, true));
                if (is_array($value)) {
                    echo esc_html(implode(', ', $value) ?: '—');
                } else {
                    echo esc_html($value ?: '—');
                }
            }
        }, 10, 2);

        // Make columns sortable
        add_filter("manage_edit-{$post_type}_sortable_columns", function($columns) use ($fields) {
            foreach ($fields as $column_key => $field_data) {
                $columns[$column_key] = $column_key;
            }
            return $columns;
        });

        // Add Quick Edit fields
        add_action('quick_edit_custom_box', function($column_name, $screen_post_type) use ($post_type, $fields) {
            if ($screen_post_type !== $post_type) {
                return;
            }
            error_log("ACF QEC: Running quick_edit_custom_box for column {$column_name}, post type {$post_type}");
            if (isset($fields[$column_name])) {
                $field_name = $fields[$column_name]['field_name'];
                $field_label = $fields[$column_name]['label'];
                $field_type = $fields[$column_name]['type'];
                ?>
                <fieldset class="inline-edit-col-right">
                    <div class="inline-edit-col">
                        <label>
                            <span class="title"><?php echo esc_html($field_label); ?></span>
                            <?php if (in_array($field_type, ['textarea', 'wysiwyg'])) : ?>
                                <textarea name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit"></textarea>
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

    // Enqueue JavaScript for pre-populating Quick Edit fields
    add_action('admin_enqueue_scripts', function($hook) use ($cpt_fields) {
        if ($hook !== 'edit.php') {
            return;
        }
        $post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
        if (!isset($cpt_fields[$post_type])) {
            return;
        }

        wp_enqueue_script(
            'acf-quick-edit',
            plugin_dir_url(__FILE__) . 'assets/acf-quick-edit.js',
            ['jquery', 'inline-edit-post'],
            '1.0.0',
            true
        );
        $field_data = [];
        foreach ($cpt_fields[$post_type] as $column_key => $field) {
            $field_data[$column_key] = $field['field_name'];
        }
        wp_localize_script('acf-quick-edit', 'acfQuickEdit', [
            'fields' => $field_data,
            'nonce' => wp_create_nonce('acf_quick_edit_nonce')
        ]);
    });

    // Save Quick Edit data
    foreach ($cpt_fields as $post_type => $fields) {
        add_action('save_post_' . $post_type, function($post_id, $post) use ($fields) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            if (!isset($_POST['acf_quick_edit_nonce']) || !wp_verify_nonce($_POST['acf_quick_edit_nonce'], 'acf_quick_edit_nonce')) {
                return;
            }

            error_log("ACF QEC: Saving Quick Edit data for post {$post_id}, post type {$post->post_type}");
            foreach ($fields as $column_key => $field) {
                $field_name = $field['field_name'];
                $input_name = "acf_{$field_name}";
                if (isset($_POST[$input_name])) {
                    $value = in_array($field['type'], ['textarea', 'wysiwyg']) ? sanitize_textarea_field($_POST[$input_name]) : sanitize_text_field($_POST[$input_name]);
                    error_log("ACF QEC: Saving field {$field_name} with value: {$value}");
                    update_field($field_name, $value, $post_id);
                } else {
                    error_log("ACF QEC: Field {$field_name} not updated (empty or unchanged)");
                }
            }
        }, 10, 2);
    }
}
add_action('init', 'acf_qec_register_columns_and_quick_edit', 20);

// Load plugin text domain for translations
function acf_qec_load_textdomain() {
    load_plugin_textdomain('acf-quick-edit-columns', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'acf_qec_load_textdomain');