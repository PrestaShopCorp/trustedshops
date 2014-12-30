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

abstract class AbsTrustedShops
{
	/**
	 * Saved errors messages.
	 * @var array
	 */
	public $errors = array();

	/**
	 * Saved warning messages.
	 * @var array
	 */
	public $warnings = array();

	/**
	 * Saved confirmations messages.
	 * @var array
	 */
	public $confirmations = array();

	/**
	 * @var string
	 */
	protected static $module_name;

	public $limited_countries = array();

	public static $smarty;

	public $tab_name;

	public $id_tab;

	/**
	 * Set the object which use the translation method for the specific module.
	 * @var Module
	 */
	protected static $translation_object;

	abstract public function install();

	abstract public function uninstall();

	abstract public function getContent();

	public static function setTranslationObject(Module $object)
	{
		self::$translation_object = $object;
	}

	protected function makeFormAction($uri, $id_tab)
	{
		$arr_query_new = array();
		$uri_component = parse_url($uri);
		$arr_query = explode('&', $uri_component['query']);

		foreach ($arr_query as $value)
		{
			$arr = explode('=', $value);
			
			if ($arr[0] != 'certificate_delete' &&
				$arr[0] != 'certificate_edit' &&
				$arr[0] != 'certificate_options'
			)
				$arr_query_new[$arr[0]] = $arr[1];
		}

		$arr_query_new['id_tab'] = $id_tab;

		return str_replace($uri_component['query'], '', $uri).http_build_query($arr_query_new);
	}

	/**
	 * Set a static name for the module.
	 *
	 * @param string $name
	 */
	public function setModuleName($name)
	{
		self::$module_name = $name;
	}

	public function setSmarty($smarty)
	{
		self::$smarty = $smarty;
	}

	/**
	 * Get translation for a given module text
	 *
	 * @param string $string String to translate
	 * @return string Translation
	 */
	public function l($string, $specific = false)
	{
		if ($specific === false)
		{
			$reflection_class = new ReflectionClass(get_class($this));
			$specific = basename($reflection_class->getFileName(), '.php');
		}

		if (self::$translation_object instanceof Module)
			return self::$translation_object->l($string, $specific);
	}

	public function display($file, $template, $cache_id = null, $compile_id = null)
	{
		if (Configuration::get('PS_FORCE_SMARTY_2')) /* Keep a backward compatibility for Smarty v2 */
		{
			$previous_template = self::$smarty->currentTemplate;
			self::$smarty->currentTemplate = Tools::substr(basename($template), 0, -4);
		}

		self::$smarty->assign('module_dir', __PS_BASE_URI__.'modules/'.basename($file, '.php').'/');

		if (($overloaded = self::isTemplateOverloadedStatic(basename($file, '.php'), $template)) === null)
			$result = Tools::displayError('No template found');
		else
		{
			self::$smarty->assign('module_template_dir', ($overloaded ? _THEME_DIR_ : __PS_BASE_URI__).'modules/'.basename($file, '.php').'/');
			$result = self::$smarty->fetch(
				($overloaded ?
					_PS_THEME_DIR_.'modules/'.basename($file, '.php') :
					_PS_MODULE_DIR_.basename($file, '.php')).'/'.$template, $cache_id, $compile_id
			);
		}

		if (Configuration::get('PS_FORCE_SMARTY_2')) /* Keep a backward compatibility for Smarty v2 */
			self::$smarty->currentTemplate = $previous_template;
		return $result;
	}

	/**
	 * Template management (display, overload, cache)
	 * @see Module::isTemplateOverloadedStatic()
	 */
	protected static function isTemplateOverloadedStatic($module_name, $template)
	{
		if (Tools::file_exists_cache(_PS_THEME_DIR_.'modules/'.$module_name.'/'.$template))
			return true;
		else if (Tools::file_exists_cache(_PS_MODULE_DIR_.$module_name.'/'.$template))
			return false;

		return null;
	}
}
