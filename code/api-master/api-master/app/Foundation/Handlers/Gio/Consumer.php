<?php


namespace App\Foundation\Handlers\Gio;


abstract class Consumer
{
    public abstract function consume($event);
}
