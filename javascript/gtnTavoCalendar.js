// add custom code into dayClick: event 'calendar-select-after' and other
(function(dayClick) {
    TavoCalendar.prototype.dayClick = function() {

        lastCalendarSelectedDate = arguments[0];
        // YYYY-MM-DD
        var format = 'YYYY-MM-DD';
        var calendar_moment = new moment(lastCalendarSelectedDate, format).startOf('day');
        var curr_moment = new moment().startOf('day');

        // blur calendars only if selected date is not in the past
        if (calendar_moment >= curr_moment) {
            this.blurCalendar();
        }

        lastCalendarSelectedDay = $(arguments[1]).clone().find(':not(.tavo-calendar__day-inner)').remove().end().text(); // not the best way, but works

        this.elements.wrapper.dispatchEvent(new Event('calendar-select-before'))

        var result = dayClick.apply(this, arguments);

        // var result = this.gtnDayClick(arguments[0], arguments[1]); // day, cal_el
        this.elements.wrapper.dispatchEvent(new Event('calendar-select-after'))

        return result;
    };
})(TavoCalendar.prototype.dayClick);

// update all calendars after change month of single calendar
(function(nextMonth) {
    TavoCalendar.prototype.nextMonth = function(e) {

        var currentElementId = $(this.elements.calendar_code).closest('.calendar-month-item.tavo-calendar').attr('id');
        // start original event
        var result = nextMonth.apply(this, arguments);

        var repeat = arguments[1]; // for stop looping
        if (repeat != 'no_repeat') {
            // update all other calendars
            allCalendars.forEach((calendarInstance) => {
                var instanceId = $(calendarInstance.elements.calendar_code).closest('.calendar-month-item.tavo-calendar').attr('id');
                if (currentElementId != instanceId) {
                    calendarInstance.nextMonth(e, 'no_repeat');
                }
            });
            updateAllCalendarMetadata();
        }

    };
})(TavoCalendar.prototype.nextMonth);
(function(prevMonth) {
    TavoCalendar.prototype.prevMonth = function(e) {

        var currentElementId = $(this.elements.calendar_code).closest('.calendar-month-item.tavo-calendar').attr('id');
        // start original event
        var result = prevMonth.apply(this, arguments);

        var repeat = arguments[1]; // for stop looping
        if (repeat != 'no_repeat') {
            // update all other calendars
            allCalendars.forEach((calendarInstance) => {
                var instanceId = $(calendarInstance.elements.calendar_code).closest('.calendar-month-item.tavo-calendar').attr('id');
                if (currentElementId != instanceId) {
                    calendarInstance.prevMonth(e, 'no_repeat');
                }
            });
            updateAllCalendarMetadata();
        }

    };
})(TavoCalendar.prototype.prevMonth);


TavoCalendar.prototype.blurCalendar = function() {
    // blur calendar only selected day is not in the past
    // YYYY-MM-DD
    var format = 'YYYY-MM-DD';
    var calendar_moment = new moment(lastCalendarSelectedDate, format).startOf('day');
    var curr_moment = new moment().startOf('day');
    if (calendar_moment >= curr_moment) {
        var calendarElement = this.elements.wrapper;
        $(calendarElement).addClass('exaplan-celendar-blured');
        var spinner = $('<div class="exaplan_loader"></div>');
        $(calendarElement).before(spinner);
    }

}
TavoCalendar.prototype.unBlurCalendar = function() {
    var calendarElement = this.elements.wrapper;
    $(calendarElement).removeClass('exaplan-celendar-blured');
    $(calendarElement).siblings('.exaplan_loader').remove();
}

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
                // console.log('gtnTavoCalendar.js:53');console.log(metaData);// !!!!!!!!!! delete it
                if (metaData.usedItems > 0) {
                    dayWrapper.attr('data-itemsUsed', metaData.usedItems);
                    var usedItemsMarker = $('<span class="exaplan-usedItems">' + metaData.usedItems + '</span>');
                    dayWrapper.find('span.tavo-calendar__day-inner').append(usedItemsMarker);
                    // disable all EVENTS. we need it for handle own events
                    dayWrapper.off();
                }
                if (typeof metaData.usersCount === 'object') {
                    var relTypes = ['desired', 'fixed', 'blocked'];
                    relTypes.forEach(function (currentType) {
                        if (metaData.usersCount.hasOwnProperty(currentType)) {
                            if (metaData.usersCount[currentType] > 0 || metaData.usersCount[currentType] === 0) {
                                dayWrapper.attr('data-has' + currentType + 'Students', 1);
                                var usedItemsMarker = $('<span class="exaplan-relatedUsers-'+currentType+'">' + metaData.usersCount[currentType] + '</span>');
                                dayWrapper.find('span.tavo-calendar__day-inner').append(usedItemsMarker);
                                // disable all EVENTS. we need it for handle own events
                                dayWrapper.off();
                            }
                        };
                    });
                }
                if (!isExaplanAdmin) { // TODO: I think here is needed some additional condition in the future
                    if (metaData.middayType) {
                        dayWrapper.attr('data-middayType', metaData.middayType);
                        var dateTypeMarker = $('<span class="exaplan-middayType">' + metaData.middayType + '</span>');
                        dayWrapper.find('span.tavo-calendar__day-inner').append(dateTypeMarker);
                        // disable all EVENTS. we need it for handle own events
                        dayWrapper.off();
                    }
                }
                if (metaData.desired) {
                    dayWrapper.addClass('exaplan-calendar-date-desired');
                }
                if (metaData.fixed) {
                    dayWrapper.addClass('exaplan-calendar-date-fixed');
                }
                if (metaData.blocked) {
                    dayWrapper.addClass('exaplan-calendar-date-blocked');
                }
                if (metaData.markHover) {
                    dayWrapper.addClass('exaplan-calendar-date-marked');
                }
                if (metaData.unMarkHover) {
                    dayWrapper.removeClass('exaplan-calendar-date-marked');
                }
                if (metaData.moduleparts && typeof currentModulepartId != 'undefined' && currentModulepartId > 0) {
                    if (metaData.moduleparts.indexOf(currentModulepartId) == -1) {
                        // add class to mute this day
                        dayWrapper.addClass('exaplan-calendar-date-anotherModulepart');
                    }
                }
            }
            break; // date found!
        }
        moment_copy.add(1, "d");
        elementIndex = elementIndex + 1;
    }

    this.elements.wrapper.dispatchEvent(new Event('calendar-metadata-finished'))

    this.unBlurCalendar();

    return true;
};

// deprecated?
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


// Own DayClick function!
// deprecated? Look on the custom code in the start of this file: ...(function(dayClick) {   TavoCalendar.prototype.dayClick = function() {
TavoCalendar.prototype.gtnDayClick = function(date, day_el) {
    lastCalendarSelectedDate = date;
    lastCalendarSelectedDay = $(day_el).clone().find(':not(.tavo-calendar__day-inner)').remove().end().text(); // not the best way, but works
        
    if (this.config.frozen) return;

    //Day lock
    if (day_el.classList.contains('tavo-calendar__day_lock')) return;

    if (this.config.range_select) {
        if ((!this.state.date_start && !this.state.date_end) || (this.state.date_start && this.state.date_end)) {
            this.state.date_start = date;
            this.state.date_end = null;
        }  else {
            if (!this.state.date_end) {
                this.state.date_end = date
            }

            this.state.lock = true;
            this.elements.wrapper.dispatchEvent(new Event('calendar-range'))
        }
    } else {
        if (this.config.multi_select) {
            if (this.state.selected.indexOf(date) > -1) {
                this.state.selected = this.state.selected.filter(date_selected => date_selected != date);
            } else {
                this.state.selected.push(date);
            }
        } else {
            this.state.selected = [date];
        }

        this.elements.wrapper.dispatchEvent(new Event('calendar-select'))
    }

    this.destroy()
    this.mount()
    this.bindEvents();

}
