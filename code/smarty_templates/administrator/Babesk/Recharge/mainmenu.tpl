{extends file=$inh_path}{block name="content"}

<h2 class="module-header">
	Kartenaufladung Menü
</h2>

<fieldset class="smallContainer">
	<legend>
		{t}Standard Actions{/t}
	</legend>
	<ul class="submodulelinkList">
		<li>
			<a href="index.php?module=administrator|Babesk|Recharge|RechargeCard">
			{t}Recharge a Card{/t}
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|Babesk|Recharge|RechargeUser">
			Nutzerkonto aufladen
			</a>
		</li>
		<li>
			<a href="index.php?module=administrator|Babesk|Recharge|PrintRechargeBalance">
				{t}Print Recharge Balance{/t}
			</a>
		</li>
	</ul>
</fieldset>

{/block}
