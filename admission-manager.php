<?php
/**
 * Plugin Name:       Online Admission Manager
 * Plugin URI:        https://github.com/biswazit/admission-manager
 * Description:       Complete online admission form with academic records, file uploads, admin panel, date control, email confirmation, CSV export, and payment QR code.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Biswajit Thokchom
 * Author URI:        https://biswazit.in
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       admission-mgr
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ADM_MGR_VERSION', '1.0.0');
define('ADM_MGR_PATH', plugin_dir_path(__FILE__));
define('ADM_MGR_URL', plugin_dir_url(__FILE__));
define('ADM_MGR_UPLOAD_DIR', 'admission_uploads');
define('ADM_MGR_DB_VERSION', '1.0.0');

/**
 * Activation hook: create/update database tables and upload directory
 */
register_activation_hook(__FILE__, 'adm_mgr_activate');
function adm_mgr_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Main submissions table (with email column)
    $table_main = $wpdb->prefix . 'admission_submissions';
    $sql_main = "CREATE TABLE $table_main (
        id INT AUTO_INCREMENT PRIMARY KEY,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        contact1 VARCHAR(20) NOT NULL,
        contact2 VARCHAR(20),
        father_name VARCHAR(255) NOT NULL,
        father_contact1 VARCHAR(20),
        father_contact2 VARCHAR(20),
        mother_name VARCHAR(255) NOT NULL,
        mother_contact1 VARCHAR(20),
        mother_contact2 VARCHAR(20),
        permanent_address TEXT NOT NULL,
        present_address TEXT NOT NULL,
        present_pin_code VARCHAR(10),
        dob DATE NOT NULL,
        sex VARCHAR(10) NOT NULL,
        nationality VARCHAR(50) NOT NULL,
        blood_group VARCHAR(5),
        aadhar_number VARCHAR(20) NOT NULL,
        country VARCHAR(100),
        state_domicile VARCHAR(100) NOT NULL,
        category VARCHAR(10) NOT NULL,
        last_school VARCHAR(255) NOT NULL,
        course_seeking VARCHAR(255) NOT NULL,
        passport_photo VARCHAR(255) NOT NULL,
        payment_proof VARCHAR(255),
        scanned_documents TEXT,
        PRIMARY KEY  (id),
        KEY idx_created (created_at)
    ) $charset_collate;";

    // Academic records table
    $table_academic = $wpdb->prefix . 'admission_academic_records';
    $sql_academic = "CREATE TABLE $table_academic (
        id INT AUTO_INCREMENT PRIMARY KEY,
        submission_id INT NOT NULL,
        exam_name VARCHAR(255) NOT NULL,
        year_passing VARCHAR(10) NOT NULL,
        class_division VARCHAR(100),
        percentage_marks VARCHAR(20),
        board_university VARCHAR(255),
        subjects_offered TEXT,
        PRIMARY KEY  (id),
        KEY idx_submission (submission_id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_main);
    dbDelta($sql_academic);

    update_option('adm_mgr_db_version', ADM_MGR_DB_VERSION);

    // Create upload directory with protection
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/' . ADM_MGR_UPLOAD_DIR;
    if (!file_exists($plugin_upload_dir)) {
        wp_mkdir_p($plugin_upload_dir);
    }
    if (!file_exists($plugin_upload_dir . '/index.html')) {
        file_put_contents($plugin_upload_dir . '/index.html', '');
    }
    if (!file_exists($plugin_upload_dir . '/.htaccess')) {
        $htaccess_content = "Options -Indexes\nDeny from all";
        file_put_contents($plugin_upload_dir . '/.htaccess', $htaccess_content);
    }
}

register_uninstall_hook(__FILE__, 'adm_mgr_uninstall');
function adm_mgr_uninstall() {
    // Only remove data if the admin has explicitly opted in via Settings.
    // This avoids silently destroying admission records on accidental deactivation/removal.
    if (get_option('adm_mgr_delete_data_on_uninstall') !== '1') {
        return;
    }

    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}admission_academic_records");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}admission_submissions");

    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/' . ADM_MGR_UPLOAD_DIR;
    if (file_exists($plugin_upload_dir)) {
        $files = glob("$plugin_upload_dir/*.*");
        if ($files) {
            array_map('unlink', $files);
        }
        @rmdir($plugin_upload_dir);
    }

    $options = array(
        'adm_mgr_start_date', 'adm_mgr_end_date', 'adm_mgr_institute_name',
        'adm_mgr_institute_logo', 'adm_mgr_payment_qr', 'adm_mgr_admin_email',
        'adm_mgr_confirmation_subject', 'adm_mgr_confirmation_message',
        'adm_mgr_db_version', 'adm_mgr_delete_data_on_uninstall',
        'adm_mgr_tagline', 'adm_mgr_logo_width', 'adm_mgr_header_align',
        'adm_mgr_title_font_size', 'adm_mgr_tagline_font_size',
    );
    foreach ($options as $option) {
        delete_option($option);
    }
}

/**
 * Enqueue frontend scripts and styles only on pages using the shortcode.
 */
add_action('wp_enqueue_scripts', 'adm_mgr_frontend_assets');
function adm_mgr_frontend_assets() {
    if (!is_singular()) {
        return;
    }
    $post = get_post();
    if (!$post || !has_shortcode($post->post_content, 'admission_form')) {
        return;
    }

    wp_enqueue_style('adm-mgr-style', ADM_MGR_URL . 'assets/style.css', array(), ADM_MGR_VERSION);
    wp_enqueue_script('adm-mgr-script', ADM_MGR_URL . 'assets/script.js', array('jquery'), ADM_MGR_VERSION, true);
    wp_localize_script('adm-mgr-script', 'adm_mgr_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('adm_mgr_nonce'),
    ));
}

/**
 * Admin menu
 */
add_action('admin_menu', 'adm_mgr_admin_menu');
function adm_mgr_admin_menu() {
    add_menu_page(
        __('Admission Entries', 'admission-mgr'),
        __('Admissions', 'admission-mgr'),
        'manage_options',
        'admission-entries',
        'adm_mgr_entries_page',
        'dashicons-forms',
        30
    );
    add_submenu_page(
        'admission-entries',
        __('Settings', 'admission-mgr'),
        __('Settings', 'admission-mgr'),
        'manage_options',
        'admission-settings',
        'adm_mgr_settings_page'
    );
}

/**
 * Load the WP media uploader only on our own admin screens.
 */
add_action('admin_enqueue_scripts', 'adm_mgr_admin_assets');
function adm_mgr_admin_assets($hook) {
    if (strpos($hook, 'admission-') === false) {
        return;
    }
    wp_enqueue_media();
}

/**
 * Settings page: institute branding, admission window, email settings.
 */
function adm_mgr_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'admission-mgr'));
    }

    if (isset($_POST['submit_institute_settings'])
        && isset($_POST['inst_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['inst_nonce'])), 'inst_settings')
    ) {
        update_option('adm_mgr_institute_name', sanitize_text_field(wp_unslash($_POST['institute_name'] ?? '')));
        update_option('adm_mgr_tagline', sanitize_text_field(wp_unslash($_POST['institute_tagline'] ?? '')));
        update_option('adm_mgr_institute_logo', esc_url_raw(wp_unslash($_POST['institute_logo'] ?? '')));
        update_option('adm_mgr_payment_qr', esc_url_raw(wp_unslash($_POST['payment_qr'] ?? '')));

        // Header adjustability: logo width, title/tagline font size, alignment
        $logo_width = isset($_POST['logo_width']) ? absint($_POST['logo_width']) : 80;
        update_option('adm_mgr_logo_width', max(20, min(400, $logo_width)));

        $title_size = isset($_POST['title_font_size']) ? absint($_POST['title_font_size']) : 22;
        update_option('adm_mgr_title_font_size', max(10, min(60, $title_size)));

        $tagline_size = isset($_POST['tagline_font_size']) ? absint($_POST['tagline_font_size']) : 14;
        update_option('adm_mgr_tagline_font_size', max(8, min(40, $tagline_size)));

        $header_align = sanitize_text_field(wp_unslash($_POST['header_align'] ?? 'center'));
        if (!in_array($header_align, array('left', 'center', 'right'), true)) {
            $header_align = 'center';
        }
        update_option('adm_mgr_header_align', $header_align);

        if (isset($_POST['adm_mgr_admin_email'])) {
            update_option('adm_mgr_admin_email', sanitize_email(wp_unslash($_POST['adm_mgr_admin_email'])));
        }
        if (isset($_POST['adm_start_date'])) {
            update_option('adm_mgr_start_date', sanitize_text_field(wp_unslash($_POST['adm_start_date'])));
        }
        if (isset($_POST['adm_end_date'])) {
            update_option('adm_mgr_end_date', sanitize_text_field(wp_unslash($_POST['adm_end_date'])));
        }
        if (isset($_POST['confirmation_subject'])) {
            update_option('adm_mgr_confirmation_subject', sanitize_text_field(wp_unslash($_POST['confirmation_subject'])));
        }
        if (isset($_POST['confirmation_message'])) {
            update_option('adm_mgr_confirmation_message', wp_kses_post(wp_unslash($_POST['confirmation_message'])));
        }
        update_option('adm_mgr_delete_data_on_uninstall', isset($_POST['delete_data_on_uninstall']) ? '1' : '0');

        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'admission-mgr') . '</p></div>';
    }

    $inst_name      = get_option('adm_mgr_institute_name', '');
    $tagline        = get_option('adm_mgr_tagline', '');
    $inst_logo      = get_option('adm_mgr_institute_logo', '');
    $payment_qr     = get_option('adm_mgr_payment_qr', '');
    $admin_email    = get_option('adm_mgr_admin_email', get_option('admin_email'));
    $start_date     = get_option('adm_mgr_start_date', '');
    $end_date       = get_option('adm_mgr_end_date', '');
    $conf_subject   = get_option('adm_mgr_confirmation_subject', 'Admission Application Received');
    $conf_message   = get_option('adm_mgr_confirmation_message', "Dear {NAME},\n\nThank you for applying. Your application ID is {ID}. We will contact you soon.\n\nRegards,\n{INSTITUTE}");
    $logo_width     = get_option('adm_mgr_logo_width', 80);
    $title_size     = get_option('adm_mgr_title_font_size', 22);
    $tagline_size   = get_option('adm_mgr_tagline_font_size', 14);
    $header_align   = get_option('adm_mgr_header_align', 'center');
    $delete_on_uninstall = get_option('adm_mgr_delete_data_on_uninstall', '0');
    ?>
    <div class="wrap adm-mgr-settings-wrap">
        <h1><?php esc_html_e('Admission Form Settings', 'admission-mgr'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('inst_settings', 'inst_nonce'); ?>

            <h2 class="title"><?php esc_html_e('Header / Branding', 'admission-mgr'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="institute_name"><?php esc_html_e('Site / Institute Title', 'admission-mgr'); ?></label></th>
                    <td><input type="text" id="institute_name" name="institute_name" value="<?php echo esc_attr($inst_name); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="institute_tagline"><?php esc_html_e('Tagline', 'admission-mgr'); ?></label></th>
                    <td>
                        <input type="text" id="institute_tagline" name="institute_tagline" value="<?php echo esc_attr($tagline); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Shown directly below the title, e.g. "Affiliated to XYZ University". Leave empty to hide.', 'admission-mgr'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="inst_logo"><?php esc_html_e('Logo Image', 'admission-mgr'); ?></label></th>
                    <td>
                        <input type="text" name="institute_logo" id="inst_logo" value="<?php echo esc_attr($inst_logo); ?>" class="regular-text">
                        <button type="button" class="button" id="upload_logo_btn"><?php esc_html_e('Upload Logo', 'admission-mgr'); ?></button>
                        <?php if ($inst_logo) : ?>
                            <div class="adm-mgr-preview-img"><img src="<?php echo esc_url($inst_logo); ?>" id="logo_preview" style="width:<?php echo esc_attr($logo_width); ?>px;"></div>
                        <?php else : ?>
                            <div class="adm-mgr-preview-img"><img src="" id="logo_preview" style="width:<?php echo esc_attr($logo_width); ?>px; display:none;"></div>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="logo_width"><?php esc_html_e('Logo Width', 'admission-mgr'); ?></label></th>
                    <td>
                        <input type="range" id="logo_width" name="logo_width" min="20" max="400" step="5" value="<?php echo esc_attr($logo_width); ?>">
                        <span id="logo_width_value"><?php echo esc_html($logo_width); ?></span>px
                        <p class="description"><?php esc_html_e('Logo width on the form and on the printed form header.', 'admission-mgr'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="title_font_size"><?php esc_html_e('Title Font Size', 'admission-mgr'); ?></label></th>
                    <td>
                        <input type="range" id="title_font_size" name="title_font_size" min="10" max="60" step="1" value="<?php echo esc_attr($title_size); ?>">
                        <span id="title_font_size_value"><?php echo esc_html($title_size); ?></span>px
                    </td>
                </tr>
                <tr>
                    <th><label for="tagline_font_size"><?php esc_html_e('Tagline Font Size', 'admission-mgr'); ?></label></th>
                    <td>
                        <input type="range" id="tagline_font_size" name="tagline_font_size" min="8" max="40" step="1" value="<?php echo esc_attr($tagline_size); ?>">
                        <span id="tagline_font_size_value"><?php echo esc_html($tagline_size); ?></span>px
                    </td>
                </tr>
                <tr>
                    <th><label for="header_align"><?php esc_html_e('Header Alignment', 'admission-mgr'); ?></label></th>
                    <td>
                        <select id="header_align" name="header_align">
                            <option value="left" <?php selected($header_align, 'left'); ?>><?php esc_html_e('Left', 'admission-mgr'); ?></option>
                            <option value="center" <?php selected($header_align, 'center'); ?>><?php esc_html_e('Center', 'admission-mgr'); ?></option>
                            <option value="right" <?php selected($header_align, 'right'); ?>><?php esc_html_e('Right', 'admission-mgr'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Applies on screen and on the printed form.', 'admission-mgr'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Live Preview', 'admission-mgr'); ?></th>
                    <td>
                        <div id="adm_mgr_header_preview" class="adm-mgr-header-preview" style="text-align:<?php echo esc_attr($header_align); ?>;">
                            <?php if ($inst_logo) : ?>
                                <img src="<?php echo esc_url($inst_logo); ?>" id="preview_logo_img" style="width:<?php echo esc_attr($logo_width); ?>px;">
                            <?php else : ?>
                                <img src="" id="preview_logo_img" style="width:<?php echo esc_attr($logo_width); ?>px; display:none;">
                            <?php endif; ?>
                            <div id="preview_title" style="font-size:<?php echo esc_attr($title_size); ?>px;"><?php echo esc_html($inst_name ?: __('Your Institute Name', 'admission-mgr')); ?></div>
                            <div id="preview_tagline" style="font-size:<?php echo esc_attr($tagline_size); ?>px; <?php echo $tagline ? '' : 'display:none;'; ?>"><?php echo esc_html($tagline); ?></div>
                        </div>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php esc_html_e('Payment', 'admission-mgr'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><?php esc_html_e('Payment QR Code Image', 'admission-mgr'); ?></th>
                    <td>
                        <input type="text" name="payment_qr" id="payment_qr" value="<?php echo esc_attr($payment_qr); ?>" class="regular-text">
                        <button type="button" class="button" id="upload_qr_btn"><?php esc_html_e('Upload QR Code', 'admission-mgr'); ?></button>
                        <p class="description"><?php esc_html_e('Upload a QR code / barcode image that applicants can scan to pay. Leave empty to hide.', 'admission-mgr'); ?></p>
                        <?php if ($payment_qr) : ?>
                            <div class="adm-mgr-preview-img"><img src="<?php echo esc_url($payment_qr); ?>" style="max-width:150px;"></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php esc_html_e('Admission Window & Notifications', 'admission-mgr'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th><?php esc_html_e('Admin Email Notifications', 'admission-mgr'); ?></th>
                    <td><input type="email" name="adm_mgr_admin_email" value="<?php echo esc_attr($admin_email); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Admission Period', 'admission-mgr'); ?></th>
                    <td>
                        <label><?php esc_html_e('Start Date:', 'admission-mgr'); ?> <input type="date" name="adm_start_date" value="<?php echo esc_attr($start_date); ?>"></label><br>
                        <label style="margin-top:5px; display:inline-block;"><?php esc_html_e('End Date:', 'admission-mgr'); ?> <input type="date" name="adm_end_date" value="<?php echo esc_attr($end_date); ?>"></label>
                        <p class="description"><?php esc_html_e('Leave empty for no restriction.', 'admission-mgr'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Confirmation Email', 'admission-mgr'); ?></th>
                    <td>
                        <label><?php esc_html_e('Subject:', 'admission-mgr'); ?> <input type="text" name="confirmation_subject" value="<?php echo esc_attr($conf_subject); ?>" style="width:100%;"></label><br>
                        <label><?php esc_html_e('Message (HTML allowed):', 'admission-mgr'); ?></label>
                        <textarea name="confirmation_message" rows="6" style="width:100%;"><?php echo esc_textarea($conf_message); ?></textarea>
                        <p class="description"><?php esc_html_e('Placeholders: {NAME}, {ID}, {INSTITUTE}, {COURSE}, {DATE}', 'admission-mgr'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('On Uninstall', 'admission-mgr'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked($delete_on_uninstall, '1'); ?>>
                            <?php esc_html_e('Delete all submissions, uploaded files, and settings when this plugin is removed.', 'admission-mgr'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Leave unchecked to keep your data safe if the plugin is ever deactivated/removed by mistake.', 'admission-mgr'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Settings', 'admission-mgr'), 'primary', 'submit_institute_settings'); ?>
        </form>
    </div>
    <script>
    jQuery(document).ready(function($){
        function mediaUploader(targetId) {
            var customUploader = wp.media({
                title: '<?php echo esc_js(__('Select Image', 'admission-mgr')); ?>',
                button: { text: '<?php echo esc_js(__('Use this image', 'admission-mgr')); ?>' },
                multiple: false
            }).on('select', function() {
                var attachment = customUploader.state().get('selection').first().toJSON();
                $('#' + targetId).val(attachment.url).trigger('change');
            }).open();
            return customUploader;
        }

        $('#upload_logo_btn').click(function(e){
            e.preventDefault();
            mediaUploader('inst_logo');
        });
        $('#upload_qr_btn').click(function(e){
            e.preventDefault();
            mediaUploader('payment_qr');
        });

        // Live preview wiring
        $('#inst_logo').on('change input', function(){
            var url = $(this).val();
            $('#logo_preview, #preview_logo_img').attr('src', url).css('display', url ? '' : 'none');
        });
        $('#logo_width').on('input change', function(){
            var val = $(this).val();
            $('#logo_width_value').text(val);
            $('#logo_preview, #preview_logo_img').css('width', val + 'px');
        });
        $('#institute_name').on('input', function(){
            $('#preview_title').text($(this).val() || '<?php echo esc_js(__('Your Institute Name', 'admission-mgr')); ?>');
        });
        $('#institute_tagline').on('input', function(){
            var val = $(this).val();
            $('#preview_tagline').text(val).css('display', val ? '' : 'none');
        });
        $('#title_font_size').on('input change', function(){
            var val = $(this).val();
            $('#title_font_size_value').text(val);
            $('#preview_title').css('font-size', val + 'px');
        });
        $('#tagline_font_size').on('input change', function(){
            var val = $(this).val();
            $('#tagline_font_size_value').text(val);
            $('#preview_tagline').css('font-size', val + 'px');
        });
        $('#header_align').on('change', function(){
            $('#adm_mgr_header_preview').css('text-align', $(this).val());
        });
    });
    </script>
    <style>
        .adm-mgr-header-preview {
            border: 1px dashed #ccc;
            background: #fff;
            padding: 20px;
            max-width: 500px;
        }
        .adm-mgr-header-preview img {
            display: block;
            margin-bottom: 8px;
            max-width: 100%;
            height: auto;
        }
        .adm-mgr-header-preview[style*="center"] img {
            margin-left: auto;
            margin-right: auto;
        }
        .adm-mgr-header-preview[style*="right"] img {
            margin-left: auto;
            margin-right: 0;
        }
        #preview_title { font-weight: 600; color: #2c3e50; }
        #preview_tagline { color: #7f8c8d; margin-top: 2px; }
        .adm-mgr-preview-img img { max-width: 150px; margin-top: 10px; border: 1px solid #ccc; padding: 4px; background: #fff; }
    </style>
    <?php
}

/**
 * Helper: is the admission window currently open?
 */
function adm_mgr_is_admission_open() {
    $start_date = get_option('adm_mgr_start_date', '');
    $end_date   = get_option('adm_mgr_end_date', '');
    $today      = current_time('Y-m-d');
    if (empty($start_date) && empty($end_date)) {
        return true;
    }
    if (!empty($start_date) && $today < $start_date) {
        return false;
    }
    if (!empty($end_date) && $today > $end_date) {
        return false;
    }
    return true;
}

function adm_mgr_get_admission_status_message() {
    $start_date = get_option('adm_mgr_start_date', '');
    $end_date   = get_option('adm_mgr_end_date', '');
    $today      = current_time('Y-m-d');
    if (!empty($start_date) && $today < $start_date) {
        return sprintf(
            /* translators: %s: admission opening date */
            __('Admissions open on %s.', 'admission-mgr'),
            date_i18n(get_option('date_format'), strtotime($start_date))
        );
    }
    if (!empty($end_date) && $today > $end_date) {
        return sprintf(
            /* translators: %s: admission closing date */
            __('Admissions closed on %s.', 'admission-mgr'),
            date_i18n(get_option('date_format'), strtotime($end_date))
        );
    }
    return '';
}

/**
 * Admin entries page with CSV export, view, and delete.
 * Delete/export are protected with nonces in addition to the capability check.
 */
function adm_mgr_entries_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'admission-mgr'));
    }

    global $wpdb;
    $table_main = $wpdb->prefix . 'admission_submissions';

    // Handle CSV export
    if (isset($_GET['export_csv']) && isset($_GET['_wpnonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'adm_mgr_export_csv')
    ) {
        adm_mgr_export_csv();
        exit;
    }

    // Handle deletion
    if (isset($_GET['action'], $_GET['id'], $_GET['_wpnonce'])
        && 'delete' === $_GET['action']
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'adm_mgr_delete_entry')
    ) {
        $id = absint($_GET['id']);
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_main WHERE id = %d", $id));
        if ($submission) {
            adm_mgr_delete_files($submission);
            $wpdb->delete($table_main, array('id' => $id));
            echo '<div class="notice notice-success"><p>' . esc_html__('Entry deleted.', 'admission-mgr') . '</p></div>';
        }
    }

    $per_page     = 20;
    $current_page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $offset       = ($current_page - 1) * $per_page;
    $total        = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_main");
    $entries      = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_main ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset));

    $export_url = wp_nonce_url(admin_url('admin.php?page=admission-entries&export_csv=1'), 'adm_mgr_export_csv');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Admission Applications', 'admission-mgr'); ?></h1>
        <div style="margin-bottom: 15px;">
            <a href="<?php echo esc_url($export_url); ?>" class="button button-primary">
                <?php esc_html_e('Export All to CSV', 'admission-mgr'); ?>
            </a>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'admission-mgr'); ?></th>
                    <th><?php esc_html_e('Name', 'admission-mgr'); ?></th>
                    <th><?php esc_html_e('Email', 'admission-mgr'); ?></th>
                    <th><?php esc_html_e('Contact', 'admission-mgr'); ?></th>
                    <th><?php esc_html_e('Course', 'admission-mgr'); ?></th>
                    <th><?php esc_html_e('Date', 'admission-mgr'); ?></th>
                    <th><?php esc_html_e('Actions', 'admission-mgr'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($entries) : ?>
                <?php foreach ($entries as $entry) :
                    $view_url   = admin_url('admin.php?page=admission-entries&action=view&id=' . $entry->id);
                    $delete_url = wp_nonce_url(admin_url('admin.php?page=admission-entries&action=delete&id=' . $entry->id), 'adm_mgr_delete_entry');
                    ?>
                    <tr>
                        <td><?php echo (int) $entry->id; ?></td>
                        <td><?php echo esc_html($entry->name); ?></td>
                        <td><?php echo esc_html($entry->email); ?></td>
                        <td><?php echo esc_html($entry->contact1); ?></td>
                        <td><?php echo esc_html($entry->course_seeking); ?></td>
                        <td><?php echo esc_html(date_i18n('d M Y', strtotime($entry->created_at))); ?></td>
                        <td>
                            <a href="<?php echo esc_url($view_url); ?>"><?php esc_html_e('View', 'admission-mgr'); ?></a> |
                            <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('Delete this entry permanently?', 'admission-mgr')); ?>')"><?php esc_html_e('Delete', 'admission-mgr'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="7"><?php esc_html_e('No applications yet.', 'admission-mgr'); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php if ($total > $per_page) :
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo paginate_links(array(
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total'     => (int) ceil($total / $per_page),
                'current'   => $current_page,
            ));
            echo '</div></div>';
        endif; ?>
    </div>
    <?php
    // Single view (details)
    if (isset($_GET['action'], $_GET['id']) && 'view' === $_GET['action']) {
        $id = absint($_GET['id']);
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_main WHERE id = %d", $id));
        if ($submission) {
            $academic = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}admission_academic_records WHERE submission_id = %d",
                $id
            ));
            ?>
            <div class="wrap">
            <div style="margin-top:30px; background:#fff; padding:20px; border:1px solid #ccc;">
                <h2><?php
                    /* translators: %d: submission ID */
                    printf(esc_html__('Application Details #%d', 'admission-mgr'), $submission->id);
                ?></h2>
                <p><strong><?php esc_html_e('Name:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->name); ?></p>
                <p><strong><?php esc_html_e('Email:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->email); ?></p>
                <p><strong><?php esc_html_e('Contact (WhatsApp):', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->contact1); ?> | <?php esc_html_e('Alt:', 'admission-mgr'); ?> <?php echo esc_html($submission->contact2); ?></p>
                <p><strong><?php esc_html_e('Father:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->father_name); ?> (<?php echo esc_html($submission->father_contact1); ?>)</p>
                <p><strong><?php esc_html_e('Mother:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->mother_name); ?> (<?php echo esc_html($submission->mother_contact1); ?>)</p>
                <p><strong><?php esc_html_e('DOB:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->dob); ?> | <?php esc_html_e('Sex:', 'admission-mgr'); ?> <?php echo esc_html($submission->sex); ?> | <?php esc_html_e('Blood Group:', 'admission-mgr'); ?> <?php echo esc_html($submission->blood_group); ?></p>
                <p><strong><?php esc_html_e('Aadhar:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->aadhar_number); ?> | <?php esc_html_e('Nationality:', 'admission-mgr'); ?> <?php echo esc_html($submission->nationality); ?> | <?php esc_html_e('Country:', 'admission-mgr'); ?> <?php echo esc_html($submission->country); ?></p>
                <p><strong><?php esc_html_e('Domicile:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->state_domicile); ?> | <?php esc_html_e('Category:', 'admission-mgr'); ?> <?php echo esc_html($submission->category); ?></p>
                <p><strong><?php esc_html_e('Last School/College:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->last_school); ?></p>
                <p><strong><?php esc_html_e('Course Seeking:', 'admission-mgr'); ?></strong> <?php echo esc_html($submission->course_seeking); ?></p>
                <p><strong><?php esc_html_e('Permanent Address:', 'admission-mgr'); ?></strong> <?php echo wp_kses_post(nl2br(esc_html($submission->permanent_address))); ?></p>
                <p><strong><?php esc_html_e('Present Address:', 'admission-mgr'); ?></strong> <?php echo wp_kses_post(nl2br(esc_html($submission->present_address))); ?> | <?php esc_html_e('Pin:', 'admission-mgr'); ?> <?php echo esc_html($submission->present_pin_code); ?></p>
                <h3><?php esc_html_e('Academic Records', 'admission-mgr'); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Exam', 'admission-mgr'); ?></th>
                            <th><?php esc_html_e('Year', 'admission-mgr'); ?></th>
                            <th><?php esc_html_e('Class/Div', 'admission-mgr'); ?></th>
                            <th><?php esc_html_e('% Marks', 'admission-mgr'); ?></th>
                            <th><?php esc_html_e('Board/Univ', 'admission-mgr'); ?></th>
                            <th><?php esc_html_e('Subjects', 'admission-mgr'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($academic as $rec) : ?>
                        <tr>
                            <td><?php echo esc_html($rec->exam_name); ?></td>
                            <td><?php echo esc_html($rec->year_passing); ?></td>
                            <td><?php echo esc_html($rec->class_division); ?></td>
                            <td><?php echo esc_html($rec->percentage_marks); ?></td>
                            <td><?php echo esc_html($rec->board_university); ?></td>
                            <td><?php echo esc_html($rec->subjects_offered); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <h3><?php esc_html_e('Uploaded Documents', 'admission-mgr'); ?></h3>
                <p><strong><?php esc_html_e('Passport Photo:', 'admission-mgr'); ?></strong> <a href="<?php echo esc_url($submission->passport_photo); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View', 'admission-mgr'); ?></a></p>
                <p><strong><?php esc_html_e('Payment Proof:', 'admission-mgr'); ?></strong> <?php echo $submission->payment_proof ? '<a href="' . esc_url($submission->payment_proof) . '" target="_blank" rel="noopener noreferrer">' . esc_html__('View', 'admission-mgr') . '</a>' : esc_html__('Not uploaded', 'admission-mgr'); ?></p>
                <p><strong><?php esc_html_e('Scanned Documents:', 'admission-mgr'); ?></strong></p>
                <ul>
                <?php
                $docs = explode(',', (string) $submission->scanned_documents);
                foreach ($docs as $doc) {
                    $doc = trim($doc);
                    if ($doc) {
                        echo '<li><a href="' . esc_url($doc) . '" target="_blank" rel="noopener noreferrer">' . esc_html(basename($doc)) . '</a></li>';
                    }
                }
                ?>
                </ul>
            </div>
            </div>
            <?php
        }
    }
}

/**
 * CSV export of all submissions plus their academic records (as JSON).
 */
function adm_mgr_export_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to do this.', 'admission-mgr'));
    }

    global $wpdb;
    $table_main     = $wpdb->prefix . 'admission_submissions';
    $table_academic = $wpdb->prefix . 'admission_academic_records';

    $submissions = $wpdb->get_results("SELECT * FROM $table_main ORDER BY id ASC");
    if (empty($submissions)) {
        wp_die(esc_html__('No data to export.', 'admission-mgr'));
    }

    $filename = 'admissions_' . gmdate('Y-m-d') . '.csv';
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');

    fputcsv($output, array(
        'ID', 'Date', 'Name', 'Email', 'WhatsApp', 'Alternate Contact',
        'Father Name', 'Father Contact1', 'Father Contact2',
        'Mother Name', 'Mother Contact1', 'Mother Contact2',
        'Permanent Address', 'Present Address', 'Pin Code',
        'DOB', 'Sex', 'Nationality', 'Blood Group', 'Aadhar Number', 'Country',
        'State Domicile', 'Category', 'Last School/College', 'Course Seeking',
        'Subjects (JSON)', 'Passport Photo URL', 'Payment Proof URL', 'Scanned Docs URLs',
    ));

    foreach ($submissions as $sub) {
        $academic = $wpdb->get_results($wpdb->prepare(
            "SELECT exam_name, year_passing, class_division, percentage_marks, board_university, subjects_offered FROM $table_academic WHERE submission_id = %d",
            $sub->id
        ));
        $academic_json = wp_json_encode($academic);
        fputcsv($output, array(
            $sub->id, $sub->created_at, $sub->name, $sub->email, $sub->contact1, $sub->contact2,
            $sub->father_name, $sub->father_contact1, $sub->father_contact2,
            $sub->mother_name, $sub->mother_contact1, $sub->mother_contact2,
            $sub->permanent_address, $sub->present_address, $sub->present_pin_code,
            $sub->dob, $sub->sex, $sub->nationality, $sub->blood_group, $sub->aadhar_number, $sub->country,
            $sub->state_domicile, $sub->category, $sub->last_school, $sub->course_seeking,
            $academic_json, $sub->passport_photo, $sub->payment_proof, $sub->scanned_documents,
        ));
    }
    fclose($output);
    exit;
}

/**
 * Remove uploaded files associated with a submission row.
 */
function adm_mgr_delete_files($submission) {
    $upload_dir = wp_upload_dir();
    $base_dir   = $upload_dir['basedir'];
    $base_url   = $upload_dir['baseurl'];

    $files = array($submission->passport_photo, $submission->payment_proof);
    foreach ($files as $file) {
        if (!$file) {
            continue;
        }
        $path = str_replace($base_url, $base_dir, $file);
        if (file_exists($path)) {
            @unlink($path);
        }
    }

    $docs = explode(',', (string) $submission->scanned_documents);
    foreach ($docs as $doc) {
        $doc = trim($doc);
        if (!$doc) {
            continue;
        }
        $path = str_replace($base_url, $base_dir, $doc);
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}

/**
 * Build the inline style for the institute header, shared by screen and print.
 */
function adm_mgr_get_header_settings() {
    return array(
        'name'          => get_option('adm_mgr_institute_name', ''),
        'tagline'       => get_option('adm_mgr_tagline', ''),
        'logo'          => get_option('adm_mgr_institute_logo', ''),
        'logo_width'    => (int) get_option('adm_mgr_logo_width', 80),
        'title_size'    => (int) get_option('adm_mgr_title_font_size', 22),
        'tagline_size'  => (int) get_option('adm_mgr_tagline_font_size', 14),
        'align'         => get_option('adm_mgr_header_align', 'center'),
    );
}

/**
 * Shortcode: [admission_form]
 */
add_shortcode('admission_form', 'adm_mgr_render_form');
function adm_mgr_render_form() {
    ob_start();

    $header        = adm_mgr_get_header_settings();
    $payment_qr    = get_option('adm_mgr_payment_qr', '');
    $is_open       = adm_mgr_is_admission_open();
    $status_message = adm_mgr_get_admission_status_message();
    $has_header    = $header['name'] || $header['tagline'] || $header['logo'];
    ?>
    <div class="admission-form-wrapper">

        <?php if ($has_header) : ?>
        <div class="institute-header" style="text-align: <?php echo esc_attr($header['align']); ?>;">
            <?php if ($header['logo']) : ?>
                <img src="<?php echo esc_url($header['logo']); ?>" class="institute-logo" style="width: <?php echo esc_attr($header['logo_width']); ?>px;" alt="<?php echo esc_attr($header['name']); ?>">
            <?php endif; ?>
            <?php if ($header['name']) : ?>
                <h2 class="institute-title" style="font-size: <?php echo esc_attr($header['title_size']); ?>px;"><?php echo esc_html($header['name']); ?></h2>
            <?php endif; ?>
            <?php if ($header['tagline']) : ?>
                <p class="institute-tagline" style="font-size: <?php echo esc_attr($header['tagline_size']); ?>px;"><?php echo esc_html($header['tagline']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!$is_open) : ?>
            <div class="admission-message error admission-status-message"><?php echo esc_html($status_message); ?></div>
        <?php endif; ?>

        <form id="admissionForm" method="post" enctype="multipart/form-data" <?php echo $is_open ? '' : 'onsubmit="return false;"'; ?>>
            <?php wp_nonce_field('adm_submit_nonce', 'adm_nonce_field'); ?>
            <div class="adm-mgr-toolbar">
                <button type="button" id="printFormBtn" class="print-btn" <?php echo $is_open ? '' : 'disabled'; ?>>
                    <?php esc_html_e('Print Form', 'admission-mgr'); ?>
                </button>
            </div>

            <fieldset>
                <legend><?php esc_html_e('Personal Information (IN BLOCK LETTERS)', 'admission-mgr'); ?></legend>
                <p><label><?php esc_html_e('Full Name:', 'admission-mgr'); ?> <input type="text" name="name" required class="adm-uppercase" <?php disabled(!$is_open); ?>></label></p>
                <p><label><?php esc_html_e('Email (for confirmation):', 'admission-mgr'); ?> <input type="email" name="email" <?php disabled(!$is_open); ?>></label><br><small><?php esc_html_e('Optional but recommended.', 'admission-mgr'); ?></small></p>
                <p>
                    <label><?php esc_html_e('WhatsApp No.:', 'admission-mgr'); ?> <input type="tel" name="contact1" required <?php disabled(!$is_open); ?>></label>
                    <label><?php esc_html_e('Alternate No.:', 'admission-mgr'); ?> <input type="tel" name="contact2" <?php disabled(!$is_open); ?>></label>
                </p>
                <p>
                    <label><?php esc_html_e("Father's Name:", 'admission-mgr'); ?> <input type="text" name="father_name" required <?php disabled(!$is_open); ?>></label>
                    <label><?php esc_html_e("Father's Contact 1:", 'admission-mgr'); ?> <input type="tel" name="father_contact1" <?php disabled(!$is_open); ?>></label>
                    <label><?php esc_html_e("Father's Contact 2:", 'admission-mgr'); ?> <input type="tel" name="father_contact2" <?php disabled(!$is_open); ?>></label>
                </p>
                <p>
                    <label><?php esc_html_e("Mother's Name:", 'admission-mgr'); ?> <input type="text" name="mother_name" required <?php disabled(!$is_open); ?>></label>
                    <label><?php esc_html_e("Mother's Contact 1:", 'admission-mgr'); ?> <input type="tel" name="mother_contact1" <?php disabled(!$is_open); ?>></label>
                    <label><?php esc_html_e("Mother's Contact 2:", 'admission-mgr'); ?> <input type="tel" name="mother_contact2" <?php disabled(!$is_open); ?>></label>
                </p>
                <p><label><?php esc_html_e('Permanent Address (Block Letters):', 'admission-mgr'); ?> <textarea name="permanent_address" rows="3" class="adm-uppercase" required <?php disabled(!$is_open); ?>></textarea></label></p>
                <p>
                    <label><?php esc_html_e('Present Address:', 'admission-mgr'); ?> <textarea name="present_address" rows="2" required <?php disabled(!$is_open); ?>></textarea></label>
                    <label><?php esc_html_e('Pin Code:', 'admission-mgr'); ?> <input type="text" name="present_pin_code" <?php disabled(!$is_open); ?>></label>
                </p>
                <p><label><?php esc_html_e('Date of Birth:', 'admission-mgr'); ?> <input type="date" name="dob" required <?php disabled(!$is_open); ?>></label></p>
                <p><label><?php esc_html_e('Sex:', 'admission-mgr'); ?>
                    <select name="sex" required <?php disabled(!$is_open); ?>>
                        <option value=""><?php esc_html_e('Select', 'admission-mgr'); ?></option>
                        <option><?php esc_html_e('Male', 'admission-mgr'); ?></option>
                        <option><?php esc_html_e('Female', 'admission-mgr'); ?></option>
                        <option><?php esc_html_e('Other', 'admission-mgr'); ?></option>
                    </select>
                </label></p>
                <p>
                    <label><?php esc_html_e('Nationality:', 'admission-mgr'); ?> <input type="text" name="nationality" value="Indian" required <?php disabled(!$is_open); ?>></label>
                    <label><?php esc_html_e('Blood Group:', 'admission-mgr'); ?> <input type="text" name="blood_group" placeholder="A+ / B+ / O+" <?php disabled(!$is_open); ?>></label>
                </p>
                <p>
                    <label><?php esc_html_e('Aadhar Number:', 'admission-mgr'); ?> <input type="text" name="aadhar_number" required pattern="[0-9]{12}" title="<?php esc_attr_e('12-digit Aadhar', 'admission-mgr'); ?>" <?php disabled(!$is_open); ?>></label>
                    <label><?php esc_html_e('Country (if foreign national):', 'admission-mgr'); ?> <input type="text" name="country" placeholder="India" <?php disabled(!$is_open); ?>></label>
                </p>
                <p>
                    <label><?php esc_html_e('State of Domicile:', 'admission-mgr'); ?> <input type="text" name="state_domicile" required <?php disabled(!$is_open); ?>></label>
                    <label><?php esc_html_e('Category:', 'admission-mgr'); ?>
                        <select name="category" required <?php disabled(!$is_open); ?>>
                            <option>Gen</option><option>ST</option><option>SC</option><option>OBC</option>
                        </select>
                    </label>
                </p>
                <p><label><?php esc_html_e('Last School/College Attended:', 'admission-mgr'); ?> <input type="text" name="last_school" required <?php disabled(!$is_open); ?>></label></p>
                <p><label><?php esc_html_e('Course Seeking Admission:', 'admission-mgr'); ?> <input type="text" name="course_seeking" required <?php disabled(!$is_open); ?>></label></p>
            </fieldset>

            <fieldset>
                <legend><?php esc_html_e('Academic Record (Add all qualifications)', 'admission-mgr'); ?></legend>
                <div id="academic-rows">
                    <div class="academic-row">
                        <input type="text" name="academic_exam_name[]" placeholder="<?php esc_attr_e('Exam Name', 'admission-mgr'); ?>" required <?php disabled(!$is_open); ?>>
                        <input type="text" name="academic_year[]" placeholder="<?php esc_attr_e('Year', 'admission-mgr'); ?>" required <?php disabled(!$is_open); ?>>
                        <input type="text" name="academic_class_div[]" placeholder="<?php esc_attr_e('Class/Division', 'admission-mgr'); ?>" <?php disabled(!$is_open); ?>>
                        <input type="text" name="academic_percent[]" placeholder="<?php esc_attr_e('% Marks', 'admission-mgr'); ?>" <?php disabled(!$is_open); ?>>
                        <input type="text" name="academic_board[]" placeholder="<?php esc_attr_e('Board/Univ', 'admission-mgr'); ?>" <?php disabled(!$is_open); ?>>
                        <textarea name="academic_subjects[]" placeholder="<?php esc_attr_e('Subjects', 'admission-mgr'); ?>" <?php disabled(!$is_open); ?>></textarea>
                        <button type="button" class="remove-row" <?php disabled(!$is_open); ?>><?php esc_html_e('Remove', 'admission-mgr'); ?></button>
                    </div>
                </div>
                <button type="button" id="add-academic-row" <?php disabled(!$is_open); ?>>+ <?php esc_html_e('Add Another Qualification', 'admission-mgr'); ?></button>
            </fieldset>

            <fieldset>
                <legend><?php esc_html_e('Document Uploads (Max 300KB each)', 'admission-mgr'); ?></legend>
                <p><label><?php esc_html_e('Passport Size Photo:', 'admission-mgr'); ?> <input type="file" name="passport_photo" accept="image/jpeg,image/png" required <?php disabled(!$is_open); ?>></label></p>
                <p><label><?php esc_html_e('Scanned Documents (multiple):', 'admission-mgr'); ?> <input type="file" name="scanned_docs[]" multiple accept="image/jpeg,image/png,application/pdf" <?php disabled(!$is_open); ?>></label>
                <small><?php esc_html_e('Upload mark sheets, certificates etc.', 'admission-mgr'); ?></small></p>

                <div class="adm-mgr-payment-row">
                    <div class="adm-mgr-payment-upload">
                        <label><?php esc_html_e('Proof of Payment:', 'admission-mgr'); ?> <input type="file" name="payment_proof" accept="image/jpeg,image/png,application/pdf" <?php disabled(!$is_open); ?>></label>
                    </div>
                    <?php if ($payment_qr) : ?>
                    <div class="adm-mgr-payment-qr">
                        <img src="<?php echo esc_url($payment_qr); ?>" alt="<?php esc_attr_e('Payment QR Code', 'admission-mgr'); ?>">
                        <p><?php esc_html_e('Scan to pay', 'admission-mgr'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <p class="note"><?php esc_html_e('Each file must be ≤ 300KB. Scan the QR code to make payment, then upload the receipt/confirmation.', 'admission-mgr'); ?></p>
            </fieldset>

            <?php if ($is_open) : ?>
                <p><input type="submit" name="submit_admission" value="<?php esc_attr_e('Submit Application', 'admission-mgr'); ?>"></p>
            <?php else : ?>
                <p><input type="button" value="<?php esc_attr_e('Submit Application', 'admission-mgr'); ?>" disabled class="adm-mgr-disabled-submit"></p>
            <?php endif; ?>
        </form>
        <div id="form-message"></div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Handle the admission form submission on init.
 */
add_action('init', 'adm_mgr_handle_submission');
function adm_mgr_handle_submission() {
    if (!isset($_POST['submit_admission'], $_POST['adm_nonce_field'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['adm_nonce_field'])), 'adm_submit_nonce')) {
        adm_mgr_output_message(__('Security check failed. Please reload the page and try again.', 'admission-mgr'), 'error');
        return;
    }
    if (!adm_mgr_is_admission_open()) {
        adm_mgr_output_message(adm_mgr_get_admission_status_message(), 'error');
        return;
    }

    $required = array(
        'name', 'contact1', 'father_name', 'mother_name', 'permanent_address',
        'present_address', 'dob', 'sex', 'nationality', 'aadhar_number',
        'state_domicile', 'category', 'last_school', 'course_seeking',
    );
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            adm_mgr_output_message(
                /* translators: %s: form field name */
                sprintf(__('Error: %s is required.', 'admission-mgr'), $field),
                'error'
            );
            return;
        }
    }

    if (!preg_match('/^[0-9]{12}$/', sanitize_text_field(wp_unslash($_POST['aadhar_number'])))) {
        adm_mgr_output_message(__('Aadhar number must be exactly 12 digits.', 'admission-mgr'), 'error');
        return;
    }

    // File upload handling
    $upload_dir         = wp_upload_dir();
    $plugin_upload_dir  = $upload_dir['basedir'] . '/' . ADM_MGR_UPLOAD_DIR;
    $plugin_upload_url  = $upload_dir['baseurl'] . '/' . ADM_MGR_UPLOAD_DIR;
    $allowed_ext        = array('jpg', 'jpeg', 'png', 'pdf');
    $allowed_mimes      = array(
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'pdf'  => 'application/pdf',
    );
    $max_size = 300 * 1024;

    if (!isset($_FILES['passport_photo']) || UPLOAD_ERR_OK !== $_FILES['passport_photo']['error']) {
        adm_mgr_output_message(__('Passport photo is required.', 'admission-mgr'), 'error');
        return;
    }
    $photo_path = adm_mgr_upload_file($_FILES['passport_photo'], $plugin_upload_dir, $plugin_upload_url, $allowed_ext, $allowed_mimes, $max_size);
    if (!$photo_path) {
        return;
    }

    $payment_path = null;
    if (isset($_FILES['payment_proof']) && UPLOAD_ERR_OK === $_FILES['payment_proof']['error']) {
        $payment_path = adm_mgr_upload_file($_FILES['payment_proof'], $plugin_upload_dir, $plugin_upload_url, $allowed_ext, $allowed_mimes, $max_size);
        if (false === $payment_path) {
            return;
        }
    }

    $scanned_paths = array();
    if (isset($_FILES['scanned_docs']) && !empty($_FILES['scanned_docs']['name'][0])) {
        foreach ($_FILES['scanned_docs']['name'] as $key => $name) {
            if (UPLOAD_ERR_OK === $_FILES['scanned_docs']['error'][$key]) {
                $file_arr = array(
                    'name'     => $_FILES['scanned_docs']['name'][$key],
                    'type'     => $_FILES['scanned_docs']['type'][$key],
                    'tmp_name' => $_FILES['scanned_docs']['tmp_name'][$key],
                    'error'    => $_FILES['scanned_docs']['error'][$key],
                    'size'     => $_FILES['scanned_docs']['size'][$key],
                );
                $path = adm_mgr_upload_file($file_arr, $plugin_upload_dir, $plugin_upload_url, $allowed_ext, $allowed_mimes, $max_size);
                if ($path) {
                    $scanned_paths[] = $path;
                } else {
                    return;
                }
            }
        }
    }

    global $wpdb;
    $table_main = $wpdb->prefix . 'admission_submissions';
    $data = array(
        'name'               => sanitize_text_field(wp_unslash($_POST['name'])),
        'email'              => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        'contact1'           => sanitize_text_field(wp_unslash($_POST['contact1'])),
        'contact2'           => sanitize_text_field(wp_unslash($_POST['contact2'] ?? '')),
        'father_name'        => sanitize_text_field(wp_unslash($_POST['father_name'])),
        'father_contact1'    => sanitize_text_field(wp_unslash($_POST['father_contact1'] ?? '')),
        'father_contact2'    => sanitize_text_field(wp_unslash($_POST['father_contact2'] ?? '')),
        'mother_name'        => sanitize_text_field(wp_unslash($_POST['mother_name'])),
        'mother_contact1'    => sanitize_text_field(wp_unslash($_POST['mother_contact1'] ?? '')),
        'mother_contact2'    => sanitize_text_field(wp_unslash($_POST['mother_contact2'] ?? '')),
        'permanent_address'  => sanitize_textarea_field(wp_unslash($_POST['permanent_address'])),
        'present_address'    => sanitize_textarea_field(wp_unslash($_POST['present_address'])),
        'present_pin_code'   => sanitize_text_field(wp_unslash($_POST['present_pin_code'] ?? '')),
        'dob'                => sanitize_text_field(wp_unslash($_POST['dob'])),
        'sex'                => sanitize_text_field(wp_unslash($_POST['sex'])),
        'nationality'        => sanitize_text_field(wp_unslash($_POST['nationality'])),
        'blood_group'        => sanitize_text_field(wp_unslash($_POST['blood_group'] ?? '')),
        'aadhar_number'      => sanitize_text_field(wp_unslash($_POST['aadhar_number'])),
        'country'            => sanitize_text_field(wp_unslash($_POST['country'] ?? '')),
        'state_domicile'     => sanitize_text_field(wp_unslash($_POST['state_domicile'])),
        'category'           => sanitize_text_field(wp_unslash($_POST['category'])),
        'last_school'        => sanitize_text_field(wp_unslash($_POST['last_school'])),
        'course_seeking'     => sanitize_text_field(wp_unslash($_POST['course_seeking'])),
        'passport_photo'     => $photo_path,
        'payment_proof'      => $payment_path,
        'scanned_documents'  => implode(',', $scanned_paths),
    );

    $inserted = $wpdb->insert($table_main, $data);
    if (false === $inserted) {
        adm_mgr_output_message(__('Something went wrong while saving your application. Please try again.', 'admission-mgr'), 'error');
        return;
    }
    $submission_id = $wpdb->insert_id;

    // Insert academic records
    $table_academic = $wpdb->prefix . 'admission_academic_records';
    $exam_names = isset($_POST['academic_exam_name']) ? (array) wp_unslash($_POST['academic_exam_name']) : array();
    $years      = isset($_POST['academic_year']) ? (array) wp_unslash($_POST['academic_year']) : array();
    $class_div  = isset($_POST['academic_class_div']) ? (array) wp_unslash($_POST['academic_class_div']) : array();
    $percent    = isset($_POST['academic_percent']) ? (array) wp_unslash($_POST['academic_percent']) : array();
    $board      = isset($_POST['academic_board']) ? (array) wp_unslash($_POST['academic_board']) : array();
    $subjects   = isset($_POST['academic_subjects']) ? (array) wp_unslash($_POST['academic_subjects']) : array();

    for ($i = 0; $i < count($exam_names); $i++) {
        if (!empty($exam_names[$i])) {
            $wpdb->insert($table_academic, array(
                'submission_id'     => $submission_id,
                'exam_name'         => sanitize_text_field($exam_names[$i]),
                'year_passing'      => sanitize_text_field($years[$i] ?? ''),
                'class_division'    => sanitize_text_field($class_div[$i] ?? ''),
                'percentage_marks'  => sanitize_text_field($percent[$i] ?? ''),
                'board_university'  => sanitize_text_field($board[$i] ?? ''),
                'subjects_offered'  => sanitize_textarea_field($subjects[$i] ?? ''),
            ));
        }
    }

    // Send confirmation email to applicant
    if (!empty($data['email'])) {
        adm_mgr_send_confirmation_email($submission_id, $data['email'], $data['name'], $data['course_seeking']);
    }

    // Notify admin
    $admin_email = get_option('adm_mgr_admin_email', get_option('admin_email'));
    wp_mail(
        $admin_email,
        __('New Admission Application', 'admission-mgr'),
        sprintf(
            /* translators: 1: submission ID, 2: applicant name */
            __('New application (ID: %1$d) from %2$s. View in admin panel.', 'admission-mgr'),
            $submission_id,
            $data['name']
        )
    );

    adm_mgr_output_message(
        sprintf(
            /* translators: %d: submission ID */
            __('Application submitted! Your ID: %d. Check your email for confirmation.', 'admission-mgr'),
            $submission_id
        ),
        'success'
    );

    $redirect_url = add_query_arg('submitted', 'success', remove_query_arg(array('submitted')));
    wp_safe_redirect($redirect_url);
    exit;
}

/**
 * Send confirmation email to the applicant using the configured template.
 */
function adm_mgr_send_confirmation_email($submission_id, $applicant_email, $applicant_name, $course) {
    $subject          = get_option('adm_mgr_confirmation_subject', 'Admission Application Received');
    $message_template = get_option('adm_mgr_confirmation_message', "Dear {NAME},\n\nThank you for applying. Your application ID is {ID}. We will contact you soon.\n\nRegards,\n{INSTITUTE}");
    $institute_name   = get_option('adm_mgr_institute_name', 'Institute');
    $date             = date_i18n(get_option('date_format'));

    $placeholders = array(
        '{NAME}'      => $applicant_name,
        '{ID}'        => $submission_id,
        '{INSTITUTE}' => $institute_name,
        '{COURSE}'    => $course,
        '{DATE}'      => $date,
    );
    $message = str_replace(array_keys($placeholders), array_values($placeholders), $message_template);
    $message = wpautop($message);
    $headers = array('Content-Type: text/html; charset=UTF-8');

    wp_mail($applicant_email, $subject, $message, $headers);
}

/**
 * Validate and move an uploaded file, checking extension, real MIME type, and size.
 *
 * @return string|false Public URL on success, false on failure (with a message already queued).
 */
function adm_mgr_upload_file($file, $target_dir, $target_url, $allowed_ext, $allowed_mimes, $max_size) {
    if (UPLOAD_ERR_OK !== $file['error']) {
        return false;
    }

    if ($file['size'] > $max_size) {
        adm_mgr_output_message(
            sprintf(
                /* translators: %s: file name */
                __('File exceeds 300KB: %s', 'admission-mgr'),
                $file['name']
            ),
            'error'
        );
        return false;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true)) {
        adm_mgr_output_message(
            sprintf(
                /* translators: %s: file name */
                __('File type not allowed: %s', 'admission-mgr'),
                $file['name']
            ),
            'error'
        );
        return false;
    }

    // Verify the file's actual content matches an allowed type (defends against
    // a malicious file simply renamed with an allowed extension).
    $filetype = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    $real_mime = $filetype['type'];
    if (!$real_mime || !in_array($real_mime, $allowed_mimes, true)) {
        adm_mgr_output_message(
            sprintf(
                /* translators: %s: file name */
                __('File content does not match an allowed type: %s', 'admission-mgr'),
                $file['name']
            ),
            'error'
        );
        return false;
    }

    $filename    = time() . '_' . wp_generate_password(8, false, false) . '.' . $ext;
    $destination = trailingslashit($target_dir) . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return trailingslashit($target_url) . $filename;
    }

    adm_mgr_output_message(
        sprintf(
            /* translators: %s: file name */
            __('Failed to upload: %s', 'admission-mgr'),
            $file['name']
        ),
        'error'
    );
    return false;
}

/**
 * Queue a one-time message to render in the footer (used for form validation feedback).
 */
function adm_mgr_output_message($msg, $type) {
    add_action('wp_footer', function () use ($msg, $type) {
        echo '<div class="admission-message ' . esc_attr($type) . '">' . esc_html($msg) . '</div>';
    });
}
