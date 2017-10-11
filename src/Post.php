<?php

namespace WPDP;

/**
 * Depublish post logic.
 *
 * @package   WPDP
 */
class Post
{
    /**
     * Hook into WordPress.
     */
    static function register_hooks()
    {
        add_action('depublish_post', [__CLASS__, 'check_and_depublish_post']);
    }

    /**
     * Depublish scheduled post and make sure post ID has pending post status.
     *
     * Invoked by cron 'depublish_post' event. This safeguard prevents cron from depublishing drafts, etc.
     *
     * @param int|WP_Post $post_id Post ID or post object.
     */
    static function check_and_depublish_post($post_id)
    {

        if (! $post = get_post($post_id)) {
            return;
        }

        // Check if scheduled for depublishing (and is already published)
        if (! Helper::is_depublish_enabled($post) || 'publish' !== $post->post_status) {
            return;
        }

        // Get depublish date
        $dep_date = Helper::get_depublish_date($post);

        if (empty($dep_date)) {
            return;
        }

        $ts = strtotime($dep_date);

        // Uh oh, someone jumped the gun!
        if ($ts > time()) {
            wp_clear_scheduled_hook('depublish_post', [$post_id]); // clear anything else in the system
            wp_schedule_single_event($ts, 'depublish_post', [$post_id]);

            return;
        }

        // Depublish post
        self::depublish_post($post);
    }

    /**
     * Depublish a post by transitioning the post status.
     *
     * @global wpdb $wpdb WordPress database abstraction object.
     *
     * @param int|WP_Post $post Post ID or post object.
     */
    static function depublish_post($post)
    {

        if (! $post = get_post($post)) {
            return;
        }

        global $wpdb;

        $wpdb->update($wpdb->posts, ['post_status' => 'pending'], ['ID' => $post->ID]);

        clean_post_cache($post->ID);

        $old_status = $post->post_status;
        $post->post_status = 'pending';
        wp_transition_post_status('pending', $old_status, $post);

        /** This action is documented in wp-includes/post.php */
        do_action('edit_post', $post->ID, $post);

        /** This action is documented in wp-includes/post.php */
        do_action("save_post_{$post->post_type}", $post->ID, $post, true);

        /** This action is documented in wp-includes/post.php */
        do_action('save_post', $post->ID, $post, true);

        /** This action is documented in wp-includes/post.php */
        do_action('wp_insert_post', $post->ID, $post, true);
    }
}
