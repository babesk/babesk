{extends file=$mealParent}{block name=content}

<form action="index.php?section=Babesk|Meals&amp;action=3" method="post">
	<!--<label>Tag:<input type="text" name="ordering_day" maxlength="2" size="2" value={$today.day} /></label>
	<label>Monat:<input type="text" name="ordering_month" maxlength="2" value={$today.month} size="2" /></label>
	<label>Jahr:<input type="text" name="ordering_year" maxlength="4" value={$today.year} size="4" /></label><br>-->
	<div class="form-group">
		<label for="meal-date">Tag der Ausgabe</label>
		<input id="meal-date" name="date" class="datepicker form-control"
			   data-date-format="dd.mm.yyyy" size="10" />
	</div>
	<input type="submit" name="show" value="Anzeigen" />
	<input type="submit" name="pdf" value="PDF erzeugen" />
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