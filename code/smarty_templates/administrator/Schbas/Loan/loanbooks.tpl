{extends file=$inh_path}{block name=content}

<script language="javascript" type="text/javascript">
<!--
//influenced by www.tizag.com
function ajaxFunction(){
	var ajax;

	try{
		//others
		ajax = new XMLHttpRequest();
	} catch (e){
		// IE
		try{
			ajax = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try{
				ajax = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e){
				alert("Browser wird nicht unterstuetzt!");
				return false;
			}
		}
	}


	ajax.onreadystatechange = function(){
		if(ajax.readyState == 4){

			var barcodeField = document.getElementById('barcode');
			barcodeField.value = '';

			var ajaxDisplay = document.getElementById('booklist');
			ajaxDisplay.innerHTML = ajax.responseText;


		}
	}

	var barcode = document.getElementById('barcode').value;
	var queryString = "inventarnr=" + encodeURIComponent(barcode) + "&card_ID={$cardid}&uid={$uid}&ajax=1";
	ajax.open("GET", "http://{$adress}" + queryString, true);

	ajax.send(null);
}

//-->
</script>

<script language="javascript" type="text/javascript">
<!--
//influenced by http://tommwilson.com
function enter_pressed(e){
var keycode;
if (window.event) keycode = window.event.keyCode;
else if (e) keycode = e.which;
else return false;
return (keycode == 13);
}

//-->
</script>
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
<h3 class="module-header">Ausleihliste f&uuml;r: {$fullname}</h3>
<h3>{$alert}</h3>
<hr>
<form name='barcode_scan' onsubmit='return false;'>
<b>Bitte Barcode eingeben:</b>
<input type='text' id='barcode' onKeyPress='if(enter_pressed(event)) ajaxFunction() '/> <br>
</form>
<hr>
<div align="center"><h3>Auszugebende B&uuml;cher</h3></div>
<div id='booklist'>
{foreach $data as $datatmp}
{$datatmp.title}, {$datatmp.publisher} <br>
{/foreach}
</div>
{/block}