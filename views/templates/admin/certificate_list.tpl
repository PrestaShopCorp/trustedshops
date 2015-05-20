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
<script type="text/javascript">
    {literal}
    $().ready(function()
    {
	$('#certificate_list').find('input[type=checkbox]').click(function()
	{
		$('#certificate_list').find('input[type=checkbox]').not($(this)).removeAttr('checked');
	});
    });
    {/literal}
</script>

<form action="{$form_action|escape:'html':'UTF-8'}" class="form-horizontal" method="post" >
    <div class="panel">
	<div class="panel-heading">{l s='Manage your Trusted Shops review profiles' mod='trustedshops'}</div>
	<table width="100%" class="table">
	    <thead>
		<tr style="text-align:center;">
		    <th>{l s='Trusted Shops ID' mod='trustedshops'}</th>
		    <th>{l s='Language' mod='trustedshops'}</th>
		    <th>{l s='State' mod='trustedshops'}</th>
		    <th>{l s='Type' mod='trustedshops'}</th>
		    <th>{l s='Shop url' mod='trustedshops'}</th>
		    <th>{l s='Variant' mod='trustedshops'}</th>
		    <th>{l s='Edit' mod='trustedshops'}</th>
		    <th>{l s='Options' mod='trustedshops'}</th>
		    <th>{l s='Delete' mod='trustedshops'}</th>
		</tr>
	    </thead>
	    <tbody id="certificate_list">
		{foreach from=$certificates item=certificate key=lang}
		    {if isset($certificate.tsID) && $certificate.tsID != ''}
			<tr style="text-align:center;">
				<td>{$certificate.tsID|escape:'html':'UTF-8'}</td>
				<td>{$lang|escape:'html':'UTF-8'}</td>
				<td>{$certificate.stateEnum|escape:'html':'UTF-8'}</td>
				<td>{$certificate.typeEnum|escape:'html':'UTF-8'}</td>
				<td>{$certificate.url|escape:'html':'UTF-8'}</td>
				<td>{$certificate.variant|escape:'html':'UTF-8'}</td>
				<td>
				    
				    {if $certificate.typeEnum == 'EXCELLENCE'}
					<a href="{$configure_link|escape:'html':'UTF-8'}&certificate_edit={$lang|escape:'html':'UTF-8'}" class="btn btn-default">{l s='Edit' mod='trustedshops'}</a>
					{if $certificate.user == ''}
					<br /><b style="color:red;font-size:0.7em;">{l s='Login or password missing' mod='trustedshops'}</b>
					{/if}
				    {else}
					{l s='No need' mod='trustedshops'}
				    {/if}
				</td>
				<td>
				    {if $certificate.typeEnum == 'EXCELLENCE' || $certificate.typeEnum == 'CLASSIC' || $certificate.typeEnum == 'UNKNOWN'}
					<a href="{$configure_link|escape:'html':'UTF-8'}&certificate_options={$lang|escape:'html':'UTF-8'}#certificate_list" class="btn btn-default">{l s='Options' mod='trustedshops'}</a>
				    {else}
					{l s='No need' mod='trustedshops'}
				    {/if}
				</td>
				<td>
				    {if $certificate.typeEnum == 'EXCELLENCE' || $certificate.typeEnum == 'CLASSIC' || $certificate.typeEnum == 'UNKNOWN'}
					<a href="{$form_action|escape:'html':'UTF-8'}&certificate_delete={$lang|escape:'html':'UTF-8'}" class="btn btn-default">{l s='Delete' mod='trustedshops'}</a>
				    {else}
					{l s='No need' mod='trustedshops'}
				    {/if}
				</td>
			    </tr>
		    {/if}
		{/foreach}

	    </tbody>
	</table>
    </div>
</form>