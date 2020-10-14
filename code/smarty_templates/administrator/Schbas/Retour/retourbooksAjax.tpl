	<table class="table table-responsive table-striped table-hover">
		<thead>
			<tr>
				<th>Titel</th>
				<th>Author</th>
				<th>Publisher</th>
				<th>Inventarnummer</th>
			</tr>
		</thead>
		<tbody>
        {foreach $data as $retourbook}
			<tr>
				<td>{$retourbook['title']}</td>
				<td>{$retourbook['author']}</td>
				<td>{$retourbook['publisher']}</td>
				<td>
                    {$retourbook['subName']}
                    {$retourbook['year_of_purchase']}
                    {$retourbook['class']}
                    {$retourbook['bundle']}
					/
                    {$retourbook['exemplar']}
				</td>
			</tr>
        {/foreach}
		</tbody>
	</table>
