$(document).ready(function() {

	$('button.delete-category').on('click', function(event) {

		event.preventDefault();

		var toDelete = $(this).attr('id');

		bootbox.confirm(
			'Die Sprechtagszeit wird dauerhaft gelöscht! Sind sie sich wirklich \
			sicher?',
			function(res) {
				if(res) {
					window.location = "index.php?module=administrator|Elawa\
					|MeetingTimes&action=2&id=" + toDelete;
				}
			}
		);
	});
	
	$('button.edit-category').on('click', function(event) {

		event.preventDefault();

		var id = $(this).attr('id');
		if(document.getElementsByClassName(id)[0].disabled == true){
			document.getElementsByClassName(id)[0].disabled = false;
			document.getElementsByClassName(id)[1].disabled = false;
			$(document.getElementsByClassName('edit'+id)).removeClass('fa-pencil');
			$(document.getElementsByClassName('edit'+id)).addClass('fa-save');
		}else{
			document.getElementById('form'+id).submit();
		}
		
	});
	
	  
	  $('#category-select').multiselect({
	    maxHeight: 400,
	    onChange: function(element, checked) {
	      document.forms['catForm'].submit();
	    }
	  });
	  return;
});