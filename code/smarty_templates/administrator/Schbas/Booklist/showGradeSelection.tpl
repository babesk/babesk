{extends file=$booklistParent}{block name=content}
<h3>B&uuml;cher, die f&uuml;r n&auml;chstes Schuljahr behalten werden k&ouml;nnen</h3>
<form action="index.php?section=Schbas|Booklist&action=showBooksFNY"	method="post">



		<label>Bitte Jahrgang ausw&auml;hlen:
			<select id="grade" name="grade">
					<option value="5" selected>5</option>
					<option value="6">6</option>
					<option value="7">7</option>
					<option value="8">8</option>
					<option value="9">9</option>
					<option value="10">10</option>
					<option value="11">11</option>
					<option value="12">12</option>
			</select>
		</label>


	<input id="submit"type="submit" value="Buchliste herunterladen" />
</form>

{/block}
