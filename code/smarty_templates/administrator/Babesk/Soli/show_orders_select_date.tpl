{extends file=$base_path}{block name=content}
Geben Sie bitte das Datum ein, wofür sie die bisher eingegangenen Bestellungen mit Teilhabepaket angezeigt haben möchten:<br>

<form action="index.php?section=Babesk|Soli&amp;action=4" method="post">
	<br>
	<fieldset>
		<legend>Kalenderwoche:</legend>
		<select name="ordering_kw">
	{section name=i loop=52}
	<option value="{{$smarty.section.i.index}+1}"  {if {{$smarty.section.i.index}+1} == {date('W')}} selected{/if}> {{$smarty.section.i.index}+1} </option>
	{/section}
	</select> (Aktuell: Kalenderwoche {date('W')}) <br>
	</fieldset>

	<fieldset>
		<legend>Einen Teilhabeberechtigten auswählen:<br></legend>
	<select name="user_id" id="user-select">
	{foreach $solis as $soli}
		<option value='{$soli.ID}'>{$soli.forename} {$soli.name}</option>
	{/foreach}
	</select><br>
	</fieldset>
 
	<input type="submit" value="Anzeigen" />
</form>

{/block}

{block name=js_include append}
<script type="text/javascript">
    $(document).ready(function() {
        $('#user-select').multiselect({
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            filterPlaceholder: 'Suche',
            templates: {
                filter: '<li class="multiselect-item filter"><div class="input-group"> <span class="input-group-addon"><i class="fa fa-search fa-fw"> </i></span><input class="form-control multiselect-search" type="text"> </div></li>',
                filterClearBtn: '<span class="input-group-btn"> <button class="btn btn-default multiselect-clear-filter" type="button"> <i class="fa fa-pencil"></i></button></span>'
            }
        });
    });
</script>
	<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
	<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
	<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>

{/block}