<?php
defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    $id = get_the_ID();
    $settings = rec_settings();
    $gallery = array_filter(array_map('absint', explode(',', (string) rec_get($id, 'gallery'))));
    if (has_post_thumbnail()) {
        array_unshift($gallery, get_post_thumbnail_id());
    }
    $gallery = array_values(array_unique($gallery));
    $amenities = array_filter(array_map('trim', explode("\n", (string) rec_get($id, 'amenities'))));
    $purpose = rec_get($id, 'purpose') ?: 'sale';
    $status = rec_get($id, 'status') ?: 'available';
    $location = rec_first_term($id, 'property_location');
    $type = rec_first_term($id, 'property_type');
    $address = rec_get($id, 'address');
    $video = rec_get($id, 'video_url');
    $map = rec_get($id, 'map_url');
    $whatsapp = preg_replace('/\D+/', '', (string) $settings['whatsapp']);
    $facts = array_filter([
        'Price' => wp_strip_all_tags(rec_price_html($id)),
        'Listed for' => rec_option_label('purpose', $purpose),
        'Availability' => rec_option_label('status', $status),
        'Type' => $type,
        'Bedrooms' => rec_get($id, 'bedrooms'),
        'Bathrooms' => rec_get($id, 'bathrooms'),
        'Area' => wp_strip_all_tags(rec_area_html($id)),
        'Location' => $location,
    ]);
    ?>
<div id="primary" class="content-area rec-page">
    <div class="rec-container rec-single">
        <nav class="rec-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> /
            <a href="<?php echo esc_url(get_post_type_archive_link('property')); ?>">Properties</a> /
            <span><?php the_title(); ?></span>
        </nav>

        <header class="rec-single-header">
            <div>
                <span class="rec-badge rec-badge-<?php echo esc_attr($purpose); ?> rec-badge-inline">For <?php echo esc_html(rec_option_label('purpose', $purpose)); ?></span>
                <?php if ($status !== 'available') : ?><span class="rec-badge rec-badge-status rec-badge-inline"><?php echo esc_html(rec_option_label('status', $status)); ?></span><?php endif; ?>
                <h1><?php the_title(); ?></h1>
                <?php if ($address || $location) : ?>
                    <p class="rec-single-address"><?php echo rec_icon('pin'); ?> <?php echo esc_html(implode(', ', array_filter([$address, $location]))); ?></p>
                <?php endif; ?>
            </div>
            <div class="rec-single-price"><?php echo wp_kses_post(rec_price_html($id)); ?></div>
        </header>

        <?php if ($gallery) : ?>
            <div class="rec-gallery" data-rec-gallery>
                <div class="rec-gallery-main">
                    <?php echo wp_get_attachment_image($gallery[0], 'large', false, ['data-rec-main' => '1']); ?>
                </div>
                <?php if (count($gallery) > 1) : ?>
                    <div class="rec-gallery-thumbs">
                        <?php foreach ($gallery as $i => $att) : ?>
                            <button type="button" class="<?php echo $i === 0 ? 'active' : ''; ?>" data-full="<?php echo esc_url(wp_get_attachment_image_url($att, 'large')); ?>">
                                <?php echo wp_get_attachment_image($att, 'thumbnail'); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="rec-single-layout">
            <div class="rec-single-main">
                <ul class="rec-facts rec-facts-large">
                    <?php if (rec_get($id, 'bedrooms')) : ?><li><?php echo rec_icon('bed'); ?> <?php echo esc_html(rec_get($id, 'bedrooms')); ?> Bedrooms</li><?php endif; ?>
                    <?php if (rec_get($id, 'bathrooms')) : ?><li><?php echo rec_icon('bath'); ?> <?php echo esc_html(rec_get($id, 'bathrooms')); ?> Bathrooms</li><?php endif; ?>
                    <?php if (rec_area_html($id)) : ?><li><?php echo rec_icon('area'); ?> <?php echo rec_area_html($id); ?></li><?php endif; ?>
                </ul>

                <section class="rec-section">
                    <h2>About this property</h2>
                    <div class="rec-content"><?php the_content(); ?></div>
                </section>

                <section class="rec-section">
                    <h2>Details</h2>
                    <dl class="rec-details">
                        <?php foreach ($facts as $label => $value) : ?>
                            <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <?php if ($amenities) : ?>
                    <section class="rec-section">
                        <h2>Amenities</h2>
                        <ul class="rec-amenities">
                            <?php foreach ($amenities as $a) : ?><li><?php echo rec_icon('check'); ?> <?php echo esc_html($a); ?></li><?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <?php if ($video && ($embed = wp_oembed_get($video))) : ?>
                    <section class="rec-section">
                        <h2>Video tour</h2>
                        <div class="rec-video"><?php echo $embed; ?></div>
                    </section>
                <?php endif; ?>

                <?php if ($map) : ?>
                    <section class="rec-section">
                        <h2>Location</h2>
                        <p><a class="rec-btn rec-btn-outline" href="<?php echo esc_url($map); ?>" target="_blank" rel="noopener">View on Google Maps ↗</a></p>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="rec-single-side" id="rec-enquiry">
                <div class="rec-enquiry-box">
                    <h3>Interested in this property?</h3>
                    <p>Send us a message and we'll get back to you shortly.</p>
                    <?php if ($settings['phone'] || $whatsapp) : ?>
                        <div class="rec-contact-buttons">
                            <?php if ($settings['phone']) : ?>
                                <a class="rec-btn rec-btn-outline" href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $settings['phone'])); ?>"><?php echo rec_icon('phone'); ?> Call</a>
                            <?php endif; ?>
                            <?php if ($whatsapp) : ?>
                                <a class="rec-btn rec-btn-whatsapp" target="_blank" rel="noopener" href="<?php echo esc_url('https://wa.me/' . $whatsapp . '?text=' . rawurlencode('Hi, I am interested in: ' . get_the_title() . ' ' . get_permalink())); ?>">WhatsApp</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php echo rec_enquiry_form($id); ?>
                </div>
            </aside>
        </div>

        <?php
        $similar = new WP_Query([
            'post_type' => 'property',
            'posts_per_page' => 3,
            'post__not_in' => [$id],
            'no_found_rows' => true,
            'tax_query' => $type ? [['taxonomy' => 'property_type', 'field' => 'name', 'terms' => $type]] : [],
        ]);
        if ($similar->have_posts()) : ?>
            <section class="rec-section rec-similar">
                <h2>Similar properties</h2>
                <div class="rec-grid">
                    <?php while ($similar->have_posts()) : $similar->the_post(); echo rec_card(get_the_ID()); endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php
endwhile;

get_footer();
