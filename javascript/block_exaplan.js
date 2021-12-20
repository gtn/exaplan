// for using in global scope
var allCalendars = [];
var exaplanCalendarDateFormat = 'YYYY-MM-DD';
var lastCalendarSelectedDate = '';
var lastCalendarSelectedDay = '';

$(function () {

// Tavo Calendar

    // calendar options
    var lastPossiobbleDay = moment().add(2, 'M').endOf("month").format(exaplanCalendarDateFormat);

    var calendar_default_options = {
        format: exaplanCalendarDateFormat,
        range_select: false,
        future_select: lastPossiobbleDay,
        multi_select: true,
        locale: 'de'
    }

    var allMonthElements = $('#block_exaplan_dashboard_calendar .calendar-month-item');
    allMonthElements.each(function (i, calMonth) {
        var month_options = Object.assign({}, calendar_default_options);
        if (i > 0) {
            // every next calendar shows next month
            month_options.date = moment().add(i, 'M').startOf("month").format(exaplanCalendarDateFormat);
        } else {
            month_options.date = moment().format(exaplanCalendarDateFormat);
        }
        var calendar_month = new TavoCalendar(calMonth, month_options);
        allCalendars.push(calendar_month);
        calMonth.addEventListener('calendar-select-before', (ev) => {
            calendar_month.blurCalendar();
        });
        calMonth.addEventListener('calendar-select', (ev) => {
            return selectedDateEvent(ev, calendar_month);
        });
        /*calMonth.addEventListener('calendar-select-after', (ev) => {
            return selectedDateEvent(ev, calendar_month);
        });*/
        calMonth.addEventListener('calendar-change', (ev) => {
            updateAllCalendarMetadata();
        });
        calMonth.addEventListener('calendar-range', (ev) => {
            updateAllCalendarMetadata();
        });
        calMonth.addEventListener('calendar-reset', (ev) => {
            updateAllCalendarMetadata();
        });
        /*calMonth.addEventListener('calendar-metadata-finished', (ev) => {

        });*/
    });


});

function selectedDateEvent(calEvent, monthCalendar) {
    if (isExaplanAdmin) {
        modulepartInfoByDateAjax(calEvent, monthCalendar);
    } else {
        selectedDateSendAjax(calEvent, monthCalendar);
    }
    return false;
}

function selectedDateSendAjax(calEvent, monthCalendar) {
    calEvent.preventDefault();
    // send request only if modulepart (or existing date) is selected
    var selectedDateEl = $('.exaplan-selectable-date[data-dateselected="1"]');
    var selectedModulepart = $('.exaplan-selectable-modulepart[data-modulepartselected="1"]');
    if (selectedDateEl.length || selectedModulepart.length) {
        if ($('input[name="midday_type"]:checked').length) {
            var middayType = $('input[name="midday_type"]:checked').val();
        } else {
            var middayType = 3; // all day
        }
        var selectedModulepartId = selectedModulepart.attr('data-modulepartId');
        if (!selectedModulepartId) {
            selectedModulepartId = selectedDateEl.attr('data-modulepartId');
        }

        var selectedDate = lastCalendarSelectedDate;

        // send request
        var ajaxUrl = calendarAjaxUrl;
        var data = {
            modulepartId: selectedModulepartId,
            // dateId: selectedDateId,
            date: selectedDate,
            middayType: middayType
        }
        $.ajax({
            method: 'post',
            data: data,
            dataType: 'json',
            url: ajaxUrl,
            cache: false
        }).done(function (result) {
            console.log('block_exaplan.js:100');console.log(result);// !!!!!!!!!! delete it
            calendarData = JSON.parse(result);
            updateCalendarSelectedDates();
            updateAllCalendarMetadata();
        }).fail(function () {
            updateAllCalendarMetadata();
            console.log('Something wrong in Ajax!! 1634564740109')
        });
    } else {
        // TODO: return metadata
        updateCalendarSelectedDates();
        updateAllCalendarMetadata();
    }
}

function modulepartInfoByDateAjax(calEvent, monthCalendar) {
    calEvent.preventDefault();
    
    var selectedDate =  lastCalendarSelectedDate;

    // send request
    var ajaxUrl = calendarAjaxUrl;
    var data = {
        date: selectedDate,
    }
    $.ajax({
        method: 'post',
        data: data,
        url: ajaxUrl,
        cache: false
    }).done(function (result) {
        var resultData = JSON.parse(result);
        calendarData = JSON.parse(resultData.calendarData);
        updateCalendarSelectedDates();
        updateAllCalendarMetadata();
        $('#modulepart-date-data').html(resultData.htmlContent);
        monthCalendar.unBlurCalendar();
    }).fail(function () {
        console.log('Something wrong in Ajax!! 1635165804897')
    });
}


function updateCalendarSelectedDates() {
    if (typeof calendarData !== 'undefined') {
        // clear al selected data
        allCalendars.forEach((calendarInstance) => {
            calendarInstance.clearSelected();
        });
        // set selected dates
        if (calendarData.selectedDates.length) {
            calendarData.selectedDates.forEach((date) => {
                allCalendars.forEach((calendarInstance) => {
                    calendarInstance.addSelected(date.date);
                });
            });
        }
    }
}

function updateAllCalendarMetadata() {
    // add metadata
    // because it is recreated after every changing! Specific of tavoCalendar.js - it redesigns HTML of whole calendar after every event
    if (typeof calendarData !== 'undefined') {
        if (calendarData.selectedDates.length) {
            calendarData.selectedDates.forEach((date) => {
                allCalendars.forEach((calendarInstance) => {
                    // add only needed props to metaData
                    var metaData = {
                        // usedItems: date.usedItems,
                        desired: date.desired,
                        fixed: date.fixed,
                        blocked: date.blocked,
                        moduleparts: date.moduleparts,
                        middayType: date.middayType,
                    }
                    if (typeof isExaplanAdmin !== 'undefined' && isExaplanAdmin) {
                        metaData.usersCount = date.usersCount; // admin must see number of students for the day
                    }
                    calendarInstance.addMetaData(date.date, metaData);
                });
            });
        } else {
            // anycase hide loader!
            allCalendars.forEach((calendarInstance) => {
                calendarInstance.unBlurCalendar();
            });
        }
    }
}


$(function () {

    // initialize default calendar data
    updateCalendarSelectedDates();
    // update metadata in calendars
    updateAllCalendarMetadata();

    // press 'Save' button
    $('body').on('click', '.save_calendar-data', function (e) {
        e.preventDefault();
        // get selected dates from ALL calendars
        var selectedDates = [];
        allCalendars.forEach((calendarInstance) => {
            var calSelected = calendarInstance.getSelected();
            selectedDates = selectedDates.concat(calSelected);
        });
        // filter for unique:
        var newArray = [];
        $.each(selectedDates, function(i, e) {
            if ($.inArray(e, newArray) == -1) newArray.push(e);
        });
        selectedDates = newArray;
        // add data to the form
        var selectedDatesJson = JSON.stringify(selectedDates);
        var form = $(this).closest('form');
        form.append('<input name="calSelectedDates" type="hidden" >');
        form.find('input[name="calSelectedDates"]').val(selectedDatesJson)
        // type of midday
        if ($('input.midday-type-radio:checked').length) {
            var midday = $('input.midday-type-radio:checked').val();
        } else {
            var midday = 3;
        }
        form.append('<input name="calMidday" type="hidden" >');
        form.find('input[name="calMidday"]').val(midday)
        form.submit();
    })

    // select date on "module part" instance
    $('.exaplan-selectable-date').on('clickDISABLED', function (e) {
        e.preventDefault();
        var currentState = $(this).attr('data-dateSelected');
        // unselect all prev selected dates:
        $('.exaplan-selectable-date').removeAttr('data-dateSelected');
        var selectedModulepart = 0;
        // select if non-selected (if it was selected - nothing to do)
        if (!currentState || typeof currentState === 'undefined') {
            $(this).attr('data-dateSelected', 1);
            var selectedModulepart = $(this).attr('data-modulepartid')
        }
        // markCalendarSelectedModulepart(selectedModulepart);
    })
    // select modulepart on "module part" instance
    $('.exaplan-selectable-modulepart').on('clickDISABLED', function (e) {
        e.preventDefault();
        var currentState = $(this).attr('data-modulepartselected');
        // unselect all prev selected parts:
        $('.exaplan-selectable-modulepart').removeAttr('data-modulepartselected');
        var selectedModulepart = 0;
        // select if non-selected (if it was selected - nothing to do)
        if (!currentState || typeof currentState === 'undefined') {
            $(this).attr('data-modulepartselected', 1);
            // mark selected dates in calendar
            var selectedModulepart = $(this).attr('data-modulepartid')
        }
        // markCalendarSelectedModulepart(selectedModulepart);
    })

    if ($('.tooltipster').length) {
        $('.tooltipster').tooltipster({
            theme: ['tooltipster-light', 'tooltipster-exaplan']
        });
    }

    // bulk action selected
    $('body').on('change', '[name="bulk_function"]', function (e) {
        var selectedAction = $(this).val();
        if (selectedAction == 'sendMessage') {
            $('#bulkMessage').show();
        } else {
            $('#bulkMessage').hide();
        }
    });

    // select all students in the sublist
    $('body').on('click', '.selectAllicon', function (e) {
        var currState = $(this).attr('data-selected');
        var currSubList = $(this).attr('data-listId');
        if (typeof currState === 'undefined' || currState == 0) {
            $('tr[data-listId="'+currSubList+'"] .fixedPuserCheckbox:checkbox').prop('checked', true);
            $(this).attr('data-selected', 1);
        } else {
            $('tr[data-listId="'+currSubList+'"] .fixedPuserCheckbox:checkbox').prop('checked', false);
            $(this).attr('data-selected', 0);
        }
    });

});


