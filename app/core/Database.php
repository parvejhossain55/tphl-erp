<?php 
class Database {

    public $conn;

    public function __construct($config)
    {
        $this->conn = new mysqli(
            $config['db_host'],
            $config['db_user'],
            $config['db_pass'],
            $config['db_name']
        );

        if($this->conn->connect_error){
            die("Database connection failed: " . $this->conn->connect_error);
        }

        // Set UTF-8 charset for proper Bengali / Unicode support
        $this->conn->set_charset("utf8mb4");

        // Set MySQL timezone to Bangladesh time (UTC+6)
        $this->conn->query("SET time_zone = '+06:00'");

        // Set PHP default timezone to Asia/Dhaka
        date_default_timezone_set('Asia/Dhaka');
    }

    public function query($sql){
        return $this->conn->query($sql);
    }
}
