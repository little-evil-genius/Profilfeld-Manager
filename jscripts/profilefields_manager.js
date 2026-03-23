$(function() {
    function profilefieldsManagerGetFieldValues(fid) {
        var fieldName = 'profile_fields[fid' + fid + ']';
        var values = [];

        var $select = $('[name="' + fieldName + '"]');
        if ($select.length) {
            if ($select.prop('multiple')) {
                values = $select.val() || [];
            } else {
                values = [$select.val()];
            }
            return values;
        }

        var $radio = $('input[name="' + fieldName + '"]:checked');
        if ($radio.length) {
            return [$radio.val()];
        }

        var $checkboxes = $('input[name="' + fieldName + '[]"]:checked');
        if ($checkboxes.length) {
            $checkboxes.each(function() {
                values.push($(this).val());
            });
        }

        return values;
    }

    function profilefieldsManagerGetAllowedValues(rawValue) {
        if (!rawValue) {
            return [];
        }

        return String(rawValue)
            .split(';')
            .map(function(value) {
                return $.trim(value);
            })
            .filter(function(value) {
                return value !== '';
            });
    }

    function profilefieldsManagerFieldShouldBeVisible(dependenceFid, dependenceContent) {
        if (!dependenceFid) {
            return true;
        }

        var currentValues = profilefieldsManagerGetFieldValues(dependenceFid);
        var allowedValues = profilefieldsManagerGetAllowedValues(dependenceContent);
        var visible = false;

        $.each(currentValues, function(index, currentValue) {
            currentValue = $.trim(String(currentValue));

            if ($.inArray(currentValue, allowedValues) !== -1) {
                visible = true;
                return false;
            }
        });

        return visible;
    }

    function profilefieldsManagerToggleDependentFields() {
        $('.profilefieldsManager_field_row_label').each(function() {
            var fid = parseInt($(this).data('fid'), 10) || 0;
            var dependenceFid = parseInt($(this).data('dependencefid'), 10) || 0;
            var dependenceContent = $(this).data('dependencecontent') || '';

            var $labelRow = $('#profilefieldsManager_field_label_' + fid);
            var $inputRow = $('#profilefieldsManager_field_input_' + fid);

            if (profilefieldsManagerFieldShouldBeVisible(dependenceFid, dependenceContent)) {
                $labelRow.show();
                $inputRow.show();
            } else {
                $labelRow.hide();
                $inputRow.hide();
            }
        });
    }

    profilefieldsManagerToggleDependentFields();

    $(document).on('change', 'select, input[type="radio"], input[type="checkbox"]', function() {
        profilefieldsManagerToggleDependentFields();
    });
});