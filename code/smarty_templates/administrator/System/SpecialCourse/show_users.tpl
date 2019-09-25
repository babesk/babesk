{extends file=$SpecialCourseParent}{block name=content}

<script type="text/template" id="user-table-template">
	<% for(var i = 0; i < users.length; i++) { %>
		<tr>
			<td align="center"><%= users[i].forename %></td>
			<td align="center"><%= users[i].name %></td>
                <% for(var j=0; j<subjects.length;j++) { %>
			<td><input type="checkbox" name="<%=users[i].ID%>|<%=subjects[j].abbreviation%>"/></td>
                <% } %>
		</tr>
	<% } %>
</script>

	<div class="dropdown">
		<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Jahrgang <span class="caret"></span> </button>
		<ul class="dropdown-menu">
            {foreach $gradelevel as $grade}
				<li><a data-name="{$grade.gradelevel}" class="dropdown-item grade">{$grade.gradelevel}</a></li>
            {/foreach}
		</ul>
	</div>

	<div class="dropdown">
		<button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Schuljahr<span class="caret"></span> </button>
		<ul class="dropdown-menu">
            {foreach $schoolyears as $schoolyear}
				<li><a data-name="{$schoolyear.id}" class="dropdown-item year">{$schoolyear.label}</a></li>
            {/foreach}
		</ul>
	</div>

	<br>

	<div class="row col-sm-12 col-md-5 col-lg-7">
		<span class="input-group filter-container">
			<input type='text' id='user_search' class="form-control">
			<span class="input-group-btn">
				<button type="button" id="search_btn" class="btn btn-default">
					<span class="fa fa-search fa-fw"></span>
				</button>
			</span>
		</span>
	</div>



<form action="index.php?section=System|SpecialCourse&action=4"
	method="post" onsubmit="submit()">
<table class="table table-striped table-hover">
	<thead>
		<tr id="subjects-list">

		</tr>
	</thead>
	<tbody id="user-table-body">

	</tbody>
</table>
	<br> <input id="submit" onclick="submit()" type="submit" value="Speichern" />
</form>

{/block}

{block name="js_include" append}
<script type="text/javascript" src="{$path_js}/vendor/paginator/jquery.bootpag.min.js"></script>

<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>

<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>

<script type="text/javascript" src="{$path_js}/custom-base.js"></script>

<script type="text/javascript" src="{$path_js}/administrator/System/SpecialCourse/show_users.js"></script>

{/block}