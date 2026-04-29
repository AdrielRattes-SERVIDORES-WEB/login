<?php
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/',             'AuthController::login');
$routes->get('/register',     'AuthController::register');
$routes->post('/register',    'AuthController::register');
$routes->get('/login',        'AuthController::login');
$routes->post('/login',       'AuthController::login');
$routes->get('/setup/totp',   'AuthController::setupTotp');
$routes->post('/setup/totp',  'AuthController::setupTotp');
$routes->get('/setup/webauthn',  'AuthController::setupWebauthn');
$routes->post('/setup/webauthn', 'AuthController::setupWebauthn');
$routes->get('/login/totp',   'AuthController::loginTotp');
$routes->post('/login/totp',  'AuthController::loginTotp');
$routes->get('/login/webauthn',  'AuthController::loginWebauthn');
$routes->post('/login/webauthn', 'AuthController::loginWebauthn');
$routes->get('/api/webauthn/challenge', 'AuthController::webauthnChallenge');
$routes->get('/dashboard',    'AuthController::dashboard');
$routes->get('/verify-email/(:segment)', 'AuthController::verifyEmail/$1');
$routes->get('/logout',       'AuthController::logout');
