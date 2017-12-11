<?php

namespace WPDP;

/**
 * @package   WPDP
 * @author    Sven van Hal <sven@svenvanhal.nl>
 *
 * @wordpress-plugin
 * Plugin Name:       Depublish Posts
 * Description:       Set an expiration date for posts.
 * Version:           1.0.0
 * Author:            Sven van Hal
 * Author URI:        Sven van Hal <sven@svenvanhal.nl>
 * Text Domain:       wp-depublish-posts
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

// Define base path
define('WPDP_ASSET_BASE', __FILE__);

// Load autoloader
require "autoloader.php";

/**
 * WP Depublish Posts. Schedule posts to expire.
 *
 * @package WISVCH
 */
class WPDP_Plugin
{
    /**
     * Initialize plug-in.
     */
    function __construct()
    {

        // Setup
        add_action('admin_enqueue_scripts', [__CLASS__, 'load_assets']);

        // Localization
        add_action('plugins_loaded', [__CLASS__, 'load_textdomain']);

        // Load sub-components
        Post::register_hooks();
        Scheduler::register_hooks();
        Metabox::register_hooks();

        if (is_admin()) {
            Columns::register_hooks();
        }
    }

    /**
     * Load plug-in assets.
     *
     * @return void
     */
    public static function load_assets($hook)
    {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_style('wp-depublish-posts', plugins_url('assets/wp-depublish-posts.css', WPDP_ASSET_BASE));
            wp_enqueue_script('wp-depublish-posts', plugins_url('assets/wp-depublish-posts.js', WPDP_ASSET_BASE), ['jquery'], false, true);
        }
    }

    /**
     * Load plugin textdomain.
     *
     * @return void
     */
    public static function load_textdomain()
    {
        load_plugin_textdomain('wp-depublish-posts', false, dirname(plugin_basename(WPDP_ASSET_BASE)).'/lang/');
    }
}

new WPDP_Plugin();
