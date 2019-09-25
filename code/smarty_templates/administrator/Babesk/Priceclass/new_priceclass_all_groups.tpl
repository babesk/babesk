{extends file=$pcParent}{block name=content}
<h3>Neue Preisklasse erstellen</h3>
<form action="index.php?section=Babesk|Priceclass&action=1" method="post">
<label>Preis: <input type="text" name="n_price"></label><br>
<label>Maximale Bestellungen: <input type="text" name="n_orders"></label>
<p style="font-size: small;">Diese Standardwerte werden für alle Perisgruppen verwendet, deren Felder
Sie leer lassen.</p><br>
<label>Name der Preisklasse: <input type="text" name="name"></label><br><br>
{foreach $groups as $group} 
	<b>======Gruppe: {$group.name}======</b><br>
	<label>Preis für die Gruppe: <input type="text" name="group_price{$group.ID}" size="5"></label><br><br>
	<label>Maximale Bestellungen: <input type="text" name="group_orders{$group.ID}" size="5"></label><br><br>
{/foreach}
	<input type="submit" name="Hinzufügen">
</form>

{/block}