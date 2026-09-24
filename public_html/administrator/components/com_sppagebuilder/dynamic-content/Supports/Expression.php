<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Supports;

class Expression
{
    /**
     * The value of the expression
     * 
     * @var string|int|float
     * @since 5.5.0
     */
    protected $value;

    /**
     * Create a new expression instance
     * 
     * @param string|int|float $value The value of the expression
     * @since 5.5.0
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value of the expression
     * 
     * @return string|int|float
     * @since 5.5.0
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the string representation of the expression
     * 
     * @return string
     * @since 5.5.0
     */
    public function __toString()
    {
        return $this->getValue();
    }
}
