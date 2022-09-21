<!-- payment history tpl -->

{if $transactions}

	<div class="transactions list-table content-padding">
		<div class="header">
			<div class="center" style="width: 40px;">#</div>
			<div>{$lang.item}</div>
			<div style="width: 280px;">{$lang.transaction_info}</div>
			<div style="width: 65px;">{$lang.status}</div>
		</div>

		{foreach from=$transactions item='item' name='transactionF'}
			{math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.transactionF.iteration current=$pInfo.current per_page=$config.transactions_per_page}
			<div class="row">
				<div class="center iteration no-flex">{$iteration}</div>
				<div data-caption="{$lang.item}" class="content">
					{if $item.Plan_name || $item.Item_name}{strip}
						{$item.Plan_name}
						<div>
							{if $item.link}
								<a href="{$item.link}">{$item.Item_name}</a>
							{else}
								{$item.Item_name}
							{/if}
						</div>{/strip}
					{else}
						<span class="red">{$lang.item_not_available}</span>
					{/if}
				</div>
			
				<div class="no-flex default">
					<div class="table-cell clearfix small">
						<div class="name">{$lang.payment_gateway}</div>
						<div class="value">{if $item.Gateway}{$item.Gateway}{else}{$lang.not_selected}{/if}</div>
					</div>
					<div class="table-cell clearfix small">
						<div class="name">{$lang.amount}</div>
						<div class="value">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} {$item.Total} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</div>
					</div>
					<div class="table-cell clearfix small">
						<div class="name">{$lang.txn_id}</div>
						<div class="value" id="txn-id-{$item.ID}" data-txn="{$item.Txn_ID}">{$item.Txn_ID}</div>
					</div>
					<div class="table-cell clearfix small">
						<div class="name">{$lang.date}</div>
						<div class="value">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
					</div>
				</div>
				<div data-caption="{$lang.status}" class="statuses"><span class="{$item.Status}">{$lang[$item.Status]}</span></div>
			</div>
		{/foreach}
	</div>
	
	<!-- paging block -->
	{paging calc=$pInfo.calc total=$transactions current=$pInfo.current per_page=$config.transactions_per_page}
	<!-- paging block end -->

{else}
	<div class="content-padding text-message">{$lang.no_account_transactions}</div>
{/if}
<!-- payment history tpl end -->