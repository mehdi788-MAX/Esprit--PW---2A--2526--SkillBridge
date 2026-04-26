<?php
require_once __DIR__ . '/../../config.php';
ensure_session_started();
session_destroy();
header("Location: " . front_url('login.php'));
exit;
