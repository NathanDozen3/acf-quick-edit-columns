<?php
/**
 * Quick Edit field rendering callbacks for ACF Quick Edit Columns.
 *
 * Contains rendering logic for each supported ACF field type in the Quick Edit UI.
 * Each function is registered to a filter of the form 'acf_quick_edit_render_field_{field_type}'.
 *
 * @package   AcfQuickEditColumns
 * @author    Nathan Johnson
 * @copyright 2024 Nathan Johnson
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
 *
 * Example: Render a text field in Quick Edit
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

/**
 * Output a password field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_password_output( $field, $field_label, $field_name ) {
    ?>
    <input type="password" name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit" value="">
    <?php
}
add_action('acf_quick_edit_field_password', __NAMESPACE__ . '\acf_quick_edit_field_password_output', 10, 3);

/**
 * Output an email field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_email_output( $field, $field_label, $field_name ) {
    ?>
    <input type="email" name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit" value="">
    <?php
}
add_action('acf_quick_edit_field_email', __NAMESPACE__ . '\acf_quick_edit_field_email_output', 10, 3);

/**
 * Output a URL field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_url_output( $field, $field_label, $field_name ) {
    ?>
    <input type="url" name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit" value="">
    <?php
}
add_action('acf_quick_edit_field_url', __NAMESPACE__ . '\acf_quick_edit_field_url_output', 10, 3);

/**
 * Output a radio field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_radio_output( $field, $field_label, $field_name ) {
    if ( ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) : ?>
        <div class="acf-quick-edit-radios acf-radio-grid">
            <?php foreach ( $field['choices'] as $value => $label ) : ?>
                <label class="acf-radio">
                    <input type="radio" name="acf_<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="acf-quick-edit">
                    <span class="acf-radio-label"><?php echo esc_html( $label ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    <?php endif;
}
add_action('acf_quick_edit_field_radio', __NAMESPACE__ . '\acf_quick_edit_field_radio_output', 10, 3);

/**
 * Output a true/false field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_true_false_output( $field, $field_label, $field_name ) {
    ?>
    <label class="acf-quick-edit-true-false">
        <input type="checkbox" name="acf_<?php echo esc_attr( $field_name ); ?>" value="1" class="acf-quick-edit">
        <span><?php echo esc_html( $field_label ); ?></span>
    </label>
    <?php
}
add_action('acf_quick_edit_field_true_false', __NAMESPACE__ . '\acf_quick_edit_field_true_false_output', 10, 3);

/**
 * Output a number field for Quick Edit.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 */
function acf_quick_edit_field_number_output( $field, $field_label, $field_name ) {
    ?>
    <input type="number" name="acf_<?php echo esc_attr( $field_name ); ?>" class="acf-quick-edit" value="">
    <?php
}
add_action('acf_quick_edit_field_number', __NAMESPACE__ . '\acf_quick_edit_field_number_output', 10, 3 );

/**
 * Output a WYSIWYG (TinyMCE) field for Quick Edit.
 *
 * Note: Due to WordPress and ACF limitations, the full WYSIWYG (TinyMCE) editor cannot be loaded in Quick Edit.
 * This callback renders a simple textarea for editing the field content. For full WYSIWYG features, use the main Edit screen.
 *
 * @param array  $field      The ACF field array.
 * @param string $field_label The field label.
 * @param string $field_name  The field name (meta key).
 *
 * Example: Render a wysiwyg field as a textarea (no TinyMCE in Quick Edit)
 */
function acf_quick_edit_field_wysiwyg_output( $field, $field_label, $field_name ) {
    // WYSIWYG fields cannot load TinyMCE in Quick Edit. Render as textarea only.
    ?>
    <textarea name="acf_<?php echo esc_attr($field_name); ?>" class="acf-quick-edit" rows="4" style="width:100%"></textarea>
    <p class="description" style="font-size:11px; color:#666; margin:2px 0 0;">
        <?php esc_html_e('Note: Full WYSIWYG editing is only available in the main Edit screen.', 'acf-quick-edit-columns'); ?>
    </p>
    <?php
}
add_action('acf_quick_edit_field_wysiwyg', __NAMESPACE__ . '\acf_quick_edit_field_wysiwyg_output', 10, 3);