<?php
/**
 * Created by PhpStorm.
 * User: reliy
 * Date: 2019/2/13
 * Time: 2:42 PM
 */

namespace App\Exceptions;

use Exception;

/**
 * Throws an internal server error
 *
 * Class ServiceException
 * @package App\Exceptions
 */
class ServiceException extends Exception
{
    /**
     * ServiceException constructor.
     *
     * @param  string  $message
     * @param  int     $code
     */
    public function __construct(string $message = "", int $code = 500)
    {
        parent::__construct($message, $code);
    }
}