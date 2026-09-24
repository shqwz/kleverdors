
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
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$user 		= Factory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn 	= $this->escape($this->state->get('list.direction'));
$canOrder 	= $user->authorise('core.edit.state','com_sppagebuilder');
$saveOrder = ($listOrder == 'a.ordering');


HTMLHelper::_('jquery.framework', false);

?>

<script type="text/javascript">
window.addEventListener("DOMContentLoaded", e => {
    Joomla.orderTable = function() {
        table = document.getElementById('sortTable');
        direction = document.getElementById('directionTable');
        order = table.options[table.selectedIndex].value;
        if (order != '<?php echo $listOrder; ?>') {
            dirn = 'asc';
        } else {
            dirn = direction.options[direction.selectedIndex].value;
        }
        Joomla.tableOrdering(order, dirn, '');
    }
})

</script>

<form action="<?php echo Route::_('index.php?option=com_sppagebuilder&view=comments'); ?>" method="POST" name="adminForm" id="adminForm">
	<?php if (JVERSION < 4 && !empty($this->sidebar)) { ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>

	<div id="j-main-container" class="span10" >
		<?php } else { ?>
			<div id="j-main-container"></div>
		<?php } ?>

		<?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
		<div class="clearfix"></div>
		<?php if (!empty($this->items)) { ?>
			<table class="table table-striped" id="commentList">
				<thead>
					<tr>

						<th width="1%" class="hidden-phone">
							<input class="form-check-input" type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
						</th>

						<th width="1%" class="nowrap center">
							<?php echo HTMLHelper::_('grid.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
						</th>

						<th>
							<?php echo HTMLHelper::_('grid.sort','COM_SPPAGEBUILDER_COMMENT_CONTENT','a.content', $listDirn, $listOrder); ?>
						</th>
						
						<th>
							<?php echo HTMLHelper::_('grid.sort','COM_SPPAGEBUILDER_COMMENT_CREATED_BY','a.created_by', $listDirn,$listOrder); ?>
						</th>
						
						<th>
							<?php echo HTMLHelper::_('grid.sort','COM_SPPAGEBUILDER_COMMENT_CREATED','a.created_on',$listDirn,$listOrder); ?>
						</th>
						
						<th>
							<?php echo HTMLHelper::_('grid.sort','COM_SPPAGEBUILDER_COMMENT_SOURCE','a.source_type',$listDirn,$listOrder); ?>
						</th>

					</tr>
				</thead>

				<tfoot>
					<tr>
						<td colspan="10">
							<?php echo $this->pagination->getListFooter(); ?>
						</td>
					</tr>
				</tfoot>
				<?php if (JVERSION < 4 ) : ?>
				<tbody>
				<?php else :?>
				<tbody <?php if ($saveOrder) :?> data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
				<?php endif; ?>
					<?php foreach($this->items as $i => $item): ?>

						<?php
						$canCheckin	= $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $user->get('id') || $item->checked_out == 0;
						$canChange		= $user->authorise('core.edit.state', 'com_sppagebuilder') && $canCheckin;
						$canEdit		= $user->authorise( 'core.edit', 'com_sppagebuilder' );
						?>
						<?php if(JVERSION < 4) :?>
						<tr>
						<?php else: ?>
						<tr class="row<?php echo $i % 2; ?>" data-draggable-group="1">
						<?php endif; ?>
							

							<td class="center hidden-phone">
								<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
							</td>

							<td class="center">
									<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'comments.', true,'cb');?>
							</td>

							<td>
								<?php if ($item->checked_out) : ?>
									<?php echo HTMLHelper::_('jgrid.checkedout', $i,$item->editor, $item->checked_out_time, 'comments.', $canCheckin); ?>
								<?php endif; ?>

								<?php if ($canEdit) : ?>
									<a class="title" href="<?php echo Route::_('index.php?option=com_sppagebuilder&task=comment.edit&id='. $item->id); ?>">
										<?php echo $this->escape($item->content); ?>
									</a>
								<?php else : ?>
									<?php echo $this->escape($item->content); ?>
								<?php endif; ?>
							</td>

							<td>
								<?php echo Factory::getUser($item->created_by)->get('username', $item->created_by); ?>
							</td>

							<td>
								<?php echo HTMLHelper::_('date', $item->created_on, 'd M, Y'); ?>
							</td>
							
							<td>
								<?php
									if ($item->source_type == 'article') {
										$article_link = SppagebuilderHelperRoute::getArticleRoute($item->item_id);
									}
								?>
								<a href="<?php echo $article_link; ?>" target="_blank">
									<?php echo $item->source_type; ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php } else { ?>
			<div class="alert alert-danger"><?php echo Text::_('COM_SPPAGEBUILDER_NO_RECORD_FOUND'); ?></div>
		<?php } ?>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
	
