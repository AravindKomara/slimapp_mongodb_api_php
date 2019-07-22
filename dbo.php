<?php

include 'config.php';

class Dbo
{

    private $mongodb_path = DB_PATH;
	private $DB_USERNAME = DB_USERNAME;
    private $DB_PASSWORD = DB_PASSWORD;
	private $connection = null;
	
    public function selectINTDB()
    {
        $connect = odbc_connect(INTDB_DSN, INTDB_USERNAME, INTDB_PASSWORD);
        return $connect;
    }
    public function selectDBMongo($db)
    {
        if (!empty($this->connection)) {
            return $this->connection;
        }
        if (!empty($this->DB_USERNAME) && !empty($this->DB_PASSWORD)) {
            $connection = new MongoClient($this->mongodb_path, array("username" => $this->DB_USERNAME, "password" => $this->DB_PASSWORD, 'db' => 'masters'));
        } else {
            $connection = new MongoClient($this->mongodb_path);
        }
        Mongo::setPoolSize(1000);
        $this->connection = $connection->$db;
        return $this->connection;
    }
    public function update($db, $collection, $filter, $set, $push, $options, $updateOptions)
    {
        $dbconn = $this->selectDBMongo($db);
        $collection = $dbconn->{$collection};
        $update = array();
        if (!empty($set) and $set != null) {
            $update['$set'] = $set;

        }
        if (!empty($push)and $push != null) {
            $update['$push'] = $push;

        }
		
		//Newly ADDED
		if (!empty($updateOptions) and $updateOptions != null) {
           foreach($updateOptions as $key=>$value)
		   {
			   $update[$key] = $value;
		   }

        }

        /* echo json_encode(array($filter,
        $update,
        $options));exit;  */

        //PREVIOUS CODE
        //$update_collection = $collection->update(
        //    $filter,
        //    $update,
        //    $options
        //);

        if (!empty($options) and $options != null) {
            $update_collection = $collection->update(
                $filter,
                $update,
                $options
            );
        } else {
            $update_collection = $collection->update(
                $filter,
                $update
            );
        }

        return $update_collection;
    }

    public function insertMany($db, $collection, $data)
    {
        $dbconn = $this->selectDBMongo($db);
        $collection = $dbconn->{$collection};

        $insert_data = $collection->batchInsert(
            $data
        );
        //echo json_encode($insert_data);exit;
    }

    //UNCOMMENT IF CORRECT
    public function find($db, $collection, $filter = [], $project = [], $sort = [], $limit = 9999, $skip = 0)
    {
        $dbconn = $this->selectDBMongo($db);
        $cursor = $dbconn->{$collection}
            ->find($filter, $project)
            ->limit((int) $limit)
            ->skip((int) $skip)
            ->sort($sort);
        /*
        $collection = $dbconn->{$collection};
        if (!empty($sort) and (!empty($limit) or $limit != null) and
        (!empty($skip) or $skip != null)) {
        $cursor = $collection->find($filter, $project)->sort($sort)->limit($limit)
        ->skip($skip);
        } else if (!empty($sort)) {
        $cursor = $collection->find($filter, $project)->sort($sort);
        } else if ((!empty($limit) or $limit != null) and (!empty($skip) or $skip != null)) {
        $cursor = $collection->find($filter, $project)->limit($limit)->skip($skip);
        } else {
        $cursor = $collection->find($filter, $project);
        }
         */
        if ($cursor->count() > 0) {
            foreach ($cursor as $document) {
                $data[] = $document;
            }
        } else {
            $data = "";
        }
        return $data;
    }

    /*

    function find($db,$collection,$filter,$project,$sort)
    {
    $dbconn = $this->selectDBMongo($db);
    $collection = $dbconn->{$collection};
    $cursor = $collection->find($filter,$project);
    if($cursor->count()>0){
    foreach ($cursor as $document) {
    $data[] = $document;
    }
    }else{
    $data = "";
    }
    return $data;
    }
     */
    public function countitem($db, $collectionname, $filter)
    {
        $dbconn = $this->selectDBMongo($db);
        $collection = $dbconn->$collectionname;
        $cursor = $collection->count($filter);
        return $cursor;
    }

    public function findOne($db, $collectionname, $filter, $project)
    {
        $dbconn = $this->selectDBMongo($db);
        $collection = $dbconn->$collectionname;
        $cursor = $collection->findOne($filter, $project);

        //print_R($cursor);
        if ($cursor != null) {
            $data = $cursor;
        } else {
            $data = array();
        }
        return $data;
    }

    public function insert($db, $collection, $data)
    {
        $dbconn = $this->selectDBMongo($db);
        $collection = $dbconn->{$collection};
        $insert_data = $collection->insert($data);
        $insert_data['id'] = $data->{'_id'}->{'$id'};
        return $insert_data;
    }
    public function findAndModify($db, $collection, $filter, $update)
    {
        $dbconn = $this->selectDBMongo($db);
        $coll = $dbconn->{$collection};
        $data = $coll->findAndModify(
            $filter,
            $update,
            null,
            array(
                "new" => true,
            )
        );
        return $data;
    }

    public function aggregate($db, $collection, $pipeline)
    {
        try { $options = array("allowDiskUse" => true, 'cursor' => (array) ["batchSize" => 100000]);
            MongoCursor::$timeout = -1;
            $dbconn = $this->selectDBMongo($db);
            $collection = $dbconn->{$collection};
            $data = $collection->aggregate($pipeline, $options);
            // echo $data->count();

        } catch (Exception $e) {
            print_r($e);exit;
        }
        return $data['cursor']['firstBatch'];
    }

    public function unsetfields($db, $collection, $filter, $unset)
    {
        $dbconn = $this->selectDBMongo($db);
        $collection = $dbconn->{$collection};
        $unset_collection = $collection->update(
            $filter, $unset
        );
        return $unset_collection;
    }
 public function distinct($db, $collection, $fieldName,$filter)
    {
        $dbconn = $this->selectDBMongo($db);
        $collection = $dbconn->{$collection};

        $distinct_data = $collection->distinct($fieldName,$filter);
        //echo json_encode($distinct_data);exit;
        return $distinct_data;
    }
    public function insertINTDBData($tableName, $insertData)
    {
        $intdb = $this->selectINTDB();
        $fieldsArray = array_keys($insertData[0]);
        $fields = implode(',', $fieldsArray);

        $insertQuery = "INSERT INTO " . $tableName . " (" . $fields . ") VALUES";
        for ($i = 0; $i < count($insertData); $i++) {
            $tableValues .= '(';
            $tableArrayValues = array_values($insertData[$i]);
            $tableValues .= implode(',', $tableArrayValues) . '),';
        }
        $tableValues = rtrim($tableValues, ',');
        $insertQuery .= $tableValues;
        //$columnDetails = odbc_columns($connect, "QACHINTDB", '%', $tableName,'%');
        //print_r(odbc_result_all($columnDetails));
        $result = odbc_exec($intdb, $insertQuery);
        $error = "Query failed - " . odbc_errormsg($connect);
        odbc_close($connect);

        if (!$result) { 
            return $error;
        }
        return true;
    }
	
	/**
     * --------------------------------------------------------------------------------
     * //! Delete
     * --------------------------------------------------------------------------------
     *
     * delete document from the passed collection based upon certain criteria
     *
     * @usage : $this->dbo->delete('db','foo',$filter);
     */
    public function delete($db, $collection = "", $filter = array())
    {
        if (empty($db)) {
            die("No Mongo database selected to delete from");
        }
        if (empty($collection)) {
            die("No Mongo collection selected to delete from");
        }
        if (empty($filter)) {
            die("Filter is required can not delete whole collection");
        }
        try
        {
            $dbconn = $this->selectDBMongo($db);
            $dbconn->{$collection}->remove($filter, array('justOne' => true));
        } catch (MongoCursorException $e) {
            print_r($e);exit;
        }
    }

    /**
     * --------------------------------------------------------------------------------
     * Delete all
     * --------------------------------------------------------------------------------
     *
     * Delete all documents from the passed collection based upon certain criteria
     *
     * @usage : $this->dbo->delete_all('db',foo', $filter = array());
     */
    public function delete_all($db, $collection = "", $filter = array())
    {
        if (empty($db)) {
            die("No Mongo database selected to delete from");
        }
        if (empty($collection)) {
            die("No Mongo collection selected to delete from");
        }
        if (empty($filter)) {
            die("Filter is required can not delete whole collection");
        }
        try
        {
            $dbconn = $this->selectDBMongo($db);
            $dbconn->{$collection}->remove($filter, array('justOne' => false));
            return (true);
        } catch (MongoCursorException $e) {
            print_r($e);exit;
        }
    }

}
