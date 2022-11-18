<?php

namespace SheaDawson\Blocks;

use SilverStripe\ORM\ArrayLib;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FormField;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;
use SheaDawson\Blocks\Model;

/**
 * BlockManager.
 *
 * @author Shea Dawson <shea@livesource.co.nz>
 */
class BlockManager extends ViewableData
{

	private static $themes = array();
	/**
	 * Use default ContentBlock class.
	 *
	 * @var bool
	 **/
	private static $use_default_blocks = true;

	/**
	 * Show a block area preview button in CMS
	 *
	 * @var bool
	 **/
	private static $block_area_preview = true;

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Gets an array of all areas defined for blocks.
	 *
	 * @param bool   $keyAsValue
	 *
	 * @return array $areas
	 **/
	public function getAreas($keyAsValue = true)
	{
		$areas = $this->config()->get('areas');

		$areas = $keyAsValue ? ArrayLib::valuekey(array_keys($areas)) : $areas;
		if (count($areas)) {
			foreach ($areas as $k => $v) {
				$areas[$k] = $keyAsValue ? FormField::name_to_label($k) : $v;
			}
		}

		return $areas;
	}

	/**
	 * Gets an array of all areas defined for the current theme.
	 *
	 * @param string $theme
	 * @param bool   $keyAsValue
	 *
	 * @return array $areas
	 **/
	public function getAreasForTheme($theme = null, $keyAsValue = true)
	{
		$theme = $theme ? $theme : $this->getTheme();
		if (!$theme) {
			return false;
		}
		$config = $this->config()->get('themes');
		if (!isset($config[$theme]['areas'])) {
			return false;
		}
		$areas = $config[$theme]['areas'];
		$areas = $keyAsValue ? ArrayLib::valuekey(array_keys($areas)) : $areas;
		if (count($areas)) {
			foreach ($areas as $k => $v) {
				$areas[$k] = $keyAsValue ? FormField::name_to_label($k) : $v;
			}
		}

		return $areas;
	}

	/**
	 * Gets an array of all areas defined that are compatible with pages of type $class.
	 *
	 * @param string $class
	 *
	 * @return array $areas
	 **/
	public function getAreasForPageType($class)
	{
		$areas = $this->getAreasForTheme(false);

		if (!$areas) {
			return false;
		}

		foreach ($areas as $area => $config) {
			if (!is_array($config)) {
				continue;
			}

			if (isset($config['except'])) {
				$except = $config['except'];
				if (is_array($except)
					? in_array($class, $except)
					: $except == $class
				) {
					unset($areas[$area]);
					continue;
				}
			}

			if (isset($config['only'])) {
				$only = $config['only'];
				if (is_array($only)
					? !in_array($class, $only)
					: $only != $class
				) {
					unset($areas[$area]);
					continue;
				}
			}
		}

		if (count($areas)) {
			foreach ($areas as $k => $v) {
				$areas[$k] = _t('Block.BlockAreaName.'.$k, FormField::name_to_label($k));
			}

			return $areas;
		} else {
			return $areas;
		}
	}

	/*
	 * Get the block config for the current theme
	 */
	private function getThemeConfig()
	{
		$theme = $this->getTheme();
		$config = $this->config()->get('themes');

		return $theme && isset($config[$theme]) ? $config[$theme] : null;
	}

	/*
	 * Get the current/active theme or 'default' to support theme-less sites
	 */
	private function getTheme()
	{
		$currentTheme = Config::inst()->get(SSViewer::class, 'theme');

		// check directly on SiteConfig incase ContentController hasn't set
		// the theme yet in ContentController->init()
		if (!$currentTheme && class_exists(SiteConfig::class)) {
			$currentTheme = SiteConfig::current_site_config()->Theme;
		}

		return $currentTheme ? $currentTheme : 'default';
	}

	public function getBlockClasses()
	{
		$classes = ArrayLib::valuekey(ClassInfo::subclassesFor(Block::class));
		array_shift($classes);
		foreach ($classes as $k => $v) {
			$classes[$k] = singleton($k)->singular_name();
		}

//		$config = $this->config()->get('options');
		$config = $this->getThemeConfig();

		if (isset($config['use_default_blocks']) && !$config['use_default_blocks']) {
			unset($classes[Model\ContentBlock::class]);
		} else if (!$config['use_default_blocks']) {
			unset($classes[Model\ContentBlock::class]);
		}

		$disabledArr = Config::inst()->get(self::class, 'disabled_blocks') ? Config::inst()->get(self::class, 'disabled_blocks') : [];
		if (isset($config['disabled_blocks'])) {
			$disabledArr = array_merge($disabledArr, $config['disabled_blocks']);
		}
		if (count($disabledArr)) {
			foreach ($disabledArr as $k => $v) {
				unset($classes[$v]);
			}
		}

		return $classes;
	}

	/*
	 * Usage of BlockSets configurable from yaml
	 */
	public function getUseBlockSets()
	{
//		$config = $this->config()->get('options');
		$config = $this->getThemeConfig();
		return isset($config['use_blocksets']) ? $config['use_blocksets'] : true;
	}

	/*
	 * Exclusion of blocks from page types defined in yaml
	 */
	public function getExcludeFromPageTypes()
	{
//		$config = $this->config()->get('options');
		$config = $this->getThemeConfig();
		return isset($config['exclude_from_page_types']) ? $config['exclude_from_page_types'] : [];
	}

	/*
	 * getWhiteListedPageTypes optionally configured by the developer
	 */
	public function getWhiteListedPageTypes()
	{
//		$config = $this->config()->get('options');
		$config = $this->getThemeConfig();
		return isset($config['pagetype_whitelist']) ? $config['pagetype_whitelist'] : [];
	}

	/*
	 * getBlackListedPageTypes optionally configured by the developer
	 * Includes blacklisted page types defined in the old exclude_from_page_types array
	 */
	public function getBlackListedPageTypes()
	{
//		$config = $this->config()->get('options');
		$config = $this->getThemeConfig();
		$legacy = isset($config['exclude_from_page_types']) ? $config['exclude_from_page_types'] : [];
		$current = isset($config['pagetype_blacklist']) ? $config['pagetype_blacklist'] : [];
		return array_merge($legacy, $current);
	}

	/*
	 * Usage of extra css classes configurable from yaml
	 */
	public function getUseExtraCSSClasses()
	{
//		$config = $this->config()->get('options');
		$config = $this->getThemeConfig();
		return isset($config['use_extra_css_classes']) ? $config['use_extra_css_classes'] : false;
	}

	/*
	 * Prefix for the default CSSClasses
	 */
	public function getPrefixDefaultCSSClasses()
	{
//		$config = $this->config()->get('options');
		$config = $this->getThemeConfig();
		return isset($config['prefix_default_css_classes']) ? $config['prefix_default_css_classes'] : false;
	}
}
