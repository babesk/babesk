<p align="center">
	<h2>
		{$pdfTitle}
	</h2>
</p>
<br />
<i align="center">{$date}</i><br>
<table border="1" cellpadding="5">
	<thead>
	</thead>
	<tbody>
		{foreach $usersWithBooks as $unit}
			<tr>
				<td width="200">
					{$unit.user['forename']} {$unit.user['name']}
				</td>
				<td width="430">
					{foreach $unit.books as $book}
						&middot; {$book['title']}<br>
					{/foreach}
				</td>
			</tr>
		{foreachelse}
			{* tcpdf would error out when a tbody has no tablerows *}
			<tr>
				<td>---</td>
			</tr>
		{/foreach}
	</tbody>
</table>