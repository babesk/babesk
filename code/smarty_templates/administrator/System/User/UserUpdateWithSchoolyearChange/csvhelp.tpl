{extends file=$base_path}{block name=content}

<h2 class="module-header">{t}Help for formatting the csv-file to update users{/t}</h2>

<p>

<fieldset>
	<legend>Übersicht</legend>
	Sie können die Nutzer mithilfe einer Spreadsheet-Datei importieren.
	Dies können beispielsweise in Microsoft Excel erstellt werden.
	Der Inhalt der Zellen in der ersten Zeile bestimmt, welche Daten in dieser Spalte stehen.
	Jede darauf folgende Reihe enthält Daten zu jeweils einem Schüler.
</fieldset>

<fieldset>
	<legend>Benennung der Spalten</legend>
	<table class="table">
		<tbody>
			<tr>
				<td>
					forename
				</td>
				<td>
					Der Vorname des Benutzers (Notwendig)
				</td>
			</tr>
			<tr>
				<td>
					name
				</td>
				<td>
					Der Nachname des Benutzers (Notwendig)
				</td>
			</tr>
			<tr>
				<td>
					grade
				</td>
				<td>
					Die Klasse, in die der Schüler im neuen Schuljahr geht (Notwendig)
				</td>
			</tr>
			<tr>
				<td>
					birthday
				</td>
				<td>
					Der Geburtstag des Benutzers. Optional, aber wenn Benutzer bereits Geburtstage zugewiesen bekommen haben, sind sie nötig, da daran der Benutzer erkannt wird!
				</td>
			</tr>
			<tr>
				<td>
					telephone
				</td>
				<td>
					Die Telefonnummer des Benutzers.
					Existiert der Nutzer bereits, wird die bestehende Telefonnummer damit überschrieben.(optional)
				</td>
			</tr>
			<tr>
				<td>
					username
				</td>
				<td>
					Der neue Nutzername des Nutzers.
					Existiert der Nutzer bereits, wird der bestehende Benutzername damit überschrieben. (optional)
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

<fieldset>
	<legend>Beispiel</legend>
	<table class="table table-bordered">
		<tbody>
			<tr>
				<td>
					forename
				</td>
				<td>
					name
				</td>
				<td>
					grade
				</td>
				<td>
					birthday
				</td>
			</tr>
			<tr>
				<td>
					Hans
				</td>
				<td>
					Mustermann
				</td>
				<td>
					7b
				</td>
				<td>
					12.3.1987
				</td>
			</tr>
			<tr>
				<td>
					Peter
				</td>
				<td>
					Müller
				</td>
				<td>
					10c
				</td>
				<td>
					9.1.2001
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

<fieldset>
	<legend>Beispiel mit Schbas-Änderungen</legend>
	<table class="table table-bordered">
		<tbody>
			<tr>
				<td>
					forename
				</td>
				<td>
					name
				</td>
				<td>
					grade
				</td>
				<td>
					birthday
				</td>
			</tr>
			<tr>
				<td>
					Hans
				</td>
				<td>
					Mustermann
				</td>
				<td>
					7b
				</td>
				<td>
					12.3.1987
				</td>
				<td>

				</td>
				<td>

				</td>
				<td>
					CH,PH
				</td>
			</tr>
			<tr>
				<td>
					Peter
				</td>
				<td>
					Müller
				</td>
				<td>
					10c
				</td>
				<td>
					9.1.2001
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

<a href="index.php?module=administrator|System|User|UserUpdateWithSchoolyearChange|NewSession"
	class="btn btn-default pull-right">
	Zurück
</a>

{/block}