{extends file=$soliParent} {block name=content}
<form action="index.php?section=Babesk|Soli&action=1" method="post">
<h4>Name:</h4>
	<select name="UID" id="user-select">
	{foreach $solis as $soli}
		<option value='{$soli.ID}'> {$soli.forename} {$soli.name}</option>
	{/foreach}
	</select><br><br>
	
	<fieldset>
		<legend>Gültigkeitsbereich</legend>
	<b>Von:</b><br>
	<input id="meal-date" name="startDate" class="datepicker"
		   data-date-format="dd.mm.yyyy" /><br>
	<b>Bis</b><br>
	<input id="meal-date" name="endDate" class="datepicker"
		   data-date-format="dd.mm.yyyy" /><br>
	<input type="submit" value="Absenden" />
</form>
	</fieldset>
{/block}

{block name=style_include append}

	<link rel="stylesheet" type="text/css" href="{$path_css}/datepicker3.css">

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
	<script type="text/javascript" src="{$path_js}/vendor/datepicker/bootstrap-datepicker.min.js"></script>
	<script type="text/javascript">
        $(document).ready(function() {
            $('.datepicker').datepicker({
            });
        });
	</script>
{/block}