{extends file=$inh_path}{block name=content}

    <h3 class="module-header">Buchzuweisungen für {$user.forename} {$user.name}</h3>


    <div class="container">
        <div class="row">
            <div class="dropdown col-md-8">
                <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Schuljahr {$activeSyName}<span class="caret"></span> </button>
                <ul class="dropdown-menu">
                    {foreach $schoolyears as $sy}
                        <li><a href="index.php?module=administrator|Schbas|BookAssignments|View&userId={$user.ID}&schoolyearId={$sy.ID}" data-name="{$sy.ID}" class="dropdown-item">{$sy.label}</a></li>
                    {/foreach}
                </ul>
            </div>
            {if $showGeneration}
                <div class="col-md-4">
                    <button id="generateNew" class="btn btn-danger">Buchzuweisungen neu generieren</button>
                    <input id="userID" type="hidden" value="{$user.ID}">
                </div>
            {/if}
        </div>
        <br>
        <div class="row">
            <div id="booklist">

                <table class="table">
                    <tr>
                        <th>Buch</th>
                        <th>Optionen</th>
                    </tr>
                    {if count($assignments)}

                        {foreach $assignments as $retourbook}
                            <tr class="">
                                <td>
                                    {$retourbook.title},
                                    {$retourbook.author},
                                    {$retourbook.publisher}
                                </td>
                                <td>
                                    <button id ="{$retourbook.AssignmentID}" class="deleteAssignment btn btn-danger fa fa-trash"></button>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <div class="alert alert-info">
                            Keine Bücher ausgeliehen.
                        </div>
                    {/if}
                </table>
            </div>
        </div>
    </div>


{/block}

{block name="style_include" append}
    <link rel="stylesheet"
          href="{$path_css}/react-select.css"
          type="text/css" />
    <link rel="stylesheet"
          href="{$path_css}/administrator/Schbas/BookAssignments/View/main.css"
          type="text/css" />
{/block}

{block name=js_include append}

    <script type="text/javascript"
            src="{$path_js}/vendor/bootbox.min.js">
    </script>
    <script type="text/javascript" src="{$path_js}/administrator/Schbas/BookAssignments/View/single_user.js"></script>

{/block}