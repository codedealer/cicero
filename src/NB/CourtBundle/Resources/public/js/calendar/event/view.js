/*jslint nomen:true*/
/*global define*/
define(['underscore', 'backbone', 'orotranslation/js/translator', 'routing', 'oro/dialog-widget', 'oroui/js/loading-mask',
    'orocalendar/js/form-validation', 'oroui/js/delete-confirmation', 'oroform/js/formatter/field'
], function (_, Backbone, __, routing, DialogWidget, LoadingMask, FormValidation, DeleteConfirmation, fieldFormatter) {
    'use strict';

    var $ = Backbone.$;

    /**
     * @export  orocalendar/js/calendar/event/view
     * @class   orocalendar.calendar.event.View
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /** @property {Object} */
        options: {
            calendar: null,
            connections: null,
            colorManager: null,
            widgetRoute: null,
            widgetOptions: null
        },

        /** @property {Object} */
        selectors: {
            loadingMaskContent: '.loading-content',
            backgroundColor: 'input[name$="[backgroundColor]"]',
            calendarUid: '[name*="calendarUid"]',
            invitedUsers: 'input[name$="[invitedUsers]"]'
        },

        /** @property {Array} */
        userCalendarOnlyFields: [
            {fieldName: 'reminders', emptyValue: {}, selector: '.reminders-collection'},
            {fieldName: 'invitedUsers', emptyValue: '', selector: 'input[name$="[invitedUsers]"]'}
        ],

        initialize: function (options) {
            this.options = _.defaults(_.pick(options || {}, _.keys(this.options)), this.options);
            this.viewTemplate = _.template($(options.viewTemplateSelector).html());
            this.template = _.template($(options.formTemplateSelector).html());

            this.listenTo(this.model, 'sync', this.onModelSave);
            this.listenTo(this.model, 'destroy', this.onModelDelete);
        },

        remove: function () {
            this.trigger('remove');
            this._hideMask();
            Backbone.View.prototype.remove.apply(this, arguments);
        },

        onModelSave: function () {
            this.trigger('addEvent', this.model);
            this.eventDialog.remove();
            this.remove();
        },

        onModelDelete: function () {
            this.eventDialog.remove();
            this.remove();
        },

        render: function () {
            var widgetOptions = this.options.widgetOptions || {},
                defaultOptions = {
                    title: this.model.isNew() ? __('Add New Event') : __('View Event'),
                    stateEnabled: false,
                    incrementalPosition: false,
                    dialogOptions: _.defaults(widgetOptions.dialogOptions || {}, {
                        modal: true,
                        resizable: false,
                        width: 475,
                        autoResize: true,
                        close: _.bind(this.remove, this)
                    }),
                    submitHandler: _.bind(this.saveModel, this)
                },
                onDelete = _.bind(function (e) {
                    var $el = $(e.currentTarget),
                        deleteUrl = $el.data('url'),
                        confirm = new DeleteConfirmation({
                            content: $el.data('message')
                        });
                    e.preventDefault();
                    confirm.on('ok', _.bind(function () {
                        this.deleteModel(deleteUrl);
                    }, this));
                    confirm.open();
                }, this),
                onEdit = _.bind(function (e) {
                    this.eventDialog.setTitle(__('Edit Event'));
                    this.eventDialog.setContent(this.getEventForm());
                    // subscribe to 'delete event' event
                    this.eventDialog.getAction('delete', 'adopted', function (deleteAction) {
                        deleteAction.on('click', onDelete);
                    });
                }, this);

            if (this.options.widgetRoute) {
                defaultOptions.el = $('<div></div>');
                defaultOptions.url = routing.generate(this.options.widgetRoute, {id: this.model.originalId});
                defaultOptions.type = 'Calendar';
            } else {
                defaultOptions.el = this.model.isNew() ? this.getEventForm() : this.getEventView();
                defaultOptions.loadingMaskEnabled = false;
            }

            this.eventDialog = new DialogWidget(_.defaults(
                _.omit(widgetOptions, ['dialogOptions']),
                defaultOptions
            ));
            this.eventDialog.render();

            // subscribe to 'delete event' event
            this.eventDialog.getAction('delete', 'adopted', function (deleteAction) {
                deleteAction.on('click', onDelete);
            });
            // subscribe to 'switch to edit' event
            this.eventDialog.getAction('edit', 'adopted', function (editAction) {
                editAction.on('click', onEdit);
            });

            // init loading mask control
            this.loadingMask = new LoadingMask();
            this.eventDialog.$el.closest('.ui-dialog').append(this.loadingMask.render().$el);

            return this;
        },

        saveModel: function () {
            var errors;
            this.model.set(this.getEventFormData());
            if (this.model.isValid()) {
                this.showSavingMask();
                try {
                    this.model.save(null, {
                        wait: true,
                        error: _.bind(this._handleResponseError, this)
                    });
                } catch (err) {
                    this.showError(err);
                }
            } else {
                errors = _.map(this.model.validationError, function (message) {
                    return __(message);
                });
                this.showError({errors: errors});
            }
        },

        deleteModel: function (deleteUrl) {
            this.showDeletingMask();
            try {
                var options = {
                    wait: true,
                    error: _.bind(this._handleResponseError, this)
                };
                if (deleteUrl) {
                    options.url = deleteUrl;
                }
                this.model.destroy(options);
            } catch (err) {
                this.showError(err);
            }
        },

        showSavingMask: function () {
            this._showMask(__('Saving...'));
        },

        showDeletingMask: function () {
            this._showMask(__('Deleting...'));
        },

        showLoadingMask: function () {
            this._showMask(__('Loading...'));
        },

        _showMask: function (message) {
            if (this.loadingMask) {
                this.loadingMask.$el
                    .find(this.selectors.loadingMaskContent)
                    .text(message);
                this.loadingMask.show();
            }
        },

        _hideMask: function () {
            if (this.loadingMask) {
                this.loadingMask.hide();
            }
        },

        _handleResponseError: function (model, response) {
            this.showError(response.responseJSON || {});
        },

        showError: function (err) {
            this._hideMask();
            if (this.eventDialog) {
                FormValidation.handleErrors(this.eventDialog.$el.parent(), err);
            }
        },

        fillForm: function (form, modelData) {
            var self = this;
            form = $(form);

            self.buildForm(form, modelData);

            var inputs = form.find('[name]');
            var fieldNameRegex = /\[(\w+)\]/g;

            // show loading mask if child events users should be updated
            if (!_.isEmpty(modelData.invitedUsers)) {
                this.eventDialog.once('renderComplete', function() {
                    self.showLoadingMask();
                });
            }

            _.each(inputs, function (input) {
                input = $(input);
                var name = input.attr('name'),
                    matches = [],
                    match;

                while ((match = fieldNameRegex.exec(name)) !== null) {
                    matches.push(match[1]);
                }

                if (matches.length) {
                    var value = self.getValueByPath(modelData, matches);
                    if (input.is(':checkbox')) {
                        if (value === false || value === true) {
                            input.prop('checked', value);
                        } else {
                            input.prop('checked', input.val() == value);
                        }
                    } else {
                        input.val(value);
                    }
                    input.change();
                }

                // hide loading mask if child events users should be updated
                if (name.indexOf('[invitedUsers]') !== -1 && !_.isEmpty(modelData.invitedUsers)) {
                    input.on('select2-data-loaded', function () {
                        self._hideMask();
                    });
                }
            });

            return form;
        },

        buildForm: function (form, modelData) {
            var self = this;
            form = $(form);
            _.each(modelData, function (value, key) {
                if (typeof value === 'object') {
                    var container = form.find('.' + key + '-collection');
                    if (container) {
                        var prototype = container.data('prototype');
                        if (prototype) {
                            _.each(value, function (collectionValue, collectionKey) {
                                container.append(prototype.replace(/__name__/g, collectionKey));
                            });
                        }
                    }

                    self.buildForm(form, value);
                }
            });
        },

        getEventView: function () {
            return this.viewTemplate(_.extend(this.model.toJSON(), {
                formatter: fieldFormatter
            }));
        },

        getEventForm: function () {
            var modelData = this.model.toJSON(),
                templateData = _.extend(this.getEventFormTemplateData(!modelData.id), modelData),
                form = this.fillForm(this.template(templateData), modelData),
                calendarColors = this.options.colorManager.getCalendarColors(this.model.get('calendarUid'));

            form.find(this.selectors.backgroundColor)
                .data('page-component-options').emptyColor = calendarColors.backgroundColor;
            if (modelData.calendarAlias !== 'user') {
                this._showUserCalendarOnlyFields(form, false);
            }
            this._toggleCalendarUidByInvitedUsers(form);

            form.find(this.selectors.calendarUid).on('change', _.bind(function (e) {
                var $emptyColor = form.find('.empty-color'),
                    $selector = $(e.currentTarget),
                    tagName = $selector.prop('tagName').toUpperCase(),
                    calendarUid = tagName === 'SELECT' || $selector.is(':checked') ? $selector.val() : this.model.get('calendarUid'),
                    colors = this.options.colorManager.getCalendarColors(calendarUid),
                    newCalendar = this.parseCalendarUid(calendarUid);
                $emptyColor.css({'background-color': colors.backgroundColor, 'color': colors.color});
                if (newCalendar.calendarAlias === 'user') {
                    this._showUserCalendarOnlyFields(form);
                } else {
                    this._showUserCalendarOnlyFields(form, false);
                }
            }, this));
            form.find(this.selectors.invitedUsers).on('change', _.bind(function (e) {
                this._toggleCalendarUidByInvitedUsers(form);
            }, this));

            return form;
        },

        getEventFormData: function () {
            var fieldNameRegex = /\[(\w+)\]/g,
                data = {},
                formData = this.eventDialog.form.serializeArray();
            formData = formData.concat(this.eventDialog.form.find('input[type=checkbox]:not(:checked)')
                .map(function () {
                    return {"name": this.name, "value": false};
                }).get());
            _.each(formData, function (dataItem) {
                var matches = [], match;
                while ((match = fieldNameRegex.exec(dataItem.name)) !== null) {
                    matches.push(match[1]);
                }

                if (matches.length) {
                    this.setValueByPath(data, dataItem.value, matches);
                }
            }, this);

            if (data.hasOwnProperty('calendarUid')) {
                if (data.calendarUid) {
                    _.extend(data, this.parseCalendarUid(data.calendarUid));
                    if (data.calendarAlias !== 'user') {
                        _.each(this.userCalendarOnlyFields, function (item) {
                            if (item.fieldName) {
                                data[item.fieldName] = item.emptyValue;
                            }
                        });
                    }
                }
                delete data.calendarUid;
            }

            if (data.hasOwnProperty('invitedUsers')) {
                data.invitedUsers = _.map(data.invitedUsers ? data.invitedUsers.split(',') : [], function (item) {
                    return parseInt(item);
                });
            }

            return data;
        },

        parseCalendarUid: function (calendarUid) {
            return {
                calendarAlias: calendarUid.substr(0, calendarUid.lastIndexOf('_')),
                calendar: parseInt(calendarUid.substr(calendarUid.lastIndexOf('_') + 1))
            };
        },

        _showUserCalendarOnlyFields: function (form, visible) {
            _.each(this.userCalendarOnlyFields, function (item) {
                if (item.selector) {
                    if (_.isUndefined(visible) || visible) {
                        form.find(item.selector).closest('.control-group').show();
                    } else {
                        form.find(item.selector).closest('.control-group').hide();
                    }
                }
            });
        },

        _toggleCalendarUidByInvitedUsers: function (form) {
            var $calendarUid = form.find(this.selectors.calendarUid);
            if (!$calendarUid.length) {
                return;
            }
            if (form.find(this.selectors.invitedUsers).val()) {
                $calendarUid.attr('disabled', 'disabled');
                $calendarUid.parent().attr('title', __("The calendar cannot be changed because the event has guests"));
                // fix select2 dynamic change disabled
                if (!$calendarUid.parent().hasClass('disabled')) {
                    $calendarUid.parent().addClass('disabled');
                }
                if ($calendarUid.prop('tagName').toUpperCase() !== 'SELECT') {
                    $calendarUid.parent().find('label').addClass('disabled');
                }
            } else {
                $calendarUid.removeAttr('disabled');
                $calendarUid.removeAttr('title');
                // fix select2 dynamic change disabled
                if ($calendarUid.parent().hasClass('disabled')) {
                    $calendarUid.parent().removeClass('disabled');
                }
                if ($calendarUid.prop('tagName').toUpperCase() !== 'SELECT') {
                    $calendarUid.parent().find('label').removeClass('disabled');
                }
            }
        },

        setValueByPath: function (obj, value, path) {
            var parent = obj, i;

            for (i = 0; i < path.length - 1; i++) {
                if (parent[path[i]] === undefined) {
                    parent[path[i]] = {};
                }
                parent = parent[path[i]];
            }

            parent[path[path.length - 1]] = value;
        },

        getValueByPath: function (obj, path) {
            var current = obj, i;

            for (i = 0; i < path.length; i++) {
                if (current[path[i]] == undefined) {
                    return undefined;
                }
                current = current[path[i]];
            }

            return current;
        },

        getEventFormTemplateData: function (isNew) {
            var templateType = '',
                calendars = [],
                ownCalendar = null,
                isOwnCalendar = function (item) {
                    return (item.get('calendarAlias') === 'user' && item.get('calendar') === item.get('targetCalendar'));
                };

            this.options.connections.each(function (item) {
                var calendar;
                if (item.get('canAddEvent')) {
                    calendar = {uid: item.get('calendarUid'), name: item.get('calendarName')};
                    if (!ownCalendar && isOwnCalendar(item)) {
                        ownCalendar = calendar;
                    } else {
                        calendars.push(calendar);
                    }
                }
            }, this);

            if (calendars.length) {
                if (isNew && calendars.length === 1) {
                    templateType = 'single';
                } else {
                    if (ownCalendar) {
                        calendars.unshift(ownCalendar);
                    }
                    templateType = 'multiple';
                }
            }

            return {
                calendarUidTemplateType: templateType,
                calendars: calendars
            };
        }
    });
});
