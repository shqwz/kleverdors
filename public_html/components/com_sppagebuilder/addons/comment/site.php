<?php

/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/rate-limiter.php';
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Component\ComponentHelper;
use JoomShaper\SPPageBuilder\Comment\Services\CommentService;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Date;

//no direct access
defined('_JEXEC') or die('Restricted access');

class SppagebuilderAddonComment extends SppagebuilderAddons
{
	/**
	 * The addon frontend render method.
	 * The returned HTML string will render to the frontend page.
	 *
	 * @return  string  The HTML string.
	 * @since   1.0.0
	 */
	public function render()
	{
		$input = Factory::getApplication()->input;
		$collectionType = $input->get('collection_type', null, 'string');

		$viewType = $input->get('view', null, 'string');

		$articleId = $input->get('id', null, 'int');

		if ($collectionType === 'articles') {
			if (!\class_exists('SppagebuilderHelperArticles')) {
				require_once JPATH_ROOT . '/components/com_sppagebuilder/helpers/articles.php';
			}
			$articleId = Factory::getApplication()->input->get('collection_item_id', null, 'array');
			$articleId = $articleId ? $articleId[0] : null;
			$authorised = \SppagebuilderHelperArticles::checkAuthorised($articleId);
	
			if (!$authorised) {
				return "";
			}
		}

		$commentService = new CommentService();
		if ($viewType == 'page') {
			$articleId = $commentService->getArticleId($articleId);
		}
		$itemId = $input->get('collection_item_id', null, 'array');
		$itemId = $itemId ? $itemId[0] : null;

		$itemId = $itemId ? $itemId : $articleId;

		if (($collectionType != 'articles' && $collectionType != 'normal-source') && empty($itemId)) {
			return "";
		}
		$collectionType = $collectionType ? $collectionType : 'articles';
		$comments = $commentService->getAllComments($itemId, $collectionType);
		$publishedCommentsCount = $commentService->getPublishedCommentsCount($itemId, $collectionType);
		$addonId = $this->addon->id;

		$settings = $this->addon->settings;
		$class = (isset($settings->class) && $settings->class) ? ' ' . $settings->class : '';

		$commentTitleText = (isset($settings->title_text) && $settings->title_text) ? $settings->title_text : 'Comments';
		$commentCountEnabled = (isset($settings->comment_count) && $settings->comment_count) ? $settings->comment_count : 0;
		$commentCountPosition = (isset($settings->comment_count_position) && $settings->comment_count_position) ? $settings->comment_count_position : 'left';
		$labelText = (isset($settings->label_text) && $settings->label_text) ? $settings->label_text : 'Leave a comment';
		$isLabelEnabled = (isset($settings->enable_label) && $settings->enable_label) ? $settings->enable_label : 0;
		$placeHolderText = (isset($settings->comment_field_placeholder) && $settings->comment_field_placeholder) ? $settings->comment_field_placeholder : 'Share your thoughts...';
		$enableAnonymousComment = (isset($settings->enable_anonymous_comment) && $settings->enable_anonymous_comment) ? $settings->enable_anonymous_comment : 0;

		$likesIcon = (isset($settings->likes_icon) && $settings->likes_icon) ? $settings->likes_icon : 'fa fa-heart';
		$replyIcon = (isset($settings->reply_icon) && $settings->reply_icon) ? $settings->reply_icon : 'fa fa-reply';

		$postBtnText = (isset($settings->post_button_text) && $settings->post_button_text) ? $settings->post_button_text : 'Leave a comment';
		$postBtnAriaLabel = (isset($settings->post_button_aria_label) && $settings->post_button_aria_label) ? $settings->post_button_aria_label : '';
		$btnClass = 'sppb-btn ';
		$btnClass .= (isset($settings->post_button_type) && $settings->post_button_type) ? ' sppb-btn-' . $settings->post_button_type : '';
		$btnClass .= (isset($settings->post_button_block) && $settings->post_button_block) ? ' ' . $settings->post_button_block : '';
		$btnClass .= (isset($settings->post_button_shape) && $settings->post_button_shape) ? ' sppb-btn-' . $settings->post_button_shape : ' sppb-btn-rounded';
		$btnClass .= (isset($settings->post_button_appearance) && $settings->post_button_appearance) ? ' sppb-btn-' . $settings->post_button_appearance : '';
		$btnClass .= (isset($settings->post_button_size) && $settings->post_button_size) ? ' sppb-btn-' . $settings->post_button_size : ' sppb-btn-md';
		$postBtnIcon = (isset($settings->post_button_icon) && $settings->post_button_icon) ? $settings->post_button_icon : '';
		$postBtnIconPosition = (isset($settings->post_button_icon_position) && $settings->post_button_icon_position) ? $settings->post_button_icon_position : 'left';

		$ellipsisBtnClass = 'sppb-btn ';
		$ellipsisBtnClass .= (isset($settings->ellipsis_button_style) && $settings->ellipsis_button_style) ? ' sppb-btn-' . $settings->ellipsis_button_style : 'sppb-btn-custom';
		$ellipsisBtnClass .= (isset($settings->ellipsis_shape) && $settings->ellipsis_shape) ? ' sppb-btn-' . $settings->ellipsis_shape : ' sppb-btn-rounded';
		$ellipsisBtnClass .= (isset($settings->ellipsis_appearance) && $settings->ellipsis_appearance) ? ' sppb-btn-' . $settings->ellipsis_appearance : '';
		$ellipsisBtnClass .= (isset($settings->ellipsis_size) && $settings->ellipsis_size) ? ' sppb-btn-' . $settings->ellipsis_size : ' sppb-btn-md';
		$ellipsisBtnIcon = (isset($settings->ellipsis_icon) && $settings->ellipsis_icon) ? $settings->ellipsis_icon : 'fa fa-ellipsis-v';

		$currentUserId = Factory::getUser()->id;


		$output = '';
		$output .= '<div class="sppb-addon sppb-addon-comment ' . $class . '">';

		$output .= '<div class="sppb-comment-title-wrapper">';
		if($commentCountEnabled && $commentCountPosition === 'left')
		{
			$output .= '<span class="sppb-comment-count">' . '(' . $publishedCommentsCount . ')' . '</span>';
		}
		$output .= '<span class="sppb-comment-title-text">' . $commentTitleText . '</span>';
		if($commentCountEnabled && $commentCountPosition === 'right')
		{
			$output .= '<span class="sppb-comment-count">' . '(' . $publishedCommentsCount . ')' . '</span>';
		}
		$output .= '</div>';

		if($isLabelEnabled)
		{
			$output .= '<div class="sppb-comment-label-wrapper">';
			$output .= '<span class="sppb-comment-label-text">' . $labelText . '</span>';
			$output .= '</div>';
		}

		$output .= '<form class="sppb-comment-form">';
		$output .= '<input hidden name="addon-id" value="' . $addonId . '">';
		$output .= '<input hidden name="enable-anonymous-comment" value="' . $enableAnonymousComment . '">';
		$output .= '<textarea class="sppb-comment-field" name="comment" placeholder="' . $placeHolderText . '"></textarea>';
		$output .= '<div class="sppb-comment-btn-wrapper">';
		
		// Show normal comment submit button
		$output .= '<button aria-label="' . $postBtnAriaLabel . '" type="submit" class="comment-submit-btn ' . $btnClass . '" ' . (!$currentUserId && !$enableAnonymousComment ? 'disabled' : '') . ' style="cursor: ' . (!$currentUserId && !$enableAnonymousComment ? 'not-allowed' : 'pointer') . '; ' . (!$currentUserId && !$enableAnonymousComment ? 'opacity: 0.5' : '') . '" ' . (!$currentUserId && !$enableAnonymousComment ? 'title="Please sign in to comment"' : '') . '>';
		if($postBtnIcon && $postBtnIconPosition === 'left')
		{
			$output .= '<i class="sppb-btn-icon ' . $postBtnIcon . '"></i>';
		}
		$output .= '<span>' . $postBtnText . '</span>';
		if($postBtnIcon && $postBtnIconPosition === 'right')
		{
			$output .= '<i class="sppb-btn-icon ' . $postBtnIcon . '"></i>';
		}
		$output .= '</button>';
		
		$output .= '</div>';
		$output .= '</form>';

		$output .= '<div class="sppb-comments-list" style="margin-top: 40px;">';
		$output .= $this->renderComments($comments, $ellipsisBtnClass, $ellipsisBtnIcon, $likesIcon, $replyIcon, $settings, $btnClass, $enableAnonymousComment, 0);
		$output .= '</div>';




		$output .= '</div>';
		$output .= $this->renderLoginModal();
		return $output;
	}

	private function getInitials($name) {
		$words = explode(' ', trim($name));
		$initials = '';
		if (count($words) > 1) {
			$initials .= strtoupper(substr($words[0], 0, 1));
			$initials .= strtoupper(substr(end($words), 0, 1));
		} else {
			$initials .= strtoupper(substr($name, 0, 1));
		}
		return $initials;
	}

	private function getTextColor($color) {
		$r = hexdec(substr($color, 1, 2));
		$g = hexdec(substr($color, 3, 2));
		$b = hexdec(substr($color, 5, 2));

		$brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return ($brightness > 128) ? '#000000' : '#FFFFFF';
	}

	private function getGravatarUrl($email, $size = 45, $default = '404') {
		if (empty($email)) {
			return false;
		}
		
		$hash = md5(strtolower(trim($email)));
		$url = "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}";
		
		return $url;
	}

	private function getRelativeTime($timestamp) {
		$time = strtotime($timestamp);
		$now = strtotime(Date::sqlSafeDate());
		$diff = $now - $time;
		
		if ($diff < 60) {
			return 'Just now';
		} elseif ($diff < 3600) {
			$minutes = floor($diff / 60);
			return $minutes . ' min ago';
		} elseif ($diff < 86400) {
			$hours = floor($diff / 3600);
			return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
		} elseif ($diff < 2592000) {
			$days = floor($diff / 86400);
			return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
		} elseif ($diff < 31536000) {
			$months = floor($diff / 2592000);
			return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
		} else {
			$years = floor($diff / 31536000);
			return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
		}
	}

	private function renderComments($comments, $ellipsisBtnClass, $ellipsisBtnIcon, $likesIcon, $replyIcon, $settings, $btnClass, $enableAnonymousComment, $level = 0) {
		$currentUserId = Factory::getUser()->id;

		$rootCommentClass = '';
		if($level === 0){
			$rootCommentClass = ' sppb-root-comment';
		} 

		$output = '';
		foreach ($comments as $comment) {
			$isOwnComment = false;
			// Check if this is the user's own comment (only for logged-in users)
			if ($currentUserId && $comment->created_by_id && $currentUserId === $comment->created_by_id) {
				$isOwnComment = true;
			}

			if(!$isOwnComment && $comment->published === 0){
				continue;
			}

			$profileImageUrl = null;

			if (!empty($comment->created_by_id)) {
				$profileImageUrl = !empty($comment->profile_image) ? $comment->profile_image : '';
			}

			$avatarColor = !empty($settings->commentator_avatar_color) ? $settings->commentator_avatar_color : "#4285F4";
			$textColor = $this->getTextColor($avatarColor);
			$initials = $this->getInitials($comment->created_by ?? 'Anonymous Person');
			$gravatarUrl = null;
			$enableGravatar = ComponentHelper::getParams('com_sppagebuilder')->get('enable_gravatar', 1);
			if ($enableGravatar && !empty($comment->created_by_email)) {
				$gravatarUrl = $this->getGravatarUrl($comment->created_by_email, 45, '404');
			}
			
			$output .= '<div data-comment-id="' . $comment->id . '" data-parent-id="' . $comment->parent_id . '" class="sppb-comment-item sppb-comment-item ' . ((!isset($comment->parent_id) && !$comment->parent_id) ? ' sppb-parent-comment' : '') . ' ' . $rootCommentClass .'" style="margin-top: -4px; margin-left: ' . ($level > 0 ? (55) : 0) . 'px;">';
			$output .= '<div class="sppb-comment-thread-line" style="left:' . (-34) . 'px;"></div>';
			$output .= '<div class="sppb-comment-thread-connector" style="left:' . (-34) . 'px; width: ' . (55) . 'px;"></div>';
			$output .= '<div class="sppb-comment-content" ' . ($level > 0 ? 'style="margin-left: 0; margin-top: 11px;"' : "")  . '>';
			$output .= '<div class="sppb-comment-header sppb-comment-header">';

			if (!empty($profileImageUrl)) {
				$output .= '<div class="sppb-comment-avatar" style="border-radius: 50%; width: 45px; height: 45px; margin-right: 10px; overflow: hidden; flex-shrink: 0;">';
				$output .= '<img src="' . $profileImageUrl . '" alt="' . htmlspecialchars($comment->created_by ?? 'Anonymous Person') . '" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';" />';
				$output .= '<div class="sppb-comment-avatar-fallback" style="background-color: ' . $avatarColor . '; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: none; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; color: ' . $textColor . '">';
				$output .= '<span>' . $initials . '</span>';
				$output .= '</div>';
				$output .= '</div>';
			} elseif (!empty($gravatarUrl)) {
				$output .= '<div class="sppb-comment-avatar" style="border-radius: 50%; width: 45px; height: 45px; margin-right: 10px; overflow: hidden; flex-shrink: 0;">';
				$output .= '<img src="' . $gravatarUrl . '" alt="' . htmlspecialchars($comment->created_by ?? 'Anonymous Person') . '" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';" />';
				$output .= '<div class="sppb-comment-avatar-fallback" style="background-color: ' . $avatarColor . '; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: none; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; color: ' . $textColor . '">';
				$output .= '<span>' . $initials . '</span>';
				$output .= '</div>';
				$output .= '</div>';
			} else {
				$output .= '<div class="sppb-comment-avatar" style="background-color: ' . $avatarColor . '; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px; font-weight: 600; margin-right: 10px; color: ' . $textColor . '">';
				$output .= '<span>' . $initials . '</span>';
				$output .= '</div>';
			}
			$output .= '<div class="sppb-comment-header-left">';
			$output .= $this->getHeaderHtml($comment->created_by ?? 'Anonymous Person', $comment, $settings);
			$output .= '</div>';

			if ($isOwnComment) {
				$output .= '<div class="sppb-comment-ellipsis-action ' . $ellipsisBtnClass . '">';
				$output .= '<i class="sppb-ellipsis-icon ' . $ellipsisBtnIcon . '"></i>';
				$output .= '<div class="sppb-ellipsis-dropdown" style="display: none;">';
				$output .= $this->renderEllipsisActionButtons($settings);
				$output .= '</div>';
				$output .= '</div>';
			}
				
			$output .= '</div>';

			$output .= '<div class="sppb-comment-content sppb-comment-body" data-comment-id="' . $comment->id . '" style="margin-left: 55px; padding-bottom: 29px;">';
			$output .= '<div class="sppb-comment-body-thread-line"></div>';

			$needsModeration = $isOwnComment && $comment->published === 0;

			if($needsModeration){
				$output .= '<div class="sppb-comment-unpublished-notice">' . Text::_('COM_SPPAGEBUILDER_COMMENT_UNAPPROVED_NOTICE') . '</div>';
			}

			$output .= '<span class="sppb-comment-content-text' . ($needsModeration ? ' sppb-comment-unpublished' : '') . '" style="margin-left: 10px; margin-top: 20px; display: inline-block;">';
			$output .= '<span>' . nl2br($comment->content) . '</span>';
			$output .= '</span>';
			$output .= '<div class="sppb-comment-edit-form sppb-comment-form" data-comment-id="' . $comment->id . '" style="display: none; ' . ($needsModeration ? 'margin-top: -45px;' : '') . '">';
			$output .= '<textarea type="text" class="sppb-comment-edit-input sppb-comment-field" data-comment-id="' . $comment->id . '">' . $comment->content . '</textarea>';
			$output .= $this->renderEditCommentButtons($settings, $comment->id);
			$output .= '</div>';

			$isLikedClass = (isset($comment->user_liked) && $comment->user_liked) ? 'liked' : '';

			if(!$needsModeration){
				$output .= '<div class="sppb-comment-actions" style="margin-left: 10px">';
				$output .= '<div class="sppb-comment-likes ' . $isLikedClass . '">';
				$output .= '<i class="sppb-likes-icon ' . $likesIcon . '"></i>';
				$output .= '<span class="sppb-likes-text">' . ($comment->likes_count ?? 0) . ' likes</span>';
				$output .= '</div>';
				$output .= '<div class="sppb-comment-reply-action" data-comment-id="' . $comment->id . '">';
				$output .= '<i class="sppb-reply-icon ' . $replyIcon . '"></i>';
				$output .= '<span class="sppb-reply-text">' . $comment->replies . ' reply</span>';
				$output .= '</div>';
				$output .= '</div>';
			}

			$output .= '<div class="sppb-comment-reply-form sppb-comment-form" data-comment-id="' . $comment->id . '" style="display: none;">';
			$output .= '<textarea type="text" class="sppb-comment-reply-input sppb-comment-field" data-comment-id="' . $comment->id . '" placeholder="Write a reply..."></textarea>';
			$output .= '<div class="sppb-comment-btn-wrapper">';
			$output .= '<button class="sppb-comment-reply-cancel-btn ' . $btnClass . '" data-comment-id="' . $comment->id . '">Cancel</button>';
			$output .= '<button class="sppb-comment-reply-submit-btn comment-submit-btn ' . $btnClass . '" data-comment-id="' . $comment->id . '" ' . (!$currentUserId && !$enableAnonymousComment ? 'disabled' : '') . ' style="cursor: ' . (!$currentUserId && !$enableAnonymousComment ? 'not-allowed' : 'pointer') . '; ' . (!$currentUserId && !$enableAnonymousComment ? 'opacity: 0.5' : '') . '" ' . (!$currentUserId && !$enableAnonymousComment ? 'title="Please sign in to comment"' : '') . '>Submit</button>';
			$output .= '</div>';
			$output .= '</div>';
			$output .= '</div>';
			

			$output .= '</div>';
			if(isset($comment->children) && !empty($comment->children) && !$needsModeration){
				$output .= '<div class="sppb-comment-children-wrapper">';
				$output .= $this->renderComments($comment->children, $ellipsisBtnClass, $ellipsisBtnIcon, $likesIcon, $replyIcon, $settings, $btnClass, $enableAnonymousComment, $level + 1);
				$output .= '</div>';
			}
			$output .= '</div>';
		}
		return $output;
	}

	private function renderEditCommentButtons($settings, $commentId){
		$editCommentItems = $settings->edit_comment_button_item ?? [];
		$output = '';
		$output .= '<div class="sppb-comment-edit-buttons-wrapper sppb-comment-btn-wrapper">';
		foreach ($editCommentItems as $item_key => $item) {
			$uniqueId = 'sppb-comment-edit-button-' . $item_key;
			$btnClass = 'sppb-btn ';
			$btnClass .= (isset($item->edit_comment_button_appearance) && $item->edit_comment_button_appearance) ? ' sppb-btn-' . $item->edit_comment_button_appearance : '';
			$btnClass .= (isset($item->edit_comment_button_size) && $item->edit_comment_button_size) ? ' sppb-btn-' . $item->edit_comment_button_size : ' sppb-btn-md';
			$btnClass .= (isset($item->edit_comment_button_shape) && $item->edit_comment_button_shape) ? ' sppb-btn-' . $item->edit_comment_button_shape : ' sppb-btn-rounded';
			$output .= '<button class="sppb-comment-edit-button ' . ($item->title == 'Update' ? 'sppb-comment-edit-submit-btn' : 'sppb-comment-edit-cancel-btn') . ' ' . $btnClass . '" id="' . $uniqueId . '" data-comment-id="' . $commentId . '">'. $item->title .'</button>';
		}
		$output .= '</div>';

		return $output;
	}

	private function renderEllipsisActionButtons($settings){
		$ellipsisActionItems = $settings->ellipsis_action_button_item ?? [];
		$output = '';
		$output .= '<div class="sppb-comment-ellipsis-action-buttons-wrapper">';
		foreach ($ellipsisActionItems as $item_key => $item) {
			$uniqueId = 'sppb-comment-ellipsis-action-button-' . $item_key;
			$btnClass = 'sppb-btn ';
			if ($item_key == 0) {
				$btnClass .= ' sppb-ellipsis-edit-btn ';
			} else {
				$btnClass .= ' sppb-ellipsis-delete-btn ';
			}
			$btnClass .= (isset($item->ellipsis_action_button_appearance) && $item->ellipsis_action_button_appearance) ? ' sppb-btn-' . $item->ellipsis_action_button_appearance : '';
			$btnClass .= (isset($item->ellipsis_action_button_size) && $item->ellipsis_action_button_size) ? ' sppb-btn-' . $item->ellipsis_action_button_size : ' sppb-btn-md';
			$btnClass .= (isset($item->ellipsis_action_button_shape) && $item->ellipsis_action_button_shape) ? ' sppb-btn-' . $item->ellipsis_action_button_shape : ' sppb-btn-rounded';
			$output .= '<button style="width: 100%; text-align: left;" class="sppb-comment-ellipsis-action-button ' . $btnClass . '" id="' . $uniqueId . '">'. $item->title .'</button>';
		}
		$output .= '</div>';

		return $output;
	}

	private function getHeaderHtml($commentatorName, $comment, $settings){
		$output = '';
		$output .= '<div class="commentator-name-wrapper">';
		$output .= '<span class="commentator-name">' . htmlspecialchars($commentatorName) . '</span>';
		$output .= '</div>';
		
		$enableTime = (isset($settings->enable_time) && $settings->enable_time) ? $settings->enable_time : 0;
		if ($enableTime) {
			$output .= '<div class="sppb-comment-time-wrapper">';
			$output .= '<span class="sppb-comment-time">' . $this->getRelativeTime($comment->created_on);
			
			$showEditedTime = (isset($settings->show_edited_time) && $settings->show_edited_time) ? $settings->show_edited_time : 0;
			if ($showEditedTime && $comment->modified && $comment->modified !== $comment->created_on) {
				$output .= '<span class="sppb-comment-edited-time"> • Edited ' . $this->getRelativeTime($comment->modified) . '</span>';
			}
			
			$output .= '</span>';
			$output .= '</div>';
		}
		
		return $output;
	}

	public static function getAjax(){
		$input  = Factory::getApplication()->input;
		$inputs = $input->get('data', [], 'ARRAY');
		$addonId = '';

		foreach ($inputs as $item) {
			if ($item['name'] === 'addon-id') {
				$addonId = $item['value'];
				break;
			}
		}

		$secureData        = Session::getInstance();
        $ipAddress         = Factory::getApplication()->input->server->get('REMOTE_ADDR');
        $maxRequests       = $secureData->get('max_requests_' . $addonId, 10);
        $timeWindow        = $secureData->get('time_window_' . $addonId, 60);
            
		if (SppagebuilderRateLimiterHelper::isRateLimited($ipAddress . $addonId, $maxRequests, $timeWindow)) {
			$timeUntilReset    = SppagebuilderRateLimiterHelper::getTimeUntilReset($ipAddress . $addonId, $timeWindow);
			$output['status']  = false;
			$output['content'] = '<span class="sppb-text-danger">' . Text::sprintf('COM_SPPAGEBUILDER_RATE_LIMIT_EXCEEDED', $timeUntilReset) . '</span>';
			return json_encode($output);
		}

		$output = [];
		$output['status']  = true;
		$output['content'] = 'success';

		return json_encode($output);
	}

	/**
	 * Generate the CSS string for the frontend page.
	 *
	 * @return 	string 	The CSS string for the page.
	 * @since 	1.0.0
	 */
	public function css()
	{
		$addon_id = '#sppb-addon-' . $this->addon->id;
		$settings = $this->addon->settings;
		$cssHelper = new CSSHelper($addon_id);
        $css = '';

		$css .= $cssHelper->generateStyle('.sppb-comment-title-wrapper', $settings,
				['title_alignment' => 'justify-content'], ['title_alignment' => false], [],[],[],
				'display: flex; align-items: center; gap: 16px; border-bottom: 1px solid #D3D7EB; margin-bottom: 40px; padding-bottom: 24px;');
		$css .= $cssHelper->typography('.sppb-comment-title-text', $settings, 'title_typography',
				['font'           => 'title_font_family',
				'size'           => 'title_fontsize',
				'line_height'    => 'title_lineheight',
				'letter_spacing' => 'title_letterspace',
				'uppercase'      => 'title_font_style.uppercase',
				'italic'         => 'title_font_style.italic',
				'underline'      => 'title_font_style.underline',
				'weight'         => 'title_font_style.weight',
				]);
		$css .= $cssHelper->generateStyle('.sppb-comment-title-text', $settings,
				['title_color' => 'color'], ['title_color' => false]);
		$settings->title_shadow = $cssHelper->parseBoxShadow($settings, 'title_shadow', true);
		$css .= $cssHelper->generateStyle('.sppb-comment-title-text', $settings,
				['title_shadow' => 'text-shadow'], ['title_shadow' => false]);
		$css .= $cssHelper->typography('.sppb-comment-count', $settings, 'comment_count_typography',
				['font'          => 'comment_count_font_family',
				'size'           => 'comment_count_fontsize',
				'line_height'    => 'comment_count_lineheight',
				'letter_spacing' => 'comment_count_letterspace',
				'uppercase'      => 'comment_count_font_style.uppercase',
				'italic'         => 'comment_count_font_style.italic',
				'underline'      => 'comment_count_font_style.underline',
				'weight'         => 'comment_count_font_style.weight',
				]);
		$css .= $cssHelper->generateStyle('.sppb-comment-count', $settings,
				['comment_count_color' => 'color'], ['comment_count_color' => false]);
		$settings->comment_count_shadow = $cssHelper->parseBoxShadow($settings, 'comment_count_shadow', true);
		$css .= $cssHelper->generateStyle('.sppb-comment-count', $settings,
				['comment_count_shadow' => 'text-shadow'], ['comment_count_shadow' => false]);

		$css .= $cssHelper->generateStyle('.sppb-comment-label-wrapper', $settings,
				['label_alignment' => 'justify-content'], ['label_alignment' => false], [],[],[],
				'display: flex; align-items: center; margin-bottom: 12px;');
		$css .= $cssHelper->generateStyle('.sppb-comment-label-text', $settings,
				['label_color' => 'color'], ['label_color' => false]);
		$settings->label_text_shadow = $cssHelper->parseBoxShadow($settings, 'label_text_shadow', true);
		$css .= $cssHelper->generateStyle('.sppb-comment-label-text', $settings,
				['label_text_shadow' => 'text-shadow'], ['label_text_shadow' => false]);

		$enableLikes = (isset($settings->enable_likes) && $settings->enable_likes) ? $settings->enable_likes : 0;
		if ($enableLikes) {
			$css .= $cssHelper->generateStyle('.sppb-comment-actions', $settings, [], [], [],[],[],
					'display: flex; align-items: center; gap: 20px; margin-top: 16px;');
			
			$css .= $cssHelper->generateStyle('.sppb-comment-likes', $settings, [], [], [],[],[],
					'display: flex; align-items: center; gap: 6px; cursor: pointer;');
			
			$css .= $cssHelper->typography('.sppb-likes-text', $settings, 'likes_typography',
					['font'           => 'likes_font_family',
					'size'           => 'likes_fontsize',
					'line_height'    => 'likes_lineheight',
					'letter_spacing' => 'likes_letterspace',
					'uppercase'      => 'likes_font_style.uppercase',
					'italic'         => 'likes_font_style.italic',
					'underline'      => 'likes_font_style.underline',
					'weight'         => 'likes_font_style.weight',
					]);
			
			$css .= $cssHelper->generateStyle('.sppb-likes-text', $settings,
					['likes_color' => 'color'], ['likes_color' => false]);

			$css .= $cssHelper->generateStyle('.sppb-comment-likes.liked > .sppb-likes-icon', $settings,
					['likes_focused_color' => 'color'], ['likes_focused_color' => false]);
			
			$settings->likes_text_shadow = $cssHelper->parseBoxShadow($settings, 'likes_text_shadow', true);
			$css .= $cssHelper->generateStyle('.sppb-likes-text', $settings,
					['likes_text_shadow' => 'text-shadow'], ['likes_text_shadow' => false]);
			
			$css .= $cssHelper->generateStyle('.sppb-likes-icon', $settings, [], [], [],[],[],
					'font-size: 16px; transition: color 0.3s ease;');
					$css .= $cssHelper->generateStyle('.sppb-likes-icon', $settings,
				['likes_color' => 'color'], ['likes_color' => false]);
		
			$css .= $cssHelper->generateStyle('.sppb-comment-avatar img', $settings, [], [], [],[],[],
					'transition: opacity 0.3s ease;');
			$css .= $cssHelper->generateStyle('.sppb-comment-avatar-fallback', $settings, [], [], [],[],[],
					'transition: opacity 0.3s ease;');
			
			$css .= $cssHelper->generateStyle('.sppb-comment-likes:hover .sppb-likes-text', $settings,
					['likes_hover_color' => 'color'], ['likes_hover_color' => false]);
			$css .= $cssHelper->generateStyle('.sppb-comment-likes:hover .sppb-likes-icon', $settings,
					['likes_hover_color' => 'color'], ['likes_hover_color' => false]);
			
			$css .= $cssHelper->generateStyle('.sppb-comment-likes:focus .sppb-likes-text', $settings,
					['likes_focused_color' => 'color'], ['likes_focused_color' => false]);
			$css .= $cssHelper->generateStyle('.sppb-comment-likes:focus .sppb-likes-icon', $settings,
					['likes_focused_color' => 'color'], ['likes_focused_color' => false]);
			
			$likesIconPosition = (isset($settings->likes_icon_position) && $settings->likes_icon_position) ? $settings->likes_icon_position : 'left';
			if ($likesIconPosition === 'right') {
				$css .= $cssHelper->generateStyle('.sppb-comment-likes', $settings, [], [], [],[],[],
						'flex-direction: row-reverse;');
			}
		}

		$enableReply = (isset($settings->enable_reply) && $settings->enable_reply) ? $settings->enable_reply : 0;
		if ($enableReply) {
			$css .= $cssHelper->generateStyle('.sppb-comment-reply-action', $settings, [], [], [],[],[],
					'display: flex; align-items: center; gap: 6px; cursor: pointer;');
			
			$css .= $cssHelper->typography('.sppb-reply-text', $settings, 'reply_typography',
					['font'           => 'reply_font_family',
					'size'           => 'reply_fontsize',
					'line_height'    => 'reply_lineheight',
					'letter_spacing' => 'reply_letterspace',
					'uppercase'      => 'reply_font_style.uppercase',
					'italic'         => 'reply_font_style.italic',
					'underline'      => 'reply_font_style.underline',
					'weight'         => 'reply_font_style.weight',
					]);
			
			$css .= $cssHelper->generateStyle('.sppb-reply-text', $settings,
					['reply_color' => 'color'], ['reply_color' => false]);
			
			$settings->reply_text_shadow = $cssHelper->parseBoxShadow($settings, 'reply_text_shadow', true);
			$css .= $cssHelper->generateStyle('.sppb-reply-text', $settings,
					['reply_text_shadow' => 'text-shadow'], ['reply_text_shadow' => false]);
			
			$css .= $cssHelper->generateStyle('.sppb-reply-icon', $settings, [], [], [],[],[],
					'font-size: 16px; transition: color 0.3s ease;');
			$css .= $cssHelper->generateStyle('.sppb-reply-icon', $settings,
					['reply_color' => 'color'], ['reply_color' => false]);
			
			$css .= $cssHelper->generateStyle('.sppb-comment-reply-action:hover .sppb-reply-text', $settings,
					['reply_hover_color' => 'color'], ['reply_hover_color' => false]);
			$css .= $cssHelper->generateStyle('.sppb-comment-reply-action:hover .sppb-reply-icon', $settings,
					['reply_hover_color' => 'color'], ['reply_hover_color' => false]);
			
			$replyIconPosition = (isset($settings->reply_icon_position) && $settings->reply_icon_position) ? $settings->reply_icon_position : 'left';
			if ($replyIconPosition === 'right') {
				$css .= $cssHelper->generateStyle('.sppb-comment-reply-action', $settings, [], [], [],[],[],
						'flex-direction: row-reverse;');
			}
		}

		$css .= $cssHelper->generateStyle('.sppb-comment-form',[], [], [], [],[],[],
				'display: flex; flex-direction: column;');
				
		$css .= $cssHelper->generateStyle('.sppb-comment-reply-form', $settings, [], [], [],[],[],
				'margin-top: 16px;');

		$css .= $cssHelper->generateStyle('.sppb-comment-field', $settings,
				['comment_field_height' => 'height',
				'comment_field_padding' => 'padding',
				'comment_field_typing_color' => 'color',
				'comment_field_background_color' => 'background-color',
				'comment_field_border_color' => 'border-color',
				'comment_field_border_width' => 'border-width'],
				['comment_field_padding' => false,
				'comment_field_typing_color' => false,
				'comment_field_background_color' => false,
				'comment_field_border_color' => false,
				'comment_field_border_width' => false],[],[],[], 'transition: all 0.05s ease-in-out; border-radius: 6px; border-bottom-left-radius: 0; border-bottom-right-radius: 0; width: 100%; box-sizing: border-box;');
		$css .= $cssHelper->generateStyle('.sppb-comment-field::placeholder', $settings,
				['comment_field_placeholder_color' => 'color'], ['comment_field_placeholder_color' => false]);
		$css .= $cssHelper->generateStyle('.sppb-comment-field:hover', $settings,
				['comment_field_background_hover_color' => 'background-color'],
				['comment_field_background_hover_color' => false]);
		$css .= $cssHelper->generateStyle('.sppb-comment-field:hover::placeholder', $settings,
				['comment_field_placeholder_hover_color' => 'color'], ['comment_field_placeholder_hover_color' => false]);
		$css .= $cssHelper->generateStyle('.sppb-comment-field:focus', $settings,
				['comment_field_border_focus_color' => 'border-color',],
				['comment_field_border_focus_color' => false]);
		$css .= $cssHelper->typography('.sppb-comment-field', $settings, 'comment_field_typography',
				['font'           => 'comment_field_font_family',
				'size'           => 'comment_field_fontsize',
				'line_height'    => 'comment_field_lineheight',
				'letter_spacing' => 'comment_field_letterspace',
				'uppercase'      => 'comment_field_font_style.uppercase',
				'italic'         => 'comment_field_font_style.italic',
				'underline'      => 'comment_field_font_style.underline',
				'weight'         => 'comment_field_font_style.weight',
				]);

		$css .= $cssHelper->generateStyle('.sppb-comment-header', $settings, [], [], [],[],[],
				'display: flex; justify-content: space-between; align-items: center; position: relative;');
		
		$css .= $cssHelper->generateStyle('.sppb-comment-header-left', $settings, [], [], [],[],[],
				'display: flex; flex-direction: column; gap: 4px;');
		
		$css .= $cssHelper->generateStyle('.sppb-comment-ellipsis-action', $settings, [], [], [],[],[],
				'position: relative; cursor: inherit;');

		$css .= $cssHelper->generateStyle('.sppb-comment-ellipsis-action i', $settings, [], [], [],[],[],
				'cursor: pointer;');

		$css .= $cssHelper->generateStyle('.sppb-comment-ellipsis-action', $settings,
				['ellipsis_icon_bg_color' => 'background-color'], ['ellipsis_icon_bg_color' => false]);

		$css .= $cssHelper->generateStyle('.sppb-comment-ellipsis-action.sppb-btn-custom.sppb-btn-outline', $settings,
				[], [], [],[],[],
				'background-color: transparent; border-color: transparent;');
		$css .= $cssHelper->generateStyle('.sppb-comment-ellipsis-action.sppb-btn-custom.sppb-btn-outline i', $settings,
				[], [], [],[],[],
				'background-color: transparent; border-color: transparent;');
		
		$css .= $cssHelper->generateStyle('.sppb-ellipsis-icon', $settings, [], [], [],[],[],
				'font-size: 16px; padding: 8px; border-radius: 50%; transition: all 0.3s ease;');
		
		$css .= $cssHelper->generateStyle('.sppb-ellipsis-icon', $settings,
				['ellipsis_icon_color' => 'color'], ['ellipsis_icon_color' => false]);
		
		$css .= $cssHelper->generateStyle('.sppb-ellipsis-icon', $settings,
				['ellipsis_icon_bg_color' => 'background-color'], ['ellipsis_icon_bg_color' => false]);
		
		$css .= $cssHelper->generateStyle('.sppb-ellipsis-dropdown', $settings, [], [], [],[],[],
				'position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 1000; min-width: 120px;');
		
		$css .= $cssHelper->typography('.sppb-ellipsis-edit-btn, .sppb-ellipsis-delete-btn', $settings, 'ellipsis_action_button_typography',
				['font'           => 'ellipsis_action_button_font_family',
				'size'           => 'ellipsis_action_button_fontsize',
				'line_height'    => 'ellipsis_action_button_lineheight',
				'letter_spacing' => 'ellipsis_action_button_letterspace',
				'uppercase'      => 'ellipsis_action_button_font_style.uppercase',
				'italic'         => 'ellipsis_action_button_font_style.italic',
				'underline'      => 'ellipsis_action_button_font_style.underline',
				'weight'         => 'ellipsis_action_button_font_style.weight',
				]);

				$ellipsisModes = ['.sppb-ellipsis-edit-btn', '.sppb-ellipsis-delete-btn'];

				for ($i = 0; $i < count($settings->ellipsis_action_button_item); $i++) {
					$css .= $cssHelper->generateStyle($ellipsisModes[$i], $settings->ellipsis_action_button_item[$i],
					['ellipsis_action_button_color' => 'color'], ['ellipsis_action_button_color' => false]);
			
					$css .= $cssHelper->generateStyle($ellipsisModes[$i], $settings->ellipsis_action_button_item[$i],
					['ellipsis_action_button_background_color' => 'background-color'], ['ellipsis_action_button_background_color' => false]);
			
					$css .= $cssHelper->generateStyle($ellipsisModes[$i] . ':hover', $settings->ellipsis_action_button_item[$i],
					['ellipsis_action_button_color_hover' => 'color'], ['ellipsis_action_button_color_hover' => false]);
			
					$css .= $cssHelper->generateStyle($ellipsisModes[$i] . ':hover', $settings->ellipsis_action_button_item[$i],
					['ellipsis_action_button_background_color_hover' => 'background-color'], ['ellipsis_action_button_background_color_hover' => false]);
				}
		
		$css .= $cssHelper->generateStyle('.sppb-ellipsis-edit-btn:first-child', $settings, [], [], [],[],[],
				'border-radius: 6px 6px 0 0;');
		
		$css .= $cssHelper->generateStyle('.sppb-ellipsis-delete-btn:last-child', $settings, [], [], [],[],[],
				'border-radius: 0 0 6px 6px;');
		
		$css .= $cssHelper->generateStyle('.sppb-ellipsis-edit-btn:only-child', $settings, [], [], [],[],[],
				'border-radius: 6px;');
		
		$css .= $cssHelper->generateStyle('.sppb-comment-ellipsis-action', $settings,
				['ellipsis_margin' => 'margin'], ['ellipsis_margin' => false]);

		$css .= $cssHelper->generateStyle('.sppb-comment-header-left', $settings, [], [], [],[],[], 'flex: 1;');

		$css .= $cssHelper->generateStyle('.sppb-comment-header-left', $settings, ['commentator_name_position' => 'display: flex; justify-content'], ['commentator_name_position' => false]);
		$css .= $cssHelper->generateStyle('.sppb-comment-ellipsis-action', $settings, ['ellipsis_position' => 'display: flex; justify-content'], ['ellipsis_position' => false]);
		
		$css .= $cssHelper->generateStyle('.comment-submit-btn',[],[],[],[],[],[], 'outline: none; cursor: pointer;');
		$css .= $cssHelper->typography('.comment-submit-btn', $settings, 'post_button_typography',
				['font'           => 'post_button_font_family',
				'size'           => 'post_button_fontsize',
				'line_height'    => 'post_button_lineheight',
				'letter_spacing' => 'post_button_letterspace',
				'uppercase'      => 'post_button_font_style.uppercase',
				'italic'         => 'post_button_font_style.italic',
				'underline'      => 'post_button_font_style.underline',
				'weight'         => 'post_button_font_style.weight',
				]);

		if(isset($settings->post_button_size) && $settings->post_button_size && $settings->post_button_size === 'custom'){
			$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-custom', $settings,
				['post_button_padding' => 'padding'], ['post_button_padding' => false]);
		}

		$css .= $cssHelper->generateStyle('.sppb-comment-form .sppb-comment-btn-wrapper',$settings,['comment_field_border_color' => 'border-color'],['comment_field_border_color' => false],[],[],[],
				'display: flex; padding: 16px; gap: 8px; align-items: center; border-width: 0px 1px 1px 1px; border-style: solid; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;');
		$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-custom', $settings,
				['post_button_color' => 'color',
				'post_button_background_color' => 'background-color',],
				['post_button_color' => false,
				'post_button_background_color' => false]);

		$currentUserId = Factory::getUser()->id;

		if ($currentUserId) {
			$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-custom:hover', $settings,
			['post_button_color_hover' => 'color',
			'post_button_background_color_hover' => 'background-color',],
			['post_button_color_hover' => false,
			'post_button_background_color_hover' => false]);
		
			$settings->post_button_background_gradient = $cssHelper->parseColor($settings, 'post_button_background_gradient');
			$settings->post_button_background_gradient_hover = $cssHelper->parseColor($settings, 'post_button_background_gradient_hover');
	
			$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient', $settings,
					['post_button_background_gradient' => 'border:none; background-image',],
					['post_button_background_gradient' => false]);
			$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient:hover', $settings,
					['post_button_background_gradient_hover' => 'border:none; background-image',],
					['post_button_background_gradient_hover' => false]);
					$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-custom.sppb-btn-outline:hover', $settings,
					['post_button_background_color_hover' => 'background-color: transparent; border-color'],
					['post_button_background_color_hover' => false]);	
		}

		$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-custom.sppb-btn-outline', $settings,
				['post_button_background_color' => 'background-color: transparent; border-color'],
				['post_button_background_color' => false]);
		
		$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-link', $settings,
				['post_button_link_color' => 'color',
				'post_button_link_border_color' => 'border-color',
				'post_button_link_border_width' => 'border-width: 0 0 %spx 0',
				'post_button_link_padding_bottom' => 'padding: 0 0 %spx 0'],
				['post_button_link_color' => false,
				'post_button_link_border_color' => false,
				'post_button_link_border_width' => false,
				'post_button_link_padding_bottom' => false]);

		$css .= $cssHelper->generateStyle('.comment-submit-btn.sppb-btn-link:hover', $settings,
				['post_button_link_hover_color' => 'color',
				'post_button_link_border_hover_color' => 'border-color'],
				['post_button_link_hover_color' => false,
				'post_button_link_border_hover_color' => false]);

		$css .= $cssHelper->generateStyle('.sppb-comment-btn-wrapper', $settings,
				['post_button_alignment' => 'justify-content'],
				['post_button_alignment' => false],
				[], [], [],
				'display: flex; align-items: center;');
		$css .= $cssHelper->generateStyle('.sppb-btn-icon', $settings,
				['post_button_icon_margin' => 'margin'], ['post_button_icon_margin' => false]);

		foreach ($settings->edit_comment_button_item as $item_key => $item) {
			$css .= $cssHelper->typography('#sppb-comment-edit-button-' . $item_key, $item, 'edit_comment_button_typography',
					['font'           => 'edit_comment_button_font_family',
					'size'           => 'edit_comment_button_fontsize',
					'line_height'    => 'edit_comment_button_lineheight',
					'letter_spacing' => 'edit_comment_button_letterspace',
					'uppercase'      => 'edit_comment_button_font_style.uppercase',
					'italic'         => 'edit_comment_button_font_style.italic',
					'underline'      => 'edit_comment_button_font_style.underline',
					'weight'         => 'edit_comment_button_font_style.weight',
					]);
			$css .= $cssHelper->generateStyle('#sppb-comment-edit-button-' . $item_key, $item,
					['edit_comment_button_color' => 'color',
					'edit_comment_button_background_color' => 'background-color'],
					['edit_comment_button_color' => false,
					'edit_comment_button_background_color' => false], [], [], [],
					'outline: none; cursor: pointer;');
			$css .= $cssHelper->generateStyle('#sppb-comment-edit-button-' . $item_key . ':hover', $item,
					['edit_comment_button_color_hover' => 'color',
					'edit_comment_button_background_color_hover' => 'background-color'],
					['edit_comment_button_color_hover' => false,
					'edit_comment_button_background_color_hover' => false]);
			$css .= $cssHelper->generateStyle('#sppb-comment-edit-button-' . $item_key . '.sppb-btn-outline', $item,
					['edit_comment_button_background_color' => 'background-color: transparent; border-color'],
					['edit_comment_button_background_color' => false]);
			$css .= $cssHelper->generateStyle('#sppb-comment-edit-button-' . $item_key . '.sppb-btn-outline:hover', $item,
					['edit_comment_button_background_color_hover' => 'background-color: transparent; border-color'],
					['edit_comment_button_background_color_hover' => false]);
			$item->edit_comment_button_background_gradient = $cssHelper->parseColor($item, 'edit_comment_button_background_gradient');
			$item->edit_comment_button_background_gradient_hover = $cssHelper->parseColor($item, 'edit_comment_button_background_gradient_hover');
			$css .= $cssHelper->generateStyle('#sppb-comment-edit-button-' . $item_key . '.sppb-btn-gradient', $item,
					['edit_comment_button_background_gradient' => 'border:none; background-image'],
					['edit_comment_button_background_gradient' => false]);
			$css .= $cssHelper->generateStyle('#sppb-comment-edit-button-' . $item_key . '.sppb-btn-gradient:hover', $item,
					['edit_comment_button_background_gradient_hover' => 'border:none; background-image'],
					['edit_comment_button_background_gradient_hover' => false]);
		}

	$css .= $cssHelper->generateStyle('.sppb-comment-edit-buttons-wrapper', $settings,
				['edit_comment_button_alignment' => 'justify-content',
				'edit_comment_button_gap' => 'gap'], ['edit_comment_button_alignment' => false, 'edit_comment_button_gap' => false],
				[], [], [],
				'display: flex; align-items: center;');

	$css .= $cssHelper->generateStyle('.commentator-name-wrapper', $settings,
				['commentator_name_alignment' => 'justify-content'], ['commentator_name_alignment' => false], [],[],[],
				'display: flex; align-items: center;');

	$css .= $cssHelper->typography('.commentator-name', $settings, 'commentator_name_typography',
				['font'           => 'commentator_name_font_family',
				'size'           => 'commentator_name_fontsize',
				'line_height'    => 'commentator_name_lineheight',
				'letter_spacing' => 'commentator_name_letterspace',
				'uppercase'      => 'commentator_name_font_style.uppercase',
				'italic'         => 'commentator_name_font_style.italic',
				'underline'      => 'commentator_name_font_style.underline',
				'weight'         => 'commentator_name_font_style.weight',
				]);
	$css .= $cssHelper->generateStyle('.commentator-name', $settings,
				['commentator_name_color' => 'color'], ['commentator_name_color' => false]);
	$settings->commentator_name_shadow = $cssHelper->parseBoxShadow($settings, 'commentator_name_text_shadow', true);
	$css .= $cssHelper->generateStyle('.commentator-name', $settings,
				['commentator_name_shadow' => 'text-shadow'], ['commentator_name_shadow' => false]);

		$css .= $cssHelper->typography('.sppb-comment-content-text', $settings, 'posted_comment_typography',
				['font'           => 'posted_comment_font_family',
				'size'           => 'posted_comment_fontsize',
				'line_height'    => 'posted_comment_lineheight',
				'letter_spacing' => 'posted_comment_letterspace',
				'uppercase'      => 'posted_comment_font_style.uppercase',
				'italic'         => 'posted_comment_font_style.italic',
				'underline'      => 'posted_comment_font_style.underline',
				'weight'         => 'posted_comment_font_style.weight',
				]);
		$css .= $cssHelper->generateStyle('.sppb-comment-content-text', $settings,
				['posted_comment_color' => 'color'], ['posted_comment_color' => false]);
		$settings->posted_comment_shadow = $cssHelper->parseBoxShadow($settings, 'posted_comment_text_shadow', true);
		$css .= $cssHelper->generateStyle('.sppb-comment-content-text', $settings,
				['posted_comment_shadow' => 'text-shadow'], ['posted_comment_shadow' => false]);


		// Time Styles
		$enableTime = (isset($settings->enable_time) && $settings->enable_time) ? $settings->enable_time : 0;
		if ($enableTime) {
			$css .= $cssHelper->typography('.sppb-comment-time', $settings, 'time_typography',
					['font'           => 'time_font_family',
					'size'           => 'time_fontsize',
					'line_height'    => 'time_lineheight',
					'letter_spacing' => 'time_letterspace',
					'uppercase'      => 'time_font_style.uppercase',
					'italic'         => 'time_font_style.italic',
					'underline'      => 'time_font_style.underline',
					'weight'         => 'time_font_style.weight',
					]);
			$css .= $cssHelper->generateStyle('.sppb-comment-time', $settings,
					['time_color' => 'color'], ['time_color' => false]);
			$settings->time_shadow = $cssHelper->parseBoxShadow($settings, 'time_text_shadow', true);
			$css .= $cssHelper->generateStyle('.sppb-comment-time', $settings,
					['time_shadow' => 'text-shadow'], ['time_shadow' => false]);
			$css .= $cssHelper->generateStyle('.sppb-comment-time-wrapper', $settings,
					['time_alignment' => 'text-align'], ['time_alignment' => false]);

			$showEditedTime = (isset($settings->show_edited_time) && $settings->show_edited_time) ? $settings->show_edited_time : 0;
			if ($showEditedTime) {
				$css .= $cssHelper->typography('.sppb-comment-edited-time', $settings, 'edited_time_typography',
						['font'           => 'edited_time_font_family',
						'size'           => 'edited_time_fontsize',
						'line_height'    => 'edited_time_lineheight',
						'letter_spacing' => 'edited_time_letterspace',
						'uppercase'      => 'edited_time_font_style.uppercase',
						'italic'         => 'edited_time_font_style.italic',
						'underline'      => 'edited_time_font_style.underline',
						'weight'         => 'edited_time_font_style.weight',
						]);
				$css .= $cssHelper->generateStyle('.sppb-comment-edited-time', $settings,
						['edited_time_color' => 'color'], ['edited_time_color' => false]);
				$settings->edited_time_shadow = $cssHelper->parseBoxShadow($settings, 'edited_time_text_shadow', true);
				$css .= $cssHelper->generateStyle('.sppb-comment-edited-time', $settings,
						['edited_time_shadow' => 'text-shadow'], ['edited_time_shadow' => false]);
				$css .= $cssHelper->generateStyle('.sppb-comment-edited-time-wrapper', $settings,
						['edited_time_alignment' => 'text-align'], ['edited_time_alignment' => false]);
			}
		}

		return $css;
	}

	public function js(){
		$js = '';
		$input = Factory::getApplication()->input;
		$collectionType = $input->get('collection_type', null, 'string');
		$viewType = $input->get('view', null, 'string');
		$articleId = $input->get('id', null, 'int');
		$commentService = new CommentService();
		if ($viewType == 'page') {
			$articleId = $commentService->getArticleId($articleId);
		}
		$itemId = $input->get('collection_item_id', null, 'array');
		$itemId = $itemId ? $itemId[0] : null;
		$itemId = $itemId ? $itemId : $articleId;
		if (($collectionType != 'articles' && $collectionType != 'normal-source') && empty($itemId)) {
			return "";
		}
		$collectionType = $collectionType ? $collectionType : 'articles';
		$avatarColor = !empty($this->addon->settings->commentator_avatar_color) ? $this->addon->settings->commentator_avatar_color : "#4285F4";
		$currentUserId = Factory::getUser()->id;

		$js .= '
		// It processes comment submissions, updates, and deletions, and returns JSON responses.

		document.addEventListener("DOMContentLoaded", function() {
			// Handle Gravatar image loading and fallback
			const handleGravatarImages = () => {
				const gravatarImages = document.querySelectorAll(\'.sppb-comment-avatar img[src*="gravatar.com"]\');
				gravatarImages.forEach(img => {
					img.addEventListener(\'error\', function() {
						// Hide the image and show the fallback
						this.style.display = \'none\';
						const fallback = this.nextElementSibling;
						if (fallback && fallback.classList.contains(\'sppb-comment-avatar-fallback\')) {
							fallback.style.display = \'flex\';
						}
					});
					
					img.addEventListener(\'load\', function() {
						// Ensure fallback is hidden when image loads successfully
						const fallback = this.nextElementSibling;
						if (fallback && fallback.classList.contains(\'sppb-comment-avatar-fallback\')) {
							fallback.style.display = \'none\';
						}
					});
				});
			};
			
			// Initialize Gravatar handling
			handleGravatarImages();
			// Function to update comment count
			const updateCommentCount = () => {
				const commentItems = document.querySelectorAll(\'.sppb-comment-item[data-parent-id=""]\');
				const count = commentItems.length;
				const countElements = document.querySelectorAll(\'.sppb-comment-count\');
				countElements.forEach(element => {
					element.textContent = \'(\' + count + \')\';
				});
			};

			// Function to count all comments (including replies)
			const updateTotalCommentCount = () => {
				const allCommentItems = document.querySelectorAll(\'.sppb-comment-item\');
				const count = allCommentItems.length;
				const countElements = document.querySelectorAll(\'.sppb-comment-count\');
				countElements.forEach(element => {
					element.textContent = \'(\' + count + \')\';
				});
			};

			// Function to update reply count for a specific comment
			const updateReplyCount = (commentId) => {
				const $commentItem = document.querySelector(`[data-comment-id="${commentId}"]`);
				if (!$commentItem) return;
				
				const $childrenWrapper = $commentItem.querySelector(\'.sppb-comment-children-wrapper\');
				const $replyText = $commentItem.querySelector(\'.sppb-reply-text\');
				
				if ($childrenWrapper && $replyText) {
					const replyCount = $childrenWrapper.querySelectorAll(`.sppb-comment-item[data-parent-id="${commentId}"]`).length;
					$replyText.textContent = replyCount + \' reply\';
				}
			};

			document.addEventListener("submit", function(event) {
				if (event.target.classList.contains("sppb-comment-form")) {
					event.preventDefault();
					
					const form = event.target;
					const formData = new FormData(form);
					const commentContent = formData.get("comment");
					const enableAnonymousComment = formData.get("enable-anonymous-comment") == "1";
					const ajaxData = $(form).serializeArray();

					const apiUrl = "'.Uri::root().'index.php?option=com_sppagebuilder&task=comment.comments";

					const requestData = {
						_method: "POST",
						comment: {
							content: commentContent,
							item_id: ' . $itemId . ',
							source_type: "'. $collectionType .'",
							parent_id: null,
							enable_anonymous_comment: enableAnonymousComment
						}
					};

					const ajaxRequestData = {
						option: "com_sppagebuilder",
						task: "ajax",
						addon: "comment",
						data: ajaxData,
					};

					$.ajax({
						type: "POST",
						data: ajaxRequestData,
						success: function (response) {
							const parsedResponse = JSON.parse(response);
							const data = JSON.parse(parsedResponse.data);
							if (data?.status === true) {
								hasRateLimit = false;
								createComment(apiUrl, requestData);
							}
							return;
						},
					});

					return false;
				}
			});

			function createComment(apiUrl, requestData) {
				fetch(apiUrl, {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
					},
					body: JSON.stringify(requestData)
				})
				.then(response => response.json())
				.then(data => {
					if (data?.success && !data?.data?.error) {
						const newComment = data.data?.data || "";

						if (!newComment) {
							return;
						}

						// Check if comment needs moderation (user\'s own comment that is unpublished)
						const needsModeration = (!newComment.created_by_id || (newComment.created_by_id && newComment.created_by_id === ' . $currentUserId . ')) && newComment.published === 0;

						const parentId = newComment.parent_id || null;
						const commentEntry = renderComment(newComment, needsModeration);

						if (parentId) {
							const parentComment = document.querySelector(`.sppb-comment-item [data-comment-id="${parentId}"]`);
							if (parentComment) {
								const repliesContainer = parentComment.querySelector(".sppb-comment-children-wrapper");
								if (repliesContainer) {
									repliesContainer.insertAdjacentHTML("afterbegin", commentEntry);
								}
							} else {
								alert("Parent comment not found.");
							}
						} else {
							const commentsList = document.querySelector(".sppb-comments-list");
							if (commentsList) {
								commentsList.insertAdjacentHTML("afterbegin", commentEntry);
							}
						}

						const commentInput = document.querySelector(".sppb-comment-field");
						if (commentInput) {
							commentInput.value = "";
						}

						// Update total comment count
						updateCommentCount();
						
						// Handle Gravatar for new comment
						handleGravatarImages();

					} else {
						alert("Error: " + data?.data?.error);
					}
				})
				.catch(error => {
					alert("An error occurred while submitting the comment: " + error.message);
				});
			}

			$(document).on("click", ".sppb-comment-reply-action", function (event) {
				event.preventDefault();
				let $self = $(this);
				let commentId = $self.data("comment-id");

				$(".sppb-comment-reply-form").hide();

				const $replyForm = $self.closest(".sppb-comment-item").find(`.sppb-comment-reply-form[data-comment-id="${commentId}"]`);
				$replyForm.show();

				$replyForm.find(".sppb-comment-reply-input").focus();
			});

			// Handle reply form submission
			$(document).on("click", ".sppb-comment-reply-submit-btn", function (event) {
				event.preventDefault();
				let $self = $(this);
				let commentId = $self.data("comment-id");
				let replyContent = $self.closest(".sppb-comment-item").find(".sppb-comment-reply-input").val();
				
				if (!replyContent.trim()) {
					alert("Please enter a reply.");
					return;
				}

				// Get the anonymous comment setting from the main form
				const enableAnonymousComment = $self.closest(".sppb-addon-comment").find("form.sppb-comment-form").find("input[name=\'enable-anonymous-comment\']")?.[0]?.value == "1";

				const apiUrl = "'.Uri::root().'index.php?option=com_sppagebuilder&task=comment.comments";

				const requestData = {
					_method: "POST",
					comment: {
						content: replyContent,
						item_id: ' . $itemId . ',
						parent_id: commentId,
						source_type: "'. $collectionType .'",
						enable_anonymous_comment: enableAnonymousComment
					}
				};

				fetch(apiUrl, {
					method: "POST",
					headers: {
						"Content-Type": "application/json",
					},
					body: JSON.stringify(requestData)
				})
				.then(response => response.json())
				.then(data => {
					if (data?.success && !data?.data?.error) {
						const newReply = data.data?.data || "";

						if (!newReply) {
							return;
						}

						// Check if reply needs moderation (user\'s own reply that is unpublished)
						const needsModeration = (!newReply.created_by_id || (newReply.created_by_id && newReply.created_by_id === ' . $currentUserId . ')) && newReply.published === 0;

						const replyEntry = renderComment(newReply, needsModeration);
						const $parentComment = $self.closest(".sppb-comment-item");

						let $repliesContainer = $parentComment.find(".sppb-comment-children-wrapper");
						if ($repliesContainer?.length === 0) {
							$repliesContainer = $("<div class=\"sppb-comment-children-wrapper\"></div>");
							$parentComment.append($repliesContainer);
						}
						
						$($repliesContainer?.[0])?.prepend(replyEntry);

						$self.closest(".sppb-comment-item").find(".sppb-comment-reply-input").val("");
						$self.closest(".sppb-comment-item").find(".sppb-comment-reply-form").hide();

						// Update reply count for the parent comment
						updateReplyCount(newReply.parent_id);

						// Update total comment count
						updateTotalCommentCount();
						
						// Handle Gravatar for new reply
						handleGravatarImages();

					} else {
						alert("Error: " + data?.data?.error);
					}
				})
				.catch(error => {
					alert("An error occurred while submitting the reply: " + error.message);
				});
			});

			$(document).on("click", ".sppb-comment-reply-cancel-btn", function (event) {
				event.preventDefault();
				let $self = $(this);
				let commentId = $self.data("comment-id");
				$self.closest(".sppb-comment-item").find(".sppb-comment-reply-form").hide();
			});

			$(document).on("click", ".sppb-ellipsis-edit-btn", function (event) {
				event.preventDefault();
				let $self = $(this);
				let commentId = $self.closest(".sppb-comment-item").data("comment-id");

				$self.closest(".sppb-ellipsis-dropdown").hide();

				const $commentItem = $self.closest(".sppb-comment-item");
				const $editForm = $commentItem.find(`.sppb-comment-edit-form[data-comment-id=${commentId}]`);
				const $contentText = $commentItem.find(`.sppb-comment-content[data-comment-id=${commentId}] .sppb-comment-content-text`);
				
				$editForm.show();
				$self.closest(".sppb-comment-header").find(".sppb-comment-header-left").hide();
				$self.closest(".sppb-comment-header").find(".sppb-comment-ellipsis-action").hide();
				$contentText.hide();

				$commentItem.find(`.sppb-comment-edit-input[data-comment-id=${commentId}]`).focus();
			});

			$(document).on("click", ".sppb-comment-edit-submit-btn", function (event) {
				event.preventDefault();
				let $self = $(this);
				let commentId = $self.data("comment-id");
				let commentContent = $self.closest(".sppb-comment-item").find(".sppb-comment-edit-input").val();
				
				if (!commentContent.trim()) {
					alert("Please enter comment content.");
					return;
				}

				const apiUrl = "'.Uri::root().'index.php?option=com_sppagebuilder&task=comment.comments";

				$.ajax({
					url: apiUrl,
					data: {
						_method: "POST",
						comment: {
							id: commentId,
							content: commentContent
						}
					},
					success: function (response) {
						if (response.success) {
							const $commentItem = $self.closest(".sppb-comment-item[data-comment-id=\'" + commentId + "\']");
							const $editForm = $commentItem.find(".sppb-comment-edit-form");
							const $contentText = $commentItem.find(".sppb-comment-content[data-comment-id=\'" + commentId + "\'] .sppb-comment-content-text span");

							const $contentTextWrapper = $commentItem.find(`.sppb-comment-content[data-comment-id=${commentId}] .sppb-comment-content-text`);

							$editForm.hide();
							$commentItem.find(".sppb-comment-header .sppb-comment-header-left").show();
							$commentItem.find(".sppb-comment-header .sppb-comment-ellipsis-action").show();
							$commentItem.find(".sppb-comment-content").show();
							
							const formattedContent = commentContent.replace(/\\n/g, \'<br>\');
							$contentTextWrapper.show();
							$contentText.html(formattedContent);
						} else {
							alert("An error occurred while editing the comment: " + response.message);
						}
					},
					error: function (xhr, status, error) {
						alert("An error occurred while editing the comment: " + error);
					}
				});
			});

			$(document).on("click", ".sppb-comment-edit-cancel-btn", function (event) {
				event.preventDefault();
				let $self = $(this);
				let commentId = $self.data("comment-id");
				const $commentItem = $self.closest(".sppb-comment-item");
				const $editForm = $commentItem.find(".sppb-comment-edit-form");
				const $contentText = $commentItem.find(".sppb-comment-content .sppb-comment-content-text");
				
				$editForm.hide();
				$commentItem.find(".sppb-comment-header .sppb-comment-header-left").show();
				$commentItem.find(".sppb-comment-header .sppb-comment-ellipsis-action").show();
				$contentText.show();
			});

			$(document).on("click", ".sppb-ellipsis-delete-btn", function (event) {
				event.preventDefault();
				let $self = $(this);
				let $commentItem = $self.closest(".sppb-comment-item");
				let commentId = $commentItem.data("comment-id");
				let parentId = $commentItem.data("parent-id");

				if (!confirm("Are you sure you want to delete this comment?")) {
					return;
				}

				$self.closest(".sppb-ellipsis-dropdown").hide();

				const apiUrl = "'.Uri::root().'index.php?option=com_sppagebuilder&task=comment.comments";

				$.ajax({
					url: apiUrl,
					data: {
						_method: "DELETE",
						comment: {
							id: commentId
						}
					},
					success: function (response) {
						if (response.success) {
							// Remove the comment item
							$commentItem.remove();
							
							// Update reply count for parent comment if this was a reply
							if (parentId) {
								updateReplyCount(parentId);
							}
							
							// Update total comment count
							updateCommentCount();
						} else {
							alert("An error occurred while deleting the comment: " + response.message);
						}
					},
					error: function (xhr, status, error) {
						alert("An error occurred while deleting the comment: " + error);
					}
				});
			});

			$(document).on("click", ".sppb-ellipsis-icon", function (event) {
				event.preventDefault();
				event.stopPropagation();
				let $self = $(this);
				let $dropdown = $self.siblings(".sppb-ellipsis-dropdown");

				$(".sppb-ellipsis-dropdown").not($dropdown).hide();

				$dropdown.toggle();
			});

			$(document).on("click", function (event) {
				if (!$(event.target).closest(".sppb-comment-ellipsis-action").length) {
					$(".sppb-ellipsis-dropdown").hide();
				}
			});

			$(document).on("click", ".sppb-comment-likes", function (event) {
				event.preventDefault();
				let $self = $(this);
				let commentId = $self.closest(".sppb-comment-item").data("comment-id");
				let $likesText = $self.find(".sppb-likes-text");
				let currentLikes = parseInt($likesText.text().match(/\d+/)[0]) || 0;
				
				const apiUrl = "'.Uri::root().'index.php?option=com_sppagebuilder&task=comment.comments";

				$.ajax({
					url: apiUrl,
					data: {
						_method: "POST",
						comment: {
							id: commentId,
							action: "like"
						}
					},
					success: function (response) {
						if (response.success) {
							if ($self.hasClass("liked")) {
								$likesText.text((currentLikes - 1) + " likes");
								$self.removeClass("liked");
							} else {
								$likesText.text((currentLikes + 1) + " likes");
								$self.addClass("liked");
							}
						} else {
							alert(response?.data?.error || "An error occurred while liking the comment");
						}
					},
					error: function (xhr, status, error) {
						alert(xhr?.responseJSON?.data?.error || "An error occurred while liking the comment: " + error);
					}
				});
			});

			const renderComment = (commentRecord, needsModeration = false) => {
				// Helper functions to match PHP implementation
				// MD5 function for Gravatar generation
				const md5 = (string) => {
					function md5_RotateLeft(lValue, iShiftBits) {
						return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
					}
					function md5_AddUnsigned(lX, lY) {
						var lX4, lY4, lX8, lY8, lResult;
						lX8 = (lX & 0x80000000);
						lY8 = (lY & 0x80000000);
						lX4 = (lX & 0x40000000);
						lY4 = (lY & 0x40000000);
						lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
						if (lX4 & lY4) {
							return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
						}
						if (lX4 | lY4) {
							if (lResult & 0x40000000) {
								return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
							} else {
								return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
							}
						} else {
							return (lResult ^ lX8 ^ lY8);
						}
					}
					function md5_F(x, y, z) {
						return (x & y) | ((~x) & z);
					}
					function md5_G(x, y, z) {
						return (x & z) | (y & (~z));
					}
					function md5_H(x, y, z) {
						return (x ^ y ^ z);
					}
					function md5_I(x, y, z) {
						return (y ^ (x | (~z)));
					}
					function md5_FF(a, b, c, d, x, s, ac) {
						a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_F(b, c, d), x), ac));
						return md5_AddUnsigned(md5_RotateLeft(a, s), b);
					}
					function md5_GG(a, b, c, d, x, s, ac) {
						a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_G(b, c, d), x), ac));
						return md5_AddUnsigned(md5_RotateLeft(a, s), b);
					}
					function md5_HH(a, b, c, d, x, s, ac) {
						a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_H(b, c, d), x), ac));
						return md5_AddUnsigned(md5_RotateLeft(a, s), b);
					}
					function md5_II(a, b, c, d, x, s, ac) {
						a = md5_AddUnsigned(a, md5_AddUnsigned(md5_AddUnsigned(md5_I(b, c, d), x), ac));
						return md5_AddUnsigned(md5_RotateLeft(a, s), b);
					}
					function md5_ConvertToWordArray(string) {
						var lWordCount;
						var lMessageLength = string.length;
						var lNumberOfWords_temp1 = lMessageLength + 8;
						var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
						var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
						var lWordArray = Array(lNumberOfWords - 1);
						var lBytePosition = 0;
						var lByteCount = 0;
						while (lByteCount < lMessageLength) {
							lWordCount = (lByteCount - (lByteCount % 4)) / 4;
							lBytePosition = (lByteCount % 4) * 8;
							lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition));
							lByteCount++;
						}
						lWordCount = (lByteCount - (lByteCount % 4)) / 4;
						lBytePosition = (lByteCount % 4) * 8;
						lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
						lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
						lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
						return lWordArray;
					}
					function md5_WordToHex(lValue) {
						var WordToHexValue = "", WordToHexValue_temp = "", lByte, lCount;
						for (lCount = 0; lCount <= 3; lCount++) {
							lByte = (lValue >>> (lCount * 8)) & 255;
							WordToHexValue_temp = "0" + lByte.toString(16);
							WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length - 2, 2);
						}
						return WordToHexValue;
					}
					function md5_Utf8Encode(string) {
						string = string.replace(/\\r\\n/g, "\\n");
						var utftext = "";
						for (var n = 0; n < string.length; n++) {
							var c = string.charCodeAt(n);
							if (c < 128) {
								utftext += String.fromCharCode(c);
							} else if ((c > 127) && (c < 2048)) {
								utftext += String.fromCharCode((c >> 6) | 192);
								utftext += String.fromCharCode((c & 63) | 128);
							} else {
								utftext += String.fromCharCode((c >> 12) | 224);
								utftext += String.fromCharCode(((c >> 6) & 63) | 128);
								utftext += String.fromCharCode((c & 63) | 128);
							}
						}
						return utftext;
					}
					var x = Array();
					var k, AA, BB, CC, DD, a, b, c, d;
					var S11 = 7, S12 = 12, S13 = 17, S14 = 22;
					var S21 = 5, S22 = 9, S23 = 14, S24 = 20;
					var S31 = 4, S32 = 11, S33 = 16, S34 = 23;
					var S41 = 6, S42 = 10, S43 = 15, S44 = 21;
					string = md5_Utf8Encode(string);
					x = md5_ConvertToWordArray(string);
					a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
					for (k = 0; k < x.length; k += 16) {
						AA = a; BB = b; CC = c; DD = d;
						a = md5_FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
						d = md5_FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
						c = md5_FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
						b = md5_FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
						a = md5_FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
						d = md5_FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
						c = md5_FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
						b = md5_FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
						a = md5_FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
						d = md5_FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
						c = md5_FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
						b = md5_FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
						a = md5_FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
						d = md5_FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
						c = md5_FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
						b = md5_FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
						a = md5_GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
						d = md5_GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
						c = md5_GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
						b = md5_GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
						a = md5_GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
						d = md5_GG(d, a, b, c, x[k + 10], S22, 0x2441453);
						c = md5_GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
						b = md5_GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
						a = md5_GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
						d = md5_GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
						c = md5_GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
						b = md5_GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
						a = md5_GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
						d = md5_GG(d, a, b, c, x[k + 20], S22, 0xFCEFA3F8);
						c = md5_GG(c, d, a, b, x[k + 20], S23, 0x676F02D9);
						b = md5_GG(b, c, d, d, x[k + 5], S24, 0x8D2A4C8A);
						a = md5_HH(a, b, c, d, x[k + 8], S21, 0xFFFA3942);
						d = md5_HH(d, a, b, c, x[k + 11], S22, 0x8771F681);
						c = md5_HH(c, d, a, b, x[k + 14], S23, 0x6D9D6122);
						b = md5_HH(b, c, d, a, x[k + 1], S24, 0xFDE5380C);
						a = md5_HH(a, b, c, d, x[k + 4], S21, 0xA4BEEA44);
						d = md5_HH(d, a, b, c, x[k + 7], S22, 0x4BDECFA9);
						c = md5_HH(c, d, a, b, x[k + 10], S23, 0xF6BB4B60);
						b = md5_HH(b, c, d, a, x[k + 13], S24, 0xBEBFBC70);
						a = md5_HH(a, b, c, d, x[k + 0], S21, 0x289B7EC6);
						d = md5_HH(d, a, b, c, x[k + 3], S22, 0xEAA127FA);
						c = md5_HH(c, d, a, b, x[k + 6], S23, 0xD4EF3085);
						b = md5_HH(b, c, d, a, x[k + 9], S24, 0x4881D05);
						a = md5_HH(a, b, c, d, x[k + 12], S21, 0xD9D4D039);
						d = md5_HH(d, a, b, c, x[k + 15], S22, 0xE6DB99E5);
						a = md5_HH(a, b, c, d, x[k + 2], S23, 0x1FA27CF8);
						b = md5_HH(b, c, d, a, x[k + 5], S24, 0xC4AC5665);
						a = md5_II(a, b, c, d, x[k + 0], S21, 0xF4292244);
						d = md5_II(d, a, b, c, x[k + 6], S22, 0x432AFF97);
						c = md5_II(c, d, a, b, x[k + 13], S23, 0xAB9423A7);
						b = md5_II(b, c, d, a, x[k + 4], S24, 0xFC93A039);
						a = md5_II(a, b, c, d, x[k + 1], S21, 0x655B59C3);
						d = md5_II(d, a, b, c, x[k + 8], S22, 0x8F0CCC92);
						c = md5_II(c, d, a, b, x[k + 11], S23, 0xFFEFF47D);
						b = md5_II(b, c, d, a, x[k + 2], S24, 0x85845DD1);
						a = md5_II(a, b, c, d, x[k + 7], S21, 0x6FA87E4F);
						d = md5_II(d, a, b, c, x[k + 14], S22, 0xFE2CE6E0);
						c = md5_II(c, d, a, b, x[k + 5], S23, 0xA3014314);
						b = md5_II(b, c, d, a, x[k + 12], S24, 0x4E0811A1);
						a = md5_II(a, b, c, d, x[k + 9], S21, 0xF7537E82);
						d = md5_II(d, a, b, c, x[k + 15], S22, 0xBD3AF235);
						c = md5_II(c, d, a, b, x[k + 2], S23, 0x2AD7D2BB);
						b = md5_II(b, c, d, a, x[k + 0], S24, 0xEB86D391);
						a = md5_AddUnsigned(a, AA);
						b = md5_AddUnsigned(b, BB);
						c = md5_AddUnsigned(c, CC);
						d = md5_AddUnsigned(d, DD);
					}
					var temp = md5_WordToHex(a) + md5_WordToHex(b) + md5_WordToHex(c) + md5_WordToHex(d);
					return temp.toLowerCase();
				};
				
				const getInitials = (name) => {
					if (typeof name !== "string") {
						return name;
					}
					const words = name.split(\' \');
					if (words.length > 1) {
						return words[0].charAt(0).toUpperCase() + words[words.length - 1].charAt(0).toUpperCase();
					}
					return name.charAt(0).toUpperCase();
				};
				
				const getRandomColor = () => {
					const colors = [\'#3B82F6\', \'#10B981\', \'#F59E0B\', \'#EF4444\', \'#8B5CF6\', \'#06B6D4\', \'#84CC16\', \'#F97316\'];
					return colors[Math.floor(Math.random() * colors.length)];
				};
				
				const getTextColor = (bgColor) => {
					const hex = bgColor.replace(\'#\', \'\');
					const r = parseInt(hex.substr(0, 2), 16);
					const g = parseInt(hex.substr(2, 2), 16);
					const b = parseInt(hex.substr(4, 2), 16);
					const brightness = (r * 299 + g * 587 + b * 114) / 1000;
					return brightness > 128 ? \'#000000\' : \'#FFFFFF\';
				};
				
				const getRelativeTime = (dateString) => {
					const date = new Date(dateString);
					const now = new Date(dateString);
					const diff = now - date;
					const diffInSeconds = Math.floor(diff / 1000);
					
					if (diffInSeconds < 60) {
						return \'Just now\';
					} else if (diffInSeconds < 3600) {
						const minutes = Math.floor(diffInSeconds / 60);
						return minutes + \' min ago\';
					} else if (diffInSeconds < 86400) {
						const hours = Math.floor(diffInSeconds / 3600);
						return hours + \' hour\' + (hours > 1 ? \'s\' : \'\') + \' ago\';
					} else if (diffInSeconds < 2592000) {
						const days = Math.floor(diffInSeconds / 86400);
						return days + \' day\' + (days > 1 ? \'s\' : \'\') + \' ago\';
					} else if (diffInSeconds < 31536000) {
						const months = Math.floor(diffInSeconds / 2592000);
						return months + \' month\' + (months > 1 ? \'s\' : \'\') + \' ago\';
					} else {
						const years = Math.floor(diffInSeconds / 31536000);
						return years + \' year\' + (years > 1 ? \'s\' : \'\') + \' ago\';
					}
				};
				
				// Generate avatar and colors
				const avatarColor = "' . $avatarColor . '";
				const textColor = getTextColor(avatarColor);
				const commentatorName = commentRecord.created_by || \'Anonymous Person\';
				const initials = getInitials(commentatorName);
				
				// Generate time display
				const createdTime = getRelativeTime(commentRecord.created_on);
				const editedTime = commentRecord.modified && commentRecord.modified !== commentRecord.created_on ? 
					\' • <span class="sppb-comment-edited-time">Edited \' + getRelativeTime(commentRecord.modified) + \'<\/span>\' : \'\';
				
				// Get settings from the page (we\'ll need to make these available)
				const settings = window.sppbCommentSettings || {};
				const ellipsisBtnClass = \'sppb-btn sppb-btn-custom sppb-btn-rounded sppb-btn-md\';
				const ellipsisBtnIcon = \'fa fa-ellipsis-v\';
				const likesIcon = \'fa fa-heart\';
				const replyIcon = \'fa fa-reply\';
				const btnClass = \'sppb-btn sppb-btn-default sppb-btn-rounded sppb-btn-md\';
				
				// Generate ellipsis action buttons HTML
				const generateEllipsisActionButtons = () => {
					return \'<div class="sppb-comment-ellipsis-action-buttons-wrapper">\' +
						\'<button style="width: 100%; text-align: left;" class="sppb-comment-ellipsis-action-button sppb-btn sppb-btn-md sppb-btn-rounded sppb-ellipsis-edit-btn" id="sppb-comment-ellipsis-action-button-0" data-comment-id="\' + commentRecord.id + \'">Edit<\/button>\' +
						\'<button style="width: 100%; text-align: left;" class="sppb-comment-ellipsis-action-button sppb-btn sppb-btn-md sppb-btn-rounded sppb-ellipsis-delete-btn" id="sppb-comment-ellipsis-action-button-1" data-comment-id="\' + commentRecord.id + \'">Delete<\/button>\' +
						\'<\/div>\';
				};
				
				// Generate edit comment buttons HTML
				const generateEditCommentButtons = () => {
					return \'<div class="sppb-comment-edit-buttons-wrapper sppb-comment-btn-wrapper">\' +
						\'<button class="sppb-comment-edit-button sppb-comment-edit-cancel-btn sppb-btn sppb-btn-md sppb-btn-rounded" id="sppb-comment-edit-button-0" data-comment-id="\' + commentRecord.id + \'">Cancel<\/button>\' +
						\'<button class="sppb-comment-edit-button sppb-comment-edit-submit-btn sppb-btn sppb-btn-md sppb-btn-rounded" id="sppb-comment-edit-button-1" data-comment-id="\' + commentRecord.id + \'">Update<\/button>\' +
						\'<\/div>\';
				};
				
				// Calculate margin and level based on parent
				const level = commentRecord.parent_id ? 1 : 0;
				const rootCommentClass = level === 0 ? \' sppb-root-comment\' : \'\';
				const parentCommentClass = (!commentRecord.parent_id) ? \' sppb-parent-comment\' : \'\';
				const marginLeft = level > 0 ? \'55px\' : \'0px\';
				const marginTop = \'-4px\';
				const contentMarginLeft = level > 0 ? \'style="margin-left: 0; margin-top: 11px;"\' : \'\';
				
				const formattedContent = commentRecord.content.replace(/\\n/g, \'<br>\');

				// Build HTML in parts to avoid string concatenation issues
				const htmlParts = [];
				
				// Main container
				htmlParts.push(\'<div data-comment-id="\' + commentRecord.id + \'" data-parent-id="\' + (commentRecord.parent_id || "") + \'" class="sppb-comment-item sppb-comment-item \' + parentCommentClass + rootCommentClass + \'" style="margin-top: \' + marginTop + \'; margin-left: \' + marginLeft + \';">\');
				htmlParts.push(\'<div class="sppb-comment-thread-line" style="left:-34px;"><\/div>\');
				htmlParts.push(\'<div class="sppb-comment-thread-connector" style="left:-34px; width: 55px;"><\/div>\');
				htmlParts.push(\'<div class="sppb-comment-content" \' + contentMarginLeft + \'>\');
				
				// Header section
				htmlParts.push(\'<div class="sppb-comment-header sppb-comment-header">\');
				
				// Avatar section with profile image, Gravatar, and fallback logic
				if (commentRecord.profile_image) {
					htmlParts.push(\'<div class="sppb-comment-avatar" style="border-radius: 50%; width: 45px; height: 45px; margin-right: 10px; overflow: hidden; flex-shrink: 0;">\');
					htmlParts.push(\'<img src="\' + commentRecord.profile_image + \'" alt="\' + commentatorName + \'" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display=\\\'none\\\'; this.nextElementSibling.style.display=\\\'flex\\\';" />\');
					htmlParts.push(\'<div class="sppb-comment-avatar-fallback" style="background-color: \' + avatarColor + \'; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: none; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; color: \' + textColor + \'">\');
					htmlParts.push(\'<span>\' + initials + \'<\/span>\');
					htmlParts.push(\'<\/div>\');
					htmlParts.push(\'<\/div>\');
				} else if (' . (!empty($this->addon->settings->enable_gravatar) ? 'true' : 'false') . ' && commentRecord.created_by_email) {
					// Generate Gravatar URL
					const gravatarHash = md5(commentRecord.created_by_email.toLowerCase().trim());
					const gravatarUrl = \'https://www.gravatar.com/avatar/\' + gravatarHash + \'?s=90&d=404\';
					htmlParts.push(\'<div class="sppb-comment-avatar" style="border-radius: 50%; width: 45px; height: 45px; margin-right: 10px; overflow: hidden; flex-shrink: 0;">\');
					htmlParts.push(\'<img src="\' + gravatarUrl + \'" alt="\' + commentatorName + \'" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display=\\\'none\\\'; this.nextElementSibling.style.display=\\\'flex\\\';" />\');
					htmlParts.push(\'<div class="sppb-comment-avatar-fallback" style="background-color: \' + avatarColor + \'; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: none; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; color: \' + textColor + \'">\');
					htmlParts.push(\'<span>\' + initials + \'<\/span>\');
					htmlParts.push(\'<\/div>\');
					htmlParts.push(\'<\/div>\');
				} else {
					// Fallback to initials only
					htmlParts.push(\'<div class="sppb-comment-avatar" style="background-color: \' + avatarColor + \'; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px; font-weight: 600; margin-right: 10px; color: \' + textColor + \'">\');
					htmlParts.push(\'<span>\' + initials + \'<\/span>\');
					htmlParts.push(\'<\/div>\');
				}
				
				// Header left content
				htmlParts.push(\'<div class="sppb-comment-header-left">\');
				htmlParts.push(\'<div class="commentator-name-wrapper">\');
				htmlParts.push(\'<span class="commentator-name">\' + commentatorName + \'<\/span>\');
				htmlParts.push(\'<\/div>\');
				htmlParts.push(\'<div class="sppb-comment-time-wrapper">\');
				htmlParts.push(\'<span class="sppb-comment-time">\' + createdTime + editedTime + \'<\/span>\');
				htmlParts.push(\'<\/div>\');
				htmlParts.push(\'<\/div>\');
				
				// Ellipsis action
				htmlParts.push(\'<div class="sppb-comment-ellipsis-action \' + ellipsisBtnClass + \'">\');
				htmlParts.push(\'<i class="sppb-ellipsis-icon \' + ellipsisBtnIcon + \'"><\/i>\');
				htmlParts.push(\'<div class="sppb-ellipsis-dropdown" style="display: none;">\');
				htmlParts.push(generateEllipsisActionButtons());
				htmlParts.push(\'<\/div>\');
				htmlParts.push(\'<\/div>\');
				htmlParts.push(\'<\/div>\');
				
				// Content section
				htmlParts.push(\'<div class="sppb-comment-content sppb-comment-body" data-comment-id="\' + commentRecord.id + \'" style="margin-left: 55px; padding-bottom: 29px;">\');
				htmlParts.push(\'<div class="sppb-comment-body-thread-line"><\/div>\');
				
				// Add moderation notice if needed
				if (needsModeration) {
					htmlParts.push(\'<div class="sppb-comment-unpublished-notice">' . Text::_('COM_SPPAGEBUILDER_COMMENT_UNAPPROVED_NOTICE') . '<\/div>\');
				}
				
				htmlParts.push(\'<span class="sppb-comment-content-text\' + (needsModeration ? \' sppb-comment-unpublished\' : \'\') + \'" style="margin-left: 10px; margin-top: 20px; display: inline-block;">\');
				htmlParts.push(\'<span>\' + formattedContent + \'<\/span>\');
				htmlParts.push(\'<\/span>\');
				
				// Edit form
				htmlParts.push(\'<div class="sppb-comment-edit-form sppb-comment-form" data-comment-id="\' + commentRecord.id + \'" style="display: none; \' + (needsModeration ? \'margin-top: -45px;\' : \'\') + \'">\');
				htmlParts.push(\'<textarea type="text" class="sppb-comment-edit-input sppb-comment-field" data-comment-id="\' + commentRecord.id + \'">\' + commentRecord.content + \'<\/textarea>\');
				htmlParts.push(generateEditCommentButtons());
				htmlParts.push(\'<\/div>\');
				
				// Actions section - only show if not needing moderation
				if (!needsModeration) {
					htmlParts.push(\'<div class="sppb-comment-actions" style="margin-left: 10px">\');
					htmlParts.push(\'<div class="sppb-comment-likes\' + (commentRecord.user_liked ? \' liked\' : \'\') + \'">\');
					htmlParts.push(\'<i class="sppb-likes-icon \' + likesIcon + \'"><\/i>\');
					htmlParts.push(\'<span class="sppb-likes-text">\' + (commentRecord.likes_count || 0) + \' likes<\/span>\');
					htmlParts.push(\'<\/div>\');
					htmlParts.push(\'<div class="sppb-comment-reply-action" data-comment-id="\' + commentRecord.id + \'">\');
					htmlParts.push(\'<i class="sppb-reply-icon \' + replyIcon + \'"><\/i>\');
					htmlParts.push(\'<span class="sppb-reply-text">\' + (commentRecord.replies || 0) + \' reply<\/span>\');
					htmlParts.push(\'<\/div>\');
					htmlParts.push(\'<\/div>\');
				}
				
				// Reply form
				htmlParts.push(\'<div class="sppb-comment-reply-form sppb-comment-form" data-comment-id="\' + commentRecord.id + \'" style="display: none;">\');
				htmlParts.push(\'<textarea type="text" class="sppb-comment-reply-input sppb-comment-field" data-comment-id="\' + commentRecord.id + \'" placeholder="Write a reply..."><\/textarea>\');
				htmlParts.push(\'<div class="sppb-comment-btn-wrapper">\');
				htmlParts.push(\'<button class="sppb-comment-reply-cancel-btn \' + btnClass + \'" data-comment-id="\' + commentRecord.id + \'">Cancel<\/button>\');
				htmlParts.push(\'<button class="sppb-comment-reply-submit-btn comment-submit-btn \' + btnClass + \'" data-comment-id="\' + commentRecord.id + \'">Submit<\/button>\');
				htmlParts.push(\'<\/div>\');
				htmlParts.push(\'<\/div>\');
				
				// Close content section
				htmlParts.push(\'<\/div>\');
				htmlParts.push(\'<\/div>\');
				htmlParts.push(\'<\/div>\');
				
				const html = htmlParts.join(\'\');
				return html;
			};
		});';

		return $js;
	}

	/**
	 * Generate the lodash template string for the frontend editor.
	 *
	 * @return 	string 	The lodash template string.
	 * @since 	1.0.0
	 */
	public static function getTemplate()
	{
		$lodash = new Lodash('#sppb-addon-{{ data.id }}');
		$enableGravatar = ComponentHelper::getParams('com_sppagebuilder')->get('enable_gravatar', 1);
		$output = '<style type="text/css">';
		
		// Title wrapper styles
		$output .= '.sppb-comment-title-wrapper {display: flex; align-items: center; gap: 16px; border-bottom: 1px solid #D3D7EB; margin-bottom: 40px; padding-bottom: 24px;}';
		$output .= $lodash->alignment('justify-content', '.sppb-comment-title-wrapper', 'data.title_alignment');
		
		// Title text typography and styles
		$typographyFallbacks = [
			'font'           => 'data.title_font_family',
			'size'           => 'data.title_fontsize',
			'line_height'    => 'data.title_lineheight',
			'letter_spacing' => 'data.title_letterspace',
			'uppercase'      => 'data.title_font_style?.uppercase',
			'italic'         => 'data.title_font_style?.italic',
			'underline'      => 'data.title_font_style?.underline',
			'weight'         => 'data.title_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-comment-title-text', 'data.title_typography', $typographyFallbacks);
		$output .= $lodash->color('color', '.sppb-comment-title-text', 'data.title_color');
		$output .= $lodash->textShadow('.sppb-comment-title-text', 'data.title_shadow');
		
		// Comment count styles
		$countTypographyFallbacks = [
			'font'           => 'data.comment_count_font_family',
			'size'           => 'data.comment_count_fontsize',
			'line_height'    => 'data.comment_count_lineheight',
			'letter_spacing' => 'data.comment_count_letterspace',
			'uppercase'      => 'data.comment_count_font_style?.uppercase',
			'italic'         => 'data.comment_count_font_style?.italic',
			'underline'      => 'data.comment_count_font_style?.underline',
			'weight'         => 'data.comment_count_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-comment-count', 'data.comment_count_typography', $countTypographyFallbacks);
		$output .= $lodash->color('color', '.sppb-comment-count', 'data.comment_count_color');
		$output .= $lodash->textShadow('.sppb-comment-count', 'data.comment_count_shadow');

		// Label wrapper styles
		$output .= '.sppb-comment-label-wrapper {display: flex; align-items: center; margin-bottom: 12px;}';
		$output .= $lodash->alignment('justify-content', '.sppb-comment-label-wrapper', 'data.label_alignment');
		$output .= $lodash->color('color', '.sppb-comment-label-text', 'data.label_color');
		$output .= $lodash->textShadow('.sppb-comment-label-text', 'data.label_text_shadow');

		// Comment form styles
		$output .= '.sppb-comment-form {display: flex; flex-direction: column;}';
		$output .= '.sppb-comment-reply-form {margin-top: 16px;}';

		// Comment field styles
		$output .= '.sppb-comment-field {transition: all 0.05s ease-in-out; border-radius: 6px; border-bottom-left-radius: 0; border-bottom-right-radius: 0; width: 100%; box-sizing: border-box;}';
		$output .= $lodash->unit('height', '.sppb-comment-field', 'data.comment_field_height');
		$output .= $lodash->spacing('padding', '.sppb-comment-field', 'data.comment_field_padding');
		$output .= $lodash->color('color', '.sppb-comment-field', 'data.comment_field_typing_color');
		$output .= $lodash->color('background-color', '.sppb-comment-field', 'data.comment_field_background_color');
		$output .= $lodash->unit('border-color', '.sppb-comment-field', 'data.comment_field_border_color');
		$output .= $lodash->unit('border-width', '.sppb-comment-field', 'data.comment_field_border_width');
		$output .= $lodash->color('color', '.sppb-comment-field::placeholder', 'data.comment_field_placeholder_color');
		$output .= $lodash->color('background-color', '.sppb-comment-field:hover', 'data.comment_field_background_hover_color');
		$output .= $lodash->color('color', '.sppb-comment-field:hover::placeholder', 'data.comment_field_placeholder_hover_color');
		$output .= $lodash->unit('border-color', '.sppb-comment-field:focus', 'data.comment_field_border_focus_color');
		
		$fieldTypographyFallbacks = [
			'font'           => 'data.comment_field_font_family',
			'size'           => 'data.comment_field_fontsize',
			'line_height'    => 'data.comment_field_lineheight',
			'letter_spacing' => 'data.comment_field_letterspace',
			'uppercase'      => 'data.comment_field_font_style?.uppercase',
			'italic'         => 'data.comment_field_font_style?.italic',
			'underline'      => 'data.comment_field_font_style?.underline',
			'weight'         => 'data.comment_field_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-comment-field', 'data.comment_field_typography', $fieldTypographyFallbacks);

		// Comment header styles
		$output .= '.sppb-comment-header {display: flex; justify-content: space-between; align-items: center; position: relative;}';
		$output .= '.sppb-comment-header-left {display: flex; flex-direction: column; gap: 4px; flex: 1;}';

		// Ellipsis action styles
		$output .= '.sppb-comment-ellipsis-action {position: relative; cursor: inherit;}';
		$output .= '.sppb-comment-ellipsis-action i {cursor: pointer;}';
		$output .= $lodash->color('background-color', '.sppb-comment-ellipsis-action', 'data.ellipsis_icon_bg_color');
		$output .= '.sppb-ellipsis-icon {font-size: 16px; padding: 8px; border-radius: 50%; transition: all 0.3s ease;}';
		$output .= $lodash->color('color', '.sppb-ellipsis-icon', 'data.ellipsis_icon_color');
		$output .= $lodash->color('background-color', '.sppb-ellipsis-icon', 'data.ellipsis_icon_bg_color');
		$output .= '.sppb-ellipsis-dropdown {position: absolute; top: 100%; right: 0; background: white; border: 1px solid #ddd; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 1000; min-width: 120px;}';

		// Comment actions styles
		$output .= '.sppb-comment-actions {display: flex; align-items: center; gap: 20px; margin-top: 16px;}';
		$output .= '.sppb-comment-likes {display: flex; align-items: center; gap: 6px; cursor: pointer;}';
		$output .= '.sppb-comment-reply-action {display: flex; align-items: center; gap: 6px; cursor: pointer;}';

		// Likes functionality styles
		$output .= $lodash->typography('.sppb-likes-text', 'data.likes_typography', [
			'font'           => 'data.likes_font_family',
			'size'           => 'data.likes_fontsize',
			'line_height'    => 'data.likes_lineheight',
			'letter_spacing' => 'data.likes_letterspace',
			'uppercase'      => 'data.likes_font_style?.uppercase',
			'italic'         => 'data.likes_font_style?.italic',
			'underline'      => 'data.likes_font_style?.underline',
			'weight'         => 'data.likes_font_style?.weight'
		]);
		$output .= $lodash->color('color', '.sppb-likes-text', 'data.likes_color');
		$output .= $lodash->color('color', '.sppb-comment-likes.liked > .sppb-likes-icon', 'data.likes_focused_color');
		$output .= $lodash->color('color', '.sppb-likes-icon', 'data.likes_color');
		$output .= '.sppb-likes-icon {font-size: 16px; transition: color 0.3s ease;}';
		$output .= $lodash->color('color', '.sppb-comment-likes:hover .sppb-likes-text', 'data.likes_hover_color');
		$output .= $lodash->color('color', '.sppb-comment-likes:hover .sppb-likes-icon', 'data.likes_hover_color');
		$output .= $lodash->color('color', '.sppb-comment-likes:focus .sppb-likes-text', 'data.likes_focused_color');
		$output .= $lodash->color('color', '.sppb-comment-likes:focus .sppb-likes-icon', 'data.likes_focused_color');
		$output .= $lodash->textShadow('.sppb-likes-text', 'data.likes_text_shadow');

		// Reply functionality styles
		$output .= $lodash->typography('.sppb-reply-text', 'data.reply_typography', [
			'font'           => 'data.reply_font_family',
			'size'           => 'data.reply_fontsize',
			'line_height'    => 'data.reply_lineheight',
			'letter_spacing' => 'data.reply_letterspace',
			'uppercase'      => 'data.reply_font_style?.uppercase',
			'italic'         => 'data.reply_font_style?.italic',
			'underline'      => 'data.reply_font_style?.underline',
			'weight'         => 'data.reply_font_style?.weight'
		]);
		$output .= $lodash->color('color', '.sppb-reply-text', 'data.reply_color');
		$output .= $lodash->color('color', '.sppb-reply-icon', 'data.reply_color');
		$output .= '.sppb-reply-icon {font-size: 16px; transition: color 0.3s ease;}';
		$output .= $lodash->color('color', '.sppb-comment-reply-action:hover .sppb-reply-text', 'data.reply_hover_color');
		$output .= $lodash->color('color', '.sppb-comment-reply-action:hover .sppb-reply-icon', 'data.reply_hover_color');
		$output .= $lodash->textShadow('.sppb-reply-text', 'data.reply_text_shadow');

		// Icon position handling
		$output .= '<# if (data.likes_icon_position === "right") { #>';
		$output .= '.sppb-comment-likes {flex-direction: row-reverse;}';
		$output .= '<# } #>';
		$output .= '<# if (data.reply_icon_position === "right") { #>';
		$output .= '.sppb-comment-reply-action {flex-direction: row-reverse;}';
		$output .= '<# } #>';

		// Commentator name styles
		$output .= '.commentator-name-wrapper {display: flex; align-items: center;}';
		$output .= $lodash->alignment('justify-content', '.commentator-name-wrapper', 'data.commentator_name_alignment');
		
		$nameTypographyFallbacks = [
			'font'           => 'data.commentator_name_font_family',
			'size'           => 'data.commentator_name_fontsize',
			'line_height'    => 'data.commentator_name_lineheight',
			'letter_spacing' => 'data.commentator_name_letterspace',
			'uppercase'      => 'data.commentator_name_font_style?.uppercase',
			'italic'         => 'data.commentator_name_font_style?.italic',
			'underline'      => 'data.commentator_name_font_style?.underline',
			'weight'         => 'data.commentator_name_font_style?.weight'
		];
		$output .= $lodash->typography('.commentator-name', 'data.commentator_name_typography', $nameTypographyFallbacks);
		$output .= $lodash->color('color', '.commentator-name', 'data.commentator_name_color');
		$output .= $lodash->textShadow('.commentator-name', 'data.commentator_name_text_shadow');
		
		// Gravatar avatar styles
		$output .= '.sppb-comment-avatar img {transition: opacity 0.3s ease;}';
		$output .= '.sppb-comment-avatar-fallback {transition: opacity 0.3s ease;}';

		// Comment content styles
		$contentTypographyFallbacks = [
			'font'           => 'data.posted_comment_font_family',
			'size'           => 'data.posted_comment_fontsize',
			'line_height'    => 'data.posted_comment_lineheight',
			'letter_spacing' => 'data.posted_comment_letterspace',
			'uppercase'      => 'data.posted_comment_font_style?.uppercase',
			'italic'         => 'data.posted_comment_font_style?.italic',
			'underline'      => 'data.posted_comment_font_style?.underline',
			'weight'         => 'data.posted_comment_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-comment-content-text', 'data.posted_comment_typography', $contentTypographyFallbacks);
		$output .= $lodash->color('color', '.sppb-comment-content-text', 'data.posted_comment_color');
		$output .= $lodash->textShadow('.sppb-comment-content-text', 'data.posted_comment_text_shadow');

		// Time styles
		$timeTypographyFallbacks = [
			'font'           => 'data.time_font_family',
			'size'           => 'data.time_fontsize',
			'line_height'    => 'data.time_lineheight',
			'letter_spacing' => 'data.time_letterspace',
			'uppercase'      => 'data.time_font_style?.uppercase',
			'italic'         => 'data.time_font_style?.italic',
			'underline'      => 'data.time_font_style?.underline',
			'weight'         => 'data.time_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-comment-time', 'data.time_typography', $timeTypographyFallbacks);
		$output .= $lodash->color('color', '.sppb-comment-time', 'data.time_color');
		$output .= $lodash->textShadow('.sppb-comment-time', 'data.time_text_shadow');
		$output .= $lodash->alignment('text-align', '.sppb-comment-time-wrapper', 'data.time_alignment');

		// Edited time styles
		$editedTimeTypographyFallbacks = [
			'font'           => 'data.edited_time_font_family',
			'size'           => 'data.edited_time_fontsize',
			'line_height'    => 'data.edited_time_lineheight',
			'letter_spacing' => 'data.edited_time_letterspace',
			'uppercase'      => 'data.edited_time_font_style?.uppercase',
			'italic'         => 'data.edited_time_font_style?.italic',
			'underline'      => 'data.edited_time_font_style?.underline',
			'weight'         => 'data.edited_time_font_style?.weight'
		];
		$output .= $lodash->typography('.sppb-comment-edited-time', 'data.edited_time_typography', $editedTimeTypographyFallbacks);
		$output .= $lodash->color('color', '.sppb-comment-edited-time', 'data.edited_time_color');
		$output .= $lodash->textShadow('.sppb-comment-edited-time', 'data.edited_time_text_shadow');
		$output .= $lodash->alignment('text-align', '.sppb-comment-edited-time-wrapper', 'data.edited_time_alignment');

		// Button styles
		$output .= '.comment-submit-btn {outline: none; cursor: pointer;}';
		$buttonTypographyFallbacks = [
			'font'           => 'data.post_button_font_family',
			'size'           => 'data.post_button_fontsize',
			'line_height'    => 'data.post_button_lineheight',
			'letter_spacing' => 'data.post_button_letterspace',
			'uppercase'      => 'data.post_button_font_style?.uppercase',
			'italic'         => 'data.post_button_font_style?.italic',
			'underline'      => 'data.post_button_font_style?.underline',
			'weight'         => 'data.post_button_font_style?.weight'
		];
		$output .= $lodash->typography('.comment-submit-btn', 'data.post_button_typography', $buttonTypographyFallbacks);
		$output .= '.sppb-comment-form .sppb-comment-btn-wrapper {display: flex; padding: 16px; gap: 8px; align-items: center; border-width: 0px 1px 1px 1px; border-style: solid; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px;}';
		$output .= $lodash->unit('border-color', '.sppb-comment-form .sppb-comment-btn-wrapper', 'data.comment_field_border_color');
		$output .= '.sppb-comment-btn-wrapper {display: flex; align-items: center;}';
		$output .= $lodash->alignment('justify-content', '.sppb-comment-btn-wrapper', 'data.post_button_alignment');

		// Edit comment buttons styles
		$output .= '.sppb-comment-edit-buttons-wrapper {display: flex; align-items: center;}';
		$output .= $lodash->alignment('justify-content', '.sppb-comment-edit-buttons-wrapper', 'data.edit_comment_button_alignment');
		$output .= $lodash->spacing('gap', '.sppb-comment-edit-buttons-wrapper', 'data.edit_comment_button_gap');

		// Edit comment button typography
		$output .= '<# if (data.edit_comment_button_item && data.edit_comment_button_item.length > 0) { #>';
		$output .= '<# data.edit_comment_button_item.forEach(function(item, index) { #>';
		$output .= $lodash->typography('#sppb-comment-edit-button-{{index}}', 'item.edit_comment_button_typography', [
			'font'           => 'item.edit_comment_button_font_family',
			'size'           => 'item.edit_comment_button_fontsize',
			'line_height'    => 'item.edit_comment_button_lineheight',
			'letter_spacing' => 'item.edit_comment_button_letterspace',
			'uppercase'      => 'item.edit_comment_button_font_style?.uppercase',
			'italic'         => 'item.edit_comment_button_font_style?.italic',
			'underline'      => 'item.edit_comment_button_font_style?.underline',
			'weight'         => 'item.edit_comment_button_font_style?.weight'
		]);
		$output .= '<# }); #>';
		$output .= '<# } #>';

		// Post button custom size handling - ONLY apply to custom button type
		$output .= '<# if (data.post_button_size === "custom") { #>';
		$output .= $lodash->spacing('padding', '.comment-submit-btn.sppb-btn-custom', 'data.post_button_padding');
		$output .= '<# } #>';

		// Post button custom styles - ONLY apply to custom button type
		$output .= $lodash->color('color', '.comment-submit-btn.sppb-btn-custom', 'data.post_button_color');
		$output .= $lodash->color('background-color', '.comment-submit-btn.sppb-btn-custom', 'data.post_button_background_color');
		$output .= $lodash->color('color', '.comment-submit-btn.sppb-btn-custom:hover', 'data.post_button_color_hover');
		$output .= $lodash->color('background-color', '.comment-submit-btn.sppb-btn-custom:hover', 'data.post_button_background_color_hover');

		// Post button outline styles - ONLY apply to custom button type
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-outline {background-color: transparent;}';
		$output .= $lodash->unit('border-color', '.comment-submit-btn.sppb-btn-custom.sppb-btn-outline', 'data.post_button_background_color');
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-outline:hover {background-color: transparent;}';
		$output .= $lodash->unit('border-color', '.comment-submit-btn.sppb-btn-custom.sppb-btn-outline:hover', 'data.post_button_background_color_hover');

		// Post button link styles
		$output .= $lodash->color('color', '.comment-submit-btn.sppb-btn-link', 'data.post_button_link_color');
		$output .= $lodash->unit('border-color', '.comment-submit-btn.sppb-btn-link', 'data.post_button_link_border_color');
		$output .= $lodash->unit('border-width', '.comment-submit-btn.sppb-btn-link', 'data.post_button_link_border_width', '0 0 %spx 0');
		$output .= $lodash->unit('padding', '.comment-submit-btn.sppb-btn-link', 'data.post_button_link_padding_bottom', '0 0 %spx 0');
		$output .= $lodash->color('color', '.comment-submit-btn.sppb-btn-link:hover', 'data.post_button_link_hover_color');
		$output .= $lodash->unit('border-color', '.comment-submit-btn.sppb-btn-link:hover', 'data.post_button_link_border_hover_color');

		// Post button gradient styles - ONLY apply to custom button type
		$output .= '<# if (data.post_button_background_gradient) { #>';
		$output .= '<# if (typeof data.post_button_background_gradient === "string") { #>';
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient {border: none; background-image: {{data.post_button_background_gradient}};}';
		$output .= '<# } else if (data.post_button_background_gradient.type) { #>';
		$output .= '<# let color1 = data.post_button_background_gradient.color || "#398AF1"; #>';
		$output .= '<# let color2 = data.post_button_background_gradient.color2 || "#5EDCED"; #>';
		$output .= '<# if (data.post_button_background_gradient.type === "linear") { #>';
		$output .= '<# let start = data.post_button_background_gradient.pos || 0; #>';
		$output .= '<# let end = data.post_button_background_gradient.pos2 || 100; #>';
		$output .= '<# let deg = data.post_button_background_gradient.deg || 0; #>';
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient {border: none; background-image: linear-gradient({{deg}}deg, {{color1}} {{start}}%, {{color2}} {{end}}%);}';
		$output .= '<# } else if (data.post_button_background_gradient.type === "radial") { #>';
		$output .= '<# let start = data.post_button_background_gradient.pos || 0; #>';
		$output .= '<# let end = data.post_button_background_gradient.pos2 || 100; #>';
		$output .= '<# let position = data.post_button_background_gradient.radialPos || "center center"; #>';
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient {border: none; background-image: radial-gradient(at {{position}}, {{color1}} {{start}}%, {{color2}} {{end}}%);}';
		$output .= '<# } else { #>';
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient {border: none; background-image: {{color1}};}';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# if (data.post_button_background_gradient_hover) { #>';
		$output .= '<# if (typeof data.post_button_background_gradient_hover === "string") { #>';
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient:hover {border: none; background-image: {{data.post_button_background_gradient_hover}};}';
		$output .= '<# } else if (data.post_button_background_gradient_hover.type) { #>';
		$output .= '<# let hoverColor1 = data.post_button_background_gradient_hover.color || "#398AF1"; #>';
		$output .= '<# let hoverColor2 = data.post_button_background_gradient_hover.color2 || "#5EDCED"; #>';
		$output .= '<# if (data.post_button_background_gradient_hover.type === "linear") { #>';
		$output .= '<# let hoverStart = data.post_button_background_gradient_hover.pos || 0; #>';
		$output .= '<# let hoverEnd = data.post_button_background_gradient_hover.pos2 || 100; #>';
		$output .= '<# let hoverDeg = data.post_button_background_gradient_hover.deg || 0; #>';
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient:hover {border: none; background-image: linear-gradient({{hoverDeg}}deg, {{hoverColor1}} {{hoverStart}}%, {{hoverColor2}} {{hoverEnd}}%);}';
		$output .= '<# } else if (data.post_button_background_gradient_hover.type === "radial") { #>';
		$output .= '<# let hoverStart = data.post_button_background_gradient_hover.pos || 0; #>';
		$output .= '<# let hoverEnd = data.post_button_background_gradient_hover.pos2 || 100; #>';
		$output .= '<# let hoverPosition = data.post_button_background_gradient_hover.radialPos || "center center"; #>';
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient:hover {border: none; background-image: radial-gradient(at {{hoverPosition}}, {{hoverColor1}} {{hoverStart}}%, {{hoverColor2}} {{hoverEnd}}%);}';
		$output .= '<# } else { #>';
		$output .= '.comment-submit-btn.sppb-btn-custom.sppb-btn-gradient:hover {border: none; background-image: {{hoverColor1}};}';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# } #>';

		// Button icon margin
		$output .= $lodash->spacing('margin', '.sppb-btn-icon', 'data.post_button_icon_margin');

		// Ellipsis action button styles with proper fallbacks
		$output .= $lodash->typography('.sppb-ellipsis-edit-btn, .sppb-ellipsis-delete-btn', 'data.ellipsis_action_button_typography', [
			'font'           => 'data.ellipsis_action_button_font_family',
			'size'           => 'data.ellipsis_action_button_fontsize',
			'line_height'    => 'data.ellipsis_action_button_lineheight',
			'letter_spacing' => 'data.ellipsis_action_button_letterspace',
			'uppercase'      => 'data.ellipsis_action_button_font_style?.uppercase',
			'italic'         => 'data.ellipsis_action_button_font_style?.italic',
			'underline'      => 'data.ellipsis_action_button_font_style?.underline',
			'weight'         => 'data.ellipsis_action_button_font_style?.weight'
		]);

		// Ellipsis button specific styles
		$output .= '.sppb-ellipsis-edit-btn:first-child {border-radius: 6px 6px 0 0;}';
		$output .= '.sppb-ellipsis-delete-btn:last-child {border-radius: 0 0 6px 6px;}';
		$output .= '.sppb-ellipsis-edit-btn:only-child {border-radius: 6px;}';

		// Ellipsis action button specific colors and backgrounds
		$output .= '<# if (data.ellipsis_action_button_item && data.ellipsis_action_button_item.length > 0) { #>';
		$output .= '<# data.ellipsis_action_button_item.forEach(function(item, index) { #>';
		$output .= $lodash->color('color', '.sppb-ellipsis-action-button:nth-child({{index + 1}})', 'item.ellipsis_action_button_color');
		$output .= $lodash->color('background-color', '.sppb-ellipsis-action-button:nth-child({{index + 1}})', 'item.ellipsis_action_button_background_color');
		$output .= $lodash->color('color', '.sppb-ellipsis-action-button:nth-child({{index + 1}}):hover', 'item.ellipsis_action_button_color_hover');
		$output .= $lodash->color('background-color', '.sppb-ellipsis-action-button:nth-child({{index + 1}}):hover', 'item.ellipsis_action_button_background_color_hover');
		$output .= '<# }); #>';
		$output .= '<# } #>';

		// Ellipsis action button gradient styles
		$output .= '<# if (data.ellipsis_action_button_item && data.ellipsis_action_button_item.length > 0) { #>';
		$output .= '<# data.ellipsis_action_button_item.forEach(function(item, index) { #>';
		$output .= '<# if (item.ellipsis_action_button_background_gradient) { #>';
		$output .= '<# if (typeof item.ellipsis_action_button_background_gradient === "string") { #>';
		$output .= '.sppb-ellipsis-action-button:nth-child({{index + 1}}).sppb-btn-gradient {border: none; background-image: {{item.ellipsis_action_button_background_gradient}};}';
		$output .= '<# } else if (item.ellipsis_action_button_background_gradient.type) { #>';
		$output .= '<# let itemColor1 = item.ellipsis_action_button_background_gradient.color || "#398AF1"; #>';
		$output .= '<# let itemColor2 = item.ellipsis_action_button_background_gradient.color2 || "#5EDCED"; #>';
		$output .= '<# if (item.ellipsis_action_button_background_gradient.type === "linear") { #>';
		$output .= '<# let itemStart = item.ellipsis_action_button_background_gradient.pos || 0; #>';
		$output .= '<# let itemEnd = item.ellipsis_action_button_background_gradient.pos2 || 100; #>';
		$output .= '<# let itemDeg = item.ellipsis_action_button_background_gradient.deg || 0; #>';
		$output .= '.sppb-ellipsis-action-button:nth-child({{index + 1}}).sppb-btn-gradient {border: none; background-image: linear-gradient({{itemDeg}}deg, {{itemColor1}} {{itemStart}}%, {{itemColor2}} {{itemEnd}}%);}';
		$output .= '<# } else if (item.ellipsis_action_button_background_gradient.type === "radial") { #>';
		$output .= '<# let itemStart = item.ellipsis_action_button_background_gradient.pos || 0; #>';
		$output .= '<# let itemEnd = item.ellipsis_action_button_background_gradient.pos2 || 100; #>';
		$output .= '<# let itemPosition = item.ellipsis_action_button_background_gradient.radialPos || "center center"; #>';
		$output .= '.sppb-ellipsis-action-button:nth-child({{index + 1}}).sppb-btn-gradient {border: none; background-image: radial-gradient(at {{itemPosition}}, {{itemColor1}} {{itemStart}}%, {{itemColor2}} {{itemEnd}}%);}';
		$output .= '<# } else { #>';
		$output .= '.sppb-ellipsis-action-button:nth-child({{index + 1}}).sppb-btn-gradient {border: none; background-image: {{itemColor1}};}';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# if (item.ellipsis_action_button_background_gradient_hover) { #>';
		$output .= '<# if (typeof item.ellipsis_action_button_background_gradient_hover === "string") { #>';
		$output .= '.sppb-ellipsis-action-button:nth-child({{index + 1}}).sppb-btn-gradient:hover {border: none; background-image: {{item.ellipsis_action_button_background_gradient_hover}};}';
		$output .= '<# } else if (item.ellipsis_action_button_background_gradient_hover.type) { #>';
		$output .= '<# let itemHoverColor1 = item.ellipsis_action_button_background_gradient_hover.color || "#398AF1"; #>';
		$output .= '<# let itemHoverColor2 = item.ellipsis_action_button_background_gradient_hover.color2 || "#5EDCED"; #>';
		$output .= '<# if (item.ellipsis_action_button_background_gradient_hover.type === "linear") { #>';
		$output .= '<# let itemHoverStart = item.ellipsis_action_button_background_gradient_hover.pos || 0; #>';
		$output .= '<# let itemHoverEnd = item.ellipsis_action_button_background_gradient_hover.pos2 || 100; #>';
		$output .= '<# let itemHoverDeg = item.ellipsis_action_button_background_gradient_hover.deg || 0; #>';
		$output .= '.sppb-ellipsis-action-button:nth-child({{index + 1}}).sppb-btn-gradient:hover {border: none; background-image: linear-gradient({{itemHoverDeg}}deg, {{itemHoverColor1}} {{itemHoverStart}}%, {{itemHoverColor2}} {{itemHoverEnd}}%);}';
		$output .= '<# } else if (item.ellipsis_action_button_background_gradient_hover.type === "radial") { #>';
		$output .= '<# let itemHoverStart = item.ellipsis_action_button_background_gradient_hover.pos || 0; #>';
		$output .= '<# let itemHoverEnd = item.ellipsis_action_button_background_gradient_hover.pos2 || 100; #>';
		$output .= '<# let itemHoverPosition = item.ellipsis_action_button_background_gradient_hover.radialPos || "center center"; #>';
		$output .= '.sppb-ellipsis-action-button:nth-child({{index + 1}}).sppb-btn-gradient:hover {border: none; background-image: radial-gradient(at {{itemHoverPosition}}, {{itemHoverColor1}} {{itemHoverStart}}%, {{itemHoverColor2}} {{itemHoverEnd}}%);}';
		$output .= '<# } else { #>';
		$output .= '.sppb-ellipsis-action-button:nth-child({{index + 1}}).sppb-btn-gradient:hover {border: none; background-image: {{itemHoverColor1}};}';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# }); #>';
		$output .= '<# } #>';

		// Edit comment button specific colors and backgrounds
		$output .= '<# if (data.edit_comment_button_item && data.edit_comment_button_item.length > 0) { #>';
		$output .= '<# data.edit_comment_button_item.forEach(function(item, index) { #>';
		$output .= $lodash->color('color', '#sppb-comment-edit-button-{{index}}', 'item.edit_comment_button_color');
		$output .= $lodash->color('background-color', '#sppb-comment-edit-button-{{index}}', 'item.edit_comment_button_background_color');
		$output .= $lodash->color('color', '#sppb-comment-edit-button-{{index}}:hover', 'item.edit_comment_button_color_hover');
		$output .= $lodash->color('background-color', '#sppb-comment-edit-button-{{index}}:hover', 'item.edit_comment_button_background_color_hover');
		$output .= $lodash->color('background-color', '#sppb-comment-edit-button-{{index}}.sppb-btn-outline', 'item.edit_comment_button_background_color');
		$output .= $lodash->unit('border-color', '#sppb-comment-edit-button-{{index}}.sppb-btn-outline', 'item.edit_comment_button_background_color');
		$output .= $lodash->color('background-color', '#sppb-comment-edit-button-{{index}}.sppb-btn-outline:hover', 'item.edit_comment_button_background_color_hover');
		$output .= $lodash->unit('border-color', '#sppb-comment-edit-button-{{index}}.sppb-btn-outline:hover', 'item.edit_comment_button_background_color_hover');
		$output .= '<# }); #>';
		$output .= '<# } #>';

		// Edit comment button gradient styles
		$output .= '<# if (data.edit_comment_button_item && data.edit_comment_button_item.length > 0) { #>';
		$output .= '<# data.edit_comment_button_item.forEach(function(item, index) { #>';
		$output .= '<# if (item.edit_comment_button_background_gradient) { #>';
		$output .= '<# if (typeof item.edit_comment_button_background_gradient === "string") { #>';
		$output .= '#sppb-comment-edit-button-{{index}}.sppb-btn-gradient {border: none; background-image: {{item.edit_comment_button_background_gradient}};}';
		$output .= '<# } else if (item.edit_comment_button_background_gradient.type) { #>';
		$output .= '<# let editColor1 = item.edit_comment_button_background_gradient.color || "#398AF1"; #>';
		$output .= '<# let editColor2 = item.edit_comment_button_background_gradient.color2 || "#5EDCED"; #>';
		$output .= '<# if (item.edit_comment_button_background_gradient.type === "linear") { #>';
		$output .= '<# let editStart = item.edit_comment_button_background_gradient.pos || 0; #>';
		$output .= '<# let editEnd = item.edit_comment_button_background_gradient.pos2 || 100; #>';
		$output .= '<# let editDeg = item.edit_comment_button_background_gradient.deg || 0; #>';
		$output .= '#sppb-comment-edit-button-{{index}}.sppb-btn-gradient {border: none; background-image: linear-gradient({{editDeg}}deg, {{editColor1}} {{editStart}}%, {{editColor2}} {{editEnd}}%);}';
		$output .= '<# } else if (item.edit_comment_button_background_gradient.type === "radial") { #>';
		$output .= '<# let editStart = item.edit_comment_button_background_gradient.pos || 0; #>';
		$output .= '<# let editEnd = item.edit_comment_button_background_gradient.pos2 || 100; #>';
		$output .= '<# let editPosition = item.edit_comment_button_background_gradient.radialPos || "center center"; #>';
		$output .= '#sppb-comment-edit-button-{{index}}.sppb-btn-gradient {border: none; background-image: radial-gradient(at {{editPosition}}, {{editColor1}} {{editStart}}%, {{editColor2}} {{editEnd}}%);}';
		$output .= '<# } else { #>';
		$output .= '#sppb-comment-edit-button-{{index}}.sppb-btn-gradient {border: none; background-image: {{editColor1}};}';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# if (item.edit_comment_button_background_gradient_hover) { #>';
		$output .= '<# if (typeof item.edit_comment_button_background_gradient_hover === "string") { #>';
		$output .= '#sppb-comment-edit-button-{{index}}.sppb-btn-gradient:hover {border: none; background-image: {{item.edit_comment_button_background_gradient_hover}};}';
		$output .= '<# } else if (item.edit_comment_button_background_gradient_hover.type) { #>';
		$output .= '<# let editHoverColor1 = item.edit_comment_button_background_gradient_hover.color || "#398AF1"; #>';
		$output .= '<# let editHoverColor2 = item.edit_comment_button_background_gradient_hover.color2 || "#5EDCED"; #>';
		$output .= '<# if (item.edit_comment_button_background_gradient_hover.type === "linear") { #>';
		$output .= '<# let editHoverStart = item.edit_comment_button_background_gradient_hover.pos || 0; #>';
		$output .= '<# let editHoverEnd = item.edit_comment_button_background_gradient_hover.pos2 || 100; #>';
		$output .= '<# let editHoverDeg = item.edit_comment_button_background_gradient_hover.deg || 0; #>';
		$output .= '#sppb-comment-edit-button-{{index}}.sppb-btn-gradient:hover {border: none; background-image: linear-gradient({{editHoverDeg}}deg, {{editHoverColor1}} {{editHoverStart}}%, {{editHoverColor2}} {{editHoverEnd}}%);}';
		$output .= '<# } else if (item.edit_comment_button_background_gradient_hover.type === "radial") { #>';
		$output .= '<# let editHoverStart = item.edit_comment_button_background_gradient_hover.pos || 0; #>';
		$output .= '<# let editHoverEnd = item.edit_comment_button_background_gradient_hover.pos2 || 100; #>';
		$output .= '<# let editHoverPosition = item.edit_comment_button_background_gradient_hover.radialPos || "center center"; #>';
		$output .= '#sppb-comment-edit-button-{{index}}.sppb-btn-gradient:hover {border: none; background-image: radial-gradient(at {{editHoverPosition}}, {{editHoverColor1}} {{editHoverStart}}%, {{editHoverColor2}} {{editHoverEnd}}%);}';
		$output .= '<# } else { #>';
		$output .= '#sppb-comment-edit-button-{{index}}.sppb-btn-gradient:hover {border: none; background-image: {{editHoverColor1}};}';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# } #>';
		$output .= '<# }); #>';
		$output .= '<# } #>';

		// Ellipsis action positioning and margins
		$output .= $lodash->spacing('margin', '.sppb-comment-ellipsis-action', 'data.ellipsis_margin');
		$output .= $lodash->alignment('justify-content', '.sppb-comment-ellipsis-action', 'data.ellipsis_position');

		// Ellipsis action outline button styles
		$output .= '.sppb-comment-ellipsis-action.sppb-btn-custom.sppb-btn-outline {background-color: transparent; border-color: transparent;}';
		$output .= '.sppb-comment-ellipsis-action.sppb-btn-custom.sppb-btn-outline i {background-color: transparent; border-color: transparent;}';

		// Comment header left positioning
		$output .= $lodash->alignment('justify-content', '.sppb-comment-header-left', 'data.commentator_name_position');
		$output .= '.sppb-comment-header-left {flex: 1;}';

		$output .= $lodash->unit('height', '.sppb-comment-field', 'data.comment_field_height', 'px');

		// Comment field hover and focus states
		$output .= $lodash->color('background-color', '.sppb-comment-field:hover', 'data.comment_field_background_hover_color');
		$output .= $lodash->color('color', '.sppb-comment-field:hover::placeholder', 'data.comment_field_placeholder_hover_color');
		$output .= $lodash->unit('border-color', '.sppb-comment-field:focus', 'data.comment_field_border_focus_color');

		// Comment form button wrapper border styles
		$output .= $lodash->unit('border-color', '.sppb-comment-form .sppb-comment-btn-wrapper', 'data.comment_field_border_color');

		$output .= '</style>';
		
		// HTML Structure
		$output .= '<div class="sppb-addon sppb-addon-comment {{ data.class }}">';
		
		// Title wrapper
		$output .= '<div class="sppb-comment-title-wrapper">';
		$output .= '<# if (data.comment_count && data.comment_count_position === "left") { #>';
		$output .= '<span class="sppb-comment-count">(5)</span>';
		$output .= '<# } #>';
		$output .= '<span class="sppb-comment-title-text">{{ data.title_text || "Comments" }}</span>';
		$output .= '<# if (data.comment_count && data.comment_count_position === "right") { #>';
		$output .= '<span class="sppb-comment-count">(5)</span>';
		$output .= '<# } #>';
		$output .= '</div>';

		// Label wrapper
		$output .= '<# if (data.enable_label) { #>';
		$output .= '<div class="sppb-comment-label-wrapper">';
		$output .= '<span class="sppb-comment-label-text">{{ data.label_text || "Leave a comment" }}</span>';
		$output .= '</div>';
		$output .= '<# } #>';

		// Comment form
		$output .= '<form class="sppb-comment-form">';
		$output .= '<textarea class="sppb-comment-field" name="comment" placeholder="{{ data.comment_field_placeholder || \'Share your thoughts...\' }}"></textarea>';
		$output .= '<div class="sppb-comment-btn-wrapper">';
		$output .= '<button type="submit" class="comment-submit-btn sppb-btn {{ data.post_button_type ? \'sppb-btn-\' + data.post_button_type : \'sppb-btn-default\' }} {{ data.post_button_block || \'\' }} {{ data.post_button_shape ? \'sppb-btn-\' + data.post_button_shape : \'sppb-btn-rounded\' }} {{ data.post_button_appearance ? \'sppb-btn-\' + data.post_button_appearance : \'\' }} {{ data.post_button_size ? \'sppb-btn-\' + data.post_button_size : \'sppb-btn-md\' }}">';
		$output .= '<# if (data.post_button_icon && data.post_button_icon_position === "left") { #>';
		$output .= '<i class="sppb-btn-icon {{ data.post_button_icon }}"></i>';
		$output .= '<# } #>';
		$output .= '<span>{{ data.post_button_text || "Leave a comment" }}</span>';
		$output .= '<# if (data.post_button_icon && data.post_button_icon_position === "right") { #>';
		$output .= '<i class="sppb-btn-icon {{ data.post_button_icon }}"></i>';
		$output .= '<# } #>';
		$output .= '</button>';
		$output .= '</div>';
		$output .= '</form>';

		// Comments list
		$output .= '<div class="sppb-comments-list" style="margin-top: 40px;">';

		$output .=  '<# function getTextColor(color) {
			const r = parseInt(color.substr(1, 2), 16);
			const g = parseInt(color.substr(3, 2), 16);
			const b = parseInt(color.substr(5, 2), 16);
		
			const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
		
			return (brightness > 128) ? "#000000" : "#FFFFFF";
		} #>';
	
		$personAvatarColor = '{{data?.commentator_avatar_color || "#4285F4"}}';
		$personAvatarColorValue = 'data?.commentator_avatar_color || "#4285F4"';
		$output .= '<# let persontextColor = getTextColor(' . $personAvatarColorValue . ') #>';
		
		// Static comment 1
		$output .= '<div data-comment-id="1" data-parent-id="" class="sppb-comment-item sppb-comment-item sppb-parent-comment sppb-root-comment" style="margin-top: -4px; margin-left: 0px;">';
		$output .= '<div class="sppb-comment-thread-line" style="left:-34px;"></div>';
		$output .= '<div class="sppb-comment-thread-connector" style="left:-34px; width: 55px;"></div>';
		$output .= '<div class="sppb-comment-content">';
		$output .= '<div class="sppb-comment-header sppb-comment-header">';
		$output .= '<div class="sppb-comment-avatar" style="background-color: ' . $personAvatarColor . '; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px; font-weight: 600; margin-right: 10px; color: {{persontextColor}}">';
		$output .= '<span>SL</span>';
		$output .= '</div>';
		$output .= '<div class="sppb-comment-header-left">';
		$output .= '<div class="commentator-name-wrapper">';
		$output .= '<span class="commentator-name">Sofia Lindstrom</span>';
		$output .= '</div>';
		$output .= '<# if (data.enable_time) { #>';
		$output .= '<div class="sppb-comment-time-wrapper">';
		$output .= '<span class="sppb-comment-time">2 hours ago</span>';
		$output .= '<# if (data.show_edited_time) { #>';
		$output .= '<span class="sppb-comment-edited-time"> • Edited 1 hour ago</span>';
		$output .= '<# } #>';
		$output .= '</div>';
		$output .= '<# } #>';
		$output .= '</div>';
		$output .= '<div class="sppb-comment-ellipsis-action sppb-btn sppb-btn-custom sppb-btn-rounded sppb-btn-md">';
		$output .= '<i class="sppb-ellipsis-icon fa fa-ellipsis-v"></i>';
		$output .= '<div class="sppb-ellipsis-dropdown" style="display: none;">';
		$output .= '<div class="sppb-comment-ellipsis-action-buttons-wrapper">';
		$output .= '<button style="width: 100%; text-align: left;" class="sppb-comment-ellipsis-action-button sppb-btn sppb-btn-md sppb-btn-rounded sppb-ellipsis-edit-btn" data-comment-id="1">Edit</button>';
		$output .= '<button style="width: 100%; text-align: left;" class="sppb-comment-ellipsis-action-button sppb-btn sppb-btn-md sppb-btn-rounded sppb-ellipsis-delete-btn" data-comment-id="1">Delete</button>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<div class="sppb-comment-content sppb-comment-body" data-comment-id="1" style="margin-left: 55px; padding-bottom: 29px;">';
		$output .= '<div class="sppb-comment-body-thread-line"></div>';
		$output .= '<span class="sppb-comment-content-text" style="margin-left: 10px; margin-top: 20px; display: inline-block;">';
		$output .= '<span>This is a great article, Jane! I really enjoyed reading it. Expecting more insightful writings from you.</span>';
		$output .= '</span>';
		$output .= '<div class="sppb-comment-edit-form sppb-comment-form" data-comment-id="1" style="display: none;">';
		$output .= '<textarea type="text" class="sppb-comment-edit-input sppb-comment-field" data-comment-id="1">This is a great article, Jane! I really enjoyed reading it. Expecting more insightful writings from you.</textarea>';
		$output .= '<div class="sppb-comment-edit-buttons-wrapper sppb-comment-btn-wrapper">';
		$output .= '<button class="sppb-comment-edit-button sppb-comment-edit-cancel-btn sppb-btn sppb-btn-md sppb-btn-rounded" data-comment-id="1">Cancel</button>';
		$output .= '<button class="sppb-comment-edit-button sppb-comment-edit-submit-btn sppb-btn sppb-btn-md sppb-btn-rounded" data-comment-id="1">Update</button>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<# if (data.enable_likes || data.enable_reply) { #>';
		$output .= '<div class="sppb-comment-actions" style="margin-left: 10px">';
		$output .= '<# if (data.enable_likes) { #>';
		$output .= '<div class="sppb-comment-likes">';
		$output .= '<i class="sppb-likes-icon fa fa-heart"></i>';
		$output .= '<span class="sppb-likes-text">3 likes</span>';
		$output .= '</div>';
		$output .= '<# } #>';
		$output .= '<# if (data.enable_reply) { #>';
		$output .= '<div class="sppb-comment-reply-action" data-comment-id="1">';
		$output .= '<i class="sppb-reply-icon fa fa-reply"></i>';
		$output .= '<span class="sppb-reply-text">2 reply</span>';
		$output .= '</div>';
		$output .= '<# } #>';
		$output .= '</div>';
		$output .= '<# } #>';
		$output .= '<div class="sppb-comment-reply-form sppb-comment-form" data-comment-id="1" style="display: none;">';
		$output .= '<textarea type="text" class="sppb-comment-reply-input sppb-comment-field" data-comment-id="1" placeholder="Write a reply..."></textarea>';
		$output .= '<div class="sppb-comment-btn-wrapper">';
		$output .= '<button class="sppb-comment-reply-cancel-btn sppb-btn sppb-btn-default sppb-btn-rounded sppb-btn-md" data-comment-id="1">Cancel</button>';
		$output .= '<button class="sppb-comment-reply-submit-btn comment-submit-btn {{ data.post_button_type ? \'sppb-btn-\' + data.post_button_type : \'sppb-btn-default\' }} {{ data.post_button_block || \'\' }} {{ data.post_button_shape ? \'sppb-btn-\' + data.post_button_shape : \'sppb-btn-rounded\' }} {{ data.post_button_appearance ? \'sppb-btn-\' + data.post_button_appearance : \'\' }} {{ data.post_button_size ? \'sppb-btn-\' + data.post_button_size : \'sppb-btn-md\' }}" data-comment-id="1">Submit</button>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		
		// Reply to comment 1
		$output .= '<div class="sppb-comment-children-wrapper">';
		$output .= '<div data-comment-id="2" data-parent-id="1" class="sppb-comment-item sppb-comment-item" style="margin-top: -4px; margin-left: 55px;">';
		$output .= '<div class="sppb-comment-thread-line" style="left:-34px;"></div>';
		$output .= '<div class="sppb-comment-thread-connector" style="left:-34px; width: 55px;"></div>';
		$output .= '<div class="sppb-comment-content" style="margin-left: 0; margin-top: 11px;">';
		$output .= '<div class="sppb-comment-header sppb-comment-header">';
		
		// Static Gravatar implementation
		$replyPersonAvatarColor = '{{data?.commentator_avatar_color || "#4285F4"}}';
		$replyPersonAvatarColorValue = 'data?.commentator_avatar_color || "#4285F4"';
		$output .= '<# let replyPersonTextColor = getTextColor(' . $replyPersonAvatarColorValue . ') #>';
		$avatarUrl = Uri::root() . 'components/com_sppagebuilder/assets/images/user_comment_placeholder.jpg';
		
		$output .= '<# if (' . $enableGravatar . ') { #>';
		$output .= '<div class="sppb-comment-avatar" style="border-radius: 50%; width: 45px; height: 45px; margin-right: 10px; overflow: hidden; flex-shrink: 0;">';
		$output .= '<img src="' . $avatarUrl . '" alt="Jane Doe" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';" />';
		$output .= '<div class="sppb-comment-avatar-fallback" style="background-color: ' . $replyPersonAvatarColor . '; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: none; align-items: center; justify-content: center; font-size: 16px; font-weight: 600; color: {{replyPersonTextColor}}">';
		$output .= '<span>JD</span>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<# } else { #>';
		$output .= '<div class="sppb-comment-avatar" style="background-color: ' . $replyPersonAvatarColor . '; padding: 10px; border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px; font-weight: 600; margin-right: 10px; color: {{replyPersonTextColor}}">';
		$output .= '<span>JD</span>';
		$output .= '</div>';
		$output .= '<# } #>';
		
		$output .= '<div class="sppb-comment-header-left">';
		$output .= '<div class="commentator-name-wrapper">';
		$output .= '<span class="commentator-name">Jane Doe</span>';
		$output .= '</div>';
		$output .= '<# if (data.enable_time) { #>';
		$output .= '<div class="sppb-comment-time-wrapper">';
		$output .= '<span class="sppb-comment-time">1 hour ago</span>';
		$output .= '<# if (data.show_edited_time) { #>';
		$output .= '<span class="sppb-comment-edited-time"> • Edited 30 min ago</span>';
		$output .= '<# } #>';
		$output .= '</div>';
		$output .= '<# } #>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '<div class="sppb-comment-content sppb-comment-body" data-comment-id="2" style="margin-left: 55px; padding-bottom: 29px;">';
		$output .= '<div class="sppb-comment-body-thread-line"></div>';
		$output .= '<span class="sppb-comment-content-text" style="margin-left: 10px; margin-top: 20px; display: inline-block;">';
		$output .= '<span>Thanks, Sofia! Stay tuned for more updates.</span>';
		$output .= '</span>';
		$output .= '<# if (data.enable_likes || data.enable_reply) { #>';
		$output .= '<div class="sppb-comment-actions" style="margin-left: 10px">';
		$output .= '<# if (data.enable_likes) { #>';
		$output .= '<div class="sppb-comment-likes">';
		$output .= '<i class="sppb-likes-icon fa fa-heart"></i>';
		$output .= '<span class="sppb-likes-text">1 like</span>';
		$output .= '</div>';
		$output .= '<# } #>';
		$output .= '<# if (data.enable_reply) { #>';
		$output .= '<div class="sppb-comment-reply-action" data-comment-id="2">';
		$output .= '<i class="sppb-reply-icon fa fa-reply"></i>';
		$output .= '<span class="sppb-reply-text">0 reply</span>';
		$output .= '</div>';
		$output .= '<# } #>';
		$output .= '</div>';
		$output .= '<# } #>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';

		
		$output .= '</div>';
		$output .= '</div>';

		// Add JavaScript for template functionality
		$js = '';
		$js .= 'jQuery(document).ready(function($) {';
		
		// Handle Gravatar image loading and fallback
		$js .= 'const handleGravatarImages = () => {';
		$js .= 'const gravatarImages = document.querySelectorAll(\'.sppb-comment-avatar img[src*="gravatar.com"]\');';
		$js .= 'gravatarImages.forEach(img => {';
		$js .= 'img.addEventListener(\'error\', function() {';
		$js .= 'this.style.display = \'none\';';
		$js .= 'const fallback = this.nextElementSibling;';
		$js .= 'if (fallback && fallback.classList.contains(\'sppb-comment-avatar-fallback\')) {';
		$js .= 'fallback.style.display = \'flex\';';
		$js .= '}';
		$js .= '});';
		$js .= 'img.addEventListener(\'load\', function() {';
		$js .= 'const fallback = this.nextElementSibling;';
		$js .= 'if (fallback && fallback.classList.contains(\'sppb-comment-avatar-fallback\')) {';
		$js .= 'fallback.style.display = \'none\';';
		$js .= '}';
		$js .= '});';
		$js .= '});';
		$js .= '};';
		$js .= 'handleGravatarImages();';

		// Ellipsis menu functionality
		$js .= '$(".sppb-comment-ellipsis-action").on("click", function(e) {';
		$js .= 'e.preventDefault();';
		$js .= 'e.stopPropagation();';
		$js .= 'var dropdown = $(this).find(".sppb-ellipsis-dropdown");';
		$js .= '$(".sppb-ellipsis-dropdown").not(dropdown).hide();';
		$js .= 'dropdown.toggle();';
		$js .= '});';
		
		// Close dropdown when clicking outside
		$js .= '$(document).on("click", function(e) {';
		$js .= 'if (!$(e.target).closest(".sppb-comment-ellipsis-action").length) {';
		$js .= '$(".sppb-ellipsis-dropdown").hide();';
		$js .= '}';
		$js .= '});';
		
		// Edit button functionality
		$js .= '$(".sppb-ellipsis-edit-btn").on("click", function(e) {';
		$js .= 'e.preventDefault();';
		$js .= 'e.stopPropagation();';
		$js .= 'var commentId = $(this).data("comment-id");';
		$js .= 'var commentContent = $(".sppb-comment-content[data-comment-id=\'" + commentId + "\']");';
		$js .= 'var contentText = commentContent.find(".sppb-comment-content-text span").text();';
		$js .= 'var editForm = commentContent.find(".sppb-comment-edit-form");';
		$js .= 'var editInput = editForm.find(".sppb-comment-edit-input");';
		$js .= 'editInput.val(contentText);';
		$js .= 'commentContent.find(".sppb-comment-content-text").hide();';
		$js .= 'editForm.show();';
		$js .= '$(".sppb-ellipsis-dropdown").hide();';
		$js .= '});';
		
		// Delete button functionality (just close dropdown)
		$js .= '$(".sppb-ellipsis-delete-btn").on("click", function(e) {';
		$js .= 'e.preventDefault();';
		$js .= 'e.stopPropagation();';
		$js .= '$(".sppb-ellipsis-dropdown").hide();';
		$js .= '});';
		
		// Edit form cancel button
		$js .= '$(".sppb-comment-edit-cancel-btn").on("click", function(e) {';
		$js .= 'e.preventDefault();';
		$js .= 'var commentId = $(this).data("comment-id");';
		$js .= 'var commentContent = $(".sppb-comment-content[data-comment-id=\'" + commentId + "\']");';
		$js .= 'commentContent.find(".sppb-comment-edit-form").hide();';
		$js .= 'commentContent.find(".sppb-comment-content-text").show();';
		$js .= '});';
		
		// Edit form update button (non-functional, just close form)
		$js .= '$(".sppb-comment-edit-submit-btn").on("click", function(e) {';
		$js .= 'e.preventDefault();';
		$js .= 'var commentId = $(this).data("comment-id");';
		$js .= 'var commentContent = $(".sppb-comment-content[data-comment-id=\'" + commentId + "\']");';
		$js .= 'commentContent.find(".sppb-comment-edit-form").hide();';
		$js .= 'commentContent.find(".sppb-comment-content-text").show();';
		$js .= '});';
		
		// Reply action functionality
		$js .= '$(".sppb-comment-reply-action").on("click", function(e) {';
		$js .= 'e.preventDefault();';
		$js .= 'var commentId = $(this).data("comment-id");';
		$js .= 'var commentContent = $(".sppb-comment-content[data-comment-id=\'" + commentId + "\']");';
		$js .= 'var replyForm = commentContent.find(".sppb-comment-reply-form");';
		$js .= 'replyForm.toggle();';
		$js .= '});';
		
		// Reply form cancel button
		$js .= '$(".sppb-comment-reply-cancel-btn").on("click", function(e) {';
		$js .= 'e.preventDefault();';
		$js .= 'var commentId = $(this).data("comment-id");';
		$js .= 'var commentContent = $(".sppb-comment-content[data-comment-id=\'" + commentId + "\']");';
		$js .= 'commentContent.find(".sppb-comment-reply-form").hide();';
		$js .= '});';
		
		// Reply form submit button (non-functional, just close form)
		$js .= '$(".sppb-comment-reply-submit-btn").on("click", function(e) {';
		$js .= 'e.preventDefault();';
		$js .= 'var commentId = $(this).data("comment-id");';
		$js .= 'var commentContent = $(".sppb-comment-content[data-comment-id=\'" + commentId + "\']");';
		$js .= 'commentContent.find(".sppb-comment-reply-form").hide();';
		$js .= 'commentContent.find(".sppb-comment-reply-input").val("");';
		$js .= '});';
		
		$js .= '});';
		$js .= '';

		Factory::getDocument()->addScriptDeclaration($js);

		return $output;
	}

	private function renderLoginModal() {
		$shouldShowLoginModal = empty(Factory::getUser()->id) && empty($this->addon->settings->enable_anonymous_comment);
		
		$output = '';
		$output .= '<div class="sppb-login-modal-overlay"></div>';
		$output .= '<div id="sppb-login-modal" class="sppb-login-modal" style="display: none;">';
		$output .= '<div class="sppb-login-modal-content">';
		$output .= '<div class="sppb-login-modal-header">';
		$output .= '<div style="display: flex; justify-content: end;">';
		$output .= '<button type="button" class="sppb-login-modal-close"><span>&times;</span></button>';
		$output .= '</div>';
		$output .= '<h3>' . Text::_('COM_SPPAGEBUILDER_LOGIN_REQUIRED') . '</h3>';
		$output .= '</div>';
		$output .= '<div class="sppb-login-modal-body">';
		$output .= '<div id="sppb-login-form-container">';
		$output .= '<p>' . Text::_('COM_SPPAGEBUILDER_LOADING_LOGIN_FORM') . '</p>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		$output .= '</div>';
		
		// Add JavaScript to fetch the login form
		$js = '
		document.addEventListener("DOMContentLoaded", function() {
			// Fetch login form when modal is opened
			$(document).on("keyup", ".sppb-comment-field", function(e) {
				' . ($shouldShowLoginModal ? '$("#sppb-login-modal").show();' : '') . '
				' . ($shouldShowLoginModal ? '$(".sppb-login-modal-overlay").show();' : '') . '
				' . ($shouldShowLoginModal ? '$("#sppb-login-modal input[name=\'username\']").focus();' : '') . '
			});
			// $(document).on("click", ".sppb-login-required", function(e) {
				// e.preventDefault();
				fetchLoginForm();
				$(".sppb-login-modal-overlay").hide();
			// });
			
			// Close modal
			$(document).on("click", ".sppb-login-modal-close", function() {
				$("#sppb-login-modal").hide();
				$(".sppb-login-modal-overlay").hide();
			});
			
			// Handle form submission
			// $(document).on("submit", "#sppb-login-form-container form", function(e) {
			// 	e.preventDefault();
			// 	const form = $(this);
			// 	const formData = new FormData(this);
				
			// 	// Add return URL for redirect after login
			// 	const returnUrl = "' . base64_encode(\Joomla\CMS\Uri\Uri::current()) . '";
			// 	formData.append("return", returnUrl);
				
			// 	// Submit form via AJAX
			// 	$.ajax({
			// 		url: form.attr("action"),
			// 		type: "POST",
			// 		data: formData,
			// 		processData: false,
			// 		contentType: false,
			// 		success: function(response) {
			// 			if (response.success) {
			// 				// Login successful, reload page
			// 				window.location.reload();
			// 			} else {
			// 				// Show error message
			// 				alert(response.message || "Login failed. Please try again.");
			// 			}
			// 		},
			// 		error: function() {
			// 			alert("Login failed. Please try again.");
			// 		}
			// 	});
			// });
		});
		
		function fetchLoginForm() {
			const container = document.getElementById("sppb-login-form-container");
			container.innerHTML = "<p>' . Text::_('COM_SPPAGEBUILDER_LOADING_LOGIN_FORM') . '</p>";
			
			// Fetch the login form from Joomla\'s default login view
			fetch("' . \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_users&view=login&tmpl=component&layout=default", {
				method: "GET",
				headers: {
					"X-Requested-With": "XMLHttpRequest"
				}
			})
			.then(response => response.text())
			.then(html => {
				// Extract the form from the response
				const parser = new DOMParser();
				const doc = parser.parseFromString(html, "text/html");
				const form = doc.querySelector("form");
				
				if (form) {
					// Update form action to use component template
					form.action = "' . \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_users&task=user.login&tmpl=component";
					
					// Add return URL
					const returnInput = form.querySelector("input[name=\'return\']");
					if (returnInput) {
						returnInput.value = "' . base64_encode(\Joomla\CMS\Uri\Uri::getInstance()->toString()) . '";
					}
					
					// Replace the container content with the actual form
					container.innerHTML = "";
					container.appendChild(form);
					
					// Initialize Joomla form validation if available
					if (typeof Joomla !== "undefined" && Joomla.formvalidator) {
						Joomla.formvalidator.init(form);
					}
				} else {
					container.innerHTML = "<p>' . Text::_('COM_SPPAGEBUILDER_ERROR_LOADING_LOGIN_FORM') . '</p>";
				}
			})
			.catch(error => {
				console.error("Error fetching login form:", error);
				container.innerHTML = "<p>' . Text::_('COM_SPPAGEBUILDER_ERROR_LOADING_LOGIN_FORM') . '</p>";
			});
		}';
		
		Factory::getDocument()->addScriptDeclaration($js);
		
		return $output;
	}
}