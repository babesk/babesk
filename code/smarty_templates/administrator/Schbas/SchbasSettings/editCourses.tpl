{extends file=$schbasSettingsParent}{block name=content}
    <div class="alert alert-info">
        Bitte wählen Sie die Kurse aus, die in den jeweiligen Jahrgängen Pflichtkurse sind.<br>
        Wahlkurse für einzelne Schüler können über die Systemeinstellungen bearbeitet werden.
    </div>
    {if $success}
        <div class="alert alert-success alert-dismissable">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            Die Änderungen wurden erfolgreich gespeichert.
        </div>
    {/if}
    <form action="index.php?section=Schbas|SchbasSettings&action=14" method="post">
        <table class="table table-striped table-hover">
            <thead>
            <tr>
                <td>Jahrgang</td>
                {foreach $courses as $course}
                    <td>{$course.name}</td>
                {/foreach}
            </tr>
            </thead>
            <tbody>
            {foreach $grades as $grade}
                <tr>
                    <td>{$grade.gradelevel}</td>
                    {foreach $courses as $course}
                        <td><input type="checkbox" name="{$grade.gradelevel}%{$course.ID}"
                            {foreach $coreSubjects as $coreSubject}
                                {if $coreSubject.gradelevel == $grade.gradelevel &&
                                    $coreSubject.subject_id == $course.ID}
                                    checked
                                    {/if}
                            {/foreach}
                            ></td>
                    {/foreach}
                </tr>
            {/foreach}
            </tbody>
        </table>
        <input type="submit" class="btn btn-primary" value="Speichern">
    </form>
{/block}
