{extends file=$inh_path}{block name=content}

<div class="panel panel-danger">
	<div class="panel-heading">
		Buch löschen
	</div>
	<div class="panel-body">
		<p>Wollen sie das Buch {$book['title']} wirklich löschen?</p>
		{if $hasInventory}
			<p>
				<b>Warnung:</b>
				Das Buch hat noch zugehörige Exemplare sowie Ausleihen.
				Sie werden ebenfalls unwiederbringlich mitgelöscht!
			</p>
		{/if}
	</div>
	<div class="panel-footer">
		<form align="center" action="index.php?section=Schbas|Booklist&amp;action=3&amp;ID={$book['id']}" method="post">
			<input type="submit" class="btn btn-danger pull-right"
				value="Ja, ich möchte das Buch löschen" name="delete">
		</form>
		<a href="index.php?module=administrator|Schbas|Booklist"
			class="btn btn-default">
			Nein, ich möchte das Buch nicht löschen
		</a>
	</div>
</div>

{/block}