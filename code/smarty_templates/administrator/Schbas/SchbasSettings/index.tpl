{extends file=$schbasSettingsParent}{block name=content}

<h3 class="module-header">Schbas Einstellungen</h3>

<fieldset>
	<legend>Grundeinstellungen</legend>
	<ul class="submodulelinkList">
		<li>
			<a href="index.php?section=Schbas|SchbasSettings&amp;action=editBankAccount">Bankverbindung</a>
		</li>
	</ul>
</fieldset>



<fieldset>
	<legend>Texteinstellungen</legend>

	<ul class="submodulelinkList">
		<li>
			<a href="index.php?section=Schbas|SchbasSettings&amp;action=editCoverLetter">Anschreiben</a>
		</li>
		<li>
			<a href="index.php?section=Schbas|SchbasSettings&amp;action=8">Informationstexte</a>
		</li>
		<li>
			<a href="index.php?section=Schbas|SchbasSettings&amp;action=previewInfoDocs">Vorschau der Informationsschreiben</a>
		</li>
		<!--<li>
			<a href="index.php?section=Schbas|SchbasSettings&amp;action=setReminder">Mahnung</a>
		</li>-->
	</ul>
</fieldset>

	<fieldset>
		<legend>Erweiterte Einstellungen</legend>
		<ul class="submodulelinkList">
			<li>
				<a href="index.php?section=Schbas|SchbasSettings&amp;action=11" class="modal-trigger">Schüler temporär in das kommende Schuljahr versetzen</a>
			</li>
			<li>
				<a href="index.php?section=Schbas|SchbasSettings&amp;action=13">Wahlkurse bearbeiten</a>
			</li>
		</ul>
	</fieldset>


{/block}



