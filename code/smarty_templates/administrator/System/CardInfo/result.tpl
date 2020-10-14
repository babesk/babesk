{extends file=$checkoutParent}{block name=content}

<h3 class="module-header">Karteninformationen</h3>

<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">Kartennummer {$user['cardnumber']}</h3>
	</div>
	<div class="panel-body">
		{if $user}
			<div class="row">
				<div class="col-sm-2">
					<label>Name</label>
				</div>
				<div class="col-sm-10">
					{$user['forename']} {$user['name']}
				</div>
			</div>
			<div class="row">
				<div class="col-sm-2">
					<label>Gesperrt</label>
				</div>
				<div class="col-sm-10">
					{if $user['locked']}
						<span class="text-danger">
							<span class="fa fa-exclamation-triangle"></span>
							Ja
						</span>
					{else}
						<span>Nein</span>
					{/if}
				</div>
			</div>
		{/if}
		<div class="row">
			<div class="col-sm-2">
				<label>Klasse</label>
			</div>
			<div class="col-sm-10">
					{$user['gradelevel']}{$user['label']}
			</div>
		</div>
	</div>
	<div class="panel-footer">
		<a class="btn btn-default"
			href="index.php?module=administrator|System|CardInfo">
			Infos zu anderer Karte
		</a>
		{if $user}
			<a class="btn btn-default"
				href="index.php?module=administrator|System|User|DisplayChange&ID={$user['ID']}">
				Benutzer anzeigen/verändern
			</a>
		{/if}
	</div>
</div>

{/block}