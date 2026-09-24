<?php
/*
 * @package SP Page Builder
 * @author JoomShaper http://www.joomshaper.com
 * @copyright Copyright (c) 2010 - 2024 JoomShaper
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 or later
 */

namespace JoomShaper\SPPageBuilder\DynamicContent\Http;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use JoomShaper\SPPageBuilder\DynamicContent\Model;
use JoomShaper\SPPageBuilder\DynamicContent\Supports\Arr;

class Response
{
    /**
     * HTTP Status Codes
     * 
     * @since 5.5.0
     */
    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_PROCESSING = 102;            // RFC2518
    public const HTTP_EARLY_HINTS = 103;           // RFC8297
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_RESET_CONTENT = 205;
    public const HTTP_PARTIAL_CONTENT = 206;
    public const HTTP_MULTI_STATUS = 207;          // RFC4918
    public const HTTP_ALREADY_REPORTED = 208;      // RFC5842
    public const HTTP_IM_USED = 226;               // RFC3229
    public const HTTP_MULTIPLE_CHOICES = 300;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_USE_PROXY = 305;
    public const HTTP_RESERVED = 306;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_PAYMENT_REQUIRED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_REQUEST_TIMEOUT = 408;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PRECONDITION_FAILED = 412;
    public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    public const HTTP_REQUEST_URI_TOO_LONG = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
    public const HTTP_MISDIRECTED_REQUEST = 421;                                         // RFC7540
    public const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
    public const HTTP_LOCKED = 423;                                                      // RFC4918
    public const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
    public const HTTP_TOO_EARLY = 425;                                                   // RFC-ietf-httpbis-replay-04
    public const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
    public const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
    public const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585
    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;                               // RFC7725
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
    public const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
    public const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
    public const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;

    // Connection Errors
    public const MYSQL_CANNOT_CONNECT = 2002;
    public const MYSQL_CANNOT_CONNECT_PORT = 2003;
    public const MYSQL_SERVER_GONE = 2006;
    public const MYSQL_PROTOCOL_MISMATCH = 2007;
    public const MYSQL_LOST_CONNECTION = 2013;

    // Syntax and Query Errors
    public const MYSQL_TABLE_ALREADY_EXISTS = 1050;
    public const MYSQL_UNKNOWN_TABLE = 1051;
    public const MYSQL_DUPLICATE_ENTRY = 1062;
    public const MYSQL_SYNTAX_ERROR = 1064;
    public const MYSQL_TABLE_DOES_NOT_EXIST = 1146;

    // Data Errors
    public const MYSQL_COLUMN_CANNOT_BE_NULL = 1048;
    public const MYSQL_NO_DEFAULT_VALUE = 1364;
    public const MYSQL_INCORRECT_STRING_VALUE = 1366;

    // Constraint Violations
    public const MYSQL_FOREIGN_KEY_CONSTRAINT_FAILS_ADD_UPDATE = 1216;
    public const MYSQL_FOREIGN_KEY_CONSTRAINT_FAILS_DELETE = 1217;
    public const MYSQL_FOREIGN_KEY_CONSTRAINT_FAILS_CHILD = 1452;

    // Authentication and Permission Errors
    public const MYSQL_ACCESS_DENIED_FOR_USER = 1045;
    public const MYSQL_ACCESS_DENIED_FOR_ROOT = 1698;

    // Server Errors
    public const MYSQL_CANNOT_CREATE_TABLE = 1005;
    public const MYSQL_CANNOT_CREATE_DATABASE = 1006;
    public const MYSQL_DATABASE_EXISTS = 1007;
    public const MYSQL_DATABASE_DOES_NOT_EXIST = 1008;

    // Other Errors
    public const MYSQL_DATA_TOO_LONG = 1406;
    public const MYSQL_UNKNOWN_DATABASE = 1049;
    public const MYSQL_UNKNOWN_COLUMN = 1054;
    public const MYSQL_DUPLICATE_COLUMN_NAME = 1060;
    public const MYSQL_CANNOT_DROP_COLUMN_KEY_NOT_EXIST = 1091;

    /**
     * @var mixed
     * @since 5.5.0
     */
    protected $content = null;

    /**
     * @var array
     * @since 5.5.0
     */
    protected $headers = [];

    /**
     * @var int
     * @since 5.5.0
     */
    protected $status = self::HTTP_OK;

    /**
     * Constructor
     * 
     * @param array $headers
     * @since 5.5.0
     */
    private function __construct(array $headers = [])
    {
        $this->setHeaders($headers);
    }

    /**
     * Get all HTTP codes
     * 
     * @return array
     * @since 5.5.0
     */
    public function httpCodes()
    {
        return [
            self::HTTP_CONTINUE,
            self::HTTP_SWITCHING_PROTOCOLS,
            self::HTTP_PROCESSING,
            self::HTTP_EARLY_HINTS,
            self::HTTP_OK,
            self::HTTP_CREATED,
            self::HTTP_ACCEPTED,
            self::HTTP_NON_AUTHORITATIVE_INFORMATION,
            self::HTTP_NO_CONTENT,
            self::HTTP_RESET_CONTENT,
            self::HTTP_PARTIAL_CONTENT,
            self::HTTP_MULTI_STATUS,
            self::HTTP_ALREADY_REPORTED,
            self::HTTP_IM_USED,
            self::HTTP_MULTIPLE_CHOICES,
            self::HTTP_MOVED_PERMANENTLY,
            self::HTTP_FOUND,
            self::HTTP_SEE_OTHER,
            self::HTTP_NOT_MODIFIED,
            self::HTTP_USE_PROXY,
            self::HTTP_RESERVED,
            self::HTTP_TEMPORARY_REDIRECT,
            self::HTTP_PERMANENTLY_REDIRECT,
            self::HTTP_BAD_REQUEST,
            self::HTTP_UNAUTHORIZED,
            self::HTTP_PAYMENT_REQUIRED,
            self::HTTP_FORBIDDEN,
            self::HTTP_NOT_FOUND,
            self::HTTP_METHOD_NOT_ALLOWED,
            self::HTTP_NOT_ACCEPTABLE,
            self::HTTP_PROXY_AUTHENTICATION_REQUIRED,
            self::HTTP_REQUEST_TIMEOUT,
            self::HTTP_CONFLICT,
            self::HTTP_GONE,
            self::HTTP_LENGTH_REQUIRED,
            self::HTTP_PRECONDITION_FAILED,
            self::HTTP_REQUEST_ENTITY_TOO_LARGE,
            self::HTTP_REQUEST_URI_TOO_LONG,
            self::HTTP_UNSUPPORTED_MEDIA_TYPE,
            self::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE,
            self::HTTP_EXPECTATION_FAILED,
            self::HTTP_I_AM_A_TEAPOT,
            self::HTTP_MISDIRECTED_REQUEST,
            self::HTTP_UNPROCESSABLE_ENTITY,
            self::HTTP_LOCKED,
            self::HTTP_FAILED_DEPENDENCY,
            self::HTTP_TOO_EARLY,
            self::HTTP_UPGRADE_REQUIRED,
            self::HTTP_PRECONDITION_REQUIRED,
            self::HTTP_TOO_MANY_REQUESTS,
            self::HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE,
            self::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS,
            self::HTTP_INTERNAL_SERVER_ERROR,
            self::HTTP_NOT_IMPLEMENTED,
            self::HTTP_BAD_GATEWAY,
            self::HTTP_SERVICE_UNAVAILABLE,
            self::HTTP_GATEWAY_TIMEOUT,
            self::HTTP_VERSION_NOT_SUPPORTED,
            self::HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL,
            self::HTTP_INSUFFICIENT_STORAGE,
            self::HTTP_LOOP_DETECTED,
            self::HTTP_NOT_EXTENDED,
            self::HTTP_NETWORK_AUTHENTICATION_REQUIRED,
        ];
    }

    public function mysqlCodes()
    {
        return [
            self::MYSQL_CANNOT_CONNECT,
            self::MYSQL_CANNOT_CONNECT_PORT,
            self::MYSQL_SERVER_GONE,
            self::MYSQL_PROTOCOL_MISMATCH,
            self::MYSQL_LOST_CONNECTION,
            self::MYSQL_TABLE_ALREADY_EXISTS,
            self::MYSQL_UNKNOWN_TABLE,
            self::MYSQL_DUPLICATE_ENTRY,
            self::MYSQL_SYNTAX_ERROR,
            self::MYSQL_TABLE_DOES_NOT_EXIST,
            self::MYSQL_COLUMN_CANNOT_BE_NULL,
            self::MYSQL_NO_DEFAULT_VALUE,
            self::MYSQL_INCORRECT_STRING_VALUE,
            self::MYSQL_FOREIGN_KEY_CONSTRAINT_FAILS_ADD_UPDATE,
            self::MYSQL_FOREIGN_KEY_CONSTRAINT_FAILS_DELETE,
            self::MYSQL_FOREIGN_KEY_CONSTRAINT_FAILS_CHILD,
            self::MYSQL_ACCESS_DENIED_FOR_USER,
            self::MYSQL_ACCESS_DENIED_FOR_ROOT,
            self::MYSQL_CANNOT_CREATE_TABLE,
            self::MYSQL_CANNOT_CREATE_DATABASE,
            self::MYSQL_DATABASE_EXISTS,
            self::MYSQL_DATABASE_DOES_NOT_EXIST,
            self::MYSQL_DATA_TOO_LONG,
            self::MYSQL_UNKNOWN_DATABASE,
            self::MYSQL_UNKNOWN_COLUMN,
            self::MYSQL_DUPLICATE_COLUMN_NAME,
            self::MYSQL_CANNOT_DROP_COLUMN_KEY_NOT_EXIST,
        ];
    }

    /**
     * Create a new response instance
     * 
     * @return self
     * @since 5.5.0
     */
    public static function create()
    {
        return new static;
    }

    /**
     * Set the headers
     * 
     * @param array $headers
     *
     * @return self
     * @since 5.5.0
     */
    public function withHeaders(array $headers = [])
    {
        $this->setHeaders($headers);
        return $this;
    }

    /**
     * Set the header
     * 
     * @param string $key
     * @param string $value
     *
     * @return self
     * @since 5.5.0
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Get the header
     * 
     * @param string $key
     *
     * @return string|null
     * @since 5.5.0
     */
    public function getHeader($key)
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Set the headers
     * 
     * @param array $headers
     *
     * @return self
     * @since 5.5.0
     */
    public function setHeaders(array $headers = [])
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Get the headers
     * 
     * @return array
     * @since 5.5.0
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set the content
     * 
     * @param mixed $content
     *
     * @return self
     * @since 5.5.0
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get the content
     * 
     * @return mixed
     * @since 5.5.0
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the status code
     * 
     * @param int $status
     *
     * @return self
     * @since 5.5.0
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Check if the code is a database error
     * 
     * @param int $code
     * @return bool
     * @since 5.5.0
     */
    protected function isDatabaseError(int $code)
    {
        return in_array($code, $this->mysqlCodes());
    }

    /**
     * Check if the code is a HTTP error
     * 
     * @param int $code
     * @return bool
     * @since 5.5.0
     */
    protected function isHttpError(int $code)
    {
        return in_array($code, $this->httpCodes());
    }

    /**
     * Get the database error status
     * 
     * @param int $code
     * @return int
     * @since 5.5.0
     */
    protected function parseStatusCode(int $code)
    {
        // Common MySQL error codes and their HTTP equivalents
        if ($code >= 1040 && $code <= 1053) {
            // Connection/server issues
            return self::HTTP_SERVICE_UNAVAILABLE;
        }

        if ($code >= 1060 && $code <= 1099) {
            // Column/table definition issues
            return self::HTTP_UNPROCESSABLE_ENTITY;
        }

        if ($code >= 1100 && $code <= 1199) {
            // General database errors
            return self::HTTP_INTERNAL_SERVER_ERROR;
        }

        if ($this->isDatabaseError($code) || !$this->isHttpError($code)) {
            return self::HTTP_INTERNAL_SERVER_ERROR;
        }

        // Default to internal server error for unknown codes
        return $code;
    }

    /**
     * Get the status code
     * 
     * @since 5.5.0
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Send the JSON response
     * 
     * @since 5.5.0
     */
    public function json($data, $status = self::HTTP_OK)
    {
        $this->setHeader('Content-Type', 'application/json')
            ->setContent($data)
            ->setStatus($status);

        return $this->send();
    }

    /**
     * Send the response
     * 
     * @since 5.5.0
     */
    protected function send()
    {
        /** @var CMSApplication $app */
        $app = Factory::getApplication();
        $app->setHeader('Content-Type', $this->getHeader('Content-Type'));
        $app->setHeader('status', $this->parseStatusCode($this->getStatus()), true);
        $app->sendHeaders();

        $data = $this->getContent();

        if ($this->isDatabaseError($this->getStatus())) {
            $data = ['message' => 'Something went wrong. Please try again later.'];
        }

        if (is_array($data)) {
            $data = array_map(function ($item) {
                return $item instanceof Model ? $item->toArray() : $item;
            }, $data);
        } elseif ($data instanceof Model) {
            $data = $data->toArray();
        } elseif ($data instanceof Arr) {
            $data = $data->toArray();
        }

        echo new JsonResponse($data);
        $app->close();
    }
}
