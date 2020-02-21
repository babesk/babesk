<html>
{block name=content}
<h3>Anzahl der Bestellungen f&uuml;r den Zeitraum:<br> {$ordering_date} bis {$end_date}</h3>

	{foreach $num_orders as $num_order} <h4>{$num_order.name} hat {$num_order.number} Bestellungen:</h4>
		{foreach $num_order.user_groups as $group}
		<p style="margin-left: 10%">
			Gruppe <b>{$group.name}</b> hat <b>{$group.counter}</b> mal bestellt.
		</p>
		{/foreach}
	

	<br> {/foreach}

	<div id="orderTable">
		<table style="text-align: center; width: 100%">
			<thead>
			<tr bgcolor="#aadd33">
				<th>Datum</th>
				<th>Men&uuml;</th>
				<th>Person</th>
				<th>Status</th>
			</tr>
			</thead>

			<tbody>
            {foreach $orders as $order}
				<tr bgcolor="#e7e7e7">
					<td>{$order.date}</td>
					<td>{$order.meal_name}</td>
					<td>{$order.user_name}</td>
					<td style="text-align: center;">{$order.is_fetched}</td>
				</tr>
            {/foreach}
			</tbody>
		</table>
	</div>
{/block}
</html>