<?php
namespace App\Models;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name','email','password','totp_secret','totp_enabled','webauthn_enabled','setup_step','email_verified','email_token'];
    protected $useTimestamps = true;
}
