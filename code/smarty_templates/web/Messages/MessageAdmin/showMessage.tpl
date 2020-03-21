{extends file=$inh_path}{block name=content}


<script type="text/javascript" src="{$path_js}/vendor/ckeditor/ckeditor.js"></script>
{literal}
<style type="text/css">
.barcodeInput {
	float: right;
}

</style>
{/literal}
<h2>
	Nachrichten-Administration
</h2>

<div class="popup messageText">
	<small style="float:right">ESC zum schließen</small>
	<textarea class="ckeditor" id="messagetext">{$messageData.text}</textarea>
</div>

<table class="dataTable">
	<tr>
		<th>Titel</th>
		<td>{if $message.GID eq $schbasID}<img src="../smarty/templates/web/images/schbas.png" title="Schulbuchausleihe-Nachricht">{/if}{$messageData.title}</td>
	</tr>
	<tr>
		<th>Text</th>
		<td>
			<button class="showMessageText">Text anzeigen</button>
		</td>
	</tr>
	<tr>
		<th>Gültig ab</th>
		<td>{$messageData.validFrom}</td>
	</tr>
	<tr>
		<th>Gültig bis</th>
		<td>{$messageData.validTo}</td>
	</tr>
</table>

<br />
<h4>
	Empfänger der Nachricht:
</h4>
<table class="dataTable">
	<tr>
		<th>ID</th>
		<th>Vorname</th>
		<th>Nachname</th>
		<th>gelesen</th>
		<th>Rückgabe-Status</th>
		<th>Aktion</th>
	</tr>
	{foreach $receivers as $receiver}
	<tr>
		<td>{$receiver->id}</td>
		<td>{$receiver->forename}</td>
		<td>{$receiver->name}</td>
		<td>
			{if ($receiver->readMessage)}
				Ja
			{else}
				Nein
			{/if}
		</td>
		<td>
			{if $receiver->returnedMessage == "noReturn"}
				<p>keine Rückgabe</p>
			{elseif $receiver->returnedMessage == "shouldReturn"}
				<p><b>Rückgabe ausstehend</b></p>
			{elseif $receiver->returnedMessage == "hasReturned"}
				<p>bereits zurückgegeben</p>
			{/if}
		</td>
		<td>
			<a id="{$receiver->id}" class="removeReceiver" href="">
				<img src="../include/res/images/delete.png" />
			</a>
			{if $shouldReturn}
			<a id="{$receiver->id}" class="toReturned" href="">
				<img src="../include/res/images/fileAdd.png"
					title="Den Benutzer als 'hat zurückgegeben' eintragen" />
			</a>
			{/if}
		</td>
	</tr>
	{/foreach}
</table>

<input id="receiverSearch" type="text" value="neuer Empfänger..." />

{if $shouldReturn}
	<button id="showBarcodeInput" class="barcodeInput">
		Barcode für Zettelrückgabe einscannen...
	</button>
	<label id="barcodeInputWrap" class="barcodeInput" hidden="hidden" />
		Barcode:<input id="barcodeInput" type="text" /><br />
		<small>Enter drücken, wenn Barcode eingescannt</small>
	</label>
{/if}
<br />

<span id="receiverSearchOutput">
</span>
<!-- <input id="receiverSelectButtonId1633" class="receiverSelectButton" type="button" value="Knut Terjung"> -->
<br />
{if $isCreator}
	<h4>
		Manager der Nachricht:
	</h4>
	<table class="dataTable">
		<tr>
			<th>ID</th>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>Aktion</th>
		</tr>
		{foreach $managers as $manager}
		<tr>
			<td>{$manager->id}</td>
			<td>{$manager->forename}</td>
			<td>{$manager->name}</td>
			<td>
				<a id="{$manager->id}" class="removeManager" href="">
					<img src="../include/res/images/delete.png">
				</a>
			</td>
		</tr>
		{/foreach}
	</table>
	<input id="managerSearch" type="text" value="neuer Manager..." /><br />
	<span id="managerSearchOutput">
	</span><br /><br />
	<input id="deleteMessage" type="button" value="Nachricht löschen" />
	<br />
{else}
	<p>
		Nur der Ersteller der Nachricht kann die Manager-Rechte verteilen und einsehen und die Nachricht löschen.
	</p>
{/if}
{/block}
{block name=js_include append}
<script type="text/JavaScript" src="{$path_js}/web/Messages/searchUser.js"></script>


{literal}
<script type="text/JavaScript">
    var _messageId = {/literal}{$messageData.ID}{literal};

$(document).ready(function() {


    $('#receiverSearch').on('keypress', function(event) {
        searchUser('receiverSearch', 'receiverSearchOutput',
            'receiverSelectButton');
    });

    $(document).on('click', '.receiverSelectButton', function(event) {
        var meId = $(this).attr('id').replace('receiverSelectButtonId', '');
        addReceiver(meId, {/literal}{$messageData.ID}{literal});
    });

    $('#managerSearch').on('keypress', function(event) {
        searchUser('managerSearch', 'managerSearchOutput', 'managerSelectButton');
    });

    $(document).on('click', '.managerSelectButton', function(event) {
        var meId = $(this).attr('id').replace('managerSelectButtonId', '');
        addManager(meId, {/literal}{$messageData.ID}{literal});
    });

    $('#deleteMessage').on('click', function(event) {
        if(confirm('Wollen sie diese Nachricht wirklich löschen?')) {
            deleteMessage({/literal}{$messageData.ID}{literal});
        }
    })

    $('.removeReceiver').on('click', function(event) {
        event.preventDefault();
        if(confirm('Wollen sie diesen Benutzer wirklich von der Nachrichtensendung entfernen?')) {
            removeReceiver(_messageId, $(this).attr('id'));
        }
    });

    $('.removeManager').on('click', function(event) {
        event.preventDefault();
        if(confirm('Wollen sie diesen Manager wirklich von der Nachrichten entfernen?')) {
            removeManager(_messageId, $(this).attr('id'));
        }
    });

    $('#showBarcodeInput').on('click', function(event) {
        $('#showBarcodeInput').hide();
        $('#barcodeInputWrap').show();
        $('#barcodeInput').focus();
    });

    $('#barcodeInput').on('keyup', function(event) {
        if(event.keyCode == 13) {
            sendUserReturnedBarcode($(this).val());
        }
    });

    $('.toReturned').on('click', function(event) {
        event.preventDefault();
        sendUserReturnedButton($(this).attr('id'));
    });
	/*Set up the use of nice-looking fade-functions for the popup*/
	$('.popup.messageText').show();
	$('.popup.messageText').fadeOut(0);
});

CKEDITOR.on('instanceReady', function(event) {
	CKEDITOR.instances.messagetext.setReadOnly(true);

});

CKEDITOR.on('instanceCreated', function(e) {
		e.editor.on('contentDom', function() {
			e.editor.document.on('keyup', function(event) {
				if(event.data.getKey() == 27) {
					$('.popup.messageText').fadeOut(400);
					$('.showMessageText').show();
				}
			}
		);
	});
});

/*
 * Popup
 */
$('.showMessageText').on('click', function(event) {
	$(this).hide();
	$('.popup.messageText').fadeIn(400);
});

$(document).on('keyup', function(event) {
	if(event.keyCode == 27 && $('.popup.messageText').css('display') == 'block') {
		$('.popup.messageText').fadeOut(400);
		$('.showMessageText').show();
	}
})

</script>
{/literal}


{/block}