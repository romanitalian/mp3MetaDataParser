<?php


class Mp3Parser
{
    public $data = [];
    public $tableToSave = 'mp3';
    public $id3;

    public function getTracks($dir) {
        $tracks = [];
        if(file_exists($dir)) {
            $files = scandir($dir);
            unset($files[0]);
            unset($files[1]);
            if(!empty($files)) {
                foreach($files as $file) {
                    $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
                    if(is_dir($fullPath)) {
                        $tracks[$fullPath] = $this->getTracks($fullPath);
                    }
                    if(is_file($fullPath)) {
                        $tracks[] = $file;
                    }
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
                    $f = $_dir . DIRECTORY_SEPARATOR . $trackFile;
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
        if($this->data) {
            $o = new ORM();
            $o->insertAll($this->tableToSave, $this->data);
        }
    }
}