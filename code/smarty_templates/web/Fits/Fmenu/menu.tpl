{extends file=$inh_path}{block name=content}
<div class="col-md-8 col-md-offset-2">
	{if $hasFits}
	<div id="order">
	    <h3><a href="index.php?section=Fits|Zeugnis">Zertifikat &ouml;ffnen</a></h3>
	</div>

	{elseif $showTestlink}
	<div id="order">
	    <h3><a href="index.php?section=Fits|Quiz">Zum Onlinetest</a></h3>
	</div>
	{else}
	<div class="alert alert-info" id="order">
		<b>Niemand hier!</b>
		Der F&uuml;hrerschein f&uuml;r IT-Systeme wird im 6. Jahrgang erworben und ist f&uuml;r den Zugriff auf die Bibliotheksrechner notwendig.
	</div>
	{/if}
</div>
<div class="col-md-2"></div>

{/block}