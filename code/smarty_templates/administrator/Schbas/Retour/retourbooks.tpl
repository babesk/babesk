{extends file=$inh_path}{block name=content}


<h2 class="module-header">Ausleihliste f&uuml;r: {$fullname}</h2>


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
						{if $accounting['lcName']}
							{$accounting['lcName']}
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
			<tr>
				<td>{$retourbook['title']}</td>
				<td>{$retourbook['author']}</td>
				<td>{$retourbook['publisher']}</td>
				<td>
					{$retourbook['subName']}
					{$retourbook['year_of_purchase']}
					{$retourbook['class']}
					{$retourbook['bundle']}
					/
					{$retourbook['exemplar']}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
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

function ajaxFunction() {
    var barcode = document.getElementById('barcode').value;
    $.ajax({
        type: 'POST',
        url: 'index.php?section=Schbas|Retour',
        data: {
            'inventarnr': barcode,
            'card_ID': "{$cardid}",
            'uid': {$uid}
        },
        success: function(data) {
            console.log(data);
            try {
                var barcodeField = document.getElementById('barcode');
                barcodeField.value = '';

                var ajaxDisplay = document.getElementById('booklist');
                ajaxDisplay.innerHTML = data;
            } catch(e) {
                toastr['error']('Konnte die Serverantwort nicht parsen');
                return;
            }
        },
        error: function(data) {
            console.log(data)
            toastr['error']('Fehler!');
        }
    });
}
</script>

{/block}