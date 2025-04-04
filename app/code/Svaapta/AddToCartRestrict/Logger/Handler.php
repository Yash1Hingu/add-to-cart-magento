<?php
namespace Svaapta\AddToCartRestrict\Logger;

use Magento\Framework\Logger\Handler\Base as MagentoBaseHandler;
use Monolog\Logger as MonologLogger;

class Handler extends MagentoBaseHandler
{
    protected $fileName = '/var/log/svaapta_restrict_cart.log';
    protected $loggerType = MonologLogger::DEBUG;
}