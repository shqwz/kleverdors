<?php
/**
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Concerns;

defined('_JEXEC') or die;

use Closure;
use Exception;
use JoomShaper\SPPageBuilder\DynamicContent\Constants\Validator as ValidatorConstants;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;

trait Validator
{
    /**
     * The errors array
     * 
     * @var array
     * 
     * @since 5.5.0
     */
    public $errors = [];

    /**
     * Validate the data
     * 
     * @param array $data The data to validate
     * @param array $rules The rules to validate the data
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    public function validate(array $data, array $rules)
    {
        // Check the rules is an associative array or not
        if (!Arr::isAssociative($rules)) {
            throw new Exception('The rules must be an associative array');
        }

        Arr::make($rules)->foreach(function ($rule, $key) use ($data) {

            $value = $data[$key] ?? null;

            // Check for closure rule
            if ($rule instanceof Closure) {
                $response = $rule($value, $key);

                if (!$response) {
                    $this->errors[$key] = sprintf('The %s field is invalid', $key);
                    return false;
                }

                return true;
            }

            $ruleSet = explode('|', $rule);
            $ruleSet = Arr::make($ruleSet);

            $checkingRules = $ruleSet->map(function ($rule) {
                $rule = explode(':', $rule, 2);
                return $rule[0];
            })->toArray();


            $ruleSet->every(function ($rule) use ($value, $key, $checkingRules) {
                $rule = explode(':', $rule, 2);
                $ruleName = $rule[0];
                $ruleValue = isset($rule[1]) ? $rule[1] : null;

                // If the required rule is not in the checking rules and the value is falsy, then skip the validation
                if (!in_array('required', $checkingRules) && !$this->validateRequired($value)) {
                    return true;
                }

                $method = ValidatorConstants::RULE_MAP[$ruleName];
                return $this->$method($value, $key, $ruleValue);
            });
        });

        if (!empty($this->errors)) {
            return false;
        }

        return true;
    }

    /**
     * Validate the required value
     * 
     * @param mixed $value The value to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function validateRequired($value)
    {
        if (is_null($value)) {
            return false;
        } elseif (!isset($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif (is_countable($value) && count($value) < 1) {
            return false;
        }

        return true;
    }

    /**
     * Check the required rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * @since 5.5.0
     */
    protected function checkRequired($value, string $key)
    {
        if (!$this->validateRequired($value)) {
            $this->errors[$key] = sprintf('The %s field is required', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the string rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * @since 5.5.0
     */
    protected function checkString($value, string $key)
    {
        if (!is_string($value)) {
            $this->errors[$key] = sprintf('The %s field must be a string', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the array rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkArray($value, string $key)
    {
        if (!is_array($value)) {
            $this->errors[$key] = sprintf('The %s field must be an array', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the object rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkObject($value, string $key)
    {
        if (!is_object($value)) {
            $this->errors[$key] = sprintf('The %s field must be an object', $key);  
            return false;
        }
        return true;
    }

    /**
     * Check the boolean rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkBoolean($value, string $key)
    {
        if (!is_bool($value)) {
            $this->errors[$key] = sprintf('The %s field must be a boolean', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the integer rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkInteger($value, string $key)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$key] = sprintf('The %s field must be an integer', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the number rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkNumber($value, string $key)
    {
        if (!is_numeric($value)) {
            $this->errors[$key] = sprintf('The %s field must be a number', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the float rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkFloat($value, string $key)
    {
        if (!filter_var($value, FILTER_VALIDATE_FLOAT) === false) {
            $this->errors[$key] = sprintf('The %s field must be a float', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the email rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkEmail($value, string $key)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$key] = sprintf('The %s field must be a valid email address', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the url rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkUrl($value, string $key)
    {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$key] = sprintf('The %s field must be a valid URL', $key);
            return false;
        }
        return true;
    }

    /**
     * Check the min rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * @param int $min The minimum value
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkMin($value, string $key, int $min)
    {
        if (strlen($value) < $min) {
            $this->errors[$key] = sprintf('The %s field must be at least %s characters long', $key, $min);
            return false;
        }
        return true;
    }

    /**
     * Check the max rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * @param int $max The maximum value
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkMax($value, string $key, int $max)
    {
        if (strlen($value) > $max) {
            $this->errors[$key] = sprintf('The %s field must be at most %s characters long', $key, $max);
            return false;
        }
        return true;
    }

    /**
     * Check the in rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * @param string $in The allowed values
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkIn($value, string $key, string $in)
    {
        $in = explode(',', $in);

        if (!in_array($value, $in)) {
            $this->errors[$key] = sprintf('The %s field must be one of the following values: %s', $key, implode(', ', $in));
            return false;
        }
        return true;
    }

    /**
     * Check the not in rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * @param string $notIn The disallowed values
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkNotIn($value, string $key, string $notIn)
    {
        $notIn = explode(',', $notIn);

        if (in_array($value, $notIn)) {
            $this->errors[$key] = sprintf('The %s field must not be one of the following values: %s', $key, implode(', ', $notIn));
            return false;
        }
        return true;
    }

    /**
     * Check the regex rule
     * 
     * @param mixed $value The value to validate
     * @param string $key The key to validate
     * @param string $regex The regex pattern
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    protected function checkRegex($value, string $key, string $regex)
    {
        if (!preg_match($regex, $value)) {
            $this->errors[$key] = sprintf('The %s field must match the following regex: %s', $key, $regex);
            return false;
        }
        return true;
    }

    /**
     * Check if the validator has errors
     * 
     * @return bool
     * 
     * @since 5.5.0
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get the errors
     * 
     * @return array
     * 
     * @since 5.5.0
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
