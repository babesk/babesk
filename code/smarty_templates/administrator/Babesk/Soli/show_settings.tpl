{extends file=$base_path}{block name=content}

<h2 class="module-header">{t}Soli-Settings{/t}</h2>

<form action="index.php?module=administrator|Babesk|Soli|Settings"
	class="simpleForm" method="post">

	<!--
	<fieldset class="smallContainer">
		<legend>{t}Enable Soli{/t}</legend>
		<div class="simpleForm">
			<label for="soliEnabled">
				{t}Is the Soli-Module enabled:{/t}
			</label>
			<input id="soliEnabled" class="inpuItem" type="checkbox"
				name="soliEnabled" {if $soliEnabled}checked="checked"{/if} />
		</div>
	</fieldset>
	-->

	<fieldset class="smallContainer">
		<legend>
			{t}Soliprice{/t}
		</legend>

		<div class="simpleForm">
			<label for="solipriceEnabled">
				{t}Is Soliprice Enabled:{/t}
			</label>
			<input id="solipriceEnabled" class="inputItem" type="checkbox"
				name="solipriceEnabled"
				{if $solipriceEnabled}checked="checked"{/if} />
		</div>
		<div class="simpleForm">
			Nutze seperate Betr&auml;ge f&uuml;r mehrere Preisklassen?
			<input id="toggleSeperatePrices" type="checkbox"
				name="toggleSeperatePrices" data-on-text="Ja"
				data-off-text="Nein" data-on-color="success"
				data-off-color="danger" {if $seperate}checked{/if}>
		</div>

		<div class="simpleForm" id="onePrice">
			<label for="soliprice">
				{t}Amount to Pay:{/t}
			</label>
			<input id="soliprice" class="inputItem" type="text" maxlength="5"
				size="5" name="soliprice" value="{$soliprice|number_format:2}" /> €
		</div>
		<div class="simpleForm" id="seperatePrice">
			<b>Zu bezahlende Betr&auml;ge:</b><br>
			{foreach $priceclasses as $priceclass}
			<label for="soliprice{$priceclass['pc_id']}">
				{$priceclass['name']}
			</label>
			<input id="soliprice{$priceclass['pc_ID']}" class="inputItem" type="text" maxlength="5"
				size="5" name="soliprice{$priceclass['pc_ID']}" value="{$priceclass['soliprice']|number_format:2}" /> €<br>
			{/foreach}
		</div>
	</fieldset>
	<input type="submit" value="{t}Commit Changes{/t}" />
</form>

{/block}

{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}


{block name=js_include append}
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js">
</script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>

<script type="text/javascript">
$(document).ready(function() {
{if $seperate}
$('#onePrice').hide();
{else}
$('#seperatePrice').hide();
{/if}
});
$('#toggleSeperatePrices').bootstrapSwitch();
$('#toggleSeperatePrices').on('switchChange.bootstrapSwitch', function(event, state) {
    $('#onePrice').toggle();
    $('#seperatePrice').toggle();
  });

</script>

{/block}
