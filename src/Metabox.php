<?php

namespace WPDP;

/**
 * Registration of depublish metabox on edit screens.
 *
 * @package   WPDP
 */
class Metabox
{
    /**
     * Hook into WordPress.
     */
    static function register_hooks()
    {

        add_action('post_submitbox_misc_actions', [__CLASS__, 'render']);
        add_action('save_post', [__CLASS__, 'save_meta']);
    }

    /**
     * Saves expiration date as post meta.
     */
    static function save_meta($post_id)
    {

        global $post;

        // Check Autosave
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit'])) {
            return $post_id;
        }

        // Don't save if only a revision
        if (isset($post->post_type) && $post->post_type == 'revision') {
            return $post_id;
        }

        // Verify nonce
        if (! isset($_POST['_depublish_nonce']) || ! wp_verify_nonce($_POST['_depublish_nonce'], 'depublish_save_'.$post_id)) {
            return $post_id;
        }

        $post_type = $post->post_type;
        $post_type_object = get_post_type_object($post_type);
        $can_publish = current_user_can($post_type_object->cap->publish_posts);

        // Check permissions
        if (! $can_publish) {
            return $post_id;
        }

        update_post_meta($post->ID, '_depublish_enable', empty($_POST['depublish_enable']) ? '0' : '1');

        // Get date fields
        $jj = isset($_POST['dep_jj']) ? zeroise((int) $_POST['dep_jj'], 2) : ''; // Day
        $mm = isset($_POST['dep_mm']) ? zeroise((int) $_POST['dep_mm'], 2) : ''; // Month
        $aa = isset($_POST['dep_aa']) ? (int) $_POST['dep_aa'] : ''; // Year
        $hh = isset($_POST['dep_hh']) ? zeroise((int) $_POST['dep_hh'], 2) : ''; // Hour
        $mn = isset($_POST['dep_mn']) ? zeroise((int) $_POST['dep_mn'], 2) : ''; // Minutes
        $ss = isset($_POST['dep_ss']) ? zeroise((int) $_POST['dep_ss'], 2) : ''; // Seconds

        // Validate date
        $date_str = $aa.'-'.$mm.'-'.$jj.' '.$hh.':'.$mn.':'.$ss;
        $timestamp = strtotime($date_str);
        $date_test = date('Y-m-d H:i:s', $timestamp);

        if ($date_str === $date_test) {

            // Save the date
            update_post_meta($post->ID, '_depublish_date', $date_test);
        }

        // Depublish post if depublish date in the past
        if ($timestamp < time()) {
            Post::depublish_post($post_id);
        }
    }

    /**
     * Renders HTML for post submitbox.
     *
     * @see wp-admin/includes/meta-boxes.php
     */
    static function render($post)
    {

        $post_type = $post->post_type;
        $post_type_object = get_post_type_object($post_type);
        $can_publish = current_user_can($post_type_object->cap->publish_posts);
        $dep_enabled = get_post_meta($post->ID, '_depublish_enable', true) === '1';
        $dep_date = get_post_meta($post->ID, '_depublish_date', true);

        if ($can_publish) {
            $datef = __('M j, Y @ H:i');
            $stamp = __('Depublish: <b>%1$s</b>');

            if (0 != $post->ID && $dep_enabled && ! empty($dep_date)) {
                $date = date_i18n($datef, strtotime($dep_date));
            } else { // draft (no saves, and thus no date specified)
                $date = 'never';
            }

            wp_nonce_field('depublish_save_'.$post->ID, '_depublish_nonce');
            ?>

            <div class="misc-pub-section curtime misc-pub-curtime">
                <span id="timestamp_depublish">
                    <?php printf($stamp, $date); ?>
                </span>
                <a href="#edit_timestamp_depublish" class="edit-timestamp-depublish hide-if-no-js" role="button">
                    <span aria-hidden="true"><?php _e('Edit'); ?></span>
                    <span class="screen-reader-text"><?php _e('Edit date and time'); ?></span>
                </a>
                <fieldset id="timestampdiv_depublish" class="hide-if-js">
                    <p>
                        <label for="depublish_enable"><input type="checkbox" name="depublish_enable" id="depublish_enable" value="1" <?php checked($dep_enabled); ?>>
                            Enable?
                        </label>
                    </p>
                    <legend class="screen-reader-text"><?php _e('Date and time'); ?></legend>
                    <?php self::_dateFields(); ?>
                </fieldset>
            </div>

            <?php
        }
    }

    /**
     * Renders HTML form to enter a datetime.
     */
    private static function _dateFields()
    {

        global $wp_locale;

        $post = get_post();
        $depublish_time = get_post_meta($post->ID, '_depublish_date', 'true');

        $edit = ! (in_array($post->post_status, ['draft', 'pending']) && (! $post->post_date_gmt || '0000-00-00 00:00:00' == $post->post_date_gmt)) && ! empty($depublish_time);

        $time_adj = current_time('timestamp');
        $jj = ($edit) ? mysql2date('d', $depublish_time, false) : gmdate('d', $time_adj);
        $mm = ($edit) ? mysql2date('m', $depublish_time, false) : gmdate('m', $time_adj);
        $aa = ($edit) ? mysql2date('Y', $depublish_time, false) : gmdate('Y', $time_adj);
        $hh = ($edit) ? mysql2date('H', $depublish_time, false) : gmdate('H', $time_adj);
        $mn = ($edit) ? mysql2date('i', $depublish_time, false) : gmdate('i', $time_adj);
        $ss = ($edit) ? mysql2date('s', $depublish_time, false) : gmdate('s', $time_adj);

        $cur_jj = gmdate('d', $time_adj);
        $cur_mm = gmdate('m', $time_adj);
        $cur_aa = gmdate('Y', $time_adj);
        $cur_hh = gmdate('H', $time_adj);
        $cur_mn = gmdate('i', $time_adj);

        $month = '<label><span class="screen-reader-text">'.__('Month').'</span><select id="dep_mm" name="dep_mm">';

        for ($i = 1; $i < 13; $i = $i + 1) {
            $monthnum = zeroise($i, 2);
            $monthtext = $wp_locale->get_month_abbrev($wp_locale->get_month($i));
            $month .= "\t\t\t".'<option value="'.$monthnum.'" data-text="'.$monthtext.'" '.selected($monthnum, $mm, false).'>';
            /* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
            $month .= sprintf(__('%1$s-%2$s'), $monthnum, $monthtext)."</option>\n";
        }
        $month .= ' </select></label> ';

        $day = '<label><span class="screen-reader-text"> '.__('Day').' </span><input type="text" id="dep_jj" name="dep_jj" value="'.$jj.'" size="2" maxlength="2" autocomplete="off" /></label> ';
        $year = '<label><span class="screen-reader-text"> '.__('Year').' </span><input type="text" id="dep_aa" name="dep_aa" value="'.$aa.'" size="4" maxlength="4" autocomplete="off" /></label> ';
        $hour = '<label><span class="screen-reader-text"> '.__('Hour').' </span><input type="text" id="dep_hh" name="dep_hh" value="'.$hh.'" size="2" maxlength="2" autocomplete="off" /></label> ';
        $minute = '<label><span class="screen-reader-text"> '.__('Minute').' </span ><input type="text" id="dep_mn" name="dep_mn" value="'.$mn.'" size="2" maxlength="2" autocomplete="off" /></label> ';

        echo '<div class="timestamp-wrap-depublish" > ';
        /* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
        printf(__('%1$s %2$s, %3$s @ %4$s:%5$s'), $month, $day, $year, $hour, $minute);

        echo ' </div ><input type="hidden" id="dep_ss" name="dep_ss" value="'.$ss.'" />';

        $map = [
            'mm' => [$mm, $cur_mm],
            'jj' => [$jj, $cur_jj],
            'aa' => [$aa, $cur_aa],
            'hh' => [$hh, $cur_hh],
            'mn' => [$mn, $cur_mn],
        ];

        foreach ($map as $timeunit => $value) {
            list($unit, $curr) = $value;

            echo '<input type="hidden" id="hidden_dep_'.$timeunit.'" name="hidden_dep_'.$timeunit.'" value="'.$unit.'" />'."\n";
            $cur_timeunit = 'cur_dep_'.$timeunit;
            echo '<input type="hidden" id="'.$cur_timeunit.'" name="'.$cur_timeunit.'" value="'.$curr.'" />'."\n";
        }
        ?>

        <p>
            <a href="#edit_timestamp_depublish" class="save-timestamp hide-if-no-js button"><?php _e('OK'); ?></a>
            <a href="#edit_timestamp_depublish" class="cancel-timestamp hide-if-no-js button-cancel"><?php _e('Cancel'); ?></a>
        </p>

        <?php
    }
}
