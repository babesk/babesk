<html>

<p style="text-align: center"><h2>Gespeicherte Daten von {$user.forename} {$user.name} (Stand: {$smarty.now|date_format:"%d.%m.%Y %H:%M"})</h2></p>

<b>Geburtstag</b>: {$user.birthday|date_format:"%d.%m.%y"}<br>
<b>Guthaben</b>: {$user.credit}€<br>
<b>Kartennummer</b>: {$card.cardnumber} {if $card.lost}<span style="color: red">Gesperrt!</span>{/if} <br>
<b>Benutzergruppen</b>: {$groups}
<br><hr><br>
<h3>Klassenzugehörigkeiten</h3>
<table border="1" cellpadding="5">
	<thead>
	<tr>
		<th><b>Klasse</b></th>
		<th><b>Schuljar</b></th>
	</tr>

	</thead>
	<tbody>
    {foreach $classes as $class}
		<tr>
			<td>{$class.gradelevel}{$class.label}</td>
			<td>{$class.sy}</td>
		</tr>
    {foreachelse}
		<tr><td colspan="2"><b>Keine vorhanden</b></td></tr>
    {/foreach}

	</tbody>
</table>
<br pagebreak="true"/>
<p style="text-align: center"><h2>Bargeldloses Bestellsystem für Schulkantinen</h2></p>
<h3>Bestellungen</h3>
<table border="1" cellpadding="5">
	<thead>
		<tr>
			<th colspan="2"><b>Gericht</b></th>
			<th><b>Preisklasse</b></th>
			<th><b>Datum</b></th>
			<th><b>Abgeholt</b></th>
		</tr>

	</thead>
	<tbody>
		{foreach $orders as $order}
			<tr>
				<td colspan="2">{$order['name']}</td>
				<td>{$order['pcname']}</td>
				<td>{$order['date']|date_format:"%d.%m.%y"}</td>
				<td>{if $order['fetched']} Ja {else} Nein {/if}</td>
			</tr>
        {foreachelse}
			<tr><td colspan="5"><b>Keine vorhanden</b></td></tr>
        {/foreach}
	</tbody>
</table><br>

<h3>Gutscheine</h3>
<table border="1" cellpadding="5">
	<thead>
	<tr>
		<th><b>gültig ab</b></th>
		<th><b>gültig bis</b></th>
	</tr>

	</thead>
	<tbody>
		{foreach $coupons as $coupon}
			<tr>
				<td>{$coupon.startdate|date_format:"%d.%m.%y"}</td>
				<td>{$coupon.enddate|date_format:"%d.%m.%y"}</td>
			</tr>
        {foreachelse}
			<tr><td colspan="2"><b>Keine vorhanden</b></td></tr>
        {/foreach}
</table><br>

<h3>Aufladungen</h3>
<table border="1" cellpadding="5">
	<thead>
	<tr>
		<th><b>Zeitpunkt</b></th>
		<th><b>Betrag</b></th>
	</tr>

	</thead>
	<tbody>
    {foreach $recharges as $recharge}
		<tr>
			<td>{$recharge.datetime|date_format:"%d.%m.%y %H:%M"}</td>
			<td>{$recharge.amount}€</td>
		</tr>
	{foreachelse}
		<tr><td colspan="2"><b>Keine vorhanden</b></td></tr>
    {/foreach}

	</tbody>
</table>
<br pagebreak="true"/>
<p style="text-align: center"><h2>Elternsprechtagswahlen</h2></p>
<table border="1" cellpadding="5">
	<thead>
	<tr>
		<th><b>Person</b></th>
		<th><b>Kategorie</b></th>
		<th><b>Raum</b></th>
		<th><b>Uhrzeit</b></th>
		<th><b>Dauer</b></th>
	</tr>

	</thead>
	<tbody>
    {foreach $elawas as $elawa}
		<tr>
			<td>{$elawa.hostName}</td>
			<td>{$elawa.catName}</td>
			<td>{$elawa.roomName}</td>
			<td>{$elawa.time|date_format:"%H:%M"}</td>
			<td>{$elawa.length|date_format:"%H:%M"}</td>
		</tr>
    {foreachelse}
		<tr><td colspan="5"><b>Keine vorhanden</b></td></tr>
    {/foreach}

	</tbody>
</table>
<br pagebreak="true"/>
<p style="text-align: center"><h2>Nachrichten</h2></p>
{foreach $messages as $message}
	<b>Betreff:</b> {$message.title}<br>
	Gültig vom {$message.validFrom|date_format:"%d.%m.%y"} bis zum {$message.validTo|date_format:"%d.%m.%y"}<br>
	<b>Absender:</b> {$message.author}<br>
	<b>Rückgabe:</b>
	{if $message.return == "noReturn"}
		Nicht gefordert
	{elseif $message.return == "shouldReturn"}
		Nicht erfolgt
	{elseif $message.return == "hasReturned"}
		Erfolgte
	{/if}<br><br>
	<p style="border: 1px solid black;">
		{$message.text|replace:"{ldelim}vorname{rdelim}":$user.forename|replace:"{ldelim}name{rdelim}":$user.name|replace:"{ldelim}klasse{rdelim}":$user.forename|replace:"{ldelim}kartennummer{rdelim}":$card.cardnumber}
	</p>
	<hr><br><br>
{foreachelse}
	<b>Keine vorhanden</b>
{/foreach}

<br pagebreak="true"/>
<p style="text-align: center"><h2>Schulbuchausleihe</h2></p>
<table border="1" cellpadding="5">
	<thead>
	<tr>
		<th><b>Schuljahr</b></th>
		<th><b>Variante</b></th>
		<th><b>Bezahlter Betrag</b></th>
	</tr>

	</thead>
	<tbody>
	{foreach $schbas as $entry}
		<tr>
			<td>{$entry.label}</td>
			<td>{$entry.loanChoice}</td>
			<td>{$entry.payedAmount}€ / {$entry.amountToPay}€</td>
		</tr>
    {foreachelse}
		<tr><td colspan="3"><b>Keine vorhanden</b></td></tr>
    {/foreach}
	</tbody>
</table><br>

<h3>Aktuelle Ausleihen</h3>
<table border="1" cellpadding="5">
	<thead>
	<tr>
		<th><b>Titel</b></th>
		<th><b>Exemplarnummer</b></th>
		<th><b>ausgeliehen am</b></th>
	</tr>

	</thead>
	<tbody>
	{foreach $lendings as $lending}
		<tr>
			<td>{$lending.title}</td>
			<td>{$lending.year_of_purchase} / {$lending.exemplar}</td>
			<td>{$lending.lend_date|date_format:"%d.%m.%y"}</td>
		</tr>
	{foreachelse}
		<tr><td colspan="3"><b>Keine vorhanden</b></td></tr>
    {/foreach}

	</tbody>
</table><br>

<h3>Selbstkäufe</h3>
{foreach $selfbuy as $buy}
	{$buy.title}
{foreachelse}
	<b>Keine vorhanden</b>
{/foreach}






</html>