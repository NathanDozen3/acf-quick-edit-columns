# ACF Quick Edit Columns

[![WordPress Plugin Version](https://img.shields.io/badge/version-1.5.4-blue)](https://github.com/NathanDozen3/acf-quick-edit-columns)
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