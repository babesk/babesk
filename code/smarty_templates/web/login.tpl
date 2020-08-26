{extends file="web/baseLayout.tpl"}{block name="content"}

	<div id="login" class="container">
		<div class="col-xs-12 col-md-10 col-lg-8 col-md-offset-1 col-lg-offset-2">
			<div class="panel bg-fit panel-primary">
				<div class="panel-body">
				{$error}
						<input id="username-inp" name="login" type="text" class="form-control input-lg" placeholder="{t}Username{/t}" autofocus>
						<input id="password-inp" name="password" type="password" class="form-control input-lg" placeholder="{t}Password{/t}">
						<button type="button" id="login-confirm" type="button"
							class="btn btn-primary btn-lg">
							Login
						</button>
						{if isset($webLoginHelptext) && $webLoginHelptext}
							<a class="btn btn-info btn-lg pull-right" href="../publicData/index.php?section=GeneralPublicData|LoginHelp">Hilfe</a>
						{/if}
				</div>
			</div>
		</div>
	</div>

{/block}

{block name="module_selector"}
	<div id="module_selector" class="container">
	<h3>Login</h3>
	</div>
{/block}

{block name="nav_user_dropdown"}{/block}
{block name="nav_help_button"}{/block}
{block name="footer_help_button"}{/block}
{block name="nav_home"}{/block}
{block name="footer_actions"}{/block}

{block name="style_include" append}
	<link rel="stylesheet" href="{$path_css}/web/login.css" type="text/css" />
{/block}

{block name="js_include" append}
	<script type="text/javascript" src="{$path_js}/login.js"></script>
{/block}
