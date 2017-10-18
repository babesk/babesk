{extends file=$soliParent}{block name=content}


<table style="text-align: center;" class="table table-responsive table-striped">
	<thead>
		<tr>
			<th align='center'>ID</th>
			<th align='center'>Vorname</th>
			<th align='center'>Name</th>
			<th align='center'>Benutzername</th>
			<th align='center'>Geburtstag</th>
			<th align='center'>Geld</th>
			<th align='center'>Gruppe</th>
			<th align='center'>letzter Login</th>
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
			<td align="center">{$user.credit}</td>
			<td align="center">{$user.groupname}</td>
			<td align="center">{$user.last_login}</td>
			<td align="center">
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>


{/block}