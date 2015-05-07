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
{if !empty($jscode)}
{$jscode}
{else}
<script type="text/javascript">
    (function () {
        var _tsid = '{$trusted_shops_id|escape}';
        _tsConfig = {
            'yOffset': '{$yoffset|escape}', /* offset from page bottom */
            'variant': '{$variant|escape}', /* text, default, small, reviews, custom, custom_reviews */
            'customElementId': '', /* required for variants custom and custom_reviews */
            'trustcardDirection': '', /* for custom variants: topRight, topLeft, bottomRight, bottomLeft */
            'customBadgeWidth': '', /* for custom variants: 40 - 90 (in pixels) */
            'customBadgeHeight': '', /* for custom variants: 40 - 90 (in pixels) */
            'disableResponsive': 'false', /* deactivate responsive behaviour */
            'disableTrustbadge': 'false' /* deactivate trustbadge */
        };

        var _ts = document.createElement('script');
        _ts.type = 'text/javascript';
        _ts.charset = 'utf-8';
        _ts.async = true;
        _ts.src = '//widgets.trustedshops.com/js/' + _tsid + '.js';
        var __ts = document.getElementsByTagName('script')[0];
        __ts.parentNode.insertBefore(_ts, __ts);

    })();
</script>
{/if}