<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Configurar Authenticator</title>
<?= view('auth/_head') ?>
</head>
<body>
<h1>Etapa 1 de 2: Authenticator</h1>
<hr>
<p>Escaneie o QR Code com o Google Authenticator ou Authy:</p>
<p><img src="<?= $qr_url ?>" alt="QR Code" style="max-width:200px;display:block;margin:10px 0;"></p>
<p>Código manual: <b id="totp-secret"><?= $secret ?></b> <button type="button" onclick="copiarCodigo()" style="width:auto;padding:6px 12px;font-size:0.85rem;">Copiar</button></p>
<p id="copiado" style="display:none;color:green;">Copiado!</p>
<script>
function copiarCodigo() {
    navigator.clipboard.writeText(document.getElementById('totp-secret').innerText)
        .then(() => { document.getElementById('copiado').style.display='block'; setTimeout(()=>{ document.getElementById('copiado').style.display='none'; }, 2000); });
}
</script>
<hr>
<?php if(session()->getFlashdata('error')): ?>
<p class="erro"><?= session()->getFlashdata('error') ?></p>
<?php endif; ?>
<form method="POST" action="/setup/totp">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="confirm">
    <input type="hidden" name="totp_secret" value="<?= esc($secret) ?>">
    <p><label>Código de 6 dígitos:<input type="text" name="code" maxlength="6" required autofocus placeholder="000000" inputmode="numeric"></label></p>
    <p><input type="submit" value="Confirmar e Ativar"></p>
</form>
<hr>
<form method="POST" action="/setup/totp">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="skip">
    <p><input type="submit" value="Deixar para depois" class="btn-secundario"></p>
</form>
</body>
</html>
