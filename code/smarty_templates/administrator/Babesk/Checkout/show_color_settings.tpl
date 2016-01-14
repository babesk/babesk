{extends file=$checkoutParent}{block name=content}

<h2 class="module-header">{t}Farbeinstellungen{/t}</h2>

<form action="index.php?section=Babesk|Checkout&action=5"
	class="simpleForm" method="post">

	<fieldset class="smallContainer">
		<legend>Preisklassen</legend>
		{foreach $pcs as $pc}
			{$pc.name} - <input name="{$pc.pc_ID}" value="{$pc.color}" 
			{literal}class="jscolor{width:101, padding:0, shadow:false, borderWidth:0, backgroundColor:'transparent', insetColor:'#000'}"{/literal}>
			<br>
		{/foreach}
	</fieldset>
	<input type="submit" value="Speichern" />
	
</form>

{/block}

{block name=js_include append}
<script type="text/javascript"
	src="{$path_js}/administrator/Babesk/Checkout/jscolor.js">
</script>
{/block}
