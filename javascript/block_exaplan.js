// for using in global scope
var allCalendars = [];

$(function () {


    /*
    // jsCalendar
    var dateFormat = 'DD/MM/YYYY';

    var firstPossiobbleDay =  moment().format(dateFormat);
    var lastPossiobbleDay =  moment().add(2, 'M').endOf("month").format(dateFormat);

    const dashboard_options = {
        firstDayOfTheWeek: "2",
        language: 'de',
        "data-min": firstPossiobbleDay,
        "data-max": lastPossiobbleDay,
    }

    const month1_options = Object.assign({}, dashboard_options);
    var firstDate = moment().format(dateFormat);
    // next month
    var month2_options = Object.assign({}, dashboard_options);
    month2_options
    var nextMonthDate = moment().add(1, 'M').startOf("month").format(dateFormat);

    // Create the calendar
    // jsCalendar.new('#block_exaplan_dashboard_calendar #month1');
    var calendar1 = '#month1';
    console.log('dashboard.js:24');console.log(month1_options);// !!!!!!!!!! delete it
    jsCalendar.new(calendar1, firstDate, month1_options);
    var calendar2 = '#month2';
    jsCalendar.new(calendar2, nextMonthDate, month2_options);*/


// Tavo Calendar

    // var dateFormat = 'DD.MM.YYYY';
    var dateFormat = 'YYYY-MM-DD';

// dashborad calendar options
    var lastPossiobbleDay = moment().add(2, 'M').endOf("month").format(dateFormat);

    var dashboard_options = {
        format: dateFormat,
        range_select: false,
        future_select: lastPossiobbleDay,
        multi_select: true,
        locale: 'de'
    }

    var allMonthElements = $('#block_exaplan_dashboard_calendar .calendar-month-item');
    allMonthElements.each(function (i, calMonth) {
        var month_options = Object.assign({}, dashboard_options);
        if (i > 0) {
            // every next calendar shows next month
            month_options.date = moment().add(i, 'M').startOf("month").format(dateFormat);
        } else {
            month_options.date = moment().format(dateFormat);
        }
        var calendar_month = new TavoCalendar(calMonth, month_options);
        allCalendars.push(calendar_month);
        calMonth.addEventListener('calendar-select', (ev) => {
            // if clicked not day, but some marker - we need to return selected this day and stop next action
            // but the calendar already has clicked day - reset it
            if (!$(ev.explicitOriginalTarget).hasClass('tavo-calendar__day-inner')) {
                $(ev.explicitOriginalTarget).closest('tavo-calendar__day-inner').trigger('calendar-select'); // click again!
                updateAllCalendarMetadata();
                ev.stopPropagation();
                return false;
            }
            return selectedDateEvent(ev, calendar_month);
        });
        calMonth.addEventListener('calendar-change', (ev) => {
            updateAllCalendarMetadata();
        });
        calMonth.addEventListener('calendar-range', (ev) => {
            updateAllCalendarMetadata();
        });
        calMonth.addEventListener('calendar-reset', (ev) => {
            updateAllCalendarMetadata();
        });
    });

});

function selectedDateEvent(calEvent, monthCalendar) {
    // updateAllCalendarMetadata(); // if ajax will enable - disable this line
    selectedDateSendAjax(calEvent, monthCalendar);
    return false;
}

function selectedDateSendAjax(calEvent, monthCalendar) {
    calEvent.preventDefault();
    // var selectedDateEl = $('.exaplan-selectable-date[data-dateselected="1"]');
    // if (selectedDateEl.length) {
        if ($('input[name="midday_type"]:checked').length) {
            var middayType = $('input[name="midday_type"]:checked').val();
        } else {
            var middayType = 3; // all day
        }
        // var selectedDateId = selectedDateEl.attr('data-dateId');
        // var selectedDate = monthCalendar.getSelected(); // get ALL selected days
        // get selected date from html element
    // console.log('block_exaplan.js:98');console.log(calEvent.explicitOriginalTarget);// !!!!!!!!!! delete it
        var selectedDay = calEvent.explicitOriginalTarget.firstChild.textContent;
        // var selectedDate = selectedDay + '.' + monthCalendar.getFocusMonth() + '.' + monthCalendar.getFocusYear();
        var selectedDate =  monthCalendar.getFocusYear() + '-' + monthCalendar.getFocusMonth() + '-' + selectedDay;

        // send request
        var ajaxUrl = ajaxAddUserDateUrl;
        var data = {
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
            calendarData = JSON.parse(result);
            updateAllCalendarMetadata();
            console.log(result);
        }).fail(function () {
            updateAllCalendarMetadata();
            console.log('Something wrong in Ajax!! 1634564740109')
        });
    // }
}


function updateAllCalendarMetadata() {
    // add metadata
    // because it is recreated after every changing! Specific of tavoCalendar.js - it redesigns HTML of whole calendar after every event
    if (typeof calendarData !== 'undefined') {
        if (calendarData.selectedDates.length) {
            calendarData.selectedDates.forEach((date) => {
                allCalendars.forEach((calendarInstance) => {
                    var metaData = {
                        usedItems: date.usedItems
                    }
                    calendarInstance.addMetaData(date.date, metaData);
                });
            });
        }
    }
}

function showUsedItemsPopup(date) {
    console.log('block_exaplan.js:154');console.log('clicked marker of: ' +date);// !!!!!!!!!! delete it
}

$(function () {

    // initialize default calendar data
    if (typeof calendarData !== 'undefined') {
        console.log('dashboard.js:106');console.log(calendarData);// !!!!!!!!!! delete it
        // set default selected dates
        if (calendarData.selectedDates.length) {
            calendarData.selectedDates.forEach((date) => {
                allCalendars.forEach((calendarInstance) => {
                    calendarInstance.addSelected(date.date);
                });
            });
        }
    }
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
        // convert into format for PHP (JS uses DD.MM.YYYY, PHP uses YYYY-MM-DD right now)
       /* var newArray = [];
        $.each(selectedDates, function(i, e) {
            var tempDate = moment(e, 'DD.MM.YYYY');
            var newDate = tempDate.format('YYYY-MM-DD');
            newArray.push(newDate);
        });
        selectedDates = newArray;*/
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
    $('.exaplan-selectable-date').on('click', function (e) {
        e.preventDefault();
        var currentState = $(this).attr('data-dateSelected');
        // unselect all prev selected dates:
        $('.exaplan-selectable-date').removeAttr('data-dateSelected');
        // select if non-selected (if it was selected - nothing to do)
        if (!currentState || typeof currentState === 'undefined') {
            $(this).attr('data-dateSelected', 1);
        }
    })
});


