{extends file=$inh_path}{block name=content}

<style type="text/css" media="all">
.dateBeyondValid {
	color: rgb(150,20,20);
}
</style>
{*Show the messages that the user got*}
	<h4>
		Posteingang:
	</h4>
{if count($receivedMsg)}
<table class="table">
	<tr>
		<th>Betreff</th>
		<th>Status</th>
		<th>Läuft ab am:</th>
		<th>Aktionen</th>
	</tr>

	{foreach $receivedMsg as $message}
	<tr>
		<td>
			{if $message.GID eq $schbasID}<img src="../include/res/images/schbas.png" title="Schulbuchausleihe-Nachricht">{/if}{$message.title}
		</td>
		<td>
			{if $message.return == 'noReturn'}
				<p>muss nicht zurückgegeben werden</p>
			{elseif $message.return == 'shouldReturn'}
				<p><b style="color:rgb(50,50,50);">muss zurückgegeben werden</b></p>
			{elseif $message.return == 'hasReturned'}
				<p>wurde zurückgegeben</p>
			{/if}
		</td>
		<td{if strtotime($message.validTo) < time()}
			class="dateBeyondValid"
                {/if}>
            {$message.validTo|date_format:"%e.%m.%Y"}
		</td>
		<td>
			{if $BaBeSkTerminal}
				Hinweis: Post kann nicht am BaBeSK-Terminal <br>ge&ouml;ffnet werden!
			{else}
			<a target="_blank" rel="noopener" href="index.php?section=Messages|MessageMainMenu&action=showMessage&ID={$message.ID}">
				<img src="../include/res/images/page_white_acrobat.png">
			</a>
			{/if}
		</td>
	</tr>
	{/foreach}
</table>
{else}
	<p>Keine Nachrichten erhalten</p>
{/if}

{*Show the messages that were created by the user*}
{if count($createdMsg) and $editor}
<br /><h4>
	Postausgang:
</h4>
<table class="table">
	<tr>
		<th>Betreff</th>
		<th>g&uuml;ltig von</th>
		<th>g&uuml;ltig bis</th>
		<th>Aktionen</th>
	</tr>

	{foreach $createdMsg as $message}
	<tr>
		<td>
			{if $message.GID eq $schbasID}<img src="../smarty/templates/web/images/schbas.png" title="Schulbuchausleihe-Nachricht">{/if}{$message.title}
		</td>
		<td>
			{$message.validFrom|date_format:"%e.%m.%Y"}
		</td>
		<td{if strtotime($message.validTo) < time()}
		class="dateBeyondValid"
		{/if}>
			{$message.validTo|date_format:"%e.%m.%Y"}
		</td>
		<td>
			{if $BaBeSkTerminal}
				Hinweis: Post kann nicht am BaBeSK-Terminal <br>ge&ouml;ffnet werden!
			{else}
			<a href="index.php?section=Messages|MessageMainMenu&amp;action=showMessage&amp;ID={$message.ID}">
				<img src="../include/res/images/page_white_acrobat.png">
			</a>&nbsp;&nbsp;&nbsp;&nbsp;
			{/if}
			<a href="#" data-toggle="modal" data-target="#deleteModal" data-id="{$message.ID}" data-title="{$message.title}">
				<img src="../include/res/images/delete.png">
			</a>&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="index.php?section=Messages|MessageAdmin&amp;action=showMessage&amp;ID={$message.ID}">
				Details...
			</a>
		</td>
	</tr>
	{/foreach}
</table>
{/if}
{if $editor}
	<a class="btn btn-success" href="index.php?section=Messages|MessageAdmin&amp;action=newMessageForm">
		Neue Nachricht erstellen
	</a>
	<div class="modal fade" id="deleteModal">
		<div class="modal-dialog ">
			<div class="modal-content">

				<!-- Modal Header -->
				<div class="modal-header">
					<h4 class="modal-title">Nachricht löschen</h4>
					<button type="button" class="close" data-dismiss="modal">&times;</button>
				</div>

				<!-- Modal body -->
				<div class="modal-body">
					Möchten Sie die Nachricht wirklich löschen?
				</div>

				<!-- Modal footer -->
				<div class="modal-footer">
					<form id="deleteForm" method="post" action="">
						<button type="submit" class="btn btn-danger">Löschen</button>
						<button type="button" class="btn btn-primary" data-dismiss="modal">Abbrechen</button>
					</form>
				</div>

			</div>
		</div>
	</div>
{/if}
{/block}
{block name=js_include append}
	{literal}
		<script type="text/javascript">
			$(document).ready(function() {
                $('#deleteModal').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget)
                    var id = button.data('id')
					var title = button.data('title')
                    var modal = $(this)
					modal.find(".modal-body").text("Möchten Sie die Nachricht \""+title+"\" wirklich löschen?")
					modal.find("#deleteForm").attr('action', "index.php?section=Messages|MessageMainMenu&action=deleteMessage&ID="+id )
                })
            })
		</script>
	{/literal}
{/block}