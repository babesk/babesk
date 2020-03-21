{extends file=$inh_path}
{block name='content'}

	<div class="row">
		<div class="col-md-6">
			{if $authorGroup}
				Lehrergruppe:
				{$authorGroup.name}
			{else}
				Keine Autorengruppe definiert!
			{/if}
			<a id="select-host-group-button" href="#" class="btn btn-default btn-xs">
				ändern
			</a>
		</div>
	</div>

	<div type="hidden" id="groups" data-groups={$groups} />

{/block}

{block name=style_include append}
	<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}

{block name=js_include append}
	<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
	<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
	<script type="text/javascript" src="{$path_js}/administrator/Messages/MessageAuthor/main-menu.js"></script>
{/block}