{extends file=$inh_path}


{block name=html_snippets}
{literal}

<script type="text/template" id="logRowTemplate">
	<tr>
		<td><%= ID %></td>
		<td><%= message %></td>
		<td><%= categoryName %></td>
		<td><%= severityName %></td>
		<td><%= date %></td>
		<td>
			<button class="btn btn-xs btn-default log-additional-data-display"
				data-target="#log-row-additionaldata-<%= ID %>">
				Daten anzeigen
			</button>
			<div id="log-row-additionaldata-<%= ID %>" hidden style="display: none">
				<div class="">
					<%= additionalData %>
				</div>
			</div>
		</td>
	</tr>
</script>

<script type="text/template" id="logPaginationTemplate">

	<li <% if(activePage == 1){ %> class="disabled" <% } %> >
		<a pagenum="<%= 1 %>" href="#">&laquo;</a>
	</li>
	<% for(var i = minPage; i <= maxPage; i++) { %>
		<li <% if(activePage == i){ %> class="active" <% } %> >
			<a pagenum="<%= i %>" href="#"><%= i %></a>
		</li>
	<% } %>
	<li <% if(activePage == pageCount){ %> class="disabled" <% } %> >
		<a pagenum="<%= pageCount %>" href="#">&raquo;</a>
	</li>

</script>

{/literal}
{/block}


{block name=filling_content}

<div class="row">
	<div class="col-sm-4 col-sm-offset-4">
		<h3 class="module-header">Log-Anzeige</h3>
	</div>
	<span id="table-size-container" class="col-sm-4">
		<span class="pull-right label label-success">
			Die Tabelle ist
			<span id="table-size"></span>
			Megabyte groß
		</span>
	</span>
</div>

<div class="row">
	<div class="col-sm-4 col-md-2 text-center">
		<span class="input-group filter-container" data-toggle="tooltip"
			title="{t}Search (Enter to commit){/t}" >
			<span class="input-group-addon">
				<span class="fa fa-search fa-fw"></span>
			</span>
			<input id="filter" type="text" class="form-control"
				placeholder="{t}Search...{/t}" />
		</span>
	</div>
	<div class="col-sm-4 col-md-2 text-center">
		<span class="input-group category-select-container" data-toggle="tooltip"
			title="Nach Kategorie filtern" >
			<span class="input-group-addon">
				<span class="fa fa-th-list fa-fw"></span>
			</span>
			<select id="category-select" class="form-control">
			</select>
		</span>
	</div>
	<div class="col-sm-4 col-md-2 text-center">
		<span class="input-group severity-select-container" data-toggle="tooltip"
			title="Nach Gewichtung filtern" >
			<span class="input-group-addon">
				<span class="fa fa-exclamation-triangle"></span>
			</span>
			<select id="severity-select" class="form-control">
			</select>
		</span>
	</div>
	<div class="col-sm-8 col-md-6 text-center">
		<ul id="page-select" class="pagination">
		</ul>
		<div class="input-group logs-per-page-container pull-right"
			title="{t}Rows per page{/t}" data-toggle="tooltip" >
			<span class="input-group-addon">
				<span class="fa fa-cog"></span>
			</span>
			<input id="logs-per-page" type="text" maxlength="3"
			class="form-control" value="10" />
		</div>
	</div>
</div>

<table id="log-table" class="table table-responsive table-striped table-hover">
	<thead>
		<tr>
			<th>Id</th>
			<th>Nachricht</th>
			<th>Kategorie</th>
			<th>Gewicht</th>
			<th>Datum</th>
			<th>Weitere Daten</th>
		</tr>
	</thead>
	<tbody>
	</tbody>
</table>
{/block}


{block name=style_include append}
<link rel="stylesheet" type="text/css" href="{$path_css}/administrator/System/Logs/showlogs.css">
{/block}


{block name=js_include append}
<script type="text/javascript" src="{$path_js}/administrator/System/Logs/showlogs.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
{/block}