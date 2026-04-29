<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Dashboard</title>
<?= view('auth/_head') ?>
</head>
<body>
<h1>Dashboard</h1>
<hr>
<h2>LOGADO COM SUCESSO</h2>
<p>Bem-vindo, <b><?= esc($user['name']) ?></b>!</p>
<p>E-mail: <?= esc($user['email']) ?></p>
<p>
    Autenticador (TOTP): <b><?= $user['totp_enabled'] ? 'Ativo' : 'Não configurado' ?></b><br>
    Touch ID (WebAuthn): <b><?= $user['webauthn_enabled'] ? 'Ativo' : 'Não configurado' ?></b>
</p>
<hr>
<p><a href="/logout">Sair</a></p>
</body>
</html>
