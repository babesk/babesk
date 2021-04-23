{extends file=$inh_path}{block name=content}

<h2>Schulbuchausleihsystem f&uuml;r das Schuljahr {$prepSchoolyear['label']}</h2>
{if $BaBeSkTerminal}
	Hinweis: Schbas kann nicht am BaBeSK-Terminal <br>ge&ouml;ffnet werden!
{else}
<!--
	This needs to be refactored, there is a better way to do this
-->
<ul class="nav nav-pills nav-justified" role="tablist">
	<li {if $loanInfo}class="active"{/if}>
		<a href="javascript:document.loanInfo.submit()">Informationen</a>
	</li>
	<li {if $loanShowBuy}class="active"{/if}>
		<a href="javascript:document.loanShowBuy.submit()">Selbstkäufe</a>
	</li>
	<li {if $loanShowForm}class="active"{/if}>
		<a href="javascript:document.loanShowForm.submit()">Anmeldeformular</a>
	</li>
</ul>
{if !$loanShowBuy && !$loanShowForm}
	<br />
	<p class="alert alert-info">Bitte wählen sie einen Menüpunkt aus.</p>
	<a href="index.php?module=web|Schbas|LoanSystem&amp;action=showLoanOverviewPdf"
		class="btn btn-default">
		Buchausleiheübersicht herunterladen
	</a>
{/if}
{*
 * This right here is legacy-code, only needed to submit the requests as
 * Post-requests. Should be refactored.
 *}
	<form action="index.php?section=Schbas|LoanSystem&action=showPdf" method="post" name="loanInfo" id="loanInfo">
		<!--<h3><a href="javascript:document.loanInfo.submit()">Informationen</a></h3>-->
	</form>
<form action="index.php?section=Schbas|LoanSystem" method="post" name="loanShowForm" id="loanShowForm">
<input type="hidden" name="loanShowForm">
	<!--<h3><a href="javascript:document.loanShowForm.submit()">Anmeldeformular</a></h3>-->
</form>
<form action="index.php?section=Schbas|LoanSystem" method="post" name="loanShowBuy" id="loanShowBuy">
<input type="hidden" name="loanShowBuy">
	<!--<h3><a href="javascript:document.loanShowBuy.submit()">Selbstk&auml;ufe</a></h3>-->
</form>
{/if}
{if $loanShowBuy}

	<h3>Selbstkäufe</h3>
	<form action="index.php?section=Schbas|LoanSystem&action=loanShowBuy" method="post" id="loanShowBuy">
		<input type="hidden" name="loanShowBuySave">
		<div class="col-md-8 col-md-offset-2">
			<p>Folgende B&uuml;cher werden f&uuml;r das kommende Schuljahr ben&ouml;tigt. Sie k&ouml;nnen in der Liste angeklickt werden, wenn sie selbst angeschafft werden. Bei der Buchausgabe werden sie dann nicht ausgegeben.</p>
			<fieldset>
				{foreach from=$booksWithStatus item=bookWithStatus}
					{$book = $bookWithStatus.book}
					{$isSelfpaying = $bookWithStatus.selfpaying}
					<div class="form-group">
						<input type="checkbox" id="bookselector-{$book['id']}"
							class="fancy-check" name="bookID[]" value="{$book['id']}"
							{if $isSelfpaying}checked{/if}>
						<label for="bookselector-{$book['id']}">
							<span class="booklist-heading">
								{$book['subject']}: {$book['title']}
							</span>
						</label>
						<p class="help-block">
							({$book['author']}, {$book['publisher']}. ISBN: {$book['isbn']}. {$book['price']} &euro;)
						</p>
					</div>
				{/foreach}
			</fieldset>
			<input class="btn btn-primary pull-right" type="submit" value="Selbstk&auml;ufe abspeichern" />
		</div>
	</form>

{/if}

{if $loanShowForm}
	<h3>Anmeldeformular</h3>
	<form action="index.php?section=Schbas|LoanSystem&action=showFormPdf" method="post" id="loanForm">
		<div class="col-md-8 col-md-offset-2">
			<fieldset>
				<legend>Persönliche Daten (Freiwillig)</legend>
				<p>Die Eingaben in diesem Bereich sind freiwillig. Sie werden direkt in das R&uuml;ckmeldedokument ausgegeben und <u>nicht</u> abgespeichert.
				Entweder geben Sie diese Daten hier online oder nach dem Ausdrucken des erstellten R&uuml;ckmeldedokuments handschriftlich ein.
				</p>
				<div>
					<label style="width:300px; float:left;" for="eb_vorname">Vorname des/der Erziehungsberechtigten:</label>
					<input type="text" name="eb_vorname"/><br>
					<label style="width:300px;float:left;"for="eb_name">Name des/der Erziehungsberechtigten:</label>
					<input type="text" name="eb_name"/><br>
					<label style="width:300px;float:left;" for="eb_adress">Anschrift:</label>
					<textarea name="eb_adress" rows=2 cols=20 style="resize: none;"></textarea><br>
					<label style="width:300px;float:left;" for="eb_tel">Telefon:</label>
					<input type="text" name="eb_tel" />
				</div>
			</fieldset>
			<fieldset>
				<legend>Ausleihe (Pflicht)</legend>
				<p>
					In diesem Bereich m&uuml;ssen Sie eine Auswahl treffen! Ihre Entscheidung erscheint im erstellten R&uuml;ckmeldedokument als Strichcode.<br/>
				</p>
					An der entgeltlichen Ausleihe von Lernmitteln im Schuljahr {$prepSchoolyear['label']}<br/>
					<input type="radio" name="loanChoice" value="noLoan" id="radio1" required /> nehmen wir nicht teil<br />
					<input type="radio" name="loanChoice" value="loan" id="radio2" checked/> nehmen wir teil und melden uns hiermit verbindlich zu den im oben abrufbaren Schreiben genannten Bedingungen an.
				<div id="loan-amount-container" >
					Den Betrag von<br>
					<input type="radio" name="loanFee" value="loanNormal" id="loanNormal" checked /> {$feeNormal} Euro <br />
					<input type="radio" name="loanFee" value="loanReduced" id="loanReduced" /> {$feeReduced} Euro (bei mehr als zwei schulpflichtigen Kindern)<br />
					<input type="radio" name="loanFee" value="loanSoli" id="loanSoli"/> Wir geh&ouml;ren zu dem von der Zahlung des Entgelts befreiten Personenkreis.
					Leistungsbescheid bzw. &auml;hnlicher Nachweis ist beigef&uuml;gt.
				</div>
			</fieldset>
			<fieldset id="more-children-container">
				<legend>Weitere schulpflichtige Kinder (freiwillig)</legend>
				<div>
					<p>Die Eingaben in diesem Bereich sind freiwillig. Sie werden direkt in das R&uuml;ckmeldedokument ausgegeben und nicht abgespeichert.
					Entweder geben Sie diese Daten hier online oder nach dem Ausdrucken des erstellten R&uuml;ckmeldedokuments handschriftlich ein.<br/>
					Weitere schulpflichtige Kinder im Haushalt (Schuljahr {$prepSchoolyear['label']}).<br/> Bitte pro Zeile den Namen, Vornamen und die Schule angeben, auf der das jeweilige Kind geht.</p>
					<textarea name="siblings" rows=5 cols=80></textarea>
				</div>
			</fieldset>
			<input class="btn btn-primary pull-right" type="submit" value="R&uuml;ckmeldedokument erstellen" />
		</div>
	</form>
{/if}

{/block}


{block name=js_include append}

<script type="text/javascript">

	$('#more-children-container').hide();

	$('input[name="loanFee"]').on('change', function(ev) {

		if($(ev.target).attr('id') != 'loanReduced') {
			$('#more-children-container').slideUp();
		}
		else {
			$('#more-children-container').slideDown();
		}
	});

	$('input[name="loanChoice"]').on('change', function(ev) {

		if($(ev.target).val() != 'loan') {
			//User does not want to participate
			$('#loan-amount-container').slideUp();
			$('#more-children-container').slideUp();
			$('input[id=loanNormal]').prop('checked', true);
			$('textarea[name=siblings]').val('');
		}
		else {
			$('#loan-amount-container').slideDown();
		}
	});

	$('#loanForm').submit(function() {

		var text = $("textarea[name=siblings]").val();
		var lines = text.split("\n");
		var linesLen = lines.length;
		if (
			$("textarea[name=siblings]").val() &&
			$('input[id=loanReduced]').prop('checked') &&
			linesLen < 2
		) {
			alert('Bitte geben Sie mindestens zwei Kinder (in zwei Zeilen) ein!');
			return false;
		}
	});

</script>

{/block}

{block name=style_include append}

<style type="text/css">

.booklist-heading {
	font-weight: 700;
}

.booklist-details {
	margin-left: 20px;
}

</style>

{/block}