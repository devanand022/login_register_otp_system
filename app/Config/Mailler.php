<?php

namespace App\Config;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailler
{
  private PHPMailer $mail;

  public function __construct()
  {
    $this->mail = new PHPMailer(true);

    try {
      $this->mail->isSMTP();
      $this->mail->Host = 'smtp.example.com';
      $this->mail->SMTPAuth = true;
      $this->mail->Username = 'ananddeva345@gmail.com';
      $this->mail->Password = 'tczs ktwi sxrn umeh';
      $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $this->mail->Port = 587;

      $this->mail->setFrom('no-reply@gmail.com', 'Staff Management');
    } catch (Exception $e) {
      die("Mailer Error: " . $e->getMessage());
    }
  }

  public function send(string $to, string $subject, string $body) : void {
    try{
      $this->mail->clearAddresses();
      $this->mail->addAddress($to);
      $this->mail->isHTML(true);
      $this->mail->Subject = $subject;
      $this->mail->Body = $body;
    }catch(Exception $e){
      throw new \Exception("Email could not be sent: " . $e->getMessage());
    }
  }
}
