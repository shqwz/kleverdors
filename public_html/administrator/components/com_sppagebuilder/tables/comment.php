
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

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\Utilities\ArrayHelper;

class SppagebuilderTableComment extends Table
{
	public function __construct(&$db)
	{
		parent::__construct('#__sppagebuilder_comments', 'id', $db);
	}

	public function bind($src, $ignore = array())
	{
		return parent::bind($src, $ignore);
	}

	public function store($updateNulls = false)
	{
		$user = Factory::getUser();
		$app  = Factory::getApplication();
		$date = new Date('now', $app->getCfg('offset'));

		if ($this->id)
		{
			$this->modified = (string)$date;
			$this->modified_by = $user->get('id');
		}

		if (empty($this->created))
		{
			$this->created = (string)$date;
		}

		if (empty($this->created_by))
		{
			$this->created_by = $user->get('id');
		}

		return parent::store($updateNulls);
	}


	public function publish($pks = null, $published = 1, $userId = 0)
	{
		$k = $this->_tbl_key;
		ArrayHelper::toInteger($pks);
		$published = (int) $published;

		if (empty($pks))
		{
			if ($this->$k)
			{
				$pks = array($this->$k);
			}
			else
			{
				$this->setError(Text::_('JLIB_DATABASE_ERROR_NO_ROWS_SELECTED'));
				return false;
			}
        }

		$where = $k . '=' . implode(' OR '. $k . ' = ', $pks);
		$query = $this->_db->getQuery(true)
			->update($this->_db->quoteName($this->_tbl))
			->set($this->_db->quoteName('published') . ' = '. $published)
			->where($where);

		$this->_db->setQuery($query);

		try
		{
			$this->_db->execute();
		}
		catch(\RuntimeException $e)
		{
			$this->setError($e->getMessage());
			return false;
		}

		if (in_array($this->$k, $pks))
		{
			$this->published = $published;
		}

		$this->setError('');

		return true;
	}
}
	
