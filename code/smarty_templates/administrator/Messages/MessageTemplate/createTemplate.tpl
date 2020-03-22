{extends file=$inh_path}
{block name=content}

<script type="text/javascript" src="{$path_js}/vendor/ckeditor/ckeditor.js"></script>

<h2 class="module-header">Neue Vorlage</h2>

<form action="index.php?section=Messages|MessageTemplate&amp;action=addTemplate" method="POST">
	<fieldset class="blockyField">
		<legend>Vorlagendaten</legend>
		<label>Betreff:<input type="text" name="templateTitle" value=""></label><br /><br />
		<label>Text:<textarea class="ckeditor" name="templateText"></textarea></label>
	</fieldset>
	<input type="submit" class="btn btn-success" value="Vorlage hinzufügen" />
</form>
{/block}