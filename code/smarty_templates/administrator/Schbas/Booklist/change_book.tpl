{extends file=$inh_path}{block name=content}

<h3 class="module-header">Buch ändern</h3>

<form action="index.php?section=Schbas|Booklist&amp;action=2&amp;ID={$book['id']}"
	class="form-horizontal" method="post">
	<div class="row">
		<div class="col-sm-6">
			<div class="row form-group">
				<label class="col-sm-3 control-label">Fach</label>
				<div class="col-sm-9">
					<div class="input-group">
						<span class="input-group-addon">
							<span class="fa fa-fw fa-pencil"></span>
						</span>
						<input type="text" name="subject"
							{if $book['name']}
								value="{$book['name']}"
							{/if}
							class="form-control" maxlength="3">
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="row form-group">
				<label class="col-sm-3 control-label">Klasse</label>
				<div class="col-sm-9">
					<div class="input-group">
						<span class="input-group-addon">
							<span class="fa fa-fw fa-folder"></span>
						</span>
						<input type="text" name="class" value="{$book['class']}"
							class="form-control" maxlength="2">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-6">
			<div class="row form-group">
				<label class="col-sm-3 control-label">Titel</label>
				<div class="col-sm-9">
					<div class="input-group">
						<span class="input-group-addon">
							<span class="fa fa-fw fa-book"></span>
						</span>
						<input type="text" name="title" value="{$book['title']}"
							class="form-control" maxlength="50">
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="row form-group">
				<label class="col-sm-3 control-label">Author</label>
				<div class="col-sm-9">
					<div class="input-group">
						<span class="input-group-addon">
							<span class="fa fa-fw fa-male"></span>
						</span>
						<input type="text" name="author" value="{$book['author']}"
							class="form-control" maxlength="30">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-6">
			<div class="row form-group">
				<label class="col-sm-3 control-label">Verlag</label>
				<div class="col-sm-9">
					<div class="input-group">
						<span class="input-group-addon">
							<span class="fa fa-fw fa-newspaper-o"></span>
						</span>
						<input type="text" name="publisher" value="{$book['publisher']}"
							class="form-control" maxlength="30">
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="row form-group">
				<label class="col-sm-3 control-label">ISBN</label>
				<div class="col-sm-9">
					<div class="input-group">
						<span class="input-group-addon">
							<span class="fa fa-fw fa-tags"></span>
						</span>
						<input type="text" name="isbn" value="{$book['isbn']}"
							class="form-control" maxlength="17">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-6">
			<div class="row form-group">
				<label class="col-sm-3 control-label">Preis</label>
				<div class="col-sm-9">
					<div class="input-group">
						<span class="input-group-addon">
							<span class="fa fa-fw fa-money"></span>
						</span>
						<input type="text" name="price"
							value="{number_format($book['price'], 2, '.', '')}"
							class="form-control" maxlength="5">
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-6">
			<div class="row form-group">
				<label class="col-sm-3 control-label">Bundle</label>
				<div class="col-sm-9">
					<div class="input-group">
						<span class="input-group-addon">
							<span class="fa fa-fw fa-bookmark"></span>
						</span>
						<input type="text" name="bundle" value="{$book['bundle']}"
							class="form-control" maxlength="1">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-6">
			<div class="row form-group">
				<div class="col-sm-offset-3 col-sm-9">
					<input id="submit" type="submit" class="btn btn-primary"
						value="Ändern">
				</div>
			</div>
		</div>
	</div>
</form>

{/block}