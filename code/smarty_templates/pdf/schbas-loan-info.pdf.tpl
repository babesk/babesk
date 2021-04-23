<div style="page-break-inside:avoid">
	<h2 align="center">
		{$coverLetter['title']}
	</h2>
	<p style="text-align: right;">
		{$letterDate}
	</p>
	{$coverLetter['text']}
</div>

<div style="page-break-inside:avoid">
	<h2 align="center">
		Lehrbücher Jahrgang {$gradelevel}
	</h2>
	<table border="0" bordercolor="#FFFFFF" style="background-color:#FFFFFF" width="100%" cellpadding="0" cellspacing="1">
		<tr style="font-weight:bold; text-align:center;">
			<th>Fach</th>
			<th>Titel</th>
			<th>Verlag</th>
			<th>ISBN-Nr.</th>
			<th>Preis</th>
		</tr>
		{foreach $books as $book}
			<tr>
				<td>
					{$book['subject']}
				</td>
				<td>
					{$book['title']}
				</td>
				<td>
					{$book['publisher']}
				</td>
				<td>
					{$book['isbn']}
				</td>
				<td align="right">
					{number_format($book['price'], 2)} €
				</td>
			</tr>
		{/foreach}
	</table>
	<p></p>
	<table style="border:solid" width="75%" cellpadding="2" cellspacing="2">
		<tr>
			<td>Leihgebühr: </td>
			<td>{$feeNormal} Euro</td>
		</tr>
		<tr>
			<td>(3 und mehr schulpflichtige Kinder:</td>
			<td>{$feeReduced} Euro)</td>
		</tr>
		<tr>
			<td>Kontoinhaber:</td>
			<td>{$bankData[0]}</td>
		</tr>
		<tr>
			<td>Kontonummer:</td>
			<td>{$bankData[1]}</td>
		</tr>
		<tr>
			<td>Bankleitzahl:</td>
			<td>{$bankData[2]}</td>
		</tr>
		<tr>
			<td>Kreditinstitut:</td>
			<td>{$bankData[3]}</td>
		</tr>
	</table>
</div>

{if $textTwo['text'] || $textThree['text']}
<div style="page-break-inside:avoid">
	<h2 align="center">
		Weitere Informationen
	</h2>
	{if $textOne['text']}
		<h3>
			{$textOne['title']}
		</h3>
		{$textOne['text']}
	{/if}
	{if $textTwo['text']}
		<h3>
			{$textTwo['title']}
		</h3>
		{$textTwo['text']}
	{/if}
	{if $textThree['text']}
		<h3>
			{$textThree['title']}
		</h3>
		{$textThree['text']}
	{/if}
</div>
{/if}