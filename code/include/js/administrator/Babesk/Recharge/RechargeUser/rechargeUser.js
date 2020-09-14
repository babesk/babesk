$(document).ready(function() {
	update = function(){
		return $.ajax({
		      type: 'POST',
		      url: 'index.php?module=administrator|Babesk|Recharge|RechargeUser',
		      data: {
		        id: $('#user-select option:selected').attr('value')
		      },
		      success: function(data, statusText, jqXHR) {
		    	rec = JSON.parse(data);
		    	if(rec.soli == true){
		    		$('#soli').html("Der Benutzer hat ein gültiges Teilhabepaket");
		    	}else{
		    		$('#soli').html("Der Benutzer hat <b>kein</b> gültiges Teilhabepaket");
		    	}
		    	$('#maxRecharge').html("Der Benutzer kann maximal noch <b>"+rec.maxRecharge+"&euro;</b> aufladen!");
		        return;
		      },
		      error: function(jqXHR, textStatus, errorThrown) {
		        toastr.error('Ein Fehler ist beim Verändern des Nutzers aufgetreten.');
		        return console.log(jqXHR);
		      }
		});
	};
	update();
	$('#uid').val($('#user-select option:selected').attr('value'));
	$('#user-select').multiselect({
		enableFiltering: true,
    	enableCaseInsensitiveFiltering: true,
    	filterPlaceholder: 'Suche',
    	templates: {
      		filter: '<li class="multiselect-item filter"><div class="input-group"> <span class="input-group-addon"><i class="fa fa-search fa-fw"> </i></span><input class="form-control multiselect-search" type="text"> </div></li>',
      		filterClearBtn: '<span class="input-group-btn"> <button class="btn btn-default multiselect-clear-filter" type="button"> <i class="fa fa-pencil"></i></button></span>'
    	},
    	onChange: function(element, checked) {
    		$('#uid').val($('#user-select option:selected').attr('value'));
    		return update();
    	}
	});
});