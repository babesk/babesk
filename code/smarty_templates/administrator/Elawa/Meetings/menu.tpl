{extends file=$inh_path}{block name=content}

<h3 class="module-header">Sprechtag-Wahlen Menü</h3>

<fieldset>
	<legend>Aktionen</legend>
	<ul class="submodulelinkList">
		<li>
			<a href="index.php?module=administrator|Elawa|Categories">
				1. Tagesbezeichnungen verwalten
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|Elawa|MeetingTimes">
				2. Sprechzeiten verwalten 
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|Elawa|Meetings|GenerateBare">
				3. Sprechtagswahlen initialisieren (Achtung: sämtliche Wahlen werden zurückgesetzt!)
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|Elawa|Meetings|ChangeDisableds">
				4. Individuelle Pausen und Raumzuordnungen setzen
			</a>
		</li>
	</ul>
</fieldset>

{/block}