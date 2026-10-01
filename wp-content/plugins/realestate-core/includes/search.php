<?php

defined('ABSPATH') || exit;

/** Filter values from the query string, sanitised. */
function rec_search_params(): array
{
    $get = fn ($k) => isset($_GET[$k]) ? sanitize_text_field(wp_unslash($_GET[$k])) : '';

    return [
        'keyword' => $get('keyword'),
        'location' => sanitize_title($get('location')),
        'type' => sanitize_title($get('type')),
        'purpose' => in_array($get('purpose'), ['sale', 'rent'], true) ? $get('purpose') : '',
        'min_price' => $get('min_price') !== '' ? absint($get('min_price')) : '',
        'max_price' => $get('max_price') !== '' ? absint($get('max_price')) : '',
        'beds' => $get('beds') !== '' ? absint($get('beds')) : '',
        'sort' => in_array($get('sort'), ['newest', 'price_asc', 'price_desc'], true) ? $get('sort') : 'newest',
    ];
}

/** Turns filter values into WP_Query args (also used by the [rec_properties] shortcode). */
function rec_apply_filters(array $args, array $p): array
{
    $meta = $args['meta_query'] ?? [];
    $tax = $args['tax_query'] ?? [];

    if ($p['keyword'] !== '') {
        $args['s'] = $p['keyword'];
    }
    if ($p['location'] !== '') {
        $tax[] = ['taxonomy' => 'property_location', 'field' => 'slug', 'terms' => $p['location']];
    }
    if ($p['type'] !== '') {
        $tax[] = ['taxonomy' => 'property_type', 'field' => 'slug', 'terms' => $p['type']];
    }
    if ($p['purpose'] !== '') {
        $meta[] = ['key' => '_rec_purpose', 'value' => $p['purpose']];
    }
    if ($p['min_price'] !== '') {
        $meta[] = ['key' => '_rec_price', 'value' => $p['min_price'], 'compare' => '>=', 'type' => 'NUMERIC'];
    }
    if ($p['max_price'] !== '') {
        $meta[] = ['key' => '_rec_price', 'value' => $p['max_price'], 'compare' => '<=', 'type' => 'NUMERIC'];
    }
    if ($p['beds'] !== '') {
        $meta[] = ['key' => '_rec_bedrooms', 'value' => $p['beds'], 'compare' => '>=', 'type' => 'NUMERIC'];
    }

    if ($p['sort'] === 'price_asc' || $p['sort'] === 'price_desc') {
        $meta['rec_price_sort'] = ['key' => '_rec_price', 'type' => 'NUMERIC'];
        $args['orderby'] = ['rec_price_sort' => $p['sort'] === 'price_asc' ? 'ASC' : 'DESC'];
    }

    if ($meta) {
        $args['meta_query'] = array_merge(['relation' => 'AND'], $meta);
    }
    if ($tax) {
        $args['tax_query'] = array_merge(['relation' => 'AND'], $tax);
    }

    return $args;
}

add_action('pre_get_posts', function (WP_Query $q) {
    if (is_admin() || ! $q->is_main_query()) {
        return;
    }
    if (! $q->is_post_type_archive('property') && ! $q->is_tax(['property_type', 'property_location'])) {
        return;
    }

    $q->set('posts_per_page', (int) rec_settings()['per_page']);
    foreach (rec_apply_filters([], rec_search_params()) as $key => $value) {
        $q->set($key, $value);
    }
});
