<?php

/**
 * BDB is ...
 * 
 * @author Beni Wahyu Adi <beniwahyuadi@gmail.com>
 * @version 0.02
 */
class DBReader extends BConfig {

    private static $_pdo = null;
    private static $_desctables = null;
    private static $_keytables = null;

    // ===================== Copy Here ================================== //
    
    // ===================== end  ================================ //
    
    // ===================== UNTUK MEMBUAT SQL ================================= //

    protected static function getConnection() {
        if (self::$_pdo !== null) {
            return self::$_pdo;
        }
        $dsn = "mysql:host=" . parent::$servername . ";dbname=" . parent::$dbname . ";charset=" . parent::$charset;
        try {
            $koneksi = new PDO($dsn, parent::$username, parent::$password);
            $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$_pdo = $koneksi;
            return $koneksi;
        } catch (PDOException $ex) {
            self::displayError("Connection Failed", $ex);
            return false;
        }
    }

    protected static function sqlInnerJoinBuilder($tableName) {
        $rel = self::getKeyTable($tableName, "FK");
        $temp = "";
        if ($rel !== null) {
            foreach ($rel as $r) {
                $rT = $r["REFERENCED_TABLE_NAME"];
                $temp .= " INNER JOIN " . $rT . " ON $tableName." . $r["COLUMN_NAME"] . " = $rT." . $r["REFERENCED_COLUMN_NAME"];
                $temp .= " " . self::sqlInnerJoinBuilder($rT);
            }
        }
        return $temp;
    }

    protected static function sqlConditionsBuilder($tableName, array $fieldsCompare, $operator, $operatorAndOr) {
        $sqlCondition = " WHERE ";
        $_operator = "";
        if ($operator == "LIKE") {
            $_operator = " LIKE :keyword ";
        } elseif ($operator == "=") {
            $_operator = " = ";
        }
        if ($fieldsCompare !== null) {
            $i = 1;
            $iL = count($fieldsCompare);
            foreach ($fieldsCompare as $f) {
                $arr = explode(".", $f);
                $length = count($arr);
                $param = $tableName . "__" . $f;
                if ($length > 1) {
                    $sqlCondition .= $arr[$length - 2] . "." . $arr[$length - 1];
                    $param = $arr[$length - 2] . "__" . $arr[$length - 1];
                } else {
                    $sqlCondition .= $tableName . "." . $f;
                }
                if ($operator == "LIKE") {
                    $sqlCondition .= $_operator;
                } elseif ($operator == "=") {
                    $sqlCondition .= $_operator . ":$param ";
                }
                if ($i < $iL)
                    $sqlCondition .= " " . $operatorAndOr . " ";
                $i++;
            }
        }
        return $sqlCondition;
    }

    protected static function sqlBuilder($tableName, $command, array $fields = null, array $inputs = null, array $operators = null) {
        $temp = "";
        if ($command == "SELECT" || $command == "select" || $command == "Select") {
            $temp .= "SELECT ";
            $fields = self::descTable($tableName);
            $i = 1;
            $iL = count($fields);
            foreach ($fields as $f) {
                $temp .= $tableName . "." . $f["Field"] . " AS '" . $f["Field"] . "'";
                if ($i < $iL)
                    $temp .= ", ";
                $i++;
            }
            $temp .= " FROM $tableName " . self::sqlInnerJoinBuilder($tableName);
        }
        elseif ($command == "SEARCH" || $command == "search") {
            $temp = self::sqlBuilder($tableName, "SELECT") . "" . self::sqlConditionsBuilder($tableName, $fields, $operators["operator"], $operators["andOr"]);
        } elseif ($command == "INSERT" || $command == "insert") {
            if ($fields != null) {
                $tFields = "";
                $tValues = "";
                $index = 0;
                foreach ($fields as $key => $value) {
                    $tFields .= $key;
                    $tValues .= ":" . $tableName . "__" . $key;
                    if ($index < count($fields) - 1) {
                        $tFields .= ", ";
                        $tValues .= ", ";
                    }
                    $index++;
                }
                $temp = "INSERT INTO $tableName ( $tFields ) VALUES ( $tValues )";
            }
        } elseif ($command == "UPDATE" || $command == "update") {
            if ($inputs !== null) {
                $sets = "";
                $index = 0;
                foreach ($inputs as $key => $value) {
                    $sets .= $key . " = :" . $tableName . "__" . $key;
                    if ($index < count($inputs) - 1) {
                        $sets .= ", ";
                    }
                    $index++;
                }
                $temp = "UPDATE $tableName SET $sets WHERE ";
                if ($fields !== null) {
                    $kons = "";
                    $index = 0;
                    foreach ($fields as $key => $value) {
                        $kons .= $key . " = :k_" . $key;
                        if ($index < count($fields) - 1) {
                            $kons .= " AND ";
                        }
                        $index++;
                    }
                    $temp .= $kons;
                }
            }
        } elseif ($command == "DELETE" || $command == "delete") {
            $temp = "DELETE FROM $tableName WHERE ";
            if ($fields != null) {
                $kons = "";
                $index = 0;
                foreach ($fields as $key => $value) {
                    $kons .= $key . " = :k_" . $key;
                    if ($index < count($fields) - 1) {
                        $kons .= " AND ";
                    }
                    $index++;
                }
                $temp .= $kons;
            }
        }
        return $temp;
    }

    // ==================== UNTUK MENCARI INFO TABLE DATABASE ================== //

    protected static function validationFields($tableName, array $fields) {
        $table = self::descTable($tableName);
        $temp = null;
        foreach ($fields as $key => $value) {
            foreach ($table as $t) {
                if ($key === $t['Field']) {
                    $temp[$key] = $value;
                }
            }
        }
        return $temp;
    }

    protected static function getTableParamType($tableName) {
        $table = self::descTable($tableName);
        $numDataTypes = array("tinyint", "smallint", "mediumint", "int", "bigint", "float", "double", "real", "decimal", "numeric");
        $temp = null;
        foreach ($table as $t) {
            $type = $t["Type"];
            $iChar = strpos($type, "(");
            if ($iChar != null) {
                $type = substr($type, 0, $iChar);
            }
            $temp[$t["Field"]] = PDO::PARAM_STR;
            foreach ($numDataTypes as $n) {
                if ($type === $n) {
                    $temp[$t["Field"]] = PDO::PARAM_INT;
                }
            }
        }
        return $temp;
    }

    protected static function descTable($tableName) {
        if (self::$_desctables !== null && isset(self::$_desctables[$tableName])) {
            return self::$_desctables[$tableName];
        }
        $sql = "DESC $tableName";
        try {
            $pdo = self::getConnection();
            $sth = $pdo->prepare($sql);
            $sth->execute();
            self::$_desctables[$tableName] = $sth->fetchAll(PDO::FETCH_ASSOC);
            return self::$_desctables[$tableName];
        } catch (Exception $ex) {
            self::displayError("descTable", $ex);
            return null;
        }
    }

    protected static function getKeyTable($tableName, $option) {
        if (self::$_keytables !== null && isset(self::$_keytables[$tableName])) {
            return self::$_keytables[$tableName][$option];
        }
        $sql = "SHOW CREATE TABLE $tableName";
        try {
            $pdo = self::getConnection();
            $sth = $pdo->prepare($sql);
            $sth->execute();
            $result = $sth->fetch(PDO::FETCH_NUM);
            $temp = self::getArrayKeyTables($result[1]);
            self::$_keytables[$tableName] = $temp;
            return $temp[$option];
        } catch (PDOException $ex) {
            self::displayError("getKeyTable", $ex);
            return null;
        }
    }

    // ====================== UNTUK MENGESET BIND VALUES ======================= //

    protected static function setBindValuesCustom(PDOStatement $sth, array $bindValues, array $bindTypes = null) {
        $string = "";
        foreach ($bindValues as $key => $value) {
            $type = PDO::PARAM_STR;
            if ($bindTypes !== null) {
                foreach ($bindTypes as $kT => $vT) {
                    if ($kT == $key) {
                        if ($vT == "null" || $vT == "NULL" || $vT == 0)
                            $type = PDO::PARAM_NULL;
                        elseif ($vT == "int" || $vT == "INT" || $vT == 1)
                            $type = PDO::PARAM_INT;
                        elseif ($vT == "null" || $vT == "NULL" || $vT == 0)
                            $type = PDO::PARAM_NULL;
                        elseif ($vT == "lob" || $vT == "LOB" || $vT == 3)
                            $type = PDO::PARAM_LOB;
                        elseif ($vT == "stmt" || $vT == "STMT" || $vT == 4)
                            $type = PDO::PARAM_STMT;
                        elseif ($vT == "bool" || $vT == "BOOL" || $vT == 5)
                            $type = PDO::PARAM_BOOL;
                        elseif ($vT == "input_output" || $vT == "INPUT_OUTPUT" || $vT == PDO::PARAM_INPUT_OUTPUT)
                            $type = PDO::PARAM_INPUT_OUTPUT;
                        else
                            $type = PDO::PARAM_STR;
                    }
                }
            }
            $sth->bindParam($key, $value, $type);
            $string .= " - \$sth->bindParam($key, $value, $type) <br/>";
        }
        return $string;
    }

    protected static function setBindValues($tableName, PDOStatement $sth, array $fields) {
        $string = "";
        $tableParamType = self::getTableParamType($tableName);
        foreach ($fields as $key => $value) {
            $arr = explode(".", $key);
            $iL = count($arr);
            if ($iL > 1) {
                $rT = $arr[$iL - 2];
                $_fields[$arr[$iL - 1]] = $value;
                self::setBindValues($rT, $sth, $_fields);
            } else {
                $sth->bindValue(":" . $tableName . "__" . $key, $value, $tableParamType[$key]);
                $string .= "- \$sth->bindValue(:" . $tableName . "__" . $key . ", $value, " . $tableParamType[$key] . ") <br/>";
            }
        }
        return $string;
    }

    // ====================== TAMPILAN ERROR & FUNCTION TAMBAHAN =============== //

    protected static function displayError($fName, PDOException $ex, $sql = "", $sBV = "") {
        if (parent::$showError) {
            echo "<br/><hr/>";
            echo "<b style='color:red; font-size:25px;'>Error : $fName</b>";
            echo "<p>" . $ex->getMessage() . "</p>";
            echo "<b>Trace :</b><br/>";
            $trace = str_replace("#", "<br/>#", $ex->getTraceAsString());
            $trace = substr($trace, strpos($trace, "#1"), strlen($trace));
            echo str_replace(".php", ".php -> Line ", $trace);
            if (parent::$showSqlWhenError) {
                echo "<br><br/><b>Query : </b><br/>";
                echo "<p style='font-family:courier new; font-size:12px;'>$sql</p>";
                echo "<b>Params :</b>";
                echo "<p style='font-family:courier new; font-size:12px;'>$sBV</p>";
            }
            if (parent::$killAppWhenError)
                die();
            echo "<center> BeniWahyuAdi </center>";
            echo "<hr/><br/>";
        }
    }

    private static function getArrayKeyTables($strCreate) {
        $_temp["PK"] = null;
        $_temp["FK"] = null;
        $aPK = explode("PRIMARY KEY (`", $strCreate);
        $aFK = explode("FOREIGN KEY (`", $strCreate);
        if (isset($aPK[1])) {
            $_temp["PK"] = explode("`)", $aPK[1])[0];
        }
        if (isset($aFK[1])) {
            for ($i = 1; $i < count($aFK); $i++) {
                $_tempR = explode("`", $aFK[$i]);
                $_temp["FK"][$i - 1]["COLUMN_NAME"] = $_tempR[0];
                $_temp["FK"][$i - 1]["REFERENCED_TABLE_NAME"] = $_tempR[2];
                $_temp["FK"][$i - 1]["REFERENCED_COLUMN_NAME"] = $_tempR[4];
            }
        }
        return $_temp;
    }

}
