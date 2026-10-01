<?php

defined('ABSPATH') || exit;

/** Enquiry form. $property_id = 0 for a general enquiry (Contact page). */
function rec_enquiry_form(int $property_id): string
{
    $state = isset($_GET['enquiry']) ? sanitize_key(wp_unslash($_GET['enquiry'])) : '';

    ob_start();
    if ($state === 'sent') {
        echo '<div class="rec-alert rec-alert-success">Thank you! Your enquiry has been sent. We will contact you soon.</div>';
    } elseif ($state === 'invalid') {
        echo '<div class="rec-alert rec-alert-error">Please enter your name, a valid phone number or email, and a message.</div>';
    }
    ?>
    <form class="rec-enquiry-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="rec_enquiry">
        <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>">
        <?php wp_nonce_field('rec_enquiry', 'rec_enquiry_nonce'); ?>
        <p class="rec-hp" aria-hidden="true"><label>Leave empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
        <p><input type="text" name="name" placeholder="Your name *" required maxlength="100"></p>
        <p><input type="tel" name="phone" placeholder="Phone / WhatsApp *" required maxlength="20"></p>
        <p><input type="email" name="email" placeholder="Email" maxlength="150"></p>
        <p><textarea name="message" rows="4" placeholder="Message *" required maxlength="2000"><?php echo $property_id ? esc_textarea('I am interested in "' . get_the_title($property_id) . '". Please share more details.') : ''; ?></textarea></p>
        <p><button type="submit" class="rec-btn rec-btn-block">Send enquiry</button></p>
    </form>
    <?php
    return (string) ob_get_clean();
}

function rec_handle_enquiry(): void
{
    $back = wp_get_referer() ?: home_url('/');
    $back = remove_query_arg('enquiry', $back);

    if (! isset($_POST['rec_enquiry_nonce']) || ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['rec_enquiry_nonce'])), 'rec_enquiry')) {
        wp_safe_redirect(add_query_arg('enquiry', 'invalid', $back));
        exit;
    }

    // Honeypot: bots fill every field.
    if (! empty($_POST['website'])) {
        wp_safe_redirect(add_query_arg('enquiry', 'sent', $back));
        exit;
    }

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    $property_id = absint($_POST['property_id'] ?? 0);
    if ($property_id && get_post_type($property_id) !== 'property') {
        $property_id = 0;
    }

    if ($name === '' || $message === '' || (! preg_match('/\d{7,}/', preg_replace('/\D/', '', $phone)) && ! is_email($email))) {
        wp_safe_redirect(add_query_arg('enquiry', 'invalid', $back) . '#rec-enquiry');
        exit;
    }

    $title = $property_id ? sprintf('%s – %s', $name, get_the_title($property_id)) : sprintf('%s – General enquiry', $name);
    $enquiry_id = wp_insert_post([
        'post_type' => 'rec_enquiry',
        'post_status' => 'private',
        'post_title' => $title,
    ]);

    if ($enquiry_id && ! is_wp_error($enquiry_id)) {
        update_post_meta($enquiry_id, '_rec_name', $name);
        update_post_meta($enquiry_id, '_rec_phone', $phone);
        update_post_meta($enquiry_id, '_rec_email', $email);
        update_post_meta($enquiry_id, '_rec_message', $message);
        update_post_meta($enquiry_id, '_rec_property_id', $property_id);
        update_post_meta($enquiry_id, '_rec_status', 'new');
    }

    $to = rec_settings()['enquiry_email'];
    if ($to && is_email($to)) {
        $body = "New enquiry from your website\n\n"
            . "Name: $name\nPhone: $phone\nEmail: $email\n"
            . ($property_id ? 'Property: ' . get_the_title($property_id) . ' (' . get_permalink($property_id) . ")\n" : '')
            . "\nMessage:\n$message\n\n"
            . 'View all enquiries: ' . admin_url('edit.php?post_type=rec_enquiry');
        $headers = is_email($email) ? ['Reply-To: ' . $name . ' <' . $email . '>'] : [];
        wp_mail($to, 'New property enquiry: ' . $title, $body, $headers);
    }

    wp_safe_redirect(add_query_arg('enquiry', 'sent', $back) . '#rec-enquiry');
    exit;
}
add_action('admin_post_rec_enquiry', 'rec_handle_enquiry');
add_action('admin_post_nopriv_rec_enquiry', 'rec_handle_enquiry');

// Enquiry list columns and detail box.
add_filter('manage_rec_enquiry_posts_columns', function () {
    return ['cb' => '<input type="checkbox">', 'title' => 'Enquiry', 'rec_contact' => 'Contact', 'rec_status' => 'Status', 'date' => 'Received'];
});

add_action('manage_rec_enquiry_posts_custom_column', function ($col, $post_id) {
    if ($col === 'rec_contact') {
        $phone = get_post_meta($post_id, '_rec_phone', true);
        $email = get_post_meta($post_id, '_rec_email', true);
        echo esc_html($phone);
        if ($email) {
            echo '<br><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
        }
    }
    if ($col === 'rec_status') {
        $labels = ['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'];
        echo esc_html($labels[get_post_meta($post_id, '_rec_status', true)] ?? 'New');
    }
}, 10, 2);

add_action('add_meta_boxes_rec_enquiry', function () {
    add_meta_box('rec_enquiry_details', 'Enquiry details', function (WP_Post $post) {
        wp_nonce_field('rec_save_enquiry', 'rec_enquiry_admin_nonce');
        $property_id = (int) get_post_meta($post->ID, '_rec_property_id', true);
        $rows = [
            'Name' => get_post_meta($post->ID, '_rec_name', true),
            'Phone' => get_post_meta($post->ID, '_rec_phone', true),
            'Email' => get_post_meta($post->ID, '_rec_email', true),
        ];
        echo '<table class="form-table"><tbody>';
        foreach ($rows as $label => $value) {
            echo '<tr><th>' . esc_html($label) . '</th><td>' . esc_html($value ?: '—') . '</td></tr>';
        }
        if ($property_id) {
            echo '<tr><th>Property</th><td><a href="' . esc_url(get_permalink($property_id)) . '" target="_blank">' . esc_html(get_the_title($property_id)) . '</a></td></tr>';
        }
        echo '<tr><th>Message</th><td>' . nl2br(esc_html(get_post_meta($post->ID, '_rec_message', true))) . '</td></tr>';
        $status = get_post_meta($post->ID, '_rec_status', true) ?: 'new';
        echo '<tr><th>Status</th><td><select name="rec_status">';
        foreach (['new' => 'New', 'contacted' => 'Contacted', 'closed' => 'Closed'] as $v => $l) {
            echo '<option value="' . esc_attr($v) . '"' . selected($status, $v, false) . '>' . esc_html($l) . '</option>';
        }
        echo '</select></td></tr></tbody></table>';
    }, 'rec_enquiry', 'normal', 'high');
});

add_action('save_post_rec_enquiry', function ($post_id) {
    if (! isset($_POST['rec_enquiry_admin_nonce']) || ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['rec_enquiry_admin_nonce'])), 'rec_save_enquiry')) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }
    $status = sanitize_key(wp_unslash($_POST['rec_status'] ?? 'new'));
    if (in_array($status, ['new', 'contacted', 'closed'], true)) {
        update_post_meta($post_id, '_rec_status', $status);
    }
});
