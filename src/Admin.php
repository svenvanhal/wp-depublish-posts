<?php

namespace WPDP;

/**
 * Additional WordPress admin settings and screen modifications.
 *
 * @package   WPDP
 */
class Admin
{
    /**
     * Hook into WordPress.
     */
    static function register_hooks()
    {

        // Column headings
        add_filter('manage_posts_columns', [__CLASS__, 'column_header']);
        add_filter('manage_pages_columns', [__CLASS__, 'column_header']);

        // Column content
        add_action('manage_posts_custom_column', [__CLASS__, 'column_content'], 10, 2);
        add_action('manage_pages_custom_column', [__CLASS__, 'column_content'], 10, 2);

        // Column sorting (after init so custom post types are registered)
        add_action('init', [__CLASS__, '_register_sorting_hooks']);
        add_filter('request', [__CLASS__, 'column_depublication_date_sort']);
    }

    static function _register_sorting_hooks()
    {
        $post_types = Helper::get_enabled_post_types();

        foreach ($post_types as $pt) {
            add_filter('manage_edit-'.$pt.'_sortable_columns', [__CLASS__, 'column_sorting']);
        }
    }

    static function column_header($defaults)
    {
        $defaults['depublication_date'] = __('Expires', 'wp-depublish-posts');

        return $defaults;
    }

    static function column_content($column, $post_id)
    {

        switch ($column) {
            case 'depublication_date':

                $date = Helper::get_depublish_date($post_id);

                if (empty($date)) {
                    echo '<span aria-hidden="true">&#8212;</span>';
                } else {

                    $ts = strtotime($date);
                    $diff = human_time_diff(time(), strtotime($date));
                    $abbr = '<abbr title="%s">%s</abbr>';

                    if ($ts < time()) {
                        echo sprintf($abbr, esc_attr($date), $diff).' '.__('ago');
                    } else {
                        echo __('in').' '.sprintf($abbr, esc_attr($date), $diff);
                    }
                }

                break;
        }
    }

    static function column_sorting($columns)
    {
        $columns['depublication_date'] = 'depublication_date';

        return $columns;
    }

    static function column_depublication_date_sort($vars)
    {
        if (isset($vars['orderby']) && 'depublication_date' == $vars['orderby']) {
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
