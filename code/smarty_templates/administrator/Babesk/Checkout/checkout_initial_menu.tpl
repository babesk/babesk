	{extends file=$checkoutParent}{block name=content}
	<!-- the initial menu-->

	<fieldset>
	<legend><b></b></legend>
	<form action="index.php?section=Babesk|Checkout&action=1" method="post">
		<input type="submit" value="Ausgabe" />
	</form>
	</fieldset>
	<fieldset>
	<legend><b>Einstellungen</b></legend>
	<form action="index.php?section=Babesk|Checkout&action=2" method="post">
		<input type="submit" value="Ausgabe-Einstellungen ver&auml;ndern" />
	</form>
	<form action="index.php?section=Babesk|Checkout&action=4" method="post">
		<input type="submit" value="Farbkennzeichnung anpassen" />
	</form>
	</fieldset>
	<br><br>
	{/block}
