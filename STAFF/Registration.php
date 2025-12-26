<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth\Authservice;
use App\Auth\AuthException;
use App\Security\Csrf;
use App\Repository\MasterDataRepository;
use function IncludeFiles\generateCaptcha;

$auth = new Authservice();
$repo = new MasterDataRepository();

$errors = [];
$db_error = "";

$designation_result = $repo->getDesignations();
$department_result = $repo->getDepartments();
$csrfToken = Csrf::token();

//Fields
$personalDetailsfields = [
  ['label' => 'Employee Name', 'name' => 'fullname', 'type' => 'text', 'placeholder' => 'Enter Your Name', 'required' => true, 'col' => '6'],
  ['label' => 'Employee Code', 'name' => 'emp_code', 'type' => 'text', 'placeholder' => 'Enter Your Employee Code', 'required' => true, 'col' => '6'],
  ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'placeholder' => 'Enter Your Email', 'required' => true, 'col' => '6'],
  ['label' => 'Mobile Number', 'name' => 'mobile', 'type' => 'text', 'placeholder' => 'Enter Your Mobile Number', 'required' => true, 'col' => '6'],
  ['label' => 'Designation', 'name' => 'designation', 'type' => 'select', 'options' => $designation_result, 'placeholder' => 'Select Designation', 'required' => true, 'col' => '6'],
  ['label' => 'Department', 'name' => 'department', 'type' => 'select', 'options' => $department_result, 'placeholder' => 'Select Department', 'required' => true, 'col' => '6'],
  ['label' => 'Date of Birth', 'name' => 'dob', 'type' => 'date', 'required' => true, 'col' => '4'],
  ['label' => 'Gender', 'name' => 'gender', 'type' => 'select', 'options' => [
    ['id' => 'male', 'gender' => 'Male'],
    ['id' => 'female', 'gender' => 'Female']
  ], 'placeholder' => 'Select Gender', 'required' => true, 'col' => '4'],
  ['label' => 'Category', 'name' => 'category', 'type' => 'select', 'options' => [
    ['id' => 'teaching', 'category' => 'Teaching'],
    ['id' => 'non_teaching', 'category' => 'Non Teaching'],
  ], 'placeholder' => 'Select Category', 'required' => true, 'col' => '4'],
  ['label' => 'Password', 'name' => 'password', 'type' => 'password', 'placeholder' => 'Enter Your Password', 'required' => true, 'col' => '6'],
  ['label' => 'Confirm Password', 'name' => 'confirm_password', 'type' => 'text', 'placeholder' => 'Enter Your Password Again', 'required' => true, 'col' => '6'],
];

if (isset($_GET['action']) && $_GET['action'] === 'refresh_captcha') {
  echo generateCaptcha();
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $captchaInput = $_POST['captcha'];

  //All Fields Validation
  foreach ($personalDetailsfields as $field) {
    if (empty($_POST[$field['name']])) {
      $errors[$field['name']] = $field['label'] . " is required";
    }
  }

  // Mobile Number Validation
  if (!empty($_POST['mobile']) && !preg_match('/^\d{10}$/', $_POST['mobile'])) {
    $errors['mobile'] = "Contact number must be exactly 10 digits.";
  }

  // Email Validation
  if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Invalid email address.";
  }

  // Password Validation
  if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
    if ($_POST['password'] !== $_POST['confirm_password']) {
      $errors['confirm_password'] = 'Password do not match';
    }
    if (strlen($_POST['password']) < 8) {
      $errors['password'] = 'Password must be atleast 8 characters';
    }
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

  //Headers
  header('Content-Type: application/json');

  if (!empty($errors)) {
    echo json_encode([
      'status' => 'error',
      'errors' => $errors,
      'new_captcha' => $captchaText
    ]);
    exit;
  }

  if (empty($errors)) {
    $data = [
      'fullname' => trim($_POST['fullname']),
      'email' => trim($_POST['email']),
      'emp_code' => trim($_POST['emp_code']),
      'mobile' => trim($_POST['mobile']),
      'designation' => $_POST['designation'],
      'department' => $_POST['department'],
      'dob' => $_POST['dob'],
      'gender' => $_POST['gender'],
      'category' => $_POST['category'],
      'password' => $_POST['password'],
    ];

    try {
      if (!Csrf::validate($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
      }

      $auth->register($data);

      echo json_encode([
        'status' => 'success',
        'message' => 'Staff registered successfully',
        'new_captcha' => $captchaText,
        'redirect' => './index.php'
      ]);
    } catch (AuthException $e) {
      $db_error = $e->getMessage();
      echo json_encode([
        'status' => 'error',
        'errors' => ['db' => $db_error],
        'new_captcha' => $captchaText
      ]);
    } catch (Exception $e) {
      $db_error = $e->getMessage();
      echo json_encode([
        'status' => 'error',
        'errors' => ['db' => $db_error],
        'new_captcha' => $captchaText
      ]);
    }
  }
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
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="" name="keywords">
  <meta content="" name="description">

  <!-- Google Web Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Icon Font Stylesheet -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Customized Bootstrap Stylesheet -->
  <link href="../SUPPORTFILES/CSS/bootstrap.min.css" rel="stylesheet">

  <!-- Template Stylesheet -->
  <link href="../SUPPORTFILES/CSS/style.css" rel="stylesheet">

  <style>
    body {
      font-family: "Heebo", sans-serif;
      background-color: #f5f7fa;
    }
  </style>


</head>

<body>
  <div class="container py-3 my-4">
    <div class="row">
      <div class="col-lg-2"></div>
      <div class="col-lg-8">
        <div class="box">

          <h3 class="text-center text-primary py-3">Staff Registration Form</h3>
          <!-- Form Starting -->
          <form id="reg_form" method="POST" autocomplete="off" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="row g-3">
              <?php foreach ($personalDetailsfields as $field): ?>
                <div class="col-md-<?= $field['col'] ?? 6 ?> mb-2">
                  <!-- Label -->
                  <label for="<?= $field['name'] ?>" class="form-label text-primary"><?= $field['label'] ?></label>
                  <!-- Input Fields -->
                  <!-- Select -->
                  <?php if ($field['type'] === 'select'): ?>
                    <select class="form-select form-select-sm" name="<?= $field['name'] ?>" id="<?= $field['name'] ?>" <?= $field['required'] ? 'required' : '' ?>>
                      <option value=""><?= $field['placeholder'] ?? 'Select' ?></option>
                      <?php foreach ($field['options'] as $opt): ?>
                        <option value="<?= htmlspecialchars($opt['id']) ?>">
                          <?= htmlspecialchars($opt[$field['name']]) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  <?php else: ?>
                    <!-- Text, Date -->
                    <input type="<?= $field['type'] ?>" class="form-control form-control-sm" name="<?= $field['name'] ?>" id="<?= $field['name'] ?>" placeholder="<?= $field['placeholder'] ?? '' ?>" <?= $field['required'] ? 'required' : '' ?>>
                  <?php endif; ?>
                  <!-- Error Field -->
                  <div class="invalid-feedback" id="<?= $field['name'] ?>_error"></div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Captcha -->
            <!-- Captcha -->
            <div class="row align-items-center g-2 my-2">

              <div class="col-auto d-flex align-items-center gap-2">
                <div class="border rounded d-flex align-items-center justify-content-center px-3"
                  style="min-width: 140px; height: 44px; font-family: monospace; font-weight: 700; letter-spacing: 2px;" id="captchaText">
                  <?php echo htmlspecialchars($captchaText, ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <button type="button"
                  class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center"
                  id="refreshCaptcha">
                  <i class="bi bi-arrow-clockwise"></i>
                </button>
              </div>

              <div class="col-auto d-md-flex align-items-center">
                <input
                  name="captcha"
                  type="text"
                  class="form-control form-control-sm"
                  placeholder="Enter CAPTCHA"
                  id="captcha"
                  required>
                <div class="mx-2 invalid-feedback" id="captcha_error"></div>
              </div>

            </div>

            <!-- Submit -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <a href="./index.php" class="link-primary small text-decoration-none">
                Already have an account? Sign in
              </a>
              <button class="btn btn-primary text-white" type="submit">
                Submit
              </button>
            </div>
            <!-- End of Form -->
          </form>

          <!-- General Errors -->
          <div id="general_errors" class="mt-3"></div>


          <!-- Footer -->
          <div style="color:#DE3163" class="footer-copyright text-center py-2">© <?= date("Y") ?> National College, Trichy - <span class="text-nowrap">620 001.</span></div>
        </div>
      </div>
      <div class="col-lg-2"></div>
    </div>
  </div>
  <!-- JavaScript Libraries -->
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

    const form = document.getElementById("reg_form");

    document.getElementById('refreshCaptcha').addEventListener('click', () => refreshCaptcha());

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      document.querySelectorAll(".is-invalid").forEach(el => {
        el.classList.remove("is-invalid");
      });
      document.querySelectorAll(".invalid-feedback").forEach(el => {
        el.innerHTML = "";
      });
      document.getElementById("general_errors").innerHTML = "";

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
              if (input && errorDiv) {
                input.classList.add("is-invalid");
                errorDiv.innerHTML = data.errors[field];
                if (!firstInputError) firstInputError = input;
              }
            }

            if (firstInputError) {
              firstInputError.scrollIntoView({
                behavior: "smooth",
                block: "center"
              });
              firstInputError.focus();
            }
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
    });
  </script>

</body>

</html>
