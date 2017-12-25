{extends file=$soliParent}{block name=content}
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
				<td>{sprintf("%01.2f", $order.price)}€</td>
				<td>{sprintf("%01.2f", $order.soliprice)}€</td>
				<td>{sprintf("%01.2f", ($order.price - $order.soliprice))}€</td>
			</tr>
        {/foreach}
		</tbody>

	</table>

	<table style="text-align: center;" class="table table-responsive table-striped">
		<thead>
		<tr>
			<th >Name</th>
			<th>Betrag</th>
		</tr>
		</thead>

		<tbody>
        {foreach $charges as $charge}
			<tr>
				<td>{$charge.name}</td>
				<td>{sprintf("%01.2f", $charge.amount)}€</td>
				</tr>
        {/foreach}
		</tbody>

	</table>
{/block}