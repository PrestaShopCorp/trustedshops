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
<form action="{$form_action|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
	<div class="panel">
	    <div class="panel-heading">{l s='Enter Trusted Shops ID' mod='trustedshops'}</div>
	<div class="form-wrapper">
	<div class="form-group alert alert-info">
		<label class="control-label col-lg-3">{l s='Your Trusted Shops ID' mod='trustedshops'}</label>
		<div class="col-lg-4">
		    <input type="text" name="new_certificate" value="" maxlength="33"/>&nbsp;&nbsp;
		</div>
		<div class="col-lg-2">
		    <select name="lang">
			<option>{l s='select language' mod='trustedshops'}</option>
			{foreach from=$languages item=iso}
			    <option value="{$iso}" {if $iso == $lang_default}selected="selected"{/if}>{$iso}</option>
			{/foreach}
		    </select>
		</div>
		<div class="col-lg-2">
		    <input type="submit" name="submit_confirm_certificate" class="btn btn-default" value="{l s='Add it' mod='trustedshops'}"/>
		</div>
    </div>
</div>
	</div>
</form>