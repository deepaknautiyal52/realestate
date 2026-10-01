<?php

defined('ABSPATH') || exit;

add_action('admin_menu', function () {
    add_submenu_page('edit.php?post_type=property', 'Real Estate Settings', 'Settings', 'manage_options', 'rec-settings', 'rec_render_settings');
});

add_action('admin_init', function () {
    register_setting('rec_settings', 'rec_settings', [
        'sanitize_callback' => function ($input) {
            $input = (array) $input;
            return [
                'enquiry_email' => sanitize_email($input['enquiry_email'] ?? ''),
                'phone' => sanitize_text_field($input['phone'] ?? ''),
                'whatsapp' => preg_replace('/\D+/', '', (string) ($input['whatsapp'] ?? '')),
                'per_page' => max(3, min(48, absint($input['per_page'] ?? 9))),
            ];
        },
    ]);
});

function rec_render_settings(): void
{
    $s = rec_settings(); ?>
    <div class="wrap">
        <h1>Real Estate Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('rec_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="rec-email">Send enquiries to</label></th>
                    <td><input id="rec-email" type="email" class="regular-text" name="rec_settings[enquiry_email]" value="<?php echo esc_attr($s['enquiry_email']); ?>">
                        <p class="description">Every enquiry is also saved under Properties → Enquiries.</p></td>
                </tr>
                <tr>
                    <th><label for="rec-phone">Phone number</label></th>
                    <td><input id="rec-phone" type="text" class="regular-text" name="rec_settings[phone]" value="<?php echo esc_attr($s['phone']); ?>" placeholder="+91 98765 43210">
                        <p class="description">Shows a "Call" button on property pages.</p></td>
                </tr>
                <tr>
                    <th><label for="rec-wa">WhatsApp number</label></th>
                    <td><input id="rec-wa" type="text" class="regular-text" name="rec_settings[whatsapp]" value="<?php echo esc_attr($s['whatsapp']); ?>" placeholder="919876543210">
                        <p class="description">With country code, digits only. Shows a "WhatsApp" button on property pages.</p></td>
                </tr>
                <tr>
                    <th><label for="rec-pp">Properties per page</label></th>
                    <td><input id="rec-pp" type="number" min="3" max="48" name="rec_settings[per_page]" value="<?php echo esc_attr($s['per_page']); ?>"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2>Shortcodes for Elementor</h2>
        <p>Add these with Elementor's <strong>Shortcode</strong> widget:</p>
        <ul style="list-style:disc;padding-left:20px">
            <li><code>[rec_search]</code> – property search bar</li>
            <li><code>[rec_properties count="6" featured="1"]</code> – property cards (also <code>purpose="rent"</code>, <code>type="villa"</code>, <code>location="dehradun"</code>)</li>
            <li><code>[rec_locations]</code> – location tiles</li>
            <li><code>[rec_contact_form]</code> – general enquiry form</li>
        </ul>
    </div>
    <?php
}
