{extends file=$inh_path} {block name='content'}

<h2 class='moduleHeader'>Einen Kurs hinzufügen</h2>

<form action='index.php?section=Kuwasys|Classes&action=addClass' method='post'>

	<label>Bezeichner: <input type='text' name='label'></label><br><br>
	<label>maximale Registrierungen: <input type='text' name='maxRegistration'></label><br><br>
	<label>Zu welchem Schuljahr gehört der Kurs? 
	<select name='schoolYear' size='1'>
		{foreach $schoolYears as $schoolYear}
			<option 
				value='{$schoolYear.ID}' 
				{if $schoolYear.active}selected='selected'{/if}>
				{$schoolYear.label}
			</option>
		{/foreach}
	</select>
	</label><br><br>
	<label>Veranstaltungstag des Kurses:
	<select name='weekday' size='1'>
		<option value='Mon'>Montag</option>
		<option value='Tue'>Dienstag</option>
		<option value='Wed'>Mittwoch</option>
		<option value='Thu'>Donnerstag</option>
		<option value='Fri'>Freitag</option>
		<option value='Sat'>Samstag</option>
		<option value='Sun'>Sonntag</option>
	</select>
	</label><br><br>
	<label>Registrierungen für Schüler ermöglichen: <input type="checkbox" name="allowRegistration" value="1" checked="checked"></label><br><br>
	<input type='submit' value='Kurs hinzufügen'>
</form>

{/block}