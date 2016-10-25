<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		{if isset($redirection)}
			{*If this Var is set, redirect the user to another Website*}
			<meta HTTP-EQUIV="REFRESH" content="{$redirection.time};
			url=index.php?section={$redirection.target}" />
		{/if}

		{block name="style_include"}
		<link rel="stylesheet" href="{$path_css}/bootstrap-theme.min.css" type="text/css" />
		<link rel="stylesheet" href="{$path_css}/bootstrap.min.css" type="text/css" />
		<link rel="stylesheet" href="{$path_css}/administrator.css" type="text/css" />
		<link rel="stylesheet" href="{$path_css}/font-awesome.min.css" type="text/css" />
		<link rel="stylesheet" href="{$path_css}/toastr.min.css" type="text/css" />
		{/block}

		<link rel="shortcut icon" href="adminicon.ico" />
		<title>{$title|default:'BaBeSK'}</title>
	</head>

	<body>
		{*-----------------------------------------------------
		 * The top-navigation
		 *}
		<div id="navigation" class="navbar-default navbar-fixed-top"
			role="navigation">
			<div class="">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<a class="navbar-brand" href="index.php">BaBeSK</a>
				</div>
				<div class="navbar-collapse collapse">
					<ul class="nav navbar-nav">
						{block name="nav_home"}
							<li>
								<a href="index.php">{t}Home{/t}</a>
							</li>
						{/block}
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<!-- <li>
							<button class="btn btn-dark navbar-btn btn-sm sidebar-toggle"
								data-toggle="tooltip" data-placement="bottom"
								title="Seitennavigation togglen">
								<span class="fa fa-th-list fa-fw"></span>
							</button>
						</li>
						-->
						{block name="nav_user_dropdown"}
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown">
									{$username} <b class="caret"></b>
								</a>
								<ul id="dropdown-user" class="dropdown-menu">
									<li>
										<a href="index.php?action=logout">{t}Logout{/t}</a>
									</li>
								</ul>
							</li>
						{/block}
					</ul>
				</div>
			</div>
		</div>

		<div id="body-wrapper"
			{if !isset($smarty.get.hideSidebar)}class="show-sidebar"{/if}>
			<button class="sidebar-toggle"
				data-toggle="tooltip" data-placement="bottom"
				title="Seitennavigation togglen">
					<span class="fa fa-chevron-circle-right"></span>
			</button>
			{*-----------------------------------------------------
			 * The sidebar-navigation
			 *}
			<div class="sidebar">
				{*-----------------------------------------------------
				 * Breadcrumb displaying which module we use right now
				 *}
				{block name="module_breadcrumb"}
					<ul class="module_breadcrumb breadcrumb">
						<li>administrator</li>
						{$level = 2}
						{if $moduleExecCommand}
							{while $moduleExecCommand->moduleAtLevelGet($level)}
							<li>
								{if $level > 2}
								<a href="index.php?module={$moduleExecCommand->pathGet('|', $level + 1)}">
								{/if}
									{$moduleExecCommand->moduleAtLevelGet($level)}
								{if $level > 2}
								</a>
								{/if}
							</li>
								{$level = $level + 1}
							{/while}
						{/if}
					</ul>
				{/block}
				<div id="sidebar-module-selection">
					<ul id="sidebar-base-nav" class="nav">
						{foreach $headmodules as $headmodule}
							{if $headmodule->isDisplayInMenuAllowed() && $headmodule->isEnabled() && $headmodule->userHasAccess()}
								{$hModulepath = $moduleGenMan->modulePathGet($headmodule)}
								<li>
									<a href="#" data-toggle="collapse"
									data-target="#sidebar-module-{str_replace('/', '_', $hModulepath)}" data-parent="#sidebar-base-nav"
									class="sidebar-folder">
										<div class="text-icon-spacer">
												<span class="fa fa-{$headmodule->getName()} fa-fw module-icon"></span>
												{_g('modulepath_'|cat:$hModulepath)}
												<span class="toggle-icon fa fa-plus pull-right">
												</span>
												<span class="clearfix"></span>
										</div>
									</a>
									<ul id="sidebar-module-{str_replace('/', '_', $hModulepath)}"
									class="nav collapse">
										{foreach $headmodule->getChilds() as $module}
											{if $module->isDisplayInMenuAllowed() && $module->isEnabled() && $module->userHasAccess()}
												{$modulepath = $moduleGenMan->modulePathGet($module)}
												<li>
													<a href="index.php?module=administrator|{$headmodule->getName()}|{$module->getName()}">
														<span>{_g('modulepath_'|cat:$modulepath)}</span>
													</a>
												</li>
											{/if}
										{/foreach}
									</ul>
								</li>
							{/if}
						{/foreach}
						<li>
							<a href="#" data-toggle="collapse" data-target="#headmod-submenu-1" class="collapsed sidebar-folder">
								<span>Custom folder</span>
								<span class="toggle-icon fa fa-plus pull-right"></span>
							</a>
							<ul id="headmod-submenu-1" class="nav collapse">
								<li>
									<a href="#">Custom Link</a>
								</li>
								<li>
									<a href="#" data-toggle="collapse" data-target="#headmod-submenu-3" class="collapsed sidebar-folder">
										<span>Custom nested folder</span>
										<span class="toggle-icon fa fa-plus pull-right"></span>
									</a>
									<ul id="headmod-submenu-3" class="nav collapse">
										<li>
											<a href="#">Custom Link</a>
										</li>
										<li>
											<a href="#">Another Link that could do something...</a>
										</li>
									</ul>
								</li>
								<li class="spacer"></li>
								<li>
									<a href="#">Hey, there is a spacer over me!</a>
								</li>

							</ul>
						</li>
					</ul>
				</div>
			</div>

			<div id="main_wrapper" class="">
				<div class="container">
					{include "{$path_smarty_tpl}/administrator/_flash-messages.tpl"}
				</div>
				<div class="container-fluid">
					<div class="row">
						<div id="content">
							<noscript>
								<div class="panel panel-danger">
									<div class="panel-heading">
										<div class="panel-title">
											Javascript ist deaktiviert
										</div>
									</div>
									<div class="panel-body">
											Ihr Browser hat JavaScript ausgestellt. Diese Seite funktioniert nur dann, wenn sie Javascript aktiviert haben!
										<a class="btn btn-primary pull-right" href="http://www.enable-javascript.com/de/" target="_blank">
											Aktivierungsanleitung
										</a>
									</div>
								</div>
							</noscript>
							{block name="filling_content"}
								<div class="container">
									{if $_userErrorOutput}
										<div class="col-md-8 col-md-offset-2 error-container">
											<div class="panel panel-danger">
												<div class="panel-heading">
													<div class="panel-title">
														<h3 class="icon-container col-xs-2 col-sm-1">
															<span class="fa fa-exclamation-triangle"></span>
														</h3>
														<span class="col-xs-10 col-sm-11">
															{t}Sorry! An error occured. We could not handle your request.{/t}
														</span>
														<div class="clearfix"></div>
													</div>
												</div>
												<div class="panel-body">
													<div>
														{if is_array($_userErrorOutput)}
															{foreach $_userErrorOutput as $msg}
																{$msg}
															{/foreach}
														{else}
															{$_userErrorOutput}
														{/if}
													</div>
												</div>

												<a class="btn btn-primary pull-right"
													href="{if $backlink}{$backlink}
														{else}javascript: history.go(-1){/if}">
													{t}back{/t}
												</a>
											</div>
										</div>
									{/if}
									{if $_userWarningOutput}
										<div class="col-md-8 col-md-offset-2 message-container">
											<div class="panel panel-warning">
												<div class="panel-heading">
													<div class="panel-title">
														<h3 class="icon-container col-xs-2 col-sm-1">
															<span class="fa fa-info-circle"></span>
														</h3>
														<span class="col-xs-10 col-sm-11">
															Warnung
														</span>
														<div class="clearfix"></div>
													</div>
												</div>
												<div class="panel-body">
														{if is_array($_userWarningOutput)}
															{foreach $_userWarningOutput as $msg}
																<div>{$msg}</div>
															{/foreach}
														{else}
															{$_userWarningOutput}
														{/if}
												</div>

												<a class="btn btn-primary pull-right"
													href="{if $backlink}{$backlink}
														{else}javascript: history.go(-1){/if}">
													{t}back{/t}
												</a>
											</div>
										</div>
									{/if}
									{if $_userMsgOutput}
										<div class="col-md-8 col-md-offset-2 message-container">
											<div class="panel panel-info">
												<div class="panel-heading">
													<div class="panel-title">
														<h3 class="icon-container col-xs-2 col-sm-1">
															<span class="fa fa-info-circle"></span>
														</h3>
														<span class="col-xs-10 col-sm-11">
															{t}Information{/t}
														</span>
														<div class="clearfix"></div>
													</div>
												</div>
												<div class="panel-body">
														{if is_array($_userMsgOutput)}
															{foreach $_userMsgOutput as $msg}
																<div>{$msg}</div>
															{/foreach}
														{else}
															{$_userMsgOutput}
														{/if}
												</div>

												<a class="btn btn-primary pull-right"
													href="{if $backlink}{$backlink}
														{else}javascript: history.go(-1){/if}">
													{t}back{/t}
												</a>
											</div>
										</div>
									{/if}
									{if $_userSuccessOutput}
										<div class="col-md-8 col-md-offset-2 success-container">
											<div class="panel panel-success">
												<div class="panel-heading">
													<div class="panel-title">
														<h3 class="icon-container col-xs-2 col-sm-1">
															<span class="fa fa-check"></span>
														</h3>
														<span class="col-xs-10 col-sm-11">
															{t}Success!{/t}
														</span>
														<div class="clearfix"></div>
													</div>
												</div>
												<div class="panel-body">
													{if is_array($_userSuccessOutput)}
														{foreach $_userSuccessOutput as $msg}
															<div>{$msg}</div>
														{/foreach}
													{else}
														{$_userSuccessOutput}
													{/if}
												</div>

												<a class="btn btn-primary pull-right"
													href="{if $backlink}{$backlink}
														{else}javascript: history.go(-1){/if}">
													{t}back{/t}
												</a>
											</div>
										</div>
									{/if}
								</div>
							{/block}
						</div>
						<div class="clearfix"></div>
					</div>
				</div>
				<div class="container">
					{block name="content"}
					{/block}
				</div>
			</div>
		</div>

		{block name=html_snippets}
			{* put your bigger html-code-snippets you need to add with javascript in
			 * here
			 *}
		{/block}

		{block name=popup_dialogs}
			{*You can append modal dialogs here to minimize the interaction with
			 *other parts of the page
			 *}
		{/block}

		{block name="js_include"}
			<script type="text/javascript" src="{$path_js}/dist/base-bundle.js">
			</script>
			<script type="text/javascript" src="{$path_js}/administrator/main.js">
			</script>
		{/block}
	</body>
</html>
