
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
use Joomla\CMS\MVC\Controller\FormController;

class SppagebuilderControllerComment extends FormController
{
	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	protected function allowAdd($data = array())
	{
		return parent::allowAdd($data);
	}

	protected function allowEdit($data = array(), $key = 'id')
	{
        $id = (int) isset($data[$key]) ? $data[$key] : 0;
		$user = Factory::getUser();

		if (!$id) {
			return parent::allowEdit($data, $key);
		}

		if ($user->authorise('core.edit', 'com_sppagebuilder.comment.' . $id)) {
			return true;
		}

		if ($user->authorise('core.edit.own', 'com_sppagebuilder.comment.' . $id)) {
			$record = $this->getModel()->getItem($id);
			if (empty($record)) {
				return false;
			}
			return $user->id === $record->created_by;
		}
		return false;
	}
}
	
