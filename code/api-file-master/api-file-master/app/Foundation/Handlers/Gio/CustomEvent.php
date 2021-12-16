<?php


namespace App\Foundation\Handlers\Gio;


class CustomEvent
{
    private $tm;
    private $n;
    private $cs1;
    private $var;
    private $t;

    public function __construct()
    {
        $this->tm = time() * 1000;
        $this->t  = "cstm";
    }

    public function eventTime($time)
    {
        $this->tm = $time;
    }

    public function eventKey($eventKey)
    {
        $this->n = $eventKey;
    }

    public function loginUserId($loginUserId)
    {
        $this->cs1 = $loginUserId;
    }

    public function EventProperties($properties)
    {
        $this->var = $properties;
    }

    public function jsonDecode() : array
    {
        $data = [];
        foreach ($this as $key => $val) {
            if ($val !== null) $data[$key] = $val;
        }

        return $data;
    }
}
