{extends file=$UserParent}

{block name=popup_dialogs append}
	<!-- Dialog for adding schoolyears to the user -->
	<div id="grade-schoolyear-modal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"
					aria-hidden="true">
						&times;
					</button>
					<h4 class="modal-title">Schuljahr und Klasse hinzufügen</h4>
				</div>
				<div class="modal-body">
					<p>
						Im folgenden können sie einen Schüler zu einem Schuljahr sowie einer Klasse hinzufügen.
					</p>
					<p>
						Der neue Schüler ist im Schuljahr...
					</p>
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon">
								<span class="fa fa-calendar fa-fw"></span>
							</div>
							<div class="input-group-addon">
								Schuljahr:
							</div>
							<select id="modal-schoolyearId" name="schoolyearid"
							class="form-control">
								{foreach $schoolyears as $syId => $syName}
									<option value="{$syId}">
											{$syName}
									</option>
								{/foreach}
							</select>
						</div>
					</div>
					<p>
						in der Klasse...
					</p>
					<div class="form-group">
						<div class="input-group">
							<div class="input-group-addon">
								<span class="fa fa-users"></span>
							</div>
							<div class="input-group-addon">
								Klasse:
							</div>
							<select id="modal-gradeid" name="gradeid" class="form-control">
								{foreach $grades as $gradeId => $gradeName}
									<option value="{$gradeId}">
											{$gradeName}
									</option>
								{/foreach}
							</select>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">
						{t}Cancel{/t}
					</button>
					<button id="grade-schoolyear-submit" type="button" class="btn btn-primary">
						{t}Add to user{/t}
					</button>
				</div>
			</div>
		</div>
	</div>
{/block}


{block name=html_snippets append}

<script type="text/template" id="grade-schoolyear-snippet">
	<div class="input-group form-group">
		<span class="input-group-addon">
			<span class="fa fa-calendar fa-fw"></span>
		</span>
		<span name="schoolyearid" class="input-group-addon">
			Im Schuljahr
		</span>
		<select class="schoolyear-selector form-control" name="schoolyearid">
			{foreach $schoolyears as $syId => $syName}
				<option value="{$syId}">
						{$syName}
				</option>
			{/foreach}
		</select>
		<span name="schoolyearid" class="input-group-addon">
			In Klasse
		</span>
		<select class="grade-selector form-control" name="gradeid">
			{foreach $grades as $gradeId => $gradeName}
				<option value="{$gradeId}">
						{$gradeName}
				</option>
			{/foreach}
		</select>
		<span class="input-group-btn">
			<a class="btn btn-danger grade-schoolyear-remove">
				<span class="fa fa-trash-o"></span>
				Verbindung löschen...
			</a>
		</span>
	</div>
</script>
{/block}


{block name=content}
<h3 class="module-header">Benutzer ändern</h3>
<form id="change-form" role="form" action="#" method="post">
	<fieldset>
		<legend>Persönliche Daten</legend>
		<div class="row">
			<input id="userId" name="userId" type="hidden" value="{$user.ID}" />
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-newspaper-o"></span></span>
					<input name="forename" id="forename" class="form-control" type="text"
						placeholder="{t}Forename{/t}" required minlength="3"
						value="{$user.forename}" data-toggle="tooltip"
						title="{t}Forename{/t}" />
				</div>
			</div>
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-newspaper-o"></span></span>
					<input name="lastname" id="lastname" class="form-control" type="text"
						placeholder="{t}Lastname{/t}" required minlength="3"
						value="{$user.name}" title="{t}Lastname{/t}" />
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-user"></span></span>
					<input name="username" id="username" class="form-control" type="text"
						placeholder="{t}Username{/t}" required minlength="3"
						value="{$user.username}" title="{t}Username{/t}" />
				</div>
			</div>
		</div>

		<!-- Password -->
		<div class="row">
			<div class="col-xs-12 col-md-6">
				<div class="form-group">
					<label for="password-switch">{t}Change password?{/t}</label>
					<input type="checkbox" id="password-switch" name="password-switch"
					data-on-text="Ja" data-off-text="Nein" data-size="small"
					data-on-color="warning" data-off-color="default"/>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-lock"></span></span>
					<input name="password" id="password" class="form-control" type="password" placeholder="{t}Password{/t}" disabled required/>
				</div>
			</div>
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-lock"></span></span>
					<input name="password-repeat" id="password-repeat" class="form-control"
					type="password" placeholder="{t}Repeat password{/t}"
					disabled required />
				</div>
			</div>
		</div>

		<!-- more userdata -->
		<div class="row">
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-envelope-o"></span></span>
					<input name="email" id="email" class="form-control" type="text"
						placeholder="{t}Email-address{/t}" email="true"
						value="{$user.email}" title="{t}Emailaddress{/t}" />
				</div>
			</div>
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-phone fa-fw"></span></span>
					<input name="telephone" id="telephone" class="form-control" type="
						text" placeholder="{t}Telephone-number{/t}"
						value="{$user.telephone}" title="{t}Telephonenumber{/t}" />
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-calendar fa-fw"></span></span>
					<input name="birthday" id="birthday" class="form-control" type="text"
						placeholder="{t}Birthday{/t}" data-provide="datepicker"
						data-date-format="dd.mm.yyyy" data-date-language="de"
						value="{$user.birthday|date_format:'%d.%m.%Y'}" date
						title="{t}Birthday{/t}" />
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-xs-12 col-md-6">
				<div class="form-group">
					<label for="account-locked">{t}Account locked{/t}</label>
					<input type="checkbox" id="account-locked" name="account-locked"
					data-on-text="Ja" data-off-text="Nein" data-size="small"
					data-on-color="danger" data-off-color="success"
					{if $user.locked}checked{/if}
					/>
				</div>
			</div>
		</div>

		<!-- usergroups -->
		<div class="row">
			<div class="col-xs-12 col-md-6 form-group">
				<fieldset id="usergroups" >
					<legend>{t}Usergroups{/t}</legend>
					<ol>
						{* Define function allowing to recursively make a list*}
						{function name=displayGroup group=0}
							{$hasChilds = $group->childsGet()}

							<a groupId="{$group->getId()}" class="group-identifier label
								{if in_array($group->getId(), $userInGroups)}
									label-success active
								{else}
									label-default
								{/if}">
								{$group->getName()}
							</a>

							{if $hasChilds}
								<a class="btn btn-default btn-xs expand"
								data-toggle="collapse"
								data-target="#group-childs-list-for-{$group->getId()}">
									<span class="fa fa-minus"></span>
								</a>

								<ol id="group-childs-list-for-{$group->getId()}"
									class="collapse in">
									{foreach $group->childsGet() as $child}
										<li>
											{displayGroup group=$child}
										</li>
									{/foreach}
								</ol>
							{/if}
						{/function}

						{displayGroup group=$usergroups}
					</ol>
				</fieldset>
				<div id="usergroups" class="col-xs-12 col-md-6"></div>
				<div class="clearfix"></div>
			</div>
		</div>

		<!-- schoolyears & grades -->
		<div class="row">
			<div class="col-xs-12 col-md-12">
				<fieldset id="grade-schoolyears">
					{foreach $gradesAndSchoolyearsOfUser as $gSy}
						<div class="input-group form-group">
							<span class="input-group-addon">
								<span class="fa fa-calendar fa-fw"></span>
							</span>
							<span name="schoolyearid" class="input-group-addon">
								Im Schuljahr
							</span>
							<select class="schoolyear-selector form-control" name="schoolyearid">
								{foreach $schoolyears as $syId => $syName}
									<option value="{$syId}"
										{if $syId == $gSy.schoolyearId} selected {/if}>
											{$syName}
									</option>
								{/foreach}
							</select>
							<span name="schoolyearid" class="input-group-addon">
								In Klasse
							</span>
							<select class="grade-selector form-control" name="gradeid">
								{foreach $grades as $gradeId => $gradeName}
									<option value="{$gradeId}"
										{if $gradeId == $gSy.gradeId} selected {/if}>
											{$gradeName}
									</option>
								{/foreach}
							</select>
							<span class="input-group-btn">
								<a class="btn btn-danger grade-schoolyear-remove">
									<span class="fa fa-trash-o"></span>
									Verbindung löschen...
								</a>
							</span>
						</div>
					{/foreach}
					<legend>{t}Grades and schoolyears{/t}</legend>
					<a class="btn btn-info btn-sm" data-toggle="modal"
					data-target="#grade-schoolyear-modal">
						Schüler zu einem Schuljahr hinzufügen...
					</a>
				</fieldset>
			</div>
		</div>
	</fieldset>

	<fieldset>
		<legend>Babesk</legend>
		<div class="row">
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-credit-card"></span></span>
					<input id="cardnumber" name="cardnumber" class="form-control"
						type="text" placeholder="{t}Cardnumber{/t}" maxlength="10"
						minlength="4"  value="{$cardnumber}" title="{t}Cardnumber{/t}" />
				</div>
			</div>
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-column">
					<label for="issoli">
						Ist Teilhabepaket-Nutzer?
					</label>
					<span>
						<input id="issoli" name="issoli" type="checkbox" data-on-text="Ja"
							data-off-text="Nein" data-on-color="warning"
							{if $user.soli}checked{/if} />
					</span>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon">
						<span class="fa fa-users"></span>
					</span>
					<label for="pricegroup" class="input-group-addon">
						{t}Pricegroup{/t}
					</label>

					{if empty($pricegroups)}
							<input id="pricegroupId" name="pricegroupId" class="form-control" type="text" placeholder="{t}No pricegroup existing{/t}" disabled />
					{else}
						<select class="form-control" id="pricegroupId" name="pricegroupId"
						title="{t}Pricegroup{/t}">
							<option value="">Keine</option>
							{html_options options=$pricegroups selected="{$user.GID}"}
						</select>
					{/if}
				</div>
			</div>
			<div class="col-xs-12 col-md-6 form-group">
				<div class="input-group">
					<span class="input-group-addon"><span class="fa fa-eur"></span></span>
					<input id="credits" name="credits" class="form-control" type="text" placeholder="{t}Credits{/t}" maxlength="5" number="true"
					value="{$user.credit}" title="{t}Credits{/t}" />
				</div>
			</div>
		</div>
	</fieldset>
	<input type="submit" value="{t}Submit{/t}" class="btn btn-primary" />
	<a class="btn btn-default pull-right"
	href="index.php?module=administrator|System|User">
		{t}Cancel{/t}
	</a>
</form>

<div id="additional-settings">
</div>
{/block}


{block name="style_include" append}
<link rel="stylesheet" href="{$path_css}/administrator/System/User/register.css"
	type="text/css" />
<link rel="stylesheet" href="{$path_css}/datepicker3.css" type="text/css" />
<link rel="stylesheet" href="{$path_css}/bootstrap-switch.min.css"
	type="text/css" />
<link rel="stylesheet" href="{$path_css}/bootstrap-multiselect.css"
	type="text/css" />
{/block}


{block name="js_include" append}
<script type="text/javascript"
	src="{$path_js}/vendor/moment-with-locales.min.js">
</script>
<script type="text/javascript"
	src="{$path_js}/vendor/datepicker/bootstrap-datepicker.min.js">
</script>
<script type="text/javascript" src="{$path_js}/vendor/datejs/date.min.js">
</script>
<script type="text/javascript"
	src="{$path_js}/vendor/datepicker/locales/bootstrap-datepicker.de.js">
</script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js">
</script>
<script type="text/javascript"
	src="{$path_js}/vendor/jquery-validate/jquery.validate.min.js">
</script>
<script type="text/javascript"
	src="{$path_js}/vendor/jquery-validate/localization/messages_de.js">
</script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js">
</script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js">
</script>
<!--
<script type="text/javascript"
	src="{$path_js}/administrator/System/User/change-user.js">
</script>
-->
<script type="text/javascript"
	src="{$path_js}/dist/administrator/System/User/main.js">
</script>
<script type="text/javascript"
	src="{$path_js}/administrator/System/User/change.js">
</script>
{/block}
