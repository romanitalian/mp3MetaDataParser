<?php
require_once "../protected/index.php";

$m = new Mp3Parser();

$m->tableToSave = 'mp3';
$m->go(ROOT.'data');

