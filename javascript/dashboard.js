
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

var dateFormat = 'DD.MM.YYYY';

// dashborad calendar options
var lastPossiobbleDay =  moment().add(2, 'M').endOf("month").format(dateFormat);

var dashboard_options = {
    format: dateFormat,
    // range_select: true,
    future_select: lastPossiobbleDay,
    multi_select: true,
    locale: 'de'
}

var month1_options = Object.assign({}, dashboard_options);
month1_options.date = moment().format(dateFormat);
// next month
var month2_options = Object.assign({}, dashboard_options);
month2_options.date = moment().add(1, 'M').startOf("month").format(dateFormat);

var month1El = document.querySelector('#block_exaplan_dashboard_calendar #month1');
var exaplan_dashboard_calendar_month1 = new TavoCalendar(month1El, month1_options);
var month2El = document.querySelector('#block_exaplan_dashboard_calendar #month2');
var exaplan_dashboard_calendar_month2 = new TavoCalendar(month2El, month2_options);

var allCalendars = [
    exaplan_dashboard_calendar_month1,
    exaplan_dashboard_calendar_month2
]

// month1El.addEventListener('calendar-select', calendarDateSelected);
month1El.addEventListener('calendar-select', (ev) => {return selectedDateSendAjax(ev, exaplan_dashboard_calendar_month1)});
month2El.addEventListener('calendar-select', (ev) => {return selectedDateSendAjax(ev, exaplan_dashboard_calendar_month2)});


function selectedDateSendAjax(e, monthCalendar) {
    e.preventDefault();
    var selectedDateEl = $('.exaplan-selectable-date[data-dateselected="1"]');
    if (selectedDateEl.length) {
        var selectedDateId = selectedDateEl.attr('data-dateId');
        console.log('dashboard.js:69');console.log(monthCalendar);// !!!!!!!!!! delete it
        var selectedDate = monthCalendar.getSelected();
        // send request
        var ajaxUrl = ajaxAddUserDateUrl;
        var data = {
            dateId: selectedDateId,
            date: selectedDate
        }
        $.ajax({
            method: 'post',
            data: data,
            dataType: 'json',
            url: ajaxUrl,
            cache: false
        }).done(function (result) {
            console.log(result);
        }).fail(function () {
            console.log('Something wrong in Ajax!! 1634564740109')
        });
    }
}

/*document.querySelector('#block_exaplan_dashboard_calendar #month1').addEventListener('click', function() {
    exaplan_dashboard_calendar_month1.sync(exaplan_dashboard_calendar_month2)
})
document.querySelector('#block_exaplan_dashboard_calendar #month2').addEventListener('click', function() {
    exaplan_dashboard_calendar_month2.sync(exaplan_dashboard_calendar_month1)
})
*/

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


