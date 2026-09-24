<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\Comment\Models;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ItemModel;
use Joomla\CMS\Table\Table;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Date;

defined('_JEXEC') or die;

class Comment extends ItemModel
{
    public function getItem($pk = null)
    {
       //todo
    }

	/**
	 * Get the table object
	 *
	 * @param string $type
	 * @param string $prefix
	 * @param array $config
	 * @return Table
	 */
	public function updateComment($id, $content)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->update($db->quoteName('#__sppagebuilder_comments'))
			->set($db->quoteName('content') . ' = ' . $db->quote($content))
			->set($db->quoteName('modified') . ' = ' . $db->quote(Date::sqlSafeDate()))
			->where($db->quoteName('id') . ' = ' . (int) $id);

		$db->setQuery($query);

		try {
			$db->execute();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Get the count of published comments for a specific item
	 *
	 * @param int|null $itemId
	 * @return int
	 */
	public function getPublishedCommentsCount($itemId, $sourceType = 'articles'){
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*)')
			->from($db->quoteName('#__sppagebuilder_comments'))
			->where($db->quoteName('item_id') . ' = ' . (int) $itemId)
			->where($db->quoteName('source_type') . ' = ' . $db->quote($sourceType))
			->where($db->quoteName('published') . ' = 1');

		$db->setQuery($query);

		try {
			return (int) $db->loadResult();
		} catch (Exception $e) {
			return 0;
		}
	}

	/**
	 * Get a comment by ID
	 *
	 * @param int $id
	 * @return object|null
	 */
	public function getComment($id)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$currentUserId = Factory::getUser()->id;
		$baseUrl = rtrim(\Joomla\CMS\Uri\Uri::root(), '/');
		$query->select([
				'c.*',
				'u.name as created_by',
				'u.email as created_by_email',
				'u.id as created_by_id',
				'COUNT(l.id) as likes_count',
				'CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as user_liked',
				'CASE WHEN p.profile_value IS NOT NULL THEN CONCAT(' . $db->quote($baseUrl) . ', JSON_UNQUOTE(p.profile_value)) ELSE NULL END as profile_image'
			])
			->from($db->quoteName('#__sppagebuilder_comments', 'c'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('c.created_by'))
			->join('LEFT', $db->quoteName('#__sppagebuilder_likes', 'l') . ' ON ' . $db->quoteName('l.comment_id') . ' = ' . $db->quoteName('c.id'))
			->join('LEFT', $db->quoteName('#__sppagebuilder_likes', 'ul') . ' ON ' . $db->quoteName('ul.comment_id') . ' = ' . $db->quoteName('c.id') . ' AND ' . $db->quoteName('ul.user_id') . ' = ' . (int) $currentUserId)
			->join('LEFT', $db->quoteName('#__user_profiles', 'p') . ' ON ' . $db->quoteName('p.user_id') . ' = ' . $db->quoteName('c.created_by') . ' AND ' . $db->quoteName('p.profile_key') . ' = ' . $db->quote('profileimage.profile_image'))
			->where($db->quoteName('c.id') . ' = ' . (int) $id)
			->where($db->quoteName('c.published') . ' IN (1, 0)')
			->group($db->quoteName('c.id'));

		$db->setQuery($query);

		try
		{
			return $db->loadObject();
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	/**
	 * Delete a comment and its children
	 *
	 * @param int $id
	 * @return boolean
	 */
	public function deleteComment($id)
	{
		$db = $this->getDbo();

		$comment = $this->getComment($id);
		
		$childComments = $this->getChildComments($id);
		
		foreach ($childComments as $child)
		{
			$this->deleteComment($child->id);
		}
		
		$query = $db->getQuery(true);
		$query->delete($db->quoteName('#__sppagebuilder_comments'))
			->where($db->quoteName('id') . ' = ' . (int) $id);

		$db->setQuery($query);

		try
		{
			$db->execute();
			
			if ($comment && $comment->parent_id)
			{
				$this->updateParentReplyCount($comment->parent_id);
			}
			
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Get all child comments recursively
	 *
	 * @param int $parentId
	 * @return array
	 */
	private function getChildComments($parentId)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$currentUserId = Factory::getUser()->id;
		$baseUrl = rtrim(\Joomla\CMS\Uri\Uri::root(), '/');
		$query->select([
				'c.*',
				'u.name as created_by',
				'u.email as created_by_email',
				'COUNT(l.id) as likes_count',
				'CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as user_liked',
				'CASE WHEN p.profile_value IS NOT NULL THEN CONCAT(' . $db->quote($baseUrl) . ', JSON_UNQUOTE(p.profile_value)) ELSE NULL END as profile_image'
			])
			->from($db->quoteName('#__sppagebuilder_comments', 'c'))
			->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('c.created_by'))
			->join('LEFT', $db->quoteName('#__sppagebuilder_likes', 'l') . ' ON ' . $db->quoteName('l.comment_id') . ' = ' . $db->quoteName('c.id'))
			->join('LEFT', $db->quoteName('#__sppagebuilder_likes', 'ul') . ' ON ' . $db->quoteName('ul.comment_id') . ' = ' . $db->quoteName('c.id') . ' AND ' . $db->quoteName('ul.user_id') . ' = ' . (int) $currentUserId)
			->join('LEFT', $db->quoteName('#__user_profiles', 'p') . ' ON ' . $db->quoteName('p.user_id') . ' = ' . $db->quoteName('c.created_by') . ' AND ' . $db->quoteName('p.profile_key') . ' = ' . $db->quote('profileimage.profile_image'))
			->where($db->quoteName('c.parent_id') . ' = ' . (int) $parentId)
			->where($db->quoteName('c.published') . ' IN (1, 0)')
			->group($db->quoteName('c.id'))
			->order($db->quoteName('c.created_on') . ' DESC');

		$db->setQuery($query);

		try
		{
			return $db->loadObjectList();
		}
		catch (Exception $e)
		{
			return array();
		}
	}

	/**
	 * Update parent comment's reply count
	 *
	 * @param int $parentId
	 * @return boolean
	 */
	private function updateParentReplyCount($parentId)
	{
		$db = $this->getDbo();
		
		$query = $db->getQuery(true);
		$query->select('COUNT(*)')
			->from($db->quoteName('#__sppagebuilder_comments'))
			->where($db->quoteName('parent_id') . ' = ' . (int) $parentId);

		$db->setQuery($query);

		try
		{
			$replyCount = $db->loadResult();
			
			$updateQuery = $db->getQuery(true);
			$updateQuery->update($db->quoteName('#__sppagebuilder_comments'))
				->set($db->quoteName('replies') . ' = ' . (int) $replyCount)
				->where($db->quoteName('id') . ' = ' . (int) $parentId);

			$db->setQuery($updateQuery);
			$db->execute();
			
			return true;
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	private function getLikes($commentId)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		
		$query->select('COUNT(*) as count')
			->from($db->quoteName('#__sppagebuilder_likes'))
			->where($db->quoteName('comment_id') . ' = ' . (int) $commentId);

		$db->setQuery($query);
		return (int) $db->loadResult();
	}

	private function getUserLikes($commentId, $userId)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);
		
		$query->select('COUNT(*) as count')
			->from($db->quoteName('#__sppagebuilder_likes'))
			->where($db->quoteName('comment_id') . ' = ' . (int) $commentId)
			->where($db->quoteName('user_id') . ' = ' . (int) $userId);

		$db->setQuery($query);
		return (int) $db->loadResult() > 0;
	}

	public function likeComment($commentId, $userId)
	{
		$db = $this->getDbo();
		
		// Check if user already liked this comment
		$alreadyLiked = $this->getUserLikes($commentId, $userId);
		
		if ($alreadyLiked) {
			// Remove like
			$query = $db->getQuery(true);
			$query->delete($db->quoteName('#__sppagebuilder_likes'))
				->where($db->quoteName('comment_id') . ' = ' . (int) $commentId)
				->where($db->quoteName('user_id') . ' = ' . (int) $userId);
		} else {
			// Add like
			$record = (object) [
				'id' => null,
				'user_id' => $userId,
				'comment_id' => $commentId,
				'created' => Date::sqlSafeDate()
			];
			
			$db->insertObject('#__sppagebuilder_likes', $record);
		}
		
		if ($alreadyLiked) {
			$db->setQuery($query);
			$db->execute();
		}
		
		return true;
	}

	/**
	 * Check if user can update comment
	 *
	 * @param int $commentId
	 * @param int $userId
	 * @return boolean
	 */
	public function canUpdateComment($commentId, $userId)
	{
		$comment = $this->getComment($commentId);
		
		if (!$comment)
		{
			return false;
		}

		$user = Factory::getUser();
		
		if ($user->authorise('core.admin'))
		{
			return true;
		}
		
		// Anonymous comments cannot be updated
		if (!$comment->created_by) {
			return false;
		}
		
		if ($comment->created_by == $userId)
		{
			return true;
		}
		
		return false;
	}

    public function createComment($payload){

        if(empty($payload['item_id']) || empty($payload['content'])) {
            return false;
        }

        // Allow anonymous comments (created_by can be null)
        if (!isset($payload['created_by'])) {
            $payload['created_by'] = null;
        }

        try {
            $db = $this->getDbo();

            $record = (object) [
                'id' => null,
                'item_id' => $payload['item_id'],
				'source_type' => !empty($payload['source_type']) ? $payload['source_type'] : 'articles',
                'created_by' => $payload['created_by'],
				'created_on' => Date::sqlSafeDate(),
				'modified' => Date::sqlSafeDate(),
                'content' => $payload['content'],
                'replies' => 0,
                'parent_id' => isset($payload['parent_id']) ? $payload['parent_id'] : null,
				'published' => $payload['published'],
            ];

            $result = $db->insertObject('#__sppagebuilder_comments', $record, 'id');
			$lastInsertId = $db->insertid();

			if ($result) {
				if ($record->parent_id) {
					$this->updateParentReplyCount($record->parent_id);
				}
				return $this->getComment($lastInsertId);
			} else {
				return false;
			}

        } catch (\Exception $e) {
            return false;
        }

    }

    public function getAllComments($itemId = null, $sourceType = 'articles'){
		if(empty($itemId)) {
			return false;
		}
		
        try{
            $db = $this->getDbo();
			$currentUserId = Factory::getUser()->id;
			$baseUrl = rtrim(\Joomla\CMS\Uri\Uri::root(), '/');
			$query = $db->getQuery(true);

			$query->select([
					'c.*',
					'COALESCE(u.name, "Anonymous Person") as created_by',
					'u.id as created_by_id',
					'u.email as created_by_email',
					'COUNT(l.id) as likes_count',
					'CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as user_liked',
					'CASE WHEN p.profile_value IS NOT NULL THEN CONCAT(' . $db->quote($baseUrl) . ', JSON_UNQUOTE(p.profile_value)) ELSE NULL END as profile_image'
				])
				->from($db->quoteName('#__sppagebuilder_comments', 'c'))
				->join('LEFT', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('c.created_by') . ' AND ' . $db->quoteName('c.created_by') . ' IS NOT NULL')
				->join('LEFT', $db->quoteName('#__sppagebuilder_likes', 'l') . ' ON ' . $db->quoteName('l.comment_id') . ' = ' . $db->quoteName('c.id'))
				->join('LEFT', $db->quoteName('#__sppagebuilder_likes', 'ul') . ' ON ' . $db->quoteName('ul.comment_id') . ' = ' . $db->quoteName('c.id') . ' AND ' . $db->quoteName('ul.user_id') . ' = ' . (int) $currentUserId)
				->join('LEFT', $db->quoteName('#__user_profiles', 'p') . ' ON ' . $db->quoteName('p.user_id') . ' = ' . $db->quoteName('c.created_by') . ' AND ' . $db->quoteName('p.profile_key') . ' = ' . $db->quote('profileimage.profile_image'))
				->where($db->quoteName('c.item_id') . ' = ' . (int) $itemId)
				->where($db->quoteName('c.source_type') . ' = ' . $db->quote($sourceType))
				->where($db->quoteName('c.published') . ' IN (1, 0)')
				->group($db->quoteName('c.id'))
				->order($db->quoteName('c.created_on') . ' DESC');

			$db->setQuery($query);
			return $db->loadObjectList();

        } catch (\Exception $e) {
            return [];
        }
    }

	public function getApprovedComments($userId, $itemId, $sourceType = 'articles')
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		$query->select('COUNT(*)')
			->from($db->quoteName('#__sppagebuilder_comments'))
			->where($db->quoteName('created_by') . ' = ' . (int) $userId)
			->where($db->quoteName('item_id') . ' = ' . (int) $itemId)
			->where($db->quoteName('source_type') . ' = ' . $db->quote($sourceType))
			->where($db->quoteName('published') . ' = 1');

		$db->setQuery($query);
		
		try {
			return (int) $db->loadResult();
		} catch (Exception $e) {
			return 0;
		}
	}
}