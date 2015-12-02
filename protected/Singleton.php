<?php

class Singleton
{
    protected static $inst = null;

    protected function __construct() {
    }

    public static function getInst() {
        if(is_null(static::$inst)) {
            static::$inst = new static;
        }
        return static::$inst;
    }

    private function __wakeup() {
    }

    private function __sleep() {
    }

    private function __clone() {
    }
}

