{extends file=$base_path}{block name=content}
Geben Sie bitte das Datum ein, wofür sie die bisher eingegangenen Bestellungen mit Teilhabepaket angezeigt haben möchten:<br><p>

	<fieldset>
<form action="index.php?section=Babesk|Soli&amp;action=8" method="post">
	<legend>Kalenderwoche</legend>
	<select name="ordering_kw">
	{section name=i loop=52}
	<option value="{{$smarty.section.i.index}+1}"  {if {{$smarty.section.i.index}+1} == {date('W')}} selected{/if}> {{$smarty.section.i.index}+1} </option>
	{/section}
	</select> <br> Aktuell: Kalenderwoche {date('W')} <br>
 
	<input type="submit" value="PDF generieren" />
</form><br>
	</fieldset>
<p><b> ODER </b></p>
	<fieldset>
<form action="index.php?section=Babesk|Soli&amp;action=8" method="post">
	<legend>Monat</legend>
	<select name="ordering_month">
	{section name=i loop=12}
	<option value="{{$smarty.section.i.index}+1}"  {if {{$smarty.section.i.index}+1} == {date('W')}} selected{/if}> {{$smarty.section.i.index}+1} </option>
	{/section}
	</select> <br>Aktuell: Monat {date('m')}({date('M')}) <br>
 
	<input type="submit" value="PDF generieren" />
</form>
	</fieldset>

{/block}

<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
