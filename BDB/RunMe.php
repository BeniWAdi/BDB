<?php

/**
 * RunMe wajib dijalankan haha XD
 * 
 * @version 0.02
 */
include 'BDB.php';

echo "<br/><hr/>";
echo "<b style='color:red; font-size:50px;'>What to do !</b>";
echo "<p>Copy to DBD/DBReader.php</p>";
echo "<center> BeniWahyuAdi </center>";
echo "<hr/><br/>";
echo "<table border='1'>";
echo "<tr><th>\$_keytables</th><th>\$_desctables</th></tr>";
echo "<tr>"
 . "<td><pre style='font-family:courier new; font-size:12px;'>" . strKeyTables() . "</pre></td>"
 . "<td><pre style='font-family:courier new; font-size:12px;'>" . strDescTables() . "</pre></td>"
 . "</tr>";
echo "</table>";

function strDescTables() {
    $dT = descTables();
    $varDescTables = "<br/>&nbsp;private static \$_desctables = array(<br/>";
    foreach ($dT as $tableName => $value) {
        $varDescTables .= "&nbsp;&nbsp;\"$tableName\" => array( <br/>";
        foreach ($value as $v) {
            $varDescTables.= "&nbsp;&nbsp;&nbsp;array("
                    . "\"Field\" => \"" . $v["Field"] . "\", "
                    . "\"Type\" => \"" . $v["Type"] . "\"), <br/>";
        }
        $varDescTables .= "&nbsp;&nbsp;), <br/>";
    }
    $varDescTables .= "&nbsp;); <br/>";
    return $varDescTables;
}

function strKeyTables() {
    $kT = keyTables();
    $varKeyTables = "<br/>&nbsp;private static \$_keytables = array(<br/>";
    foreach ($kT as $tableName => $keyTable) {
        $varKeyTables .= "&nbsp;&nbsp;\"$tableName\" => array( <br/>"
                . "&nbsp;&nbsp;&nbsp;\"PK\" => " . nd($keyTable["PK"]) . ", <br/>"
                . "&nbsp;&nbsp;&nbsp;\"FK\" => ";
        if ($keyTable["FK"] === null) {
            $varKeyTables .= "null, <br>";
        } else {
            $varKeyTables .= "array( <br/>";
            foreach ($keyTable["FK"] as $fk) {
                $varKeyTables.= "&nbsp;&nbsp;&nbsp;&nbsp;array( <br/>";
                foreach ($fk as $key => $value) {
                    $varKeyTables.= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\"$key\" => \"$value\", <br/>";
                }
                $varKeyTables .= "&nbsp;&nbsp;&nbsp;&nbsp;), <br/>";
            }
            $varKeyTables .= "&nbsp;&nbsp;&nbsp;), <br/>";
        }
        $varKeyTables .= "&nbsp;&nbsp;), <br/>";
    }
    $varKeyTables .= "&nbsp;); <br/>";
    return $varKeyTables;
}

function showTables() {
    $sql = "SHOW TABLES";
    $result = BDB::query($sql, null, null, BDB::$RETURN_MULTI_ROWS_NUM);
    return $result;
}

function descTables() {
    $descTables = null;
    $tables = showTables();
    foreach ($tables as $table) {
        $sql = "DESC " . $table[0];
        $result = BDB::query($sql);
        $temp = null;
        $i = 0;
        foreach ($result as $r) {
            $temp[$i]["Field"] = $r["Field"];
            $temp[$i]["Type"] = $r["Type"];
            $i++;
        }
        $descTables[$table[0]] = $temp;
    }
    return $descTables;
}

function keyTables() {
    $keyTables = null;
    $tables = showTables();
    foreach ($tables as $table) {
        $sql = "SHOW CREATE TABLE " . $table[0];
        $result = BDB::query($sql, null, null, BDB::$RETURN_SINGLE_ROW_NUM);
        $keyTables[$table[0]] = getArrayKeyTables($result[1]);
    }
    return $keyTables;
}

function getArrayKeyTables($strCreate) {
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

function nd($str) {
    if ($str == NULL) {
        return "null";
    }
    return "\"$str\"";
}
