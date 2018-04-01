{extends file=$SpecialCourseParent}{block name=content}
{block name=search}
<form action="index.php?section=System|SpecialCourse&action=3" method="post"><input type='text' name='user_search'><input type='submit' value='Mit Benutzernamen oder Kartennummer suchen'></form>
{/block}
<table width=100%>
<tr><th align='center'>{$navbar}</th></tr>
</table>


<form action="index.php?section=System|SpecialCourse&action=4"
	method="post" onsubmit="submit()">
<table class="table table-striped table-hover">
	<thead>
		<tr>
			<th align='center'><a href="index.php?section=System|SpecialCourse&action=3&filter=ID">ID</a></th>
			<th align='center'><a href="index.php?section=System|SpecialCourse&action=3&filter=forename">Vorname</a></th>
			<th align='center'><a href="index.php?section=System|SpecialCourse&action=3&filter=name">Name</a></th>
			<th align='center'><a href="index.php?section=System|SpecialCourse&action=3&filter=username">Benutzername</a></th>
			<th align='center'><a href="index.php?section=System|SpecialCourse&action=3&filter=birthday">Geburtsdatum</a></th>		
			<th align='center'>Fremdsprachen<br />
				{foreach from=$SpecialCourses item=specialCourse name=zaehler}
		{$specialCourse}&nbsp;
		{/foreach}
			</th>	
		</tr>
	</thead>
	<tbody>
		{foreach $users as $user}
		<tr>
			<td align="center">{$user.ID}</td>
			<td align="center">{$user.forename}</td>
			<td align="center">{$user.name}</td>
			<td align="center">{$user.username}</td>
			<td align="center">{$user.birthday}</td>
			<td align="center">
				{foreach from=$SpecialCourses item=specialCourse name=zaehler}
		<input type="checkbox" name="{$user.ID}[]" value="{$specialCourse}" {if $user.special_course|strstr:$specialCourse}checked{/if} />
		{/foreach}
			</td>
			<td align="center" bgcolor='#FFD99'>
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>
	<br> <input id="submit" onclick="submit()" type="submit" value="Speichern" />
</form>

{/block}