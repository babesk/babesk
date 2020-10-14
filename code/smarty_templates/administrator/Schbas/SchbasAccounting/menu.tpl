{extends file=$checkoutParent}{block name=content}

<script>
EnableSubmit = function(val)
{
    var sbmt = document.getElementById("sendReminder");

    if (val.checked == true)
    {
        sbmt.disabled = false;
    }
    else
    {
        sbmt.disabled = true;
    }
}
</script>

<h3 class="module-header">Schbasverwaltung Menü</h3>

<fieldset>
	<legend>Antr&auml;ge verwalten</legend>
	<ul class="submodulelinkList">
		<li>
			<a href="index.php?section=Schbas|SchbasAccounting&amp;action=userSetReturnedFormByBarcode">
				Antrag erfassen
			</a>
		</li>
		<li>
			<a href="index.php?section=Schbas|SchbasAccounting&amp;action=userRemoveByID">
				Antrag löschen
			</a>
		</li>
	</ul>
</fieldset>

<fieldset>
	<legend>Finanzen verwalten</legend>
	<ul class="submodulelinkList">
		<li>
			<a href="index.php?module=administrator|Schbas|SchbasAccounting|RecordReceipt">
				Geldeingang erfassen
			</a>
		</li>
		<li>
			<form action="index.php?section=Schbas|SchbasAccounting&action=sendReminder"
				method="post">
				<input type="checkbox" name="TOS" value="agreeReminder"
					onClick="EnableSubmit(this)">
				<input type="submit" id="sendReminder" class="btn btn-default"
					value="Mahnungen versenden" disabled="disabled">
			</form>
		</li>
	</ul>

</fieldset>

<fieldset>
	<legend>Bücherübersichten</legend>
	<form action="index.php?module=administrator|Schbas|SchbasAccounting&action=bookOverview" class="form-inline" method="post">
		<div class="form-group">
			<label class="control-label" for="book-overview-grades">Für:</label>
			<select id="book-overview-grades" class="form-control" name="grade">
				{foreach $grades as $grade}
					<option value="{$grade['ID']}">
						{$grade['gradelevel']}{$grade['label']}
					</option>
				{/foreach}
			</select>
		</div>
		<div class="form-group">
			<button type="submit" name="booksToReturn" class="btn btn-default">
				Noch abzugebende Bücher
			</button>
		</div>
		<div class="form-group">
			<button type="submit" name="booksToLoan" class="btn btn-default">
				Noch auszuleihende Bücher
			</button>
		</div>
	</form>

</fieldset>

<fieldset>
	<legend>Noch abzugebende B&uuml;cher</legend>
	<form action="index.php?section=Schbas|SchbasAccounting&action=remember" method="post">
		<input type="submit" value="Liste mit allen Schülern erstellen">
	</form>
	Liste nach Klassen:<br/>
	{$listOfClasses}
</fieldset>

<fieldset>
	<legend>Noch auszuleihende B&uuml;cher</legend>
	Liste nach Klassen:<br/>
	{$listOfClassesRebmemer}
</fieldset>


{/block}
