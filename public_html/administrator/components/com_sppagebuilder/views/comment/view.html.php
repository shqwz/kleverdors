
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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Helper\ContentHelper;

class SppagebuilderViewComment extends HtmlView
{
	protected $item;
	protected $form;

	public function display($tpl = null)
	{
		$this->item = $this->get('Item');
		$this->form = $this->get('Form');

		if (count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode('<br>',$errors), 500);
		}

		$this->addToolbar();

		return parent::display($tpl);
	}

	protected function addToolbar()
	{
		$input = Factory::getApplication()->input;
		$input->set('hidemainmenu',true);

		$user = Factory::getUser();
		$userId = $user->get('id');
		$isNew = $this->item->id == 0;
		$canDo = ContentHelper::getActions('com_sppagebuilder','component');

		ToolbarHelper::title(Text::_('COM_SPPAGEBUILDER_COMMENT_TITLE_' . ($isNew ? 'ADD' : 'EDIT')), '');

		if ($canDo->get('core.edit'))
		{
			ToolbarHelper::apply('comment.apply','JTOOLBAR_APPLY');
			ToolbarHelper::save('comment.save','JTOOLBAR_SAVE');
		}

		ToolbarHelper::cancel('comment.cancel','JTOOLBAR_CLOSE');
	}
}
	
