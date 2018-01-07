{extends file=$base_path}{block name=content}
Geben Sie bitte das Datum ein, wofür sie die bisher eingegangenen Bestellungen mit Teilhabepaket angezeigt haben möchten:<br><p>

	<fieldset>
<form action="index.php?section=Babesk|Soli&amp;action=8" method="post">
	<legend>Für eine Kalenderwoche</legend>
	<div class="col-xs-2">
		<select name="ordering_kw" class="form-control input-small">
			{section name=i loop=52}
				<option value="{{$smarty.section.i.index}+1}"  {if {{$smarty.section.i.index}+1} == {date('W')}} selected{/if}> {{$smarty.section.i.index}+1} </option>
			{/section}
		</select>
	</div>
	<div class="col-xs-2">
    	{html_select_date prefix='' time=$time start_year='-5'
    	end_year='+1' display_days=false display_months=false all_extra=" class='form-control' "}
	</div>
	<br><br> Aktuell: Kalenderwoche {date('W')}
    <br><br>
 
	<input type="submit" value="PDF generieren" />
</form><br>
	</fieldset>
<p><b> ODER </b></p>
	<fieldset>
<form action="index.php?section=Babesk|Soli&amp;action=8" method="post">
	<legend>Für einen Monat</legend>
	<div class="col-xs-2">
		<select name="ordering_month" class="form-control">
			{section name=i loop=12}
				<option value="{{$smarty.section.i.index}+1}"  {if {{$smarty.section.i.index}+1} == {date('W')}} selected{/if}> {{$smarty.section.i.index}+1} </option>
			{/section}
		</select>
	</div>
	<div class="col-xs-2">
    	{html_select_date prefix='' time=$time start_year='-5'
    	end_year='+1' display_days=false display_months=false all_extra=" class='form-control' "}
	</div>
	<br><br>Aktuell: Monat {date('m')}({date('M')})
	<br><br>
 
	<input type="submit" value="PDF generieren" />
</form>
	</fieldset>

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