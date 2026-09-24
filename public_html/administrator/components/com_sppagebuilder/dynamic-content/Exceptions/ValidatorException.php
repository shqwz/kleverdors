<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Exceptions;

use Exception;

class ValidatorException extends Exception
{
    /**
     * The validation errors.
     * 
     * @var array
     * @since 5.5.0
     */
    protected $data;

    /**
     * Constructor.
     * 
     * @param array $data The validation errors.
     * @param string $message The error message.
     * @param int $code The error code.
     * @param Exception $previous The previous exception.
     *
     * @since 5.5.0
     */
    public function __construct(array $data, $message = 'Validation failed', $code = 400, Exception $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the validation errors.
     * 
     * @return array
     * @since 5.5.0
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Convert the exception to a string.
     * 
     * @return string
     * @since 5.5.0
     */
    public function __toString()
    {
        return json_encode($this->getData());
    }
}
