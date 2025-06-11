<?php
/**
 * Quick Edit field output callbacks for ACF Quick Edit Columns.
 *
 * Each function renders the Quick Edit field for a specific ACF field type.
 *
 * @package   AcfQuickEditColumns
 * @copyright Nathan Johnson
 * @license   GPL-2.0-or-later
 * @since     2.0.0
 */
declare(strict_types=1);
namespace AcfQuickEditColumns;

/**
 * Output a textarea field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_textarea_output( $field, $field_label, $field_name ) {
    ?>
    <textarea name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit"></textarea>
    <?php
}
add_action('acf_quick_edit_field_textarea', __NAMESPACE__ . '\acf_quick_edit_field_textarea_output', 10, 3);

/**
 * Output a text field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_text_output( $field, $field_label, $field_name ) {
    ?>
    <input type="text" name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit" value="">
    <?php
}
add_action('acf_quick_edit_field_text', __NAMESPACE__ . '\acf_quick_edit_field_text_output', 10, 3);

/**
 * Output a select field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_select_output( $field, $field_label, $field_name ) {
    ?>
    <select name="acf_<?php echo esc_attr($field_name); ?><?php echo !empty($field['multiple']) ? '[]' : ''; ?>" class="acf-quick-edit" <?php echo !empty($field['multiple']) ? 'multiple' : ''; ?>>
        <?php if (empty($field['multiple'])) : ?>
            <option value=""><?php esc_html_e('—', 'acf-quick-edit-columns'); ?></option>
        <?php endif; ?>
        <?php if (!empty($field['choices']) && is_array($field['choices'])) : foreach ($field['choices'] as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
        <?php endforeach; endif; ?>
    </select>
    <?php
}
add_action('acf_quick_edit_field_select', __NAMESPACE__ . '\acf_quick_edit_field_select_output', 10, 3);

/**
 * Output a checkbox field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_checkbox_output( $field, $field_label, $field_name ) {
    ?>
    <div class="acf-quick-edit-checkboxes acf-checkbox-grid">
        <?php if (!empty($field['choices']) && is_array($field['choices'])) : foreach ($field['choices'] as $value => $label) : ?>
            <label class="acf-checkbox">
                <input type="checkbox" name="acf_<?php echo esc_attr($field_name); ?>[]" value="<?php echo esc_attr($value); ?>" class="acf-quick-edit">
                <span class="acf-checkbox-label"><?php echo esc_html($label); ?></span>
            </label>
        <?php endforeach; endif; ?>
    </div>
    <?php
}
add_action('acf_quick_edit_field_checkbox', __NAMESPACE__ . '\acf_quick_edit_field_checkbox_output', 10, 3);

/**
 * Output an image field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_image_output( $field, $field_label, $field_name ) {
    ?>
    <div class="acf-quick-edit-image" data-field="<?php echo esc_attr($field_name); ?>">
        <div class="acf-image-preview" style="margin-bottom: 10px;">
            <img src="" alt="" style="max-width: 100px; height: auto; display: none;">
        </div>
        <p class="acf-image-filename" style="margin: 0 0 10px; font-size: 12px;"></p>
        <input type="hidden" name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit acf-image-id" value="">
        <button type="button" class="button acf-select-image"><?php esc_html_e('Select Image', 'acf-quick-edit-columns'); ?></button>
        <button type="button" class="button acf-remove-image" style="display: none;"><?php esc_html_e('Remove', 'acf-quick-edit-columns'); ?></button>
    </div>
    <?php
}
add_action('acf_quick_edit_field_image', __NAMESPACE__ . '\acf_quick_edit_field_image_output', 10, 3);

/**
 * Output a post object field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_post_object_output( $field, $field_label, $field_name ) {
    ?>
    <select name="acf_<?php echo esc_attr($field_name); ?><?php echo !empty($field['multiple']) ? '[]' : ''; ?>"
            class="acf-quick-edit-query acf-post-object"
            <?php echo !empty($field['multiple']) ? 'multiple' : ''; ?>>
        <option value=""><?php esc_html_e('Select', 'acf-quick-edit-columns'); ?></option>
    </select>
    <?php
}
add_action('acf_quick_edit_field_post_object', __NAMESPACE__ . '\acf_quick_edit_field_post_object_output', 10, 3);