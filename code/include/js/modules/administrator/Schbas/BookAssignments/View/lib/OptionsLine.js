// Generated by Coffeescript
var Button, DropdownButton, ListGroup, ListGroupItem, MenuItem, OptionAddAssignmentLine, React, Row;

React = require('react');

React.Bootstrap = require('react-bootstrap');

OptionAddAssignmentLine = require('./OptionAddAssignmentLine');

Button = React.Bootstrap.Button;

DropdownButton = React.Bootstrap.DropdownButton;

MenuItem = React.Bootstrap.MenuItem;

Row = React.Bootstrap.Row;

ListGroupItem = React.Bootstrap.ListGroupItem;

ListGroup = React.Bootstrap.ListGroup;

module.exports = React.createClass({
  getInitialState: function() {
    return {
      showDialog: false
    };
  },
  getDefaultProps: function() {
    return {
      handleChangeSchoolyear: function() {
        return {};
      },
      schoolyears: []
    };
  },
  handleAddAssignmentsClicked: function() {
    this.setState({
      showDialog: !this.state.showDialog
    });
    return console.log(this.state);
  },
  handleSchoolyearSelect: function(schoolyearId) {
    return this.props.handleChangeSchoolyear(schoolyearId);
  },
  render: function() {
    var schoolyearBtnTitle, selSchoolyear;
    selSchoolyear = $.grep(this.props.schoolyears, function(sy) {
      return sy.active;
    });
    if (selSchoolyear[0] != null) {
      selSchoolyear = selSchoolyear[0];
    }
    schoolyearBtnTitle = "Für Schuljahr " + selSchoolyear.name;
    return React.createElement(ListGroup, null, React.createElement(ListGroupItem, null, React.createElement(Button, {
      "bsStyle": 'primary',
      "onClick": this.handleAddAssignmentsClicked
    }, (!this.state.showDialog ? 'Zuweisung hinzufügen...' : 'Zuweisung abbrechen')), React.createElement(DropdownButton, {
      "bsStyle": 'default',
      "title": schoolyearBtnTitle,
      "className": 'pull-right',
      "onSelect": this.handleSchoolyearSelect
    }, this.props.schoolyears.map((function(_this) {
      return function(schoolyear) {
        if (!schoolyear.active) {
          return React.createElement(MenuItem, {
            "eventKey": schoolyear.id,
            "key": schoolyear.id
          }, schoolyear.name);
        }
      };
    })(this)))), (this.state.showDialog ? React.createElement(OptionAddAssignmentLine, {
      "schoolyear": selSchoolyear
    }) : void 0));
  }
});