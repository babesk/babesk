{extends file=$inh_path}{block name=content}

<h3 class="module-header">Lehrer-Auswahl</h3>

<div class="panel panel-default">
	<div class="panel-heading">
		Für welchen Lehrer wollen sie eine Sprechzeit wählen?
	</div>
	<div class="panel-body">
		<div class="list-group">
			{foreach $hosts as $hostData}
				<a {if $hostData.selectable}href="index.php?module=web|Elawa|Selection&amp;hostId={$hostData.host['ID']}"{/if}
				class="list-group-item {if !$hostData.selectable}disabled{/if}">
					{$hostData.host['name']},
					{$hostData.host['forename']}
					{if !empty($hostData.statusText)}
						<span class="badge">
							{$hostData.statusText}
						</span>
					{/if}
				</a>
			{/foreach}
		</div>
	</div>
</div>

{/block}