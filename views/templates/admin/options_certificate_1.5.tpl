{**
* 2014 silbersaiten The module is based on the trustedshops module originally developed by PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@silbersaiten.de so we can send you a copy immediately.
*
* @author    silbersaiten www.silbersaiten.de <info@silbersaiten.de>
* @copyright 2014 silbersaiten
* @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
*}
<script language="javascript">
    {if ! isset($certificate.send_separate_mail) || ! $certificate.send_separate_mail}
	{literal}
	$(document).ready( function() { $("#send_seperate_mail_infos").hide(); });
	{/literal}
    {/if}
    
    function toggleSendMailInfos() {ldelim}
	if (!$("input[name=send_separate_mail]").attr("checked")) {ldelim}
	    $("#send_seperate_mail_infos").hide();
	    alert("{l s='Warning, all the existing rating alerts will be deleted' mod='trustedshops'}");
	{rdelim} else {ldelim}
	    $("#send_seperate_mail_infos").show();
	{rdelim}
    {rdelim}
</script>

<form action="{$form_action|escape:'html':'UTF-8'}" class="form-horizontal" method="post" >
    <fieldset>
	<legend>{l s='Edit options' mod='trustedshops'}</legend>
	<input type="hidden" name="iso_lang" value="{$lang|escape:'html':'UTF-8'}" />

	<label>{l s='Language' mod='trustedshops'}</label>
	<div class="margin-form">
	    <p>{$lang|escape:'html':'UTF-8'}</p>
	</div>
	
	<label>{l s='Shop url' mod='trustedshops'}</label>
	<div class="margin-form">
	    <p>{$certificate.url|escape:'html':'UTF-8'}</p>
	</div>
	
	<label>{l s='Certificate id' mod='trustedshops'}</label>
	<div class="margin-form">
	    <p>{$certificate.tsID|escape:'html':'UTF-8'}</p>
	</div>
	    
	<div class="margin-form">
	    <p><strong>{l s='Trusted Shops Seal of Approval' mod='trustedshops'}</strong></p>
	</div>
	
	<label>{l s='Variant' mod='trustedshops'}</label>
	<div class="margin-form">
	    <select name="variant">
		{foreach from=$available_seal_variants item=v key=k}
		    <option value="{$k|escape:'html':'UTF-8'}" {if $certificate.variant == $k}selected="selected"{/if}>{$v|escape:'html':'UTF-8'}</option>
		{/foreach}
	    </select>
	</div>
	    
	<div class="margin-form">
	    <p><strong>{l s='Trusted Shops Customer Rating' mod='trustedshops'}</strong></p>
	</div>
	    
	<div class="margin-form">
	    <p>{l s='Start collecting 100% real customer ratings now! Integrate rating request and rating widget in your shop and show honest interest in you customer\'s opinions.' mod='trustedshops'}</p>
	</div>
	
	<label>{l s='Display rating link in shop front-end' mod='trustedshops'}</label>
	<div class="margin-form">
	    <input type="checkbox" name="display_rating_front_end" value="1" {if isset($certificate.display_rating_front_end) && $certificate.display_rating_front_end}checked="checked"{/if} />
	</div>
	
	<label>{l s='Display rating link on order confirmation page' mod='trustedshops'}</label>
	<div class="margin-form">
	    <input type="checkbox" name="display_rating_oc" value="1" {if isset($certificate.display_rating_oc) && $certificate.display_rating_oc}checked="checked"{/if} />
	</div>
	
	<label>{l s='Send rating link in separate e-mail' mod='trustedshops'}</label>
	<div class="margin-form">
	    <div>
		<input onclick="toggleSendMailInfos()" type="checkbox" name="send_separate_mail" value="1" {if isset($certificate.send_separate_mail) && $certificate.send_separate_mail}checked="checked"{/if} /> <br />
		<div id="send_seperate_mail_infos">
		    {l s='Send the email after' mod='trustedshops'}
		    <input class="" size="2" type="text" name="send_seperate_mail_delay" value="{if isset($certificate.send_seperate_mail_delay)}{$certificate.send_seperate_mail_delay|escape:'html':'UTF-8'}{/if}" />
		    {l s='days' mod='trustedshops'} {l s='of setting order to state' mod='trustedshops'}
		    <select name="send_seperate_mail_order_state">
			{foreach from=$order_states item=order_state}
			<option value="{$order_state.id_order_state|escape:'intval':'UTF-8'}" {if isset($certificate.send_seperate_mail_order_state) && $order_state.id_order_state == $certificate.send_seperate_mail_order_state}selected="selected"{/if}>{$order_state.name|escape:'html':'UTF-8'}</option>
			{/foreach}
		    </select>
		    <span style="color: #CC0000; font-weight: bold;">{l s='IMPORTANT:' mod='trustedshops'}</span> {l s='Put this URL in crontab or call it manually daily:' mod='trustedshops'}<br />
		    {$cron_link|escape:'html':'UTF-8'}
		</div>
	    </div>
	</div>
    </fieldset>
    
    <p style="text-align:center;">
	<input type="submit" name="submit_changeoptions_certificate" class="button" value="{l s='Update it' mod='trustedshops'}"/>
    </p>
</form>