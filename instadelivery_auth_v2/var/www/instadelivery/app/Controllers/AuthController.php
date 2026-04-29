<?php
namespace App\Controllers;
use App\Models\UserModel;
use OTPHP\TOTP;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends BaseController
{
    private string $jwtKey = 'instadelivery_jwt_secret_key_2026_auth_system_secure';

    public function register()
    {
        if ($this->request->getMethod() === 'POST') {
            $name     = $this->request->getPost('name');
            $email    = $this->request->getPost('email');
            $password = $this->request->getPost('password');
            if (!$name || !$email || !$password)
                return redirect()->back()->with('error', 'Preencha todos os campos.');
            $model = new UserModel();
            if ($model->where('email', $email)->first())
                return redirect()->back()->with('error', 'E-mail já cadastrado.');
            $token = bin2hex(random_bytes(32));
            $model->insert([
                'name'           => $name,
                'email'          => $email,
                'password'       => password_hash($password, PASSWORD_BCRYPT),
                'setup_step'     => 'totp',
                'email_verified' => 0,
                'email_token'    => $token,
            ]);
            $this->sendVerificationEmail($email, $name, $token);
            return redirect()->to('/login')->with('success', 'Cadastro realizado! Verifique seu e-mail para continuar. Se não chegou na caixa de entrada, verifique o spam.');
        }
        return view('auth/register');
    }

    private function sendVerificationEmail(string $email, string $name, string $token): void
    {
        $link = base_url("/verify-email/{$token}");
        $mailer = \Config\Services::email();
        $mailer->setTo($email);
        $mailer->setSubject('Confirme seu e-mail');
        $mailer->setMessage("
            <p>Olá, {$name}!</p>
            <p>Clique no link abaixo para confirmar seu e-mail:</p>
            <p><a href='{$link}'>{$link}</a></p>
            <p>O link expira em 24 horas.</p>
        ");
        $mailer->setMailType('html');
        $mailer->send();
    }

    public function verifyEmail(string $token)
    {
        $model = new UserModel();
        $user  = $model->where('email_token', $token)->where('email_verified', 0)->first();
        if (!$user)
            return redirect()->to('/login')->with('error', 'Link inválido ou já utilizado.');
        $model->update($user['id'], ['email_verified' => 1, 'email_token' => null]);
        session()->set('setup_user_id', $user['id']);
        return redirect()->to('/setup/totp')->with('success', 'E-mail confirmado! Configure seu 2FA.');
    }

    public function login()
    {
        if ($this->request->getMethod() === 'POST') {
            $email    = $this->request->getPost('email');
            $password = $this->request->getPost('password');
            $model    = new UserModel();
            $user     = $model->where('email', $email)->first();
            if (!$user || !password_verify($password, $user['password']))
                return redirect()->back()->with('error', 'Credenciais inválidas.');
            if (!$user['email_verified'])
                return redirect()->back()->with('error', 'Confirme seu e-mail antes de fazer login.');
            if (!$user['totp_enabled'] && !$user['webauthn_enabled'])
                return $this->issueJwt($user);
            session()->set('auth_user_id', $user['id']);
            session()->set('auth_pending_totp',    (bool)$user['totp_enabled']);
            session()->set('auth_pending_webauthn', (bool)$user['webauthn_enabled']);
            if ($user['webauthn_enabled'])
                return redirect()->to('/login/webauthn');
            return redirect()->to('/login/totp');
        }
        return view('auth/login');
    }

    public function setupTotp()
    {
        $userId = session()->get('setup_user_id');
        if (!$userId) return redirect()->to('/register');
        $model = new UserModel();
        $user  = $model->find($userId);
        if ($this->request->getMethod() === 'POST') {
            if ($this->request->getPost('action') === 'skip')
                return redirect()->to('/setup/webauthn');
            $code   = trim($this->request->getPost('code'));
            $secret = $this->request->getPost('totp_secret');
            if (!$secret) return redirect()->back()->with('error', 'Sessão expirada. Recarregue a página.');
            $totp   = TOTP::createFromSecret($secret);
            $totp->setDigits(6);
            $totp->setPeriod(30);
            if (!$totp->verify($code, null, 29))
                return redirect()->back()->with('error', 'Código inválido. Tente novamente.');
            $model->update($userId, ['totp_secret' => $secret, 'totp_enabled' => 1]);
            return redirect()->to('/setup/webauthn')->with('success', 'TOTP ativado! Seu app deve mostrar agora: ' . $totp->now() . ' — se bater, está correto.');
        }
        $secret = \ParagonIE\ConstantTime\Base32::encodeUpperUnpadded(random_bytes(20));
        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($user['email']);
        $totp->setIssuer('Auth');
        $totp->setDigits(6);
        $totp->setPeriod(30);
        $qrUrl = $totp->getQrCodeUri('https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=200x200', '[DATA]');
        return view('auth/setup_totp', ['qr_url' => $qrUrl, 'secret' => $totp->getSecret()]);
    }

    public function setupWebauthn()
    {
        $userId = session()->get('setup_user_id');
        if (!$userId) return redirect()->to('/register');
        $model = new UserModel();
        if ($this->request->getMethod() === 'POST') {
            if ($this->request->getPost('action') === 'skip') {
                $model->update($userId, ['setup_step' => 'done']);
                session()->remove('setup_user_id');
                return redirect()->to('/login')->with('success', 'Configuração concluída! Faça login.');
            }
            $credentialId = $this->request->getPost('credential_id');
            $publicKey    = $this->request->getPost('public_key');
            if ($credentialId && $publicKey) {
                db_connect()->table('webauthn_credentials')->insert([
                    'user_id'       => $userId,
                    'credential_id' => $credentialId,
                    'public_key'    => $publicKey,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
                $model->update($userId, ['webauthn_enabled' => 1, 'setup_step' => 'done']);
                session()->remove('setup_user_id');
                return redirect()->to('/login')->with('success', 'Touch ID configurado! Faça login.');
            }
            return redirect()->back()->with('error', 'Erro ao salvar credencial.');
        }
        $user      = $model->find($userId);
        $challenge = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $userId64  = rtrim(strtr(base64_encode((string)$userId), '+/', '-_'), '=');
        session()->set('webauthn_challenge', $challenge);
        return view('auth/setup_webauthn', [
            'user'      => $user,
            'challenge' => $challenge,
            'userId64'  => $userId64,
        ]);
    }

    public function loginTotp()
    {
        $userId = session()->get('auth_user_id');
        if (!$userId) return redirect()->to('/login');
        if ($this->request->getMethod() === 'POST') {
            $code  = trim($this->request->getPost('code'));
            $model = new UserModel();
            $user  = $model->find($userId);
            $totp  = TOTP::createFromSecret($user['totp_secret']);
            $totp->setDigits(6);
            $totp->setPeriod(30);
            log_message('debug', "TOTP recebido: [{$code}] | gerado: [{$totp->now()}] | secret: [{$user['totp_secret']}]");
            if (!$totp->verify($code, null, 29))
                return redirect()->back()->with('error', 'Código inválido. [recebido: '.$code.' | esperado: '.$totp->now().']');
            session()->set('auth_pending_totp', false);
            if (session()->get('auth_pending_webauthn'))
                return redirect()->to('/login/webauthn');
            return $this->issueJwt($user);
        }
        return view('auth/login_totp');
    }

    public function loginWebauthn()
    {
        $userId = session()->get('auth_user_id');
        if (!$userId) return redirect()->to('/login');
        if ($this->request->getMethod() === 'POST') {
            $credentialId = $this->request->getPost('credential_id');
            $cred = db_connect()->table('webauthn_credentials')
                ->where('user_id', $userId)
                ->where('credential_id', $credentialId)
                ->get()->getRowArray();
            if (!$cred)
                return redirect()->back()->with('error', 'Touch ID não reconhecido.');
            session()->set('auth_pending_webauthn', false);
            if (session()->get('auth_pending_totp'))
                return redirect()->to('/login/totp');
            $model = new UserModel();
            return $this->issueJwt($model->find($userId));
        }
        $challenge   = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        session()->set('webauthn_challenge', $challenge);
        $credentials = db_connect()->table('webauthn_credentials')->where('user_id', $userId)->get()->getResultArray();
        $credIds     = array_map(fn($c) => $c['credential_id'], $credentials);
        return view('auth/login_webauthn', [
            'challenge' => $challenge,
            'credIds'   => json_encode($credIds),
        ]);
    }

    public function webauthnChallenge()
    {
        $userId = session()->get('auth_user_id') ?? session()->get('setup_user_id');
        if (!$userId) return $this->response->setJSON(['error'=>'unauthorized'])->setStatusCode(401);
        $model = new UserModel();
        $user  = $model->find($userId);
        $challenge = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        session()->set('webauthn_challenge', $challenge);
        $credentials = db_connect()->table('webauthn_credentials')->where('user_id',$userId)->get()->getResultArray();
        return $this->response->setJSON([
            'challenge'        => $challenge,
            'rp'               => ['name'=>'InstaDelivery','id'=>'instadelivery.shop'],
            'user'             => ['id'=>rtrim(strtr(base64_encode((string)$userId), '+/', '-_'), '='),'name'=>$user['email'],'displayName'=>$user['name']],
            'allowCredentials' => array_map(fn($c) => ['type'=>'public-key','id'=>$c['credential_id']], $credentials),
        ]);
    }

    public function dashboard()
    {
        $jwt = $this->request->getCookie('jwt') ?? str_replace('Bearer ','',($this->request->getHeaderLine('Authorization')));
        if (!$jwt) return redirect()->to('/login');
        try {
            $payload = JWT::decode($jwt, new Key($this->jwtKey, 'HS256'));
            $model   = new UserModel();
            $user    = $model->find($payload->sub);
            return view('auth/dashboard', ['user' => $user]);
        } catch (\Exception $e) {
            return redirect()->to('/login')->with('error', 'Sessão expirada.');
        }
    }

    public function logout()
    {
        session()->destroy();
        $this->response->deleteCookie('jwt');
        return redirect()->to('/login');
    }

    private function issueJwt(array $user)
    {
        $payload = ['sub'=>$user['id'],'name'=>$user['name'],'iat'=>time(),'exp'=>time()+3600*8];
        $token = JWT::encode($payload, $this->jwtKey, 'HS256');
        session()->destroy();
        return redirect()->to('/dashboard')->setCookie('jwt', $token, 3600*8, '', '/', '', true, true);
    }
}
