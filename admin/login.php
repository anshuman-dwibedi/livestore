<?php
require_once dirname(__DIR__) . '/core/bootstrap.php';

if (Auth::check() && Auth::role() === 'admin') { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $db       = Database::getInstance();
    $admin    = $db->fetchOne('SELECT * FROM admins WHERE email = ?', [$email]);
    if ($admin && Auth::verifyPassword($password, $admin['password'])) {
        Auth::login(array_merge($admin, ['role' => 'admin']));
        header('Location: dashboard.php'); exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — LiveStore</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../core/ui/devcore.css">
<style>
  body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
  .login-card { width:100%; max-width:400px; }
</style>
</head>
<body>
<div class="login-card dc-card" style="padding:36px">
  <div style="text-align:center;margin-bottom:28px">
    <div style="width:48px;height:48px;background:var(--dc-accent-glow);border-radius:var(--dc-radius-lg);display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
      <i class="dc-icon dc-icon-lock dc-icon-lg" style="color:var(--dc-accent-2)"></i>
    </div>
    <div class="dc-h3">Live<span style="color:var(--dc-accent)">Store</span></div>
    <div class="dc-caption" style="color:var(--dc-text-3);margin-top:4px">Admin Panel</div>
  </div>

  <?php if ($error): ?>
  <div style="background:rgba(255,92,106,0.08);border:1px solid rgba(255,92,106,0.2);border-radius:var(--dc-radius);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-size:.88rem;color:var(--dc-danger)">
    <i class="dc-icon dc-icon-alert-triangle dc-icon-sm"></i>
    <?= htmlspecialchars($error) ?>
  </div>
  <?php endif; ?>

  <form method="POST" style="display:flex;flex-direction:column;gap:16px">
    <div class="dc-form-group">
      <label class="dc-label-field">Email Address</label>
      <input type="email" name="email" class="dc-input" placeholder="admin@store.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
    </div>
    <div class="dc-form-group">
      <label class="dc-label-field">Password</label>
      <input type="password" name="password" class="dc-input" placeholder="••••••••" required>
    </div>
    <button type="submit" class="dc-btn dc-btn-primary dc-btn-full" style="margin-top:4px">
      <i class="dc-icon dc-icon-arrow-right dc-icon-sm"></i> Sign In
    </button>
  </form>

  <p class="dc-caption" style="text-align:center;margin-top:20px;color:var(--dc-text-3)">
    Demo: admin@store.com / admin123
  </p>
</div>
<script src="../../../core/ui/devcore.js"></script>
</body>
</html>
