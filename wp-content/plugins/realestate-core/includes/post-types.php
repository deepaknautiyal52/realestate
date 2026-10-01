<?php

defined('ABSPATH') || exit;

function rec_register_post_types(): void
{
    register_post_type('property', [
        'labels' => [
            'name' => 'Properties',
            'singular_name' => 'Property',
            'add_new_item' => 'Add New Property',
            'edit_item' => 'Edit Property',
            'all_items' => 'All Properties',
            'search_items' => 'Search Properties',
            'not_found' => 'No properties found',
            'menu_name' => 'Properties',
        ],
        'public' => true,
        'has_archive' => 'properties',
        'rewrite' => ['slug' => 'property', 'with_front' => false],
        'menu_icon' => 'dashicons-admin-home',
        'menu_position' => 5,
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'elementor'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('property_type', 'property', [
        'labels' => ['name' => 'Property Types', 'singular_name' => 'Property Type', 'menu_name' => 'Types'],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'property-type'],
    ]);

    register_taxonomy('property_location', 'property', [
        'labels' => ['name' => 'Locations', 'singular_name' => 'Location', 'menu_name' => 'Locations'],
        'hierarchical' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => 'location'],
    ]);

    register_post_type('rec_enquiry', [
        'labels' => [
            'name' => 'Enquiries',
            'singular_name' => 'Enquiry',
            'all_items' => 'Enquiries',
            'edit_item' => 'Enquiry',
            'not_found' => 'No enquiries yet',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'edit.php?post_type=property',
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => ['create_posts' => 'do_not_allow'],
        'map_meta_cap' => true,
    ]);
}
add_action('init', 'rec_register_post_types');

// Let Elementor edit property descriptions.
add_action('init', function () {
    $cpts = (array) get_option('elementor_cpt_support', ['page', 'post']);
    if (! in_array('property', $cpts, true)) {
        $cpts[] = 'property';
        update_option('elementor_cpt_support', $cpts);
    }
}, 20);

// Admin list columns for properties.
add_filter('manage_property_posts_columns', function ($cols) {
    $new = [];
    foreach ($cols as $key => $label) {
        if ($key === 'title') {
            $new['rec_thumb'] = '';
        }
        $new[$key] = $label;
        if ($key === 'title') {
            $new['rec_price'] = 'Price';
            $new['rec_purpose'] = 'For';
            $new['rec_status'] = 'Availability';
        }
    }
    return $new;
});

add_action('manage_property_posts_custom_column', function ($col, $post_id) {
    switch ($col) {
        case 'rec_thumb':
            echo get_the_post_thumbnail($post_id, [60, 45]);
            break;
        case 'rec_price':
            echo wp_kses_post(rec_price_html($post_id));
            break;
        case 'rec_purpose':
            echo esc_html(rec_option_label('purpose', rec_get($post_id, 'purpose') ?: 'sale'));
            break;
        case 'rec_status':
            echo esc_html(rec_option_label('status', rec_get($post_id, 'status') ?: 'available'));
            echo rec_get($post_id, 'featured') ? ' <span title="Featured">★</span>' : '';
            break;
    }
}, 10, 2);
