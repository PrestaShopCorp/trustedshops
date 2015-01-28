<?php
/**
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
 */

if (!defined('_PS_VERSION_'))
	exit;

require(_PS_MODULE_DIR_.'trustedshops/classes/AbsTrustedShops.php');
require(_PS_MODULE_DIR_.'trustedshops/classes/TSCommon.php');

class TrustedShops extends Module
{
	/**
	 * Saved each Object needed list of AbsTrustedShops extended objects
	 * @var array
	 */
	private static $obj_ts_common;

	private $errors = array();

	private $warnings = array();

	public $limited_countries = array();

	private $confirmations = array();

	public static $seal_displayed = false;
	
	private static $template_version;

	private $available_languages = array('en', 'fr', 'de', 'es', 'it', 'pl');

	public function __construct()
	{
		$this->name = 'trustedshops';
		$this->tab = 'payment_security';
		$this->version = '2.2.3';
		$this->author = 'silbersaiten';
		$this->bootstrap = true;
		
		self::$template_version = version_compare(_PS_VERSION_, '1.6', '<') ? '1.5' : '1.6';

		parent::__construct();

		TSCommon::setTranslationObject($this);
		self::$obj_ts_common = new TSCommon();
		self::$obj_ts_common->setEnvApi(TSCommon::ENV_MOD);
		self::$obj_ts_common->setModuleName($this->name);
		self::$obj_ts_common->setSmarty($this->context->smarty);

		if (!extension_loaded('soap'))
			$this->warnings[] = $this->l('This module requires the SOAP PHP extension to function properly.');

		if (!empty(self::$obj_ts_common->warnings))
			$this->warnings = array_merge($this->warnings, self::$obj_ts_common->warnings);

		if (!empty($this->warnings))
			$this->warning = implode(',<br />', $this->warnings).'.';

		$this->displayName = $this->l('Trusted Shops trust solutions');
		$this->description = $this->l('Build confidence in your online shop with the Trusted Shops quality seal, buyer protection and customer rating.');
		$this->confirmUninstall = $this->l('Are you sure you want to delete all your settings?');
	}

	public function install()
	{
		self::$obj_ts_common->install();

		$return = parent::install() &&
			$this->registerHook('LeftColumn') &&
			$this->registerHook('displayBackOfficeHeader') &&
			$this->registerHook('orderConfirmation') &&
			$this->registerHook('newOrder') &&
			$this->registerHook('actionOrderStatusPostUpdate') &&
			$this->registerHook('Footer') &&
			$this->registerHook('paymentTop') &&
            //$this->registerHook('displayAfterShoppingCartBlock') &&
			$this->registerHook('orderConfirmation');
		$id_hook = _PS_VERSION_ < '1.5' ? Hook::get('payment') : Hook::getIdByName('payment');
		$this->updatePosition($id_hook, 0, 1);

		return $return;
	}

	public function uninstall()
	{
		self::$obj_ts_common->uninstall();
		return parent::uninstall();
	}
	
	public static function getTemplateByVersion($template_name)
	{
		if (self::$template_version == '1.5')
			return $template_name . '_1.5.tpl';
		
		return $template_name . '.tpl';
	}

	private function getAllowedIsobyId($id_lang)
	{
		$lang = Language::getIsoById($id_lang);
		$lang = in_array($lang, $this->available_languages) ? $lang : 'en';

		return $lang;
	}

	public function displayInfo()
	{
		switch (Tools::strtolower(Language::getIsoById((int)$this->context->cookie->id_lang)))
		{
			case 'de':
				$applynow_link = 'http://www.trustedshops.de/shopbetreiber/index.html?shopsw=eulegal';
				break;
			case 'es':
				$applynow_link = 'https://www.trustedshops.es/comerciante/partner/';
				break;
			case 'fr':
				$applynow_link = 'https://www.trustedshops.fr/marchands/partenaires/';
				break;
			case 'pl':
				$applynow_link = 'https://www.trustedshops.pl/handlowcy/partner/';
				break;
			default:
				$applynow_link = 'https://www.trustedshops.co.uk/merchants/?shopsw=eulegal';
		}

		$this->smarty->assign(array(
			'_path' => $this->_path,
			'ts_rating_image' => $this->_path.'img/ts_rating_'.$this->getAllowedIsobyId($this->context->cookie->id_lang).'.jpg',
			'applynow_link' => $applynow_link
		));

		return $this->display(__FILE__, 'views/templates/admin/'.self::getTemplateByVersion('information'));
	}

	public function displayConfiguration()
	{
		return '';
	}

	public function getContent()
	{
		$out = $this->displayInfo();
		$out .= $this->displayConfiguration();
		$out .= self::$obj_ts_common->getContent();

		// Check If each object (display as Tab) contains errors message of
		$this->checkObjectsErrorsOrConfirmations();

		return (empty($this->errors)?$this->displayConfirmations():$this->displayErrors()).$out;
	}

	private function displayCSSJSTab()
	{
		$id_tab = Tools::getIsset('id_tab') ? (int)Tools::getValue('id_tab') : 0;

		return '
		<style>
			#menuTabs { float: left; padding: 0; text-align: left; margin:0}
			#menuTabs li { text-align: left; float: left; display: inline; padding: 5px 10px 5px 5px;
			background: #EFEFEF; font-weight: bold; cursor: pointer; border-left: 1px solid #EFEFEF;
			border-right: 1px solid #EFEFEF; border-top: 1px solid #EFEFEF; }
			#menuTabs li.menuTabButton.selected { background: #FFF6D3; border-left: 1px solid #CCCCCC;
			border-right: 1px solid #CCCCCC; border-top: 1px solid #CCCCCC; }
			#tabList { clear: left;}
			.tabItem { display: none; }
			.tabItem.selected { display: block; background: #fcfcfc; border: 1px solid #CCCCCC;
			padding: 10px; padding-top: 20px;}
		</style>
		<script>
			$().ready(function()
			{
				$("#menuTab'.$id_tab.'Sheet").addClass("selected");
				$("#menuTab'.$id_tab.'").addClass("selected");
			})
			$(".menuTabButton").click(function ()
			{
				$(".menuTabButton.selected").removeClass("selected");
				$(this).addClass("selected");
				$(".tabItem.selected").removeClass("selected");
				$("#" + this.id + "Sheet").addClass("selected");
			});
		</script>
		';
	}

	/**
	 * Check If each object (display as Tab) contains errors message of
	 *
	 * @return void
	 */
	private function checkObjectsErrorsOrConfirmations()
	{
		if (!empty(self::$obj_ts_common->errors))
			$this->errors = array_merge($this->errors, self::$obj_ts_common->errors);

		if (!empty(self::$obj_ts_common->confirmations))
			$this->confirmations = array_merge($this->confirmations, self::$obj_ts_common->confirmations);
	}

	private function displayConfirmations()
	{
		$html = '';

		if (!empty($this->confirmations))
			foreach ($this->confirmations as $confirmations)
				$html .= $this->displayConfirmation($confirmations);

		return $html;
	}

	private function displayErrors()
	{
		$html = '';

		if (!empty($this->errors))
			foreach ($this->errors as $error)
				$html .= $this->displayError($error);

		return $html;
	}

	public function hookOrderConfirmation($params)
	{
		return $this->dynamicHook($params, __FUNCTION__);
	}

	public function hookActionOrderStatusPostUpdate($params)
	{
		return $this->dynamicHook($params, __FUNCTION__);
	}

	public function hookNewOrder($params)
	{
		return $this->dynamicHook($params, __FUNCTION__);
	}

	public function hookLeftColumn($params)
	{
		return $this->dynamicHook($params, __FUNCTION__);
	}

	public function hookRightColumn($params)
	{
		return $this->hookLeftColumn($params);
	}

	public function hookPaymentTop($params)
	{
		return $this->dynamicHook($params, __FUNCTION__);
	}

    public function hookDisplayAfterShoppingCartBlock($params)
    {
        return $this->dynamicHook($params, __FUNCTION__);
    }

	public function hookFooter($params)
	{
		return $this->dynamicHook($params, __FUNCTION__);
	}

	private function dynamicHook($params, $hook_name)
	{
		if (!$this->active)
			return '';

		$return = '';

		if (method_exists(self::$obj_ts_common, $hook_name))
			$return .= self::$obj_ts_common->{$hook_name}($params);

		return $return;
	}

	public static function displaySeal()
	{
		if (!TrustedShops::$seal_displayed)
		{
			Context::getContext()->smarty->assign('ts_module_dir', __PS_BASE_URI__.'modules/trustedshops/');
			TrustedShops::$seal_displayed = true;
			return Context::getContext()->smarty->fetch(dirname(__FILE__).'/views/templates/front/'.TSCommon::getTemplateByVersion('seal_of_approval'));
		}
		return '';
	}
	
	public function hookDisplayBackOfficeHeader($params)
	{
		if ($this->context->controller instanceof AdminModulesController && Tools::getValue('configure') == $this->name) {
			$this->context->controller->addCSS($this->_path . 'css/admin.css');
		}
	}
}