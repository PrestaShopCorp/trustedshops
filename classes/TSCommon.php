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

require_once(_PS_MODULE_DIR_.'trustedshops/lib/TSBPException.php');
require_once(_PS_MODULE_DIR_.'trustedshops/lib/TrustedShopsSoapApi.php');
require_once(_PS_MODULE_DIR_.'trustedshops/lib/WidgetCache.php');
require_once(_PS_MODULE_DIR_.'trustedshops/classes/RatingAlert.php');

/**
 * @see the technical doc for entire description.
 *        too long to set it here.
 * @author Prestashop - Nans Pellicari
 * @since prestashop 1.4
 * @version 0.1
 */
class TSCommon extends AbsTrustedShops
{

	const APPLY_URL = 'https://www.trustedshops.com/buyerrating/signup.html';
	const PARTNER_PACKAGE = 'presta';
	const SHOP_SW = 'PrestaShop';

	private $rating_url_base = array(
		'EN' => 'https://www.trustedshops.com/buyerrating/rate_',
		'DE' => 'https://www.trustedshops.com/bewertung/bewerten_',
		'FR' => 'https://www.trustedshops.com/evaluation/evaluer_',
		'ES' => 'https://www.trustedshops.es/evaluacion/evaluar_',
		'IT' => 'https://www.trustedshops.it/valutazione-del-negozio/rate_'
	);

	private $apply_url_base = array(
		'EN' => 'https://www.trustedshops.com/buyerrating/signup.html',
		'DE' => 'https://www.trustedshops.com/bewertung/anmeldung.html',
		'FR' => 'https://www.trustedshops.com/evaluation/inscription.html',
		'ES' => 'https://www.trustedshops.es/comerciante/'
	);

	private $apply_url_tracker = array(
		'EN' => '&et_cid=53&et_lid=3361',
		'DE' => '',
		'FR' => '&et_cid=53&et_lid=3362',
		'ES' => ''
	);

	private $error_soap_call;
	
	private static $template_version;

	const PREFIX_TABLE = 'TS';
	
	/* 'test' or 'production' */
	const ENV_MOD = 'production';
	const DB_ITEMS = 'ts_buyerprotection_items';
	const DB_APPLI = 'ts_application_id';
	const WEBSERVICE_BO = 'administration';
	const WEBSERVICE_FO = 'front-end';

	/**
	 * List of registration link, need to add parameters
	 * @see TSCommon::_getRegistrationLink()
	 * @var array
	 */
	private $registration_link = array(
		'DE' => 'http://www.trustedshops.de/shopbetreiber/mitgliedschaft.html',
		'EN' => 'http://www.trustedshops.com/merchants/membership.html',
		'FR' => 'http://www.trustedshops.com/marchands/affiliation.html',
		'PL' => 'http://www.trustedshops.pl/handlowcy/cennik.html',
		'ES' => '',
		'IT' => ''
	);

	/**
	 * Link to obtain the certificate about the shop.
	 * Use by seal of approval.
	 * @see TSCommon::hookRightColumn()
	 * @var array
	 */
	private static $certificate_link = array(
		'DE' => 'http://www.trustedshops.de/profil/#shop_name#_#shop_id#.html',
		'EN' => 'http://www.trustedshops.com/profile/#shop_name#_#shop_id#.html',
		'FR' => 'http://www.trustedshops.fr/boutique_en_ligne/profil/#shop_name#_#shop_id#.html',
		'PL' => 'http://www.trustedshops.de/profil/#shop_name#_#shop_id#.html',
		'ES' => 'http://www.trustedshops.es/perfil/#shop_name#_#shop_id#.html',
		'IT' => 'http://www.trustedshops.it/profilo/#shop_name#_#shop_id#.html'
	);

	/**
	 * Available language for used TrustedShops Buyer Protection
	 * @see TSCommon::__construct()
	 * @var array
	 */
	public static $available_languages = array(/*'XX'=>'xx', */'EN' => 'en', 'FR' => 'fr', 'DE' => 'de', 'PL' => 'pl', 'ES' => 'es', 'IT' => 'it', 'NL' => 'nl');

	public static $available_languages_for_adding = array();

	/**
	 * @todo : be sure : see TrustedShopsRating::__construct()
	 * @var array
	 */
	public $limited_countries = array('PL', 'GB', 'US', 'FR', 'DE', 'ES', 'IT' , 'NL');

	/**
	 * Differents urls to call for Trusted Shops API
	 * @var array
	 */
	private static $webservice_urls = array(
		'administration' => array(
			'test' => 'https://qa.trustedshops.de/ts/services/TsProtection?wsdl',
			'production' => 'https://www.trustedshops.de/ts/services/TsProtection?wsdl',
		),
		'front-end' => array(
			'test' => 'https://protection-qa.trustedshops.com/ts/protectionservices/ApplicationRequestService?wsdl',
			'production' => 'https://protection.trustedshops.com/ts/protectionservices/ApplicationRequestService?wsdl',
		),
	);

	/* Configuration vars */
	private static $shopsw;
	private static $et_cid;
	private static $et_lid;

	/**
	 * Its must look like :
	 * array(
	 *        'lang_iso(ex: FR)' => array('stateEnum'=>'', 'typeEnum'=>'', 'url'=>'', 'tsID'=>'', 'user'=>'', 'password'=>'', 'variant'=>''),
	 *        ...
	 * )
	 * @var array
	 */
	public static $certificates;

	private $available_seal_variants = array('default' => 'Default', 'small' => 'Small', 'text' => 'Text', 'reviews' => 'Reviews');

	private static $default_lang;
	private static $cat_id;
	private static $env_api;

	/**
	 * save shop url
	 * @var string
	 */
	private $site_url;

	/**
	 * Payment type used by Trusted Shops.
	 * @var array
	 */
	private static $payments_type;

	public function __construct()
	{
		self::$template_version = version_compare(_PS_VERSION_, '1.6', '<') ? '1.5' : '1.6';
		
		// need to set this in constructor to allow translation
		TSCommon::$payments_type = array(
			'DIRECT_DEBIT' => $this->l('Direct debit'),
			'CREDIT_CARD' => $this->l('Credit Card'),
			'INVOICE' => $this->l('Invoice'),
			'CASH_ON_DELIVERY' => $this->l('Cash on delivery'),
			'PREPAYMENT' => $this->l('Prepayment'),
			'CHEQUE' => $this->l('Cheque'),
			'PAYBOX' => $this->l('Paybox'),
			'PAYPAL' => $this->l('PayPal'),
			'CASH_ON_PICKUP' => $this->l('Cash on pickup'),
			'FINANCING' => $this->l('Financing'),
			'LEASING' => $this->l('Leasing'),
			'T_PAY' => $this->l('T-Pay'),
			'CLICKANDBUY' => $this->l('Click&Buy'),
			'GIROPAY' => $this->l('Giropay'),
			'GOOGLE_CHECKOUT' => $this->l('Google Checkout'),
			'SHOP_CARD' => $this->l('Online shop payment card'),
			'DIRECT_E_BANKING' => $this->l('DIRECTebanking.com'),
			'MONEYBOOKERS' => $this->l('moneybookers.com'),
			'OTHER' => $this->l('Other method of payment'),
		);

		$this->tab_name = $this->l('Trusted Shops quality seal and buyer protection');
		$this->site_url = Tools::htmlentitiesutf8('http://'.$_SERVER['HTTP_HOST'].__PS_BASE_URI__);

		TSBPException::setTranslationObject($this);

		if (!method_exists('Tools', 'jsonDecode') || !method_exists('Tools', 'jsonEncode'))
			$this->warnings[] = $this->l('Json functions must be implemented in your php version');
		else
		{
			foreach (array_keys(self::$available_languages) as $iso)
			{
				$certificate = Configuration::get(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.Tools::strtoupper($iso));
				TSCommon::$certificates[Tools::strtoupper($iso)] = (array)Tools::jsonDecode(Tools::htmlentitiesDecodeUTF8($certificate));

				if (!isset(TSCommon::$certificates[Tools::strtoupper($iso)]['tsID']) || (isset(TSCommon::$certificates[Tools::strtoupper($iso)]['tsID']) && TSCommon::$certificates[Tools::strtoupper($iso)]['tsID'] == ''))
					TSCommon::$available_languages_for_adding[Tools::strtoupper($iso)] = Tools::strtoupper($iso);
			}

			if (TSCommon::$shopsw === null)
			{
				TSCommon::$shopsw = Configuration::get(TSCommon::PREFIX_TABLE.'SHOPSW');
				TSCommon::$et_cid = Configuration::get(TSCommon::PREFIX_TABLE.'ET_CID');
				TSCommon::$et_lid = Configuration::get(TSCommon::PREFIX_TABLE.'ET_LID');
				TSCommon::$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
				TSCommon::$cat_id = (int)Configuration::get(TSCommon::PREFIX_TABLE.'CAT_ID');
				TSCommon::$env_api = Configuration::get(TSCommon::PREFIX_TABLE.'ENV_API');
			}
		}
	}
	
	public static function getTemplateByVersion($template_name) {
		if (self::$template_version == '1.5') {
			return $template_name . '_1.5.tpl';
		}
		
		return $template_name . '.tpl';
	}

	public function install()
	{
		if (!method_exists('Tools', 'jsonDecode') || !method_exists('Tools', 'jsonEncode'))
			return false;

		foreach (array_keys(self::$available_languages) as $iso)
			Configuration::updateValue(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.Tools::strtoupper($iso),
				Tools::htmlentitiesUTF8(Tools::jsonEncode(array('stateEnum' => '', 'typeEnum' => '', 'url' => '', 'tsID' => '', 'user' => '', 'password' => ''))));

		Configuration::updateValue(TSCommon::PREFIX_TABLE.'SHOPSW', '');
		Configuration::updateValue(TSCommon::PREFIX_TABLE.'ET_CID', '');
		Configuration::updateValue(TSCommon::PREFIX_TABLE.'ET_LID', '');
		Configuration::updateValue(TSCommon::PREFIX_TABLE.'ENV_API', TSCommon::ENV_MOD);

		$query = '
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.TSCommon::DB_ITEMS.'` (
			`id_item` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`id_product` INT NOT NULL,
			`ts_id` VARCHAR( 33 ) NOT NULL,
			`id` INT NOT NULL,
			`currency` VARCHAR( 3 ) NOT NULL,
			`gross_fee` DECIMAL( 20, 6 ) NOT NULL,
			`net_fee` DECIMAL( 20, 6 ) NOT NULL,
			`protected_amount_decimal` INT NOT NULL,
			`protection_duration_int` INT NOT NULL,
			`ts_product_id` TEXT NOT NULL,
			`creation_date` VARCHAR( 25 ) NOT NULL
		);';

		Db::getInstance()->Execute($query);

		$query = '
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.TSCommon::DB_APPLI.'` (
			`id_application` INT NOT NULL PRIMARY KEY,
			`ts_id` VARCHAR( 33 ) NOT NULL,
			`id_order` INT NOT NULL,
			`statut_number` INT NOT NULL DEFAULT \'0\',
			`creation_date` DATETIME NOT NULL,
			`last_update` DATETIME NOT NULL
		);';

		Db::getInstance()->Execute($query);

		//add hidden category
		$category = new Category();

		foreach (self::$available_languages as $iso => $lang)
		{
			$language = Language::getIdByIso(Tools::strtolower($iso));

			$category->name[$language] = 'Trustedshops';
			$category->link_rewrite[$language] = 'trustedshops';
		}

		// If the default lang is different than available languages :
		// (Bug occurred otherwise)
		if (!array_key_exists(Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT')), self::$available_languages))
		{
			$language = (int)Configuration::get('PS_LANG_DEFAULT');

			$category->name[$language] = 'Trustedshops';
			$category->link_rewrite[$language] = 'trustedshops';
		}

		// $category->id_parent = Configuration::get('PS_HOME_CATEGORY');
		$category->id_parent = 0;
		$category->level_depth = 0;
		$category->active = 0;
		$category->add();

		Configuration::updateValue(TSCommon::PREFIX_TABLE.'CAT_ID', (int)$category->id);
		Configuration::updateValue(TSCommon::PREFIX_TABLE.'SECURE_KEY', Tools::strtoupper(Tools::passwdGen(16)));

		return (RatingAlert::createTable());
	}

	public function uninstall()
	{
		foreach (array_keys(self::$available_languages) as $iso)
			Configuration::deleteByName(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.Tools::strtoupper($iso));

		$category = new Category((int)TSCommon::$cat_id);
		$category->delete();

		Configuration::deleteByName(TSCommon::PREFIX_TABLE.'CAT_ID');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE.'SHOPSW');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE.'ET_CID');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE.'ET_LID');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE.'ENV_API');
		Configuration::deleteByName(TSCommon::PREFIX_TABLE.'SECURE_KEY');

		return (RatingAlert::dropTable());
	}

	/**
	 * Just for return the file path
	 * @return string
	 */
	public function getCronFilePath()
	{
		return $this->site_url.'modules/'.self::$module_name.'/cron_garantee.php?secure_key='.Configuration::get(TSCommon::PREFIX_TABLE.'SECURE_KEY');
	}

	/**
	 * This method is used to access of TrustedShops API
	 * from a SoapClient object.
	 *
	 * @uses TSCommon::$webservice_urls with TSCommon::$env_api
	 *         To get the api url according to the environment (test or production)
	 * @param string $type
	 * @return SoapClient
	 */
	private function getClient($type = TSCommon::WEBSERVICE_BO)
	{
		$url = TSCommon::$webservice_urls[$type][TSCommon::$env_api];
		$client = false;

		try
		{
			$client = new SoapClient($url);
		}
		catch (SoapFault $fault)
		{
			$this->errors[] = $this->l('Code #').$fault->faultcode.',<br />'.$this->l('message:').$fault->faultstring;
		}

		return $client;
	}

	private function isValidCertificateID($certificate)
	{
		if (!preg_match('/^(X){1}([0-9A-Z]{32})$/', $certificate))
			return false;

		foreach (self::$certificates as $cert_info)
		{
			if (isset($cert_info['tsID']) && ($cert_info['tsID'] == $certificate))
				return false;
		}

		return true;
	}

	/**
	 * Checks the Trusted Shops IDs entered in the shop administration
	 * and returns the characteristics of the corresponding certificate.
	 *
	 * @uses TSCommon::getClient()
	 * @param string $certificate certificate code already send by Trusted Shops
	 */
	private function checkCertificate($certificate, $lang)
	{
		$array_state = array(
			'PRODUCTION' => $this->l('The Trusted Shops ID is valid'),
			'NO_AUDIT' => $this->l('The Trusted Shops ID is not audit'),
			'CANCELLED' => $this->l('The Trusted Shops ID has expired'),
			'DISABLED' => $this->l('The Trusted Shops ID has been disabled'),
			'INTEGRATION' => $this->l('The shop is currently being certified'),
			'INVALID_TS_ID' => $this->l('No ID has been allocated to the Trusted Shops ID'),
			'TEST' => $this->l('Test Trusted Shops ID'),

		);

		$client = $this->getClient();
		$validation = false;

		if ($lang == '')
			$this->errors[] = $this->l('Select language');
		elseif (!in_array($lang, self::$available_languages_for_adding))
			$this->errors[] = $this->l('This language is not in list of available languages for Trusted Shops ID');
		elseif ($this->isValidCertificateID($certificate))
		{
			try
			{
				$validation = $client->checkCertificate($certificate);
			}
			catch (SoapFault $fault)
			{
				$this->errors[] = $this->l('Code #').$fault->faultcode.',<br />'.$this->l('message:').$fault->faultstring;
				return false;
			}

			if (is_int($validation))
				throw new TSBPException($validation, TSBPException::ADMINISTRATION);
			if (!$validation || array_key_exists($validation->stateEnum, $array_state))
			{
				if ($validation->stateEnum === 'TEST' ||
					$validation->stateEnum === 'PRODUCTION' ||
					$validation->stateEnum === 'INTEGRATION'
				)
				{
					$this->confirmations[] = $array_state[$validation->stateEnum];
					return $validation;
				}
				elseif ($validation->stateEnum == 'INVALID_TS_ID' || $validation->stateEnum == 'NO_AUDIT')
				{

					$filename = $this->getTempWidgetFilename($certificate);
					$cache = new WidgetCache(_PS_MODULE_DIR_.$filename, $certificate);

					if (!$cache->isFresh())
						$cache->refresh();

					if (filesize(_PS_MODULE_DIR_.$filename) > 1000)
					{
						$validation->certificationLanguage = $lang;
						$validation->stateEnum = 'PRODUCTION';
						$validation->typeEnum = 'UNKNOWN';

						return $validation;
					}
					else
					{
						$this->errors[] = $array_state[$validation->stateEnum];
						return false;
					}
				}
				else
				{
					$this->errors[] = $array_state[$validation->stateEnum];
					return false;
				}
			}
			else
				$this->errors[] = $this->l('Unknown error.');
		}
		else
			$this->errors[] = $this->l('Invalid Trusted Shops ID.');
	}

	/**
	 * Checks the shop's web service access credentials.
	 *
	 * @uses TSCommon::getClient()
	 * @param string $ts_id
	 * @param string $user
	 * @param string $password
	 */
	private function checkLogin($ts_id, $user, $password)
	{
		$client = $this->getClient();
		$return = 0;

		try
		{
			$return = $client->checkLogin($ts_id, $user, $password);
		}
		catch (SoapClient $fault)
		{
			$this->errors[] = $this->l('Code #').$fault->faultcode.',<br />'.$this->l('message:').$fault->faultstring;
		}

		if ($return < 0)
			throw new TSBPException($return, TSBPException::ADMINISTRATION);

		return true;
	}

	/**
	 * Returns the characteristics of the buyer protection products
	 * that are allocated individually to each certificate by Trusted Shops.
	 *
	 * @uses TSCommon::getClient()
	 * @param string $ts_id
	 */
	private function getProtectionItems($ts_id)
	{
		$client = $this->getClient();

		try
		{
			$items = $client->getProtectionItems($ts_id);

			// Sometimes an object could be send for the item attribute if there is only one result
			if (isset($items) && !is_array($items->item))
				$items->item = array(0 => $items->item);
		}
		catch (SoapFault $fault)
		{
			$this->errors[] = $this->l('Code #').$fault->faultcode.',<br />'.$this->l('message:').$fault->faultstring;
		}

		return (isset($items->item)) ? $items->item : false;
	}

	/**
	 * Check validity for params required for TSCommon::requestForProtectionV2()
	 *
	 * @param array $params
	 */
	private function requestForProtectionV2ParamsValidator($params)
	{
		$bool_flag = true;

		$mandatory_keys = array(
			array('name' => 'tsID', 'validator' => array('isCleanHtml')),
			array('name' => 'tsProductID', 'validator' => array('isCleanHtml')),
			array('name' => 'amount', 'validator' => array('isFloat')),
			array('name' => 'currency', 'length' => 3, 'validator' => array('isString')),
			array('name' => 'paymentType', 'validator' => array('isString')),
			array('name' => 'buyerEmail', 'validator' => array('isEmail')),
			array('name' => 'shopCustomerID', 'validator' => array('isInt')),
			array('name' => 'shopOrderID', 'validator' => array('isString')),
			array('name' => 'orderDate', 'ereg' => '#[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}#'),
			array('name' => 'shopSystemVersion', 'validator' => array('isCleanHtml')),
			array('name' => 'wsUser', 'validator' => array('isCleanHtml')),
			array('name' => 'wsPassword', 'validator' => array('isCleanHtml'))
		);

		foreach ($mandatory_keys as $key)
		{
			$bool_flag = (array_key_exists($key['name'], $params)) ? $bool_flag : false;

			if ($bool_flag)
			{
				if (isset($key['length']))
					$bool_flag = Tools::strlen((string)$params[$key['name']]) === $key['length'];
				if (isset($key['length-min']))
					$bool_flag = Tools::strlen((string)$params[$key['name']]) > $key['length-min'];
				if (isset($key['length-max']))
					$bool_flag = Tools::strlen((string)$params[$key['name']]) < $key['length-max'];
				if (isset($key['validator']))
					foreach ($key['validator'] as $validator)
						if (method_exists('Validate', $validator))
							$bool_flag = !Validate::$validator((string)$params[$key['name']]) ? false : $bool_flag;
				if (isset($key['ereg']))
					$bool_flag = !preg_match($key['ereg'], $params[$key['name']]) ? false : $bool_flag;
			}

			if (!$bool_flag)
			{
				$this->errors[] = sprintf($this->l('The field %s is wrong, please ensure it was correctly filled.'), $key['name']);
				break;
			}
		}
		return $bool_flag;
	}

	/**
	 * Create the Buyer Protection application by the web service.
	 * Applications are saved by Trusted Shops and are processed at regular intervals.
	 *
	 * @uses TSCommon::getClient()
	 * @uses TSCommon::requestForProtectionV2ParamsValidator()
	 *         to check required params
	 * @see TSCommon::cronTasks()
	 * @param array $params
	 */
	private function requestForProtectionV2($params)
	{
		$code = 0;
		$client = $this->getClient(TSCommon::WEBSERVICE_FO);
		$testing_params = $this->requestForProtectionV2ParamsValidator($params);

		$query = '
		SELECT `id_order`
		FROM `'._DB_PREFIX_.TSCommon::DB_APPLI.'`
		WHERE `id_order` = "'.(int)$params['shopOrderID'].'"';

		// If an order was already added, no need to continue.
		// Otherwise a new application is created by TrustedShops.
		// this can occurred when order confirmation page is reload.
		if (Db::getInstance()->getValue($query))
			return false;

		if ($testing_params)
		{
			try
			{
				$code = $client->requestForProtectionV2(
					$params['tsID'], $params['tsProductID'], $params['amount'],
					$params['currency'], $params['paymentType'], $params['buyerEmail'],
					$params['shopCustomerID'], $params['shopOrderID'], $params['orderDate'],
					$params['shopSystemVersion'], $params['wsUser'], $params['wsPassword']);

				if ($code < 0)
					throw new TSBPException($code, TSBPException::FRONT_END);
			}
			catch (SoapFault $fault)
			{
				$this->errors[] = $this->l('Code #').$fault->faultcode.',<br />'.$this->l('message:').$fault->faultstring;
			}
			catch (TSBPException $e)
			{
				$this->errors[] = $e->getMessage();
			}

			if ($code > 0)
			{
				$date = date('Y-m-d H:i:s');

				$query = '
				INSERT INTO `'._DB_PREFIX_.TSCommon::DB_APPLI.'`
				(`id_application`, `ts_id`, `id_order`, `creation_date`, `last_update` )
				VALUES ("'.pSQL($code).'", "'.pSQL($params['tsID']).'", "'.pSQL($params['shopOrderID']).'", "'.pSQL($date).'", "'.pSQL($date).'")';

				Db::getInstance()->Execute($query);

				// To reset product quantity in database.
				$query = '
				SELECT `id_product`
				FROM `'._DB_PREFIX_.TSCommon::DB_ITEMS.'`
				WHERE `ts_product_id` = "'.pSQL($params['tsProductID']).'"
				AND `ts_id` = "'.pSQL($params['tsID']).'"';

				if (($id_product = Db::getInstance()->getValue($query)))
				{
					$product = new Product($id_product);
					$product->quantity = 1000;
					$product->update();
					unset($product);
				}
			}
		}
		else
			$this->errors[] = $this->l('Some parameters sending to "requestForProtectionV2" method are wrong or missing.');
	}

	/**
	 * With the getRequestState() method,
	 * the status of a guarantee application is requested
	 * and in the event of a successful transaction,
	 * the guarantee number is returned.
	 *
	 * @uses TSCommon::getClient()
	 * @param array $params
	 * @throws TSBPException
	 */
	private function getRequestState($params)
	{
		$client = $this->getClient(TSCommon::WEBSERVICE_FO);
		$code = 0;

		try
		{
			$code = $client->getRequestState($params['tsID'], $params['applicationID']);

			if ($code < 0)
				throw new TSBPException($code, TSBPException::FRONT_END);
		}
		catch (SoapFault $fault)
		{
			$this->errors[] = $this->l('Code #').$fault->faultcode.',<br />'.$this->l('message:').$fault->faultstring;
		}
		catch (TSBPException $e)
		{
			$this->errors[] = $e->getMessage();
		}

		return $code;
	}

	/**
	 * Check statut of last applications
	 * saved with TSCommon::requestForProtectionV2()
	 *
	 * Negative value means an error occurred.
	 * Error code are managed in TSBPException.
	 * @see (exception) TSBPException::_getFrontEndMessage() method
	 *
	 * Trusted Shops recommends that the request
	 * should be automated by a cronjob with an interval of 10 minutes.
	 * @see /../cron_garantee.php
	 *
	 * A message is added to the sheet order in Back-office,
	 * @see Message class
	 *
	 * @uses TSCommon::getRequestState()
	 * @uses Message class
	 * @return void
	 */
	public function cronTask()
	{
		// get the last 20min to get the api number (to be sure)
		$mktime = mktime(date('H'), date('i') - 20, date('s'), date('m'), date('d'), date('Y'));
		$date = date('Y-m-d H:i:s', $mktime);
		$db_name = _DB_PREFIX_.TSCommon::DB_APPLI;

		$query = '
		SELECT *
		FROM `'.$db_name.'`
		WHERE `last_update` >= "'.pSQL($date).'"
		OR `statut_number` <= 0';

		$to_check = Db::getInstance()->ExecuteS($query);

		foreach ($to_check as $application)
		{
			$code = $this->getRequestState(array('tsID' => $application['ts_id'], 'applicationID' => $application['id_application']));

			if (!empty($this->errors))
			{
				$return_message = '<p style="color:red;">'.$this->l('Trusted Shops API returns an error concerning the application #').$application['id_application'].': <br />'.implode(', <br />', $this->errors).'</p>';
				$this->errors = array();
			}
			elseif ($code > 0)
				$return_message = sprintf($this->l('Trusted Shops application number %1$d was successfully processed. The guarantee number is: %2$d'), $application['id_application'], $code);

			$query = '
			UPDATE `'.$db_name.'`
			SET `statut_number` = "'.pSQL($code).'"
			WHERE `id_application` >= "'.pSQL($application['id_application']).'"';

			Db::getInstance()->Execute($query);

			$msg = new Message();
			$msg->message = $return_message;
			$msg->id_order = (int)$application['id_order'];
			$msg->private = 1;
			$msg->add();
		}
	}

	/**
	 * Registration link to Trusted Shops
	 *
	 * @param string $shopsw
	 * @param string $et_cid
	 * @param string $et_lid
	 * @param string $lang
	 * @return boolean|string boolean in case of $lang is not supported by Trusted Shops
	 *           string return is the url to access of form subscription
	 */
	private function makeRegistrationLink($shopsw, $et_cid, $et_lid, $lang)
	{
		if (array_key_exists($lang, $this->registration_link))
			return $this->registration_link[$lang].sprintf('?shopsw=%s&et_cid=%s&et_lid=%s', urlencode($shopsw), urlencode($et_cid), urlencode($et_lid));
		return false;
	}

	/**
	 * saved paramter to acces of particular subscribtion link.
	 *
	 * @return string the registration link.
	 */
	private function submitRegistrationLink()
	{
		// @todo : ask for more infos about values types
		TSCommon::$shopsw = (Validate::isCleanHtml(Tools::getValue('shopsw'))) ? Tools::getValue('shopsw') : '';
		TSCommon::$et_cid = (Validate::isCleanHtml(Tools::getValue('et_cid'))) ? Tools::getValue('et_cid') : '';
		TSCommon::$et_lid = (Validate::isCleanHtml(Tools::getValue('et_lid'))) ? Tools::getValue('et_lid') : '';

		Configuration::updateValue(TSCommon::PREFIX_TABLE.'SHOPSW', TSCommon::$shopsw);
		Configuration::updateValue(TSCommon::PREFIX_TABLE.'ET_CID', TSCommon::$et_cid);
		Configuration::updateValue(TSCommon::PREFIX_TABLE.'ET_LID', TSCommon::$et_lid);

		$link_registration = $this->makeRegistrationLink(TSCommon::$shopsw, TSCommon::$et_cid, TSCommon::$et_lid, Tools::getValue('lang'));
		$this->confirmations[] = $this->l('Registration link has been created. Follow this link if you were not redirected earlier:').'&nbsp;<a href="'.$link_registration.'" class="link">&gt;'.$this->l('Link').'&lt;</a>';

		return $link_registration;
	}

	/**
	 * Save in special database each buyer protection product for a certificate,
	 * Each Trusted Shops particular characteristics is saved.
	 * Create a product in Prestashop database to allow added each of them in cart.
	 *
	 * @param array|stdClass $protection_items
	 * @param string $ts_id
	 */
	private function saveProtectionItems($protection_items, $ts_id)
	{
		$query = '
		DELETE ts, p, pl
		FROM `'._DB_PREFIX_.TSCommon::DB_ITEMS.'` AS ts
		LEFT JOIN `'._DB_PREFIX_.'product` AS p ON ts.`id_product` = p.`id_product`
		LEFT JOIN `'._DB_PREFIX_.'product_lang` AS pl ON ts.`id_product` = pl.`id_product`
		WHERE ts.`ts_id`="'.pSQL($ts_id).'"';

		Db::getInstance()->Execute($query);

		foreach ($protection_items as $item)
		{
			//add hidden product
			$product = new Product();

			foreach (array_keys(self::$available_languages) as $iso)
			{
				$language = Language::getIdByIso(Tools::strtolower($iso));

				if ((int)$language !== 0)
				{
					$product->name[$language] = 'TrustedShops guarantee';
					$product->link_rewrite[$language] = 'trustedshops_guarantee';
				}
			}

			// If the default lang is different than available languages :
			// (Bug occurred otherwise)
			if (!array_key_exists(Language::getIsoById((int)Configuration::get('PS_LANG_DEFAULT')), self::$available_languages))
			{
				$product->name[(int)Configuration::get('PS_LANG_DEFAULT')] = 'Trustedshops';
				$product->link_rewrite[(int)Configuration::get('PS_LANG_DEFAULT')] = 'trustedshops';
			}

			// Add specifics translations
			$id_lang = Language::getIdByIso('de');
			if ((int)$id_lang > 0) $product->name[$id_lang] = 'Trusted Shops Käuferschutz';
			$id_lang = Language::getIdByIso('en');
			if ((int)$id_lang > 0) $product->name[$id_lang] = 'Trusted Shops buyer protection';
			$id_lang = Language::getIdByIso('fr');
			if ((int)$id_lang > 0) $product->name[$id_lang] = 'Trusted Shops protection acheteur';
			$id_lang = Language::getIdByIso('it');
			if ((int)$id_lang > 0) $product->name[$id_lang] = 'Trusted Shops protezione acquirenti';

			$product->quantity = 1000;
			$product->price = Tools::convertPrice($item->grossFee, Currency::getIdByIsoCode($item->currency));
			$product->id_category_default = TSCommon::$cat_id;
			$product->active = true;
			$product->visibility = 'none';
			$product->id_tax_rules_group = 0;
			$product->add();
			$product->addToCategories(TSCommon::$cat_id);
			if ($product->id)
			{
				$query = '
				INSERT INTO `'._DB_PREFIX_.TSCommon::DB_ITEMS.'`
				(`creation_date`, `id_product`, `ts_id`, `id`, `currency`, `gross_fee`, `net_fee`,
				`protected_amount_decimal`, `protection_duration_int`, `ts_product_id`)
				VALUES ("'.pSQL($item->creationDate).'", "'.pSQL($product->id).'", "'.pSQL($ts_id).'",
				"'.(int)$item->id.'", "'.pSQL($item->currency).'", "'.pSQL($item->grossFee).'",
				"'.pSQL($item->netFee).'", "'.pSQL($item->protectedAmountDecimal).'",
				"'.pSQL($item->protectionDurationInt).'", "'.pSQL($item->tsProductID).'")';

				Db::getInstance()->Execute($query);

				if (class_exists('StockAvailable'))
				{
					$id_stock_available = Db::getInstance()->getValue('
						SELECT s.`id_stock_available` FROM `'._DB_PREFIX_.'stock_available` s
						WHERE s.`id_product` = '.(int)$product->id);

					$stock = new StockAvailable($id_stock_available);
					$stock->id_product = $product->id;
					$stock->out_of_stock = 1;
					$stock->id_product_attribute = 0;
					$stock->quantity = 1000000;
					$stock->id_shop = Context::getContext()->shop->id;
					if ($stock->id)
						$stock->update();
					else
						$stock->add();
				}
			}
			else
				$this->errors['products'] = $this->l('Product wasn\'t saved.');
		}
	}

	/**
	 * Check a Trusted Shops certificate in shop for preview.
	 *
	 * @uses TSCommon::getProtectionItems()
	 *         to get all buyer protection products from Trusted Shops
	 * @uses TSCommon::saveProtectionItems()
	 *         to save buyer protection products in shop
	 * @return boolean true if certificate is added successfully, false otherwise
	 */
	private function submitAddCertificate()
	{
		$checked_certificate = false;

		try
		{
			$checked_certificate = $this->checkCertificate(ToolsCore::getValue('new_certificate'), Tools::getValue('lang'));
		}
		catch (TSBPException $e)
		{
			$this->errors[] = $e->getMessage();
		}

		return (bool)$checked_certificate;
	}
	
	public static function registerCertificate($certificate_id)
	{
		if (extension_loaded('curl'))
		{
			$ch = curl_init();
			
			$data = array(
				'cid' => $certificate_id,
				'time' => time()
			);
			
			curl_setopt_array($ch, array(
				CURLOPT_HEADER => false,
				CURLOPT_URL => 'http://silbersaiten.de/ts/register.php?' . http_build_query($data),
				CURLOPT_POST => 0,
				CURLOPT_FRESH_CONNECT => 1,
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_FORBID_REUSE => 1,
				CURLOPT_TIMEOUT => 10
			));
			
			if ( ! $result = curl_exec($ch))
				echo curl_error($ch);

			curl_close($ch);
		}
	}

	/**
	 * Check, confirm and add a Trusted Shops certificate in shop.
	 *
	 * @uses TSCommon::getProtectionItems()
	 *         to get all buyer protection products from Trusted Shops
	 * @uses TSCommon::saveProtectionItems()
	 *         to save buyer protection products in shop
	 * @return boolean true if certificate is added successfully, false otherwise
	 */
	private function submitConfirmCertificate()
	{
		$checked_certificate = false;

		try
		{
			$checked_certificate = $this->checkCertificate(ToolsCore::getValue('new_certificate'), Tools::getValue('lang'));
		}
		catch (TSBPException $e)
		{
			$this->errors[] = $e->getMessage();
		}

		if ($checked_certificate)
		{
			TSCommon::$certificates[Tools::strtoupper($checked_certificate->certificationLanguage)] = array(
				'stateEnum' => $checked_certificate->stateEnum,
				'typeEnum' => $checked_certificate->typeEnum,
				'tsID' => $checked_certificate->tsID,
				'url' => $checked_certificate->url,
				'user' => '',
				'password' => '',
				'variant' => 'default',
				'yoffset' => '0',
				'jscode' => '',
				'display_rating_front_end' => '1',
				'display_rating_oc' => '0',
				'send_separate_mail' => '0',
				'send_seperate_mail_delay' => '0',
				'send_seperate_mail_order_state' => Configuration::get('PS_OS_SHIPPING')
			);

			// update the configuration var
			Configuration::updateValue(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.Tools::strtoupper($checked_certificate->certificationLanguage), Tools::htmlentitiesUTF8(Tools::jsonEncode(TSCommon::$certificates[Tools::strtoupper($checked_certificate->certificationLanguage)])));
			unset(self::$available_languages_for_adding[Tools::strtoupper($checked_certificate->certificationLanguage)]);
			$this->confirmations[] = $this->l('Your Trusted Shops membership is valid.');
			
			if ($checked_certificate->typeEnum !== 'UNKNOWN')
				self::registerCertificate($checked_certificate->tsID);

			if ($checked_certificate->typeEnum === 'EXCELLENCE')
			{
				try
				{
					$protection_items = $this->getProtectionItems($checked_certificate->tsID);

					if ($protection_items)
						$this->saveProtectionItems($protection_items, $checked_certificate->tsID);
				}
				catch (TSBPException $e)
				{
					$this->errors[] = $e->getMessage();
				}
			}
		}
		return (bool)$checked_certificate;
	}

	/**
	 * Change the certificate values.
	 * concerns only excellence certificate
	 * for payment type, login and password values.
	 *
	 * @uses TSCommon::checkLogin()
	 * @return true;
	 */
	private function submitChangeCertificate()
	{
		$all_payment_type = Tools::getValue('choosen_payment_type');
		$iso_lang = Tools::getValue('iso_lang');
		$password = Tools::getValue('password');
		$user = Tools::getValue('user');

		if ($user != '' && $password != '')
		{
			TSCommon::$certificates[$iso_lang]['payment_type'] = array();
			$check_login = false;

			if ($all_payment_type)
				if (is_array($all_payment_type))
					foreach ($all_payment_type as $key => $module_id)
						TSCommon::$certificates[$iso_lang]['payment_type'][(string)$key] = $module_id;

			try
			{
				$check_login = $this->checkLogin(TSCommon::$certificates[$iso_lang]['tsID'], $user, $password);
			}
			catch (TSBPException $e)
			{
				$this->errors[] = $e->getMessage();
			}

			if ($check_login)
			{
				TSCommon::$certificates[$iso_lang]['user'] = $user;
				TSCommon::$certificates[$iso_lang]['password'] = $password;

				Configuration::updateValue(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.$iso_lang, Tools::htmlentitiesUTF8(Tools::jsonEncode(TSCommon::$certificates[$iso_lang])));
				$this->confirmations[] = $this->l('ID login has been successful.');

			}
			else
				$this->errors[] = $this->l('ID login failed');
		}
		else
			$this->errors[] = $this->l('You have to set a username and a password before any changes can be made.');

		return true;
	}

	private function submitChangeOptionsCertificate()
	{
		$variant = Tools::getValue('variant', 'default');
		$yoffset = Validate::isInt(Tools::getValue('yoffset', '0'))?Tools::getValue('yoffset', '0'):0;
		$jscode = Tools::getValue('jscode', 'default');
		$display_rating_front_end = Tools::getValue('display_rating_front_end', '0');
		$display_rating_oc = Tools::getValue('display_rating_oc', '0');
		$send_separate_mail = Tools::getValue('send_separate_mail', '0');
		$send_seperate_mail_delay = Tools::getValue('send_seperate_mail_delay', '0');
		$send_seperate_mail_order_state = Tools::getValue('send_seperate_mail_order_state', '0');
		$iso_lang = Tools::strtoupper(Tools::getValue('iso_lang'));


		TSCommon::$certificates[$iso_lang] = array_merge(TSCommon::$certificates[$iso_lang], array(
			'variant' => $variant,
			'yoffset' => $yoffset,
			'jscode' => $jscode,
			'display_rating_front_end' => $display_rating_front_end,
			'display_rating_oc' => $display_rating_oc,
			'send_separate_mail' => $send_separate_mail,
			'send_seperate_mail_delay' => $send_seperate_mail_delay,
			'send_seperate_mail_order_state' => $send_seperate_mail_order_state
		));


		//update the configuration var
		Configuration::updateValue(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.$iso_lang, Tools::htmlentitiesUTF8(Tools::jsonEncode(TSCommon::$certificates[$iso_lang])));

		//print_r(Configuration::get(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.$iso_lang)); exit;

		$this->confirmations[] = $this->l('Trusted Shops ID options has been modified successfully.');


		//$this->errors[] = $this->l('You have to set a username and a password before any changes can be made.');

		return true;
	}


	/**
	 * Change the environment for working.
	 * Not use anymore but keeped
	 * @return true
	 */
	private function submitEnvironment()
	{
		TSCommon::$env_api = Tools::getValue('env_api');
		Configuration::updateValue(TSCommon::PREFIX_TABLE.'ENV_API', TSCommon::$env_api);

		return true;
	}

	/*
	 ** Update the env_api
	 */
	public function setEnvApi($env_api)
	{
		if (Configuration::get(TSCommon::PREFIX_TABLE.'ENV_API') != $env_api)
			Configuration::updateValue(TSCommon::PREFIX_TABLE.'ENV_API', $env_api);
		TSCommon::$env_api = $env_api;
	}

	/**
	 * Dispatch post process depends on each formular
	 *
	 * @return array depend on the needs about each formular.
	 */
	private function preProcess()
	{
		$posts_return = array();

		/*if (Tools::isSubmit('submit_registration_link'))
			$posts_return['registration_link'] = $this->submitRegistrationLink();*/

		//add certificate
		if (Tools::isSubmit('submit_add_certificate'))
			$posts_return['add_certificate'] = $this->submitAddCertificate();

		//confirm certificate
		if (Tools::isSubmit('submit_confirm_certificate'))
			$posts_return['confirm_certificate'] = $this->submitConfirmCertificate();

		$edit = Tools::getValue('certificate_edit', '');
		$delete = Tools::getValue('certificate_delete', '');
		$options = Tools::getValue('certificate_options', '');

		// delete certificate
		if (($delete != '') && isset(TSCommon::$certificates[$delete]['tsID']))
		{
			$certificate_to_delete = TSCommon::$certificates[$delete]['tsID'];
			Configuration::deleteByName(TSCommon::PREFIX_TABLE.'CERTIFICATE_'.Tools::strtoupper($delete));
			unset(TSCommon::$certificates[$delete]);
			$this->confirmations[] = $this->l('The Trusted Shops ID').' "'.$certificate_to_delete.'" ('.$this->l('language').' : '.$delete.') '.$this->l('has been deleted successfully');
		}

		// edit cert
		if ($edit != '')
			$posts_return['edit_certificate'] = $edit;

		// set options of cert
		if ($options != '')
			$posts_return['options_certificate'] = $options;


		if (Tools::isSubmit('submit_change_certificate'))
			$posts_return['change_certificate'] = $this->submitChangeCertificate();

		if (Tools::isSubmit('submit_changeoptions_certificate'))
			$posts_return['changeoptions_certificate'] = $this->submitChangeOptionsCertificate();

		return $posts_return;
	}

	/**
	 * Display each formaular in back-office
	 *
	 * @see Module::getContent()
	 * @return string for displaying form.
	 */
	public function getContent()
	{
		$bool_display_certificats = false;
		$posts_return = $this->preProcess();

		$out = $this->displayFormAddCertificate(isset($posts_return['add_certificate']) && $posts_return['add_certificate']);

		if (is_array(self::$certificates))
			foreach (self::$certificates as $certif)
				$bool_display_certificats = (isset($certif['tsID']) && $certif['tsID'] != '') ? true : $bool_display_certificats;

		if ($bool_display_certificats)
			$out .= $this->displayFormCertificatesList();

		if (isset($posts_return['edit_certificate']))
			$out .= $this->displayFormEditCertificate($posts_return['edit_certificate']).'<br />';

		if (isset($posts_return['options_certificate']))
			$out .= $this->displayFormOptionsCertificate($posts_return['options_certificate']).'<br />';


		$out .= $this->displayInfoCronTask();

		//Context::getContext()->smarty->registerFilter('output', array($this, 'smarty_outputfilter_zoo'));

		return $out;
	}

	/*function smarty_outputfilter_zoo($output, Smarty_Internal_Template $template)
	{
		return str_replace('[gallery:id]',
			'test custom tag displayed', $output);
	}*/

	private function getLinkConfigureModule()
	{
		$params = array(
			'configure'   => urlencode(self::$translation_object->name),
			'tab_module'  => self::$translation_object->tab,
			'module_name' => urlencode(self::$translation_object->name),
			// 'token'       => Tools::getAdminTokenLite('AdminModules')
		);
		
		$link = Context::getContext()->link->getAdminLink('AdminModules', true);
		
		foreach ($params as $p => $v)
		{
			$link.= '&'.$p.'='.$v;
		}
		
		return $link;
	}

	private function displayFormRegistrationLink($link = false)
	{
		TSCommon::$smarty->assign(array(
			'form_action' => $this->makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab),
			'shopsw' => TSCommon::$shopsw,
			'etcid' => TSCommon::$et_cid,
			'etlid' => TSCommon::$et_lid,
			'languages' => self::$available_languages,
			'lang_default' => TSCommon::$default_lang,
			'link' => $link
		));

		return TSCommon::$smarty->fetch(dirname(__FILE__).'/../views/templates/admin/'.self::getTemplateByVersion('form'));
	}

	private function displayFormAddCertificate()
	{
		TSCommon::$smarty->assign(array(
			'form_action' => $this->makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab),
			'languages' => TSCommon::$available_languages_for_adding,
			'lang_default' => Tools::getValue('lang'),
		));
		
		return TSCommon::$smarty->fetch(dirname(__FILE__).'/../views/templates/admin/'.self::getTemplateByVersion('add_certificate'));
	}

	private function displayFormCertificatesList()
	{
		TSCommon::$smarty->assign(array(
			'form_action' => $this->makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab),
			'certificates' => TSCommon::$certificates,
			'configure_link' => $this->getLinkConfigureModule()
		));
		
		return TSCommon::$smarty->fetch(dirname(__FILE__).'/../views/templates/admin/'.self::getTemplateByVersion('certificate_list'));
	}

	/**
	 * Check if a module is payment module.
	 *
	 * Method instanciate a $module by its name,
	 * Module::getInstanceByName() rather than Module::getInstanceById()
	 * is used for cache improvement and avoid an sql request.
	 *
	 * Method test if PaymentMethod::getCurrency() is a method from the module.
	 *
	 * @see Module::getInstanceByName() in classes/Module.php
	 * @param string $module name of the module
	 */
	private static function isPaymentModule($module)
	{
		$return = false;
		$module = Module::getInstanceByName($module);

		if (method_exists($module, 'getCurrency'))
			$return = clone $module;

		unset($module);

		return $return;
	}

	private function displayFormEditCertificate($lang)
	{
		$certificate = TSCommon::$certificates[$lang];

		$payment_module_collection = array();
		$installed_modules = Module::getModulesInstalled();

		foreach ($installed_modules as $value)
			if (TSCommon::isPaymentModule($value['name']))
				$payment_module_collection[$value['id_module']] = $value;

		TSCommon::$smarty->assign(array(
			'site_uri' => $this->site_url,
			'form_action' => $this->makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab),
			'lang' => $lang,
			'certificate' => $certificate,
			'payment_types' => TSCommon::$payments_type,
			'payment_collection' => $payment_module_collection,
			'payment_types_json' => Tools::jsonEncode(TSCommon::$payments_type),
			'payment_collection_json' => Tools::jsonEncode($payment_module_collection)
		));
		
		return TSCommon::$smarty->fetch(dirname(__FILE__).'/../views/templates/admin/'.self::getTemplateByVersion('edit_certificate'));
	}

	private function displayFormOptionsCertificate($lang)
	{

		$certificate = TSCommon::$certificates[$lang];

		TSCommon::$smarty->assign(array(
			'form_action' => $this->makeFormAction(strip_tags($_SERVER['REQUEST_URI']), $this->id_tab),
			'lang' => $lang,
			'certificate' => $certificate,
			'available_seal_variants' => $this->available_seal_variants,
			'yoffset' => $certificate['yoffset'],
			'jscode' => $certificate['jscode'],
			'order_states' => OrderState::getOrderStates(Context::getContext()->language->id),
			'cron_link' => self::getHttpHost(true, true)._MODULE_DIR_.self::$module_name.'/cron.php?secure_key='.Configuration::get(TSCommon::PREFIX_TABLE.'SECURE_KEY')
		));
		
		return TSCommon::$smarty->fetch(dirname(__FILE__).'/../views/templates/admin/'.self::getTemplateByVersion('options_certificate'));
	}

	private function displayInfoCronTask()
	{
		TSCommon::$smarty->assign(array(
			'cron_path' => $this->getCronFilePath(),
		));
		
		return TSCommon::$smarty->fetch(dirname(__FILE__).'/../views/templates/admin/'.self::getTemplateByVersion('cron_list'));
	}

	public function hookRightColumn($params)
	{
		$iso_lang = $iso_cert = Tools::strtoupper(Language::getIsoById($params['cookie']->id_lang));

		$tab_id = false;
		if (isset(TSCommon::$certificates[Tools::strtoupper($iso_cert)]['tsID']))
			$tab_id = TSCommon::$certificates[Tools::strtoupper($iso_cert)]['tsID'];

		if (!$tab_id)
			return false;

		if (isset(TSCommon::$certificates[$iso_cert]['tsID']))
		{
			TSCommon::$smarty->assign('trusted_shops_id', TSCommon::$certificates[$iso_cert]['tsID']);
			TSCommon::$smarty->assign('variant', isset(TSCommon::$certificates[$iso_cert]['variant']) ? (in_array(TSCommon::$certificates[$iso_cert]['variant'], array_keys($this->available_seal_variants)) ? TSCommon::$certificates[$iso_cert]['variant'] : 'default') : 'default');
			TSCommon::$smarty->assign('yoffset', TSCommon::$certificates[$iso_cert]['yoffset']);
			TSCommon::$smarty->assign('jscode', TSCommon::$certificates[$iso_cert]['jscode']);
			TSCommon::$smarty->assign('onlineshop_name', ConfigurationCore::get('PS_SHOP_NAME'));

			$url = str_replace(array('#shop_id#', '#shop_name#'), array(
					TSCommon::$certificates[$iso_cert]['tsID'],
					urlencode(str_replace('_', '-', ConfigurationCore::get('PS_SHOP_NAME')))
				),
				TSCommon::$certificate_link[$iso_lang]);
			TSCommon::$smarty->assign('trusted_shops_url', $url);

			if (isset(TSCommon::$certificates[$iso_cert]))
			{
				$certificate = TSCommon::$certificates[$iso_cert];

				if (isset($certificate['tsID']) && ($certificate['typeEnum'] == 'CLASSIC' || $certificate['typeEnum'] == 'UNKNOWN' || ($certificate['typeEnum'] == 'EXCELLENCE' && $certificate['user'] != '' && $certificate['password'] != '')))
					return TrustedShops::displaySeal();
			}
		}

		return '';
	}

	public function hookFooter($params)
	{
		return $this->hookRightColumn($params);
	}

	/**
	 * For Excellence certificate display Buyer protection products.
	 * An error message if the certificate is not totally filled
	 *
	 * @param array $params
	 * @return string tpl content
	 */
    public function hookDisplayAfterShoppingCartBlock($params)
    {
        return $this->hookPaymentTop($params);
    }

	public function hookPaymentTop($params)
	{
		$lang = Tools::strtoupper(Language::getIsoById($params['cookie']->id_lang));


		if (!isset(TSCommon::$certificates[$lang]) ||
			!isset(TSCommon::$certificates[$lang]['typeEnum'])
		)
			return '';

		// This hook is available only with EXCELLENCE certificate.
		if (TSCommon::$certificates[$lang]['typeEnum'] == 'CLASSIC' || TSCommon::$certificates[$lang]['typeEnum'] == 'UNKNOWN' ||
			(TSCommon::$certificates[$lang]['stateEnum'] !== 'INTEGRATION' &&
				TSCommon::$certificates[$lang]['stateEnum'] !== 'PRODUCTION' &&
				TSCommon::$certificates[$lang]['stateEnum'] !== 'TEST'))
			return '';

		// If login parameters missing for the certificate an error occurred
		if ((TSCommon::$certificates[$lang]['user'] == '' || TSCommon::$certificates[$lang]['password'] == '') && TSCommon::$certificates[$lang]['typeEnum'] == 'EXCELLENCE')
			return '';


		// Set default value for an unexisting item
		TSCommon::$smarty->assign('item_exist', false);
		if (array_key_exists($lang, self::$available_languages))
		{
			$currency = new Currency((int)$params['cookie']->id_currency);

			$query = '
			SELECT
				*
			FROM `'._DB_PREFIX_.TSCommon::DB_ITEMS.'`
			WHERE ts_id ="'.pSQL(TSCommon::$certificates[$lang]['tsID']).'"
			AND `protected_amount_decimal` >= "'.(int)$params['cart']->getOrderTotal(true, Cart::BOTH).'"
			AND `currency` = "'.pSQL($currency->iso_code).'"
			ORDER BY `protected_amount_decimal` ASC';

			// If amout is bigger, get the max one requested by TS
			if (!$item = Db::getInstance()->getRow($query))
			{
				$query = '
				SELECT
					*,
					MAX(protected_amount_decimal)
				FROM `'._DB_PREFIX_.TSCommon::DB_ITEMS.'`
				WHERE ts_id ="'.pSQL(TSCommon::$certificates[$lang]['tsID']).'"
				AND `currency` = "'.pSQL($currency->iso_code).'"';

				$item = Db::getInstance()->getRow($query);
			}

			if ($item && count($item))
				TSCommon::$smarty->assign(array(
						'item_exist' => true,
						'shop_id' => TSCommon::$certificates[$lang]['tsID'],
						'buyer_protection_item' => $item,
						'currency', $currency)
				);
		}

		/**
		 * We need to clean the cart of other TSCommon product, in case the customer wants to change the currency
		 * The price of a TSCommon product is different for each currency, the conversion_rate won't change anything
		 */

		$query = 'SELECT id_product FROM `'._DB_PREFIX_.TSCommon::DB_ITEMS.'`';

		$product = Db::getInstance()->ExecuteS($query);

		$product_protection = array();

		foreach ($product as $item)
			$product_protection[] = $item['id_product'];

		// TODO : REWRITE this part because it's completely not a good way (Control  + R, add Product dynamically)
		foreach ($params['cart']->getProducts() as $item)
			if (in_array($item['id_product'], $product_protection))
				$params['cart']->deleteProduct($item['id_product']);

		return $this->display(TSCommon::$module_name, '/views/templates/front/'.self::getTemplateByVersion('display_products'));
	}

	/**
	 * This prepare values to create the Trusted Shops web service
	 * for Excellence certificate.
	 *
	 * @see TSCommon::requestForProtectionV2() method
	 * @param array $params
	 * @param string $lang
	 * @return string empty if no error occurred or no item was set.
	 */
	private function orderConfirmationExcellence($params, $lang)
	{
		$currency = new Currency((int)$params['objOrder']->id_currency);
		$order_products = $params['objOrder']->getProducts();
		$order_item_ids = array();

		foreach ($order_products as $product)
			$order_item_ids[] = (int)$product['product_id'];

		$query = '
		SELECT *
		FROM `'._DB_PREFIX_.TSCommon::DB_ITEMS.'`
		WHERE `id_product` IN ('.implode(',', $order_item_ids).')
		AND `ts_id` ="'.pSQL(TSCommon::$certificates[$lang]['tsID']).'"
		AND `currency` = "'.pSQL($currency->iso_code).'"';

		if (!($item = Db::getInstance()->getRow($query)))
			return '';

		$customer = new Customer($params['objOrder']->id_customer);
		$payment_module = Module::getInstanceByName($params['objOrder']->module);
		$arr_params = array();

		$arr_params['paymentType'] = '';
		foreach (TSCommon::$certificates[$lang]['payment_type'] as $payment_type => $id_modules)
			if (in_array($payment_module->id, $id_modules))
			{
				$arr_params['paymentType'] = (string)$payment_type;
				break;
			}

		if ($arr_params['paymentType'] == '')
			$arr_params['paymentType'] = 'OTHER';

		$arr_params['tsID'] = TSCommon::$certificates[$lang]['tsID'];
		$arr_params['tsProductID'] = $item['ts_product_id'];
		$arr_params['amount'] = $params['total_to_pay'];
		$arr_params['currency'] = $currency->iso_code;
		$arr_params['buyerEmail'] = $customer->email;
		$arr_params['shopCustomerID'] = $customer->id;
		$arr_params['shopOrderID'] = Order::getUniqReferenceOf($params['objOrder']->id);
		$arr_params['orderDate'] = date('Y-m-d\TH:i:s', strtotime($params['objOrder']->date_add));
		$arr_params['shopSystemVersion'] = 'Prestashop '._PS_VERSION_;
		$arr_params['wsUser'] = TSCommon::$certificates[$lang]['user'];
		$arr_params['wsPassword'] = TSCommon::$certificates[$lang]['password'];

		$this->requestForProtectionV2($arr_params);

		if (!empty($this->errors))
			return '<p style="color:red">'.implode('<br />', $this->errors).'</p>';

		return $this->display(TSCommon::$module_name, '/views/templates/front/'.self::getTemplateByVersion('order-confirmation-tsbp-excellence'));
	}

	/**
	 * Trusted Shops Buyer Protection is integrated at the end of the checkout
	 * as a form on the order confirmation page.
	 * At the moment the customer clicks the registration button,
	 * the order data is processed to Trusted Shops.
	 * The customer confirms the Buyer Protection on the Trusted Shops site.
	 * The guarantee is then booked and the customer receives an email by Trusted Shops.
	 *
	 * @param array $params
	 * @param string $lang
	 * @return string tpl content
	 */
	private function orderConfirmationClassic($params, $lang)
	{
		$payment_type = 'OTHER';

		// Payment type for native module list
		/*
		DIRECT_DEBIT                               Lastschrift / Bankeinzug
		CREDIT_CARD                                Kreditkarte
		INVOICE                                    Rechnung
		CASH_ON_DELIVERY                           Nachnahme
		PREPAYMENT                                 Vorauskasse / Überweisung
		CHEQUE                                     Verrechnungsscheck
		PAYBOX                                     Paybox Integrationshandbuch Shopbetreiber
		PAYPAL                                     PayPal
		AMAZON_PAYMENTS                            Amazon Payments
		CASH_ON_PICKUP                             Zahlung bei Abholung
		FINANCING                                 Finanzierung
		LEASING                                   Leasing
		T_PAY                                     T-Pay
		CLICKANDBUY                               Click&Buy
		GIROPAY                                   Giropay
		GOOGLE_CHECKOUT                           Checkout
		SHOP_CARD                                 Zahlungskarte des Online Shops
		DIRECT_E_BANKING                          SOFORT Überweisung
		MONEYBOOKERS                              moneybookers.com
		DOTPAY                                    Dotpay
		PLATNOSCI                                 Płatności
		PRZELEWY24                                Przelewy24
		OTHER                                     Andere Zahlungsart
		*/

		$payment_type_list = array(
			'bankwire' => 'PREPAYMENT',
			'authorizeaim' => 'CREDIT_CARD',
			'buyster' => 'CREDIT_CARD',
			'cashondelivery' => 'CASH_ON_DELIVERY',
			'dibs' => 'CREDIT_CARD',
			'cheque' => 'CHEQUE',
			'gcheckout' => 'GOOGLE_CHECKOUT',
			'hipay' => 'CREDIT_CARD',
			'moneybookers' => 'MONEYBOOKERS',
			'kwixo' => 'CREDIT_CARD',
			'paypal' => 'CREDIT_CARD',
			'paysafecard' => 'CREDIT_CARD',
			'wexpay' => 'CREDIT_CARD',
			'banktransfert' => 'DIRECT DEBIT'
		);

		if (array_key_exists($params['objOrder']->module, $payment_type_list))
			$payment_type = $payment_type_list[$params['objOrder']->module];

		$customer = new Customer($params['objOrder']->id_customer);
		$currency = new Currency((int)$params['objOrder']->id_currency);

		$arr_params = array(
			'amount' => $params['total_to_pay'],
			'buyer_email' => $customer->email,
			'charset' => 'UTF-8',
			'currency' => $currency->iso_code,
			'customer_id' => $customer->id,
			'order_id' => $params['objOrder']->id,
			'order_reference' => Order::getUniqReferenceOf($params['objOrder']->id),			
			'payment_type' => $payment_type,
			'shop_id' => TSCommon::$certificates[$lang]['tsID']
		);

		TSCommon::$smarty->assign(
			array(
				'tax_label' => 'TTC',
				'buyer_protection' => $arr_params
			)
		);

		return $this->display(TSCommon::$module_name, '/views/templates/front/'.self::getTemplateByVersion('order-confirmation-tsbp-classic'));
	}


	/**
	 * Order confirmation displaying and actions depend on the certificate type.
	 *
	 * @uses TSCommon::orderConfirmationClassic() for Classic certificate
	 * @uses TSCommon::orderConfirmationExcellence for Excellence certificate.
	 * @param array $params
	 * @return string depend on which certificate is used.
	 */
	public function hookOrderConfirmation($params)
	{
		$out = '';

		$lang = Tools::strtoupper(Language::getIsoById($params['objOrder']->id_lang));

		// Security check to avoid any useless warning, a certficate tab will always exist for a configured language
		if (!isset(TSCommon::$certificates[$lang]) || !count(TSCommon::$certificates[$lang]))
			$out .= '';

		if (((isset(TSCommon::$certificates[$lang]['typeEnum'])) && (TSCommon::$certificates[$lang]['typeEnum'] == 'EXCELLENCE') &&
			TSCommon::$certificates[$lang]['user'] != '' &&
			TSCommon::$certificates[$lang]['password'] != ''))
		{
            if (TSCommon::$certificates[$lang]['display_rating_oc'] == 1)
            {
                self::$smarty->assign(array(
                        'ratenow_url' => $this->getRatenowUrl($lang, (int)$params['objOrder']->id),
                        'ratelater_url' => $this->getRatelaterUrl($lang, (int)$params['objOrder']->id),
                        'img_rateshopnow' => _MODULE_DIR_.'trustedshops/img/'.Tools::strtoupper($lang).'/rate_now_'.Tools::strtolower($lang).'_190.png',
                        'img_rateshoplater' => _MODULE_DIR_.'trustedshops/img/'.Tools::strtoupper($lang).'/rate_later_'.Tools::strtolower($lang).'_190.png',
                    )
                );
                $out .= $this->display(self::$module_name, '/views/templates/front/'.self::getTemplateByVersion('order-confirmation'));
            }
			$out .= $this->orderConfirmationExcellence($params, $lang);
		}
		else if ((isset(TSCommon::$certificates[$lang]['typeEnum'])) &&
			(TSCommon::$certificates[$lang]['typeEnum'] == 'CLASSIC' || TSCommon::$certificates[$lang]['typeEnum'] == 'UNKNOWN') &&
			(TSCommon::$certificates[$lang]['stateEnum'] == 'INTEGRATION' ||
				TSCommon::$certificates[$lang]['stateEnum'] == 'PRODUCTION' ||
				TSCommon::$certificates[$lang]['stateEnum'] == 'TEST'))
		{
            if (TSCommon::$certificates[$lang]['display_rating_oc'] == 1)
            {
                self::$smarty->assign(array(
                        'ratenow_url' => $this->getRatenowUrl($lang, (int)$params['objOrder']->id),
                        'ratelater_url' => $this->getRatelaterUrl($lang, (int)$params['objOrder']->id),
                        'img_rateshopnow' => _MODULE_DIR_.'trustedshops/img/'.Tools::strtoupper($lang).'/rate_now_'.Tools::strtolower($lang).'_190.png',
                        'img_rateshoplater' => _MODULE_DIR_.'trustedshops/img/'.Tools::strtoupper($lang).'/rate_later_'.Tools::strtolower($lang).'_190.png',
                    )
                );
                $out .= $this->display(self::$module_name, '/views/templates/front/'.self::getTemplateByVersion('order-confirmation'));
            }
			$out .= $this->orderConfirmationClassic($params, $lang);
		}
		return $out;
	}

	public static function getHttpHost($http = false, $entities = false)
	{
		if (method_exists('Tools', 'getHttpHost'))
			return call_user_func(array('Tools', 'getHttpHost'), array($http, $entities));

		$host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);

		if ($entities)
			$host = htmlspecialchars($host, ENT_COMPAT, 'UTF-8');
		if ($http)
			$host = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').$host;

		return $host;
	}

	private function getLastOrderId($id_customer)
	{
		$query = '
		SELECT `id_order`
		FROM `'._DB_PREFIX_.'orders`
		WHERE `id_customer` = '.(int)$id_customer.'
		ORDER BY `date_add` DESC';

		return (int)Db::getInstance()->getValue($query);
	}

	private function getAllowedIsobyId($id_lang)
	{
		$lang = Language::getIsoById($id_lang);
		$lang = in_array($lang, self::$available_languages) ? $lang : 'en';

		return $lang;
	}

	/**
	 * Reset all data for new submited tsid even if it's the same one
	 * @param $id_lang
	 */
	public function resetTSID($id_lang)
	{
		Configuration::deleteByName(self::PREFIX_CONF_NAME.(int)$id_lang, '');
		Configuration::deleteByName(self::PREFIX_ACTIF_CONF_NAME.(int)$id_lang, '');
	}

	private function validateTrustedShopId($ts_id, $iso_lang)
	{
		$result = Tools::strtoupper(TrustedShopsSoapApi::validate(self::PARTNER_PACKAGE, $ts_id));

		if ($result != TrustedShopsSoapApi::RT_OK)
			switch ($result)
			{
				case TrustedShopsSoapApi::RT_INVALID_TSID:
					$this->error_soap_call = $this->l('Invalid Trusted Shops ID').' ['.Language::getIsoById($iso_lang).']. '.$this->l('Please register').' <a href="'.$this->getApplyUrl().'">'.$this->l('here').'</a> '.$this->l('or contact service@trustedshops.co.uk.');
					break;
				case TrustedShopsSoapApi::RT_NOT_REGISTERED:
					$this->error_soap_call = $this->l('Customer Rating has not yet been activated for this Trusted Shops ID').' ['.Language::getIsoById($iso_lang).']. '.$this->l('Please register').' <a href="'.$this->getApplyUrl().'">'.$this->l('here').'</a> '.$this->l('or contact service@trustedshops.co.uk.');
					break;
				default:
					$this->error_soap_call = $this->l('An error has occurred');
			}

		return $result;
	}

	private function hasSOAPCallError()
	{
		return (bool)!empty($this->error_soap_call);
	}

	public function getApplyUrl()
	{
		$context = Context::getContext();

		$lang = $this->getAllowedIsobyId($context->language->id);
		
		$params = array(
			'partnerPackage' => self::PARTNER_PACKAGE,
			'shopsw'         => self::SHOP_SW,
			'website'        => urlencode(_PS_BASE_URL_.__PS_BASE_URI__),
			'firstName'      => urlencode($context->cookie->firstname),
			'lastName'       => urlencode($context->cookie->lastname),
			'email'          => urlencode(Configuration::get('PS_SHOP_EMAIL')),
			'language'       => Tools::strtoupper(Language::getIsoById((int)$context->language->id)),
			'ratingProduct'  => 'RATING_PRO'
		);
		
		$link = $this->apply_url_base[$lang];
		
		$i = 0;
		
		foreach ($params as $p => $v)
		{
			$link.= ($i == 0 ? '?' : '&').$p.'='.$v;
			
			$i++;
		}
		
		$link.= $this->apply_url_tracker[$lang];

		return $link;
	}

	public function getRatenowUrl($iso_cert, $id_order = '')
	{
		$context = Context::getContext();
		
		$buyer_email = '';

		if ($context->customer->isLogged())
		{
			if (empty($id_order) && !empty($context->cookie->id_customer))
				$id_order = (int)$this->getLastOrderId((int)$context->cookie->id_customer);

			$buyer_email = $context->cookie->email;
		}

		return $this->getRatingUrlWithBuyerEmail((int)$context->cookie->id_lang, $iso_cert, (int)$id_order, $buyer_email);
	}

	public function getRatelaterUrl($iso_cert, $id_order = '')
	{
		$buyer_email = '';

		if (Context::getContext()->customer->isLogged())
		{
			if (empty($id_order) && !empty(Context::getContext()->cookie->id_customer))
				$id_order = (int)$this->getLastOrderId((int)Context::getContext()->cookie->id_customer);

			$buyer_email = Context::getContext()->cookie->email;
		}
		
		$params = array(
			'shop_id'     => TSCommon::$certificates[$iso_cert]['tsID'],
			'buyerEmail'  => urlencode(base64_encode($buyer_email)),
			'shopOrderID' => urlencode(base64_encode((int)$id_order)),
			'orderDate'   => urlencode(base64_encode(date('Y-m-d H:i:s'))),
			'days'        => 10
		);
		
		$link = 'http://www.trustedshops.com/reviews/rateshoplater.php';
		
		$i = 0;
		
		foreach ($params as $p => $v)
		{
			$link.= ($i == 0 ? '?' : '&').$p.'='.$v;
			
			$i++;
		}

		return $link;
	}

	public function getRatingUrlWithBuyerEmail($id_lang, $iso_cert, $id_order = '', $buyer_email = '')
	{
		$language = Tools::strtoupper(Language::getIsoById((int)$id_lang));
		
		if (isset($this->rating_url_base[$language]) && isset(TSCommon::$certificates[$iso_cert])) {
			$base_url = $this->rating_url_base[$language].TSCommon::$certificates[$iso_cert]['tsID'].'.html';
	
			if (!empty($buyer_email))
				$base_url .= '&buyerEmail='.urlencode(base64_encode($buyer_email)).($id_order ? '&shopOrderID='.urlencode(base64_encode((int)$id_order)) : '').'&orderDate='.urlencode(base64_encode(date('Y-m-d H:i:s')));
	
			return $base_url;
		}
	}

	public function getTempWidgetFilename($ts_id)
	{
		return self::$module_name.'/cache/'.$ts_id.'.gif';
	}

	public function hookActionOrderStatusPostUpdate($params)
	{
		//'newOrderStatus' => $new_os,'id_order' => (int)$order->id

		$order = new Order((int)$params['id_order']);
		$iso = Language::getIsoById((int)$order->id_lang);
		$iso_upper = Tools::strtoupper($iso);

		if (!isset(TSCommon::$certificates[$iso_upper]['send_separate_mail']) || TSCommon::$certificates[$iso_upper]['send_separate_mail'] != 1 || TSCommon::$certificates[$iso_upper]['send_seperate_mail_order_state'] != (int)$params['newOrderStatus']->id)
			return false;

		RatingAlert::save((int)$order->id, $iso_upper);
	}


	public function getL($key)
	{
		$translations = array(
			'title_part_1' => $this->l('Are you satisfied with'),
			'title_part_2' => $this->l('? Please write a review!')
		);

		return $translations[$key];
	}
}
