{extends file=$inh_path}{block name=content}

<h3 class="module-header">Sprechzeiten Übersicht</h3>

<div class="panel panel-default">
	<div class="panel-body">
		{if count($meetings)}
			<table class="table table-responsive table-striped">
				<thead>
					<th>Tag</th>
					<th>Zeit</th>
					<th>Lehrer</th>
					<th>Raum</th>
				</thead>
				<tbody>
					{foreach $meetings as $meeting}
						<tr>
							<td>
								{$meeting['catname']}

							</td>
							<td>
								{$meeting['time']}
							</td>
							<td>
								{$meeting['name']},
								{$meeting['forename']}
							</td>
							<td>
								{$meeting['roomname']}
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		{else}
			<div class="alert alert-info">
				Bisher keine Sprechzeiten gewählt.
			</div>
		{/if}
	</div>
	<div class="panel-footer">
		<a href="index.php?module=web|Elawa|Selection" class="btn btn-primary pull-right">
			Neue Sprechzeit wählen
		</a>
		<div class="clearfix"></div>
	</div>
</div>

{/block}