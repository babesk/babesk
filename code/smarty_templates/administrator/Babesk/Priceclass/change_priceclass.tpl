{extends file=$pcParent}{block name=content}
<form action='index.php?section=Babesk|Priceclass&action=4&where={$ID}' method='post'>
	<input type='hidden' value="{$ID}" name="ID" /> </label><br>
	<label>Name der Preisklasse: {$name}</label><br>
	<label>Zugehörige Gruppe: </label>
		{foreach $groups as $group}
			{if $group['default']}
                {$group['name']}
			{/if}
		{/foreach}<br>
	<label>Preis: <br> <input type='text' value="{$price}" size="5" maxlength="5" name="price" />Euro</label><br>
	<label>Maximale Anzahl an Bestellungen: <br> <input type='text' value="{$max_orders}" size="5" maxlength="5" name="orders" /></label><br>
	<input type="submit" value="bestätigen">
</form>
{/block}