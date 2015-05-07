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
<script type="text/javascript" src="{$site_uri|escape:'html':'UTF-8'}modules/trustedshops/js/payment.js" ></script>
<script type="text/javascript">
    $().ready(function() {
	TSPayment.payment_type = $.parseJSON('{$payment_types_json|escape:'UTF-8'}');
	TSPayment.payment_module = $.parseJSON('{$payment_collection_json|escape:'UTF-8'}');
	
	{literal}
	$('.payment-module-label').css(TSPayment.module_box.css).fadeIn();
	$('.choosen_payment_type').each(function() {
	    TSPayment.deleteModuleFromList($(this).val());
	    TSPayment.setLabelModuleName($(this).val());
	});
	
	TSPayment.init();
	{/literal}
    });
</script>
<form action="{$form_action|escape:'html':'UTF-8'}" class="form-horizontal" method="post" >
    <fieldset>
	<legend>{l s='Edit Trusted Shops ID' mod='trustedshops'}</legend>
	<input type="hidden" name="iso_lang" value="{$lang|escape:'html':'UTF-8'}" />
	
	<label>{l s='Language' mod='trustedshops'}</label>
	<div class="margin-form">
	    <p>{$lang|escape:'html':'UTF-8'}</p>
	</div>
	
	<label>{l s='Shop url' mod='trustedshops'}</label>
	<div class="margin-form">
	    <p>{$certificate.url|escape:'html':'UTF-8'}</p>
	</div>
	
	<label>{l s='Trusted Shops ID' mod='trustedshops'}</label>
	<div class="margin-form">
	    <p>{$certificate.tsID|escape:'html':'UTF-8'}</p>
	</div>
	
	<label>{l s='User Name' mod='trustedshops'}</label>
	<div class="margin-form">
	    <input type="text" name="user" value="{$certificate.user|escape:'html':'UTF-8'}"/>
	</div>
	
	<label>{l s='Password' mod='trustedshops'}</label>
	<div class="margin-form">
	    <input type="text" name="password" value="{$certificate.password|escape:'html':'UTF-8'}" style="width:300px;"/>
	</div>
	
	<label class="control-label col-lg-3 required">{l s='Payment type to edit' mod='trustedshops'}</label>
	<div id="payment-type" class="margin-form">
	    <p>
		<select name="payment_type">
		    {foreach from=$payment_types item=translation key=type}
			<option value="{$type|escape:'html':'UTF-8'}">{$translation|escape:'html':'UTF-8'}</option>
		    {/foreach}
		</select>
	    </p>
	    <p>
		{l s='with' mod='trustedshops'}
	    </p>
	    <p>
		<select name="payment_module">
		    {foreach from=$payment_collection item=module_info}
			<option value="{$module_info.id_module|escape:'intval':'UTF-8'}">{$module_info.name|escape:'html':'UTF-8'}</option>
		    {/foreach}
		</select>
	    </p>
	    <p>
		{l s='payment module' mod='trustedshops'}
	    </p>
	    <p>
		<input type="button" value="{l s='Add it' mod='trustedshops'}" class="button" name="add_payment_module" />
	    </p>
	</div>
	    
	{if isset($certificate.payment_type) && $certificate.payment_type|@count}
	<label>{$payment_types[$payment_type]|escape:'html':'UTF-8'}</label>
	<div id="payment_type_list" class="margin-form">
		{foreach from=$certificate.payment_type item=modules key=payment_type}
		    <div id="block-payment-{$payment_type|escape:'html':'UTF-8'}">
			{foreach from=$modules item=module_id}
			    <b class="payment-module-label" id="label-module-{$module_id|escape:'intval':'UTF-8'}"></b>
			{/foreach}
		    </div>
		{/foreach}
	</div>
	{/if}

	<p id="input-hidden-val" style="display:none;">
	    {if isset($certificate.payment_type) && $certificate.payment_type|@count}
		{foreach from=$certificate.payment_type item=modules key=payment_type}
		    {foreach from=$modules item=module_id}
			<input type="hidden" value="{$module_id|escape:'intval':'UTF-8'}" class="choosen_payment_type" name="choosen_payment_type[{$payment_type|escape:'html':'UTF-8'}][]">
		    {/foreach}
		{/foreach}
	    {/if}
	</p>

	<p style="text-align:center;">
	    <input type="submit" name="submit_change_certificate" class="button" value="{l s='Update it' mod='trustedshops'}"/>
	</p>
    </fieldset>
</form>