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
        <span id="tsCheckoutOrderNr">{$buyer_protection.order_reference|escape}</span>
        <span id="tsCheckoutBuyerEmail">{$buyer_protection.buyer_email|escape}</span>
        <span id="tsCheckoutBuyerId">{$buyer_protection.customer_id|escape}</span>
        <span id="tsCheckoutOrderAmount">{$buyer_protection.amount|escape}</span>
        <span id="tsCheckoutOrderCurrency">{$buyer_protection.currency|escape}</span>
        <span id="tsCheckoutOrderPaymentType">{$buyer_protection.payment_type|escape}</span>
        <span id="tsCheckoutOrderEstDeliveryDate"></span>
    </div>
</div>

