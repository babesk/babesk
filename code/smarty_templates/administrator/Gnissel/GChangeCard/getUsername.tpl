{extends file=$base_path}{block name=content}
<h3>Benutzername eingeben</h3>
<form action="index.php?section=Gnissel|GChangeCard" method="post">
	<fieldset>
		<legend>Benutzername (mit Punkt!)</legend>
		<label>Benutzername</label>
			<input type="text" name="username" size="50" maxlength="50" /><br />
	</fieldset>
	<input type="submit" value="Senden" />
</form>

<script type="text/javascript">
$(document).ready(function() {
	$('input[name=username]').focus();
});
</script>
{/block}
