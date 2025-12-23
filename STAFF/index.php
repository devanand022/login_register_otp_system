<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use function IncludeFiles\generateCaptcha;
use Database\DB;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

DB::init();
$conn = DB::$conn;

if (isset($_GET['action']) && $_GET['action'] === 'refresh_captcha') {
  echo generateCaptcha();
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $errors = [];
  $db_error = "";
  define('INVALID_USERNAME', 'Invalid Username or Password');

  header('Content-Type: application/json');

  $captchaInput = $_POST['captcha'];

  if (empty($_POST['username'])) {
    $errors['username'] = "Username is required";
  }

  if (empty($_POST['password'])) {
    $errors['password'] = "Password is required";
  }

  if (!isset($_SESSION['captcha']) || strcasecmp(trim($captchaInput), trim($_SESSION['captcha'])) !== 0) {
    $errors['captcha'] = 'Incorrect CAPTCHA. Please try again.';
    $captchaText = generateCaptcha();
    $_SESSION['captcha'] = $captchaText;
  } else {
    unset($_SESSION['captcha']);
    $captchaText = generateCaptcha();
    $_SESSION['captcha'] = $captchaText;
  }

  if (!empty($errors)) {
    echo json_encode([
      'status' => 'error',
      'errors' => $errors,
      'new_captcha' => $captchaText
    ]);
    exit;
  }

  if (empty($errors)) {
    try {
      $stmt = $conn->prepare("SELECT id, emp_code, email, password FROM staff WHERE emp_code = ? LIMIT 1");
      $stmt->bind_param("s", $_POST['username']);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows !== 1) {
        echo json_encode([
          'status' => 'error',
          'errors' => ['db' => INVALID_USERNAME],
          'new_captcha' => $captchaText
        ]);
        exit;
      }

      $user = $result->fetch_assoc();

      if (!password_verify($_POST['password'], $user['password'])) {
        echo json_encode([
          'status' => 'error',
          'errors' => ['db' => INVALID_USERNAME],
          'new_captcha' => $captchaText
        ]);
        exit;
      }

      $stmt->close();

      $_SESSION['user_id']   = $user['id'];
      $_SESSION['emp_code']  = $user['emp_code'];
      $_SESSION['email']     = $user['email'];

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

        $mail->setFrom('ananddeva345@gmail.com', 'Staff Login OTP');
        $mail->addAddress($user['email']);

        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "<p>Your OTP is: </p><h2>{$otp}</h2><p>Valid for 5 minutes.</p>";

        $mail->send();
        $otpstmt->close();
      } catch (Exception $e) {
        echo json_encode([
          'status' => 'error',
          'errors' => ['db' => 'Unable to send OTP. Please try again later.'],
          'new_captcha' => $captchaText
        ]);
        exit;
      }

      echo json_encode([
        'status' => 'success',
        'message' => 'Login successful. Please verify OTP.',
        'redirect' => './VerifyOtp.php?Page=login'
      ]);
      exit;
    } catch (Exception $e) {
      $db_error = INVALID_USERNAME;
      echo json_encode([
        'status' => 'error',
        'errors' => ['db' => $db_error],
        'new_captcha' => $captchaText
      ]);
    }
  }
  $conn->close();
  exit;
} else {
  $captchaText = generateCaptcha();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>National College(Autonomous), Trichy - 620 001.</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Bootstrap -->
  <link href="../SUPPORTFILES/CSS/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      font-family: "Heebo", sans-serif;
      background: #f5f7fa;
    }
  </style>
</head>

<body>

  <div class="container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="row w-100 justify-content-center">
      <div class="col-sm-10 col-lg-5">

        <div class="card shadow-sm rounded-4">

          <div class="card-body p-4">

            <h3 class="text-center mb-4 fw-bold text-primary">Staff Login</h3>

            <form id="login_form" method="POST" autocomplete="off" novalidate>

              <div class="mb-3">
                <label for="username" class="form-label text-primary">Employee Code</label>
                <input type="text" id="username" name="username" class="form-control form-control-sm" placeholder="Enter Your Employee Code" required>
                <div class="invalid-feedback" id="username_error"></div>
              </div>

              <div class="mb-3">
                <label for="password" class="form-label text-primary">Password</label>
                <input type="password" id="password" name="password" class="form-control form-control-sm" placeholder="Enter Your Password" required>
                <div class="invalid-feedback" id="password_error"></div>
              </div>

              <!-- CAPTCHA  -->
              <div class="mb-3">
                <div class="d-flex align-items-center gap-2">

                  <div class="border rounded d-flex align-items-center justify-content-center px-3"
                    style="min-width: 120px; height: 44px; font-family: monospace; font-weight: 700; letter-spacing: 2px;" id="captchaText">
                    <?php echo htmlspecialchars($captchaText, ENT_QUOTES, 'UTF-8'); ?>
                  </div>

                  <button type="button" id="refreshCaptcha" class="btn btn-outline-secondary btn-sm" title="Refresh CAPTCHA">
                    <i class="bi bi-arrow-clockwise"></i>
                  </button>

                  <input type="text" name="captcha" id="captcha" class="form-control form-control-sm flex-grow-1"
                    placeholder="Enter CAPTCHA" required>
                </div>
                <div class="invalid-feedback" id="captcha_error"></div>
              </div>

              <div class="d-flex justify-content-between mb-3">
                <a href="./Registration.php" class="text-primary small">New Registration</a>
                <a href="./ForgetPassword.php" class="text-primary small">Forget Password?</a>
              </div>

              <div class="d-grid">
                <button type="submit" id="submitBtn" class="btn btn-primary btn-sm text-white">Login</button>
              </div>

              <div id="general_errors" class="mt-3"></div>

            </form>

          </div>
        </div>

        <div class="text-center py-2" style="color:#DE3163;">© <?= date("Y") ?> National College, Trichy - <span class="text-nowrap">620 001.</span></div>
      </div>
    </div>
  </div>

  <script src="../SUPPORTFILES/JS/jquery-3.4.1.min.js"></script>
  <script src="../SUPPORTFILES/JS/bootstrap.bundle.min.js"></script>
  <script src="../SUPPORTFILES/JS/main.js"></script>

  <script>
    function refreshCaptcha() {
      fetch('?action=refresh_captcha')
        .then(res => res.text())
        .then(data => {
          document.getElementById('captchaText').textContent = data;
        });
    }

    const form = document.getElementById("login_form");
    const submitBtn = document.getElementById('submitBtn');

    document.getElementById('refreshCaptcha').addEventListener('click', refreshCaptcha);

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
      document.querySelectorAll(".invalid-feedback").forEach(el => el.innerHTML = "");
      document.getElementById("general_errors").innerHTML = "";

      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Logging in...';

      let formData = new FormData(form);

      fetch("", {
          method: "POST",
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === "error") {
            let firstInputError = null;
            for (let field in data.errors) {
              if (field === 'db') {
                document.getElementById("general_errors").innerHTML = `<div class="alert alert-danger">${data.errors[field]}</div>`;
                continue;
              }
              let input = document.getElementById(field);
              let errorDiv = document.getElementById(field + "_error");
              console.log(errorDiv);
              if (input && errorDiv) {
                input.classList.add("is-invalid");
                errorDiv.innerHTML = data.errors[field];
                if (!firstInputError) firstInputError = input;
              }
            }
            if (firstInputError) firstInputError.focus();
            if (data.new_captcha) {
              document.getElementById('captchaText').textContent = data.new_captcha;
            }
          } else if (data.status === "success") {
            document.getElementById("general_errors").innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            form.reset();
            if (data.new_captcha) {
              document.getElementById('captchaText').textContent = data.new_captcha;
            }
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 800);
          }
        })
        .catch(err => {
          console.error("Submission Error", err);
          document.getElementById("general_errors").innerHTML = `<div class="alert alert-danger">Something went wrong. Please try again.</div>`;
        })
        .finally(() => {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Login';
        });
    });
  </script>

</body>

</html>
