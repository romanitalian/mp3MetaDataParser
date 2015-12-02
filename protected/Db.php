<?php

class Db extends Singleton
{
    private $DbHost = 'localhost';
    private $DbName = 'tmp';
    private $DbUser = 'root';
    private $DbPass = '';
    protected $cdb = null;

    public function __construct($connectionString = '', $userName = '', $userPassword = '') {
        $this->connent($connectionString, $userName, $userPassword);
    }

    private function connent($connectionString = '', $userName = '', $userPassword = '') {
        if(!$this->cdb) {
            $userName = $userName ?: $this->DbUser;
            $userPassword = $userPassword ?: $this->DbPass;
            if(!$connectionString) {
                $connectionString = 'mysql:host=' . $this->DbHost . ';dbname=' . $this->DbName . ';charset=utf8';
            }
            $this->cdb = new PDO($connectionString, $userName, $userPassword);
        }
    }
}
