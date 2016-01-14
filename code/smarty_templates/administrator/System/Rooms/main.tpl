{extends file=$inh_path}{block name='content'}

<h3 class="module-header">Raumliste editieren</h3>

<fieldset>
	<legend>Kategorien</legend>
	<table class="table table-responsive table-hover table-striped">
		<thead></thead>
		<tbody>
			<tr>
				<th>ID</th>
				<th>Bezeichnung</th>
			</tr>
			{foreach $rooms as $room}
				<form action="index.php?module=administrator|System|Rooms&action=4" method="post" id="form{$room->getId()}">
					<tr>
						<td><input type="hidden" name="id" value="{$room->getId()}">{$room->getId()}</td>
						<td><input type="text" class="{$room->getId()}" name="name" value="{$room->getName()}" disabled></td>
						<td>
							<button type="button" class="btn btn-info btn-xs edit-room" id="{$room->getId()}">
									<span class="fa fa-edit fa-fw edit{$room->getId()}"></span>
							</button>
							{if !$elawaEnabled->getValue()}
							<button type="button"
								class="btn btn-danger btn-xs delete-room" id="{$room->getId()}">
								<span class="fa fa-trash-o"></span>
							</button>
							{/if}
						</td
					</tr>
				</form>
			{/foreach}
			<tr>
				<td/>
				<td>
					<form action="index.php?module=administrator|System|Rooms&action=3" method="post">
						<input type="text" name="name">
						<button type="submit"
							class="btn btn-info btn-xs add-room">
							Hinzuf&uuml;gen
						</button>
					</form>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

{/block}


{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}


{block name=js_include append}
<script type="text/javascript" src="{$path_js}/administrator/System/Rooms/main.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
{/block}