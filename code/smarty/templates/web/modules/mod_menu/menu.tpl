{include file='web/header.tpl' title='Hauptmen&uuml;'}

<div id="order">
    <h3><a href="index.php?section=order">Bestellen</a></h3>
</div>
<p><b>Bestellungen:</b></p>
{foreach $meal as $meal2}
<p>{$meal2.date}: {$meal2.name}  {if $meal2.cancel}<a href="index.php?section=cancel&id={$meal2.orderID}">Abbestellen</a>{/if}</p>
{/foreach}
{include file='web/footer.tpl'}