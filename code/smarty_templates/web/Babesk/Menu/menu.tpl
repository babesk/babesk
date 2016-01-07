{extends file=$inh_path}{block name=content}

<h3>Bestellungen</h3>
<div class="panel panel-default">
	<div class="panel-body">
		<div class="col-md-offset-2 col-md-8">
			{if $error}
				<div class="alert alert-info">{$error}</div>
			{else}
			{if count($meal)==0}
				<div class="alert alert-info">Keine aktuellen Bestellungen vorhanden!</div>
				{else}
				<table class="table table-responsive table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th>Datum</th>
						<th>Mahlzeit</th>
						<th>Aktion</th>
					</tr>
				</thead>
				<tbody>
					{foreach $meal as $m}
					<tr>
						<td>{$m.date}</td>
						<td>{$m.name}</td>
						<td>
							{if $m.cancel}
								<a href="index.php?section=Babesk|Cancel&id={$m.orderID}">
									Abbestellen
								</a>
							{else}
								---
							{/if}
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
			{/if}
			<div id="history" class="collapse">
			<table class="table table-responsive table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th>Datum</th>
						<th>Mahlzeit</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					{foreach $mealHistory as $m}
					<tr>
						<td>{$m.date}</td>
						<td>{$m.name}</td>
						<td>
							
								<p>{if $m.fetched}abgeholt{else}nicht abgeholt{/if}</p>
							
						</td>
					</tr>
					{/foreach}
				</tbody>
			</table>
			</div>
			{/if}
			<div id="showHistory">
			
			<button type="button" class="btn btn-primary pull-left" data-toggle="collapse" data-target="#history">Vergangene Bestellungen</button>
			
				<a class="btn btn-primary pull-right" href="index.php?section=Babesk|Order">Essen bestellen</a>
			</div>
		</div>
	</div>
</div>
{/block}