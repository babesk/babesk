{extends file=$mealParent}{block name=content}

<h3>Mahlzeiten</h3>

<!-- the initial menu-->
	<fieldset>
		<legend><b>Mahlzeitenverwaltung</b></legend>
		<ul class="submodulelinkList">
			<li>
				<a href="index.php?section=Babesk|Meals&action=1">Neue Mahlzeit erstellen</a>
			</li>
			<li>
				<a href="index.php?section=Babesk|Meals&action=2">Mahlzeiten bearbeiten</a>
			</li>
			<li>
				<a href="index.php?section=Babesk|Meals&action=3">Bestellungen anzeigen</a>
			</li>
		</ul>
	</fieldset>
	<fieldset>
		<legend><b>Erweiterte Einstellungen</b></legend>
		<ul class="submodulelinkList">
			<li>
				<a href="index.php?module=administrator|Babesk|Meals|EditMenuInfotexts">Infotexte</a>
			</li>
			<li>
				<a href="index.php?module=administrator|Babesk|Meals|MaxOrderAmount">Maximale Anzahl an Bestellungen</a>
			</li>
			<li>
				<a href="index.php?section=Babesk|Meals&action=4">Alte Mahlzeiten löschen</a>
			</li>
		</ul>
	</fieldset>
{/block}
