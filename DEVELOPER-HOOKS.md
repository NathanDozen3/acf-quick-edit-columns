# ACF Quick Edit Columns: Developer Hooks & Filters

This document lists all available WordPress hooks (filters and actions) provided by the ACF Quick Edit Columns plugin, with arguments, usage, and real-world extension examples.

---

## Table of Contents
- [Supported Field Types Filter](#supported-field-types-filter)
- [Quick Edit Field Rendering](#quick-edit-field-rendering)
- [Quick Edit Field Sanitization](#quick-edit-field-sanitization)
- [Admin Column Output](#admin-column-output)
- [Other Actions & Filters](#other-actions--filters)
- [JavaScript Prefill Extension](#javascript-prefill-extension)

---

## Supported Field Types Filter

**Filter:** `acf_quick_edit_supported_field_types`

- **Purpose:** Add or remove field types supported in Quick Edit.
- **Arguments:**
  - `$types` (array): List of supported field type keys.
- **Example:**

```php
add_filter('acf_quick_edit_supported_field_types', function($types) {
    $types[] = 'star_rating'; // Add your custom type
    return $types;
});
```

---

## Quick Edit Field Rendering

**Filter:** `acf_quick_edit_render_field_{field_type}`

- **Purpose:** Render the Quick Edit field HTML for a given field type.
- **Arguments:**
  - `$output` (string|null): Default output (usually null).
  - `$field` (array): ACF field array.
  - `$post_id` (int): Post ID being edited.
- **Example:**

```php
add_filter('acf_quick_edit_render_field_star_rating', function($output, $field, $post_id) {
    $value = get_field($field['name'], $post_id);
    return '<input type="number" min="1" max="5" name="acf_' . esc_attr($field['name']) . '" value="' . esc_attr($value) . '" />';
}, 10, 3);
```

---

## Quick Edit Field Sanitization

**Filter:** `acf_quick_edit_sanitize_field_{field_type}`

- **Purpose:** Sanitize the value before saving from Quick Edit.
- **Arguments:**
  - `$value` (mixed): Raw value from $_POST.
  - `$field` (array): ACF field array.
  - `$post_id` (int): Post ID being saved.
- **Example:**

```php
add_filter('acf_quick_edit_sanitize_field_star_rating', function($value, $field, $post_id) {
    return intval($value); // Ensure integer
}, 10, 3);
```

---

## Admin Column Output

**Filter:** `acf_quick_edit_columns_{field_type}`

- **Purpose:** Customize the admin column output for a field type.
- **Arguments:**
  - `$output` (string): Default output (may be empty).
  - `$post_id` (int): Post ID.
  - `$field_name` (string): Field name.
  - `$field_type` (string): Field type key.
- **Example:**

```php
add_filter('acf_quick_edit_columns_star_rating', function($output, $post_id, $field_name, $field_type) {
    $value = get_field($field_name, $post_id);
    return esc_html($value) . ' stars';
}, 10, 4);
```

---

## Other Actions & Filters

- `acf_quick_edit_columns_placeholder` — Filter the placeholder for empty/unsupported values.
- `acf_quick_edit_field_choices` — Filter the choices for select, checkbox, or radio fields in Quick Edit.
- `acf_quick_edit_field_label` — Filter the label displayed for a field in Quick Edit.
- `acf_quick_edit_field_input_attrs` — Filter additional HTML attributes for Quick Edit field inputs.

---

## JavaScript Prefill Extension

To prefill your custom field in Quick Edit, listen for the `acfQuickEditPrefill` event in JS:

```js
jQuery(document).on('acfQuickEditPrefill', function(e, data) {
    // data.fieldType, data.fieldName, data.value, data.editRow
    if (data.fieldType === 'star_rating') {
        data.editRow.find('input[name="acf_' + data.fieldName + '"]').val(data.value);
    }
});
```

---

## Real-World Example: Star Rating Field

**PHP (functions.php or plugin):**
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

**JavaScript (admin enqueue or custom JS):**
```js
jQuery(document).on('acfQuickEditPrefill', function(e, data) {
    if (data.fieldType === 'star_rating') {
        data.editRow.find('input[name="acf_' + data.fieldName + '"]').val(data.value);
    }
});
```

---

For more, see the plugin source and the README's Extending section.
