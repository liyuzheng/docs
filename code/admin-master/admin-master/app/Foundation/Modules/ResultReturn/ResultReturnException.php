<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/2/21
 * Time: 上午11:25
 */

namespace App\Foundation\Modules\ResultReturn;

use Throwable;

/**
 * Class ResultReturnException
 * @package App\Foundation\Modules\ResultReturn
 */
class ResultReturnException extends \Exception
{
    /**
     * ResultReturnException constructor.
     *
     * @param  string          $message
     * @param  int             $code
     * @param  Throwable|null  $previous
     */
    public function __construct(string $message = "", int $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code);
    }
}