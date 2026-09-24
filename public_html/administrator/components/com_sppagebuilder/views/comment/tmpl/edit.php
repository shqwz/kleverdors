
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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

$doc = Factory::getDocument();
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
if (JVERSION < 4)
{
	HTMLHelper::_('formbehavior.chosen','select',null,array('disable_search_threshold' => 0));
}
$rowClass = JVERSION < 4 ? 'row-fluid' : 'row';
$colClass = JVERSION < 4 ? 'span' : 'col-lg-';
?>

<form action="<?php echo Route::_('index.php?option=com_sppagebuilder&view=comment&layout=edit&id=' . (int) $this->item->id); ?>" name="adminForm" id="adminForm" method="post" class="form-validate">
	<?php if (JVERSION < 4 && !empty($this->sidebar)) { ?>
    <div id="j-sidebar-container" class="<?php echo $colClass;?>2">
		<?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="<?php echo $colClass;?>10" >
		<?php } else { ?>
            <div id="j-main-container"></div>
		<?php } ?>
	<div class="form-horizontal">
		<div class="<?php echo $rowClass;?>">
			<div class="<?php echo $colClass;?>12">
				<?php echo $this->form->renderFieldset('basic'); ?>
			</div>
		</div>
	</div>

	<input type="hidden" name="task" value="comment.edit" />
	<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>

	
