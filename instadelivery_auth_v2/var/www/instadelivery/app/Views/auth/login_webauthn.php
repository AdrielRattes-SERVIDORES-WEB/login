<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>Touch ID</title>
<?= view('auth/_head') ?>
</head>
<body>
<h1>Touch ID / Biometria</h1>
<hr>
<p>Use sua biometria para confirmar o acesso.</p>
<?php if(session()->getFlashdata('error')): ?>
<p class="erro"><?= session()->getFlashdata('error') ?></p>
<?php endif; ?>
<p id="status"></p>
<form method="POST" action="/login/webauthn" id="webauthn-form">
    <?= csrf_field() ?>
    <input type="hidden" name="credential_id" id="credential_id">
    <p><button type="button" onclick="verifyWebAuthn()">Usar Touch ID / Biometria</button></p>
</form>
<script>
function b64url(s) {
    s = s.replace(/-/g, '+').replace(/_/g, '/');
    while (s.length % 4) s += '=';
    return Uint8Array.from(atob(s), c => c.charCodeAt(0));
}
const CHALLENGE = '<?= $challenge ?>';
const CRED_IDS  = <?= $credIds ?>;

async function verifyWebAuthn() {
    document.getElementById('status').innerText = 'Aguardando biometria...';
    try {
        const assertion = await navigator.credentials.get({
            publicKey: {
                challenge: b64url(CHALLENGE),
                rpId: 'instadelivery.shop',
                allowCredentials: CRED_IDS.map(id => ({ type: 'public-key', id: b64url(id) })),
                userVerification: 'preferred',
                timeout: 60000,
            }
        });
        document.getElementById('credential_id').value = btoa(String.fromCharCode(...new Uint8Array(assertion.rawId)));
        document.getElementById('status').innerText = 'Verificado! Entrando...';
        document.getElementById('webauthn-form').submit();
    } catch(e) {
        document.getElementById('status').innerText = 'Erro: ' + e.message;
    }
}
window.onload = verifyWebAuthn;
</script>
</body>
</html>
