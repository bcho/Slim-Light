<?php

require_once __DIR__ . '/../vendor/autoload.php';


class Light_TestCase extends PHPUnit_Framework_TestCase
{
    protected $defaultSettings = array(
        'version' => '0.0.0',
        'debug' => false,
        'mode' => 'testing'
    );

    protected function getDefaultAPP()
    {
        return new \Slim\Light\Light($this->defaultSettings);
    }
}
