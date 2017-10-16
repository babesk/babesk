{extends file=$soliParent}{block name=content}
<h3 align=center>{$name} - Essenszuschuss für den Zeitraum {$start} - {$end}</h3><br>
{literal}
<style>
td {
	padding-left: 15px;
	padding-right: 15px;
	padding-bottom: 5px;
	padding-top: 5px;
}

</style>
{/literal}
<table style="text-align: center;" class="table table-responsive table-striped">
	<thead>
		<tr>
			<th >Datum</th>
			<th>Men&uuml;</th>
			<th>Preis</th>
			<th>Eigenanteil</th>
			<th>Aus Kasse</th>
		</tr>
	</thead>
	
	<tbody>
	{foreach $orders as $order}
		<tr>
			<td>{$order.mealdate|date_format:"%d.%m.%Y"}</td>
			<td>{$order.mealname}</td>
			<td>{sprintf("%01.2f", $order.mealprice)}€</td>
			<td>{sprintf("%01.2f", $order.soliprice)}€</td>
			<td>{sprintf("%01.2f", ($order.mealprice - $order.soliprice))}€</td>
		</tr>
	{/foreach}
		<tr>
			<td><b>Summe</b></td>
			<td></td>
			<td>{sprintf("%01.2f", $sum[0])}€</td>
			<td>{sprintf("%01.2f", $sum[1])}€</td>
			<td>{sprintf("%01.2f", $sum[2])}€</td>
		</tr>
	</tbody>
	
</table>
	{/block}