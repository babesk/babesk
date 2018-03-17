{extends file=$ReligionParent}{block name='content'}

<form action="index.php?section=System|Religion&action=2"
	method="post" onsubmit="submit()">
	<h2 class="module-header">Konfessionen editieren</h2>
	<div class="row">
		<fieldset class="form-group">
			<legend>Konfessionen bearbeiten:</legend>
			{foreach from=$religions item=religion name=zaehler}
				<div class="container">
					<div class="row">
						<div class="col-md-1 col-sm-2 col-xs-3">
							<input type="text" class="form-control" name="rel{$smarty.foreach.zaehler.iteration}" size="3" maxlength="3" value="{$religion}" /><br/>
						</div>
					</div>
				</div>
			{/foreach}
			<input type="hidden" name="relcounter" value="{$smarty.foreach.zaehler.total+1}" />
		</fieldset>
	</div>
	<div class="row">
		<fieldset class="form-group">
			<legend>Konfession hinzufügen:</legend>
			<div class="container">
				<div class="row">
					<div class="col-md-1 col-sm-2 col-xs-3">
						<input type="text" class="form-control col-xs-1" name="rel{$smarty.foreach.zaehler.total+1}" size="3" maxlength="3" value="" /><br/>
					</div>
				</div>
				<div class="row">
					<small class="text-muted">Kürzel darf max. 3 Zeichen lang sein.</small>
				</div>
			</div>
		</fieldset>
	</div>
	<div class="row">
		<!--<input class="btn btn-primary" id="submit" onclick="submit()" type="submit" value="Speichern" />-->
		<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#closeTab">Speichern</button>
	</div>
	<div id="closeTab" class="modal fade" role="dialog">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<!-- X -->
					<button type="submit" class="close">&times;</button>
					<div class="panel panel-success">
						<div class="panel-heading">Konfessionen gespeichert!</div>
						<div class="panel-body">Alle Änderungen wurden übernommen.</div>
					</div>
					<button type="submit" class="btn btn-success">Ok</button>
				</div>
			</div>

		</div>
	</div>

</form>
{/block}