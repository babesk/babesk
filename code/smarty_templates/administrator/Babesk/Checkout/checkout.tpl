{extends file=$checkoutParent}{block name=content}

<style type="text/css">

a.nextOrder {
	padding: 10px 10px 15px 10px;
	margin-top: 10px;
	border-radius: 5px;
	border: 1px solid #006699;
}

a.pull-right{
	background: transparent;
	font-size: 0px;
	border: none;
}
	
td, th  { 
	border: 1px solid; 
}

table{
	min-width: 100%;
}

</style>

	<table style="text-align: center;">
		<thead>
			<tr>
				<th align="center"><b>Name</b></th>
				<th align="center"><b>Men&uuml;</b></th>
				<th align="center"><b>Gericht</b></th>
			</tr>
		</thead>
	
		<tbody>
		{foreach $orders as $order}
			<tr style="background-color:#{$order.color}">
				<td align="center">{$order.name}</td>
				<td align="center">{$order.menu}</td>
				<td align="center">{$order.meal}</td>
				
			</tr>
		{/foreach}
		</tbody>
	
	</table>
<p>

<form action="index.php?section=Babesk|Checkout&action=1&{$sid}" method="post">
	<fieldset>
		<legend>Karte</legend>
		<label>ID</label>
			<input type="text" name="card_ID" size="10" maxlength="10"
				autofocus />
			<br />
	</fieldset>
	<input type="submit" value="Submit" />
</form>
{/block}
