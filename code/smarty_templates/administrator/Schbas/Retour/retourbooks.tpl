{extends file=$inh_path}{block name=content}


<h2 class="module-header">Ausleihliste f&uuml;r: {$fullname}</h2>

{if $user->getSchbasAccounting() && count($user->getSchbasAccounting())}
	{$accounting = $user->getSchbasAccounting()->first()}
	{if $accounting->getLoanChoice()}
		{$loanChoiceName = $accounting->getLoanChoice()->getName()}
	{/if}
{/if}

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
						{if $loanChoiceName}
							{$loanChoiceName}
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
						{$missing = $accounting->getAmountToPay() - $accounting->getPayedAmount()}
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
						{$accounting->getPayedAmount()} €
					{else}
						---
					{/if}
				</td>
				<td>
					{if $accounting}
						{$accounting->getAmountToPay()} €
					{else}
						---
					{/if}
				</td>
			</tr>
		</tbody>
	</table>
</div>

<form name='barcode_scan' onsubmit='return false;' />
	<div class="form-group">
		<label for="barcode">Inventarnummer</label>
		<input type='text' id='barcode' class="form-control" autofocus/> <br />
	</div>
</form>

<div id="booklist">
	<table class="table table-responsive table-striped table-hover">
		<thead>
			<tr>
				<th>Titel</th>
				<th>Author</th>
				<th>Publisher</th>
				<th>Inventarnummer</th>
			</tr>
		</thead>
		<tbody>
			{foreach $data as $retourbook}
				{foreach $retourbook->getExemplars() as $exemplar}
					<tr>
						<td>{$retourbook->getTitle()}</td>
						<td>{$retourbook->getAuthor()}</td>
						<td>{$retourbook->getPublisher()}</td>
						<td>
							{$retourbook->getSubject()->getAbbreviation()}
							{$exemplar->getYearOfPurchase()}
							{$retourbook->getClass()}
							{$retourbook->getBundle()}
							/
							{$exemplar->getExemplar()}
						</td>
					</tr>
				{/foreach}
			{/foreach}
		</tbody>
	</table>


	{*
	{foreach $data as $retourbook}
		{$retourbook.title}, {$retourbook.author}, {$retourbook.publisher}. Inv.-Nr.: {$retourbook.subject} {$retourbook.year_of_purchase} {$retourbook.class} {$retourbook.bundle} / {$retourbook.exemplar}<br />
	{/foreach}
	*}
</div>
	<a class="btn btn-primary pull-right"
		 href="index.php?module=administrator|Schbas|Retour">
		Nächster Benutzer
	</a>
{/block}

{block name=js_include append}

<script language="javascript" type="text/javascript">

$('#barcode').enterKey(function(ev) {
	ajaxFunction();
});

<!--
//Browser Support Code
function ajaxFunction(){
	var ajaxRequest;  // The variable that makes Ajax possible!

	try{
		// Opera 8.0+, Firefox, Safari
		ajaxRequest = new XMLHttpRequest();
	} catch (e){
		// Internet Explorer Browsers
		try{
			ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try{
				ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e){
				// Something went wrong
				alert("Your browser broke!");
				return false;
			}
		}
	}
	// Create a function that will receive data sent from the server
	ajaxRequest.onreadystatechange = function(){
		if(ajaxRequest.readyState == 4){

			var barcodeField = document.getElementById('barcode');
			barcodeField.value = '';

			var ajaxDisplay = document.getElementById('booklist');
			ajaxDisplay.innerHTML = ajaxRequest.responseText;
		}
	}

	var barcode = document.getElementById('barcode').value;
	var queryString = "inventarnr=" + encodeURIComponent(barcode) + "&card_ID={$cardid}&uid={$uid}&ajax=1";

	ajaxRequest.open("GET", "https://{$adress}" + queryString, true);

	ajaxRequest.send(null);
}

//-->
</script>

{/block}