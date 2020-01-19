<?php

namespace NFePHP\NFSeNatal;

/**
 * Class for comunications with NFSe webserver in Nacional Standard
 *
 * @category  NFePHP
 * @package   NFePHP\NFSeNatal
 * @copyright NFePHP Copyright (c) 2020
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Cleiton Perin <cperin20 at gmail dot com>
 * @link      http://github.com/nfephp-org/sped-nfse-natal for the canonical source repository
 */

use NFePHP\Common\Certificate;
use NFePHP\Common\Validator;
use NFePHP\NFSeNatal\Common\Tools as BaseTools;

class Tools extends BaseTools
{
    const ERRO_EMISSAO = 1;
    const SERVICO_NAO_CONCLUIDO = 2;

    protected $xsdpath;

    public function __construct($config, Certificate $cert)
    {
        parent::__construct($config, $cert);
        $path = realpath(
            __DIR__ . '/../storage/schemes'
        );
        $this->xsdpath = $path . '/nfse.xsd';
    }

    /**
     * Solicita o cancelamento de NFSe (SINCRONO)
     * @param integer $numero
     * @param integer $codigo
     * @return string
     */
    public function cancelarNfse($numero, $codigo = self::ERRO_EMISSAO)
    {
        $operation = 'CancelarNfse';
        $pedido = "<Pedido xmlns=\"http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd\">"
            . "<InfPedidoCancelamento>"
            . "<IdentificacaoNfse>"
            . "<Numero>$numero</Numero>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "<CodigoMunicipio>" . $this->config->cmun . "</CodigoMunicipio>"
            . "</IdentificacaoNfse>"
            . "<CodigoCancelamento>$codigo</CodigoCancelamento>"
            . "</InfPedidoCancelamento>"
            . "</Pedido>";

        $signed = $this->sign($pedido, 'InfPedidoCancelamento', 'Id');
        $content = "<CancelarNfseEnvio>"
            . $signed
            . "</CancelarNfseEnvio>";
        //Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta Lote RPS (SINCRONO) após envio com recepcionarLoteRps() (ASSINCRONO)
     * complemento do processo de envio assincono.
     * Que deve ser usado quando temos mais de um RPS sendo enviado
     * por vez.
     * @param string $protocolo
     * @return string
     */
    public function consultarLoteRps($protocolo)
    {
        $operation = 'ConsultarLoteRps';
        $content = "<ConsultarLoteRpsEnvio xmlns=\"http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd\">"
            . $this->prestador
            . "<Protocolo>$protocolo</Protocolo>"
            . "</ConsultarLoteRpsEnvio>";
        //Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta NFSe emitidas em um periodo e por tomador (SINCRONO)
     * @param string $dini
     * @param string $dfim
     * @param string $tomadorCnpj
     * @param string $tomadorCpf
     * @param string $tomadorIM
     * @return string
     */
    public function consultarNfse($dini, $dfim, $tomadorCnpj = null, $tomadorCpf = null, $tomadorIM = null)
    {
        $operation = 'ConsultarNfse';
        $content = "<ConsultarNfseEnvio xmlns=\"http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd\">"
            . $this->prestador
            . "<PeriodoEmissao>"
            . "<DataInicial>$dini</DataInicial>"
            . "<DataFinal>$dfim</DataFinal>"
            . "</PeriodoEmissao>";

        if ($tomadorCnpj || $tomadorCpf) {
            $content .= "<Tomador>"
                . "<CpfCnpj>";
            if (isset($tomadorCnpj)) {
                $content .= "<Cnpj>$tomadorCnpj</Cnpj>";
            } else {
                $content .= "<Cpf>$tomadorCpf</Cpf>";
            }
            $content .= "</CpfCnpj>";
            if (isset($tomadorIM)) {
                $content .= "<InscricaoMunicipal>$tomadorIM</InscricaoMunicipal>";
            }
            $content .= "</Tomador>";
        }
        $content .= "</ConsultarNfseEnvio>";
        //Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Consulta NFSe por RPS (SINCRONO)
     * @param integer $numero
     * @param string $serie
     * @param integer $tipo
     * @return string
     */
    public function consultarNfsePorRps($numero, $serie, $tipo)
    {
        $operation = "ConsultarNfsePorRps";
        $content = "<ConsultarNfseRpsEnvio xmlns=\"http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd\">"
            . "<IdentificacaoRps>"
            . "<Numero>$numero</Numero>"
            . "<Serie>$serie</Serie>"
            . "<Tipo>$tipo</Tipo>"
            . "</IdentificacaoRps>"
            . $this->prestador
            . "</ConsultarNfseRpsEnvio>";
        //Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

    /**
     * Envia LOTE de RPS para emissão de NFSe (ASSINCRONO)
     * @param array $arps Array contendo de 1 a 50 RPS::class
     * @param string $lote Número do lote de envio
     * @return string
     * @throws \Exception
     */
    public function recepcionarLoteRps($arps, $lote)
    {
        $operation = 'RecepcionarLoteRps';
        $no_of_rps_in_lot = count($arps);
        if ($no_of_rps_in_lot > 50) {
            throw new \Exception('O limite é de 50 RPS por lote enviado.');
        }
        $content = '';
        foreach ($arps as $rps) {
            //$xml = $this->putPrestadorInRps($rps);

            $xml = '<Rps><InfRps Id="rps:14494652"><IdentificacaoRps><Numero>14494</Numero><Serie>652</Serie><Tipo>1</Tipo></IdentificacaoRps><DataEmissao>2012-07-11T00:00:00</DataEmissao><NaturezaOperacao>1</NaturezaOperacao><OptanteSimplesNacional>2</OptanteSimplesNacional><IncentivadorCultural>2</IncentivadorCultural><Status>1</Status><Servico><Valores><ValorServicos>4</ValorServicos><ValorPis>0</ValorPis><ValorCofins>0</ValorCofins><ValorInss>0</ValorInss><ValorIr>0</ValorIr><ValorCsll>0</ValorCsll><IssRetido>2</IssRetido><ValorIss>0.08</ValorIss><ValorIssRetido>0</ValorIssRetido><OutrasRetencoes>0</OutrasRetencoes><BaseCalculo>4</BaseCalculo><Aliquota>0.02</Aliquota><ValorLiquidoNfse>4</ValorLiquidoNfse><DescontoIncondicionado>0</DescontoIncondicionado><DescontoCondicionado>0</DescontoCondicionado></Valores><ItemListaServico>10.05</ItemListaServico><CodigoCnae>6821801</CodigoCnae><Discriminacao>ASD</Discriminacao><CodigoMunicipio>3509502</CodigoMunicipio></Servico><Prestador><Cnpj>09267632000190</Cnpj><InscricaoMunicipal>1358286</InscricaoMunicipal></Prestador><Tomador><IdentificacaoTomador><CpfCnpj><Cpf>05135872922</Cpf></CpfCnpj></IdentificacaoTomador><RazaoSocial>Tomador CAMPINAS</RazaoSocial><Endereco><Endereco>endereco</Endereco><Numero>1</Numero><Complemento>complemento</Complemento><Bairro>bairro</Bairro><CodigoMunicipio>3509502</CodigoMunicipio><Uf>SP</Uf><Cep>89000000</Cep></Endereco><Contato><Telefone>04734515555</Telefone><Email>teste@123.com</Email></Contato></Tomador></InfRps></Rps>';
            //$xml = str_replace(['\n', '\r'], '', $xml);
            $xmlsigned = $this->sign($xml, 'InfRps', 'Id');
            //header("Content-type: text/xml");
            //echo $xmlsigned;
            //exit;
            $content .= $xmlsigned;
        }
        $contentmsg = "<EnviarLoteRpsEnvio xmlns=\"http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd\">"
            . "<LoteRps Id=\"lote:$lote\">"
            . "<NumeroLote>$lote</NumeroLote>"
            . "<Cnpj>" . $this->config->cnpj . "</Cnpj>"
            . "<InscricaoMunicipal>" . $this->config->im . "</InscricaoMunicipal>"
            . "<QuantidadeRps>$no_of_rps_in_lot</QuantidadeRps>"
            . "<ListaRps>"
            . $content
            . "</ListaRps>"
            . "</LoteRps>"
            . "</EnviarLoteRpsEnvio>";
        $content = $this->sign($contentmsg, 'LoteRps', 'Id');
        //header("Content-type: text/xml");
        //echo $content;
        //exit;
        //Validator::isValid($content, $this->xsdpath);
        return $this->send($content, $operation);
    }

}
