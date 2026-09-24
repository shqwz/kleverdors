
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
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\AdminModel;

class SppagebuilderModelComment extends AdminModel
{
	protected $text_prefix = 'COM_SPPAGEBUILDER';

	public function __construct($config = [])
    {
        parent::__construct($config);
        $this->setDispatcher(Factory::getApplication()->getDispatcher());
    }

	public function getTable($name = 'Comment', $prefix = 'SppagebuilderTable', $config = array())
	{
		return Table::getInstance($name, $prefix, $config);
	}

	public function getForm($data = array(), $loadData = true)
	{
		$app = Factory::getApplication();
		$form = $this->loadForm('com_sppagebuilder.comment','comment',array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	public function loadFormData()
	{
		$data = Factory::getApplication()
			->getUserState('com_sppagebuilder.edit.comment.data',array());

		if (empty($data))
		{
			$data = $this->getItem();
		}

		return $data;
	}

	protected function canDelete($record)
	{
		if (!empty($record->id))
		{
			if ($record->published != -2)
			{
				return ;
			}

			$user = Factory::getUser();

			return parent::canDelete($record);
		}
	}

	protected function canEditState($record)
	{
		return parent::canEditState($record);
	}

	public function getItem($pk = null)
	{
		return parent::getItem($pk);
	}
}
	
