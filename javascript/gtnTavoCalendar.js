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