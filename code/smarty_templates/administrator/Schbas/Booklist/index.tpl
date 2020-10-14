{extends file=$booklistParent}{block name=content}

<h3 class="module-header">Bücherlistenmenü</h3>

<fieldset class="smallContainer">
	<legend>
		Standard-Aktionen
	</legend>
	<ul class="submodulelinkList">
		<li><a href="index.php?module=administrator|Schbas|Booklist|ShowBooklist">
			B&uuml;cherliste
		</a></li>
		<li><a href="index.php?section=Schbas|Booklist&action=4">
			B&uuml;cher hinzuf&uuml;gen
		</a></li>
	</ul>
</fieldset>

<fieldset>
	<legend>
		Weiteres
	</legend>
	<ul class="submodulelinkList">
		<li><a href="index.php?section=Schbas|Booklist&action=showBooksFNY">
			B&uuml;cher zeigen, die f&uuml;r n&auml;chstes Jahr behalten werden k&ouml;nnen
		</a></li>
		<li><a href="index.php?section=Schbas|Booklist&action=showBooksBT">
			B&uuml;cher zeigen, die f&uuml;r ein Fach ben&ouml;tigt werden
		</a></li>
	</ul>
</fieldset>

{/block}