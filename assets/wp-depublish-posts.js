jQuery(function ($) {

    var stamp, updateText, $timestampdiv = $('#timestampdiv_depublish');

    if ($('#submitdiv').length) {
        stamp = $('#timestamp_depublish').html();

        /**
         * Make sure all labels represent the current settings.
         *
         * @returns {boolean} False when an invalid timestamp has been selected, otherwise True.
         */
        updateText = function () {

            if (!$timestampdiv.length)
                return true;

            var attemptedDate, originalDate, currentDate, publishOn,
                mm = $('#dep_mm').val(), jj = $('#dep_jj').val(), aa = $('#dep_aa').val(), hh = $('#dep_hh').val(), mn = $('#dep_mn').val();

            attemptedDate = new Date(aa, mm - 1, jj, hh, mn);
            originalDate = new Date($('#hidden_dep_aa').val(), $('#hidden_dep_mm').val() - 1, $('#hidden_dep_jj').val(), $('#hidden_dep_hh').val(), $('#hidden_dep_mn').val());

            // Catch unexpected date problems.
            if (attemptedDate.getFullYear() != aa || (1 + attemptedDate.getMonth()) != mm || attemptedDate.getDate() != jj || attemptedDate.getMinutes() != mn) {
                $timestampdiv.find('.timestamp-wrap-depublish').addClass('form-invalid');
                return false;
            } else {
                $timestampdiv.find('.timestamp-wrap-depublish').removeClass('form-invalid');
            }

            publishOn = 'Depublish: ';

            if ($("#depublish_enable").prop('checked') !== true) {
                $('#timestamp_depublish').html('\n' + publishOn + '<b>never</b>');
            } else {
                $('#timestamp_depublish').html(
                    '\n' + publishOn + ' <b>' +
                    postL10n.dateFormat
                        .replace('%1$s', $('option[value="' + mm + '"]', '#dep_mm').attr('data-text'))
                        .replace('%2$s', parseInt(jj, 10))
                        .replace('%3$s', aa)
                        .replace('%4$s', ( '00' + hh ).slice(-2))
                        .replace('%5$s', ( '00' + mn ).slice(-2)) +
                    '</b> '
                );
            }


            return true;
        };

        // Edit publish time click.
        $timestampdiv.siblings('a.edit-timestamp-depublish').click(function (event) {
            if ($timestampdiv.is(':hidden')) {
                $timestampdiv.slideDown('fast', function () {
                    $('input, select', $timestampdiv.find('.timestamp-wrap-depublish')).first().focus();
                });
                $(this).hide();
            }
            event.preventDefault();
        });

        // Cancel editing the publish time and hide the settings.
        $timestampdiv.find('.cancel-timestamp').click(function (event) {
            $timestampdiv.slideUp('fast').siblings('a.edit-timestamp-depublish').show().focus();
            $('#dep_mm').val($('#hidden_mm').val());
            $('#dep_jj').val($('#hidden_jj').val());
            $('#dep_aa').val($('#hidden_aa').val());
            $('#dep_hh').val($('#hidden_hh').val());
            $('#dep_mn').val($('#hidden_mn').val());
            updateText();
            event.preventDefault();
        });

        // Save the changed timestamp.
        $timestampdiv.find('.save-timestamp').click(function (event) { // crazyhorse - multiple ok cancels
            if (updateText()) {
                $timestampdiv.slideUp('fast');
                $timestampdiv.siblings('a.edit-timestamp-depublish').show().focus();
            }
            event.preventDefault();
        });

        // Cancel submit when an invalid timestamp has been selected.
        $('#post').on('submit', function (event) {
            if (!updateText()) {
                event.preventDefault();
                $timestampdiv.show();

                if (wp.autosave) {
                    wp.autosave.enableButtons();
                }

                $('#publishing-action .spinner').removeClass('is-active');
            }
        });

    }

});
