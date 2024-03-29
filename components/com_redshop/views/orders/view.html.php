<?php
/**
 * @package     RedSHOP.Frontend
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2005 - 2013 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

JLoader::import('joomla.application.component.view');

class ordersVieworders extends JView
{
	public function display($tpl = null)
	{
		$app = JFactory::getApplication();

		$user = JFactory::getUser();

		// Preform security checks
		if ($user->id == 0)
		{
			$app->Redirect('index.php?option=com_redshop&view=login&Itemid=' . JRequest::getInt('Itemid'));
			exit;
		}

		$layout = JRequest::getCmd('layout', 'default');
		$this->setLayout($layout);

		$params        = $app->getParams('com_redshop');
		$prodhelperobj = new producthelper;
		$prodhelperobj->generateBreadcrumb();

		// Request variables
		$limit      = $app->getUserStateFromRequest('com_redshop' . 'limit', 'limit', 10, 'int');
		$limitstart = JRequest::getInt('limitstart', 0, '', 'int');

		$detail           = $this->get('data');
		$this->pagination = $this->get('Pagination');

		$this->detail = $detail;
		$this->params = $params;
		parent::display($tpl);
	}
}
