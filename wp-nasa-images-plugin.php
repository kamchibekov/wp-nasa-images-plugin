<?php

/**
 * Plugin Name: Nasa images
 * Description: Shows images from Nasa imagery
 * Version: 1.0
 * Author: Adilet Kamchibekov
 * Author URI: https://github.com/kamchibekov
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-nasa-images-plugin
 */


// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

/**
 * Register the "post-nasa-gallery" custom post type
 */
function pluginprefix_setup_post_type()
{
    register_post_type('post-nasa-gallery', [
        'label' => 'Nasa Images Posts',
        'public' => true
    ]);
}
add_action('init', 'pluginprefix_setup_post_type');


/**
 * Activate the plugin.
 */
function pluginprefix_activate()
{
    // Trigger our function that registers the custom post type plugin.
    pluginprefix_setup_post_type();
    // Clear the permalinks after the post type has been registered.
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'pluginprefix_activate');

/**
 * Deactivation hook.
 */
function pluginprefix_deactivate()
{
    // Unregister the post type, so the rules are no longer in memory.
    unregister_post_type('post-nasa-gallery');
    // Clear the permalinks to remove our post type's rules from the database.
    flush_rewrite_rules();

    // remove cron task
    wp_clear_scheduled_hook('nasa_images_pull_task');

    // Don't remove options and transients
    //delete_option('nasa-images-5loaded');
    //delete_transient('nasa_images_api');

    // hide the sortcode incase if not deleted
    add_shortcode('nasa-images-gallery', function () {
        return null;
    });
}
register_deactivation_hook(__FILE__, 'pluginprefix_deactivate');


if (is_admin()) {
    // we are in admin mode
    require_once __DIR__ . '/admin/wp-nasa-images-plugin.php';
} else {
    add_action("wp_enqueue_scripts", "myscripts");

    function myscripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_style('slick-theme', plugins_url('/public/css/slick-theme.css', __FILE__));
        wp_enqueue_style('slick-css', plugins_url('/public/css/slick.css', __FILE__));
        wp_enqueue_script('slick-js',  plugins_url('/public/js/slick.min.js', __FILE__));
    }
}


//[nasa-images-gallery]
function get_gallery()
{
    ob_start();
    $args = array(
        'post_type' => 'post-nasa-gallery',
    );

    $the_query = new WP_Query($args);
    if ($the_query->have_posts()) : ?>
        <section class="nasa-images-gallery slider">
            <?php
            while ($the_query->have_posts()) :
                $the_query->the_post(); ?>

                <div>
                    <img src="<?php echo get_the_post_thumbnail_url(null, [500, 500]) ?>">
                </div>


            <?php endwhile; ?>
        </section>
        <script type="text/javascript">
            jQuery(".nasa-images-gallery").slick({
                dots: true,
                infinite: true,
                speed: 500,
                fade: true,
                cssEase: 'linear'
            });
        </script>
<?php wp_reset_postdata();
    else :
    endif;
    return ob_get_clean();
}
add_shortcode('nasa-images-gallery', 'get_gallery');
