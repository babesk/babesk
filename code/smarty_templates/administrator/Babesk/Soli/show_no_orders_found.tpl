{extends file=$soliParent}{block name=content}
<h3 align=center>{$name} - Essenszuschuss f&uuml;r den Zeitraum {$start} - {$end}</h3><br>
{literal}
<style>
td {
	padding-left: 15px;
	padding-right: 15px;
	padding-bottom: 5px;
	padding-top: 5px;
}

</style>
{/literal}
<p class="error">Keine Bestellungen vorhanden!</p>	
{/block}