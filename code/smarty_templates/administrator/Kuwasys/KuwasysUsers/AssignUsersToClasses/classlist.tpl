{extends file=$inh_path}{block name="content"}

<h2 class="module-header">Kursübersicht über temporäre Zuweisungen</h2>

<table class="table table-responsive table-striped table-hover">
	<tr>
		<th>Kursname</th>
		<th>Anzahl Schüler</th>
		<th>Wochentag</th>
		<th>Optionen</th>
	</tr>
	{foreach $classes as $class}
	<tr>
		<td>{$class.classlabel}</td>
		<td>{$class.usercount}</td>
		<td>{$class.weekday}</td>
		<td>
			<a class="btn btn-info btn-xs" href="index.php?module=administrator|Kuwasys|KuwasysUsers|AssignUsersToClasses|Classdetails&amp;classId={$class.classId}&amp;categoryId={$class.categoryId}" class="displayDetails">
				Details
			</a>
		</td>
	</tr>
	{/foreach}
</table>

{/block}
