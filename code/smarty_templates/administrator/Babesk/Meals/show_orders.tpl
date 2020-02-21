{extends file=$mealParent}{block name=content}
{literal}
<script type="text/javascript">

function switchInfo(divName) {	
	if(document.getElementById(divName).style.display == 'inline')
		document.getElementById(divName).style.display = 'none';
	else
		document.getElementById(divName).style.display = 'inline';
}

</script>
{/literal}

	<form action="index.php?section=Babesk|Meals&amp;action=3" method="post">
	<div class="form-group container">
		<label>Zeitraum der Ausgabe</label>
		<div class="row">
			<div class="col-sm-auto">
				<input id="meal-date" name="date" class="datepicker"
					   data-date-format="dd.mm.yyyy" value="{$ordering_date}" size="10" />
			</div>
			<div class="col-sm-auto">
			<b>bis</b>
			</div>
			<div class="col-sm-auto">
				<input id="meal-date" name="date-end" class="datepicker"
					   data-date-format="dd.mm.yyyy" value={$end_date} size="10" />
			</div>
		</div>
	</div>
	<input type="submit" name="show" value="Anzeigen" />
	<input type="submit" name="pdf" value="PDF erzeugen" />
	</form>

<h3>Anzahl der Bestellungen f&uuml;r den Zeitraum: {$ordering_date} bis {$end_date}</h3>

	{foreach $num_orders as $num_order} <h4>{$num_order.name} hat {$num_order.number} Bestellungen:</h4>
		{foreach $num_order.user_groups as $group}
		<p style="margin-left: 10%">
			Gruppe <b>{$group.name}</b> hat <b>{$group.counter}</b> mal bestellt.
		</p>
		{/foreach}
	

	<br> {/foreach}


<a href="javascript:switchInfo('orderTable')"><h3>Bestell-Tabelle anzeigen</h3></a>
<div id="orderTable" style="display: none;">
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

{block name=style_include append}

	<link rel="stylesheet" type="text/css" href="{$path_css}/datepicker3.css">

{/block}

{block name=js_include append}

	<script type="text/javascript" src="{$path_js}/vendor/datepicker/bootstrap-datepicker.min.js">
	</script>

	<script type="text/javascript">
        $(document).ready(function() {
            $('.datepicker').datepicker({
                'daysOfWeekDisabled': [0,6]
            });
        });
	</script>

{/block}