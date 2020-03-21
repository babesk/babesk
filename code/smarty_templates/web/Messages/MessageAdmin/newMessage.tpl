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

<form id="addMessage"
	action='index.php?section=Messages|MessageAdmin&amp;action=newMessage'
	method="post">
	<fieldset>
		<legend>Nachricht</legend>
	<label>Titel:<input id="messagetitle" type="text" name="messagetitle" value="" /></label><br />
	{if isset($templates) and count($templates)}
		<label>Vorlage:
			<select id="templateSelection" name="template">
				{foreach $templates as $template}
					<option value="{$template.ID}">{$template.title}</option>
				{/foreach}
			</select>
		</label>
	{/if}
	<br />
	<label>Text:<textarea class="ckeditor" name="messagetext"></textarea></label>
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
		<label>
			Eine Email versenden?
			<input type="checkbox" name="shouldEmail">
		</label><br />
		<small>
			Wenn bestätigt, wird eine Notiz-Email an alle Empfänger dieser Nachricht versendet, die eine Email angegeben haben.
		</small><br />
	</fieldset>
	<fieldset>
		<legend>Gültigkeitsbereich</legend>
		<label>
			G&uuml;ltig von: {if $date_str} {html_select_date prefix='StartDate' end_year="+1" time=$startdate_str}
			{else}{html_select_date prefix='StartDate' end_year="+1"}
			{/if}
		</label><br /><br />
		<label>
			G&uuml;ltig bis: {if $date_str} {html_select_date prefix='EndDate' end_year="+1" time=$enddate_str}
			{else}{html_select_date prefix='EndDate' end_year="+1"}
			{/if}
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
		<select name="grades[]" size="5" multiple>
			{foreach item=grade from=$grades}
				<option value="{$grade.ID}">{$grade.name}</option>
			{/foreach}
		</select><br />
		<small>Mehrfachwahlen sind durch halten der Strg- (oder Ctrl-)Taste beim klicken möglich. Damit kann sich auch die angewählte Klasse wieder abwählen lassen. Strg+A, um alle Klassen auszuwählen.</small>
	</fieldset>
	<input id="submit" onclick="submit()" type="submit" value="Absenden" />
</form>
{/block}
{block name=js_include append}
<script type="text/JavaScript" src="{$path_js}/web/Messages/searchUser.js"></script>
<script type="text/JavaScript" src="{$path_js}/web/Messages/MessageAdmin/newMessageBinds.js"></script>
{/block}