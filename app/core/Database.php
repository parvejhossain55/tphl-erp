<?php 
class Database {

    public $conn;
    private static $instance = null;

    public function __construct($config)
    {
        self::$instance = $this;

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

    public static function getInstance()
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__) . "/config/config.php";
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function query($sql){
        return $this->conn->query($sql);
    }

    public function prepare($sql){
        return $this->conn->prepare($sql);
    }

    public function __call($name, $arguments)
    {
        if (is_object($this->conn) && method_exists($this->conn, $name)) {
            return call_user_func_array([$this->conn, $name], $arguments);
        }
        throw new BadMethodCallException("Method {$name} does not exist on Database or mysqli.");
    }
}
