{extends file=$UserParent}

{block name=html_snippets append}

<script type="text/template" id="column-show-template">
	<div class="form-group">
		<label class="col-sm-6 control-label" for="column-show-<%= name %>">
			<%= displayName %>
		</label>
		<input type="checkbox" class="column-switch" data-on-text="Ja"
		data-off-text="Nein" data-size="mini" data-on-color="info"
		data-off-color="default" id="column-show-<%= name %>"
		<% if(isDisplayed){ %> checked <% } %>
		<% if(name == "ID"){ %> disabled <% } %> />
	</div>
</script>

<script type="text/template" id="list-user-settings-template">
	<td>
		<div class="btn-group">
			<a class="btn btn-xs btn-info user-action-settings"
				href="index.php?module=administrator|System|User|DisplayChange&ID=<%= ID %>" title="Nutzereinstellungen"
				>
				<span class="fa fa-cog"></span>
			</a>
			<a class="btn btn-xs btn-default user-action-settings"
				href="index.php?module=administrator|Schbas|BookAssignments|View&userId=<%= ID %>" title="Schbas-Zuweisungen"
				>
				<span class="fa fa-eye"></span>
			</a>
			<a class="btn btn-xs btn-danger user-action-delete" href="#"
				title="Nutzer löschen">
				<span class="fa fa-trash-o"></span>
			</a>
			<a class="btn btn-xs btn-info user-action-dsgvo" href="index.php?module=administrator|System|User&action=DSGVO&id=<%= ID %>"
			   title="DSGVO">
				<span class="fa fa-info"></span>
			</a>
		</div>
	</td>
</script>

<script type="text/template" id="deleted-user-pdf-template">
	<div class="form-group">
		<label class="col-sm-6 control-label"><%= forename %> <%= name %></label>
		<a class="btn btn-info" target="_blank"
			href="index.php?module=administrator|System|User&amp;showPdfOfDeletedUser&amp;pdfId=<%= pdfId %>" >Pdf abrufen</a>
	</div>
</script>

{/block}


{block name=popup_dialogs append}
<div id="table-columns-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"
				aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title">Angezeigte Spalten</h4>
			</div>
			<div class="modal-body">
				<form id="column-show-form" class="form-horizontal" role="form">
				</form>
			</div>
			<div class="modal-footer">
				<button id="column-show-form-submit" type="button"
					class="btn btn-primary">
					{t}Ok{/t}
				</button>
			</div>
		</div>
	</div>
</div>

<div id="deleted-user-pdf-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"
					aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title">Pdfs der gelöschten Nutzer</h4>
			</div>
			<div class="modal-body">
				<form id="deleted-user-pdf-form" class="form-horizontal" role="form">
					<p class="no-users-deleted">Es wurden noch keine Nutzer gelöscht.</p>
				</form>
			</div>
		</div>
	</div>
</div>

<div id="multiselection-actions-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"
					aria-hidden="true">
					&times;
				</button>
				<h4 class="modal-title">Aktionen mit selektierten Nutzern</h4>
					<small>
						<b>Hinweis:</b>
						Die Veränderungen werden beim Klick auf den Verändern-Button ohne Nachfrage durchgeführt!
					</small>
			</div>
			<div id="multiselection-actions-container" class="modal-body">
				{* Dynamically created content here *}
			</div>
		</div>
	</div>
</div>

{/block}


{block name=filling_content}
<div class="row">
	<div class="center-block">
		<div class="col-sm-12 col-md-3 text-center">
			<span class="input-group filter-container">
				<span class="input-group-btn">
					<select id="search-select-menu" class="dropdown-menu pull-right"
						role="menu" multiple="multiple">
					</select>
				</span>
				<input id="filter" type="text" class="form-control"
					placeholder="{t}Search...{/t}"
					title="{t}Search (Enter to commit){/t}" />
				<span class="input-group-btn">
					<button id="search-submit" class="btn btn-default">
						<span class="fa fa-search fa-fw"></span>
					</button>
				</span>
			</span>
		</div>
		<div class="col-sm-12 col-md-6 text-center">
			<ul id="page-select" class="pagination">
			</ul>
		</div>
		<div class="col-sm-12 col-md-3 form-group">
				<button class="btn btn-default" data-toggle="modal"
					data-target="#table-columns-modal"
					title="{t}What Columns should be displayed{/t}">
					{t}Columns{/t}
				</button>
				<button id="deleted-user-pdf-modal-btn" class="btn btn-default"
					data-toggle="modal" data-target="#deleted-user-pdf-modal"
					title="{t}Pdfs of deleted users{/t}">
					PDF
				</button>
				<div class="input-group users-per-page-container pull-right"
					title="{t}Rows per page{/t}" >
					<span class="input-group-addon">
						<span class="fa fa-cog"></span>
					</span>
					<input id="users-per-page" type="text" maxlength="3" class="form-control"
						value="10" />
				</div>
		</div>
	</div>
</div>

<div>
	<table id="user-table" class="table table-striped table-responsive table-hover">
	</table>
</div>
<div>
	<button id="selected-action-button" class="btn btn-default" type="button"
		data-toggle="modal" data-target="#multiselection-actions-modal">
		&uArr; Aktion mit selektierten
	</button>
	<ul id="relative-pager" class="pager">
		<li id="relative-pager-prev"><a href="#">&larr; {t}Previous{/t}</a></li>
		<li id="relative-pager-next"><a href="#">{t}Next{/t} &rarr;</a></li>
	</ul>
</div>

{/block}

{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/administrator/System/User/display-all.css" type="text/css" />
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
<link rel="stylesheet" href="{$path_css}/bootstrap-multiselect.css" type="text/css" />
{/block}

{block name="js_include" append}
<script src="{$path_js}/administrator/System/User/display-all.js">
</script>
<script type="text/javascript" src="{$path_js}/vendor/jquery.cookie.js">
</script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js">
</script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
{/block}
