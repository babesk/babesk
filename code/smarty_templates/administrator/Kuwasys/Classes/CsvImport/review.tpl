{extends file=$inh_path}{block name='content'}

<h2 class="module-header">Vorschau Kurs-Importierung</h2>

{if count($classes)}
<form action="index.php?module=administrator|Kuwasys|Classes|CsvImport|ImportExecute"
	method="post">
	<table class="table">
		{$tempId = 1}
		{foreach $classes as $class}
		<tr style="background-color: #fff">
			<th>
				{t}Name{/t}
			</th>
			<th>{$class.name}</th>
			<input type="hidden" name="classes[{$tempId}][name]"
				value="{$class.name|escape}">
			<input type="hidden" name="classes[{$tempId}][description]"
				value="{$class.description|escape}">
			<input type="hidden" name="classes[{$tempId}][maxRegistration]"
				value="{$class.maxRegistration}">
			<input type="hidden" name="classes[{$tempId}][registrationEnabled]"
				value="{$class.registrationEnabled}">
			<input type="hidden" name="classes[{$tempId}][isOptional]"
				value="{$class.isOptional}">
		</tr>

		<tr>
			<td>
				{t}Classteacher{/t}
			</td>
			<td>
				{* For every Classteacher *}
				{foreach name=cts from=$class.classteacher  key=ctKey item=ct}
					{if $ct.displayOptions == 1}
						{* Classteacher was not found, show alternative Options to User *}
						{if !empty($ct.origName)}
							<b>(Eingabe: "{$ct.origName}")</b><br />
							<input type="hidden" value="{$ct.origName|escape}"
									name="classes[{$tempId}][classteacher][{$ctKey}][name]" >
						{/if}
						<input type="radio"
							name="classes[{$tempId}][classteacher][{$ctKey}][ID]"
							value="CREATE_NEW" checked >Kursleiter neu erstellen<br />
						<input type="radio"
							name="classes[{$tempId}][classteacher][{$ctKey}][ID]"
							value="0" >Kein Kursleiter<br />
						{if $ct.similar}
							{foreach $ct.similar as $ctSimId => $ctSimName}
								<input type="radio"
									name="classes[{$tempId}][classteacher][{$ctKey}][ID]"
									value="{$ctSimId}">
									{$ctSimName}
									<br>
							{/foreach}
						{/if}
					{else}
						{* Classteacher was found, just show him *}
						{$ct.name}
						<input type="hidden"
							name="classes[{$tempId}][classteacher][{$ctKey}][ID]"
							value="{$ct.ID}">
					{/if}
					{if not $smarty.foreach.cts.last}
						<hr>
					{/if}
				{/foreach}
			</td>
		</tr>
		<tr>
			<td>
				{t}Day{/t}
			</td>
			<td>
				{foreach name=cats from=$class.categories item=category}
					<p>
						{$category.name}
						{if $category.name != $category.originalName}
							(Eingabe: <b>{$category.originalName}</b>)
						{/if}
					</p>
					<input type="hidden" name="classes[{$tempId}][categories][]"
						value="{$category.ID}">
				{/foreach}
			</td>
		</tr>
		{$tempId = $tempId + 1}
		{/foreach}
	</table>
	<input type="submit" value="{t}execute Changes{/t}">
</form>
{else}
	<p>
		{t}The uploaded file did not contain any usable data.{/t}
		<a href="index.php?administrator|Kuwasys|Classes|CsvImport">
			{t}click here to go back{/t}
		</a>
	</p>
{/if}

{/block}