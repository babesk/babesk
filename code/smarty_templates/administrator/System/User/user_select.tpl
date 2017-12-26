{extends file=$UserParent}{block name=content}

<h2 class="module-header">{t}Usersettings-Mainmenu{/t}</h2>

<fieldset>
	<legend>
		{t}General{/t}
	</legend>
	<ul class="submodulelinkList" >
		<li>
			<a href="index.php?module=administrator|System|User|Register">
				{t}Register a User{/t}
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|System|User|DisplayAll">
				{t}Show Users{/t}
			</a>
		</li>
	</ul>
</fieldset>

<fieldset>
	<legend>
		{t}Bulk-Changes{/t}
	</legend>
	<ul class="submodulelinkList" >
		<li>
			<a href="index.php?module=administrator|System|User|CreateUsernames">
				{t}Assign Usernames to User automatically{/t}
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|System|User|RemoveSpecialCharsFromUsernames">
				{t}Remove Specialcharacters from Usernames{/t}
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|System|User|ResetAllUserPasswords">
				{t}Reset passwords of users to preset passwords{/t}
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|System|User|UserUpdateWithSchoolyearChange">
				{t}Update users with Schoolyear-change{/t}
			</a>
		</li>
	</ul>
</fieldset>

{/block}
