{extends file=$ReligionParent}{block name=content}
<h2 class="module-header">Bitte Buch scannen</h2>
<form action="index.php?section=System|Religion&action=5" method="post">
	<fieldset>
		<textarea name="bookcodes" cols="50" rows="10"></textarea><br />
		<input type="hidden" name="uid" value="{$uid}" /><br />
	</fieldset>
	<input type="submit" class="btn btn-primary" value="Senden" />
</form>
{/block}