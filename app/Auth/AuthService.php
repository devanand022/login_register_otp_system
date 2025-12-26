<?php

namespace App\Auth;

use App\Config\Database;
use PDO;
use PDOException;

class Authservice
{
  private PDO $db;

  public function __construct()
  {
    $this->db = Database::connect();
  }

  public function register(array $data): void
  {
    try {
      $stmt = $this->db->prepare(
        "INSERT INTO staff(fullname, emp_code, email, mobile, designation, department, dob, gender, category, password) VALUES (:fullname, :emp_code, :email, :mobile, :designation, :department, :dob, :gender, :category, :password)"
      );

      $stmt->execute([
        ':fullname' => $data['fullname'],
        ':email' => $data['email'],
        ':emp_code' => $data['emp_code'],
        ':mobile' => $data['mobile'],
        ':designation' => $data['designation'],
        ':department' => $data['department'],
        ':dob' => $data['dob'],
        ':gender' => $data['gender'],
        ':category' => $data['category'],
        ':password' => password_hash($data['password'], PASSWORD_DEFAULT)
      ]);
    } catch (PDOException $e) {
      if ($e->errorInfo[1] === 1062) {
        throw new AuthException("This Email or Employee Code is already registered.");
      }

      throw new AuthException("Registration failed. Please try again.");
    }
  }

  public function login(string $email, string $password): void
  {
    $stmt = $this->db->prepare("SELECT id, password, emp_code, fullname FROM staff WHERE email =?");
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (!$user || password_verify($password, $user['password'])) {
      throw new AuthException("Invalid email or password.");
    }

    session_regenerate_id(true);
    $_SESSION['id'] = $user['id'];
    $_SESSION['emp_code'] = $user['emp_code'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['email'] = $email;
  }
}
