// Generated by Coffeescript
var React, classnames;

React = require('react');

classnames = require('classnames');

module.exports = React.createClass({
  getDefaultProps: function() {
    return {
      size: false,
      pullRight: false,
      pullLeft: false,
      name: false,
      fixedWidth: false,
      spin: false
    };
  },
  render: function() {
    var classes;
    classes = classnames({
      'fa': true,
      'pull-left': this.props.pullLeft,
      'pull-right': this.props.pullRight,
      'fa-fw': this.props.fixedWidth,
      'fa-spin': this.props.spin
    });
    if (this.props.size) {
      classes += " fa-" + this.props.size;
    }
    if (this.props.name) {
      classes += " fa-" + this.props.name;
    }
    return React.createElement("i", {
      "className": classes
    });
  }
});
