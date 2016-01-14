{extends file=$inh_path}{block name=content}

<h3 class="module-header">Sprechtag-Wahlen Menü</h3>

<fieldset>
	<legend>Kategorien</legend>
	<table class="table table-responsive table-hover table-striped">
		<thead></thead>
		<tbody>
			<tr>
				<th>ID</th>
				<th>Bezeichnung</th>
			</tr>
			{foreach $categories as $category}
				<form action="index.php?module=administrator|Elawa|Categories&action=4" method="post" id="form{$category->getId()}">
					<tr>
						<td><input type="hidden" name="id" value="{$category->getId()}">{$category->getId()}</td>
						<td><input type="text" class="{$category->getId()}" name="name" value="{$category->getName()}" disabled></td>
						<td>
							<button type="button" class="btn btn-info btn-xs edit-category" id="{$category->getId()}">
									<span class="fa fa-edit fa-fw edit{$category->getId()}"></span>
							</button>
							{if !$elawaEnabled->getValue()}
							<button type="button"
								class="btn btn-danger btn-xs delete-category" id="{$category->getId()}">
								<span class="fa fa-trash-o"></span>
							</button>
							{/if}
						</td
					</tr>
				</form>
			{/foreach}
			<tr>
				<td/>
				<td>
					<form action="index.php?module=administrator|Elawa|Categories&action=3" method="post">
						<input type="text" name="name">
						<button type="submit"
							class="btn btn-info btn-xs add-category">
							Hinzuf&uuml;gen
						</button>
					</form>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

{/block}


{block name=style_include append}
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css" type="text/css" />
{/block}


{block name=js_include append}
<script type="text/javascript" src="{$path_js}/administrator/Elawa/Categories/main.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
{/block}