<?php

namespace App\Repository;

use App\Config\Database;
use PDO;

class MasterDataRepository
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  public function getDesignations(): array
  {
    $stmt = $this->db->prepare("SELECT * FROM designation");
    $stmt->execute();
    return $stmt->fetchAll();
  }

  public function getDepartments(): array
  {
    $stmt = $this->db->prepare("SELECT * FROM department");
    $stmt->execute();
    return $stmt->fetchAll();
  }
}
