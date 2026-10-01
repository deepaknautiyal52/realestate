<?php
defined('ABSPATH') || exit;

get_header();

$title = 'Properties';
if (is_tax()) {
    $title = single_term_title('', false);
}
global $wp_query;
$params = rec_search_params();
$base = remove_query_arg(['sort', 'paged']);
?>
<div id="primary" class="content-area rec-page">
    <section class="rec-hero rec-hero-small">
        <div class="rec-container">
            <h1><?php echo esc_html($title); ?></h1>
            <p><?php echo esc_html(sprintf(_n('%s property found', '%s properties found', (int) $wp_query->found_posts, 'realestate-core'), number_format_i18n($wp_query->found_posts))); ?></p>
        </div>
    </section>

    <div class="rec-container">
        <?php echo rec_search_form(); ?>

        <div class="rec-toolbar">
            <span></span>
            <label>Sort by
                <select onchange="window.location.href=this.value">
                    <?php foreach (['newest' => 'Newest first', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low'] as $v => $l) : ?>
                        <option value="<?php echo esc_url(add_query_arg('sort', $v, $base)); ?>" <?php selected($params['sort'], $v); ?>><?php echo esc_html($l); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <?php if (have_posts()) : ?>
            <div class="rec-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php echo rec_card(get_the_ID()); ?>
                <?php endwhile; ?>
            </div>
            <div class="rec-pagination">
                <?php echo paginate_links(['prev_text' => '← Previous', 'next_text' => 'Next →']); ?>
            </div>
        <?php else : ?>
            <div class="rec-empty">
                <h3>No properties match your search</h3>
                <p>Try widening the budget or location, or <a href="<?php echo esc_url(get_post_type_archive_link('property')); ?>">see all properties</a>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
get_footer();
