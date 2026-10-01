<?php

defined('ABSPATH') || exit;

// [rec_search] – the property search bar (for the home page hero).
add_shortcode('rec_search', function ($atts) {
    $atts = shortcode_atts(['compact' => '1'], $atts, 'rec_search');
    return rec_search_form($atts['compact'] === '1');
});

// [rec_properties count="6" featured="1" purpose="sale" type="villa" location="dehradun"]
add_shortcode('rec_properties', function ($atts) {
    $atts = shortcode_atts([
        'count' => 6,
        'featured' => '',
        'purpose' => '',
        'type' => '',
        'location' => '',
        'columns' => 3,
    ], $atts, 'rec_properties');

    $args = [
        'post_type' => 'property',
        'posts_per_page' => max(1, min(24, (int) $atts['count'])),
        'no_found_rows' => true,
    ];
    if ($atts['featured'] === '1') {
        $args['meta_query'] = [['key' => '_rec_featured', 'value' => '1']];
    }
    $args = rec_apply_filters($args, [
        'keyword' => '',
        'location' => sanitize_title($atts['location']),
        'type' => sanitize_title($atts['type']),
        'purpose' => in_array($atts['purpose'], ['sale', 'rent'], true) ? $atts['purpose'] : '',
        'min_price' => '',
        'max_price' => '',
        'beds' => '',
        'sort' => 'newest',
    ]);

    $q = new WP_Query($args);
    if (! $q->have_posts()) {
        return '<p class="rec-empty-inline">New properties coming soon.</p>';
    }

    $html = '<div class="rec-grid rec-cols-' . (int) $atts['columns'] . '">';
    while ($q->have_posts()) {
        $q->the_post();
        $html .= rec_card(get_the_ID());
    }
    wp_reset_postdata();

    return $html . '</div>';
});

// [rec_locations] – tiles linking to each location with its property count.
add_shortcode('rec_locations', function () {
    $terms = get_terms(['taxonomy' => 'property_location', 'hide_empty' => false, 'parent' => 0]);
    if (! $terms || is_wp_error($terms)) {
        return '';
    }
    $html = '<div class="rec-locations">';
    foreach ($terms as $t) {
        $html .= sprintf(
            '<a class="rec-location" href="%s"><strong>%s</strong><span>%s</span></a>',
            esc_url(get_term_link($t)),
            esc_html($t->name),
            esc_html(sprintf(_n('%d property', '%d properties', $t->count, 'realestate-core'), $t->count))
        );
    }
    return $html . '</div>';
});

// [rec_contact_form] – general enquiry form (Contact page).
add_shortcode('rec_contact_form', function () {
    return rec_enquiry_form(0);
});
