<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Constants;

defined('_JEXEC') or die;

class Status
{
    /**
     * The published status
     * 
     * @var int
     * 
     * @since 5.5.0
     */
    public const PUBLISHED = 1;

    /**
     * The unpublished status
     * 
     * @var int
     * 
     * @since 5.5.0
     */
    public const UNPUBLISHED = 0;

    /**
     * The all status
     * 
     * @var string
     * 
     * @since 5.5.0
     */
    public const ALL = '*';

    /**
     * The trashed status
     * 
     * @var int
     * 
     * @since 5.5.0
     */
    public const TRASHED = -2;
}
