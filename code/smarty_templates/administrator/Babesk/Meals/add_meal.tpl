<!-- str-ifs looks up if some fields should be filled out beforehand -->
{extends file=$base_path}{block name=content}

<h3 class="module-header">Mahlzeit hinzufügen</h3>

<form action="index.php?section=Babesk|Meals&amp;action=1" method="post">

	<div class="form-group">
		<label for="meal-name">Name der Mahlzeit</label>
		<input type="text" id="meal-name" class="form-control" name="name"
			size="40" {if $name_str}value="{$name_str}"{/if}>
	</div>
	<div class="form-group">
		<label for="meal-description">Beschreibung</label>
		<textarea id="meal-description" class="form-control" name="description"
			cols="40" rows="10" >{if $descr_str}{$descr_str}{/if}</textarea>
	</div>
	<div class="row">
		<div class="form-group col-sm-6">
			<label for="meal-price-class">Preisklasse</label>
			<select id="meal-price-class" class="form-control" name="price_class">
				{if $pc_str}
					{html_options values=$price_class_id output=$price_class_name selected=$pc_str}
				{else}
					{html_options values=$price_class_id output=$price_class_name selected="1"}
				{/if}
			</select>
		</div>
		<div class="form-group col-sm-6">
			<label for="max-orders">Maximale Bestellungen</label>
				<input type="text" id="max-orders" class="form-control" name="max_orders"
					maxlength="5" size="5"
					{if $max_order_str}value="{$max_order_str}"{else}value="999"{/if} />
		</div>
	</div>
	<div class="form-group">
		<label for="meal-date">Tag der Ausgabe</label>
		<input id="meal-date" name="meal-date" class="datepicker form-control"
			data-date-format="dd.mm.yyyy" />
	</div>
	<input class="btn btn-default" type="submit" value="Hinzuf&uuml;gen" />
</form>

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