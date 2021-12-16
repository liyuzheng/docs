<?php

namespace App\Foundation\Handlers\Gio;

use PHPUnit\Util\Exception;

class GrowingIO
{

    private $_accountID;
    private $_options;
    private $_consumer;

    private static $_instance = null;

    function validateAccountID($accountID)
    {
        if ($accountID == null) throw new Exception("accountID is null");
        if (strlen($accountID) <> 16 && strlen($accountID) <> 32)
            throw new Exception("accountID length error");
    }

    private function currentMillisecond()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return $msectime;
    }

    /**
     * Instantiates a new GrowingIO instance.
     *
     * @param         $accountID
     * @param  array  $options
     */
    private function __construct($accountID, $options = array())
    {
        $this->validateAccountID($accountID);
        $this->_accountID = $accountID;
        $this->_options   = array_merge($options, array("accountId" => $accountID));
        $this->_consumer  = new SimpleConsumer($this->_options);
    }

    /**
     * Returns a singleton instance of GrowingIO
     *
     * @param         $accountID
     * @param  array  $options
     *
     * @return GrowingIO
     */
    public static function getInstance($accountID, $options = array())
    {
        if (self::$_instance == null) {
            self::$_instance = new GrowingIO($accountID, $options);
        }

        return self::$_instance;
    }

    /**
     * track a custom event
     *
     * @param  string  $loginUserId  登录用户ID
     * @param  string  $eventKey     埋点key
     * @param  array   $properties   埋点内容
     *
     * @return void
     */
    public function track($loginUserId, $eventKey, $properties)
    {
        $event = new CustomEvent();
        $event->eventTime($this->currentMillisecond());
        $event->eventKey($eventKey);
        $event->loginUserId($loginUserId);
        $event->eventProperties($properties);
        $this->_consumer->consume($event);
    }
}
