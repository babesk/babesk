<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>BaBeSK Login</title>
	<link rel="stylesheet" href="{$path_css}/bootstrap-theme.min.css"
		type="text/css" />
	<link rel="stylesheet" href="{$path_css}/bootstrap.min.css"
		type="text/css" />
	<link rel="stylesheet" href="{$path_css}/toastr.min.css" type="text/css" />
	<link rel="stylesheet" href="{$path_css}/administrator/login.css"
		type="text/css" />
	<link rel="shortcut icon" href="adminicon.ico" />
</head>

<body>
<div id="login_header">
	<h1>BaBeSK Administrator Bereich</h1>
</div>
<div id="login">
	{if $status != ''}
	   <h4>{$status}</h4>
	{/if}
	<form class="form-horizontal" action="index.php" method="post">
		<fieldset>
			<legend>Einloggen</legend>
			<div class="form-group">
				<label for="username" class="col-sm-5 control-label">
					Benutzername
				</label>
				<div class="col-sm-7">
					<input id="username" class="form-control" type="text"
						name="Username" autofocus />
				</div>
			</div>
			<div class="form-group">
				<label for="password" class="col-sm-5 control-label">Passwort</label>
				<div class="col-sm-7">
					<input id="password" class="form-control" type="password"
						name="Password" />
				</div>
			</div>
		</fieldset>
		<input class="btn btn-primary" type="submit" value="Einloggen" />
	</form>
</div>
<div id="footer">
	<p>BaBeSK {$babesk_version} &copy; 2011 Lessing Gymnasium Uelzen {$smarty.version}</p>
</div>

<script src="http{if $https_enabled}s{/if}://code.jquery.com/jquery-1.9.1.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$('input[name=Username]').keypress(function(event){
			if (event.which == '13') {
				event.preventDefault();
			}
		}
	);
	$('input[name=Username]').focus();
});
</script>

</body>
</html>
