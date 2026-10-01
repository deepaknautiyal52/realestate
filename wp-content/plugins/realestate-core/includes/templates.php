<?php

defined('ABSPATH') || exit;

// Use the plugin's templates unless the theme provides its own.
add_filter('template_include', function ($template) {
    if (is_singular('property')) {
        return locate_template('single-property.php') ?: REC_DIR . 'templates/single-property.php';
    }
    if (is_post_type_archive('property') || is_tax(['property_type', 'property_location'])) {
        return locate_template('archive-property.php') ?: REC_DIR . 'templates/archive-property.php';
    }
    return $template;
});

// Astra: no sidebar and full width on property pages (the templates lay themselves out).
foreach (['astra_page_layout', 'astra_get_content_layout'] as $hook) {
    add_filter($hook, function ($layout) use ($hook) {
        if (is_singular('property') || is_post_type_archive('property') || is_tax(['property_type', 'property_location'])) {
            return $hook === 'astra_page_layout' ? 'no-sidebar' : 'page-builder';
        }
        return $layout;
    });
}

/** Listing card used by the archive, shortcodes and "similar properties". */
function rec_card(int $post_id): string
{
    $status = rec_get($post_id, 'status') ?: 'available';
    $purpose = rec_get($post_id, 'purpose') ?: 'sale';
    $location = rec_first_term($post_id, 'property_location');
    $type = rec_first_term($post_id, 'property_type');
    $beds = rec_get($post_id, 'bedrooms');
    $baths = rec_get($post_id, 'bathrooms');
    $area = rec_area_html($post_id);

    ob_start(); ?>
    <article class="rec-card">
        <a class="rec-card-media" href="<?php echo esc_url(get_permalink($post_id)); ?>">
            <?php if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, 'medium_large', ['loading' => 'lazy']);
            } else {
                echo '<span class="rec-card-noimg"></span>';
            } ?>
            <span class="rec-badge rec-badge-<?php echo esc_attr($purpose); ?>">For <?php echo esc_html(rec_option_label('purpose', $purpose)); ?></span>
            <?php if ($status !== 'available') : ?>
                <span class="rec-badge rec-badge-status"><?php echo esc_html(rec_option_label('status', $status)); ?></span>
            <?php endif; ?>
        </a>
        <div class="rec-card-body">
            <div class="rec-card-price"><?php echo wp_kses_post(rec_price_html($post_id)); ?></div>
            <h3 class="rec-card-title"><a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?></a></h3>
            <?php if ($location || $type) : ?>
                <p class="rec-card-meta"><?php echo rec_icon('pin'); ?> <?php echo esc_html(implode(' · ', array_filter([$type, $location]))); ?></p>
            <?php endif; ?>
            <ul class="rec-facts">
                <?php if ($beds) : ?><li><?php echo rec_icon('bed'); ?> <?php echo esc_html($beds); ?> Beds</li><?php endif; ?>
                <?php if ($baths) : ?><li><?php echo rec_icon('bath'); ?> <?php echo esc_html($baths); ?> Baths</li><?php endif; ?>
                <?php if ($area) : ?><li><?php echo rec_icon('area'); ?> <?php echo $area; // escaped in helper ?></li><?php endif; ?>
            </ul>
        </div>
    </article>
    <?php
    return (string) ob_get_clean();
}

/** Search/filter form. $action defaults to the properties archive. */
function rec_search_form(bool $compact = false): string
{
    $p = rec_search_params();
    $locations = get_terms(['taxonomy' => 'property_location', 'hide_empty' => false]);
    $types = get_terms(['taxonomy' => 'property_type', 'hide_empty' => false]);
    $budgets = [
        '' => 'Any budget',
        '2500000' => 'Up to ₹25 Lakh',
        '5000000' => 'Up to ₹50 Lakh',
        '10000000' => 'Up to ₹1 Cr',
        '20000000' => 'Up to ₹2 Cr',
    ];

    ob_start(); ?>
    <form class="rec-search<?php echo $compact ? ' rec-search-compact' : ''; ?>" method="get" action="<?php echo esc_url(get_post_type_archive_link('property')); ?>">
        <div class="rec-search-field rec-search-keyword">
            <label>Search</label>
            <input type="text" name="keyword" value="<?php echo esc_attr($p['keyword']); ?>" placeholder="Locality, project or keyword">
        </div>
        <div class="rec-search-field">
            <label>Looking to</label>
            <select name="purpose">
                <option value="">Buy or rent</option>
                <option value="sale" <?php selected($p['purpose'], 'sale'); ?>>Buy</option>
                <option value="rent" <?php selected($p['purpose'], 'rent'); ?>>Rent</option>
            </select>
        </div>
        <div class="rec-search-field">
            <label>Location</label>
            <select name="location">
                <option value="">All locations</option>
                <?php foreach ((array) $locations as $t) : if (! is_object($t)) { continue; } ?>
                    <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($p['location'], $t->slug); ?>><?php echo esc_html($t->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rec-search-field">
            <label>Type</label>
            <select name="type">
                <option value="">All types</option>
                <?php foreach ((array) $types as $t) : if (! is_object($t)) { continue; } ?>
                    <option value="<?php echo esc_attr($t->slug); ?>" <?php selected($p['type'], $t->slug); ?>><?php echo esc_html($t->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="rec-search-field">
            <label>Budget</label>
            <select name="max_price">
                <?php foreach ($budgets as $v => $l) : ?>
                    <option value="<?php echo esc_attr($v); ?>" <?php selected((string) $p['max_price'], (string) $v); ?>><?php echo esc_html($l); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if (! $compact) : ?>
            <div class="rec-search-field">
                <label>Bedrooms</label>
                <select name="beds">
                    <option value="">Any</option>
                    <?php foreach ([1, 2, 3, 4] as $b) : ?>
                        <option value="<?php echo $b; ?>" <?php selected((string) $p['beds'], (string) $b); ?>><?php echo $b; ?>+</option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="rec-search-field rec-search-submit">
            <button type="submit" class="rec-btn">Search</button>
        </div>
    </form>
    <?php
    return (string) ob_get_clean();
}
