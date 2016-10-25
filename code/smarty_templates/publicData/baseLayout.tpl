<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>{block name=title}BaBeSK{/block}</title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
{block name=html_head}{/block}
</head>

<body
	<div id="header">
	{nocache}
	{block name=header}
	{/block}
	{/nocache}
	</div>

	{if $_userMsgOutput or $_userErrorOutput}
	<div id="main">
		<div id="content">
		{foreach $_userErrorOutput as $error}
		<p class="error">{$error}</p>
		{/foreach}
		{foreach $_userMsgOutput as $msg}
		<p>{$msg}</p>
		{/foreach}
		</div>
	</div>
	{/if}

	<div id="main">
		<div id="content">{block name=content}{/block}</div>
	</div>
	<div id="footer">
		<p>{block name=signature}<div id="footer">
    <p>BaBeSK {$babesk_version} &copy; 2011 Lessing Gymnasium Uelzen</p>
</div>{/block}</p>
		{block name=footer}{/block}
		{block name=links}{/block}
	</div>
</body>
</html>
