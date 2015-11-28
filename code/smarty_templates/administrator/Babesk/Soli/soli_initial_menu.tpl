	{extends file=$soliParent}{block name=content}
	<!-- the initial menu-->

	<fieldset>
	<legend><b>Gutscheinverwaltung</b></legend>
	<form action="index.php?section=Babesk|Soli&action=1" method="post">
		<input type="submit" value="Ein neuen Coupon für einen Benutzer hinzufügen." />
	</form>
	<form action="index.php?section=Babesk|Soli&action=2" method="post">
		<input type="submit" value="Gutscheine Anzeigen" />
	</form>
	</fieldset>
	<fieldset>
	<legend><b>Benutzer</b></legend>
	<form action="index.php?section=Babesk|Soli&action=3" method="post">
		<input type="submit" value="Soli-Benutzer anzeigen" />
	</form>
	<form action="index.php?section=Babesk|Soli&action=8" method="post">
		<input type="submit" value="Alle Bestellungen als PDF ausgeben" />
	</form>
	<form action="index.php?section=Babesk|Soli&action=4" method="post">
		<input type="submit" value="Bestellungen eines Benutzers für eine Bestimmte Woche anzeigen" />
	</form>
	<form action="index.php?section=Babesk|Soli&action=7" method="post">
		<input type="submit" value="Bestellungen r&uuml;ckwirkend &uuml;bernehmen" />
	</form>
	</fieldset>
	<fieldset>
	<legend><b>Einstellungen</b></legend>
	<form action="index.php?module=administrator|Babesk|Soli|Settings"
			method="post">
		<input type="submit" value="Die Soli-Einstellungen verändern" />
	</form>
	</fieldset>
	<br><br>
	{/block}
