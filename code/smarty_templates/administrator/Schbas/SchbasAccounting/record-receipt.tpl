{extends file=$base_path}
{block name=html_snippets append}

<script type="text/template" id="user-table-template">
	<thead>
		<tr>
			<th>Kartennummer</th>
			<th>ID</th>
			<th>Vorname</th>
			<th><a id="name-table-head">Nachname</a></th>
			<th>Benutzername</th>
			<th><a id="grade-table-head">Klasse</a></th>
			<th>Fehlend</th>
			<th>Bezahlt</th>
			<th>Soll</th>
			<th>Typ</th>
			<th>Selektor</th>
		</tr>
	</thead>
	<tbody>
		<% for(var i = 0; i < users.length; i++) { %>
			<tr data-user-id="<%= users[i].id %>" tabindex="1">
				<td class="cardnumber"> <%= users[i].cardnumber %> </td>
				<td class="id"><%= users[i].id %></td>
				<td class="forename"> <%= users[i].forename %> </td>
				<td class="name"> <%= users[i].name %> </td>
				<td class="username"> <%= users[i].username %> </td>
				<td class="active-grade"> <%= users[i].activeGrade %> </td>
				<% if(users[i].loanChoiceAbbreviation != 'ls') { %>
					<td class="payment-missing">
						<% users[i].missingAmount = parseFloat(users[i].missingAmount); %>
						<% if(users[i].missingAmount > 0) { %>
							<span class="text-warning">
								<%= users[i].missingAmount.toFixed(2) %> €
							</span>
						<% } else if(users[i].missingAmount == 0) { %>
							<span class="text-success">
								<%= users[i].missingAmount.toFixed(2) %> €
							</span>
						<% } else { %>
							<span class="text-danger">Überschuss!
								<%= users[i].missingAmount.toFixed(2) %> €
							</span>
						<% } %>
					</td>
					<td class="payment-payed">
						<%= parseFloat(users[i].payedAmount).toFixed(2) %> €
					</td>
					<td class="payment-to-pay">
						<%= parseFloat(users[i].amountToPay).toFixed(2) %> €
					</td>
				<% } else { %>
					<td class="returned" colspan="3" style="text-align: center" data-value=<%=users[i].formReturned %> >
						<% if (users[i].formReturned == 1){ %>
							<span class="text-success">Leistungsbescheid abgegeben</span>
						<% }else{ %>
							<span class="text-danger">Leistungsbescheid liegt nicht vor</span>
						<% } %>
					</td>
				<% } %>
				<td class="loan-choice-type">
					<%
						var col = '';
						if(users[i].loanChoiceAbbreviation == 'lr') {
							col = 'text-primary';
						}
						else if(users[i].loanChoiceAbbreviation == 'ls') {
							col = 'text-success';
						}
						else if(users[i].loanChoiceAbbreviation == 'nl') {
							col = 'text-danger';
						}
					%>
					<span class="<%= col %>">
						<% if(users[i].loanChoice) { %>
							<%= users[i].loanChoice %>
						<% } else { %>
							<p class="text-muted">Antrag nicht erfasst</p>
						<% } %>
					</span>
				</td>
				<td class="selector" data-selector="<%= (i + 1) % 10 %>">
					<a class="btn btn-default btn-xs">Shift + <%= (i + 1) % 10 %></a>
				</td>
			</tr>
		<% } %>
	</tbody>
</script>

{/block}


{block name=popup_dialogs append}

<div class="modal fade" id="credits-change-modal" aria-hidden="true"
	tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="credits-change-form">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"
						aria-hidden="true">&times;</button>
					<h4 class="modal-title">Zahlung registrieren</h4>
				</div>
				<div class="modal-body">
					<label for="credits-change-input">
						Neues Guthaben von <span class="username"></span>:
					</label>
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon">
								<span class="fa fa-eur"></span>
							</div>
							<input type="text" id="credits-change-input" class="form-control"
								placeholder="Guthaben eingeben..." />
						</div>
					</div>
					<label for="to-pay-change-input">
						Soll-Betrag verändern:
					</label>
					<div class="form-group-row">
						<div class="input-group">
							<div class="input-group-addon">
								<span class="fa fa-eur"></span>
							</div>
							<input type="text" id="to-pay-change-input" class="form-control"
								   placeholder="Soll-Betrag eingeben..." />
						</div>
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default pull-left"
						data-toggle="popover" data-container="body"
						title="Hilfe zur Zahlungseingabe" data-content="In diesem Popup können sie den Geldeingang des ausgewählten Benutzers ändern. Es ist angepasst, um mit der Tastatur gut bedienbar zu sein. Das obere Feld enthält die Zahlung des Benutzers. Darunter befindet sich ein Feld, in das man einen manuellen zu addierenden Betrag eingeben kann. Um statt die Zahlung direkt einzugeben etwas hinzuzuaddieren, drücken sie einmal 'Tab', geben den Betrag ein, und dann Enter. Falls der Betrag dem gewünschten entspricht, speichert ein weiteres Enter die Zahlung ab. Mit der Plus-Taste (+) wird der Soll-Betrag direkt in das Zahlungsfeld übernommen.">
						Hilfe
					</button>
					<button type="button" class="btn btn-default" data-dismiss="modal">
						Abbrechen
					</button>
					<input type="submit" class="btn btn-primary apply" value="Speichern">
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal fade" id="form-change-modal" aria-hidden="true"
	 tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<form id="form-change-form" class="simpleForm">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"
							aria-hidden="true">&times;</button>
					<h4 class="modal-title">Leistungsbescheid registrieren</h4>
				</div>
				<div class="modal-body">
					<div class="simpleForm">
						<input id="formReturnedSwitch" type="checkbox"
							   name="formReturnedSwitch" data-on-text="Ja"
							   data-off-text="Nein" data-on-color="success"
							   data-off-color="danger" class="bootstrap-switch">
					</div>
					<div class="clearfix"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">
						Abbrechen
					</button>
					<input type="submit" class="btn btn-primary apply" id="submit" value="Speichern">
				</div>
			</form>
		</div>
	</div>
</div>

{/block}


{block name=filling_content}

<h3 class="module-header">
	Geldeingänge erfassen (<span id='prepSy'></span>)
</h3>

<div class="alert alert-info">
	<button type="button" class="close" data-dismiss="alert" aria-hidden="true">
		&times;
	</button>
	<strong>Benutzerauswahl</strong>
	Sie können die Benutzer, nachdem sie sie
	gesucht haben, entweder in der Tabelle anklicken, oder mit Shift und einer
	Zahl (zB Shift + 1) einen Benutzer anwählen. Dies funktioniert nur mit der
	Zahlenreihe über den Buchstaben. Nachdem erfolgreich ein Zahlung registriert
	wurde, können sie sofort wieder anfangen den nächsten Nutzer zu suchen.
</div>

<div class="row">
	<div class="center-block">
		<div class="col-sm-12 col-md-5 col-lg-7">
			<div class="row">
				<div class="col-md-12 col-lg-8">
					<span class="input-group filter-container">
						<span class="input-group-btn">
							<select id="search-select-menu" class="dropdown-menu pull-right"
								role="menu" multiple="multiple">
								<option value="cardnumber" selected>Kartennummer</option>
								<option value="username" selected>Benutzername</option>
								<option value="grade" selected>Klasse</option>
							</select>
						</span>
						<input id="filter" type="text" class="form-control"
							placeholder="Suchen (Benutzername/Kartennummer/Klasse)"
							title="{t}Search (Enter to commit){/t}" autofocus />
						<span class="input-group-btn">
							<button id="search-submit" class="btn btn-default">
								<span class="fa fa-search fa-fw"></span>
							</button>
						</span>
					</span>
				</div>
				<div class="col-md-12 col-lg-4">
					<div class="btn-group">
						<button type="button" class="btn btn-default dropdown-toggle"
							data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							Spezieller Filter <span class="caret"></span>
						</button>
						<ul id="special-filter-menu" class="dropdown-menu">
							<li>
								<a id="show-missing-amount-only" class="special-filter-item"
									data-name="showMissingAmountOnly">
									Nur fehlendes Geld
								</a>
							</li>
							<li>
								<a id="show-missing-form-only" class="special-filter-item"
									data-name="showMissingFormOnly">
									Nur fehlender Antrag
								</a>
							</li>
							<li>
								<a id="show-missing-amount-only" class="special-filter-item"
									data-name="showSelfbuyerOnly">
									Nur Selbstkäufer
								</a>
							</li>
							<li>
								<a id="show-missing-amount-only" class="special-filter-item"
									data-name="showNotPayingOnly">
									Nur Befreite
								</a>
							</li>
							<li>
								<a id="show-missing-amount-only" class="special-filter-item"
								   data-name="showPayedTooMuch">
									Zu viel bezahlt
								</a>
							</li>
							<li>
								<a id="show-missing-amount-only" class="special-filter-item"
								   data-name="showFormNotReturned">
									Leistungsbescheid nicht abgegeben
								</a>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-12 col-md-7 col-lg-5">
			<div id="page-select" class="pull-right"></div>
		</div>
	</div>
</div>

<div>
	<table id="user-table"
		class="table table-striped table-responsive table-hover">
	</table>
</div>

<div class="row">
	<div class="col-sm-12">
		<button id="btn-print-pdf" class="btn btn-default">
			PDF herunterladen
		</button>
	</div>
</div>

{/block}

{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/bootstrap-multiselect.css" type="text/css" />
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}

{block name=js_include append}

<script type="text/javascript" src="{$path_js}/vendor/paginator/jquery.bootpag.min.js"></script>

<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>

<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>

<script type="text/javascript"
	src="{$path_js}/administrator/Schbas/SchbasAccounting/RecordReceipt/record-receipt.js">
</script>

{/block}