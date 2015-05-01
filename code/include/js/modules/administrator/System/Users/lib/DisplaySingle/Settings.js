// Generated by Coffeescript
var Button, Col, Icon, Input, Panel, React, Row, SelectList, Toggle;

React = require('react');

Button = require('react-bootstrap/lib/Button');

Icon = require('lib/FontAwesomeIcon');

Input = require('react-bootstrap/lib/Input');

Panel = require('react-bootstrap/lib/Panel');

Toggle = require('react-toggle');

Row = require('react-bootstrap/lib/Row');

Col = require('react-bootstrap/lib/Col');

SelectList = require('react-widgets/lib/SelectList');

module.exports = React.createClass({
  getDefaultProps: function() {
    return {
      user: {},
      groups: [],
      onUserChange: function(key, value) {
        return console.log([key, value]);
      }
    };
  },
  handleGroupChange: function(selectedGroups) {
    var groups;
    groups = selectedGroups.map(function(group) {
      return group.id;
    });
    if (groups.length === 0) {
      groups = false;
    }
    return this.props.onUserChange('groups', groups);
  },
  render: function() {
    var personalTitle, systemTitle;
    console.log(this.props);
    personalTitle = React.createElement("h4", null, "Personendaten");
    systemTitle = React.createElement("h4", null, "Systemdaten");
    return React.createElement(Row, null, React.createElement(Col, {
      "md": 12.,
      "lg": 6.
    }, React.createElement(Panel, {
      "className": 'panel-dashboard',
      "header": personalTitle
    }, React.createElement("form", {
      "className": 'form-horizontal'
    }, React.createElement(Input, {
      "type": 'text',
      "value": this.props.user.forename,
      "label": 'Vorname',
      "labelClassName": 'col-xs-2',
      "wrapperClassName": 'col-xs-10',
      "addonBefore": React.createElement(Icon, {
        "name": 'newspaper-o',
        "fixedWidth": true
      })
    }), React.createElement(Input, {
      "type": 'text',
      "value": this.props.user.surname,
      "label": 'Nachname',
      "labelClassName": 'col-xs-2',
      "wrapperClassName": 'col-xs-10',
      "addonBefore": React.createElement(Icon, {
        "name": 'newspaper-o',
        "fixedWidth": true
      })
    }), React.createElement(Input, {
      "type": 'text',
      "value": this.props.user.username,
      "label": 'Benutzername',
      "labelClassName": 'col-xs-2',
      "wrapperClassName": 'col-xs-10',
      "addonBefore": React.createElement(Icon, {
        "name": 'user',
        "fixedWidth": true
      })
    }), React.createElement(Input, {
      "type": 'text',
      "value": this.props.user.email,
      "label": 'Emailadresse',
      "labelClassName": 'col-xs-2',
      "wrapperClassName": 'col-xs-10',
      "addonBefore": React.createElement(Icon, {
        "name": 'envelope-o',
        "fixedWidth": true
      })
    }), React.createElement(Input, {
      "type": 'text',
      "value": this.props.user.telephone,
      "label": 'Telefonnummer',
      "labelClassName": 'col-xs-2',
      "wrapperClassName": 'col-xs-10',
      "addonBefore": React.createElement(Icon, {
        "name": 'phone',
        "fixedWidth": true
      })
    }), React.createElement(Input, {
      "type": 'text',
      "value": this.props.user.birthday,
      "label": 'Geburtsdatum',
      "labelClassName": 'col-xs-2',
      "wrapperClassName": 'col-xs-10',
      "addonBefore": React.createElement(Icon, {
        "name": 'calendar',
        "fixedWidth": true
      })
    })))), React.createElement(Col, {
      "md": 12.,
      "lg": 6.
    }, React.createElement(Panel, {
      "className": 'panel-dashboard',
      "header": systemTitle
    }, React.createElement("form", {
      "className": 'form-horizontal'
    }, React.createElement(Input, {
      "label": 'Konto gesperrt?',
      "labelClassName": 'col-xs-2',
      "wrapperClassName": 'col-xs-10'
    }, React.createElement(Toggle, null)), React.createElement(Input, {
      "wrapperClassName": 'col-xs-offset-2 col-xs-10'
    }, React.createElement(Button, {
      "bsStyle": 'default'
    }, "Passwort \u00e4ndern")), React.createElement(Input, {
      "label": 'Benutzergruppen',
      "labelClassName": 'col-xs-2',
      "wrapperClassName": 'col-xs-10'
    }, React.createElement(SelectList, {
      "data": this.props.groups,
      "valueField": 'id',
      "value": this.props.user.activeGroups,
      "textField": 'name',
      "multiple": true,
      "onChange": this.handleGroupChange
    }))))));
  }
});
