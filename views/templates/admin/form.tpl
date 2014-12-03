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
<form action="{$form_action|escape:'html':'UTF-8'}" method="post" >
	<fieldset>
		<legend><img src="../img/admin/cog.gif" alt="" />{l s='Get the Registration Link' mod='trustedshops'}</legend>
		<p>{l s='This variable was sent to you via e-mail by TrustedShops' mod='trustedshops'}</p>
		<label>{l s='Internal identification of shop software at Trusted Shops' mod='trustedshops'}</label>
		<div class="margin-form">
			<input type="text" name="shopsw" value="{$shopsw|escape:'html':'UTF-8'}"/>
		</div>
		<br />
		<br class="clear" />
		<label>{l s='Etracker channel' mod='trustedshops'}</label>
		<div class="margin-form">
			<input type="text" name="et_cid" value="{$etcid|escape:'html':'UTF-8'}"/>
		</div>
		<br class="clear" />
		<label>{l s='Etracker campaign' mod='trustedshops'}</label>
		<div class="margin-form">
			<input type="text" name="et_lid" value="{$etlid|escape:'html':'UTF-8'}"/>
		</div>
		<label>{l s='Language' mod='trustedshops'}</label>
		<div class="margin-form">
		    <select name="lang" >
			{foreach from=$languages item=language key=iso}
			    <option value="{$iso|escape:'html':'UTF-8'}" {if $language.id_lang == $lang_default}selected="selected"{/if}>{$language.name|escape:'html':'UTF-8'}</option>
			{/foreach}
		    </select>
		</div>
		<div style="text-align:center;">
	{if $link}
	    <script type="text/javascript">
		$().ready(function(){ldelim}window.open("{$link|escape:'html':'UTF-8'}");{rdelim});
	    </script>
	    <noscript>
		<p>
		    <a href="{$link|escape:'html':'UTF-8'}" target="_blank" title="{l s='Registration link' mod='trustedshops'}" class="link">{l s='Click to get the Registration Link' mod='trustedshops'}</a>
		<p>
	    </noscript>
	{/if}
	<input type="submit" name="submit_registration_link" class="btn btn-default" value="{l s='Send' mod='trustedshops'}"/>
		</div>
	</fieldset>
</form>