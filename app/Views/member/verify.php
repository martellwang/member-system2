<div class="container">
  <div class="card result-card">
    <div class="result-icon <?= $success ? 'ok' : 'fail' ?>"><?= $success ? '✓' : '!' ?></div>
    <div class="form-title"><?= htmlspecialchars($title) ?></div>
    <div class="form-subtitle"><?= htmlspecialchars($message) ?></div>
    <a class="btn btn-primary" href="<?= htmlspecialchars($appBase, ENT_QUOTES) ?>/register">回到會員註冊</a>
  </div>
</div>
