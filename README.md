# InstaDelivery Auth System

Sistema de autenticação multi-fator construído com CodeIgniter 4.

## Stack

- **Backend:** PHP 8.2 + CodeIgniter 4
- **Banco:** MySQL
- **Servidor:** Nginx + PHP-FPM (Oracle VPS)
- **Auth:** JWT (firebase/php-jwt)
- **2FA:** TOTP via Google Authenticator (spomky-labs/otphp)
- **Biometria:** WebAuthn (Touch ID / Face ID via browser)
- **E-mail:** Gmail SMTP

## Funcionalidades

- Cadastro com verificação de e-mail
- Login com senha
- 2FA via Google Authenticator (TOTP)
- 2FA via biometria (WebAuthn)
- Sessão via JWT (cookie HttpOnly)
- Logout com destruição de sessão e cookie

## Fluxo de cadastro

1. Usuário preenche nome, e-mail e senha
2. E-mail de confirmação enviado (link expira em 24h)
3. Após confirmar, configura Google Authenticator (opcional)
4. Configura biometria Touch ID (opcional)
5. Redirecionado para login

## Fluxo de login

1. E-mail + senha validados
2. Se tiver TOTP ativo → tela de código 6 dígitos
3. Se tiver WebAuthn ativo → autenticação biométrica
4. JWT gerado e salvo em cookie seguro (HttpOnly + Secure)
5. Acesso ao dashboard

## Banco de dados

**Tabela `users`**
- `id`, `name`, `email`, `password`
- `email_verified`, `email_token`
- `totp_secret`, `totp_enabled`
- `webauthn_enabled`, `setup_step`

**Tabela `webauthn_credentials`**
- `id`, `user_id`, `credential_id`, `public_key`, `created_at`

## Variáveis de ambiente (.env)

```
database.default.hostname = localhost
database.default.database = instadelivery
database.default.username = ...
database.default.password = ...

email.protocol = smtp
email.SMTPHost = smtp.gmail.com
email.SMTPPort = 587
email.SMTPUser = seu@gmail.com
email.SMTPPass = app_password_gmail
```

## Instalação

```bash
composer install
cp env .env
# editar .env com suas credenciais
php spark migrate
```

## Rotas principais

| Rota | Descricao |
|------|-----------|
| `GET/POST /register` | Cadastro |
| `GET /verify-email/{token}` | Confirmacao de e-mail |
| `GET/POST /login` | Login |
| `GET/POST /login/totp` | Verificacao TOTP |
| `GET/POST /login/webauthn` | Verificacao biometrica |
| `GET/POST /setup/totp` | Configurar Google Authenticator |
| `GET/POST /setup/webauthn` | Configurar Touch ID |
| `GET /dashboard` | Area autenticada |
| `GET /logout` | Logout |
