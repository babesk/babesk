{extends file=$booklistParent}{block name=content}
<h3>B&uuml;cher, die f&uuml;r ein Fach ben&ouml;tigt werden</h3>
<form action="index.php?section=Schbas|Booklist&action=showBooksBT"	method="post">



		<label>Bitte Fach ausw&auml;hlen:
			<select id="topic" name="topic">
					<option value="DE" selected>DE</option>
					<option value="EN">EN</option>
					<option value="FR">FR</option>
                                        <option value="RU">RU</option>
                                        <option value="LA">LA</option>
                                        <option value="KU">KU</option>
                                        <option value="MU">MU</option>
                                        <option value="GE">GE</option>
                                        <option value="EK">EK</option>
                                        <option value="PO">PO</option>
                                        <option value="RE">RE</option>
                                        <option value="WN">WN</option>
                                        <option value="MA">MA</option>
                                        <option value="PH">PH</option>
                                        <option value="BI">BI</option>
                                        <option value="CH">CH</option>
                                        <option value="IF">IF</option>
                                        
			</select>
		</label>


	<input id="submit"type="submit" value="Buchliste herunterladen" />
</form>

{/block}
