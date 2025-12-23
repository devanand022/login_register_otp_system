<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include '../DATABASE/db.php';
require_once '../INCLUDE/PHPMailer/src/Exception.php';
require_once '../INCLUDE/PHPMailer/src/PHPMailer.php';
require_once '../INCLUDE/PHPMailer/src/SMTP.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ./index.php");
  exit;
}

$page = $_GET['Page'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $response = [];

  header('Content-Type: application/json');

  if (isset($_POST['verify_otp'])) {

    $otp = $_POST['otp'];

    if (!$otp) {
      $response['status'] = 'error';
      $response['message'] = 'OTP is required';
      echo json_encode($response);
      exit;
    }
    $user_id = $_SESSION['user_id'];
    $now = time();

    $stmt = $conn->prepare("SELECT * FROM staff_otps WHERE staff_id=? AND otp=? AND expries_at >=?");
    $stmt->bind_param("isi", $user_id, $otp, $now);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $stmt = $conn->prepare("DELETE FROM staff_otps WHERE staff_id=?");
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $_SESSION['logged_in'] = true;
      $response['status'] = 'success';
      if ($page === 'login') {
        $response['redirect'] = '../DASHBOARD/index.php';
      } elseif ($page === 'changepassword') {
        $response['redirect'] = './ChangePassword.php';
      } else {
        $response['redirect'] = './index.php';
      }
    } else {
      $response['status'] = 'error';
      $response['message'] = 'Invalid or expired OTP';
    }

    echo json_encode($response);
    exit;
  }

  if (isset($_POST['resend_otp'])) {
    $otp = rand(100000, 999999);
    $now = time();
    $user_id = $_SESSION['user_id'];
    $expries = $now + 300;

    $stmt = $conn->prepare("DELETE FROM staff_otps WHERE staff_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO staff_otps(staff_id, otp, created_at, expries_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isii", $user_id, $otp, $now, $expries);
    $stmt->execute();

    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = 'ananddeva345@gmail.com';
      $mail->Password = 'tczs ktwi sxrn umeh';
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = 587;

      $mail->setFrom('no-reply@gmail.com', 'Staff Login OTP');
      $mail->addAddress($_SESSION['email']);

      $mail->isHTML(true);
      $mail->Subject = 'Your OTP Code';
      $mail->Body = "<p>Your OTP is: </p><h2>{$otp}</h2><p>Valid for 5 minutes.</p>";

      $mail->send();
      $response['status'] = 'success';
      $response['message'] = 'OTP resent successfully';
    } catch (Exception $e) {
      $response['status'] = 'error';
      $response['message'] = 'Unable to resend OTP. Please try again later.';
    }
    echo json_encode($response);
    exit;
  }
  $conn->close();
  exit;
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
  <style>
    .letter-spacing {
      letter-spacing: 0.4rem;
    }
  </style>
</head>

<body class="bg-light">

  <div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100 justify-content-center">
      <div class="col-sm-10 col-md-6 col-lg-4">

        <div class="card shadow border-0 rounded-4">
          <div class="card-body p-4">

            <!-- Header -->
            <div class="text-center mb-4">
              <h4 class="mt-2 fw-semibold">OTP Verification</h4>
              <p class="text-muted small mb-0">
                Enter the 6-digit OTP sent to your registered email
              </p>
            </div>

            <!-- Alert -->
            <div id="alertBox"></div>

            <!-- OTP Form -->
            <form id="otpForm" autocomplete="off" novalidate>

              <!-- OTP Input -->
              <div class="mb-3">
                <input
                  type="text"
                  name="otp"
                  id="otp_input"
                  class="form-control text-center fs-4 letter-spacing"
                  maxlength="6"
                  placeholder="••••••"
                  required>
                <div class="invalid-feedback" id="otp_error"></div>
              </div>

              <!-- Verify Button -->
              <div class="d-grid mb-2">
                <button type="submit" id="submitBtn" class="btn btn-primary fw-semibold text-white">
                  Verify OTP
                </button>
              </div>

              <!-- Resend -->
              <div class="text-center">
                <button type="button" id="resendBtn" class="btn btn-link btn-sm">
                  Didn’t receive OTP? Resend
                </button>
              </div>

            </form>

          </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-muted small mt-3">
          © National College (Autonomous), Trichy
        </p>

      </div>
    </div>
  </div>
  <script src="../SUPPORTFILES/JS/jquery-3.4.1.min.js"></script>
  <script src="../SUPPORTFILES/JS/bootstrap.bundle.min.js"></script>
  <script src="../SUPPORTFILES/JS/main.js"></script>
  <script>
    const otpForm = document.getElementById('otpForm');
    const alertBox = document.getElementById('alertBox');
    const resendBtn = document.getElementById('resendBtn');
    const submitBtn = document.getElementById('submitBtn');

    otpForm.addEventListener('submit', function(e) {
      e.preventDefault();

      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Verifing...';

      const formData = new FormData(otpForm);
      formData.append('verify_otp', true);
      fetch('', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          alertBox.innerHTML = "";
          console.log(data);
          if (data.status === 'error') {
            let input = document.getElementById("otp_input");
            let errorInput = document.getElementById("otp_error");
            if (input && errorInput) {
              input.classList.add("is-invalid");
              errorInput.innerHTML = data.message;
            }
          } else if (data.status === 'success') {
            alertBox.innerHTML = '<div class="alert alert-success">OTP verified. Redirecting...</div>';
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 800);
          }
        })
        .catch(err => {
          alertBox.innerHTML = '<div class="alert alert-danger">Something went wrong. Try again</div>';
          console.error(err);
        }).finally(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Verify OTP';
        });
    });

    resendBtn.addEventListener('click', function() {
      const formData = new FormData();
      formData.append('resend_otp', true);

      fetch('', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          alertBox.innerHTML = '';
          if (data.status === 'success') {
            alertBox.innerHTML = `<div class="alert alert-info">${data.message}</div>`;
          } else {
            alertBox.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
          }
        })
        .catch(err => {
          alertBox.innerHTML = '<div class="alert alert-danger">Something went wrong. Try again.</div>';
          console.error(err);
        });
    });
  </script>
</body>

</html>