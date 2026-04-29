<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Verificação 2FA</title>
<?= view('auth/_head') ?>
</head>
<body>
<h1>Verificação 2FA</h1>
<hr>
<p>Insira o código de 6 dígitos do seu app autenticador.</p>
<?php if(session()->getFlashdata('error')): ?>
<p class="erro"><?= session()->getFlashdata('error') ?></p>
<?php endif; ?>
<form method="POST" action="/login/totp">
    <?= csrf_field() ?>
    <p><label>Código TOTP:<input type="text" name="code" maxlength="6" required autofocus placeholder="000000" inputmode="numeric"></label></p>
    <p><input type="submit" value="Verificar"></p>
</form>
</body>
</html>
