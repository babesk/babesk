{extends file=$inh_path} {block name='content'}

<style type='text/css'  media='all'>
</style>


<h3 class='module-header'>
	Liste der noch auszuleihenden B&uuml;cher ({$className})
</h3>

<p>{$listOfClasses}</p>

<table class="table table-responsive table-striped table-hover">
	<tr>
		<th align="center">Name</th>
		<th align="center">Vorname</th>
		<th align="center">Auszuleihende B&uuml;cher</th>
	</tr>

	{for $i=0 to $nr}
	<tr>
		<td align="left">{$name[$i]}</td>
		<td align="left">{$forename[$i]}</td>
		<td align="left">
			{foreach $books[$i] as $book}
				<li>{$book['title']}</li>
			{/foreach}
		</td>
	</tr>
	{/for}
</table>


{/block}
