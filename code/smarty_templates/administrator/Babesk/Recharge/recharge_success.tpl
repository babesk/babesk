{extends file=$base_path}{block name=content}

<div class="panel panel-success">
		<div class="panel-heading">
			<h3 class="panel-title">Geldaufladung erfolgreich!</h3>
		</div>
		<div class="panel-body">
			Dem Benutzer "{$username}" wurden {$amount}&euro; gutgeschrieben
		</div>
		<div class="panel-footer">
			<a class="btn btn-primary" href="index.php?module=administrator|Babesk|Recharge|Recharge{if $userform}User{else}Card{/if}">weiter zur n&auml;chsten Geldaufladung</a>
		</div>
</div>

{/block}

{block name=js_include append}

<script type="text/javascript">
var ref = 'index.php?module=administrator|Babesk|Recharge|RechargeCard';

$(document).ready(function() {
	$('body').on('keypress', function(event) {
		if(event.which == '13') {
			location.href = ref;
		}
	});
});
</script>

{/block}
