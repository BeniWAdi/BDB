<?php
/**
 * BDB is ...
 * 
 * @author Beni Wahyu Adi <beniwahyuadi@gmail.com>
 * @version 0.02
 */

require_once (__DIR__."/BConfig.php");
require_once (__DIR__."/DBReader.php");

namespace BDB\BDB;

class BDB extends DBReader {
    // returnOption function query
    static $RETURN_AUTO = 0;
    static $RETURN_SINGLE_ROW_ASSOC = 1;
    static $RETURN_BOOL = 2;
    static $RETURN_MULTI_ROWS_ASSOC = 3;
    static $RETURN_ROW_COUNT = 4;
    static $RETURN_COLUMN_COUNT = 5;
    static $RETURN_SINGLE_ROW_NUM = 6;
    static $RETURN_MULTI_ROWS_NUM = 7;
    static $RETURN_STH = 8;

    static function query($statement, array $bindValues = null, array $bindTypes = null, $returnOption = 0) {
        $sBV = "";
        try {
            $pdo = parent::getConnection();
            $sth = $pdo->prepare($statement);
            if ($bindValues !== null) {
                $sBV = parent::setBindValuesCustom($sth, $bindValues, $bindTypes);
            }
            $sth->execute();
            return self::returnOptions($sth, $returnOption);
        } catch (PDOException $ex) {
            parent::displayError("query", $ex, $sql, $sBV);
            return false;
        }
    }

    static function findByPk($tableName, $primaryKey, $relationLimit = null) {
        $fieldKey = parent::getKeyTable($tableName, "PK");
        $sql = "SELECT * FROM $tableName "
                . "WHERE " . $fieldKey . " = :pk";
        try {
            $pdo = parent::getConnection();
            $sth = $pdo->prepare($sql);
            $sth->bindValue(":pk", $primaryKey, parent::getTableParamType($tableName)[$fieldKey]);
            $sth->execute();
            $result = $sth->fetch(PDO::FETCH_ASSOC);
            if ($sth->rowCount() === 0) {
                return null;
            }
            $relations = self::findRelations($tableName, $result, $relationLimit);
            if ($relations !== null) {
                foreach ($relations as $key => $value) {
                    $result[$key] = $value;
                }
            }
            return $result;
        } catch (PDOException $ex) {
            parent::displayError("findByPk", $ex, $sql, " - \$sth->bindValue(:pk, $primaryKey, " . parent::getTableParamType($tableName)[$fieldKey] . ")");
            return null;
        }
    }

    static function findAll($tableName, $additionalQuery = "", $relationLimit = null) {
        $sql = "SELECT * FROM $tableName $additionalQuery";
        try {
            $pdo = parent::getConnection();
            $sth = $pdo->prepare($sql);
            $sth->execute();
            if ($sth->rowCount() == 0) {
                return null;
            }
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            $temp = null;
            $i = 0;
            foreach ($result as $r) {
                $temp[$i] = $r;
                $relations = self::findRelations($tableName, $r, $relationLimit);
                if ($relations !== null) {
                    foreach ($relations as $key => $value) {
                        $temp[$i][$key] = $value;
                    }
                }
                $i++;
            }
            return $temp;
        } catch (PDOException $ex) {
            parent::displayError("findByPk", $ex, $sql);
            return null;
        }
    }

    static function searchAll($tableName, $fieldsCompare, $keyword, $additionalQuery = "", $relationLimit = null, $operator = "OR") {
        $fieldsCompare = preg_replace('/\s+/', '', $fieldsCompare);
        $fields = explode(",", $fieldsCompare);
        $_operator["andOr"] = $operator;
        $_operator["operator"] = "LIKE";
        $sql = parent::sqlBuilder($tableName, "SEARCH", $fields, null, $_operator) . $additionalQuery;
        try {
            $pdo = parent::getConnection();
            $sth = $pdo->prepare($sql);
            $key = "%" . $keyword . "%";
            $sth->bindValue(":keyword", $key, PDO::PARAM_STR);
            $sth->execute();
            if ($sth->rowCount() == 0) {
                return null;
            }
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            $temp = null;
            $i = 0;
            foreach ($result as $r) {
                $temp[$i] = $r;
                $relations = self::findRelations($tableName, $r, $relationLimit);
                if ($relations !== null) {
                    foreach ($relations as $key => $value) {
                        $temp[$i][$key] = $value;
                    }
                }
                $i++;
            }
            return $temp;
        } catch (PDOException $ex) {
            parent::displayError("searchAll", $ex, $sql, " - \$sth->bindValue(:keyword, $key, PDO::PARAM_STR)");
            return null;
        }
    }

    static function findAllByFields($tableName, array $fields, $additionalQuery = "", $relationLimit = null, $operator = "AND") {
        $_operator["andOr"] = $operator;
        $_operator["operator"] = "=";
        $_fields = array();
        foreach ($fields as $key => $value) {
            $_fields[] = $key;
        }
        $sql = parent::sqlBuilder($tableName, "SEARCH", $_fields, null, $_operator) . $additionalQuery;
        $sBV = "";
        try {
            $pdo = parent::getConnection();
            $sth = $pdo->prepare($sql);
            $sBV = parent::setBindValues($tableName, $sth, $fields);
            $sth->execute();
            if ($sth->rowCount() == 0) {
                return null;
            }
            $result = $sth->fetchAll(PDO::FETCH_ASSOC);
            $temp = null;
            $i = 0;
            foreach ($result as $r) {
                $temp[$i] = $r;
                $relations = self::findRelations($tableName, $r, $relationLimit);
                if ($relations !== null) {
                    foreach ($relations as $key => $value) {
                        $temp[$i][$key] = $value;
                    }
                }
                $i++;
            }
            return $temp;
        } catch (PDOException $ex) {
            parent::displayError("findAllByFields", $ex, $sql, $sBV);
            return null;
        }
    }

    static function insert($tableName, array $fields) {
        $_fields = parent::validationFields($tableName, $fields);
        $sql = parent::sqlBuilder($tableName, "INSERT", $_fields);
        $sBV = "";
        try {
            $pdo = parent::getConnection();
            $sth = $pdo->prepare($sql);
            $sBV = parent::setBindValues($tableName, $sth, $_fields);
            $sth->execute();
            if ($sth->rowCount() > 0)
                return true;
            return false;
        } catch (PDOException $ex) {
            parent::displayError("insert", $ex, $sql, $sBV);
            return false;
        }
    }

    static function update($tableName, array $fields, $pkOrFields, $operator = "AND") {
        $sql = "";
        $fieldKey = parent::getKeyTable($tableName, "PK");
        $_fields = parent::validationFields($tableName, $fields);
        if (is_array($pkOrFields)) {
            $sql = parent::sqlBuilder($tableName, "UPDATE", $pkOrFields, $_fields);
        } else {
            $sql = parent::sqlBuilder($tableName, "UPDATE", null, $_fields);
            $sql .= "$fieldKey = :pk";
        }
        $sBV = "";
        try {
            $pdo = parent::getConnection();
            $sth = $pdo->prepare($sql);
            $sBV = parent::setBindValues($tableName, $sth, $_fields);
            $tableParamValues = parent::getTableParamType($tableName);
            if (is_array($pkOrFields)) {
                foreach ($pkOrFields as $key => $value) {
                    $p = ":k_" . $key;
                    $sth->bindValue($p, $value, $tableParamValues[$key]);
                    $sBV .= " - \$sth->bindValue($p, $value, " . $tableParamValues[$key] . ")<br/>";
                }
            } else {
                $sth->bindValue(":pk", $pkOrFields, $tableParamValues[$fieldKey]);
                $sBV .= " - \$sth->bindValue(:pk, $pkOrFields, " . $tableParamValues[$fieldKey] . ")";
            }
            $sth->execute();
            if ($sth->rowCount() > 0)
                return true;
            return false;
        } catch (PDOException $ex) {
            parent::displayError("update", $ex, $sql, $sBV);
            return false;
        }
    }

    static function delete($tableName, $pkOrFields, $operator = "AND") {
        $sql = "";
        $fieldKey = parent::getKeyTable($tableName, "PK");
        if (is_array($pkOrFields)) {
            $sql = parent::sqlBuilder($tableName, "DELETE", $pkOrFields);
        } else {
            $sql = parent::sqlBuilder($tableName, "DELETE");
            $sql .= "$fieldKey = :pk";
        }
        $sBV = "";
        try {
            $pdo = parent::getConnection();
            $sth = $pdo->prepare($sql);
            $tableParamValues = parent::getTableParamType($tableName);
            if (is_array($pkOrFields)) {
                foreach ($pkOrFields as $key => $value) {
                    $sth->bindValue(":k_" . $key, $value, $tableParamValues[$key]);
                    $sBV .= " - \$sth->bindValue(:k_" . $key . ", $value, " . $tableParamValues[$key] . ")";
                }
            } else {
                $sth->bindValue(":pk", $pkOrFields, $tableParamValues[$fieldKey]);
                $sBV .= " - \$sth->bindValue(:pk, $pkOrFields, " . $tableParamValues[$fieldKey] . ")";
            }
            $sth->execute();
            if ($sth->rowCount() > 0)
                return true;
            return false;
        } catch (PDOException $ex) {
            parent::displayError("delete", $ex, $sql, $sBV);
            return false;
        }
    }

    static function beginTransaction() {
        try {
            $pdo = parent::getConnection();
            return $pdo->beginTransaction();
        } catch (PDOException $ex) {
            parent::displayError("beginTransaction", $ex);
            return false;
        }
    }

    static function commitTransaction() {
        try {
            $pdo = parent::getConnection();
            return $pdo->commit();
        } catch (PDOException $ex) {
            parent::displayError("commitTransaction", $ex);
            return false;
        }
    }

    static function rollBackTransaction() {
        try {
            $pdo = parent::getConnection();
            return $pdo->rollBack();
        } catch (PDOException $ex) {
            parent::displayError("rollBackTransaction", $ex);
            return false;
        }
    }

    static function isInTransaction() {
        try {
            $pdo = parent::getConnection();
            return $pdo->inTransaction();
        } catch (PDOException $ex) {
            parent::displayError("rollBackTransaction", $ex);
            return false;
        }
    }

    private static function findRelations($tableName, $pervResult, $relationLimit) {
        $temp = null;
        if ($relationLimit === null || $relationLimit > 0) {
            $relations = parent::getKeyTable($tableName, "FK");
            if ($relations !== null && $pervResult !== null) {
                foreach ($relations as $r) {
                    $foreignKey = $pervResult[$r["REFERENCED_COLUMN_NAME"]];
                    $referencedTable = $r["REFERENCED_TABLE_NAME"];
                    if ($relationLimit === null)
                        $temp[$referencedTable] = self::findByPk($referencedTable, $foreignKey, null);
                    else
                        $temp[$referencedTable] = self::findByPk($referencedTable, $foreignKey, $relationLimit - 1);
                }
            }
        }
        return $temp;
    }

    private static function returnOptions(PDOStatement $sth, $returnOption) {
        if ($returnOption === 0) {
            if ($sth->columnCount() === 0 && $sth->rowCount() === 0) {
                return false;
            } elseif ($sth->columnCount() === 0 && $sth->rowCount() > 0) {
                return true;
            } elseif ($sth->columnCount() > 0 && $sth->rowCount() > 0) {
                return $sth->fetchAll(PDO::FETCH_ASSOC);
            }
        } elseif ($returnOption === 1) {
            return $sth->fetch(PDO::FETCH_ASSOC);
        } elseif ($returnOption === 2) {
            if ($sth->rowCount() > 0)
                return true;
            return false;
        }elseif ($returnOption === 3) {
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($returnOption === 4) {
            return $sth->rowCount();
        } elseif ($returnOption === 5) {
            return $sth->columnCount();
        } elseif ($returnOption === 6) {
            return $sth->fetch(PDO::FETCH_NUM);
        } elseif ($returnOption === 7) {
            return $sth->fetchAll(PDO::FETCH_NUM);
        } elseif ($returnOption === 8) {
            return $sth;
        }
        return false;
    }

}
