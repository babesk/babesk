{extends file=$base_path}{block name=content}
Geben Sie bitte das Datum ein, wofür sie die bisher eingegangenen Bestellungen mit Teilhabepaket angezeigt haben möchten:<br><p>

<form action="index.php?section=Babesk|Soli&amp;action=8" method="post">
	Kalenderwoche:<select name="ordering_kw">
	{section name=i loop=52}
	<option value="{{$smarty.section.i.index}+1}"  {if {{$smarty.section.i.index}+1} == {date('W')}} selected{/if}> {{$smarty.section.i.index}+1} </option>
	{/section}
	</select> (Aktuell: Kalenderwoche {date('W')}) <br>
 
	<input type="submit" value="PDF generieren" />
</form><br>
<p><b> ODER </b></p>
<form action="index.php?section=Babesk|Soli&amp;action=8" method="post">
	Monat:<select name="ordering_month">
	{section name=i loop=12}
	<option value="{{$smarty.section.i.index}+1}"  {if {{$smarty.section.i.index}+1} == {date('W')}} selected{/if}> {{$smarty.section.i.index}+1} </option>
	{/section}
	</select> (Aktuell: Monat {date('m')}({date('M')}) ) <br>
 
	<input type="submit" value="PDF generieren" />
</form>

{/block}