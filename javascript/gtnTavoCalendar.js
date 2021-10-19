TavoCalendar.prototype.addMetaData = function(date, metaData) {

    var format = 'DD.MM.YYYY';
    var calendar_moment = moment(date, format);
    var days_in_month = calendar_moment.daysInMonth();

    var moment_copy = calendar_moment.clone();
    moment_copy.startOf('month');

    var elementIndex = 0;
    var monthOffset = moment_copy.isoWeekday() % (7 + calendar_moment.localeData().firstDayOfWeek());
    if (monthOffset > 0) {
        elementIndex = elementIndex + monthOffset - 1;
    }

    var year = moment_copy.year();
    var month = moment_copy.month();
    var calendarElement = this.elements.wrapper;

    if (typeof this.state.date === 'undefined') {
        return false;
    }

    var calendarStateMoment = moment(this.state.date, format);
    var calendarStateYear = calendarStateMoment.year();
    var calendarStateMonth = calendarStateMoment.month();
    if (calendarStateYear != year || calendarStateMonth != month) {
        return false;
    }

    // go through ALL calendar elements step by step and add metadata
    for (var d = 1; d <= days_in_month; d++) {
        var stepDate = moment_copy.format(format);
        if (stepDate == date) {
            var dayWrapper = $(calendarElement).find('.tavo-calendar__day').eq(elementIndex);
            if (typeof metaData !== 'undefined') {
                if (metaData.usedItems) {
                    dayWrapper.attr('data-itemsUsed', metaData.usedItems);
                    dayWrapper.find('span.tavo-calendar__day-inner').append('<span class="exaplan-usedItems">' + metaData.usedItems + '</span>');
                }
            }
            break; // date found!
        }
        moment_copy.add(1, "d");
        elementIndex = elementIndex + 1;
    }

    return true;
};

