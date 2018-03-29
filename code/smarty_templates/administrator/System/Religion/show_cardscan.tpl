{extends file=$ReligionParent}{block name=content}
<h2 class="module-header">Bitte Karte Scannen</h2>
<form action="index.php?section=System|Religion&action=5" method="post">
	<div class="row">
		<fieldset class="form-group">
			<legend>Karte</legend>
			<div class="container">
				<div class="row">
					<div class="form-group input-group col-lg-3 col-md-5 col-sm-7 col-xs-9">
						<span class="input-group-addon">ID</span>
						<input type="text" class="form-control" name="card_ID" size="10" maxlength="10" autofocus />
					</div>
				</div>
				<div class="row">
					<small class="text-muted">Geben Sie die 10-stellige ID von Ihrer Karte ein.</small>
				</div>
			</div>
		</fieldset>
	</div>
	<div class="row">
		<input class="btn btn-primary" type="submit" value="Senden" />
	</div>
</form>
{/block}
