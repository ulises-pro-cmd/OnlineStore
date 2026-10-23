<?php
require_once __DIR__ . '/../google-api-php-client/vendor/autoload.php'; 
// o la ruta exacta donde hayas colocado el autoload.php de la librería descargada

$config_local = __DIR__ . '/google_config.local.php';
if (is_file($config_local)) {
    require $config_local;
}

$clientID = $clientID ?? getenv('GOOGLE_CLIENT_ID') ?: '';
$clientSecret = $clientSecret ?? getenv('GOOGLE_CLIENT_SECRET') ?: '';
$redirectUri = $redirectUri ?? getenv('GOOGLE_REDIRECT_URI') ?: '';

if ($clientID === '' || $clientSecret === '' || $redirectUri === '') {
    throw new RuntimeException('Falta configurar las credenciales de Google.');
}

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope('email');
$client->addScope('profile');
?>
