<html>
{block name=content}
<h3 align=center>Essenszuschuss f&uuml;r {if $weekMode} {$ordering_date}. KW {$year}{else} {$ordering_date} {$year}{/if}</h3><br>
<p>Differenz zwischen normalem Preis und Soli-Preis gesamt:  <b>{sprintf("%01.2f", $sum)}€</b><p>	
{foreach $SoliOrders as $SoliOrder name=soli}

	<h4 align=center>{$SoliOrder.soli.forename} {$SoliOrder.soli.name}</h4><br>	
	<table style="text-align: center;">
		<thead>
			<tr>
				<th><b>Datum</b></th>
				<th><b>Men&uuml;</b></th>
				<th><b>Preis</b></th>
				<th><b>Eigenanteil</b></th>
				<th><b>Aus Kasse</b></th>
			</tr>
		</thead>
	
		<tbody>
		{foreach $SoliOrder.orders as $order}
			<tr>
				<td>{$order.mealdate|date_format:"%d.%m.%Y"}</td>
				<td>{$order.mealname}</td>
				<td>{sprintf("%01.2f", $order.mealprice)}€</td>
				<td>{sprintf("%01.2f", $order.soliprice)}€</td>
				<td>{sprintf("%01.2f", ($order.mealprice - $order.soliprice))}€</td>
			</tr>
		{/foreach}
		</tbody>
	
	</table>
	<p> Differenz zwischen normalem Preis und Soli-Preis f&uuml;r {$SoliOrder.soli.forename} {$SoliOrder.soli.name}:  <b>{sprintf("%01.2f", $SoliOrder.pricediff)}€</b><p>	

{/foreach}
{/block}

</html>