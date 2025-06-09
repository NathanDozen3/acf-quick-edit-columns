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