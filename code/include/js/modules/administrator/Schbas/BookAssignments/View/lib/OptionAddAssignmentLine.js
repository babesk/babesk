// Generated by Coffeescript
var Button, ButtonGroup, Col, DropdownButton, ExtendedSelect, Input, ListGroupItem, MenuItem, React, Row;

React = require('react');

React.Bootstrap = require('react-bootstrap');

ListGroupItem = React.Bootstrap.ListGroupItem;

Row = React.Bootstrap.Row;

Col = React.Bootstrap.Col;

Button = React.Bootstrap.Button;

Input = React.Bootstrap.Input;

ButtonGroup = React.Bootstrap.ButtonGroup;

DropdownButton = React.Bootstrap.DropdownButton;

MenuItem = React.Bootstrap.MenuItem;

ExtendedSelect = require('react-select');

module.exports = React.createClass({
  getInitialState: function() {
    return {
      selectedBook: {
        value: 0,
        label: ''
      },
      selectedType: 'user',
      selectedValue: {
        value: 0,
        label: ''
      }
    };
  },
  getDefaultProps: function() {
    return {
      schoolyear: {},
      onAssignmentsChanged: function() {
        return {};
      }
    };
  },
  handleSubmit: function() {
    if (!this.state.selectedBook.value) {
      toastr.error('Bitte wählen sie ein Buch aus');
      return;
    }
    if (!this.state.selectedValue.value) {
      toastr.error('Bitte wählen sie einen Eintrag bei Benutzer/Klasse/Klassenstufe aus');
      return;
    }
    if (!this.props.schoolyear.id) {
      toastr.error('Kein Schuljahr ausgewählt');
      return;
    }
    return $.post('index.php?module=administrator|Schbas|BookAssignments|Add', {
      bookId: this.state.selectedBook.value,
      entityType: this.state.selectedType,
      entityId: this.state.selectedValue.value,
      schoolyearId: this.props.schoolyear.id
    }).done((function(_this) {
      return function(data) {
        toastr.success(data, 'Erfolgreich');
        return _this.props.onAssignmentsChanged();
      };
    })(this)).fail(function(jqxhr) {
      return toastr.error(jqxhr.responseText, 'Fehler beim Hinzufügen');
    });
  },
  replaceKeys: function(data, keyLabelName, keyValueName) {
    return data.map(function(entry) {
      entry.label = entry[keyLabelName];
      entry.value = entry[keyValueName];
      delete entry[keyLabelName];
      delete entry[keyValueName];
      return entry;
    });
  },
  searchBooks: function(input, callback) {
    return setTimeout((function(_this) {
      return function() {
        if (!input.length) {
          return;
        }
        return $.getJSON('index.php?module=administrator|Schbas|Books|Search', {
          title: input
        }).done(function(data) {
          var selectData;
          selectData = _this.replaceKeys(data, 'title', 'id');
          return callback(null, {
            options: selectData
          });
        }).fail(function(jqxhr) {
          return toastr.error(jqxhr.responseText, 'Fehler beim Buch-suchen');
        });
      };
    })(this), 500);
  },
  searchUsers: function(input, callback) {
    return setTimeout((function(_this) {
      return function() {
        if (!input.length) {
          return;
        }
        if (_this.props.schoolyear.id == null) {
          toastr.error('Kein Schuljahr ausgewählt');
        }
        return $.getJSON('index.php?module=administrator|System|Users|Search', {
          username: input,
          schoolyearId: _this.props.schoolyear.id
        }).done(function(data) {
          var selectData;
          selectData = _this.replaceKeys(data, 'username', 'id');
          return callback(null, {
            options: selectData
          });
        }).fail(function(jqxhr) {
          return toastr.error(jqxhr.responseText, 'Fehler beim User-suchen');
        });
      };
    })(this), 500);
  },
  searchGrades: function(input, callback) {
    return setTimeout((function(_this) {
      return function() {
        if (!input.length) {
          return;
        }
        return $.getJSON('index.php?module=administrator|System|Grades|Search', {
          gradename: input
        }).done(function(data) {
          var selectData;
          selectData = _this.replaceKeys(data, 'gradename', 'id');
          return callback(null, {
            options: selectData
          });
        }).fail(function(jqxhr) {
          return toastr.error(jqxhr.responseText, 'Fehler beim Klassen-suchen');
        });
      };
    })(this), 500);
  },
  searchGradelevels: function(input, callback) {
    return setTimeout((function(_this) {
      return function() {
        if (!input.length) {
          return;
        }
        return $.getJSON('index.php?module=administrator|System|Gradelevels|Search', {
          gradelevel: input
        }).done(function(data) {
          var selectData;
          selectData = data.map(function(entry) {
            entry.label = entry.gradelevel.toString();
            entry.value = entry.gradelevel;
            delete entry.gradelevel;
            return entry;
          });
          return callback(null, {
            options: selectData
          });
        }).fail(function(jqxhr) {
          return toastr.error(jqxhr.responseText, 'Fehler beim Klassenstufen-suchen');
        });
      };
    })(this), 500);
  },
  handleTypeSelect: function(event) {
    return this.setState({
      selectedType: event.target.value,
      selectedValue: {
        id: 0,
        label: ''
      }
    });
  },
  handleBookSelect: function(bookVal, bookData) {
    var data;
    data = bookData[0];
    return this.setState({
      selectedBook: data
    });
  },
  handleEntityValueSelect: function(value, entityData) {
    var data;
    data = entityData[0];
    return this.setState({
      selectedValue: data
    });
  },
  render: function() {
    var typeLabel;
    typeLabel = '';
    if (this.state.selectedType === 'user') {
      typeLabel = 'Benutzer';
    }
    if (this.state.selectedType === 'grade') {
      typeLabel = 'Klasse';
    }
    if (this.state.selectedType === 'gradelevel') {
      typeLabel = 'Klassenstufe';
    }
    return React.createElement(ListGroupItem, null, React.createElement("form", {
      "className": 'form-horizontal'
    }, React.createElement(Input, {
      "type": 'select',
      "label": 'Hinzufügen zu',
      "labelClassName": 'col-sm-2',
      "wrapperClassName": 'col-sm-10',
      "onChange": this.handleTypeSelect,
      "value": this.state.selectedType
    }, React.createElement("option", {
      "value": 'user',
      "key": 'user',
      "eventKey": 'user'
    }, "Benutzer"), React.createElement("option", {
      "value": 'grade',
      "key": 'grade',
      "eventKey": 'grade'
    }, "Klasse"), React.createElement("option", {
      "value": 'gradelevel',
      "key": 'gradelevel',
      "eventKey": 'gradelevel'
    }, "Klassenstufe")), React.createElement(Input, {
      "label": 'Buch',
      "labelClassName": 'col-sm-2',
      "wrapperClassName": 'col-sm-10'
    }, React.createElement(ExtendedSelect, {
      "key": 1.,
      "asyncOptions": this.searchBooks,
      "autoload": false,
      "name": 'add-assignment-book-search',
      "value": this.state.selectedBook.label,
      "onChange": this.handleBookSelect
    })), React.createElement(Input, {
      "label": typeLabel,
      "labelClassName": 'col-sm-2',
      "wrapperClassName": 'col-sm-10',
      "onChange": this.handleTypeSelect
    }, (this.state.selectedType === 'grade' ? React.createElement(ExtendedSelect, {
      "key": 2.,
      "asyncOptions": this.searchGrades,
      "autoload": false,
      "name": 'add-assignment-grade-search',
      "value": this.state.selectedValue.label,
      "onChange": this.handleEntityValueSelect
    }) : this.state.selectedType === 'gradelevel' ? React.createElement(ExtendedSelect, {
      "key": 3.,
      "asyncOptions": this.searchGradelevels,
      "autoload": false,
      "name": 'add-assignment-gradelevel-search',
      "value": this.state.selectedValue.label,
      "onChange": this.handleEntityValueSelect
    }) : this.state.selectedType === 'user' ? React.createElement(ExtendedSelect, {
      "key": 4.,
      "asyncOptions": this.searchUsers,
      "autoload": false,
      "name": 'add-assignment-users-search',
      "value": this.state.selectedValue.label,
      "onChange": this.handleEntityValueSelect
    }) : void 0)), React.createElement(Button, {
      "bsStyle": 'primary',
      "className": 'pull-right',
      "onClick": this.handleSubmit
    }, "Buch hinzuf\u00fcgen"), React.createElement("div", {
      "className": 'clearfix'
    })));
  }
});
