{extends file=$inh_path}{block name=content}

<h3 class="module-header">Elawa Hauptmenü</h3>

<div class="row">
	<div class="col-md-6">
		{if $hostGroup}
			Lehrergruppe:
			{$hostGroup['name']}
		{else}
			Keine Lehrergruppe definiert!
		{/if}
		<a id="select-host-group-button" href="#" class="btn btn-default btn-xs">
			ändern
		</a>
	</div>
	<div class="col-md-6">
		<div class="pull-right">
			<label for="enable-selections">
				Wahlen freigegeben:
			</label>
			<input type="checkbox" name="enable-selections" id="enable-selections"
				data-size="mini" data-off-text="Nein" data-on-text="Ja"
				data-off-color="default" data-on-color="primary"
				{if $selectionsEnabled}checked{/if}>
		</div>
	</div>
</div>


<fieldset>
	<legend>Aktionen</legend>
	<ul class="submodulelinkList">
		<li>
			<a href="index.php?module=administrator|Elawa|Meetings">
				1. Sprechstunden verwalten
			</a>
		</li>
		<li>
			<a id="generatePDF" href="index.php?module=administrator|Elawa|GenerateHostPdf"">
				2. Aushänge im PDF-Format generieren (ben&ouml;tigt etwas Zeit!)
			</a>
		</li>
	</ul>
</fieldset>

<div type="hidden" id="groups" data-groups={$allGroups} />

{/block}



{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}


{block name=js_include append}
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
<script type="text/javascript" src="{$path_js}/administrator/Elawa/main-menu.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
{/block}