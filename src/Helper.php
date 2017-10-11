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
     * @param $post
     * @return bool|mixed|void
     */
    static function get_depublish_date($post)
    {

        if (! $post = get_post($post)) {
            return;
        }

        $dep_date_local_tz = get_post_meta($post->ID, '_depublish_date', true);

        // Convert to GMT
        $dep_date_gmt = get_gmt_from_date($dep_date_local_tz);

        if (self::_validate_depublish_date($dep_date_gmt)) {
            return $dep_date_gmt;
        }

        return false;
    }

    /**
     * Check if a meta value corresponds to an active depublish setting.
     *
     * @param $post
     * @return bool Whether or not depublishing is enabled.
     */
    static function is_depublish_enabled($post)
    {

        if (! $post = get_post($post)) {
            return;
        }

        $dep_enbl = get_post_meta($post->ID, '_depublish_enable', true);

        return ! empty($dep_enbl) && $dep_enbl === '1';
    }

    /**
     * @param int|string $date Date to validate. String is processed with strtotime(), int as timestamps.
     * @return bool Whether or not the date is valid.
     */
    private static function _validate_depublish_date($date)
    {

        if (empty($date)) {
            return;
        }

        // Timestamp
        if (is_numeric($date)) {
            return $date === date('U', $date);
        }

        // Datetime
        return $date === date('Y-m-d H:i:s', strtotime($date));
    }
}
