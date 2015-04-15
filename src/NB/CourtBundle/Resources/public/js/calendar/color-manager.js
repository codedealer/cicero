/*jslint nomen:true*/
/*global define*/
define(['underscore', 'oroui/js/tools/color-util'], function (_, colorUtil) {
    'use strict';

    /**
     * @export  orocalendar/js/calendar/color-manager
     * @class   orocalendar.calendar.colorManager
     */
    var ColorManager = {
        /**
         * A list of background colors are used to determine colors of events of connected calendars
         *  @property {Array}
         */
        colors: null,

        /** @property {String} */
        defaultColor: null,

        /** @property {Object} */
        calendarColors: null,

        initialize: function (options) {
            this.colors = options.colors;
            this.defaultColor = options.colors[15];
            this.calendarColors = {};
        },

        setCalendarColors: function (calendarId, backgroundColor) {
            this.calendarColors[calendarId] = {
                color: this.getContrastColor(backgroundColor),
                backgroundColor: backgroundColor
            };
        },

        removeCalendarColors: function (calendarId) {
            if (!_.isUndefined(this.calendarColors[calendarId])) {
                delete this.calendarColors[calendarId];
            }
        },

        getCalendarColors: function (calendarId) {
            return this.calendarColors[calendarId];
        },

        applyColors: function (obj, getLastBackgroundColor) {
            if (_.isEmpty(obj.color) && _.isEmpty(obj.backgroundColor)) {
                obj.backgroundColor = this._findNextColor(getLastBackgroundColor());
                obj.color = this.getContrastColor(obj.backgroundColor);
            } else if (_.isEmpty(obj.color)) {
                obj.color = this.getContrastColor(this.defaultColor);
            } else if (_.isEmpty(obj.backgroundColor)) {
                obj.backgroundColor = this.defaultColor;
            }
        },

        /**
         * Calculates contrast color
         *
         * @param {string} hex A color in six-digit hexadecimal form.
         * @returns {string}
         */
        getContrastColor: function (hex) {
            return colorUtil.getContrastColor(hex);
        },

        _findColorIndex: function (color) {
            var i = -1;
            _.each(this.colors, function (clr, index) {
                if (clr === color) {
                    i = index;
                }
            });
            return i;
        },

        _findNextColor: function (color) {
            if (_.isEmpty(color)) {
                return this.defaultColor;
            }
            color = color.toUpperCase();
            var i = this._findColorIndex(color);
            if (i === -1) {
                i = this._findColorIndex(this.defaultColor);
            }
            var unusedColors = _.difference(this.colors, _.pluck(this.calendarColors, 'backgroundColor'));
            if (unusedColors.length > 0) {
                //find unused color to end of color list
                for (var j=i+1; j < this.colors.length; j++) {
                    if (_.indexOf(unusedColors, this.colors[j]) !== -1) {
                        return this.colors[j];
                    }
                }
                //find unused color from start of color list to current color
                for (j=0; j < i+1; j++) {
                    if (_.indexOf(unusedColors, this.colors[j]) !== -1) {
                        return this.colors[j];
                    }
                }
            }
            //get next color from list because all colors was used
            return this.colors[i+1];
        }
    };

    return function (options) {
        var obj = _.extend({}, ColorManager);
        obj.initialize(options);
        return obj;
    };
});
