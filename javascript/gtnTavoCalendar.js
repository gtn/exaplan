// add custom dayClick event 'calendar-select-after'
/*
(function(dayClick) {
    TavoCalendar.prototype.dayClick = function() {
        var result = dayClick.apply(this, arguments);
        // console.log('gtnTavoCalendar.js:5');console.log(this);// !!!!!!!!!! delete it
        this.elements.wrapper.dispatchEvent(new Event('calendar-select-after'))
        return result;
    };
})(TavoCalendar.prototype.dayClick);
*/



TavoCalendar.prototype.addMetaData = function(date, metaData) {

    var that = this;

    // var format = 'DD.MM.YYYY';
    var format = 'YYYY-MM-DD';
    var calendar_moment = moment(date, format);
    var days_in_month = calendar_moment.daysInMonth();

    var moment_copy = calendar_moment.clone();
    moment_copy.startOf('month');

    var elementIndex = 0;
    var monthOffset = moment_copy.isoWeekday() % (7 + calendar_moment.localeData().firstDayOfWeek());
    if (monthOffset > 0) {
        elementIndex = elementIndex + monthOffset - 1;
    }

    var year = calendar_moment.year();
    var month = calendar_moment.month() + 1; // why????
    var calendarElement = this.elements.wrapper;

    if (typeof this.state.date === 'undefined') {
        return false;
    }

    // var calendarStateMoment = moment(this.state.date, format);
    var calendarStateYear = this.getFocusYear();
    var calendarStateMonth = this.getFocusMonth();
    if (calendarStateYear != year || calendarStateMonth != month) {
        return false;
    }

    // go through ALL calendar elements step by step and add metadata
    for (var d = 1; d <= days_in_month; d++) {
        var stepDate = moment_copy.format(format);
        if (stepDate == date) {
            var dayWrapper = $(calendarElement).find('.tavo-calendar__day').eq(elementIndex);
            if (typeof metaData !== 'undefined') {
                if (metaData.usedItems > 0) {
                    dayWrapper.attr('data-itemsUsed', metaData.usedItems);
                    var usedItemsMarker = $('<span class="exaplan-usedItems">' + metaData.usedItems + '</span>');
                    dayWrapper.find('span.tavo-calendar__day-inner').append(usedItemsMarker);
                    // disable all EVENTS. we need it for handle own events
                    dayWrapper.off();
                    // console.log('gtnTavoCalendar.js:46');console.log($(dayWrapper.get(0).getElementsByClassName('exaplan-usedItems')));// !!!!!!!!!! delete it
                    // $(dayWrapper.get(0).getElementsByClassName('exaplan-usedItems')).on('click', function(ev) {
                    //     console.log('gtnTavoCalendar.js:42');console.log(ev.currentTarget);// !!!!!!!!!! delete it
                    // });

                }
            }
            break; // date found!
        }
        moment_copy.add(1, "d");
        elementIndex = elementIndex + 1;
    }

    return true;
};

TavoCalendar.prototype.markSelectedModulePart = function(modulepartId, calendarData) {
    var that = this;

    if (typeof this.state.date === 'undefined') {
        return false;
    }
    var format = 'YYYY-MM-DD';
    var calendar_moment = moment(this.state.date, format);
    var days_in_month = calendar_moment.daysInMonth();
    var moment_copy = calendar_moment.clone();
    moment_copy.startOf('month');

    var elementIndex = 0;
    var monthOffset = moment_copy.isoWeekday() % (7 + calendar_moment.localeData().firstDayOfWeek());
    if (monthOffset > 0) {
        elementIndex = elementIndex + monthOffset - 1;
    }
    
    var calendarElement = this.elements.wrapper;

    // go through ALL calendar elements step by step and add metadata
    for (var d = 1; d <= days_in_month; d++) {
        var stepDate = moment_copy.format(format);
        var dayWrapper = $(calendarElement).find('.tavo-calendar__day').eq(elementIndex);
        // at first remove marker for all days
        dayWrapper.removeClass('usedForModulepart');
        if (modulepartId > 0 && calendarData.selectedDates.length) {
            // go through all selected days and check on modulepartid
            calendarData.selectedDates.forEach((date) => {
                if (stepDate == date.date && typeof date.moduleparts != 'undefined' && date.moduleparts.indexOf(modulepartId) != -1) {
                    // mark this day
                    console.log('gtnTavoCalendar.js:93');console.log('day ' + moment_copy.format(format) + ' marked');// !!!!!!!!!! delete it
                    dayWrapper.addClass('usedForModulepart');
                    // TODO: different marks? 'fixed / desired'
                }
            });
        }
        moment_copy.add(1, "d");
        elementIndex = elementIndex + 1;
    }

    return true;
}


// function to ignore click on the day.
// every clicking on the day will call tavo-calendar events. Sometime we need to return this sate.
// but trigger 'click' is not suitable - use this function
// (tavo-calendar recreates calendar html after every event)
TavoCalendar.prototype.returnDay = function(selectedDate) {
    var that = this;

    // var format = 'DD.MM.YYYY';
    var format = 'YYYY-MM-DD';
    var calendar_moment = moment(this.state.date, format);
    var days_in_month = calendar_moment.daysInMonth();

    var moment_copy = calendar_moment.clone();
    moment_copy.startOf('month');

    var elementIndex = 0;
    var monthOffset = moment_copy.isoWeekday() % (7 + calendar_moment.localeData().firstDayOfWeek());
    if (monthOffset > 0) {
        elementIndex = elementIndex + monthOffset - 1;
    }

    console.log('gtnTavoCalendar.js:126');console.log(this.state.selected);// !!!!!!!!!! delete it

    if (this.state.selected.indexOf(selectedDate) != -1) {
        // if no selected after click - we need to add this date again
        this.state.selected.push(selectedDate);
        var actionSelectDay = true;
    } else {
        // if exists after click - we need to remove it
        var index = this.state.selected.indexOf(selectedDate);
        if (index !== -1) {
            this.state.selected.splice(index, 1);
        }
        var actionSelectDay = false;
    }

    var calendarElement = this.elements.wrapper;

    // go through ALL calendar elements step by step and work with html of the selected day
    for (var d = 1; d <= days_in_month; d++) {
        var stepDate = moment_copy.format(format);
        if (stepDate == selectedDate) {
            var dayWrapper = $(calendarElement).find('.tavo-calendar__day').eq(elementIndex);
            console.log('gtnTavoCalendar.js:148');console.log(dayWrapper);// !!!!!!!!!! delete it
            // if (actionSelectDay) {
                dayWrapper.addClass('tavo-calendar__day_select');
            // } else {
            //     dayWrapper.removeClass('tavo-calendar__day_select');
            // }
            break; // date found!
        }
        moment_copy.add(1, "d");
        elementIndex = elementIndex + 1;
    }

    return true;
}
