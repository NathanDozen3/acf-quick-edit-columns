# ACF Quick Edit Columns

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.5.8-blue)](https://github.com/NathanDozen3/acf-quick-edit-columns)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress Tested](https://img.shields.io/badge/WordPress-6.6%2B-blue)](https://wordpress.org)

A WordPress plugin that enhances the admin interface by adding Advanced Custom Fields (ACF) as sortable columns and pre-populated Quick Edit fields for all public custom post types (CPTs). Built for flexibility and ease of use, it streamlines content management for any WordPress site using ACF.

Developed by [Twelve Three Media](https://www.digitalmarketingcompany.com/).

## Features

- **Dynamic ACF Columns**: Automatically adds columns for ACF fields (text, textarea, wysiwyg, select, checkbox, image) assigned to public CPTs, with `acf_` prefixes to avoid conflicts (e.g., `acf_title` for a `title` field). Flexible content fields are excluded.
- **Pre-populated Quick Edit**: Enables Quick Edit fields for supported ACF field types, pre-filled with existing values using JavaScript for a seamless editing experience.
- **Clearable Fields**: Allows clearing ACF fields by submitting empty inputs in Quick Edit.
- **Sortable Columns**: All ACF columns are sortable in the admin list table.
- **Broad Compatibility**: Works with any public CPT and supported ACF field types; extensible for additional types.
- **Enhanced Image UI**: Supports image fields with a thumbnail preview, file name, and media library integration in Quick Edit, mimicking ACF’s edit screen.
- **Secure and Modern**: Includes nonce verification, namespaced code (`AcfQuickEditColumns`), and strict typing for reliability.
- **Debugging Support**: Comprehensive error logging to aid troubleshooting.

## Requirements

- **WordPress**: Version 5.0 or higher (tested up to 6.6+).
- **Advanced Custom Fields**: Free or Pro, version 5.0 or higher.
- **PHP**: Version 7.4 or higher (strict typing enabled).

## Installation

1. **Download the Plugin**:
   - Clone the repository:
     ```bash
     git clone https://github.com/NathanDozen3/acf-quick-edit-columns.git
     ```
   - Or download the ZIP from the [Releases](https://github.com/NathanDozen3/acf-quick-edit-columns/releases) page.

2. **Install the Plugin**:
   - Upload the `acf-quick-edit-columns` folder to `wp-content/plugins/`.
   - Alternatively, install via WordPress admin: **Plugins > Add New > Upload Plugin**.

3. **Activate the Plugin**:
   - Go to **Plugins > Installed Plugins** and activate "ACF Quick Edit Columns".
   - Ensure ACF is installed and active.

4. **Configure ACF**:
   - In **Custom Fields > Field Groups**, assign ACF fields (e.g., `title` labeled 'Location', `image` labeled 'Photo' as image) to your CPTs (e.g., `testimonials`).

## Usage

1. **Access CPT Admin Screen**:
   - Navigate to a CPT admin screen (e.g., `https://yoursite.com/wp-admin/edit.php?post_type=testimonials`).
   - Verify that supported ACF fields appear as columns (e.g., 'Location', 'Photo'). Flexible content fields are not shown.

2. **Use Quick Edit**:
   - Click "Quick Edit" on a post row.
   - Edit pre-populated ACF fields:
     - Text inputs for text fields (e.g., `title`).
     - Textareas for textarea or wysiwyg fields.
     - Dropdowns for select fields, supporting single or multiple selections.
     - Checkboxes for checkbox fields, allowing multiple selections.
     - Image fields with a thumbnail preview, file name, “Select Image” button to open the media library, and “Remove” button to clear the image.
   - To clear a field, leave the input empty (text, textarea, select), uncheck all options (checkbox), or click “Remove” (image).
   - Click "Update" to save changes, which reflect in the columns.

3. **Sort Columns**:
   - Click column headers to sort by ACF field values.

4. **Debugging**:
   - Enable debugging in `wp-config.php`:
     ```php
     define('WP_DEBUG', true);
     define('WP_DEBUG_LOG', true);
     define('WP_DEBUG_DISPLAY', false);
     ```
   - Check `wp-content/debug.log` for logs prefixed with `ACF Quick Edit Columns:`.

## Troubleshooting

- **Columns or Fields Missing**:
  - Ensure ACF fields are assigned to the CPT in **Custom Fields > Field Groups**.
  - Verify the CPT supports Quick Edit:
    ```php
    add_action('init', function() {
        add_post_type_support('your-cpt', 'custom-fields');
    });
    ```
  - Check `debug.log` for `Fields for CPT [name]` or `Running quick_edit_custom_box`.

- **Flexible Content Fields Appearing**:
  - Confirm the field is set as `flexible_content` in ACF settings.
  - Check `debug.log` for `Skipping flexible content field: [field_name]`.
  - Ensure plugin version is 1.5.0 or higher.

- **Pre-population Not Working**:
  - Confirm `acf-quick-edit.js` is loaded (check page source for script tag).
  - In browser console, verify:
    ```javascript
    console.log(acfQuickEdit);
    ```
  - For image fields, check AJAX response for `acf_quick_edit_get_image` in browser dev tools (Network tab).

- **Image Fields Not Rendering or Saving**:
  - Ensure the image field is set to return an array in ACF settings.
  - Check `debug.log` for `Saving image field` or `Clearing field`. Inspect `$_POST`:
    ```php
    error_log('ACF Quick Edit Columns: POST data: ' . print_r($_POST, true));
    ```
  - Verify `wp.media` is enqueued (check for media library scripts in page source).
  - Ensure the image ID is valid and exists in the media library.

- **Values Not Saving or Clearing**:
  - Check `debug.log` for `Saving Quick Edit data` or `Clearing field`.
  - Ensure nonce verification passes.
  - For select/checkbox, verify field choices match saved values.

- **Title Field Conflict**:
  - If an ACF field named `title` causes issues, rename it (e.g., `location_title`):
    ```sql
    UPDATE wp_postmeta
    SET meta_key = 'location_title'
    WHERE meta_key = 'title' AND post_id IN (
        SELECT ID FROM wp_posts WHERE post_type = 'your-cpt'
    );
    ```

- **Compatibility Issues**:
  - Test with a default theme (e.g., Twenty Twenty-Five) and only ACF active.
  - Ensure ACF is updated.

## Core ACF Field Types Quick Edit Support Matrix

The following table summarizes the Quick Edit support for each core ACF field type in this plugin. "Fully Supported" means the field type has a Quick Edit UI, JS prefill, column output, and save/sanitize logic. "Partially Supported" means the field type is only supported in the admin column (and saving), but does not have a Quick Edit UI (or, in the case of WYSIWYG, is rendered as a plain textarea, so advanced features are not available).

| Field Type         | Quick Edit UI         | Prefill/JS | Column Output | Save/Sanitize | Notes                                                      | Support Level        |
|--------------------|:--------------------:|:----------:|:-------------:|:-------------:|------------------------------------------------------------|---------------------|
| text               | Yes                  | Yes        | Yes           | Yes           |                                                            | Fully Supported     |
| textarea           | Yes                  | Yes        | Yes           | Yes           |                                                            | Fully Supported     |
| wysiwyg            | Yes (as textarea)    | Yes        | Yes           | Yes           | Rendered as textarea, no rich editor in Quick Edit         | Partially Supported |
| select             | Yes                  | Yes        | Yes           | Yes           | Supports single/multiple                                   | Fully Supported     |
| checkbox           | Yes                  | Yes        | Yes           | Yes           | Supports multiple                                          | Fully Supported     |
| radio              | Yes                  | Yes        | Yes           | Yes           |                                                            | Fully Supported     |
| true_false         | Yes                  | Yes        | Yes           | Yes           |                                                            | Fully Supported     |
| image              | Yes                  | Yes        | Yes           | Yes           | Media library, preview, remove                             | Fully Supported     |
| number             | Yes                  | Yes        | Yes           | Yes           |                                                            | Fully Supported     |
| email              | Yes                  | Yes        | Yes           | Yes           |                                                            | Fully Supported     |
| url                | Yes                  | Yes        | Yes           | Yes           |                                                            | Fully Supported     |
| password           | Yes                  | Yes        | Yes           | Yes           | Column output is masked                                    | Fully Supported     |
| post_object        | Yes                  | Yes        | Yes           | Yes           | Select2 AJAX                                               | Fully Supported     |
| file               | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| gallery            | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| date_picker        | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| datetime_picker    | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| time_picker        | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| oembed             | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| relationship       | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| page_link          | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| user               | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| taxonomy           | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| google_map         | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| color_picker       | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| repeater           | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| group              | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |
| clone              | No                   | N/A        | Yes           | Yes           | No Quick Edit UI                                           | Partially Supported |

**Legend:**
- **Quick Edit UI**: Field is rendered in the Quick Edit box.
- **Prefill/JS**: Field is pre-populated with the current value via JavaScript.
- **Column Output**: Field value is shown in the admin list table column.
- **Save/Sanitize**: Field value is saved and sanitized when Quick Edit is used.
- **Support Level**: "Fully Supported" = all features; "Partially Supported" = only column output/saving, or limited UI.

If you want to add support for more field types, see the "Extending: Supporting Custom ACF Field Types" section below.

## Extending: Supporting Custom ACF Field Types

ACF Quick Edit Columns is designed to be extensible. You can add support for your own custom ACF field types (or those provided by other plugins) in Quick Edit by using the provided WordPress hooks.

### 1. Register Your Field Type for Quick Edit

Add your field type to the list of supported types:

```php
add_filter('acf_quick_edit_supported_field_types', function($types) {
    $types[] = 'my_custom_type'; // Replace with your field type key
    return $types;
});
```

### 2. Render Your Field in Quick Edit

Provide the HTML for your field in the Quick Edit box:

```php
add_filter('acf_quick_edit_render_field_my_custom_type', function($output, $field, $post_id) {
    // Example: Render a text input for your custom field
    $value = get_field($field['name'], $post_id);
    return '<input type="text" name="acf_' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" />';
}, 10, 3);
```

### 3. Sanitize Your Field Value on Save

Sanitize the value before it is saved:

```php
add_filter('acf_quick_edit_sanitize_field_my_custom_type', function($value, $field, $post_id) {
    // Example: Basic text sanitization
    return sanitize_text_field($value);
}, 10, 3);
```

### 4. (Optional) Display Your Field in the Admin Column

To customize how your field appears in the admin column, use:

```php
add_filter('acf_quick_edit_columns_my_custom_type', function($output, $post_id, $field_name, $field_type) {
    $value = get_field($field_name, $post_id);
    // Format and return your display value
    return esc_html($value);
}, 10, 4);
```

### Understanding the Filter Names for Custom Field Types

The filter names shown above use a placeholder (e.g., `my_custom_type`) to represent your custom ACF field type's key. This key is the value you use when registering your custom field type in ACF. The plugin uses dynamic filter names based on the field type, so you must replace `my_custom_type` with your actual field type key.

**For example:**

If your custom field type is `star_rating`, you would use:

- `acf_quick_edit_supported_field_types` to add `'star_rating'` to the supported types array.
- `acf_quick_edit_render_field_star_rating` to render the field in Quick Edit.
- `acf_quick_edit_sanitize_field_star_rating` to sanitize the value on save.
- `acf_quick_edit_columns_star_rating` to customize the admin column display.

**Example code:**

```php
add_filter('acf_quick_edit_supported_field_types', function($types) {
    $types[] = 'star_rating';
    return $types;
});

add_filter('acf_quick_edit_render_field_star_rating', function($output, $field, $post_id) {
    $value = get_field($field['name'], $post_id);
    return '<input type="number" min="1" max="5" name="acf_' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" />';
}, 10, 3);

add_filter('acf_quick_edit_sanitize_field_star_rating', function($value, $field, $post_id) {
    return intval($value);
}, 10, 3);

add_filter('acf_quick_edit_columns_star_rating', function($output, $post_id, $field_name, $field_type) {
    $value = get_field($field_name, $post_id);
    return esc_html($value) . ' stars';
}, 10, 4);
```

**Summary:**
Replace `my_custom_type` in the filter name with your actual field type key (e.g., `star_rating`). This tells the plugin which field type your filter applies to.

---

**That’s it!**  
Your custom field type will now be available in Quick Edit, will be sanitized on save, and can be displayed in the admin columns.

For more advanced use, see the plugin’s source code and the [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/).

## Contributing

Contributions are welcome! To contribute:

1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/your-feature`.
3. Commit changes: `git commit -m "Add your feature"`.
4. Push to the branch: `git push origin feature/your-feature`.
5. Open a pull request.

Please include tests and follow WordPress coding standards.

## License

This plugin is licensed under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html).

## Support

For issues, feature requests, or questions:
- Open an issue on [GitHub](https://github.com/NathanDozen3/acf-quick-edit-columns/issues).
- Contact [Twelve Three Media](https://www.digitalmarketingcompany.com/).

## Changelog

### 1.5.9 (2025-06-11)
- Consistent, translatable placeholder for all admin column outputs via acf_qec_placeholder().
- Added plugin version constant (ACF_QEC_VERSION) and used for all asset enqueues.
- Minor code polish and documentation improvements.

### 1.5.8 (2025-06-11)
- Refactored and modularized main plugin files for maintainability and extensibility.
- Added/updated file-level and function doc blocks across all major PHP files.
- Documented all dynamic actions and filters for extensibility.
- Improved README with changelog, support matrix, extension instructions, and clarified filter naming.
- Added Quick Edit field rendering callbacks and JS prefill for: email, url, password, radio, true_false, number, wysiwyg (as textarea).
- Fixed Quick Edit save logic to use array_key_exists for all fields, ensuring empty values are saved/cleared (especially for password and true_false fields).
- Ensured password fields are saved and cleared properly from Quick Edit, and column output is masked.
- Updated column output for password fields to display asterisks.
- Added wysiwyg_output() for safe HTML rendering in admin columns.
- Updated changelog entries for versions 1.5.6, 1.5.7, and added 1.5.8 for wysiwyg improvements.
- Updated check_acf() to clarify support for both ACF (Free/Pro) and SCF.
- Combined the full and partial support tables in README into a single matrix, and marked wysiwyg as "Partially Supported" (rendered as textarea).
- Clarified in documentation and code comments that wysiwyg is only partially supported and why.
- Added a dedicated wysiwyg_output() function for column output, using wp_kses_post for safe HTML rendering.
- Reviewed and documented consistency between column output and AJAX prefill for all field types.
- Added summary comments in code documenting the consistency review for all field output callbacks and AJAX prefill.
- Updated plugin version to 1.5.8 in all relevant files (plugin header, enqueued scripts, README badge).

### 1.5.7
- Added Quick Edit support for password, email, and URL fields.
- Improved save logic for true_false and password fields.
- Updated documentation and support matrix.

### 1.5.6
- Added Quick Edit support for radio and number fields.
- Improved JS prefill logic for all supported field types.
- Updated documentation and changelog.

### 1.5.5 (2025-06-10)
- Security: Always applies built-in sanitization for core ACF field types in Quick Edit, even if a custom filter is present, ensuring safe fallback for all core types.
- Refactor: Added `sanitize_core_quick_edit_field_value()` fallback logic to `save_post` for robust sanitization.
- Maintenance: Version bump and documentation update.

### 1.5.4 (2025-06-10)
- Fixed: ACF image fields in admin columns now render as images instead of raw HTML code. Only image fields allow HTML output; all other fields remain escaped for security.
- Improved: Output escaping and field type handling in admin column rendering for better security and compatibility.
- Documentation and version bump.

### 1.5.3 (2025-06-10)
- Added `get_acf_fields_by_post_type()` utility for retrieving ACF fields by post type.
- Improved Quick Edit save logic to use the new utility and better field sanitization.
- Enhanced Select2 AJAX integration for post object fields, ensuring grouped results by post type.
- Improved error logging and code comments for maintainability.
- Minor code and documentation cleanups.

### 1.5.2 (2025-06-09)
- Improved Select2 integration for post object fields: results are now grouped by post type (optgroups) in Quick Edit, matching the main post edit page.
- Fixed Select2 AJAX response structure to ensure compatibility with grouped results.
- Enhanced debugging and error logging for AJAX handlers.
- Improved compatibility with all public custom post types, posts, pages, and media in post object searches.
- Minor code cleanup and adherence to WordPress coding standards.

### 1.5.1 (2025-05-20)
- Enhanced checkbox UI in Quick Edit with a responsive grid layout and improved styling.

### 1.5.0 (2025-05-19)
- Excluded ACF flexible content fields from Quick Edit and admin columns.

### 1.4.1 (2025-05-19)
- Fixed pre-population of ACF image fields in Quick Edit to show thumbnail, file name, and remove button.

### 1.4.0 (2025-05-19)
- Added support for ACF image fields in Quick Edit with thumbnail preview, file name, and media library integration.

### 1.3.0 (2025-05-19)
- Added support for ACF select and checkbox field types in Quick Edit and columns.

### 1.2.0 (2025-05-19)
- Added support for clearing ACF fields by submitting empty inputs in Quick Edit.

### 1.1.0 (2025-05-19)
- Added JavaScript for pre-populating Quick Edit fields.
- Introduced `AcfQuickEditColumns` namespace and strict typing.
- Simplified function names (removed `acf_qec_` prefixes).
- Added nonce verification for Quick Edit saves.
- Updated author to "Twelve Three Media".
- Improved debugging logs.

### 1.0.0
- Initial release with ACF column and Quick Edit support (no pre-population).

## Credits

Developed by [Twelve Three Media](https://www.digitalmarketingcompany.com/). Built with ❤️ for the WordPress community.