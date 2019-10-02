{extends file=$inh_path} {block name='content'}

<h3 class="module-header">Benutzerhomepage-Einstellungen</h3>

	<fieldset>
		<legend><b>Einstellungen für den Benutzerlogin</b></legend>
<ul class="submodulelinkList">
	<li>
		<a href="index.php?section=System|WebHomepageSettings&amp;action=redirect">
			Weiterleitung nach dem Login einstellen
		</a>
	</li>
	<li>
		<a href="index.php?section=System|WebHomepageSettings&amp;action=helptext">
			Hilfetext auf der Loginseite einrichten
		</a>
	</li>
	<li>
		<a href="index.php?section=System|WebHomepageSettings&amp;action=maintenance">
			Den Wartungsmodus einstellen
		</a>
	</li>
</ul>
	</fieldset>

{/block}