<h3>Bitte Betrag Eingeben</h3>
<p>Der Benutzer kann maximal noch {$max_amount}&euro; aufladen!</p>
<form action="index.php?section=recharge&{$sid}" method="post">
	<fieldset>
		<label>Betrag</label>
			<input type="text" name="amount" /><br />
	</fieldset>
	<input type="submit" value="Submit" />
</form>