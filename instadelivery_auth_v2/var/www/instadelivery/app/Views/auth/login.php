<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Login</title>
<?= view('auth/_head') ?>
</head>
<body>
<h1>Login</h1>
<hr>
<?php if(session()->getFlashdata('error')): ?>
<p class="erro"><?= session()->getFlashdata('error') ?></p>
<?php endif; ?>
<?php if(session()->getFlashdata('success')): ?>
<p class="ok"><?= session()->getFlashdata('success') ?></p>
<?php endif; ?>
<form method="POST" action="/login">
    <?= csrf_field() ?>
    <p><label>E-mail:<input type="email" name="email" required autofocus></label></p>
    <p><label>Senha:<input type="password" name="password" required></label></p>
    <p><input type="submit" value="Entrar"></p>
</form>
<hr>
<p>Não tem conta? <a href="/register">Cadastrar</a></p>
</body>
</html>
