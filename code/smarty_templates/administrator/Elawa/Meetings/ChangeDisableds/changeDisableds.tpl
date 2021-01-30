{extends file=$inh_path}{block name=content}

<h3 class="module-header">Pausen der Sprechzeiten</h3>

<div class="text-center">
	<select id="hosts-select">
		{foreach $hosts as $host}
			<option value="{$host['ID']}">
				{$host['forename']} {$host['name']}
			</option>
		{/foreach}
	</select>
</div>

<table id="meeting-statuses" class="table table-responsive table-striped">
	<thead></thead>
	<tbody></tbody>
</table>

{/block}

{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/bootstrap-multiselect.css" type="text/css" />
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}


{block name=js_include append}
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
<script type="text/javascript" src="{$path_js}/administrator/Elawa/Meetings/ChangeDisableds/change-disableds.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
{/block}