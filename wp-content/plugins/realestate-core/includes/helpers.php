<?php

defined('ABSPATH') || exit;

/** Property fields: key => [label, type, options]. Stored as post meta "_rec_{key}". */
function rec_fields(): array
{
    return [
        'price' => ['label' => 'Price (₹)', 'type' => 'number', 'help' => 'Full amount in rupees, e.g. 4500000. For rentals, the monthly rent.'],
        'price_label' => ['label' => 'Price text (optional)', 'type' => 'text', 'help' => 'Shown instead of the number, e.g. "Price on request".'],
        'purpose' => ['label' => 'Listed for', 'type' => 'select', 'options' => ['sale' => 'Sale', 'rent' => 'Rent']],
        'status' => ['label' => 'Availability', 'type' => 'select', 'options' => ['available' => 'Available', 'under_offer' => 'Under offer', 'sold' => 'Sold', 'rented' => 'Rented']],
        'bedrooms' => ['label' => 'Bedrooms', 'type' => 'number'],
        'bathrooms' => ['label' => 'Bathrooms', 'type' => 'number'],
        'area' => ['label' => 'Area', 'type' => 'number'],
        'area_unit' => ['label' => 'Area unit', 'type' => 'select', 'options' => ['sqft' => 'sq ft', 'sqyd' => 'sq yd', 'sqm' => 'sq m', 'nali' => 'nali', 'bigha' => 'bigha', 'acre' => 'acre']],
        'address' => ['label' => 'Address / locality', 'type' => 'text'],
        'map_url' => ['label' => 'Google Maps link', 'type' => 'url'],
        'video_url' => ['label' => 'Video tour link (YouTube)', 'type' => 'url'],
        'amenities' => ['label' => 'Amenities (one per line)', 'type' => 'textarea'],
        'featured' => ['label' => 'Featured on home page', 'type' => 'checkbox'],
        'gallery' => ['label' => 'Photo gallery', 'type' => 'gallery'],
    ];
}

function rec_get(int $post_id, string $key)
{
    return get_post_meta($post_id, '_rec_' . $key, true);
}

function rec_settings(): array
{
    return wp_parse_args((array) get_option('rec_settings', []), [
        'enquiry_email' => get_option('admin_email'),
        'phone' => '',
        'whatsapp' => '',
        'per_page' => 9,
    ]);
}

/** Indian number grouping: 4500000 -> 45,00,000 */
function rec_indian_number($n): string
{
    $n = (string) (int) round((float) $n);
    if (strlen($n) <= 3) {
        return $n;
    }
    $last3 = substr($n, -3);
    $rest = substr($n, 0, -3);
    return preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last3;
}

/** Short price: 4500000 -> ₹45 Lakh, 12500000 -> ₹1.25 Cr */
function rec_short_price($n): string
{
    $n = (float) $n;
    if ($n >= 10000000) {
        return '₹' . rtrim(rtrim(number_format($n / 10000000, 2), '0'), '.') . ' Cr';
    }
    if ($n >= 100000) {
        return '₹' . rtrim(rtrim(number_format($n / 100000, 2), '0'), '.') . ' Lakh';
    }
    return '₹' . rec_indian_number($n);
}

function rec_price_html(int $post_id): string
{
    $label = rec_get($post_id, 'price_label');
    if ($label) {
        return esc_html($label);
    }
    $price = rec_get($post_id, 'price');
    if ($price === '' || $price === null) {
        return esc_html__('Price on request', 'realestate-core');
    }
    $html = esc_html(rec_short_price($price));
    if (rec_get($post_id, 'purpose') === 'rent') {
        $html .= '<span class="rec-per">/month</span>';
    }
    return $html;
}

function rec_option_label(string $key, $value): string
{
    $fields = rec_fields();
    return $fields[$key]['options'][$value] ?? (string) $value;
}

function rec_area_html(int $post_id): string
{
    $area = rec_get($post_id, 'area');
    if (! $area) {
        return '';
    }
    return esc_html(rec_indian_number($area) . ' ' . rec_option_label('area_unit', rec_get($post_id, 'area_unit') ?: 'sqft'));
}

function rec_first_term(int $post_id, string $taxonomy): string
{
    $terms = get_the_terms($post_id, $taxonomy);
    return $terms && ! is_wp_error($terms) ? $terms[0]->name : '';
}

function rec_icon(string $name): string
{
    $paths = [
        'bed' => '<path d="M3 18v-6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v6"/><path d="M3 18h18M3 14h18M7 10V7a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v3"/>',
        'bath' => '<path d="M4 12h16v3a4 4 0 0 1-4 4H8a4 4 0 0 1-4-4v-3Z"/><path d="M6 12V5a2 2 0 0 1 3.5-1.3"/><path d="M7 19l-1 2M17 19l1 2"/>',
        'area' => '<path d="M3 9V3h6M21 9V3h-6M3 15v6h6M21 15v6h-6"/>',
        'pin' => '<path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
    ];
    return '<svg class="rec-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}
