<?php


namespace App\Foundation\Handlers\Gio;


class SimpleConsumer extends Consumer
{
    private $uploader;

    public function __construct($options)
    {
        $this->uploader = new JsonUploader($options);
    }

    public function consume($event)
    {
        $this->uploader->uploadEvents(array($event));
    }
}
