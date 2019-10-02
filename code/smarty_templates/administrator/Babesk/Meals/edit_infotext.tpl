{extends file=$mealParent}{block name=content}
<script type="text/javascript" src="{$path_js}/vendor/ckeditor/ckeditor.js"></script>
<center><h3>Speiseplan Infotexte</h3></center>
<form action="index.php?module=administrator|Babesk|Meals|EditMenuInfotexts"
	method="post" onsubmit="submit()">
	<fieldset>
		<legend>Infotext 1</legend>
		<textarea class="ckeditor" name="infotext1">
			{$infotexts.menu_text1}
		</textarea>
	</fieldset>
	<fieldset>
		<legend>Infotext 2</legend>
		<textarea class="ckeditor" name="infotext2">
			{$infotexts.menu_text2}
		</textarea>
	</fieldset>
	<br> <input id="submit" onclick="submit()" type="submit" value="Speichern" />
</form>

{/block}
