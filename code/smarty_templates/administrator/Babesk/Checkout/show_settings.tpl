{extends file=$base_path}{block name=content}

<h2 class="module-header">{t}Ausgabe-Settings{/t}</h2>

<form action="index.php?module=administrator|Babesk|Checkout&action=3"
	class="simpleForm" method="post">

	<fieldset class="smallContainer">
		<legend>Einstellungen</legend>
		Anzahl an anzuzeigenden Bestellungen:
		<input id="count_last_meals" class="inputItem" type="text" maxlength="5"
				size="5" name="count_last_meals" value="{$count}" /> 
	</fieldset>
	<input type="submit" value="Speichern" />
	
</form>

{/block}
