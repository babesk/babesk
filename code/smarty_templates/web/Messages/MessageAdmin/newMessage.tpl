{extends file=$inh_path}{block name=content}
<script type="text/javascript" src="{$path_js}/vendor/ckeditor/ckeditor.js"></script>

{literal}

<style type='text/css'  media='all'>
fieldset {
	border: 1px solid #000000;
}
</style>

{/literal}

<h3>Neue Nachricht erstellen:</h3>

<form id="addMessage" class="form-inline" action='index.php?section=Messages|MessageAdmin&amp;action=newMessage' method="post">
    {if isset($templates) and count($templates)}
		<div class="dropdown">
			<button class="btn btn-basic dropdown-toggle" type="button" data-toggle="dropdown">Template verwenden <span class="caret"></span></button>
			<ul class="dropdown-menu" name="template">
                {foreach $templates as $template}
					<li class="templateSelection" id="{$template.ID}"><a href="#"> {$template.title}</a></li>
                {/foreach}
			</ul>
		</div>
		<br>
    {/if}
	<fieldset>
		<legend>Nachricht</legend>
		<label for="messagetitle">Betreff:</label><br>
		<input id="messagetitle" type="text" name="messagetitle" placeholder="Betreff" class="form-control mb-2" /><br />
		<br />
		<label for="cke">Text:</label>
		<textarea id="cke" class="ckeditor" name="messagetext"></textarea>
	</fieldset>
	<fieldset>
		<legend>Einstellungen</legend>
		<label>
			Zettel zurückgeben?
			<input type="checkbox" name="shouldReturn">
		</label><br />
		<small>
			dadurch werden Funktionen für diese Nachricht benutzt, die eine Übersicht erlauben, um zu sehen welche Schüler (noch nicht) abgegeben haben.
		</small><br />
		<!--<label>
			Eine Email versenden?
			<input type="checkbox" name="shouldEmail">
		</label><br />
		<small>
			Wenn bestätigt, wird eine Notiz-Email an alle Empfänger dieser Nachricht versendet, die eine Email angegeben haben.
		</small><br /> -->
	</fieldset>
	<fieldset>
		<legend>Gültigkeitsbereich</legend>
		<label>
			G&uuml;ltig von:
			<input id="meal-date" name="startDate" class="datepicker form-control" data-provide="datepicker"
				   data-date-format="dd.mm.yyyy" />
		</label><br /><br />
		<label>
			G&uuml;ltig bis:
			<input id="meal-date" name="endDate" class="datepicker form-control" data-provide="datepicker"
				   data-date-format="dd.mm.yyyy" />
		</label><br /><br />
	</fieldset>
	<fieldset>
		<legend>Nachricht an einzelne Benutzer senden</legend>
		<input id="searchUserInp" type="text" name="searchUserInp"
			value="">
		<div id="userSelection">
		</div>
		Einzelne Benutzer, an die die Nachricht geschickt wird:
		<ul id="userSelected">
		</ul>
	</fieldset>
	<fieldset>
		<legend>An Klassen senden</legend>
		<select id="class-select" name="grades[]" size="5" multiple>
			{foreach item=grade from=$grades}
				<option value="{$grade.ID}">{$grade.name}</option>
			{/foreach}
		</select><br />
    </fieldset>
	<div class="form-group">

	</div>
	<input id="submit" class="btn btn-primary" onclick="submit()" type="submit" value="Absenden" />
</form>
{/block}

{block name=style_include append}

	<link rel="stylesheet" type="text/css" href="{$path_css}/datepicker3.css">

{/block}

{block name=js_include append}
	<script type="text/JavaScript" src="{$path_js}/web/Messages/searchUser.js"></script>
	<script type="text/JavaScript" src="{$path_js}/web/Messages/MessageAdmin/newMessageBinds.js"></script>
	<script type="text/javascript" src="{$path_js}/vendor/datepicker/bootstrap-datepicker.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#class-select').multiselect({
                enableFiltering: true,
                enableCaseInsensitiveFiltering: true,
                filterPlaceholder: 'Suche',
                nonSelectedText: 'Keine ausgewählt',
                templates: {
                    filter: '<li class="multiselect-item filter"><div class="input-group"> <span class="input-group-addon"><i class="fa fa-search fa-fw"> </i></span><input class="form-control multiselect-search" type="text"> </div></li>',
                    filterClearBtn: '<span class="input-group-btn"> <button class="btn btn-default multiselect-clear-filter" type="button"> <i class="fa fa-pencil"></i></button></span>'
                }
            });
        });
    </script>

    <script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
    <script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
{/block}