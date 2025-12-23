<?php
  $host = "localhost";
  $user = "root";
  $db = "staff_details";
  $pass = "";

  $conn = new mysqli($host, $user, $pass, $db);
  if($conn->connect_error){
    die("Connection falied" . $conn->connect_error);
  }

  session_start();
