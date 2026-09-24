
<?php
/**
* @package    	Joomla.Administrator
* @subpackage 	com_sppagebuilder
* @author 		JoomShaper support@joomshaper.com
* @copyright 	
* @license     	GNU General Public License version 2 or later; see http://www.gnu.org/licenses/gpl-2.0.html
*/

// No Direct Access
defined ('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

class SppagebuilderViewComments extends HtmlView
{
	protected $items;
	protected $state;
	protected $pagination;
	protected $model;
	public $filterForm, $activeFilters;

	public function display($tpl = null)
	{
		$this->items			= $this->get('Items');
		$this->state			= $this->get('State');
		$this->pagination		= $this->get('Pagination');
		$this->model			= $this->getModel('comments');
		$this->filterForm		= $this->get('FilterForm');
		$this->activeFilters	= $this->get('ActiveFilters');

		if (count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode('<br>',$errors), 500);
		}

		$this->addToolbar();

		return parent::display($tpl);
	}

	protected function addToolbar()
	{
		$state	= $this->get('State');
		$canDo	= ContentHelper::getActions('com_sppagebuilder','component');
		$toolbar = Toolbar::getInstance();

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::editList('comment.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			ToolbarHelper::publish('comments.publish','JTOOLBAR_PUBLISH',true);
			ToolbarHelper::unpublish('comments.unpublish','JTOOLBAR_UNPUBLISH',true);
		}

		if ($state->get('filter.published') === '-2' && $canDo->get('core.delete'))
		{
			$toolbar->delete('comments.delete', 'JTOOLBAR_EMPTY_TRASH')->message(Text::_('JGLOBAL_CONFIRM_DELETE'))->listCheck(true);
		}
		elseif ($canDo->get('core.edit.state'))
		{
			$toolbar->trash('comments.trash')->listCheck(true);
		}

		if ($canDo->get('core.admin'))
		{
			ToolbarHelper::preferences('com_sppagebuilder');
		}

		ToolbarHelper::title(Text::_('COM_SPPAGEBUILDER_COMMENT_TITLE'),'');
	}
}
	
