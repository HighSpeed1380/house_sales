<!-- credit card payment tpl -->
<div id="card-form">
	<input type="hidden" name="form" value="checkout" />

	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='credit_card_details' name=$lang.credit_card_details}
	<div class="submit-cell">
		<div class="name">{$lang.card_holder_name}</div>
		<div class="field">
			<input type="text" name="f[card_name]" class="wauto" maxlength="35" size="35" value="{$smarty.post.f.card_name}" />
		</div>
	</div>

	<div class="submit-cell">
		<div class="name">{$lang.card_number} <span class="red">&nbsp;*</span></div>
		<div class="field">
			<input type="text" name="f[card_number]" class="wauto" maxlength="18" size="18" />

			<img id="card_icon" src="{$rlTplBase}img/blank.gif" alt="card type" />
			<input type="hidden" value="" name="card_type" />
		</div>
	</div>

	<div class="submit-cell">
		<div class="name">{$lang.card_expiration} <span class="red">&nbsp;*</span></div>
		<div class="field">
			<select name="f[exp_month]" class="w120">
				<option>{$lang.month}</option>
				{foreach from=','|explode:$lang.dp_months_list item='month' name='monthF'}
                    {if $smarty.foreach.monthF.iteration < 10}
                        {assign var="exp_month" value='0'|cat:$smarty.foreach.monthF.iteration}
                    {else}
                        {assign var="exp_month" value=$smarty.foreach.monthF.iteration}
                    {/if}
					<option {if $smarty.post.f.exp_month == $exp_month}selected="selected"{/if} value="{if $smarty.foreach.monthF.iteration < 10}0{/if}{$smarty.foreach.monthF.iteration}">{if $smarty.foreach.monthF.iteration < 10}0{/if}{$smarty.foreach.monthF.iteration} - {$month}</option>
				{/foreach}
			</select>
			{php}
				global $rlSmarty;
				$years_range = range(date('Y'), date('Y')+15);
				$rlSmarty->assign('years_range', $years_range);
			{/php}
			<select name="f[exp_year]" style="width: 70px;">
				<option>{$lang.year}</option>
				{foreach from=$years_range item='year'}
					<option {if $smarty.post.f.exp_year == $year}selected="selected"{/if} value="{$year}">{$year}</option>
				{/foreach}
			</select>
		</div>
	</div>

	<div class="submit-cell">
		<div class="name">{$lang.card_verification_code} <span class="red">&nbsp;*</span></div>
		<div class="field">
			<input type="text" name="f[card_verification_code]" class="wauto" maxlength="4" size="4" />
			<img class="cvc" src="{$rlTplBase}img/blank.gif" alt="cvc" />
		</div>
	</div>

	{rlHook name='creditCardPayment'}

	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='billing_details' name=$lang.billing_details}
	<div class="submit-cell">
		<div class="name"></div>
		<div class="field checkbox-field">
			<label><input type="checkbox" name="use_account_info" id="use-account-info" {if $smarty.post.use_account_info || !$smarty.post.form}checked="checked"{/if} /> {$lang.use_account_info}</label>
		</div>
	</div>
	<div id="billing-form" {if $smarty.post.use_account_info || !$smarty.post.form}class="hide"{/if}>
		<div class="submit-cell">
			<div class="name">{$lang.first_name}</div>
			<div class="field single-field">
				<input type="text" name="f[first_name]" class="wauto" value="{$smarty.post.f.first_name}" maxlength="50" size="35" />
			</div>
		</div>
		<div class="submit-cell">
			<div class="name">{$lang.last_name}</div>
			<div class="field single-field">
				<input type="text" name="f[last_name]" class="wauto" value="{$smarty.post.f.last_name}" maxlength="50" size="35" />
			</div>
		</div>
		<div class="submit-cell clearfix">
			<div class="name">{$lang.billing_country}</div>
			<div class="field single-field">
				<select name="f[b_country]">
					<option value="">{$lang.select}</option>
					{foreach from='countries'|df item='country'}
						<option value="{$country.Key}" {if $smarty.post.f.b_country == $country.Key}selected="selected"{/if}>{$country.name}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="submit-cell hide">
			<div class="name">{$lang.billing_state}</div>
			<div class="field single-field">  
				<select name="f[b_states]">
					<option value="">{$lang.select}</option>
					{foreach from='us_states'|df item='state'}
						<option value="{$state.iso}" {if $smarty.post.f.state == $state.iso}selected="selected"{/if}>{$state.name}</option>
					{/foreach}
				</select>
			</div>
		</div>
		<div class="submit-cell">
			<div class="name">{$lang.billing_state}</div>
			<div class="field single-field">
				<input id="state" type="text" name="f[region]" class="wauto" value="{$smarty.post.f.region}" size="35" />
			</div>
		</div>
		<div class="submit-cell">
			<div class="name">{$lang.billing_city}</div>
			<div class="field single-field">
				<input type="text" name="f[city]" class="wauto" value="{$smarty.post.f.city}" maxlength="100" size="35" />
			</div>
		</div>
		<div class="submit-cell">
			<div class="name">{$lang.billing_zip}</div>
			<div class="field single-field">
				<input type="text" name="f[zip]" class="wauto numeric" value="{$smarty.post.f.zip}" maxlength="5" size="8" />
			</div>
		</div>
		<div class="submit-cell">
			<div class="name">{$lang.billing_address}</div>
			<div class="field single-field">
				<input type="text" name="f[address]" class="wauto" value="{$smarty.post.f.address}" maxlength="255" size="35" />
			</div>
		</div>
	</div>
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

	<script>
	{literal}

	$(document).ready(function(){
		flynaxTpl.locationHandler();
		$('input[name="f[card_number]"]').validateCreditCard(function(result){
			$('#card_icon').attr('class', '');
			if ( result.card_type ) {
				$('#card_icon').addClass(result.card_type.name);
				$('input[name=card_type]').val(result.card_type.name);
			}
		});
		if ($('input#use-account-info').is(':checked')) {
			$('#billing-form').hide();
		}
		$('input#use-account-info').change(function() {
			if ($(this).is(':checked'))	{
				$('#billing-form').hide();
			} else {
				$('#billing-form').show();	
			}
		});
	});

	{/literal}
	</script>

	{rlHook name='creditCardPaymentBottom'}

</div>
<!-- credit card payment tpl end -->