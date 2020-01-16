<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\Common\Certificate;
use NFePHP\Natal\Tools;
use NFePHP\Natal\Common\Soap\SoapFake;
use NFePHP\Natal\Common\FakePretty;

try {

    $config = [
        'cnpj' => '15581977000117',
        'im' => '1983539',
        'cmun' => '2408102',
        'razao' => 'Empresa Test Ltda',
        'tpamb' => 2
    ];

    $configJson = json_encode($config);

    $content = file_get_contents('C:\Users\Cleiton\Downloads\FREDERICK KEYSTER COSTA DE AZEVEDO15581977000117.pfx');
    $password = '123456';
    $cert = Certificate::readPfx($content, $password);
    
    $soap = new SoapFake();
    $soap->disableCertValidation(true);
    
    $tools = new Tools($configJson, $cert);
    //$tools->loadSoapClass($soap);

    $protocolo = '40100270';

    $response = $tools->consultarLoteRps($protocolo);

    //echo FakePretty::prettyPrint($response, '');
    header("Content-type: text/plain");
    echo $response;
 
} catch (\Exception $e) {
    echo $e->getMessage();
}