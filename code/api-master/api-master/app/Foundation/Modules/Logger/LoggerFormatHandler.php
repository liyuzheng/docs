<?php

namespace App\Foundation\Modules\Logger;


use Monolog\Formatter\LogstashFormatter;

/**
 * Class LoggerFormatHandler
 * @package App\Foundation\Services\Logger
 */
class LoggerFormatHandler
{
    /**
     * Set the custom logger format instance.
     *
     * @param  \Monolog\Logger  $logger
     *
     * @return void
     */
    public function __invoke($logger)
    {
        $formatter = new LogstashFormatter(config('app.name'));
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }
}
