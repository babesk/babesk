{extends file=$mealParent}{block name=content}

	<h3>Mahlzeiten</h3>

	<!-- the initial menu-->
	<form action="index.php?section=Babesk|Meals&action=1" method="post">
		<input class="btn btn-default" type="submit" value="Eine neue Mahlzeit erstellen" />
	</form>
	<br />
	<form action="index.php?section=Babesk|Meals&action=2" method="post">
		<input class="btn btn-default" type="submit" value="Die Mahlzeiten anzeigen" />
	</form>
	<br />
	<form action="index.php?section=Babesk|Meals&action=3" method="post">
		<input class="btn btn-default" type="submit" value="Die Bestellungen anzeigen" />
	</form>
	<br />
	<form action="index.php?module=administrator|Babesk|Meals|EditMenuInfotexts"
		  method="post">
		<input class="btn btn-default" type="submit" value="Infotexte f&uuml;r Speiseplan editieren" />
	</form>
	<br />
	<form action="index.php?module=administrator|Babesk|Meals|MaxOrderAmount"
		  method="post">
		<input class="btn btn-default" type="submit" value="Maximale Anzahl Bestellungen pro Tag setzen" />
	</form>
	<br />
	<form action="index.php?section=Babesk|Meals&action=4" method="post">
		<input class="btn btn-danger" type="submit" value="Alte Mahlzeiten und Bestellungen löschen" />
	</form>
{/block}
