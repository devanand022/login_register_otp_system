<?php

namespace IncludeFiles;

function generateCaptcha(): string
{
  $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghijklmnopqrstuvwxyz';
  $captcha = '';
  for ($i = 0; $i < 6; $i++) {
    $captcha .= $chars[random_int(0, strlen($chars) - 1)];
  }
  $_SESSION['captcha'] = $captcha;
  return $captcha;
}
