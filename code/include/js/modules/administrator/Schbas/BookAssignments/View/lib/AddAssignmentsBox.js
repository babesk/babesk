// Generated by Coffeescript
var Button, React, Row;

React = require('react');

React.Bootstrap = require('react-bootstrap');

Button = React.Bootstrap.Button;

Row = React.Bootstrap.Row;

module.exports = React.createClass({
  getInitialState: function() {
    return {
      showDialog: false
    };
  },
  handleAddAssignmentsClicked: function() {
    this.setState({
      showDialog: !this.state.showDialog
    });
    return console.log(this.state);
  },
  render: function() {
    return React.createElement("div", null, (!this.state.showDialog ? React.createElement(Button, {
      "bsStyle": 'primary',
      "onClick": this.handleAddAssignmentsClicked
    }, "Zuweisung hinzuf\u00fcgen...") : "Hallo!"));
  }
});
