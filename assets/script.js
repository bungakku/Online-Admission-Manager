/**
 * Online Admission Manager — Frontend behavior.
 * No external dependencies beyond jQuery (already bundled with WordPress).
 */
jQuery(document).ready(function ($) {
    'use strict';

    var MAX_FILE_SIZE = 300 * 1024; // 300KB, must match the server-side limit.

    // Add new academic record row, based on the first row's structure.
    $('#add-academic-row').on('click', function () {
        var newRow = $('.academic-row:first').clone();
        newRow.find('input, textarea').val('');
        $('#academic-rows').append(newRow);
    });

    // Remove an academic row (always keep at least one).
    $(document).on('click', '.remove-row', function () {
        if ($('.academic-row').length > 1) {
            $(this).closest('.academic-row').remove();
        } else {
            window.alert('At least one academic record is required.');
        }
    });

    // Client-side file size validation before submit (server re-validates too).
    $('#admissionForm').on('submit', function (e) {
        var valid = true;

        $(this).find('input[type="file"]').each(function () {
            var files = this.files;
            for (var i = 0; i < files.length; i++) {
                if (files[i].size > MAX_FILE_SIZE) {
                    window.alert('File "' + files[i].name + '" exceeds the 300KB limit.');
                    valid = false;
                    return false;
                }
            }
        });

        if (!valid) {
            e.preventDefault();
        }
    });

    // Print the form using the browser's native print dialog. The @media print
    // rules in style.css handle hiding controls and showing the header.
    $('#printFormBtn').on('click', function () {
        if (!$(this).prop('disabled')) {
            window.print();
        }
    });

    // Show a confirmation message if redirected back with ?submitted=success.
    if (window.location.search.indexOf('submitted=success') > -1) {
        $('<div class="admission-message success">Application submitted successfully! You can now close this page.</div>')
            .insertBefore('#admissionForm');
    }
});
