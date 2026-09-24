<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Version;
use Joomla\Input\Input;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Request;
use JoomShaper\SPPageBuilder\DynamicContent\Http\Response;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;

class DynamicContent
{
    /**
     * The instance of the dynamic content
     * 
     * @var self
     * @since 5.5.0
     */
    private static $instance = null;

    /**
     * The controller instance
     * 
     * @var Controller
     * @since 5.5.0
     */
    private $controller = null;

    /**
     * The controller namespace
     * 
     * @var string
     * @since 5.5.0
     */
    protected const CONTROLLER_NAMESPACE = "JoomShaper\\SPPageBuilder\\DynamicContent\\Controllers\\";

    /**
     * The service namespace
     * 
     * @var string
     * @since 5.5.0
     */
    protected const SERVICE_NAMESPACE = "JoomShaper\\SPPageBuilder\\DynamicContent\\Services\\";

    /**
     * Constructor method
     * 
     * @param Controller $controller The controller
     * 
     * @since 5.5.0
     */
    private function __construct($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Create a new instance of the dynamic content
     * 
     * @param Controller $controller The controller
     * 
     * @return self
     * @since 5.5.0
     */
    public static function create($controller)
    {
        if (self::$instance === null) {
            self::$instance = new static($controller);
        }

        return self::$instance;
    }

    /**
     * Dispatch the request
     * 
     * @param string $task The task
     * @param Input $input The input
     * 
     * @return Response
     * @since 5.5.0
     */
    public function dispatch(string $task, Input $input)
    {
        $joomlaVersion = defined('JVERSION') ? JVERSION : (new Version())->getShortVersion();
        $isJoomla6OrHigher = version_compare($joomlaVersion, '6.0', '>=');
        
        if ($isJoomla6OrHigher) {
            $context = $input->getString('_context', '');
            
            $requestData = array_merge(
                $input->get->getArray(),
                $input->post->getArray()
            );
        } else {
            $context = $input->getString('_context', '');
            $requestData = $input->getArray();
        }
        
        /** @var Request $request */
        $request = new Request($requestData);

        if (empty($context)) {
            return response()->json(['message' => 'Context is required. Please provide a valid context for the request using the `_context` parameter.'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($task)) {
            return response()->json(['message' => 'Task is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!Session::checkToken()) {
            return response()->json(['message' => 'Invalid token provided'], Response::HTTP_UNAUTHORIZED);
        }

        $task = $this->createRequestMethod($task);
        $controllerClass = $this->createControllerClassName($context);

        if (!class_exists($controllerClass)) {
            return response()->json(['message' => sprintf('Controller class `%s` not found', $controllerClass)], Response::HTTP_NOT_FOUND);
        }

        $serviceClass = $this->createServiceClassName($context);
        $service = null;

        if (class_exists($serviceClass)) {
            $service = new $serviceClass();
        }

        $controller = new $controllerClass($service, $this->controller);

        if (!method_exists($controller, $task)) {
            return response()->json(['message' => 'Invalid endpoint provided'], Response::HTTP_NOT_FOUND);
        }

        return $controller->$task($request);
    }

    /**
     * Create a request method from the request parameter named task.
     * 
     * @param string $task The task
     * 
     * @return string
     * @since 5.5.0
     */
    protected function createRequestMethod(string $task)
    {
        $task = preg_replace('/[^a-zA-Z0-9-]/', '', $task);
        $parts = explode('-', $task);
        return Arr::make($parts)->map(function ($part, $index) {
            $part = strtolower($part);
            if ($index === 0) {
                return $part;
            }
            return ucfirst($part);
        })->join('');
    }

    /**
     * Create a controller class name from the context
     * 
     * @param string $context The context
     * 
     * @return string
     * @since 5.5.0
     */
    protected function createControllerClassName(string $context)
    {
        $context = preg_replace('/[^a-zA-Z0-9-]/', '', $context);
        $parts = explode('-', $context);
        $baseName = Arr::make($parts)->map(function ($part) {
            $part = strtolower($part);
            return ucfirst($part);
        })->join('');

        return static::CONTROLLER_NAMESPACE . $baseName . 'Controller';
    }

    /**
     * Create a service class name from the context
     * 
     * @param string $context The context
     * 
     * @return string
     * @since 5.5.0
     */
    protected function createServiceClassName(string $context)
    {
        $context = preg_replace('/[^a-zA-Z0-9-]/', '', $context);
        $parts = explode('-', $context);
        $baseName = Arr::make($parts)->map(function ($part) {
            $part = strtolower($part);
            return ucfirst($part);
        })->join('');

        return static::SERVICE_NAMESPACE . $baseName . 'Service';
    }
}
