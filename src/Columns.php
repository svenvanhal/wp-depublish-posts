<?php

namespace WPDP;

/**
 * WordPress admin screen modifications.
 *
 * @package   WPDP
 */
class Columns
{
    /**
     * Hook into WordPress.
     */
    public static function register_hooks()
    {

        // Column headings
        add_filter('manage_posts_columns', [__CLASS__, 'column_header']);
        add_filter('manage_pages_columns', [__CLASS__, 'column_header']);

        // Column content
        add_action('manage_posts_custom_column', [__CLASS__, 'column_content'], 10, 2);
        add_action('manage_pages_custom_column', [__CLASS__, 'column_content'], 10, 2);

        // Column sorting (after init so custom post types are registered)
        add_action('init', [__CLASS__, '_register_sorting_hooks']);
        add_filter('request', [__CLASS__, 'column_depublish_date_sort']);
    }

    public static function _register_sorting_hooks()
    {
        $post_types = Helper::get_enabled_post_types();

        foreach ($post_types as $pt) {
            add_filter('manage_edit-'.$pt.'_sortable_columns', [__CLASS__, 'column_sorting']);
        }
    }

    public static function column_header($defaults)
    {
        $defaults['depublish_date'] = __('Expires', 'wp-depublish-posts');

        return $defaults;
    }

    public static function column_content($column, $post_id)
    {

        switch ($column) {
            case 'depublish_date':

                if (Helper::is_depublish_enabled($post_id, false)) {

                    $date = Helper::get_depublish_date($post_id);

                    if (empty($date)) {
                        echo '<span aria-hidden="true">&#8212;</span>';
                    } else {

                        $ts = strtotime($date);
                        $diff = human_time_diff(time(), strtotime($date));
                        $abbr = '<abbr title="%s">%s</abbr>';

                        // Add 'in' or 'ago' to human readable time diff
                        if ($ts < time()) {
                            $output = sprintf(__('%1$s ago', 'wp-depublish-posts'), $diff);
                        } else {
                            $output = sprintf(__('in %1$s', 'wp-depublish-posts'), $diff);
                        }

                        printf($abbr, esc_attr($date), $output);
                    }
                } else {
                    echo '<span aria-hidden="true">&#8212;</span>';
                }

                break;
        }
    }

    public static function column_sorting($columns)
    {
        $columns['depublish_date'] = 'depublish_date';

        return $columns;
    }

    public static function column_depublish_date_sort($vars)
    {
        if (isset($vars['orderby']) && 'depublish_date' == $vars['orderby']) {
            $vars = array_merge($vars, [
                'meta_query' => [
                    'relation' => 'OR',
                    'depublish_date',
                    [
                        'key' => '_depublish_date',
                        'type' => 'DATETIME',
                    ],
                    [
                        'key' => '_depublish_date',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
                'orderby' => 'depublish_date',
            ]);
        }

        return $vars;
    }
}
