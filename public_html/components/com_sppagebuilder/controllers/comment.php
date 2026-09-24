<?php
/*
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2025 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use JoomShaper\SPPageBuilder\Comment\Services\CommentService;

class SppagebuilderControllerComment extends FormController{

    /**
     * @var CommentService
     */
    protected $commentService;

    /**
     * SppagebuilderControllerComment constructor.
     */
    // Initialize the controller with the CommentService
    public function __construct()
    {
        parent::__construct();
        $this->commentService = new CommentService();
    }

    /**
     * Handle comment requests
     *
     * @return JsonResponse
     */
    public function comments()
    {
        $method = $this->getInputMethod();

        switch ($method)
        {
            case 'POST':
                $action = $this->getInputAction();
                if($action == 'like'){
                    $this->likeComment();
                }else{
                    $this->createOrUpdateComment();
                }
                break;

            case 'DELETE':
                $this->deleteComment();
                break;
        }
    }

    /**
     * Create or update a comment
     *
     * @return JsonResponse
     */
    private function createOrUpdateComment()
    {
        $input = Factory::getApplication()->input;

        $contentType = $input->server->getString('CONTENT_TYPE', '');
    
        if (strpos($contentType, 'application/json') !== false) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
            $commentPayload = isset($jsonData['comment']) ? $jsonData['comment'] : [];
        } else {
            $commentPayload = $input->get('comment', [], 'array');
        }

        if (empty($commentPayload)) {
            return new JsonResponse(['error' => 'Invalid comment data'], 400);
        }

        $id = isset($commentPayload['id']) ? $commentPayload['id'] : null;

        if(empty($id)){
            $this->commentService->createComment($commentPayload);
        }else{
            $this->commentService->updateComment($id, $commentPayload);
        }
        
    }

    private function likeComment() {
        $input = Factory::getApplication()->input;
        $commentPayload = $input->get('comment', [], 'array');
        $commentId = $commentPayload['id'];
        $userId = Factory::getUser()->id;
        $this->commentService->likeComment($commentId, $userId);
        return new JsonResponse(['success' => true, 'message' => 'Comment liked successfully'], 200);
    }

    /**
     * Delete a comment
     *
     * @return JsonResponse
     */
    private function deleteComment() {
		$input = Factory::getApplication()->input;
        
        $contentType = $input->server->getString('CONTENT_TYPE', '');
    
        if (strpos($contentType, 'application/json') !== false) {
            $jsonData = json_decode(file_get_contents('php://input'), true);
            $commentPayload = isset($jsonData['comment']) ? $jsonData['comment'] : [];
        } else {
            $commentPayload = $input->get('comment', [], 'array');
        }

        if (empty($commentPayload)) {
            return new JsonResponse(['error' => 'Invalid comment data'], 400);
        }

        $id = isset($commentPayload['id']) ? $commentPayload['id'] : null;

        $this->commentService->deleteComment($id);
	}

    /**
     * Get the input method
     *
     * @return string
     */
    private function getInputMethod()
    {
        $input = Factory::getApplication()->input;
        $methodOverride = $input->getString('_method', '');
        
        if (!empty($methodOverride)) {
            return strtoupper($methodOverride);
        }
        return strtoupper($input->getMethod());
    }

    private function getInputAction() {
        $input = Factory::getApplication()->input;
        $commentPayload = $input->get('comment', [], 'array');
        return isset($commentPayload['action']) ? strtolower($commentPayload['action']) : null;
    }
}