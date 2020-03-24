/**
 * Adds a hidden Input-Field and a List-Element describing the User.
 * The List-Element will be shown to the User, so that he sees his already
 * made selections
 * The hidden Input allows the server to add the Users when the form is
 * submitted
 * @param {int} userId              The ID of the user
 * @param {string} name                The name of the user
 * @param {string} form                The ID of the formular where the hidden
 * input-elements should be added
 * @param {string} addedUsersContainer The ID of the container where the added
 * Users will be displayed as a list
 */
function addUserAsHiddenInp(userId, name, form, addedUsersContainer) {
	var output = document.createElement("li");
	output.innerHTML = name;
	document.getElementById(addedUsersContainer).appendChild(output);
}

function cleanUserAsHiddenInput(addedUsersContainer){
    document.getElementById(addedUsersContainer).innerHTML = ""
}

function addReceiver(userId, messageId) {
	$.ajax({
		'type': "POST",
		'url': "index.php?section=Messages|MessageAdmin&action=addReceiverAjax",
		data: {
			'userId': userId,
			'messageId': messageId
		},
		success: function(data) {
			//refresh the tables... no interest in ajax-ing them, too
			if(data == "No Manager!") {
				alert("Sie sind kein Manager dieser Nachricht!");
			}
			location.reload();
		},
		error: function(data) {
			alert('Fehler beim hinzufügen des Benutzers');
		}
	});
}

function addManager(userId, messageId) {
	$.ajax({
		'type': "POST",
		'url': "index.php?section=Messages|MessageAdmin&action=addManagerAjax",
		data: {
			'userId': userId,
			'messageId': messageId
		},
		success: function(data) {
			//refresh the tables... no interest in ajax-ing them, too
			if(data == "No Manager!") {
				alert("Sie sind kein Manager dieser Message!");
			}
			location.reload();
		},
		error: function(data) {
			alert('Fehler beim hinzufügen des Benutzers');
		}
	});
}

function deleteMessage(messageId) {
	$.ajax({
		'type': 'POST',
		'url': 'index.php?section=Messages|MessageAdmin&action=deleteMessageAjax',
		data: {
			'messageId': messageId
		},
		success: function(data) {
			if(data == 'No Owner!') {
				alert('Sie sind nicht der Ersteller der Nachricht!');
			}
			else if(data == 'error') {
				alert('Konnte die Nachricht nicht löschen');
			}
			else {
				window.open('index.php?section=Messages|MessageMainMenu', '_self');
			}
		},
		error: function(data) {
			alert('Fehler beim löschen der Nachricht');
		}
	})
}

function removeReceiver(messageId, receiverId) {
	$.ajax({
		'type': 'POST',
		'url': 'index.php?section=Messages|MessageAdmin&action=removeReceiverAjax',
		data: {
			'messageId': messageId,
			'receiverId': receiverId
		},
		success: function(data) {
			if(data == 'No Manager!') {
				alert('Sie sind kein Manager der Nachricht!');
			}
			else if(data == 'error') {
				alert('Konnte den Empfänger nicht herausnehmen');
			}
			else {
				location.reload();
			}
		},
		error: function(data) {
			alert('Ein Fehler ist beim aufrufen des ServerSkripts aufgetreten!');
		}
	});
}

function removeManager(messageId, managerId) {
	try {
		$.ajax({
			'type': 'POST',
			'url': 'index.php?section=Messages|MessageAdmin&action=removeManagerAjax',
			data: {
				'messageId': messageId,
				'managerId': managerId
			},
			success: function(data) {
				if(data == 'No Manager!') {
					alert('Sie sind kein Manager der Nachricht!');
				}
				else if(data == 'error') {
					alert('Konnte den Manager nicht herausnehmen');
				}
				else if(data == 'errorSelf') {
					alert('Sie können sich nicht selber aus der Manager-Liste löschen!');
				}
				else {
					location.reload();
				}
			},
			error: function(data) {
				alert('Ein Fehler ist beim aufrufen des ServerSkripts aufgetreten!' + data.status);
			}
		});
	}
	catch(e) {
		alert(e);
	}
}

function sendUserReturnedBarcode(barcode) {
	$.ajax({
		'type': 'POST',
		'url': 'index.php?section=Messages|MessageAdmin&action=userSetReturnedMsgByBarcodeAjax',
		data: {
			'barcode': barcode
		},
		success: function(data) {
			if(data == 'error') {
				alert('Konnte die Nachricht des Benutzers nicht als "bereits zurückgegeben" markieren');
			}
			else if(data == 'entryNotFound') {
				alert('Der Link zwischen Nachricht und Benutzer konnte nicht gefunden werden');
			}
			else if(data == 'noManager') {
				alert('Sie sind kein Manager der Nachricht!');
			}
			else if(data == 'notNumeric') {
				alert('Der Barcode enthält inkorrekte Zeichen');
			}
			else {
				location.reload();
			}
		},
		error: function(data) {
			alert('Ein Fehler ist beim Senden des Barcodes aufgetreten!');
		}
	});
}

function sendUserReturnedButton(userId) {
	$.ajax({
		'type': 'POST',
		'url': 'index.php?section=Messages|MessageAdmin&action=userSetReturnedMsgByButtonAjax',
		data: {
			'userId': userId,
			'messageId': _messageId
		},
		success: function(data) {
			if(data == 'error') {
				alert('Konnte die Nachricht des Benutzers nicht als "bereits zurückgegeben" markieren');
			}
			else if(data == 'entryNotFound') {
				alert('Der Link zwischen Nachricht und Benutzer konnte nicht gefunden werden');
			}
			else if(data == 'noManager') {
				alert('Sie sind kein Manager der Nachricht!');
			}
			else {
				location.reload();
			}
		},
		error: function(data) {
			alert('Ein Fehler ist beim Senden der Infos aufgetreten!');
		}
	});
}