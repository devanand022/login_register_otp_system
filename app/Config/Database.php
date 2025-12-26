<?php

namespace App\Config;

use PDO;
use PDOException;

class Database
{
  public static function connect(): PDO
  {
    try {
      return new PDO(
        "mysql:host=localhost;dbname=staff_details;charset=utf8mb4",
        "root",
        "",
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
      );
    } catch (PDOException) {
      die("Database connection failed");
    }
  }
}
