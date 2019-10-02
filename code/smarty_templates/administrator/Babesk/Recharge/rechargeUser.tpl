{extends file=$base_path}{block name=content}
<h3 class="module-header">Bitte Betrag Eingeben</h3>

<h4>Name:</h4>
	<select name="UID" id="user-select">
	{foreach $users as $user}
		<option value='{$user.ID}'> {$user.forename} {$user.name} ({$user.priceGroup}) {$user.grade}</option>
	{/foreach}
	</select><br><br>
	
<p class="alert alert-success" id="soli">
	
</p>

<p class="alert alert-info" id="maxRecharge">

</p>
<form action="index.php?module=administrator|Babesk|Recharge|RechargeUser" method="post">
	<div class="form-group">
		<label for="amount">Betrag</label>
		<input type="text" id="amount" class="form-control" name="amount"
			autofocus />
	</div>
	<input type="hidden" value="{$uid}" name="uid" id="uid">
	<input type="submit" class="btn btn-default" value="Submit" />
</form>

{/block}

{block name=js_include append}
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-switch.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootstrap-multiselect.min.js"></script>
<script type="text/javascript" src="{$path_js}/vendor/bootbox.min.js"></script>
<script type="text/javascript" src="{$path_js}/administrator/Babesk/Recharge/RechargeUser/rechargeUser.js"></script>

{/block}
