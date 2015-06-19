// Generated by Coffeescript
var Button, Col, Cookies, Icon, Input, NProgress, Panel, React, Row, Table;

React = require('react');

Button = require('react-bootstrap/lib/Button');

Input = require('react-bootstrap/lib/Input');

Panel = require('react-bootstrap/lib/Panel');

Table = require('react-bootstrap/lib/Table');

Row = require('react-bootstrap/lib/Row');

Col = require('react-bootstrap/lib/Col');

Cookies = require('js-cookie');

NProgress = require('nprogress');

Icon = require('lib/FontAwesomeIcon');

module.exports = React.createClass({
  getInitialState: function() {
    return {
      selectedSchoolyearId: false
    };
  },
  getDefaultProps: function() {
    return {
      schoolyears: [],
      bookAssignments: [],
      userId: false
    };
  },
  componentWillReceiveProps: function(newProps) {
    var activeSyArray, pos, prevSelectedSy;
    if (newProps.schoolyears.length > 0) {
      if (Cookies.get('bookAssignmentsSelectedSchoolyearId') != null) {
        prevSelectedSy = parseInt(Cookies.get('bookAssignmentsSelectedSchoolyearId'));
        pos = lookupKeyOfObjectInArray(newProps.schoolyears, 'id', prevSelectedSy);
        if (pos !== false) {
          return this.setState({
            selectedSchoolyearId: prevSelectedSy
          });
        }
      } else {
        activeSyArray = newProps.schoolyears.filter(function(s) {
          return s.active === true;
        });
        if (activeSyArray.length) {
          return this.setState({
            selectedSchoolyearId: activeSyArray[0].id
          });
        } else {
          return this.setState({
            selectedSchoolyearId: newProps.schoolyears[0].id
          });
        }
      }
    }
  },
  handleSchoolyearSelectChange: function(event, stuff) {
    var schoolyearId;
    schoolyearId = parseInt(event.target.value);
    this.setState({
      selectedSchoolyearId: schoolyearId
    });
    return Cookies.set('bookAssignmentsSelectedSchoolyearId', schoolyearId);
  },
  handleBookAssignmentsGenerate: function() {
    return bootbox.confirm("Wollen sie wirklich alle Buchzuweisungen neu generieren? Jetzige Buchzuweisungen werden dabei geloescht. Die Zuweisungen werden nur fuer das Schbas-Vorbeitungsschuljahr neu generiert.", (function(_this) {
      return function(res) {
        if (res) {
          NProgress.start();
          return $.ajax({
            method: 'POST',
            url: 'index.php?module=administrator|Schbas|BookAssignments|Generate',
            data: {
              userId: _this.props.userId
            }
          }).done(function(res) {
            NProgress.done();
            _this.props.refresh();
            return toastr.success(res);
          }).fail(function(jqxhr) {
            NProgress.done();
            return toastr.error(jqxhr.responseText, 'Fehler beim Erstellen derBuchzuweisungen');
          });
        }
      };
    })(this));
  },
  handleRemoveBookAssignment: function(bookAssignmentId, event) {
    NProgress.start();
    return $.ajax({
      method: 'POST',
      url: 'index.php?module=administrator|Schbas|BookAssignments|Delete',
      data: {
        bookAssignmentId: bookAssignmentId
      }
    }).done((function(_this) {
      return function(res) {
        NProgress.done();
        return _this.props.refresh();
      };
    })(this)).fail(function(jqxhr) {
      NProgress.done();
      return toastr.error(jqxhr.responseText, 'Fehler beim loeschen der Buchzuweisung');
    });
  },
  render: function() {
    return React.createElement(Panel, {
      "className": 'panel-dashboard',
      "header": React.createElement("h4", null, "Buchzuweisungen")
    }, React.createElement("form", {
      "className": 'form-horizontal'
    }, React.createElement(Col, {
      "xs": 2.,
      "md": 6.
    }, React.createElement(Input, {
      "type": 'select',
      "label": 'Schuljahr',
      "placeholder": '---',
      "labelClassName": 'col-xs-4',
      "wrapperClassName": 'col-xs-8',
      "onChange": this.handleSchoolyearSelectChange,
      "value": this.state.selectedSchoolyearId
    }, this.props.schoolyears.map(function(schoolyear) {
      return React.createElement("option", {
        "key": schoolyear.id,
        "value": schoolyear.id
      }, schoolyear.label);
    }))), React.createElement(Col, {
      "xs": 2.,
      "md": 6.
    }, React.createElement("a", {
      "className": 'btn btn-success pull-right',
      "data-toggle": 'tooltip',
      "title": 'Zu Buchzuweisung hinzufuegen gehen...',
      "href": 'index.php?module=administrator|Schbas|BookAssignments|View'
    }, React.createElement(Icon, {
      "name": 'plus',
      "fixedWidth": true
    })), React.createElement(Button, {
      "bsStyle": 'default',
      "className": 'pull-right',
      "onClick": this.handleBookAssignmentsGenerate
    }, "Buchzuweisungen neu generieren"))), React.createElement(Table, {
      "striped": true,
      "bordered": true,
      "fill": true
    }, React.createElement("thead", null, React.createElement("tr", null, React.createElement("th", null, "Buch"), React.createElement("th", null, "Optionen"))), React.createElement("tbody", null, this.props.bookAssignments.map((function(_this) {
      return function(bookAssignment) {
        if (bookAssignment.schoolyear.id === _this.state.selectedSchoolyearId) {
          return React.createElement("tr", {
            "key": bookAssignment.id
          }, React.createElement("td", null, bookAssignment.book.title), React.createElement("td", null, React.createElement(Button, {
            "bsStyle": 'danger',
            "bsSize": 'xsmall',
            "onClick": _this.handleRemoveBookAssignment.bind(null, bookAssignment.id)
          }, React.createElement(Icon, {
            "name": 'trash-o',
            "fixedWidth": true
          }))));
        }
      };
    })(this)))));
  }
});