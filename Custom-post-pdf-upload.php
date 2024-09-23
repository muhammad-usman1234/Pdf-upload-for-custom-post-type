<?php
//////////////////////////////


//Register meta box
function my_custom_meta_boxes() {
    add_meta_box(
        'my_meta_box',
        __('PDFs for Clients', 'textdomain'),
        'my_meta_box_callback',
        'pdf_resource', // Replace with your custom post type
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'my_custom_meta_boxes');

function my_meta_box_callback($post) {
    wp_nonce_field('my_meta_box_nonce', 'meta_box_nonce');
    $pdf_ids = get_post_meta($post->ID, '_pdf_ids', true);
    $pdf_ids = is_array($pdf_ids) ? $pdf_ids : array();

    // Sort PDF IDs by date
    usort($pdf_ids, function($a, $b) {
        $date_a = get_the_date('Y-m-d', $a);
        $date_b = get_the_date('Y-m-d', $b);
        return strcmp($date_b, $date_a); // Sort by date descending
    });

    echo '<input type="button" id="add_pdf_button" class="button" value="' . __('Upload PDF', 'textdomain') . '" />';
    echo '<div id="pdfs-container" style="margin-top: 10px;">';
    if($pdf_ids){
    foreach ($pdf_ids as $pdf_id) {
        $pdf_url = wp_get_attachment_url($pdf_id);
        $pdf_date = get_the_date('Y-m-d', $pdf_id);
        $pdf_icon = wp_mime_type_icon(get_post_mime_type($pdf_id)); // Use WordPress's default icon for the file type
        $pdf_name = basename(get_attached_file($pdf_id));
        
        echo '<div class="pdf-row" style="display: flex; justify-content: flex-start; align-items: center; gap: 5px; margin-bottom: 10px; border-bottom: 1px solid black; padding-bottom:10px;">';
        echo '<input type="hidden" name="pdf_ids[]" value="' . esc_attr($pdf_id) . '" />';
        echo '<img src="' . esc_url($pdf_icon) . '" max-width: 36px; alt="PDF Icon" class="pdf-icon" />';
        echo '<span class="pdf-name">' . esc_html($pdf_name) . '</span>';
        // echo '<input type="button" class="upload_pdf_button button" value="' . __('Upload PDF', 'textdomain') . '" />';
        echo '<input type="button" class="delete_pdf_button button" value="' . __('Delete', 'textdomain') . '" />';
        echo '</div>';
    }
    }
    echo '</div>';
}

// Save meta box data
function my_save_meta_box_data($post_id) {
    if (!isset($_POST['meta_box_nonce']) || !wp_verify_nonce($_POST['meta_box_nonce'], 'my_meta_box_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['pdf_ids']) && is_array($_POST['pdf_ids'])) {
        $pdf_ids = array_map('intval', $_POST['pdf_ids']);
        update_post_meta($post_id, '_pdf_ids', $pdf_ids);
    } else {
        delete_post_meta($post_id, '_pdf_ids');
    }
}
add_action('save_post', 'my_save_meta_box_data');

// Register and enqueue the JavaScript for media uploader
function my_enqueue_admin_scripts($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script(
        'jquery-ui-sortable'
    );
    wp_enqueue_script(
        'my-custom-admin-script',
        get_template_directory_uri() . '/js/my-custom-admin-script.js',
        array('jquery', 'jquery-ui-sortable', 'media-views'),
        null,
        true
    );
    wp_localize_script('my-custom-admin-script', 'wpvars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'post_id' => get_the_ID(), // Pass current post ID
        'nonce' => wp_create_nonce('update_pdf_order_nonce') // Security nonce
    ));
}
add_action('admin_enqueue_scripts', 'my_enqueue_admin_scripts');



//////////////////////////////
?>