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
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

/**
 * Register the "post-nasa-gallery" custom post type
 */
function pluginprefix_setup_post_type()
{
    register_post_type('post-nasa-gallery', ['public' => true]);
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
}
register_deactivation_hook(__FILE__, 'pluginprefix_deactivate');


if (is_admin()) {
    // we are in admin mode
    require_once __DIR__ . '/admin/wp-nasa-images-plugin.php';
}
