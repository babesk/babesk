{extends file=$soliParent} {block name=content}
<form action="index.php?section=Babesk|Soli&action=1" method="post">
<h4>Name:</h4>
	<select name="UID" id="user-select">
	{foreach $solis as $soli}
		<option value='{$soli.ID}'> {$soli.forename} {$soli.name}</option>
	{/foreach}
	</select><br>
	
	<h4>Gültigkeitsbereich:</h4>
	Von:<br>
	{html_select_date prefix='StartDate' end_year="+1"}<br>
	Bis:<br>
	{html_select_date prefix='EndDate' end_year="+1"}<br>
	<input type="submit" value="Absenden" />
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