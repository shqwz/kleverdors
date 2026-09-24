<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Content.sppagebuilderloadmodule
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use JoomShaper\Plugin\Content\Sppagebuilderloadmodule\Extension\Sppagebuilderloadmodule;

defined('_JEXEC') or die;


return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   5.4.3
     */
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $plugin     = new Sppagebuilderloadmodule(
                    $dispatcher,
                    (array) PluginHelper::getPlugin('content', 'sppagebuilderloadmodule')
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};