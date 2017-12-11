<?php

namespace WPDP;

/**
 * WPDB helper functions.
 *
 * @package   WPDP
 */
class Helper
{
    /**
     * Retrieves a post's depublish date.
     *
     * @param $post
     * @return bool|string
     */
    public static function get_depublish_date($post)
    {

        if (! $post = get_post($post)) {
            return false;
        }

        $dep_date_local_tz = get_post_meta($post->ID, '_depublish_date', true);

        if (empty($dep_date_local_tz)) {
            return false;
        }

        // Convert to GMT
        $dep_date_gmt = get_gmt_from_date($dep_date_local_tz);

        if (! self::validate_depublish_date($dep_date_gmt)) {
            return false;
        }

        return $dep_date_gmt;
    }

    /**
     * Check if a post is scheduled for depublication.
     *
     * @param $post
     * @return bool Whether or not depublish is enabled.
     */
    public static function is_depublish_enabled($post, $check_status = true)
    {

        if (! $post = get_post($post)) {
            return false;
        }

        // Check post status
        if ($check_status && $post->post_status !== 'publish') {
            return false;
        }

        // Check enabled flag
        $dep_enbl = get_post_meta($post->ID, '_depublish_enable', true);

        return ! empty($dep_enbl) && $dep_enbl === '1';
    }

    /**
     * Retrieve post types for which depublish is enabled.
     *
     * @return array Post types enabled for depublication
     */
    public static function get_enabled_post_types()
    {
        global $wp_post_types;

        $pt = wp_list_pluck($wp_post_types, 'name');

        // Remove irrelevant built-in post types
        unset($pt['revision']);
        unset($pt['nav_menu_item']);
        unset($pt['custom_css']);
        unset($pt['customize_changeset']);

        // @TODO Load enabled post type setting here

        if (empty($pt)) {
            return [];
        }

        return $pt;
    }

    /**
     * Validate a timestamp or datetime string.
     *
     * @param int|string $date Date to validate. String is processed with strtotime(), int as timestamps.
     * @return bool Whether or not the date is valid.
     */
    public static function validate_depublish_date($date)
    {

        if (empty($date)) {
            return false;
        }

        // Timestamp
        if (is_numeric($date)) {
            return $date === date('U', $date);
        }

        // Datetime
        return $date === date('Y-m-d H:i:s', strtotime($date));
    }
}
