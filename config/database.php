<?php
/**
 * CreatorAI - Database Connection (Cloudflare D1)
 * HTTP REST API wrapper acting like PDO
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    
    private function __construct() {
        if (empty(CF_ACCOUNT_ID) || empty(CF_DB_ID) || empty(CF_API_TOKEN)) {
            error_log("Database connection failed: Cloudflare D1 credentials missing.");
            if (APP_DEBUG) {
                die("Database connection failed. Please check your CF configuration.");
            }
            die("A database error occurred. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function executeD1Query($sql, $params = []) {
        $url = "https://api.cloudflare.com/client/v4/accounts/" . CF_ACCOUNT_ID . "/d1/database/" . CF_DB_ID . "/query";
        
        $payload = json_encode([
            "sql" => $sql,
            "params" => $params
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . CF_API_TOKEN,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local WAMP dev
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("D1 Request Error: $error");
        }
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if ($httpCode !== 200 || empty($data['success'])) {
            $err = $data['errors'][0]['message'] ?? 'Unknown D1 Error';
            error_log("D1 Query error: $err | SQL: $sql");
            throw new Exception("D1 Query Error: $err");
        }
        
        // Return the first query result
        return $data['result'][0] ?? null;
    }
    
    public function query($sql, $params = []) {
        // Return a mock PDOStatement for backwards compatibility
        $res = $this->executeD1Query($sql, $params);
        return new D1StatementMock($res);
    }
    
    public function fetchAll($sql, $params = []) {
        $res = $this->executeD1Query($sql, $params);
        return $res['results'] ?? [];
    }
    
    public function fetch($sql, $params = []) {
        $res = $this->executeD1Query($sql, $params);
        return $res['results'][0] ?? null;
    }
    
    public function fetchColumn($sql, $params = []) {
        $row = $this->fetch($sql, $params);
        if ($row) {
            return array_values($row)[0];
        }
        return false;
    }
    
    public function insert($sql, $params = []) {
        $res = $this->executeD1Query($sql, $params);
        return $res['meta']['last_row_id'] ?? 0;
    }
    
    public function update($sql, $params = []) {
        $res = $this->executeD1Query($sql, $params);
        return $res['meta']['changes'] ?? 0;
    }
    
    public function delete($sql, $params = []) {
        $res = $this->executeD1Query($sql, $params);
        return $res['meta']['changes'] ?? 0;
    }
    
    public function beginTransaction() {
        return true; // D1 doesn't support interactive transactions over HTTP
    }
    
    public function commit() {
        return true;
    }
    
    public function rollback() {
        return true;
    }
    
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}

class D1StatementMock {
    private $results;
    private $meta;
    
    public function __construct($apiResult) {
        $this->results = $apiResult['results'] ?? [];
        $this->meta = $apiResult['meta'] ?? [];
    }
    
    public function fetchAll() {
        return $this->results;
    }
    
    public function fetch() {
        return $this->results[0] ?? null;
    }
    
    public function fetchColumn() {
        $row = $this->fetch();
        return $row ? array_values($row)[0] : false;
    }
    
    public function rowCount() {
        return $this->meta['changes'] ?? 0;
    }
}

function db() {
    return Database::getInstance();
}
