(function($) {
    $(document).on('click', '.editinline', function() {
        var post_id = $(this).closest('tr').attr('id').replace('post-', '');
        var $row = $('#post-' + post_id);

        $.each(acfQuickEdit.fields, function(column_key, field_name) {
            var $cell = $row.find('td.column-' + column_key);
            var value = $cell.text().trim() !== '—' ? $cell.text().trim() : '';
            var $input = $('#edit-' + post_id).find('input[name="acf_' + field_name + '"], textarea[name="acf_' + field_name + '"]');
            $input.val(value);
        });

        // Add nonce to the form
        var $nonce = $('#edit-' + post_id).find('input[name="acf_quick_edit_nonce"]');
        if ($nonce.length === 0) {
            $('#edit-' + post_id).append('<input type="hidden" name="acf_quick_edit_nonce" value="' + acfQuickEdit.nonce + '">');
        }
    });
})(jQuery);