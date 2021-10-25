<?php

namespace NSWDPC\Utilities\Cache;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * Simple log handling
 */
class Logger
{
    public static function log($message, $level = "DEBUG")
    {
        Injector::inst()->get(LoggerInterface::class)->log($level, $message);
    }
}
