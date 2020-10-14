{extends file=$base_path}
{block name=content}

<h3 class="module-header">Ausleihliste</h3>
    <div class="panel panel-default">
	<table class="table">
		<thead>
		<tr>
			<th>
				Ausleihstatus
			</th>
			<th>
				Fehlend
			</th>
			<th>
				Bezahlt
			</th>
			<th>
				Soll
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
                {if $accounting}
                    {if $accounting['name']}
                        {$accounting['name']}
                    {else}
						???
                    {/if}
                {else}
					Antrag nicht erfasst
                {/if}
			</td>
			<td>
                {if $accounting}
                    {$missingClass = ''}
                    {$missing = $accounting['amountToPay'] - $accounting['payedAmount']}
                    {if $missing == 0}
                        {$missingClass = 'text-success'}
                    {else}
                        {$missingClass = 'text-warning'}
                    {/if}
					<span class="{$missingClass}">
							{$missing} €
						</span>
                {else}
					---
                {/if}
			</td>
			<td>
                {if $accounting}
                    {$accounting['payedAmount']} €
                {else}
					---
                {/if}
			</td>
			<td>
                {if $accounting}
                    {$accounting['amountToPay']} €
                {else}
					---
                {/if}
			</td>
		</tr>
		</tbody>
	</table>
	</div>
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">
			Ausleihliste für: {$userdata['forename']} {$userdata['name']}
		</h3>
	</div>
	<ul class="list-group checklist">
		{if $accounting}
			<li class="list-group-item list-group-item-success">
				<span class="fa fa-check fa-fw pull-left"></span>
				Das Formular zur Buchausleihe wurde abgegeben.
			</li>
			{*
			 * userPaid and userSelfpayer can only be checked if form was submitted
			 *}
			{if $userPaid}
				<li class="list-group-item list-group-item-success">
					<span class="fa fa-check fa-fw pull-left"></span>
					Der Benutzer hat für die Buchausleihe bezahlt.
				</li>
			{else}
				{if $loanChoice == 'ls'}
					<li class="list-group-item list-group-item-warning">
						<span class="fa fa-info-circle pull-left"></span>
						Der Benutzer ist Selbsteinkäufer!
					</li>
				{elseif $loanChoice == 'nl'}
					<li class="list-group-item list-group-item-warning">
						<span class="fa fa-info-circle pull-left"></span>
						Keine Teilnahme des Benutzers!
					</li>
				{else}
					<li class="list-group-item list-group-item-danger">
						<span class="fa fa-exclamation-triangle pull-left"></span>
						Der Benutzer hat nicht genug für die Bücher bezahlt!
					</li>
				{/if}
			{/if}
		{else}
			<li class="list-group-item list-group-item-danger">
				<span class="fa fa-exclamation-triangle pull-left"></span>
				Das Formular zur Buchausleihe wurde noch nicht abgegeben!
			</li>
		{/if}

		{if count($exemplarsLent) == 0}
			<li class="list-group-item list-group-item-success">
				<span class="fa fa-check fa-fw pull-left"></span>
				Der Benutzer besitzt keine der ausgeliehenen Bücher mehr.
			</li>
		{else}
			<li class="list-group-item list-group-item-warning">
				<span class="fa fa-info-circle pull-left"></span>
				Dem Benutzer sind noch Bücher ausgeliehen
				<a href="#" class="btn btn-xs btn-default pull-right"
					data-toggle="collapse" data-target="#lent-exemplars-table">
					Tabelle anzeigen / verstecken
				</a>
				<table id="lent-exemplars-table"
					class="table table-condensed collapse">
					<thead>
						<tr>
							<th>Name</th>
							<th>Exemplarnummer</th>
							<th>Fach</th>
						</tr>
					</thead>
					<tbody>
						{foreach $exemplarsLent as $exemplar}
							<tr>
								<td>
									{$exemplar['title']}
								</td>
								<td>
									{$exemplar['exemplar']}
								</td>
								<td>
									{$exemplar['abbreviation']}
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</li>
		{/if}
		{if count($booksSelfpaid) == 0}
			<li class="list-group-item list-group-item-success">
				<span class="fa fa-check fa-fw pull-left"></span>
				Der Benutzer kauft keine Bücher selber ein.
			</li>
		{else}
			<li class="list-group-item list-group-item-info">
				<span class="fa fa-info-circle pull-left"></span>
				Der Benutzer kauft folgende Bücher selber ein:
				<ul class="selfbuy-books-list">
					{foreach $booksSelfpaid as $book}
						<li>
							{$book['title']}
						</li>
					{/foreach}
				</ul>
			</li>
		{/if}
	</ul>
	<div class="panel-body">
		<h5 class="books-to-loan-table">Auszugebende Bücher</h5>
	</div>
	<table class="table table-striped table-hover">
		<thead>
			<tr>
				<th>Titel</th>
				<th>Publisher</th>
			</tr>
		</thead>
		<tbody>
			{foreach $booksToLoan as $bookData}
				{$book = $bookData.book}
				{$alreadyLent = $bookData.alreadyLent}
				<tr data-book-id="{$book['id']}"
					{if $alreadyLent}class="bg-success text-success"{/if}>
					<td>
						{if $alreadyLent}
							<span class="fa fa-check"></span>
							<small class="label label-success">
								bereits ausgeliehen
							</small>&nbsp;
						{/if}
						{$book['title']}
					</td>
					<td>{$book['publisher']}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
	<div class="panel-footer">
		<div class="input-group">
			<span class="input-group-addon">
				<span class="fa fa-barcode"></span>
			</span>
			<input type="text" id="book-barcode" class="form-control"
				placeholder="Buchcode hier einscannen" autofocus />
			<span class="input-group-btn">
				<button id="book-barcode-submit" class="btn btn-default">
					Buch ausgeben
				</button>
			</span>
		</div>
		</div>
</div>

<a class="btn btn-primary pull-right"
	href="index.php?module=administrator|Schbas|Loan">
	Nächster Benutzer
</a>

<input type="hidden" id="user-id" value="{$userdata['UID']}" />

{/block}


{block name=style_include append}

<style type="text/css">

	ul.checklist span.fa {
		font-size: 24px;
		margin-top: -3px;
		margin-right: 10px;
	}

	/*
	 * Bootstraps .in displays elements as a block, this would not stretch the
	 * table, but we want it to
	 */
	#lent-exemplars-table.in {
		display: table !important;
	}

	table tbody span.icon {
		margin-right: 10px;
	}

	ul.lent-books-list, ul.selfbuy-books-list {
		margin-left: 10px;
		list-style: disc;
	}

	h5.books-to-loan-table {
		position: relative;
		top: 7px;
		right: 5px;
		padding: 0;
		margin: 0;
		font-weight: 600;
	}

</style>

{/block}

{block name=js_include append}

<script type="text/javascript">

$(document).ready(function(){

	$('#book-barcode').enterKey(barcodeSubmit);
	$('#book-barcode-submit').on('click', barcodeSubmit);

	function barcodeSubmit() {
		var barcode = $('input#book-barcode').val();
		console.log(barcode);
		var userId = $('#user-id').val();
		$.ajax({
			'type': 'POST',
			'url': 'index.php?module=administrator|Schbas|Loan&wacken',
			'data': {
				'barcode': barcode,
				'userId': userId
			},
			'dataType': 'json',
			'success': success,
			'error': error
		});

		function success(res) {

			console.log(res);
			var $row = $('table tr[data-book-id="' + res.bookId + '"]');
			toastr.success(
				'Das Buch "' + res.title + '" wurde erfolgreich verliehen'
			);
			$row.addClass('bg-success text-success');
			$row.children('td')
				.first()
				.prepend('<span class="fa fa-check"></span>');
			$('#book-barcode').focus().select();
		}

		function error(jqXHR) {

			console.log(jqXHR.responseText);
			if(jqXHR.status == 500) {
				if(typeof jqXHR.responseJSON !== 'undefined' &&
					typeof jqXHR.responseJSON.message !== 'undefined') {
					toastr.error(jqXHR.responseJSON.message, 'Fehler');
				}
				else {
					toastr.error('Ein Fehler ist beim Ausleihen aufgetreten');
				}
			}
			else {
				toastr.error('Konnte das Buch nicht ausleihen. Ein genereller Fehler \
					ist aufgetreten', 'Fehler (' + jqXHR.status + ') beim Ausleihen');
			}
			$('#book-barcode').focus().select();
		}
	};
});

</script>

{/block}