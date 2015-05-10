// Generated by Coffeescript
var Button, Col, ColumnDisplaySelect, Icon, IndexBox, Input, InventoryTable, NProgress, Paginator, Panel, React, Row, Select, Table;

React = require('react');

Button = require('react-bootstrap/lib/Button');

Icon = require('lib/FontAwesomeIcon');

Panel = require('react-bootstrap/lib/Panel');

Row = require('react-bootstrap/lib/Row');

Col = require('react-bootstrap/lib/Col');

Table = require('react-bootstrap/lib/Table');

Input = require('react-bootstrap/lib/Input');

NProgress = require('nprogress');

Paginator = require('lib/Paginator');

Select = require('react-select');

IndexBox = React.createClass({
  getInitialState: function() {
    return {
      activePage: 1,
      entriesPerPage: 10,
      pageCount: 1,
      columns: ['id', 'barcode', 'lentUser', 'bookTitle', 'bookIsbn', 'subjectName', 'bookAuthor'],
      columnTranslations: {
        id: 'ID',
        barcode: 'Barcode',
        lentUser: 'Verliehen an',
        bookTitle: 'Buchtitel',
        bookAuthor: 'Buchauthor',
        bookIsbn: 'ISBN',
        subjectName: 'Fach'
      },
      displayColumns: ['barcode', 'lentUser'],
      filter: '',
      sort: '',
      data: []
    };
  },
  componentDidMount: function() {
    return this.updateData();
  },
  updateData: function() {
    NProgress.start();
    return $.getJSON('index.php?module=administrator|Schbas|Inventory&index&ajax', {
      filter: this.state.filter,
      sort: this.state.sort,
      activePage: this.state.activePage,
      entriesPerPage: this.state.entriesPerPage,
      displayColumns: this.state.displayColumns
    }).done((function(_this) {
      return function(res) {
        var stateTemp;
        stateTemp = _this.state;
        stateTemp.data = res.data;
        stateTemp.pageCount = parseInt(res.pageCount);
        _this.setState(stateTemp);
        return NProgress.done();
      };
    })(this)).fail(function(jqxhr) {
      toastr.error(jqxhr.responseText, 'Fehler');
      return NProgress.done();
    });
  },
  handleChangeActivePage: function(pagenum) {
    return this.setState({
      activePage: pagenum
    }, this.updateData);
  },
  handleSearch: function() {
    return this.updateData();
  },
  handleFilterChange: function(event) {
    return this.setState({
      filter: event.target.value
    });
  },
  handleFilterKeyDown: function(event) {
    if (event.key === 'Enter') {
      return this.handleSearch();
    }
  },
  handleSelectedColumnsChange: function(values) {
    if (values.indexOf('barcode') < 0) {
      values.unshift('barcode');
    }
    return this.setState({
      displayColumns: values
    });
  },
  render: function() {
    var searchButton;
    searchButton = React.createElement(Button, {
      "onClick": this.handleSearch
    }, React.createElement(Icon, {
      "name": 'search'
    }));
    return React.createElement(Panel, {
      "className": 'panel-dashboard',
      "header": React.createElement("h4", null, "Inventar \u00dcbersicht")
    }, React.createElement(Row, {
      "className": 'text-center'
    }, React.createElement(Col, {
      "md": 4.
    }, React.createElement(Input, {
      "type": 'text',
      "value": this.state.filter,
      "onChange": this.handleFilterChange,
      "onKeyDown": this.handleFilterKeyDown,
      "buttonAfter": searchButton
    })), React.createElement(Col, {
      "md": 4.
    }, React.createElement(Paginator, {
      "maxPages": 10.,
      "numPages": this.state.pageCount,
      "onClick": this.handleChangeActivePage
    })), React.createElement(Col, {
      "md": 4.
    }, React.createElement(ColumnDisplaySelect, {
      "columnTranslations": this.state.columnTranslations,
      "columns": this.state.columns,
      "onChange": this.handleSelectedColumnsChange,
      "displayColumns": this.state.displayColumns
    }))), React.createElement(Row, null, React.createElement(Col, {
      "xs": 12.
    }, React.createElement(InventoryTable, {
      "columnTranslations": this.state.columnTranslations,
      "dataRows": this.state.data,
      "displayColumns": this.state.displayColumns
    }))));
  }
});

ColumnDisplaySelect = React.createClass({
  propTypes: {
    columns: React.PropTypes.array,
    columnTranslations: React.PropTypes.array,
    onChange: React.PropTypes.func
  },
  getDefaultProps: function() {
    return {
      columns: [],
      columnTranslations: [],
      onChange: function(values) {
        return console.log(values);
      }
    };
  },
  handleChange: function(value) {
    var pos, values;
    values = value.split(',');
    if (values.indexOf('') > -1) {
      pos = values.indexOf('');
      values.splice(pos, pos + 1);
    }
    return this.props.onChange(values);
  },
  render: function() {
    var possibleCols;
    possibleCols = this.props.columns.map((function(_this) {
      return function(col) {
        if (_this.props.columnTranslations[col] != null) {
          return {
            label: _this.props.columnTranslations[col],
            value: col
          };
        } else {
          return {
            label: col,
            value: col
          };
        }
      };
    })(this));
    return React.createElement(Select, {
      "multi": true,
      "placeholder": 'Spaltenanzeige',
      "options": possibleCols,
      "onChange": this.handleChange,
      "value": this.props.displayColumns
    });
  }
});

InventoryTable = React.createClass({
  getDefaultProps: function() {
    return {
      displayColumns: ['id', 'barcode', 'lentUser'],
      columnTranslations: {},
      dataRows: []
    };
  },
  render: function() {
    return React.createElement(Table, null, React.createElement("thead", null, React.createElement("tr", null, this.props.displayColumns.map((function(_this) {
      return function(column, index) {
        var columnName;
        if (_this.props.columnTranslations[column] != null) {
          columnName = _this.props.columnTranslations[column];
        } else {
          columnName = column;
        }
        return React.createElement("th", {
          "key": index
        }, columnName);
      };
    })(this)))), React.createElement("tbody", null, this.props.dataRows.map((function(_this) {
      return function(row) {
        return React.createElement("tr", {
          "key": row.id
        }, _this.props.displayColumns.map(function(column) {
          return React.createElement("td", null, (row[column] != null ? column === 'lentUser' ? _this.renderLentUser(row[column]) : row[column] : void 0));
        }));
      };
    })(this))));
  },
  renderLentUser: function(data) {
    return React.createElement("a", {
      "href": "index.php?module=administrator|System|Users&id=" + data.id
    }, data.username);
  }
});

React.render(React.createElement(IndexBox, null), $('#entry')[0]);
