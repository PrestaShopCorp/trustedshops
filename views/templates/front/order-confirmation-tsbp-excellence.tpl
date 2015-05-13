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
<style>
    {literal}
    input.myButtonTricksTS {
        font-size: 9px !important;
        font-weight: bolder;
        cursor: pointer;
        padding: 3px;
    }

    {/literal}
</style>
<div class="trustedshops-form" style="text-align:center;border:0px solid #ccc;padding:0px;height:0px;">
    <div id="trustedShopsCheckout" style="display: none;">
        <span id="tsCheckoutTsID">{$buyer_protection.tsID|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutTsProductID">{$buyer_protection.tsProductID|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutAmount">{$buyer_protection.amount|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutCurrency">{$buyer_protection.currency|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutPaymentType">{$buyer_protection.paymentType|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutBuyerEmail">{$buyer_protection.buyerEmail|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutShopCustomerID">{$buyer_protection.shopCustomerID|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutShopOrderID">{$buyer_protection.shopOrderID|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutOrderDate">{$buyer_protection.orderDate|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutShopSystemVersion">{$buyer_protection.shopSystemVersion|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutWsUser">{$buyer_protection.wsUser|escape:'html':'UTF-8'}</span>
        <span id="tsCheckoutWsPassword">{$buyer_protection.wsPassword|escape:'html':'UTF-8'}</span>
    </div>
</div>

