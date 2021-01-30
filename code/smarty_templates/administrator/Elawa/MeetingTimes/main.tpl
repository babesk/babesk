{extends file=$inh_path}{block name=content}

<h3 class="module-header">Sprechtag-Wahlen Menü</h3>

<div class="text-center">
	<form action="index.php?module=administrator|Elawa|MeetingTimes" method="post" id="catForm">
		<select id="category-select" name="category">
			{foreach $categories as $category}
				<option value="{$category['id']}" {if $catId==$category['id']} selected {/if}>
					{$category['name']}
				</option>
			{/foreach}
		</select>
	</form>
</div>

<fieldset>
	<legend>Zeiten</legend>
	<table class="table table-responsive table-hover table-striped">
		<thead></thead>
		<tbody>
			<tr>
				<th>ID</th>
				<th>Start</th>
				<th>L&auml;nge</th>
			</tr>
			{foreach $times as $time}
				<form action="index.php?module=administrator|Elawa|MeetingTimes&action=4" method="post" id="form{$time['id']}">
					<tr>
						<td><input type="hidden" name="id" value="{$time['id']}">{$time['id']}</td>
						<td><input type="text" class="{$time['id']}" name="start" value="{$time['time']}" disabled></td>
						<td><input type="text" class="{$time['id']}" name="length" value="{$time['length']}" disabled></td>
						<td>
							<button type="button" class="btn btn-info btn-xs edit-category" id="{$time['id']}">
								<span class="fa fa-edit fa-fw edit{$time['id']}"></span>
							</button>
							{if !$elawaEnabled['value']}
							<button type="button" class="btn btn-danger btn-xs delete-category" id="{$time['id']}">
								<span class="fa fa-trash-o"></span>
							</button>
							{/if}
						</td>
					</tr>
				</form>
			{/foreach}
			<form action="index.php?module=administrator|Elawa|MeetingTimes&action=3" method="post">
				<tr>
					<td>
						<input type="hidden" name="category" value="{$catId}">
					</td>
					<td>
						<input type="text" name="start" placeholder="Hrs:Min">
					</td>
					<td>
						<input type="text" name="length" placeholder="Hrs:Min">
					</td>
					<td>
						<button type="submit"
							class="btn btn-info btn-xs add-category">
							Hinzuf&uuml;gen
						</button>
					<td>
				</tr>
			</form>
		</tbody>
	</table>
</fieldset>

{/block}


{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/bootstrap-multiselect.css" type="text/css" />
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}


{block name=js_include append}
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
<script type="text/javascript" src="{$path_js}/administrator/Elawa/MeetingTimes/main.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
{/block}
