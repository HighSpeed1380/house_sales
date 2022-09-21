<div class="highlight">
	{if $step != "rlPayment::POST_URL"|constant}
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='order_details' name=$lang.order_details}

		<div class="table-cell">
			<div class="name"><div><span>{$lang.item}</span></div></div>
			<div class="value">
				{$transaction.Item_name}
			</div>
		</div>
		<div class="table-cell">
			<div class="name"><div><span>{$lang.total}</span></div></div>
			<div class="value">
				{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
				{$transaction.Total|number_format:2:'.':','}
				{if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
			</div>
		</div>
		{if $transaction.Status == 'paid'}
			<div class="table-cell">
				<div class="name"><div><span>{$lang.txn_id}</span></div></div>
				<div class="value">
					{$transaction.Txn_ID}
				</div>
			</div>
			<div class="table-cell">
				<div class="name"><div><span>{$lang.payment_gateway}</span></div></div>
				<div class="value">
					{$transaction.Gateway}
				</div>
			</div>
		{/if}

		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

		<!-- payment gateways -->
		{if $step == "rlPayment::CHECKOUT_URL"|constant}
			{gateways}
		{/if}
		<!-- end payment gateways -->	
	{/if}
</div>