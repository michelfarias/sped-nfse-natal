<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require_once '../bootstrap.php';

use NFePHP\Common\Certificate;
use NFePHP\Natal\Common\Soap\SoapFake;
use NFePHP\Natal\Rps;
use NFePHP\Natal\Tools;

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

    $arps = [];

    $std = new \stdClass();
    $std->version = '1.00';
    $std->IdentificacaoRps = new \stdClass();
    $std->IdentificacaoRps->Numero = 1000; //limite 15 digitos
    $std->IdentificacaoRps->Serie = '1'; //BH deve ser string numerico
    $std->IdentificacaoRps->Tipo = 1; //1 - RPS 2-Nota Fiscal Conjugada (Mista) 3-Cupom
    $std->DataEmissao = '2020-01-15T12:33:22';
    $std->NaturezaOperacao = 1; // 1 – Tributação no município
                                // 2 - Tributação fora do município
                                // 3 - Isenção
                                // 4 - Imune
                                // 5 – Exigibilidade suspensa por decisão judicial
                                // 6 – Exigibilidade suspensa por procedimento administrativo

    $std->RegimeEspecialTributacao = 3;    // 1 – Microempresa municipal
                                           // 2 - Estimativa
                                           // 3 – Sociedade de profissionais
                                           // 4 – Cooperativa
                                           // 5 – MEI – Simples Nacional
                                           // 6 – ME EPP – Simples Nacional

    $std->OptanteSimplesNacional = 2; //1 - SIM 2 - Não
    $std->IncentivadorCultural = 1; //1 - SIM 2 - Não
    $std->Status = 1;  // 1 – Normal  2 – Cancelado

    $std->Tomador = new \stdClass();
    $std->Tomador->Cnpj = null;
    $std->Tomador->Cpf = "06337921445";
    $std->Tomador->RazaoSocial = "Fulano de Tal";

    $std->Tomador->Endereco = new \stdClass();
    $std->Tomador->Endereco->Endereco = 'Rua das Rosas';
    $std->Tomador->Endereco->Numero = '111';
    $std->Tomador->Endereco->Complemento = 'Sobre Loja';
    $std->Tomador->Endereco->Bairro = 'Centro';
    $std->Tomador->Endereco->CodigoMunicipio = 2403251;
    $std->Tomador->Endereco->Uf = 'RN';
    $std->Tomador->Endereco->Cep = 59200000;

    $std->Servico = new \stdClass();
    $std->Servico->ItemListaServico = '14.06';
    $std->Servico->CodigoCnae = '6821801';
    $std->Servico->CodigoTributacaoMunicipio = '522310000';
    $std->Servico->Discriminacao = 'Teste de RPS';
    $std->Servico->CodigoMunicipio = 3509502;

    $std->Servico->Valores = new \stdClass();
    $std->Servico->Valores->ValorServicos = 20.00;
    $std->Servico->Valores->ValorDeducoes = 0.00;
    $std->Servico->Valores->ValorPis = 0.00;
    $std->Servico->Valores->ValorCofins = 0.00;
    $std->Servico->Valores->ValorInss = 0.00;
    $std->Servico->Valores->ValorIr = 0.00;
    $std->Servico->Valores->ValorCsll = 0.00;
    $std->Servico->Valores->IssRetido = 2;
    $std->Servico->Valores->ValorIss = 0.08;
    $std->Servico->Valores->ValorIssRetido = 0.00;
    $std->Servico->Valores->OutrasRetencoes = 0.00;
    $std->Servico->Valores->BaseCalculo = 20.00;
    $std->Servico->Valores->Aliquota = 1.00;
    $std->Servico->Valores->ValorLiquidoNfse = '0.20';
    $std->Servico->Valores->DescontoIncondicionado = 0.00;
    $std->Servico->Valores->DescontoCondicionado = 0.00;

    $std->ConstrucaoCivil = new \stdClass();
    $std->ConstrucaoCivil->CodigoObra = '1234';
    $std->ConstrucaoCivil->Art = '1234';

    $arps[] = new Rps($std);

    $lote = '123456';
    $response = $tools->recepcionarLoteRps($arps, $lote);

    //echo \NFePHP\Natal\Common\FakePretty::prettyPrint($response, '');
    header("Content-type: text/plain");
    echo $response;

} catch (\Exception $e) {
    echo $e->getMessage();
}