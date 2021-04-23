</p>
	Bitte ausgef&uuml;llt zur&uuml;ckgeben an die Klassen- bzw. Kursleitung des Lessing-Gymnasiums bis zum {$schbasDeadlineClaim}!
</p>

<table border="1">
	<tr>
		{if empty($parentName) || empty($parentForename)}
			<td>
				Name, Vorname des/der Erziehungsberechtigten:<br><br><br><br><br><br>
			</td>
		{else}
			<td>
				Name, Vorname des/der Erziehungsberechtigten:<br/>
				{$parentName}, {$parentForename}
				<br><br><br><br>
			</td>
		{/if}

		{if empty($parentAddress)}
			<td>
				Anschrift:
			</td>
		{else}
			<td>
				Anschrift: <br>
				{nl2br($parentAddress)}
			</td>
		{/if}

		{if empty($parentTelephone)}
			<td>
				Telefon:
			</td>
		{else}
			<td>
				Telefon:<br>
				{$parentTelephone}
			</td>
		{/if}
	</tr>
	<tr>
		<td colspan="2">
			Name, Vorname des Sch&uuml;lers / der Sch&uuml;lerin:<br>
			{$user['name']}, {$user['forename']}
		</td>
		<td>
			<b>
				Jahrgangsstufe: {$grade['gradelevel']}
			</b>
		</td>
	</tr>
</table>

&nbsp;<br><br>

An der entgeltlichen Ausleihe von Lernmitteln im Schuljahr {$schoolyear}
{if $loanChoice == 'noLoan'}
	nehmen wir nicht teil.
{else if $loanFee == 'loanSoli'}
	nehmen wir teil und melden uns hiermit verbindlich zu den in Ihrem Schreiben vom {$letterDate} genannten Bedingungen an.<br/>
	Wir geh&ouml;ren zu dem von der Zahlung des Entgelts befreiten Personenkreis.<br/> Leistungsbescheid bzw. &auml;hnlicher Nachweis ist beigef&uuml;gt.
{else}
	nehmen wir teil und melden uns hiermit verbindlich zu den in Ihrem Schreiben vom {$letterDate} genannten Bedingungen an.<br/>
	{if $loanFee == 'loanNormal'}
		Der Betrag von {$feeNormal} &euro;
	{else if $loanFee == 'loanReduced'}
		Den Betrag von {$feeReduced} &euro; (mehr als 2 schulpflichtigen Kinder)
	{/if}
	wird bis sp&auml;testens {$schbasDeadlineTransfer} &uuml;berwiesen.<br/><br/>
	<table style="border:solid" width="75%" cellpadding="2" cellspacing="2">
		<tr>
			<td>Kontoinhaber:</td>
			<td>{$bankData.0}</td>
		</tr>
		<tr>
			<td>Kontonummer:</td>
			<td>{$bankData.1}</td>
		</tr>
		<tr>
			<td>Bankleitzahl:</td>
			<td>{$bankData.2}</td>
		</tr>
		<tr>
			<td>Kreditinstitut:</td>
			<td>{$bankData.3}</td>
		</tr>
		<tr>
			<td>Verwendungszeck:</td>
			<td>
				{$user['username']}
				JG {$grade['gradelevel']}
				SJ {$schoolyear}
			</td>
		</tr>
	</table>

	<br/><br/>
	Sollte der Betrag nicht fristgerecht eingehen, besteht kein Anspruch auf Teilnahme an der Ausleihe.
	<br/><br/>

	{if $loanFee == 'loanReduced'}
		<u>
			Weitere schulpflichtige Kinder im Haushalt
			(Schuljahr {$schoolyear}):
		</u>
		<br/><br/>
		{if empty($siblings)}
			<table style="border:solid" width="75%" cellpadding="2" cellspacing="2">
				<tr>
					<td>
						Name, Vorname, Schule jedes Kindes:
						<br><br><br><br><br><br><br><br>
					</td>
				</tr>
			</table>
		{else}
			<table style="border:solid" width="75%" cellpadding="2" cellspacing="2">
				<tr>
					<td>
						Name, Vorname, Schule jedes Kindes:<br/>
						{nl2br($siblings)}
					</td>
				</tr>
			</table>
		{/if}
	{/if}
{/if}
<br><br><br><br><br><br><br>
__________&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;_______________________________<br>
Ort, Datum &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Unterschrift Erziehungsberechtigte/r bzw. vollj&auml;hriger Sch&uuml;ler