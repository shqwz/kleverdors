<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 */

namespace JoomShaper\SPPageBuilder\Comment\Services;

defined('_JEXEC') or die;

use Exception;
use JLoader;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\Component;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use JoomShaper\SPPageBuilder\Comment\Models\Comment;

/**
 * Comment Service
 * This class is responsible for creating, updating, deleting and fetching comments tied to a specific article.
 *
 * @since 6.0.0
 */
class CommentService
{
    /** @var \Joomla\CMS\Application\CMSApplication $app */
    protected $app;
    protected $model;

    /**
     * CommentService constructor.
     */
    public function __construct()
    {
        $this->app = Factory::getApplication();
        $this->model = new Comment();
    }

	public function getArticleId($pageId) {
		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->select('view_id')
			->from('#__sppagebuilder')
			->where('id = ' . $pageId)
			->where('extension_view = ' . $db->quote('article'));

		$db->setQuery($query);
		return $db->loadResult();
	}
	
	/**
	 * Get all comments
	 *
	 * @return array
	 */
    public function getAllComments($itemId = null, $sourceType = 'articles'){
        $commentData = $this->model->getAllComments($itemId, $sourceType);
        $formattedComments = $this->getFormattedComments($commentData);

		return $formattedComments;
    }

	/**
	 * Get the count of published comments for a specific item
	 *
	 * @param int|null $itemId
	 * @return int
	 */
	public function getPublishedCommentsCount(?int $itemId = null, $sourceType = 'articles'): int {
		return $this->model->getPublishedCommentsCount($itemId, $sourceType);
	}

    /**
     * Create a new comment or reply
     *
     * @param array $payload
     * @return void
     * @throws Exception
     */
    public function createComment($commentPayload){
        $userId = Factory::getUser()->id;
        $sanitizedPayload = $this->sanitizeCommentPayload($commentPayload);

		if(empty($sanitizedPayload['content'])) {
			$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_CREATING_COMMENT')], 500);
		}

        // Check if anonymous comments are enabled
        $enableAnonymousComment = !empty($commentPayload['enable_anonymous_comment']) ? true : false;
        
        // If user is not logged in and anonymous comments are not enabled, return error
        if(empty($userId) && !$enableAnonymousComment) {
            $this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_ANONYMOUS_COMMENTS_DISABLED')], 400);
        }
		
		$params = ComponentHelper::getParams('com_sppagebuilder');
		$manualCommentApproval = $params->get('manual_comment_approval', 0);
		$previouslyApprovedComment = $params->get('previously_approved_comment', 0);
		$published = 1;

		if($manualCommentApproval) {
			if($previouslyApprovedComment) {
				$published = $this->hasPreviouslyApprovedComment($userId, $sanitizedPayload['item_id']) ? 1 : 0;
			} else {
				$published = 0;
			}
		}

        if((!empty($userId) || $enableAnonymousComment) && !empty($sanitizedPayload['item_id'])) {
             $payload = [
                'created_by' => $userId ?: null, // Set to null for anonymous comments
                'item_id' => isset($sanitizedPayload['item_id']) ? $sanitizedPayload['item_id'] : 0,
				'source_type' => !empty($sanitizedPayload['source_type']) ? $sanitizedPayload['source_type'] : 'articles',
                'content' => $sanitizedPayload['content'],
                'likes' => 0,
                'replies' => 0,
                'parent_id' => isset($sanitizedPayload['parent_id']) ? $sanitizedPayload['parent_id'] : null,
				'published' => $published,
            ];

            $response = $this->model->createComment($payload);

            if ($response) {
                $this->sendResponse(['success' => true, 'message' => Text::_('COM_SPPAGEBUILDER_COMMENT_CREATED_SUCCESSFULLY'), 'data' => $response]);
            } else {
                $this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_CREATING_COMMENT')], 500);
            }

        } else {
            $this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_INVALID_USER_OR_ARTICLE')], 400);
        }
    }

	/**
	 * Delete a comment
	 *
	 * @param int $commentId
	 * @return array
	 */
	public function deleteComment($commentId)
	{
		if (empty($commentId)) {
			$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_INVALID_COMMENT_ID')], 400);
		}

		try
		{
			$user = Factory::getUser();
			
			if (!$user->id)
			{
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_NOT_LOGGED_IN')], 401);
			}

			$model = $this->model;
			$comment = $model->getComment($commentId);
			
			// Anonymous comments cannot be deleted by regular users
			if ($comment && !$comment->created_by) {
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_ANONYMOUS_COMMENT_CANNOT_BE_DELETED')], 403);
			}

			if (!$model->canUpdateComment($commentId, $user->id))
			{
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_NOT_AUTHORIZED')], 403);
			}

			if ($model->deleteComment($commentId))
			{
				$this->sendResponse(['success' => true, 'message' => Text::_('COM_SPPAGEBUILDER_COMMENT_DELETED_SUCCESSFULLY')]);
			}
			else
			{
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_DELETING_COMMENT')], 500);
			}
		}
		catch (Exception $e)
		{
			$this->sendResponse(['error' => $e->getMessage()], 500);
		}
	}

	/**
	 * Update a comment
	 *
	 * @param int $commentId
	 * @param array $data
	 * @return array
	 */
	public function updateComment($commentId, $data)
	{
		if (empty($commentId)) {
			$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_INVALID_COMMENT_ID')], 400);
		}

		try
		{
			$user = Factory::getUser();
			
			if (!$user->id)
			{
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_NOT_LOGGED_IN')], 401);
			}

			$model = $this->model;
			$comment = $model->getComment($commentId);
			
			// Anonymous comments cannot be updated
			if ($comment && !$comment->created_by) {
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_ANONYMOUS_COMMENT_CANNOT_BE_UPDATED')], 403);
			}

			if (!$model->canUpdateComment($commentId, $user->id))
			{
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_NOT_AUTHORIZED')], 403);
			}

			if ($model->updateComment($commentId, $data['content']))
			{
				$this->sendResponse(['success' => true, 'message' => Text::_('COM_SPPAGEBUILDER_COMMENT_UPDATED_SUCCESSFULLY')]);
			}
			else
			{
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_UPDATING_COMMENT')], 500);
			}
		}
		catch (Exception $e)
		{
			$this->sendResponse(['error' => $e->getMessage()], 500);
		}
	}

	/**
	 * Get formatted comments
	 *
	 * @param array $comments
	 * @return array
	 */
	private function getFormattedComments(array $comments): array {
		$commentMap = [];
		$roots = [];
	
		foreach ($comments as $comment) {
			$comment->children = [];
			$commentMap[$comment->id] = $comment;
		}
	
		foreach ($comments as $comment) {
			if ($comment->parent_id === null) {
				$roots[] = $comment;
			} else {
				$parentId = $comment->parent_id;
				if (isset($commentMap[$parentId])) {
					$commentMap[$parentId]->children[] = $comment;
				}
			}
		}
	
		return $roots;
	}

	private function hasPreviouslyApprovedComment($userId, $itemId, $sourceType = 'articles'): bool
	{
		$approvedComments = $this->model->getApprovedComments($userId, $itemId, $sourceType);
		return !empty($approvedComments);
	}

    /**
     * Sanitize comment payload
     *
     * @param array $payload
     * @return array
     */
    //sanitization function to prevent XSS attacks
	private function sanitizeCommentPayload($payload)
	{
		$sanitizedPayload = [];
		foreach ($payload as $key => $value) {
			if ($value === null) {
				$sanitizedPayload[$key] = null;
			} elseif ($key === 'item_id') {
				$sanitizedPayload[$key] = (int) $value;
			} elseif ($key === 'parent_id') {
				$sanitizedPayload[$key] = ($value === null || $value === '') ? null : (int) $value;
			} else {
				$sanitizedPayload[$key] = trim(htmlspecialchars(strip_tags((string) $value), ENT_QUOTES, 'UTF-8'));
			}
		}
		return $sanitizedPayload;
	}

	public function likeComment($commentId, $userId)
	{
		if (empty($commentId)) {
			$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_INVALID_PARAMETERS')], 400);
		}

		if (empty($userId)) {
			$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_ANONYMOUS_COMMENT_CANNOT_BE_LIKED')], 403);
		}

		try
		{
			$model = $this->model;
			$comment = $model->getComment($commentId);

			if ($model->likeComment($commentId, $userId))
			{
				$this->sendResponse(['success' => true, 'message' => Text::_('COM_SPPAGEBUILDER_COMMENT_LIKED_SUCCESSFULLY')]);
			}
			else
			{
				$this->sendResponse(['error' => Text::_('COM_SPPAGEBUILDER_ERROR_LIKING_COMMENT')], 500);
			}
		}
		catch (Exception $e)
		{
			$this->sendResponse(['error' => $e->getMessage()], 500);
		}
	}

    /**
     * Send a JSON response
     *
     * @param array $response
     * @param int $statusCode
     */
    private function sendResponse($response, int $statusCode = 200)
	{
		$this->app->setHeader('Content-Type', 'application/json');
		$this->app->setHeader('status', $statusCode, true);
		$this->app->sendHeaders();

		echo new JsonResponse($response);

		$this->app->close();
	}

}