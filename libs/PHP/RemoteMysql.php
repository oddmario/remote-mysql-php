<?php
/**
 * Class RemoteMysqlInvalidConnection
 * Extends Exception
 */
class RemoteMysqlInvalidConnection extends Exception {}
/**
 * Class RemoteMysqlUninitialized
 * Extends Exception
 */
class RemoteMysqlUninitialized extends Exception {}
/**
 * Class RemoteMysqlSqlError
 * Extends Exception
 */
class RemoteMysqlSqlError extends Exception {}
/**
 * Class RemoteMysqlPreparedStatementError
 * Extends Exception
 */
class RemoteMysqlPreparedStatementError extends Exception {}

/**
 * Class RemoteMysql
 */
class RemoteMysql {
    private $handlerURI = '';
    private $proxy = '';

    function __construct($handler_uri, $proxy = '', $validate_connection = false) {
        $this->handlerURI = $handler_uri;
        $this->proxy = $proxy;

        if( $validate_connection ) {
            if( !$this->validateConnection() ) {
                throw new RemoteMysqlInvalidConnection("Failed to connect to the database.");
            }
        }
    }

    /**
     * Execute a SQL query
     * 
     * @param string $sql
     * @param boolean $return_rows
     * @param array $b64_fields
     * @return RemoteMysqlResult
     */
    public function query($sql, $return_rows = true, $b64_fields = array()) {
        $reqData = array(
            "sql" => $sql,
        );

        if(!$return_rows) {
            $reqData['no_rows'] = true;
        }

        if(!empty($b64_fields)) {
            $reqData['b64_fields'] = $b64_fields;
        }

        $req = $this->request($reqData);

        if( $req['error'] ) {
            throw new RemoteMysqlSqlError($req['err']);
        }

        $result = new RemoteMysqlResult();
        $result->num_rows = $req['num_rows'];
        $result->insert_id = $req['insert_id'];
        $result->rows = $req['rows'];

        return $result;
    }

    /**
     * Initialize a prepared statement instance
     * 
     * @param string $sql
     * @param boolean $return_rows
     * @param array $b64_fields
     * @return RemoteMysqlPreparedStatement
     */
    public function prepare($sql, $return_rows = true, $b64_fields = array()) {
        return new RemoteMysqlPreparedStatement($this, $sql, $return_rows, $b64_fields);
    }

    /**
     * Used privately by the class to validate the connection
     * 
     * @return boolean
     */
    private function validateConnection() {
        if( empty($this->handlerURI) ) {
            throw new RemoteMysqlUninitialized("RemoteMysql has not been initialized.");
        }

        $req = $this->request(array(
            "sql" => "SELECT VERSION();",
        ));

        if( $req['error'] ) {
            return false;
        }

        return true;
    }

    /**
     * Used by the class to execute the requests of it
     * This was made public so any RemoteMysqlPreparedStatement instances can access it
     * 
     * @param array $data
     * @return array
     */
    public function request($data = array()) {
        $ch = curl_init($this->handlerURI);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        if( !empty($this->proxy) ) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
        }
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    /**
     * Close the remote database connection
     * 
     * @return boolean
     */
    public function close() {
        $this->handlerURI = NULL;
        $this->proxy = NULL;

        return true;
    }
}

/**
 * Class RemoteMysqlResult
 */
class RemoteMysqlResult {
    public $num_rows = 0;
    public $insert_id = 0;
    public $rows = array();

    /**
     * Fetch all the rows of the result (if there's any)
     * 
     * @return array
     */
    public function fetch_all() {
        return $this->rows;
    }
}

/**
 * Class RemoteMysqlPreparedStatement
 */
class RemoteMysqlPreparedStatement {
    private $parent;
    private $bindLetters = '';
    private $bindValues = array();
    private $sql = '';
    private $return_rows;
    private $b64_fields = array();
    private $is_executed = false;
    private $result;

    function __construct($parent, $sql, $return_rows = true, $b64_fields = array()) {
        $this->parent = $parent;
        $this->sql = $sql;
        $this->return_rows = $return_rows;
        $this->b64_fields = $b64_fields;
    }

    /**
     * Safely bind parameters to the prepared statement
     * 
     * @param string $letters
     * @param array $values
     * @return boolean
     */
    public function bind_param($letters, ...$values) {
        if( empty($letters) || empty($values) ) {
            throw new RemoteMysqlPreparedStatementError("Can't bind nothing to the prepared statement.");
        }

        $this->bindLetters = $letters;
        $this->bindValues = $values;

        return true;
    }

    /**
     * Execute the prepared statement
     * 
     * @return boolean
     */
    public function execute() {
        $reqData = array(
            "sql" => $this->sql,
        );

        if( !empty($this->bindLetters) ) {
            $reqData['bind_letters'] = $this->bindLetters;
            $reqData['bind_values'] = $this->bindValues;
        }

        if(!$this->return_rows) {
            $reqData['no_rows'] = true;
        }

        if(!empty($this->b64_fields)) {
            $reqData['b64_fields'] = $this->b64_fields;
        }

        $req = $this->parent->request($reqData);

        if( $req['error'] ) {
            throw new RemoteMysqlSqlError($req['err']);
        }

        $this->is_executed = true;

        $result = new RemoteMysqlResult();
        $result->num_rows = $req['num_rows'];
        $result->insert_id = $req['insert_id'];
        $result->rows = $req['rows'];

        $this->result = $result;

        return true;
    }

    /**
     * If executed, return the prepared statement result
     * 
     * @return RemoteMysqlResult
     */
    public function get_result() {
        if( !$this->is_executed ) {
            throw new RemoteMysqlPreparedStatementError("This prepared statement hasn't been executed.");
        }

        return $this->result;
    }

    /**
     * Close the prepared statement instance
     * 
     * @return boolean
     */
    public function close() {
        $this->parent = NULL;
        $this->bindLetters = NULL;
        $this->bindValues = NULL;
        $this->sql = NULL;
        $this->return_rows = NULL;
        $this->b64_fields = NULL;

        return true;
    }
}