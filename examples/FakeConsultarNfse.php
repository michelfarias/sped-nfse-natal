<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\Common\Certificate;
use NFePHP\NFSeNatal\Common\Soap\SoapFake;
use NFePHP\NFSeNatal\Tools;
use NFePHP\NFSeNatal\Common\FakePretty;

try {

    $config = [
        'cnpj' => '99999999000191',
        'im' => '1733160024',
        'cmun' => '2408102',
        'razao' => 'Empresa Test Ltda',
        'tpamb' => 2
    ];

    $configJson = json_encode($config);

    $content = file_get_contents('expired_certificate.pfx');
    $password = 'associacao';
    $cert = Certificate::readPfx($content, $password);
    
    $soap = new SoapFake();
    $soap->disableCertValidation(true);
    
    $tools = new Tools($configJson, $cert);
    //$tools->loadSoapClass($soap);

    $dini = '2019-12-01'; //obrigatório
    $dfim = '2019-12-31'; //obrigatório
    $tomadorCnpj = null; //opcional
    $tomadorCpf = null; //opcional
    $tomadorIM = null; //opcional

    $response = $tools->consultarNfse($dini, $dfim, $tomadorCnpj, $tomadorCpf, $tomadorIM);

    //echo FakePretty::prettyPrint($response, '');
    header("Content-type: text/plain");
    echo $response;
 
} catch (\Exception $e) {
    echo $e->getMessage();
}
