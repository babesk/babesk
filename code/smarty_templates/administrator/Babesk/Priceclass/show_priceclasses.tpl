{extends file=$pcParent}{block name=content}
<table cellpadding='10' cellspacing='10'>
	<thead> 
		<tr>
			<th align="center">Preisklassenname</th>
			<th align="center">zugehörige Gruppe</th>
			<th align="center">Preis</th>
			<th align="center">Maximale Bestellungen pro Benutzer</th>
		</tr>
	</thead>
	
	<tbody>
	{foreach $priceclasses as $priceclass}
		<tr>
			<td align="center">{$priceclass['name']}</td>
			<td align="center">{$priceclass['group_name']}</td>
			<td align="center">{$priceclass['price']} Euro</td>
			<td align="center">{$priceclass['orders_per_user']}</td>
			<td align="center"><form action='index.php?section=Babesk|Priceclass&action=3&where={$priceclass['ID']}' method='post'>
				<input type="submit" value='löschen'>
			</form></td>
			<td align="center"><form action='index.php?section=Babesk|Priceclass&action=4&where={$priceclass['ID']}' method='post'>
				<input type="submit" value='ändern'>
			</form></td>
		</tr>
	{/foreach}
	</tbody>

</table>
{/block}