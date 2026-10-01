<?php

defined('ABSPATH') || exit;

add_action('add_meta_boxes', function () {
    add_meta_box('rec_details', 'Property Details', 'rec_render_details_box', 'property', 'normal', 'high');
});

function rec_render_details_box(WP_Post $post): void
{
    wp_nonce_field('rec_save_property', 'rec_nonce');
    echo '<div class="rec-admin-grid">';
    foreach (rec_fields() as $key => $field) {
        $name = 'rec[' . $key . ']';
        $id = 'rec-' . $key;
        $value = rec_get($post->ID, $key);
        $wide = in_array($field['type'], ['textarea', 'gallery'], true) || in_array($key, ['address', 'map_url', 'video_url'], true);

        echo '<div class="rec-admin-field' . ($wide ? ' rec-wide' : '') . '">';
        if ($field['type'] !== 'checkbox') {
            echo '<label for="' . esc_attr($id) . '"><strong>' . esc_html($field['label']) . '</strong></label>';
        }

        switch ($field['type']) {
            case 'select':
                echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
                foreach ($field['options'] as $v => $l) {
                    echo '<option value="' . esc_attr($v) . '"' . selected($value, $v, false) . '>' . esc_html($l) . '</option>';
                }
                echo '</select>';
                break;
            case 'textarea':
                echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="4">' . esc_textarea($value) . '</textarea>';
                break;
            case 'checkbox':
                echo '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1"' . checked($value, '1', false) . '> ' . esc_html($field['label']) . '</label>';
                break;
            case 'gallery':
                $ids = array_filter(array_map('absint', explode(',', (string) $value)));
                echo '<input type="hidden" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr(implode(',', $ids)) . '">';
                echo '<div class="rec-gallery-preview" data-for="' . esc_attr($id) . '">';
                foreach ($ids as $att) {
                    echo wp_get_attachment_image($att, [80, 60]);
                }
                echo '</div>';
                echo '<p><button type="button" class="button rec-gallery-pick" data-for="' . esc_attr($id) . '">Choose photos</button> ';
                echo '<button type="button" class="button-link rec-gallery-clear" data-for="' . esc_attr($id) . '">Clear</button></p>';
                echo '<p class="description">The featured image is the main photo; these appear in the gallery on the property page.</p>';
                break;
            default:
                $type = $field['type'] === 'number' ? 'number' : ($field['type'] === 'url' ? 'url' : 'text');
                $step = $key === 'price' ? '1' : 'any';
                echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . ($type === 'number' ? ' min="0" step="' . $step . '"' : '') . '>';
        }

        if (! empty($field['help'])) {
            echo '<p class="description">' . esc_html($field['help']) . '</p>';
        }
        echo '</div>';
    }
    echo '</div>';
}

add_action('save_post_property', function ($post_id) {
    if (! isset($_POST['rec_nonce']) || ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['rec_nonce'])), 'rec_save_property')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ! current_user_can('edit_post', $post_id)) {
        return;
    }

    $input = isset($_POST['rec']) && is_array($_POST['rec']) ? wp_unslash($_POST['rec']) : [];

    foreach (rec_fields() as $key => $field) {
        $raw = $input[$key] ?? '';
        switch ($field['type']) {
            case 'number':
                $value = $raw === '' ? '' : (string) max(0, (float) $raw);
                break;
            case 'url':
                $value = esc_url_raw(trim((string) $raw));
                break;
            case 'textarea':
                $value = sanitize_textarea_field((string) $raw);
                break;
            case 'checkbox':
                $value = $raw ? '1' : '';
                break;
            case 'select':
                $value = array_key_exists($raw, $field['options']) ? $raw : array_key_first($field['options']);
                break;
            case 'gallery':
                $value = implode(',', array_filter(array_map('absint', explode(',', (string) $raw))));
                break;
            default:
                $value = sanitize_text_field((string) $raw);
        }
        update_post_meta($post_id, '_rec_' . $key, $value);
    }
});

add_action('admin_enqueue_scripts', function ($hook) {
    $screen = get_current_screen();
    if (! $screen || $screen->post_type !== 'property' || ! in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style('rec-admin', REC_URL . 'assets/admin.css', [], REC_VERSION);
    wp_enqueue_script('rec-admin', REC_URL . 'assets/admin.js', ['jquery'], REC_VERSION, true);
});
