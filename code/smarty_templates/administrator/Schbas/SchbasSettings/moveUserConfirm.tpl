{extends file=$schbasSettingsParent}{block name=content}
    <div class="alert alert-danger">
        Durch diesen Vorgang werden bereits bestehende Daten des Schuljahres {$sy} überschrieben. Möchten Sie trotzdem fortfahren?<br>
    </div>
        <form action="index.php?section=Schbas|SchbasSettings&amp;action=12" method="post">
            <input type="submit" class="btn btn-primary" value="Fortfahren">
        </form>
        <form action="index.php?section=Schbas|SchbasSettings" method="post">
            <input type="submit" class="btn btn-danger" value="Abbrechen">
        </form>

{/block}