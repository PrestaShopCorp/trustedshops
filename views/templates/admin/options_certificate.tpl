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

<form action="{$form_action|escape:'html':'UTF-8'}" class="form-horizontal" method="post">
    <div class="panel">
	<div class="panel-heading">{l s='Edit options' mod='trustedshops'}</div>
	<div class="form-wrapper">
	    <input type="hidden" name="iso_lang" value="{$lang|escape:'html':'UTF-8'}" />
	    
	    <div class="form-group">
                <label class="control-label col-lg-3">{l s='Language' mod='trustedshops'}</label>
                <div class="col-lg-4"><output style="font-size: 13px;">{$lang|escape:'html':'UTF-8'}</output></div>
	    </div>
	    
	    <div class="form-group">
                <label class="control-label col-lg-3">{l s='Shop url' mod='trustedshops'}</label>
		<div class="col-lg-4"><output style="font-size: 13px;">{$certificate.url|escape:'html':'UTF-8'}</output></div>
	    </div>
	    
	    <div class="form-group">
                <label class="control-label col-lg-3">{l s='Trusted Shops ID' mod='trustedshops'}</label>
		<div class="col-lg-4"><output style="font-size: 13px;">{$certificate.tsID|escape:'html':'UTF-8'}</output></div>
	    </div>
	    
	    <div class="form-group">
		<div class="col-lg-offset-1"><strong>{l s='Trusted Shops Seal of Approval' mod='trustedshops'}</strong></div>
	    </div>
	    
	    <div class="form-group">
            <label class="control-label col-lg-3">{l s='Variant' mod='trustedshops'}</label>
			<div class="col-lg-4">
			    <select name="variant">
				{foreach from=$available_seal_variants item=v key=k}
				    <option value="{$k|escape:'html':'UTF-8'}" {if $certificate.variant == $k}selected="selected"{/if}>{$v|escape:'html':'UTF-8'}</option>
				{/foreach}
			    </select>
			</div>
	    </div>

	    <div class="form-group">
			<label class="control-label col-lg-3">{l s='yOffset' mod='trustedshops'}</label>
			<div class="col-lg-4">
			    <input type="text" name="yoffset" value="{$yoffset|escape:'html':'UTF-8'}" maxlength="33"/>
			</div>
		</div>

		<div class="form-group">
			<label class="control-label col-lg-3">{l s='JS Code' mod='trustedshops'}</label>
			<div class="col-lg-4">
				<textarea name="jscode" rows="10" id="social-text">{$jscode|escape:'html':'UTF-8'}</textarea>
				<p class="help-block">{l s='This code is displayed (if the field is not empty) instead of the standard badge' mod='trustedshops'}</p>
			</div>
		</div>		
	</div>
    </div>
    <p style="text-align:center;">
	    <input type="submit" name="submit_changeoptions_certificate" class="btn btn-default" value="{l s='Update it' mod='trustedshops'}"/>
    </p>
</form>