<?php
namespace Database;

class DB {
    public static $conn;

    public static function init() {
        $host = "localhost";
        $user = "root";
        $db = "staff_details";
        $pass = "";

        self::$conn = new \mysqli($host, $user, $pass, $db);
        if (self::$conn->connect_error) {
            die("Connection failed: " . self::$conn->connect_error);
        }
    }
}
