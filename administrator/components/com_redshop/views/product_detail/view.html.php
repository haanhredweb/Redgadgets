<?php
/**
 * @package     RedSHOP.Backend
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2005 - 2013 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');

require_once JPATH_COMPONENT . '/helpers/extra_field.php';
require_once JPATH_COMPONENT . '/helpers/category.php';
require_once JPATH_COMPONENT . '/helpers/shopper.php';
require_once JPATH_COMPONENT_SITE . '/helpers/product.php';

/**
 * Product Detail View
 *
 * @package     RedShop.Component
 * @subpackage  Admin
 *
 * @since       1.0
 */
class Product_DetailViewProduct_Detail extends JView
{
	/**
	 * The request url.
	 *
	 * @var  string
	 */
	public $request_url;

	public $productSerialDetail;

	public $input;

	public $producthelper;

	public $dispatcher;

	public $option;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @see     fetch()
	 * @since   11.1
	 */
	public function display($tpl = null)
	{
		JHtml::_('behavior.tooltip');

		$app = JFactory::getApplication();
		$this->input = $app->input;
		$user = JFactory::getUser();

		JPluginHelper::importPlugin('redshop_product_type');
		$this->dispatcher = JDispatcher::getInstance();

		$redTemplate = new Redtemplate;
		$redhelper = new redhelper;
		$this->producthelper = new producthelper;

		$this->option = $this->input->getString('option', 'com_redshop');
		$db = JFactory::getDBO();
		$dbPrefix = $app->getCfg('dbprefix');
		$lists = array();

		$model = $this->getModel('product_detail');
		$detail = $this->get('data');

		$isNew = ($detail->product_id < 1);

		// Load new product default values
		if ($isNew)
		{
			$detail->append_to_global_seo = '';
			$detail->canonical_url        = '';
		}

		// Fail if checked out not by 'me'
		if ($model->isCheckedOut($user->get('id')))
		{
			$msg = JText::_('COM_REDSHOP_PRODUCT_BEING_EDITED');
			$app->redirect('index.php?option=com_redshop', $msg);
		}

		// Check redproductfinder is installed
		$CheckRedProductFinder = $model->CheckRedProductFinder();
		$this->CheckRedProductFinder = $CheckRedProductFinder;

		// Get association id
		$getAssociation = $model->getAssociation();
		$this->getassociation = $getAssociation;

		// ToDo: Move SQL from here. SQL shouldn't be in view files!
		$sql = "SHOW TABLE STATUS LIKE '" . $dbPrefix . "redshop_product'";
		$db->setQuery($sql);
		$row = $db->loadObject();
		$next_product = $row->Auto_increment;

		/* Get the tag names */
		$tags = $model->Tags();
		$associationtags = array();

		if (isset($getAssociation) && count($getAssociation) > 0)
		{
			$associationtags = $model->AssociationTags($getAssociation->id);
		}

		if (count($tags) > 0)
		{
			$lists['tags'] = JHtml::_('select.genericlist', $tags, 'tag_id[]', 'multiple', 'id', 'tag_name', $associationtags);
		}

		$types = $model->TypeTagList();

		/* Get the Quality Score data */
		$qs = $this->get('QualityScores', 'product_detail');

		// ToDo: Don't echo HTML but use tmpl files.
		/* Create the select list as checkboxes */
		$html = '<div id="select_box">';

		if (count($types) > 0)
		{
			foreach ($types as $typeid => $type)
			{
				$counttags = count($type['tags']);
				$rand = rand();
				/* Add the type */
				$html .= '<div class="select_box_parent" onClick="showBox(' . $rand . ')">' . JText::_('COM_REDSHOP_TYPE_LIST')
					. ' ' . $type['type_name'] . '</div>';
				$html .= '<div id="' . $rand . '" class="select_box_child';
				$html .= '">';

				/* Add the tags */
				if ($counttags > 0)
				{
					foreach ($type['tags'] as $tagid => $tag)
					{
						/* Check if the tag is selected */
						if (in_array($tagid, $associationtags))
						{
							$selected = 'checked="checked"';
						}

						else
						{
							$selected = '';
						}

						$html .= '<table><tr><td colspan="2"><input type="checkbox" class="select_box" ' . $selected
							. ' name="tag_id[]" value="' . $typeid . '.' . $tagid . '" />'
							. JText::_('COM_REDSHOP_TAG_LIST') . ' ' . $tag['tag_name'];
						$html .= '</td></tr>';

						$qs_value = '';

						if (is_array($qs))
						{
							if (array_key_exists($typeid . '.' . $tagid, $qs))
							{
								$qs_value = $qs[$typeid . '.' . $tagid]['quality_score'];
							}
						}

						$html .= '<tr><td><span class="quality_score">' . JText::_('COM_REDSHOP_QUALITY_SCORE')
							. '</span></td><td><input type="text" class="quality_score_input"  name="qs_id[' . $typeid
							. '.' . $tagid . ']" value="' . $qs_value . '" />';
						$html .= '</td></tr>';

						$html .= '<tr ><td colspan="2"><select name="sel_dep' . $typeid . '_' . $tagid
							. '[]" id="sel_dep' . $typeid . '_' . $tagid . '" multiple="multiple" size="10"  >';

						foreach ($types as $sel_typeid => $sel_type)
						{
							if ($typeid == $sel_typeid)
							{
								continue;
							}

							$dependent_tag = $model->getDependenttag($detail->product_id, $typeid, $tagid);

							$html .= '<optgroup label="' . $sel_type['type_name'] . '">';

							foreach ($sel_type['tags'] as $sel_tagid => $sel_tag)
							{
								$selected = in_array($sel_tagid, $dependent_tag) ? "selected" : "";
								$html .= '<option value="' . $sel_tagid . '" ' . $selected . ' >' . $sel_tag['tag_name'] . '</option>';
							}

							$html .= '</optgroup>';
						}

						$html .= '</select>&nbsp;<a href="#" onClick="javascript:add_dependency('
							. $typeid . ',' . $tagid . ',' . $detail->product_id . ');" >'
							. JText::_('COM_REDSHOP_ADD_DEPENDENCY') . '</a></td></tr></table>';
					}
				}

				$html .= '</div>';
			}
		}

		$html .= '</div>';
		$lists['tags'] = $html;

		$templates = $redTemplate->getTemplate("product");

		$manufacturers = $model->getmanufacturers();

		$supplier = $model->getsupplier();

		$product_categories = $this->input->post->get('product_category', array(), 'array');

		if (!empty($product_categories))
		{
			$productcats = $product_categories;
		}
		else
		{
			$productcats = $model->getproductcats();
		}

		$attributes = $model->getattributes();

		$attributesSet = $model->getAttributeSetList();

		$product_category = new product_category;

		// Merging select option in the select box
		$temps = array();
		$temps[0] = new stdClass;
		$temps[0]->template_id = "0";
		$temps[0]->template_name = JText::_('COM_REDSHOP_SELECT');

		if (is_array($templates))
		{
			$templates = array_merge($temps, $templates);
		}

		// Merging select option in the select box
		$supps = array();
		$supps[0] = new stdClass;
		$supps[0]->value = "0";
		$supps[0]->text = JText::_('COM_REDSHOP_SELECT');

		if (is_array($manufacturers))
		{
			$manufacturers = array_merge($supps, $manufacturers);
		}

		// Merging select option in the select box
		$supps = array();
		$supps[0] = new stdClass;
		$supps[0]->value = "0";
		$supps[0]->text = JText::_('COM_REDSHOP_SELECT');

		if (is_array($supplier))
		{
			$supplier = array_merge($supps, $supplier);
		}

		JToolBarHelper::title(JText::_('COM_REDSHOP_PRODUCT_MANAGEMENT_DETAIL'), 'redshop_products48');

		$document = JFactory::getDocument();

		$document->addScriptDeclaration("var WANT_TO_DELETE = '" . JText::_('COM_REDSHOP_DO_WANT_TO_DELETE') . "';");

		/**
		 * Override field.js file.
		 * With this trigger the file can be loaded from a plugin. This can be used
		 * to display different JS generated interface for attributes depending on a product type.
		 * So, product type plugins should be used for this event. Be aware that this file should
		 * be loaded only once.
		 */
		$loadedFromAPlugin = $this->dispatcher->trigger('loadFieldsJSFromPlugin', array($detail));

		if (in_array(1, $loadedFromAPlugin))
		{
			$loadedFromAPlugin = true;
		}
		else
		{
			$loadedFromAPlugin = false;
		}

		if (!$loadedFromAPlugin)
		{
			$document->addScript('components/' . $this->option . '/assets/js/fields.js');
		}

		$document->addScript('components/' . $this->option . '/assets/js/select_sort.js');
		$document->addScript('components/' . $this->option . '/assets/js/json.js');
		$document->addScript('components/' . $this->option . '/assets/js/validation.js');
		$document->addStyleSheet('components/com_redshop/assets/css/search.css');

		if (file_exists(JPATH_SITE . '/components/com_redproductfinder/helpers/redproductfinder.css'))
		{
			$document->addStyleSheet('components/com_redproductfinder/helpers/redproductfinder.css');
		}

		$document->addScript('components/com_redshop/assets/js/search.js');
		$document->addScript('components/com_redshop/assets/js/related.js');

		$uri = JFactory::getURI();

		$layout = $this->input->getString('layout', '');

		if ($layout == 'property_images')
		{
			$this->setLayout('property_images');
		}
		elseif ($layout == 'attribute_color')
		{
			$this->setLayout('attribute_color');
		}
		elseif ($layout == 'productstockroom')
		{
			$this->setLayout('productstockroom');
		}
		else
		{
			$this->setLayout('default');
		}

		$text = $isNew ? JText::_('COM_REDSHOP_NEW') : $detail->product_name . " - " . JText::_('COM_REDSHOP_EDIT');

		JToolBarHelper::title(JText::_('COM_REDSHOP_PRODUCT') . ': <small><small>[ ' . $text . ' ]</small></small>', 'redshop_products48');

		if ($detail->product_id > 0)
		{
			JToolBarHelper::addNew('prices', JText::_('COM_REDSHOP_ADD_PRICE_LBL'));
		}

		JToolBarHelper::apply();
		JToolBarHelper::save();
		JToolBarHelper::save2new();

		if ($isNew)
		{
			JToolBarHelper::cancel();
		}
		else
		{
			$model->checkout($user->get('id'));

			JToolBarHelper::cancel('cancel', JText::_('JTOOLBAR_CLOSE'));
		}

		$model = $this->getModel('product_detail');

		$accessory_product = array();

		if ($detail->product_id)
		{
			$accessory_product = $this->producthelper->getProductAccessory(0, $detail->product_id);
		}

		$lists['accessory_product'] = $accessory_product;

		$navigator_product = array();

		if ($detail->product_id)
		{
			$navigator_product = $this->producthelper->getProductNavigator(0, $detail->product_id);
		}

		$lists['navigator_product'] = $navigator_product;

		$lists['QUANTITY_SELECTBOX_VALUE'] = $detail->quantity_selectbox_value;

		$result = array();

		$lists['product_all'] = JHtml::_('select.genericlist', $result, 'product_all[]',
			'class="inputbox" ondblclick="selectnone(this);" multiple="multiple"  size="15" style="width:200px;" ',
			'value', 'text', 0
		);

		$related_product_data = $model->related_product_data($detail->product_id);

		$relatedProductCssClass   = 'class="inputbox" multiple="multiple"  size="15" style="width:200px;" ';
		$relatedProductCssClass  .= ' onmousewheel="mousewheel_related(this);" ondblclick="selectnone_related(this);" ';
		$lists['related_product'] = JHtml::_('select.genericlist', $related_product_data, 'related_product[]', $relatedProductCssClass, 'value', 'text', 0);

		$lists['product_all_related'] = JHtml::_('select.genericlist', $result, 'product_all_related[]',
			'class="inputbox" ondblclick="selectnone_related(this);" multiple="multiple"  size="15" style="width:200px;" ',
			'value', 'text', 0
		);

		// For preselected.
		if ($detail->product_template == "")
		{
			$default_preselected = PRODUCT_TEMPLATE;
			$detail->product_template = $default_preselected;
		}

		$lists['product_template'] = JHtml::_('select.genericlist', $templates, 'product_template',
			'class="inputbox" size="1" onchange="set_dynamic_field(this.value,\'' . $detail->product_id . '\',\'1,12,17\');"  ',
			'template_id', 'template_name', $detail->product_template
		);

		$product_tax = $model->gettax();
		$temps = array();
		$temps[0] = new stdClass;
		$temps[0]->value = "0";
		$temps[0]->text = JText::_('COM_REDSHOP_SELECT');

		if (is_array($product_tax))
		{
			$product_tax = array_merge($temps, $product_tax);
		}

		$lists['product_tax'] = JHtml::_('select.genericlist', $product_tax, 'product_tax_id',
			'class="inputbox" size="1"  ', 'value', 'text', $detail->product_tax_id
		);

		$categories = $product_category->list_all("product_category[]", 0, $productcats, 10, true, true);
		$lists['categories'] = $categories;
		$detail->first_selected_category_id = isset($productcats[0]) ? $productcats[0] : null;

		$lists['manufacturers'] = JHtml::_('select.genericlist', $manufacturers, 'manufacturer_id',
			'class="inputbox" size="1" ', 'value', 'text', $detail->manufacturer_id
		);

		$lists['supplier'] = JHtml::_('select.genericlist', $supplier, 'supplier_id', 'class="inputbox" size="1" ', 'value', 'text', $detail->supplier_id);
		$lists['published'] = JHtml::_('select.booleanlist', 'published', 'class="inputbox"', $detail->published);
		$lists['product_on_sale'] = JHtml::_('select.booleanlist', 'product_on_sale', 'class="inputbox"', $detail->product_on_sale);
		$lists['copy_attribute'] = JHtml::_('select.booleanlist', 'copy_attribute', 'class="inputbox"', 0);
		$lists['product_special'] = JHtml::_('select.booleanlist', 'product_special', 'class="inputbox"', $detail->product_special);
		$lists['product_download'] = JHtml::_('select.booleanlist', 'product_download', 'class="inputbox"', $detail->product_download);
		$lists['not_for_sale'] = JHtml::_('select.booleanlist', 'not_for_sale', 'class="inputbox"', $detail->not_for_sale);
		$lists['expired'] = JHtml::_('select.booleanlist', 'expired', 'class="inputbox"', $detail->expired);

		// For individual pre-order
		$preorder_data = $redhelper->getPreOrderByList();
		$lists['preorder'] = JHtml::_('select.genericlist', $preorder_data, 'preorder', 'class="inputbox" size="1" ', 'value', 'text', $detail->preorder);

		// Discount calculator
		$lists['use_discount_calc'] = JHtml::_('select.booleanlist', 'use_discount_calc', 'class="inputbox"', $detail->use_discount_calc);

		$selectOption = array();
		$selectOption[] = JHtml::_('select.option', '1', JText::_('COM_REDSHOP_RANGE'));
		$selectOption[] = JHtml::_('select.option', '0', JText::_('COM_REDSHOP_PRICE_PER_PIECE'));
		$lists['use_range'] = JHtml::_('select.genericlist', $selectOption, 'use_range', 'class="inputbox" size="1" ', 'value', 'text', $detail->use_range);
		unset($selectOption);

		// Calculation method
		$selectOption[] = JHtml::_('select.option', '0', JText::_('COM_REDSHOP_SELECT'));
		$selectOption[] = JHtml::_('select.option', 'volume', JText::_('COM_REDSHOP_VOLUME'));
		$selectOption[] = JHtml::_('select.option', 'area', JText::_('COM_REDSHOP_AREA'));
		$selectOption[] = JHtml::_('select.option', 'circumference', JText::_('COM_REDSHOP_CIRCUMFERENCE'));
		$lists['discount_calc_method'] = JHtml::_('select.genericlist', $selectOption, 'discount_calc_method',
			'class="inputbox" size="1" ', 'value', 'text', $detail->discount_calc_method
		);
		unset($selectOption);

		// Calculation UNIT
		$remove_format = JHtml::$formatOptions;

		$selectOption[] = JHtml::_('select.option', 'mm', JText::_('COM_REDSHOP_MILLIMETER'));
		$selectOption[] = JHtml::_('select.option', 'cm', JText::_('COM_REDSHOP_CENTIMETER'));
		$selectOption[] = JHtml::_('select.option', 'm', JText::_('COM_REDSHOP_METER'));
		$lists['discount_calc_unit'] = JHtml::_('select.genericlist', $selectOption, 'discount_calc_unit[]',
			'class="inputbox" size="1" ', 'value', 'text', DEFAULT_VOLUME_UNIT
		);
		$lists['discount_calc_unit'] = str_replace($remove_format['format.indent'], "", $lists['discount_calc_unit']);
		$lists['discount_calc_unit'] = str_replace($remove_format['format.eol'], "", $lists['discount_calc_unit']);
		unset($selectOption);

		$productVatGroup = $model->getVatGroup();
		$temps = array();
		$temps[0] = new stdClass;
		$temps[0]->value = "";
		$temps[0]->text = JText::_('COM_REDSHOP_SELECT');

		if (is_array($productVatGroup))
		{
			$productVatGroup = array_merge($temps, $productVatGroup);
		}

		if (DEFAULT_VAT_GROUP && !$detail->product_tax_group_id)
		{
			$detail->product_tax_group_id = DEFAULT_VAT_GROUP;
		}

		$append_to_global_seo = array();
		$append_to_global_seo[] = JHtml::_('select.option', 'append', JText::_('COM_REDSHOP_APPEND_TO_GLOBAL_SEO'));
		$append_to_global_seo[] = JHtml::_('select.option', 'prepend', JText::_('COM_REDSHOP_PREPEND_TO_GLOBAL_SEO'));
		$append_to_global_seo[] = JHtml::_('select.option', 'replace', JText::_('COM_REDSHOP_REPLACE_TO_GLOBAL_SEO'));
		$lists['append_to_global_seo'] = JHtml::_('select.genericlist', $append_to_global_seo, 'append_to_global_seo',
			'class="inputbox" size="1" ', 'value', 'text', $detail->append_to_global_seo
		);

		$lists['product_tax_group_id'] = JHtml::_('select.genericlist', $productVatGroup, 'product_tax_group_id',
			'class="inputbox" size="1" ', 'value', 'text', $detail->product_tax_group_id
		);
		$prop_oprand = array();
		$prop_oprand[] = JHtml::_('select.option', 'select', JText::_('COM_REDSHOP_SELECT'));
		$prop_oprand[] = JHtml::_('select.option', '+', JText::_('COM_REDSHOP_PLUS'));
		$prop_oprand[] = JHtml::_('select.option', '=', JText::_('COM_REDSHOP_EQUAL'));
		$prop_oprand[] = JHtml::_('select.option', '-', JText::_('COM_REDSHOP_MINUS'));

		$cat_in_sefurl = $model->catin_sefurl($detail->product_id);
		$lists['cat_in_sefurl'] = JHtml::_('select.genericlist', $cat_in_sefurl, 'cat_in_sefurl',
			'class="inputbox" size="1" ', 'value', 'text', $detail->cat_in_sefurl
		);

		$lists['attributes'] = $attributes;

		$temps = array();
		$temps[0] = new stdClass;
		$temps[0]->value = "";
		$temps[0]->text = JText::_('COM_REDSHOP_SELECT');

		if (is_array($attributesSet))
		{
			$attributesSet = array_merge($temps, $attributesSet);
		}

		$lists['attributesSet'] = JHtml::_('select.genericlist', $attributesSet, 'attribute_set_id',
			'class="inputbox" size="1" ', 'value', 'text', $detail->attribute_set_id
		);

		// Product type selection
		$productTypeOptions = array();
		$productTypeOptions[] = JHtml::_('select.option', 'product', JText::_('COM_REDSHOP_PRODUCT'));
		$productTypeOptions[] = JHtml::_('select.option', 'file', JText::_('COM_REDSHOP_FILE'));
		$productTypeOptions[] = JHtml::_('select.option', 'subscription', JText::_('COM_REDSHOP_SUBSCRIPTION'));

		/*
		 * Trigger event which can update list of product types.
		 * Example of a returned value:
		 * return array('value' => 'redDESIGN', 'text' => JText::_('PLG_REDSHOP_PRODUCT_TYPE_REDDESIGN_REDDESIGN_PRODUCT_TYPE'));
		 */
		$productTypePluginOptions = $this->dispatcher->trigger('onListProductTypes');

		foreach ($productTypePluginOptions as $productTypePluginOption)
		{
			$productTypeOptions[] = JHtml::_('select.option', $productTypePluginOption['value'], $productTypePluginOption['text']);
		}

		if ($detail->product_download == 1)
		{
			$detail->product_type = 'file';
		}

		$lists["product_type"] = JHtml::_(
									'select.genericlist',
									$productTypeOptions,
									'product_type',
									'class="inputbox" size="1" ',
									'value',
									'text',
									$detail->product_type
								);

		$accountgroup = $redhelper->getEconomicAccountGroup();
		$op = array();
		$op[] = JHtml::_('select.option', '0', JText::_('COM_REDSHOP_SELECT'));
		$accountgroup = array_merge($op, $accountgroup);

		$lists["accountgroup_id"] = JHtml::_('select.genericlist', $accountgroup, 'accountgroup_id',
			'class="inputbox" size="1" ', 'value', 'text', $detail->accountgroup_id
		);

		// For downloadable products
		$productSerialDetail = $model->getProdcutSerialNumbers();

		$this->model = $model;
		$this->lists = $lists;
		$this->detail = $detail;
		$this->productSerialDetail = $productSerialDetail;
		$this->next_product = $next_product;
		$this->request_url = $uri->toString();

		parent::display($tpl);
	}
}
