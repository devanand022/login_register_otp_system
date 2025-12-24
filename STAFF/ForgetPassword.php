<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use Database\DB;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

DB::init();
$conn = DB::$conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';

  if ($email) {

    header('Content-Type: application/json');

    try {
      $stmt = $conn->prepare("SELECT id, email FROM staff WHERE email = ? LIMIT 1");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows !== 1) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Email not found!'
        ]);
        exit;
      }

      $user = $result->fetch_assoc();
      $_SESSION['user_id']   = $user['id'];
      $_SESSION['email']   = $user['email'];
      $stmt->close();

      $otp = rand(100000, 999999);
      $now = time();
      $expires = $now + 300;

      $conn->query("DELETE FROM staff_otps WHERE staff_id={$user['id']}");

      $otpstmt = $conn->prepare("INSERT INTO staff_otps (staff_id, otp, created_at, expries_at) VALUES (?, ?, ?, ?)");

      $otpstmt->bind_param("isii", $user['id'], $otp, $now, $expires);
      $otpstmt->execute();

      $mail = new PHPMailer(true);
      try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ananddeva345@gmail.com';
        $mail->Password = 'tczs ktwi sxrn umeh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('ananddeva345@gmail.com', 'Staff Forget Password');
        $mail->addAddress($user['email']);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "<p>Your OTP is: </p><h2>{$otp}</h2><p>Valid for 5 minutes.</p>";

        $mail->send();
        $otpstmt->close();
      } catch (Exception $e) {
        echo json_encode([
          'status' => 'error',
          'message' => 'Unable to send OTP. Please try again later.'
        ]);
        exit;
      }

      echo json_encode([
        'status' => 'success',
        'message' => 'OTP Sent to your Registerd Email.',
        'redirect' => './VerifyOtp.php?Page=changepassword'
      ]);
      exit;
    } catch (Exception $e) {
      echo json_encode([
        'status' => 'error',
        'message' => 'Something Went Wrong',
      ]);
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>National College(Autonomous), Trichy - 620 001.</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Google Web Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="../SUPPORTFILES/CSS/bootstrap.min.css" rel="stylesheet">

  <!-- Custom Styles -->
  <link href="../SUPPORTFILES/CSS/style.css" rel="stylesheet">
</head>

<body class="bg-light">

  <div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="row w-100 justify-content-center">
      <div class="card shadow-sm p-4" style="max-width: 400px; width: 100%;">

        <div class="text-center mb-4">
          <h4 class="fw-bold text-primary">Forgot Password</h4>
          <p class="text-muted small">Enter your registered email to reset your password</p>
        </div>

        <form id="forgotForm" method="POST" autocomplete="off" novalidate>

          <!-- Email Input -->
          <div class="mb-3">
            <label for="email" class="form-label text-primary">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
            <div class="invalid-feedback" id="email_error"></div>
          </div>

          <!-- Submit Button -->
          <div class="d-grid mb-3">
            <button type="submit" id="submitBtn" class="btn btn-primary text-white">Send OTP</button>
          </div>

          <!-- Back to Login -->
          <div class="text-center">
            <a href="index.php" class="text-decoration-none">Back to Login</a>
          </div>

          <!-- General message -->
          <div id="general_message" class="mt-3"></div>

        </form>
      </div>

      <p class="text-center text-muted small mt-3">
        © National College (Autonomous), Trichy
      </p>
    </div>
  </div>

  <script src="../SUPPORTFILES/JS/jquery-3.4.1.min.js"></script>
  <script src="../SUPPORTFILES/JS/bootstrap.bundle.min.js"></script>
  <script src="../SUPPORTFILES/JS/main.js"></script>

  <!-- Optional AJAX handling for sending email -->
  <script>
    const form = document.getElementById('forgotForm');
    const generalMessage = document.getElementById('general_message');
    const emailInput = document.getElementById('email');
    const email_error = document.getElementById('email_error');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function(e) {
      e.preventDefault();

      generalMessage.innerHTML = '';
      email_error.textContent = '';
      emailInput.classList.remove('is-invalid');

      const emailValue = emailInput.value.trim();

      if (!emailValue) {
        email_error.textContent = 'Email is required';
        emailInput.classList.add('is-invalid');
        return;
      }

      if (!emailPattern.test(emailValue)) {
        email_error.textContent = 'Please enter a valid email address';
        emailInput.classList.add('is-invalid');
        return;
      }

      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Sending...';

      const formData = new FormData(form);

      fetch('', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            generalMessage.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            form.reset();
            if (data.redirect) {
              setTimeout(() => {
                window.location.href = data.redirect;
              }, 800);
            }
          } else {
            generalMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
          }
        })
        .catch(() => {
          generalMessage.innerHTML =
            `<div class="alert alert-danger">Something went wrong. Try again.</div>`;
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Send OTP';
        });
    });
  </script>

</body>

</html>
