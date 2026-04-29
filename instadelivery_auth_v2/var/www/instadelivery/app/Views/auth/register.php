<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Cadastro</title>
<?= view('auth/_head') ?>
</head>
<body>
<h1>Cadastro</h1>
<hr>
<?php if(session()->getFlashdata('error')): ?>
<p class="erro"><?= session()->getFlashdata('error') ?></p>
<?php endif; ?>
<form method="POST" action="/register">
    <?= csrf_field() ?>
    <p><label>Nome:<input type="text" name="name" required autofocus></label></p>
    <p><label>E-mail:<input type="email" name="email" required></label></p>
    <p><label>Senha:<input type="password" name="password" required minlength="8"></label></p>
    <p><input type="submit" value="Cadastrar"></p>
</form>
<hr>
<p>Já tem conta? <a href="/login">Fazer login</a></p>
</body>
</html>
