<?php
/**
 * Created by PhpStorm.
 * User: Florian Moser
 * Date: 03.07.2015
 * Time: 10:01
 */

namespace famoser\phpFrame\Services;

use PDO;

class GenericDatabaseService extends DatabaseService
{
    function GetById($table, $id, $addRelationships = true)
    {
        return $this->GetSingleByCondition($table, array("Id" => $id), $addRelationships);
    }

    function GetAllOrderedBy($table, $orderBy, $addRelationships = true, $additionalSql = null)
    {
        return $this->GetAllByCondition($table, null, $addRelationships, $orderBy, $additionalSql);
    }

    function GetAllByCondition($table, $condition, $addRelationships = true, $orderBy = null, $additionalSql = null)
    {
        if ($orderBy != null)
            $orderBy = " ORDER BY " . $orderBy;

        $model = $this->GetModelByTable($table);

        $db = $this->GetDatabaseConnection();
        $stmt = $db->prepare('SELECT * FROM ' . $table . $this->ConstructConditionSQL($condition) . $orderBy . " " . $additionalSql);
        $stmt->execute($condition);

        $result = $stmt->fetchAll(PDO::FETCH_CLASS, $model);
        if ($addRelationships) {
            foreach ($result as $res) {
                $this->AddRelationsToSingle($res);
            }
        }
        return $result;
    }

    function GetSingleByCondition($table, $condition, $addRelationships = true, $orderBy = null)
    {
        if ($orderBy != null)
            $orderBy = " ORDER BY " . $orderBy;

        $model = $this->GetModelByTable($table);

        $db = $this->GetDatabaseConnection();
        $stmt = $db->prepare('SELECT * FROM ' . $table . $this->ConstructConditionSQL($condition) . $orderBy . " LIMIT 1");
        $stmt->execute($condition);

        $result = $stmt->fetchAll(PDO::FETCH_CLASS, $model);
        if (isset($result[0])) {
            if ($addRelationships)
                $this->AddRelationsToSingle($result[0]);
            return $result[0];
        } else
            return false;
    }

    function GetPropertyByCondition($table, $condition, $property, $orderBy = null)
    {
        $model = $this->GetModelByTable($table);

        if ($orderBy != null)
            $orderBy = " ORDER BY " . $orderBy;
        else
            $orderBy = " ORDER BY " . $property;

        $db = $this->GetDatabaseConnection();
        $stmt = $db->prepare('SELECT ' . $property . ' FROM ' . $table . $this->ConstructConditionSQL($condition) . $orderBy);
        $stmt->execute($condition);

        $result = $stmt->fetchAll(PDO::FETCH_CLASS, $model);
        $resArray = array();
        foreach ($result as $res) {
            $resArray[] = $res->$property;
        }

        return $resArray;
    }

    function AddRelationsToSingle(&$obj)
    {
        $vars = get_object_vars($obj);
        foreach ($vars as $key => $val) {
            if ($val != null && strpos($key, "Id") !== false) {
                if ($val > 0) {
                    $objectName = str_replace("Id", "", $key);
                    if (array_key_exists($objectName, $vars)) {
                        $tableName = strtolower($objectName);
                        $relationObj = $this->GetById($tableName . "s", $val, false);
                        if ($relationObj !== false)
                            $obj->$objectName = $relationObj;
                    }
                }
            }
        }

    }

    function GetHighestId($table)
    {
        $db = $this->GetDatabaseConnection();
        $stmt = $db->prepare("SELECT Id FROM " . $table . " ORDER By Id DESC LIMIT 1");
        $stmt->execute();
        $newId = $stmt->fetchAll();
        if (isset($newId[0]) && isset($newId[0][0]))
            return $newId[0][0];
        else
            return 0;
    }

    function AddOrUpdate($table, $arr)
    {
        $arr = $this->PrepareGenericArray($arr);
        if (!isset($arr["Id"]) || $arr["Id"] == 0) {
            $arr["Id"] = $this->GetHighestId($table) + 1;

            if ($this->Insert($table, $arr)) {
                return $arr["Id"];
            }
        } else {
            $obj = $this->GetById($table, $arr["Id"], false);

            if ($obj == null) {
                if ($this->Insert($table, $arr)) {
                    return $arr["Id"];
                }
            } else {
                return $this->Update($table, $arr);
            }
        }

        return false;
    }

    function Insert($table, $arr)
    {
        $db = $this->GetDatabaseConnection();
        $excludedArray = array();
        $params = $this->CleanUpGenericArray($arr);
        $stmt = $db->prepare('INSERT INTO ' . $table . ' ' . $this->ConstructMiddleSQL("insert", $params, $excludedArray));
        return $stmt->execute($params);
    }

    function Update($table, $arr)
    {
        $db = $this->GetDatabaseConnection();
        $params = $this->CleanUpGenericArray($arr);
        $excludedArray = array();
        $excludedArray[] = "Id";
        $stmt = $db->prepare('UPDATE ' . $table . ' SET ' . $this->ConstructMiddleSQL("update", $params, $excludedArray) . ' WHERE Id = :Id');
        return $stmt->execute($params);
    }

    function DeleteById($table, $id)
    {
        $db = $this->GetDatabaseConnection();
        $stmt = $db->prepare('DELETE FROM ' . $table . ' WHERE Id = :Id');
        $stmt->bindParam(":Id", $id);
        return $stmt->execute();
    }

    function GetModelByTable($table)
    {
        return strtoupper(substr($table, 0, 1)) . substr($table, 1, strlen($table) - 2) . "Model";
    }

    function ConstructConditionSQL($params)
    {
        if ($params == null || !is_array($params) || count($params) == 0)
            return "";

        $sql = " WHERE ";
        foreach ($params as $key => $val) {
            $sql .= $key . " = :" . $key . " AND ";
        }
        $sql = substr($sql, 0, -4);
        return $sql;
    }

    function ConstructMiddleSQL($mode, $params, $excluded)
    {
        $sql = "";
        if ($mode == "update") {
            foreach ($params as $key => $val) {
                if (!in_array($key, $excluded))
                    $sql .= $key . " = :" . $key . ", ";
            }
            $sql = substr($sql, 0, -2);
        } else if ($mode == "insert") {
            $part1 = "(";
            $part2 = "VALUES (";
            foreach ($params as $key => $val) {
                if (!in_array($key, $excluded)) {
                    $part1 .= $key . ", ";
                    $part2 .= ":" . $key . ", ";
                }
            }
            $part1 = substr($part1, 0, -2);
            $part2 = substr($part2, 0, -2);

            $part1 .= ") ";
            $part2 .= ")";
            $sql = $part1 . $part2;
        }
        return $sql;
    }

    function PrepareGenericArray($params)
    {
        if (is_object($params)) {
            $properties = get_object_vars($params);
            $params = array();
            foreach ($properties as $key => $val) {
                if ($val != null && !is_object($val))
                    $params[$key] = $val;
            }
        }
        return $params;
    }

    function CleanUpGenericArray($params, $removeNull = false)
    {
        $params = $this->PrepareGenericArray($params);
        $deleteKeys = array();

        foreach ($params as $key => $val) {
            if (strpos($key, "Checkbox") !== false) {
                $realName = str_replace("CheckboxPlaceholder", "", $key);
                if (!isset($params[$realName]))
                    $params[$realName] = false;
                $deleteKeys[] = $key;
            } else if (strpos($key, "DateTime") !== false)
                $params[$key] = $this->ConvertToDateTime($val);

            else if (strpos($key, "Date") !== false)
                $params[$key] = $this->ConvertToDate($val);

            else if (strpos($key, "Bool") !== false)
                $params[$key] = 1;

            else if (strpos($key, "PasswordHash") !== false)
                $params[$key] = $this->ConvertToPasswordHash($val);

            if ($removeNull && $params[$key] == null)
                $deleteKeys[] = $key;
        }
        foreach ($deleteKeys as $notValidKey) {
            unset($params[$notValidKey]);
        }

        return $params;
    }

    function ConvertToDateTime($input)
    {
        if ($input == null || $input == "")
            return null;
        return date(DATETIME_FORMAT_DATABASE, strtotime($input));
    }

    function ConvertToDate($input)
    {
        if ($input == null || $input == "")
            return null;
        return date(DATE_FORMAT_DATABASE, strtotime($input));
    }

    function ConvertToPasswordHash($password)
    {
        $options = [
            'cost' => 12,
        ];
        $hash = password_hash($password, PASSWORD_BCRYPT, $options);
        return $hash;
    }
}