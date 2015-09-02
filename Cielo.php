<?php

require_once("Array2XML.php");
require_once("Cartao.php");
require_once("Empresa.php");
require_once("Pedido.php");
require_once("RequisicaoAutorizacaoTid.php");
require_once("RequisicaoCancelamento.php");
require_once("RequisicaoCaptura.php");
require_once("RequisicaoConsulta.php");
require_once("RequisicaoToken.php");
require_once("RequisicaoTransacao.php");
require_once("Retorno.php");
require_once("TransacaoId.php");
require_once("Valor.php");
require_once("XML2Array.php");

/**
 * Gerencia todas as transações
 * @see https://www.cielo.com.br/wps/wcm/connect/c682298e-4518-4e2b-8945-cef23e04b5ec/Cielo-E-commerce-Manual-do-Desenvolvedor-WebService-PT-V2.5.4.pdf?MOD=AJPERES&CONVERT_TO=url&CACHEID=c682298e-4518-4e2b-8945-cef23e04b5ec
 * @see https://developercielo.github.io/Webservice-1.5/
 * @author David Deizepi <ddr94@hotmal.com>
 * @version 1.0
 */
class Cielo {
    
    const PRODUCAO          = 'https://ecommerce.cielo.com.br/servicos/ecommwsec.do';
    const DESENVOLVIMENTO   = 'https://qasecommerce.cielo.com.br/servicos/ecommwsec.do';

    const XMLNS  = "http://ecommerce.cbmp.com.br";
    const VERSAO = '1.2.1';

    /**
     * Tipo de requisição feita (requisicao-transacao, requisicao-token, etc)
     * @var string $requisicao
     */
    private $requisicao;

    /**
     * XML de retorno convertido em objeto Retorno
     * @var Retorno $retorno
     */
    private $retorno;

    /**
     * Array que será convertido em XML e enviado para a Cielo
     * @var array $array
     */
    private $array;

    /**
     * Iniciará o procedimento de envio de XML, convertendo o objeto para array
     * @return Retorno - resposta da Cielo
     */
    public function enviar(){
        $this->objetoParaVetor($this);
        return $this->vetorParaXml();
    }

    /**
     * Recebe a resposta da Cielo e converte para o objeto Retorno
     * @param string $resposta - XML de resposta da Cielo
     * @return Retorno - objeto Retorno com os dados da resposta
     */
    public function receber($resposta){
        $this->retorno = new Retorno($resposta);
        return $this->retorno;
    }

    /**
     * Converte o objeto instanciado para array
     * @param Cielo $objeto - Objeto instanciado
     */
    private function objetoParaVetor($objeto){

        if(!isset($this->requisicao)){
            $this->setRequisicao($objeto);
        }

        $reflection = new ReflectionClass($objeto);
        $campos = $reflection->getProperties();

        foreach($campos as $campo){
            $atributo = $campo->getName();
            $metodo   = "get".ucfirst($atributo);
            $valor    = $objeto->$metodo();
            if(is_object($valor)){
                $this->objetoParaVetor($valor);
            } else {
                $this->array[$atributo] = $valor;
            }
        }

    }

    /**
     * Converte o array gerado para o XML que será enviado para a Cielo
     * @return Retorno - Objeto gerado a partir da resposta da Cielo
     */
    private function vetorParaXml(){

        $array['@attributes'] = array(
            'xmlns'  => Cielo::XMLNS,
            'versao' => Cielo::VERSAO
        );
        if(isset($this->array['pedido']))
            $array['@attributes']['id'] = $this->array['pedido'];
        else 
            $array['@attributes']['id'] = uniqid(); //Toda requisição deve ter um ID

        if(isset($this->array['tid']))
            $array['tid'] = $this->array['tid'];

        $array['dados-ec']['numero'] = Empresa::NUMERO;
        $array['dados-ec']['chave']  = Empresa::CHAVE;

        if(isset($this->array['cartao'])){
            $array['dados-portador']['numero']           = $this->array['cartao'];
            $array['dados-portador']['validade']         = $this->array['validade'];
            $array['dados-portador']['indicador']        = $this->array['indicador'];
            $array['dados-portador']['codigo-seguranca'] = $this->array['codigo'];
        }

        if(isset($this->array['token'])){
            unset($array['dados-portador']); //Se a transação por por token, não são necessários outros valores
            $array['dados-portador']['token'] = $this->array['token'];
        }

        if(isset($this->array['valor']) AND !isset($this->array['pedido']))
            $array['valor'] = $this->array['valor'];

        if(isset($this->array['pedido'])){
            $array['dados-pedido']['numero']          = $this->array['pedido'];
            $array['dados-pedido']['valor']           = $this->array['valor'];
            $array['dados-pedido']['moeda']           = $this->array['moeda'];
            $array['dados-pedido']['data-hora']       = $this->array['data_hora'];
            $array['dados-pedido']['descricao']       = $this->array['descricao'];
            $array['dados-pedido']['idioma']          = $this->array['idioma'];
            $array['dados-pedido']['soft-descriptor'] = $this->array['soft_descriptor'];
            //$array['dados-pedido']['taxa-embarque']   = $this->array['taxa_embarque'];
        }

        if(isset($this->array['produto'])){
            $array['forma-pagamento']['bandeira'] = $this->array['bandeira'];
            $array['forma-pagamento']['produto']  = $this->array['produto'];
            $array['forma-pagamento']['parcelas'] = $this->array['parcelas'];
        }

        if(isset($this->array['url_retorno']))
            $array['url-retorno'] = $this->array['url_retorno'];

        if(isset($this->array['autorizar']))
            $array['autorizar'] = $this->array['autorizar'];

        if(isset($this->array['capturar']))
            $array['capturar'] = $this->array['capturar'];

        if(isset($this->array['campo_livre']))
            $array['campo-livre'] = $this->array['campo_livre'];

        if(isset($this->array['bin']) AND isset($this->array['dados-pedido']))
            $array['bin'] = $this->array['bin'];
        
        if(isset($this->array['gerar_token']))
            $array['gerar-token'] = $this->array['gerar_token'];

        if(isset($this->array['avs']))
            $array['avs'] = $this->array['avs'];

        unset($this->array);
        $xml = Array2XML::createXML($this->getRequisicao(), $array);
        $enviar = $xml->saveXML();

        return $this->enviarXml($enviar);

    }

    /**
     * Gera o tipo de requisição que será enviada para a Cielo a partir do objeto instanciado
     * @param Cielo $objeto - Objeto instanciado
     */
    private function setRequisicao($objeto){
        $nome = get_class($objeto);
        $this->requisicao = strtolower(substr(preg_replace("/[^a-z]/", '-${0}', $nome), 1));
    }

    /**
     * @return string
     */
    private function getRequisicao(){
        return $this->requisicao;
    }

    /**
     * Envia o XML gerado para a Cielo
     * @param string $xml - XML que será enviado para a Cielo
     * @return Retorno - objeto gerado a partir da resposta da Cielo
     */ 
    public function enviarXml($xml){

        $headers = ['Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                    'Accept: text/xml; charset=utf-8',
                    'User-Agent: PHP-SDK: 1.0'];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, Cielo::DESENVOLVIMENTO);
        curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['mensagem' => $xml]));

        $resposta = curl_exec($curl);

        if(!$resposta){
            throw new UnexpectedValueException("Não foi possível se conectar ao servidor da Cielo. Motivo: ".curl_errno($curl)." | ".curl_error($curl));     
        }

        curl_close($curl);

        return $this->receber($resposta);

    }

    /**
     * Obtém a data e hora atual
     * @return string
     */
    public function getDataHora(){
        return date("Y-m-d\TH:i:s", time());
    }

}
