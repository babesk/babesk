{extends file=$mealParent}{block name=content}

<form action="index.php?section=Babesk|Meals&amp;action=3" method="post">
	<div class="form-group container">
		<label>Zeitraum der Ausgabe</label>
		<div class="row">
			<div class="col-sm-auto">
				<input id="meal-date" name="date" class="datepicker"
					   data-date-format="dd.mm.yyyy" size="10" />
			</div>
			<div class="col-sm-auto">
			<b>bis</b>
			</div>
			<div class="col-sm-auto">
				<input id="meal-date" name="date-end" class="datepicker"
					   data-date-format="dd.mm.yyyy" size="10" />
			</div>
		</div>
	</div>
	<input type="submit" name="show" value="Anzeigen" />
	<input type="submit" name="pdf" value="PDF erzeugen" />

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