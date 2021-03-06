<?php
/**
 * @author     Daniel Dimitrov <daniel@compojoom.com>
 * @date       02.06.12
 *
 * @copyright  Copyright (C) 2008 - 2012 compojoom.com . All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.viewlegacy');

/**
 * Class JedcheckerViewUploads
 *
 * @since  1.0
 */
class JedcheckerViewUploads extends JViewLegacy
{
	/**
	 * Display method
	 *
	 * @param   string  $tpl  - the template
	 *
	 * @return mixed|void
	 */
	public function display($tpl = null)
	{
		$this->path         = JFactory::getConfig()->get('tmp_path') . '/jed_checker';
		
		$this->setToolbar();
		$this->jsOptions['url'] = JUri::base();
		$this->jsOptions['rules'] = $this->getRules();
		parent::display($tpl);
	}

	/**
	 * Get the component rules
	 *
	 * @return array
	 */
	public function getRules()
	{
		$rules = array();
		$files = JFolder::files(JPATH_COMPONENT_ADMINISTRATOR . '/libraries/rules', '.php$', false, false);
		JLoader::discover('jedcheckerRules', JPATH_COMPONENT_ADMINISTRATOR . '/libraries/rules/');

		foreach ($files as $file)
		{
			$rules[] = substr($file, 0, -4);
		}

		return $rules;
	}

	/**
	 * Creates the toolbar options
	 *
	 * @return void
	 */
	public function setToolbar()
	{
		if ($this->filesExist('unzipped'))
		{
			JToolbarHelper::custom('police.check', 'search', 'search', 'Check', false);
		}

		JToolbarHelper::title('JED checker');
		if ( file_exists($this->path) ) 
		{
			JToolbarHelper::custom('uploads.clear', 'delete', 'delete', 'Clear', false);
		}
		
		if (JFactory::getUser()->authorise('core.admin', 'com_jedchecker'))     
		{	
			JToolbarHelper::preferences('com_jedchecker');
		}
	}

	/**
	 * Checks if folder + files exist in the jed_checker tmp path
	 *
	 * @param   string  $type  - action
	 *
	 * @return bool
	 */
	private function filesExist($type)
	{
		$path = JFactory::getConfig()->get('tmp_path') . '/jed_checker/' . $type;

		if (JFolder::exists($path))
		{
			if (JFolder::folders($path, '.', false) || JFolder::files($path, '.', false))
			{
				return true;
			}
		}
		else
		{
			$local = JFactory::getConfig()->get('tmp_path') . '/jed_checker/local.txt';

			if ($type == 'unzipped' && JFile::exists($local))
			{
				return true;
			}
		}

		return false;
	}
}
