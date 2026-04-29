<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Configurar Touch ID</title>
<?= view('auth/_head') ?>
</head>
<body>
<h1>Etapa 2 de 2: Touch ID</h1>
<hr>
<p>Registre sua biometria (Touch ID, Face ID ou chave de segurança).</p>
<?php if(session()->getFlashdata('error')): ?>
<p class="erro"><?= session()->getFlashdata('error') ?></p>
<?php endif; ?>
<p id="status"></p>
<form method="POST" action="/setup/webauthn" id="webauthn-form">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="register">
    <input type="hidden" name="credential_id" id="credential_id">
    <input type="hidden" name="public_key" id="public_key">
    <p><button type="button" onclick="registerWebAuthn()">Registrar Biometria</button></p>
</form>
<hr>
<form method="POST" action="/setup/webauthn">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="skip">
    <p><input type="submit" value="Deixar para depois"></p>
</form>
<script>
function b64url(s) {
    s = s.replace(/-/g, '+').replace(/_/g, '/');
    while (s.length % 4) s += '=';
    return Uint8Array.from(atob(s), c => c.charCodeAt(0));
}
async function registerWebAuthn() {
    document.getElementById('status').innerText = 'Aguardando biometria...';
    try {
        const cred = await navigator.credentials.create({
            publicKey: {
                challenge: b64url('<?= $challenge ?>'),
                rp: { name: 'InstaDelivery', id: 'instadelivery.shop' },
                user: {
                    id: b64url('<?= $userId64 ?>'),
                    name: '<?= esc($user['email']) ?>',
                    displayName: '<?= esc($user['name']) ?>',
                },
                pubKeyCredParams: [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
                authenticatorSelection: { userVerification: 'preferred' },
                timeout: 60000,
            }
        });
        document.getElementById('credential_id').value = btoa(String.fromCharCode(...new Uint8Array(cred.rawId)));
        document.getElementById('public_key').value = btoa(String.fromCharCode(...new Uint8Array(cred.response.attestationObject)));
        document.getElementById('status').innerText = 'Biometria capturada! Salvando...';
        document.getElementById('webauthn-form').submit();
    } catch(e) {
        document.getElementById('status').innerText = 'Erro: ' + e.message;
    }
}
</script>
</body>
</html>
