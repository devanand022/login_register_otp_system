<?php

include '../DATABASE/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ./index.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');

  $user_id = $_SESSION['user_id'];
  $new_password = $_POST['new_password'];
  $confirm_password = $_POST['confirm_password'];

  if ($new_password === $confirm_password) {
    $password = password_hash($new_password, PASSWORD_DEFAULT);
    $conn->begin_transaction();
    try {
      $stmt = $conn->prepare("UPDATE staff SET password = ? WHERE id =? LIMIT 1");
      $stmt->bind_param("si", $password, $user_id);
      if ($stmt->execute()) {
        $_SESSION = [];
        session_destroy();
        echo json_encode([
          'status' => 'success',
          'message' => 'Password updated successfully.',
          'redirect' => './index.php'
        ]);
      } else {
        echo json_encode([
          'status' => 'error',
          'message' => 'Failed to update password.'
        ]);
      }

      $stmt->close();
    } catch (Exception $e) {
      $conn->rollback();
      echo json_encode([
        'status' => 'error',
        'message' => 'Failed to Update Password'
      ]);
    }
    $conn->close();
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>National College(Autonomous), Trichy - Change Password</title>
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
          <h4 class="fw-bold text-primary">Change Password</h4>
          <p class="text-muted small">Enter your new password below</p>
        </div>

        <form id="changePasswordForm" methed="POST" autocomplete="off" novalidate>

          <!-- New Password Input -->
          <div class="mb-3">
            <label for="new_password" class="form-label text-primary">New Password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Enter new password" required>
            <div class="invalid-feedback" id="new_password_error"></div>
          </div>

          <!-- Confirm Password Input -->
          <div class="mb-3">
            <label for="confirm_password" class="form-label text-primary">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
            <div class="invalid-feedback" id="confirm_password_error"></div>
          </div>

          <!-- Submit Button -->
          <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary text-white">Update Password</button>
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

  <!-- Optional AJAX handling for password change -->
  <script>
    const form = document.getElementById('changePasswordForm');
    const generalMessage = document.getElementById('general_message');

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      generalMessage.innerHTML = '';

      const newPassword = document.getElementById('new_password').value.trim();
      const confirmPassword = document.getElementById('confirm_password').value.trim();
      let valid = true;

      // Validate new password
      if (!newPassword) {
        document.getElementById('new_password_error').textContent = "New password is required";
        document.getElementById('new_password').classList.add('is-invalid');
        valid = false;
      } else if (newPassword.length < 8) {
        document.getElementById('new_password_error').textContent =
          "Password must be at least 8 characters long";
        document.getElementById('new_password').classList.add('is-invalid');
        valid = false;

      } else {
        document.getElementById('new_password_error').textContent = "";
        document.getElementById('new_password').classList.remove('is-invalid');
      }

      // Validate confirm password
      if (!confirmPassword) {
        document.getElementById('confirm_password_error').textContent = "Confirm password is required";
        document.getElementById('confirm_password').classList.add('is-invalid');
        valid = false;
      } else if (confirmPassword !== newPassword) {
        document.getElementById('confirm_password_error').textContent = "Passwords do not match";
        document.getElementById('confirm_password').classList.add('is-invalid');
        valid = false;
      } else {
        document.getElementById('confirm_password_error').textContent = "";
        document.getElementById('confirm_password').classList.remove('is-invalid');
      }

      if (!valid) return;

      // Send form data via AJAX
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
        .catch(err => {
          generalMessage.innerHTML = `<div class="alert alert-danger">Something went wrong. Try again.</div>`;
        });
    });
  </script>

</body>

</html>