$(document).ready(function() {

	$('button.delete-room').on('click', function(event) {

		event.preventDefault();

		var toDelete = $(this).attr('id');

		bootbox.confirm(
			'Der Raum wird dauerhaft gelöscht! Sind sie sich wirklich \
			sicher?',
			function(res) {
				if(res) {
					window.location = "index.php?module=administrator|System|Rooms&action=2&id=" + toDelete;
				}
			}
		);
	});
	
	$('button.edit-room').on('click', function(event) {

		event.preventDefault();

		var id = $(this).attr('id');
		if(document.getElementsByClassName(id)[0].disabled == true){
			document.getElementsByClassName(id)[0].disabled = false;
			$(document.getElementsByClassName('edit'+id)).removeClass('fa-pencil');
			$(document.getElementsByClassName('edit'+id)).addClass('fa-save');
		}else{
			document.getElementById('form'+id).submit();
		}
		
	});
});