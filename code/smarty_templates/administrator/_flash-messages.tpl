{if isset($_flashDanger)}
{foreach $_flashDanger as $flash}
	<div class="alert alert-danger">
		<button type="button" class="close" data-dismiss="alert"
			aria-hidden="true">
			&times;
		</button>
		<strong>{$flash.title}</strong> {$flash.msg}
	</div>
	{/foreach}
{/if}

{if isset($_flashWarning)}
{foreach $_flashWarning as $flash}
	<div class="alert alert-warning">
		<button type="button" class="close" data-dismiss="alert"
			aria-hidden="true">
			&times;
		</button>
		<strong>{$flash.title}</strong> {$flash.msg}
	</div>
	{/foreach}
{/if}

{if isset($_flashInfo)}
{foreach $_flashInfo as $flash}
	<div class="alert alert-info">
		<button type="button" class="close" data-dismiss="alert"
			aria-hidden="true">
			&times;
		</button>
		<strong>{$flash.title}</strong> {$flash.msg}
	</div>
	{/foreach}
{/if}

{if isset($_flashSuccess)}
{foreach $_flashSuccess as $flash}
	<div class="alert alert-success">
		<button type="button" class="close" data-dismiss="alert"
			aria-hidden="true">
			&times;
		</button>
		<strong>{$flash.title}</strong> {$flash.msg}
	</div>
	{/foreach}
{/if}