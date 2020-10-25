$(document).ready(function() {

	var activePage = 1;
	var sortColumn = '';
	//Contains if the page should only show users with an missing (unpaid) amount
	var showOnlyMissing = false;
	var specialFilter = false;
	var charCodeToSelector = {
		33: 1, 34: 2, 167: 3, 36: 4, 37: 5, 38: 6, 47: 7, 40: 8, 41: 9, 61: 0
	};

    var userIDForm;

	dataFetch();
	var $pagination = $('#page-select');

	$pagination.bootpag({
		total: 1,
		page: activePage,
		maxVisible: 9
	}).on('page', activePageChange);

	$('#search-select-menu').multiselect({
		buttonContainer: '<div class="btn-group" />',
		includeSelectAllOption: true,
		buttonWidth: 150,
		selectAllText: 'Alle auswählen',
		numberDisplayed: 1
	});

	$('#filter').enterKey(function(ev) {
		activePage = 1;
		dataFetch();
	});

	$('#search-submit').on('click', function(ev) {
		activePage = 1;
		dataFetch();
	});

	$('table#user-table').on('click', 'tbody tr', userClicked);

	$('#credits-change-form').on('submit', paidAmountChange);

    $('#form-change-form').on('submit', formReturnedChange);

	$('#credits-change-input').enterKey(function(event) {
		event.preventDefault();
		$('#credits-change-form').submit();
	});

	$('#credits-change-modal').on('shown.bs.modal', function(event) {
		$(this).find('input#credits-change-input').focus().select();
	});

	$('#credits-change-modal').on('hidden.bs.modal', function(event) {
		$('input#filter').focus().select();
	});

	$('#credits-change-modal').on('keypress', payFullAmount);

	$('body').on('keypress', executeSelectorKey);

	$('button.preset-credit-change').on('click', creditChangeByPreset);

	$('input#credits-add-input').enterKey(creditAddToByInput);

	$('.special-filter-item').on('click', specialFilterToggle);
	$('#btn-print-pdf').on('click', printPdf);

	$('#user-table').on('click', 'a#name-table-head', function(ev) {
		sortColumn = (sortColumn == '') ? 'name' : '';
		dataFetch();
	});

	$('#user-table').on('click', 'a#grade-table-head', function(ev) {
		sortColumn = (sortColumn == '') ? 'grade' : '';
		dataFetch();
	});

	function dataFetch() {

		var filter = $('#filter').val();
		$.postJSON(
			'index.php?module=administrator|Schbas|SchbasAccounting|RecordReceipt',
			{
				'filter': filter,
				'filterForColumns': columnsToSearchSelectedGet(),
				'sortColumn': sortColumn,
				'activePage': activePage,
				'specialFilter': specialFilter
			},
			success
		);

		function success(res, textStatus, jqXHR) {
			if(jqXHR.status == 200) {
				if(typeof res.value !== 'undefined' && res.value == 'error') {
					toastr.error('Ein Fehler ist aufgetreten!');
				}
				else {
					tableFill(res.users);
					pageSelectorUpdate(res.pagecount);
					$('#prepSy').html(res.schbasPreparationSchoolyear);
				}
			}
			else if(jqXHR.status == 204) {
				toastr.error('Keinen Benutzer gefunden!');
				pageSelectorUpdate(0);
				tableFill([]);
				$('#filter').focus().select();
			}
			else {
				toastr.error(
					'Fehler! Unbekannter Status ' + toString(jqXHR.status)
				);
			}
		};
	};

	function tableFill(users) {

		var html = microTmpl(
			$('#user-table-template').html(),
			{'users': users}
		);
		$('#user-table').html(html);
	};

	function pageSelectorUpdate(pagecount) {
		$pagination.bootpag({
			total: pagecount,
			page: activePage,
			maxVisible: 9
		});
	};

	function activePageChange(event, pagenum) {
		activePage = pagenum;
		dataFetch();
	};

	function userClicked(event) {

        var $row = $(event.target).closest('tr');
        var userId = $row.data('user-id');
        userIDForm = userId;

        if ($row.children('td.loan-choice-type').text().includes('Von Zahlung befreit')) {
            var $modal = $('#form-change-modal');
            var returned = $row.children('td.returned').data('value');
            if(returned == 1){
                console.log("Test");
                $modal.find('input#formReturnedSwitch').prop('checked', true)
            }else{
                $modal.find('input#formReturnedSwitch').prop('checked', false)
            }
            $('#form-change-modal').modal();
        } else {
            var $modal = $('#credits-change-modal');
            var username = $row.children('td.username').text();
            var paid = parseFloat($row.children('td.payment-payed').text());
            var toPay = parseFloat($row.children('td.payment-to-pay').text());
            $modal.find('.username').html(username);
            $modal.find('.credits-before').html(toPay.toFixed(2) + '€');
            $modal.find('input#to-pay-change-input').val(toPay.toFixed(2));
            var $input = $modal.find('input#credits-change-input');
            $input.val(paid.toFixed(2));
            $input.data('user-id', userId);
            $('#credits-change-modal').modal();
        }
	};

	function paidAmountChange(event) {

		event.preventDefault();
		var $modal = $('#credits-change-modal');
		var $input = $modal.find('input#credits-change-input');
		var amount = $input.val().replace(",", ".");
		var $topay = $modal.find('input#to-pay-change-input').val().replace(",", ".");
		var userId = $input.data('user-id');

		$.ajax({
			'type': 'POST',
			'url': 'index.php?module=administrator|Schbas|SchbasAccounting|\
				RecordReceipt',
			'data': {
				"userId": userId,
				"amount": amount,
				"to-pay": $topay
			},
			'success': success,
			'error': error,
			'dataType': 'json'
		});

		//$.postJSON(
		//	'index.php?module=administrator|Babesk|Recharge|RechargeCard',
		//	{
		//		"userId": userId,
		//		"credits": credits
		//	},
		//	success
		//);

		function success(res) {

			var $row = $('table#user-table tbody')
				.find('tr[data-user-id=' + res.userId + ']');
			$row.find('td.payment-payed')
				.html(parseFloat(res.paid).toFixed(2) + ' €');
			$textCont = $row.find('td.payment-missing span')
			$textCont.html(parseFloat(res.missing).toFixed(2) + ' €');
			var col = '';
			if(res.missing > 0) {
				col = 'text-warning';
			} else if(res.missing == 0) {
				col = 'text-success';
			} else {
				col = 'text-danger';
				$textCont.prepend('Überschuss!');
			}
			$textCont.removeClass().addClass(col);
			$row.find('td.payment-to-pay').html(parseFloat(res.toPay).toFixed(2) + ' €');
			toastr.success('Zahlungsbetrag erfolgreich verändert.');
			$modal.modal('hide');
		};

		function error(jqXHR) {

			console.log(jqXHR);
			if(jqXHR.status == 500) {
				if(typeof jqXHR.responseJSON !== 'undefined' &&
					typeof jqXHR.responseJSON.message !== 'undefined') {
					toastr.error(jqXHR.responseJSON.message, 'Fehler');
				}
				else {
					toastr.error('Ein Fehler ist beim Ändern aufgetreten');
				}
			}
			else {
				toastr.error('Konnte die Serverantwort nicht lesen!', 'Fehler');
			}
		};
	};

    function formReturnedChange(event) {

        event.preventDefault();
        var $modal = $('#form-change-modal');
        var returned = $modal.find('input#formReturnedSwitch').prop('checked');
        console.log(userIDForm);

        $.ajax({
            'type': 'POST',
            'url': 'index.php?module=administrator|Schbas|SchbasAccounting|\
				RecordReceipt',
            'data': {
                "userId": userIDForm,
                "returned": returned
            },
            'success': success,
            'error': error,
            'dataType': 'json'
        });

        function success(res) {
            toastr.success('Änderungen erfolgreich gespeichert');
            $modal.modal('hide');
            dataFetch();
        };

        function error(jqXHR) {

            console.log(jqXHR);
            if(jqXHR.status == 500) {
                if(typeof jqXHR.responseJSON !== 'undefined' &&
                    typeof jqXHR.responseJSON.message !== 'undefined') {
                    toastr.error(jqXHR.responseJSON.message, 'Fehler');
                }
                else {
                    toastr.error('Ein Fehler ist beim Ändern aufgetreten');
                }
            }
            else {
                toastr.error('Konnte die Serverantwort nicht lesen!', 'Fehler');
            }
        };
    };

	function executeSelectorKey(event) {

		if(event.shiftKey == true) {
			if(charCodeToSelector[event.charCode] != undefined &&
				charCodeToSelector[event.charCode] != "undefined"
			) {
				event.preventDefault();
				var num = charCodeToSelector[event.charCode];
				$('table#user-table tbody td.selector[data-selector=' + num + ']')
					.closest('tr').click();
			}
		}
	};

	function creditChangeByPreset(event) {

		event.preventDefault();
		var $input = $('input#credits-change-input');
		var amount = parseInt($(this).text());
		var prevAmount = parseFloat($input.val().replace(",", "."));
		$input.val((prevAmount + amount).toFixed(2));
		$input.focus();
	};

	function creditAddToByInput(event) {

		//Dont let the enter-keypress bubble up to the form, so that the modal dont
		//closes
		//event.stopPropagation();
		event.preventDefault();
		var $input = $('input#credits-change-input');
		var addAmount = parseFloat($(this).val().replace(",", "."));
		var changeAmount = parseFloat($input.val().replace(",", "."));
		console.log((changeAmount + addAmount).toFixed(2));
		$input.val((changeAmount + addAmount).toFixed(2));
		$input.focus();
	};

	function specialFilterToggle(event) {

		function removeActiveFromFilters() {
			$items = $('#special-filter-menu li.active');
			$items.removeClass('active');
		}

		var $item = $(event.target);
		var isActive = $item.parent('li').hasClass('active');
		removeActiveFromFilters();
		if(!isActive) {
			$item.parent('li').addClass('active');
			specialFilter = $item.data('name');
		}
		else {
			specialFilter = false;
		}
		activePage = 1;
		dataFetch();
	};

	function payFullAmount(event) {

		if(event.key == '+') {
			event.preventDefault();
			var toPay = parseFloat(
				$('#credits-change-modal').find('span.credits-before').text()
				).toFixed(2);
			$('input#credits-change-input').val(toPay);
		}
	}

	function columnsToSearchSelectedGet() {
		var filterForColumns = $('#search-select-menu').val();
		//Handle select-all checkbox; we do not need to know it is selected
		var pos = $.inArray('multiselect-all', filterForColumns);
		if(pos > -1) {
			filterForColumns.splice(pos, 1);
		}
		return filterForColumns;
	}

	function printPdf() {

		bootbox.prompt({
			title: "Bitte geben sie einen Titel für das Dokument an",
			value: "Übersicht über die Geldeingänge",
			callback: function(res) {
				if(res !== null) {
					var filter = $('#filter').val();
					var params = $.param({
						'filter': filter,
						'filterForColumns': columnsToSearchSelectedGet(),
						'sortColumn': sortColumn,
						'activePage': activePage,
						'specialFilter': specialFilter,
						'pdf-title': res
					});

					window.open(
						"index.php?module=administrator|Schbas|SchbasAccounting|\
							RecordReceipt&" + params,
						'_blank'
					);
				}
			}
		});
	}
});