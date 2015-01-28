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
{if $item_exist}
    <script type="text/javascript">
        $(document).ready(function () {
            var items_length = $('#ts-list-items').find('input').length;
            //$('#ts-list-items').css('line-height', ((70/items_length) > 12 ? Math.round(70/items_length) : 12 )+'px');
            TS.init();
        });

        var TS = (function () {
            function updateTsProduct(id_product, type_action) {
                $.ajax({
                    type: 'POST',
                    url: baseUri + '?rand=' + new Date().getTime(),
                    async: true,
                    cache: false,
                    dataType: "json",
                    data: 'controller=cart&' + type_action + '=1&ajax=true&qty=1&id_product=' + id_product + '&token=' + static_token,
                    success: function (jsonData) {
                        ajaxCart.updateCart(jsonData);
                        $('#total_price, #cart_block_total').html(jsonData.total);
                    }
                });
            }

            // TODO : REWRITE this part because it's completely not a good way (Control  + R, add Product dynamically)
            return {
                handle_product: function (block) {
                    var id_number = block.attr('id').split('-')[2];

                    if (block.attr('checked'))
                        updateTsProduct(id_number, 'add');
                    else
                        updateTsProduct(id_number, 'delete');
                },
                init: function () {
                    $('#ts-list-items input[type=checkbox]').click(function (e) {
                        TS.handle_product($(this));
                    });
                }
            }
        })();
    </script>
    <div class="box">
        <h3 class="page-subheading">{l s='Trusted Shops Buyer Protection (recommended)' mod='trustedshops'}</h3>
        <div style="float:left; width:100px;">
            <a href="https://www.trustedshops.com/shop/certificate.php?shop_id={$shop_id|escape}" target="_blank">
                <img id="logo_trusted" style="margin:2px 0 10px 10px" alt="logo"
                     src="{$module_dir|escape}img/siegel.gif" border="0"/>
            </a>
        </div>
        <div id="ts-list-items">
            <p style="margin-bottom: 10px;">
                <input id="ts-product-{$buyer_protection_item.id_product|escape}" type="checkbox"
                       value="{$buyer_protection_item.id_product|escape}" name="item_product">
                {l s='Buyer protection up to' mod='trustedshops'} {$buyer_protection_item.protected_amount_decimal|round:2}{$currency->sign}
                ({$buyer_protection_item.gross_fee|round:2}{$currency->sign} {l s='incl. VAT' mod='trustedshops'})
            </p>

            <div id="content_checkout" style="margin-left:100px">
                <p>
                    {l s='The Trusted Shops Buyer Protection secures your online purchase. I agree to my email address being transferred and' mod='trustedshops'}
                    <b>
                        <a href="http://www.trustedshops.com/shop/data_privacy.php?shop_id={$shop_id|escape}"
                           target="_blank">{l s='saved' mod='trustedshops'}</a></b>
                    {l s='for the purposes of Buyer Protection processing by Trusted Shops.' mod='trustedshops'} <b>
                        <a href="http://www.trustedshops.com/shop/protection_conditions.php?shop_id={$shop_id|escape}"
                           target="_blank">{l s='Conditions' mod='trustedshops'}</a></b>
                    {l s='for Buyer Protection.' mod='trustedshops'}</p></div>
        </div>
        <div class="clear"/>
    </div>
    </div>
{/if}