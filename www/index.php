<?php
require_once('../getID3-1.9.10/getid3/getid3.php');

class Singleton
{
    protected static $inst = null;
    protected function __construct() {}
    public static function getInst() {
        if(is_null(static::$inst)) {
            static::$inst = new static;
        }
        return static::$inst;
    }

    private function __wakeup() {}
    private function __sleep() {}
    private function __clone() {}
}

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
            $userName = $userName ? : $this->DbUser;
            $userPassword = $userPassword ? : $this->DbPass;
            if(!$connectionString) {
                $connectionString = 'mysql:host=' .$this->DbHost. ';dbname=' .$this->DbName. ';charset=utf8';
            }
            $this->cdb = new PDO($connectionString, $userName, $userPassword);
        }
    }
}

class ORM extends DB
{
    public function getAll($sql, $returnType = 'obj') {
        $q = $this->cdb->prepare($sql);
        $q->execute();
        if($returnType == 'obj') {
            $rows = $q->fetchAll(PDO::FETCH_OBJ);
        } else {
            $rows = $q->fetchAll();
        }
        return $rows;
    }


    public function dbBuildInsertForManyRows_($table, array $array) {
        $str = "INSERT INTO $table ";
        $strn = '';
        $strv = array();
        foreach($array as $vals) {
            $strn = "(";
            $_strv = '(';
            if(!is_array($vals)) {
                throw new Exception('Two dimension array is incorrect.');
            }
            while(list($name, $value) = each($vals)) {
                if(is_bool($value)) {
                    $strn .= "$name,";
                    $_strv .= ($value ? "true" : "false").",";
                    continue;
                };
                if(is_string($value)) {
                    $value = mysql_escape_string($value);
                    $strn .= "$name,";
                    $_strv .= "'$value',";
                    continue;
                }
                if(!is_null($value) and ($value !== "")) {
                    $strn .= "$name,";
                    $_strv .= "$value,";
                    continue;
                }
            }
            $_strv[strlen($_strv) - 1] = ')';
            $strn[strlen($strn) - 1] = ')';
            $strv[] = $_strv;
        }
        $str .= $strn." VALUES \r\n".implode(", \r\n", $strv);
        return $str;
    }

    public function insertAll($tableName, array $rows) {
        if(!empty($rows)) {
//            foreach ($rows as $row) {
//                $this->cdb->query('insert into '.$tableName.'')
//            }
            $sql = $this->dbBuildInsertForManyRows_($tableName, $rows);
            $this->cdb->query($sql);
        }
        return $this;
    }

    public function createInsertAll($tableName, array $rows) {
        // @todo if not isset tableName ? create tableName : pas
        $this->insertAll($tableName, $rows);
    }
}

class Mp3Parser
{
    public $data = [];
    public $tableToSave = 'mp3';
    public $id3;

    public function getTracks($dir) {
        $tracks = [];
        $files = scandir($dir);
        unset($files[0]);
        unset($files[1]);
        if (!empty($files)) {
            foreach($files as $file) {
                $fullPath = $dir.DIRECTORY_SEPARATOR.$file;
                if(is_dir($fullPath)) {
                    $tracks[$fullPath] = $this->getTracks($fullPath);
                }
                if(is_file($fullPath)) {
                    $tracks[] = $file;
                }
            }
        }
        return $tracks;
    }

    public function getId3() {
        if(!$this->id3) {
            $this->id3 = new getID3;
        }
        return $this->id3;
    }

    public function parse($fileName) {
        $getID3 = $this->getId3();
        $ThisFileInfo = $getID3->analyze($fileName);
        $track = new stdClass();
        $track->track_name = $ThisFileInfo['tags']['id3v2']['title'][0];
        $track->duration = $ThisFileInfo['playtime_seconds'];
        $track->artist_name = $ThisFileInfo['tags']['id3v2']['artist'][0];
        $track->album_name = $ThisFileInfo['tags']['id3v2']['album'][0];
        $track->album_img = $ThisFileInfo['comments']['picture'][0]['data'];
        $track->file = $fileName;
        return $track;
    }

    public function go($dir) {
        $tracks = $this->getTracks($dir);
        $out = array();
        if(!empty($tracks)) {
            foreach($tracks as $_dir => $tracksInDir) {
                foreach($tracksInDir as $k2 => $trackFile) {
                    $f = $_dir.DIRECTORY_SEPARATOR.$trackFile;
                    $track = $this->parse($f);
                    $out[] = (array)$track;
                }
            }
        }
        if(!empty($out)) {
            $this->data = $out;
            $this->saveToDb();
        }
    }

    public function saveToDb() {
        if ($this->data) {
            $o = new ORM();
            $o->insertAll($this->tableToSave, $this->data);
        }
    }
}

$dir = 'data';
$m = new Mp3Parser();
$m->tableToSave = 'mp3';
$m->go($dir);

