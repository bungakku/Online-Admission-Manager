/**
 * Online Admission Manager — Frontend behavior.
 * No external dependencies beyond jQuery (already bundled with WordPress).
 */
jQuery(document).ready(function ($) {
    'use strict';

    var MAX_FILE_SIZE = 300 * 1024; // 300KB, must match the server-side limit.

    // -------------------------------------------------------------------
    // Academic record rows
    // -------------------------------------------------------------------
    $('#add-academic-row').on('click', function () {
        var newRow = $('.academic-row:first').clone();
        newRow.find('input, textarea').val('');
        $('#academic-rows').append(newRow);
    });

    $(document).on('click', '.remove-row', function () {
        if ($('.academic-row').length > 1) {
            $(this).closest('.academic-row').remove();
        } else {
            window.alert('At least one academic record is required.');
        }
    });

    // -------------------------------------------------------------------
    // Live passport photo preview, shown next to the Name field
    // -------------------------------------------------------------------
    var lastPhotoDataUrl = null;

    $('#admPassportPhotoInput').on('change', function () {
        var file = this.files && this.files[0];
        var box = $('#admPhotoPreviewBox');

        if (!file) {
            lastPhotoDataUrl = null;
            box.html('<span class="adm-photo-preview-placeholder">No photo yet</span>');
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            // Still preview it locally so the applicant can see what they
            // picked, but the actual upload validation/rejection happens
            // on submit (and again on the server).
            box.html('<span class="adm-photo-preview-placeholder">File too large (max 300KB)</span>');
            lastPhotoDataUrl = null;
            return;
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            lastPhotoDataUrl = e.target.result;
            box.html('<img src="' + lastPhotoDataUrl + '" alt="Passport photo preview">');
        };
        reader.readAsDataURL(file);
    });

    // -------------------------------------------------------------------
    // Client-side file size validation before submit (server re-validates).
    // -------------------------------------------------------------------
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

    // -------------------------------------------------------------------
    // Print: build a static, read-only snapshot of everything the
    // applicant has entered so far (including the photo), and open it in
    // a new tab ready to print or save as PDF. Nothing here is submitted
    // to the server — it is all read directly from the live form in the
    // browser at the moment the button is clicked.
    // -------------------------------------------------------------------
    $('#printFormBtn').on('click', function () {
        if ($(this).prop('disabled')) {
            return;
        }
        openPrintPreview();
    });

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function collectFieldValues() {
        var values = {};
        $('#admissionForm [data-field]').each(function () {
            var key = $(this).data('field');
            values[key] = $(this).val();
        });
        return values;
    }

    function collectAcademicRows() {
        var rows = [];
        $('.academic-row').each(function () {
            var $row = $(this);
            var exam = $row.find('[name="academic_exam_name[]"]').val();
            // Skip fully empty rows (e.g. the template row left blank).
            if (!exam) {
                return;
            }
            rows.push({
                exam: exam,
                year: $row.find('[name="academic_year[]"]').val(),
                classDiv: $row.find('[name="academic_class_div[]"]').val(),
                percent: $row.find('[name="academic_percent[]"]').val(),
                board: $row.find('[name="academic_board[]"]').val(),
                subjects: $row.find('[name="academic_subjects[]"]').val()
            });
        });
        return rows;
    }

    // Field grid layout: label + data-field key, in display order.
    // Aadhar is intentionally excluded from the print snapshot — printed
    // pages get carried around, photographed, and left on desks, and the
    // applicant typed it once already; it stays in the form (and on the
    // server, encrypted) rather than on a piece of paper.
    var PRINT_FIELDS = [
        ['Email', 'email'],
        ['WhatsApp No.', 'contact1'],
        ['Alternate No.', 'contact2'],
        ["Father's Name", 'father_name'],
        ["Father's Contact 1", 'father_contact1'],
        ["Father's Contact 2", 'father_contact2'],
        ["Mother's Name", 'mother_name'],
        ["Mother's Contact 1", 'mother_contact1'],
        ["Mother's Contact 2", 'mother_contact2'],
        ['Permanent Address', 'permanent_address'],
        ['Present Address', 'present_address'],
        ['Pin Code', 'present_pin_code'],
        ['Date of Birth', 'dob'],
        ['Sex', 'sex'],
        ['Blood Group', 'blood_group'],
        ['Nationality', 'nationality'],
        ['Country (if foreign national)', 'country'],
        ['State of Domicile', 'state_domicile'],
        ['Category', 'category'],
        ['Last School/College', 'last_school'],
        ['Course Seeking Admission', 'course_seeking']
    ];

    function openPrintPreview() {
        var values = collectFieldValues();
        var academic = collectAcademicRows();
        var templateHtml = document.getElementById('admPrintTemplate').innerHTML;

        var printWin = window.open('', '_blank');
        if (!printWin) {
            window.alert('Your browser blocked the print preview popup. Please allow popups for this site and try again.');
            return;
        }

        printWin.document.open();
        printWin.document.write(
            '<!DOCTYPE html><html><head><meta charset="utf-8">' +
            '<title>Admission Application — Print Preview</title>' +
            '<style id="adm-print-style-placeholder"></style>' +
            '</head><body>' +
            '<div class="adm-print-toolbar"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>' +
            templateHtml +
            '</body></html>'
        );
        printWin.document.close();

        // Fetch and inline the dedicated print stylesheet so the new tab
        // is fully self-contained (no dependency on the parent page or
        // popup-blocked external requests).
        fetch(admMgrPrintCssUrl)
            .then(function (r) { return r.text(); })
            .then(function (css) {
                var styleTag = printWin.document.getElementById('adm-print-style-placeholder');
                if (styleTag) {
                    styleTag.textContent = css;
                }
                populatePrintWindow(printWin, values, academic);
            })
            .catch(function () {
                // Even if the stylesheet fetch fails, still populate the
                // data so the applicant isn't blocked from printing.
                populatePrintWindow(printWin, values, academic);
            });
    }

    function populatePrintWindow(printWin, values, academic) {
        var doc = printWin.document;

        // Name
        var nameEl = doc.querySelector('[data-print="name"]');
        if (nameEl) {
            nameEl.textContent = values.name || '';
        }

        // Passport photo
        var photoImg = doc.getElementById('admPrintPhotoImg');
        if (photoImg && lastPhotoDataUrl) {
            photoImg.src = lastPhotoDataUrl;
            photoImg.style.display = 'block';
        }

        // Field grid
        var grid = doc.getElementById('admPrintGrid');
        if (grid) {
            var rowsHtml = '';
            for (var i = 0; i < PRINT_FIELDS.length; i++) {
                var label = PRINT_FIELDS[i][0];
                var key = PRINT_FIELDS[i][1];
                var val = values[key] || '';
                rowsHtml += '<div class="adm-print-field">' +
                    '<span class="adm-print-label">' + escapeHtml(label) + '</span>' +
                    '<span class="adm-print-value">' + escapeHtml(val) + '</span>' +
                    '</div>';
            }
            grid.innerHTML = rowsHtml;
        }

        // Academic record rows
        var tbody = doc.getElementById('admPrintAcademicBody');
        if (tbody) {
            if (academic.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#999;">No academic records entered yet</td></tr>';
            } else {
                var bodyHtml = '';
                for (var j = 0; j < academic.length; j++) {
                    var rec = academic[j];
                    bodyHtml += '<tr>' +
                        '<td>' + escapeHtml(rec.exam) + '</td>' +
                        '<td>' + escapeHtml(rec.year) + '</td>' +
                        '<td>' + escapeHtml(rec.classDiv) + '</td>' +
                        '<td>' + escapeHtml(rec.percent) + '</td>' +
                        '<td>' + escapeHtml(rec.board) + '</td>' +
                        '<td>' + escapeHtml(rec.subjects) + '</td>' +
                        '</tr>';
                }
                tbody.innerHTML = bodyHtml;
            }
        }
    }

    // Show a confirmation message if redirected back with ?submitted=success.
    if (window.location.search.indexOf('submitted=success') > -1) {
        $('<div class="admission-message success">Application submitted successfully! You can now close this page.</div>')
            .insertBefore('#admissionForm');
    }
});
