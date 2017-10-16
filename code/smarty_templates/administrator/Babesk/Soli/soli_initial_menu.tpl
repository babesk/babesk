	{extends file=$soliParent}{block name=content}
	<!-- the initial menu-->

	<fieldset>
	<legend><b>Gutscheinverwaltung</b></legend>
		<ul class="submodulelinkList">
			<li>
				<a href="index.php?section=Babesk|Soli&action=1">Coupon erstellen</a>
			</li>
			<li>
				<a href="index.php?section=Babesk|Soli&action=2">Coupons anzeigen</a>
			</li>
		</ul>
	</fieldset>
	<fieldset>
	<legend><b>Benutzer</b></legend>
		<ul class="submodulelinkList">
			<li>
				<a href="index.php?section=Babesk|Soli&action=3">Soli-Nutzer anzeigen</a>
			</li>
			<li>
				<a href="index.php?section=Babesk|Soli&action=8">Alle Bestellungen als PDF ausgeben</a>
			</li>
			<li>
				<a href="index.php?section=Babesk|Soli&action=4">Bestellung für einen Nutzer in einer Woche anzeigen</a>
			</li>
			<li>
				<a href="index.php?section=Babesk|Soli&action=7">Bestellungen r&uuml;ckwirkend &uuml;bernehmen</a>
			</li>
		</ul>
	</fieldset>
	<fieldset>
	<legend><b>Einstellungen</b></legend>
		<ul class="submodulelinkList">
			<li>
				<a href="index.php?module=administrator|Babesk|Soli|Settings">Einstellungen</a>
			</li>
		</ul>
	</fieldset>
	<br><br>
	{/block}
