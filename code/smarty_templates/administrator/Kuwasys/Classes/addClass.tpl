{extends file=$inh_path} {block name='content'}

<h2 class='module-header'>Einen Kurs hinzufügen</h2>

<form action='index.php?module=administrator|Kuwasys|Classes|AddClass'
	role="form" method='post'>

	<div class="form-group input-group" data-toggle="tooltip" title="Kursname">
		<span class="input-group-addon">
			<span class="fa fa-newspaper-o"></span>
		</span>
		<input class="form-control" type="text" name="label"
			placeholder="Kursname" required />
	</div>
	<div class="form-group input-group" data-toggle="tooltip"
		title="Kursbeschreibung">
		<span class="input-group-addon">
			<span class="fa fa-clipboard fa-fw"></span>
		</span>
		<textarea class="form-control" type="text" name="description"
			placeholder="Kursbeschreibung" rows="3" required></textarea>
	</div>

	<div class="row">
		<div class="col-sm-6">
			<div class="form-group input-group" data-toggle="tooltip"
				title="Maximale Registrierungen">
				<span class="input-group-addon">
					<span class="fa fa-th-list fa-fw"></span>
				</span>
				<input class="form-control" type="text" name="maxRegistration"
					placeholder="maximale Registrierungen" required />
			</div>
		</div>
		<div class="col-sm-6">
			<div class="form-group input-group"
				data-toggle="tooltip" title="Zu welchem Schuljahr der Kurs gehört">
				<span class="input-group-addon">
					<span class="fa fa-calendar fa-fw"></span>
				</span>
				<select class="form-control" name='schoolyear'>
					{foreach $schoolyears as $schoolyear}
						<option
							value='{$schoolyear.ID}'
							{if $schoolyear.active}selected='selected'{/if}>
							{$schoolyear.label}
						</option>
					{/foreach}
				</select>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-sm-6">
			<div class="form-group input-group"
				data-toggle="tooltip" title="Der Veranstaltungszeitpunkt des Kurses">
				<span class="input-group-addon">
					<span class="fa fa-clock-o"></span>
				</span>
				<select class="form-control" name='classunit'>
					{foreach $classunits as $classunit}
					<option value='{$classunit.ID}'>{$classunit.translatedName}</option>
					{/foreach}
				</select>
				<span class="input-group-btn">
					<button class="additional-category btn btn-default"
						type="button" data-toggle="tooltip" title="Weiterer Zeitpunkt">
						<span class="fa fa-plus"></span>
					</button>
				</span>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-sm-6 form-group">
			<div class="input-column" data-toggle="tooltip"
			data-title="Schüler können sich nur dann anmelden wenn sowohl kursspezifische Registrierungen als auch die globalen Kursregistrierungen aktiviert sind." >
				<label>Registrierungen für diesen Kurs ermöglichen:</label>
				<input type="checkbox" id="allow-registration" name="allowRegistration"
					value="1" checked="checked" data-off-text="Nein" data-on-text="Ja"
					data-off-color="warning" />
			</div>
		</div>
	</div>
	<input class="btn btn-primary" type='submit' value='Kurs hinzufügen'>

</form>

{/block}


{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}


{block name=js_include append}
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>

<script>

$(document).ready(function() {
	$('#allow-registration').bootstrapSwitch();
});

</script>
{/block}