<?php

namespace WPDP;

/**
 * Post depublish scheduler.
 *
 * @package   WPDP
 */
class Scheduler
{
    /**
     * Hook into WordPress.
     */
    public static function register_hooks()
    {
        add_action('save_post', [__CLASS__, 'schedule'], 100);
    }

    /**
     * Hook used to schedule depublication for a post marked for the future.
     *
     * @param WP_Post $post Post object.
     */
    public static function schedule($post)
    {

        if (! $post = get_post($post)) {
            return;
        }

        if (Helper::is_depublish_enabled($post)) {

            $date = Helper::get_depublish_date($post);

            if (! empty($date)) {
                wp_clear_scheduled_hook('depublish_post', [$post->ID]);
                wp_schedule_single_event(strtotime($date), 'depublish_post', [$post->ID]);
            }
        }
    }
}
