<?php


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
                    $_strv .= ($value ? "true" : "false") . ",";
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
        $str .= $strn . " VALUES \r\n" . implode(", \r\n", $strv);
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