<?php

class Retorno {

    /**
     * TID (Transaction Identifier): Identificada unicamente uma transação Cielo
     * @var string @tid
     */
    private $tid;

    /**
     * Número do pedido da loja passado na compra
     * @var string $pedido
     */
    private $numero;

    /**
     * Valor cobrado do cliente
     * @var integer $valor
     */
    private $valor;

    /**
     * Bandeira do cartão
     * @var string $bandeira    
     */ 
    private $bandeira;

    /**
     * Forma de pagamento da compra:
     *  1 – Crédito à Vista.
     *  2 – Parcelado loja.
     *  A – Débito.
     * @var string $produto
     */
    private $produto;

    /**
     * Número de parcelas da compra 
     * @var integer $parcelas
     */
    private $parcelas;

    /**
     * É a informação base para a loja controlar a transação:
     *      Transação Criada            = 0
     *      Transação em Andamento      = 1
     *      Transação Autenticada       = 2
     *      Transação não Autenticada   = 3
     *      Transação Autorizada        = 4
     *      Transação não Autorizada    = 5
     *      Transação Capturada         = 6
     *      Transação Cancelada         = 9
     *      Transação em Autenticação   = 10
     *      Transação em Cancelamento   = 12
     * @var integer $status
     */
    private $status;

    /**
     * Código do processamento da autenticação
     * @var integer $autenticacao
     */
    private $autenticacao;

    /**
     * Nível de segurança
     * @var integer $eci
     */
    private $eci;

    /**
     * Código do processamento da autorização
     * @var integer $autorizacao
     */
    private $autorizacao;

    /**
     * Código de resposta LR. Exceto os códigos AA, AC e GA, todos os outros são gerados pelos emissores/bandeiras
     * @var integer $lr
     */
    private $lr;

    /**
     * Código ARP
     * @var integer @arp
     */
    private $arp;

    /**
     * Código NSU
     * @var integer $nsu
     */
    private $nsu;

    /**
     * Token que deve ser utilizado em substituição aos dados do cartão para uma autorização direta ou uma 
     * transação recorrente. Não é permitido o envio do token junto com os dados do cartão na mesma transação.
     * @var string $token
     */
    private $token;

    /**
     * Número do cartão truncado, com os seis primeiros dígitos e os quatro últimos dígitos
     * @var string $numero_cartao
     */
    private $numero_cartao;

    /**
     * Código de resposta da captura
     * @var integer $capturada
     */
    private $capturada;

    /**
     * Valor capturado
     * @var integer $valor_capturada
     */
    private $valor_capturada;

    /**
     * Código de resposta do cancelamento
     * @var integer $cancelamento
     */
    private $cancelamento;

    /**
     * Valor cancelado
     * @var integer $valor_cancelamento
     */
    private $valor_cancelamento;

    /**
     * Código de erro
     * @var integer $codigo
     */
    private $codigo;

    /**
     * Mensagem de erro
     * @var string $mensagem
     */
    private $mensagem;

    /**
     * O XML de resposta será convertido em array
     * @var array $array
     */
    private $array;

    /**
     * Tratará todos os retornos possíveis do Webservice, convertendo o resultado em array e armazenando
     *  os valores específicos nos atributos desse objeto.
     * @param string $xml - XML de resposta da Cielo ou endereço do arquivo de XML de resposta
     */
    function __construct($xml){
        if(substr($xml, 0, 5) != '<?xml'){
            $xml = file_get_contents($xml);
        }
        $array = XML2Array::createArray($xml);
        foreach($array as $indice => $valor){
            $this->array = $valor;
            switch($indice){
                case 'transacao':
                    $this->retornoTransacao();
                    break;
                case 'retorno-token':
                    $this->retornoToken();
                    break;
                case 'erro':
                    $this->retornoErro();
                    break;
                default:
                    throw new \UnexpectedValueException('Retorno inesperado.');
            }
        }
        unset($this->array);
    }

    /**
     * Caso o XML de resposta seja <transacao>
     */
    private function retornoTransacao(){
        $this->setTid();
        $this->setDadosPedido();
        $this->setFormaPagamento();
        $this->setStatus();
        $this->setAutenticacao();
        $this->setAutorizacao();
        $this->setToken();
        $this->setCapturada();
        $this->setCancelamento();
    }

    /**
     * Caso o XML de resposta seja <retorno-token>
     */
    private function retornoToken(){
        $this->setToken();
    }

    /**
     * Caso do XML de resposta seja <erro>
     */
    private function retornoErro(){
        $this->setErro();
    }

    /**
     * Armazena o TID retornado pelo Webservice da Cielo
     */
    private function setTid(){
        $this->tid = $this->array['tid'];
    }

    /**
     * Armazena todos os dados do pedido, presentes na tag <dados-pedido> do XML de resposta
     */
    private function setDadosPedido(){
        $dados = @$this->array['dados-pedido'];
        if(isset($dados)){
            $this->numero = $dados['numero'];
            $this->valor  = $dados['valor'];
        }
    }

    /**
     * Armazena todos os dados referentes ao pagamento, presentes na tag <forma-pagamento>
     */
    private function setFormaPagamento(){
        $forma_pagamento = @$this->array['forma-pagamento'];
        if(isset($forma_pagamento)){
            $this->bandeira   = $forma_pagamento['bandeira'];
            $this->produto    = $forma_pagamento['produto'];
            $this->parcelas   = $forma_pagamento['parcelas'];
        }
    }

    /**
     * Armazena o status da transacao
     */
    private function setStatus(){
        $this->status = @$this->array['status'];
    }

    /**
     * Armazena o código de resposta da autenticação e o nível se segurança (ECI)
     */
    private function setAutenticacao(){
        $autenticacao = @$this->array['autenticacao'];
        if(isset($autenticacao)){
            $this->autenticacao   = $autenticacao['codigo'];
            $this->eci            = $autenticacao['eci'];
        }
    }

    /**
     * Armazena todos os códigos de resposta da autorização, presentes na tag <autorizacao>
     */
    private function setAutorizacao(){
        $autorizacao = @$this->array['autorizacao'];
        if(isset($autorizacao)){
            $this->autorizacao    = $autorizacao['codigo'];
            $this->lr             = $autorizacao['lr'];
            $this->arp            = $autorizacao['arp'];
            $this->nsu            = $autorizacao['nsu'];
        }
    }

    /**
     * Armazena o token e o número do cartão truncado
     */
    private function setToken(){
        $token = @$this->array['token']['dados-token'];
        if(isset($token)){
            $this->token          = $token['codigo-token'];
            $this->numero_cartao  = $token['numero-cartao-truncado'];
        }
    }

    /**
     * Armazena o código de resposta da tentativa de captura e o valor capturado
     */
    private function setCapturada(){
        $captura = @$this->array['captura'];
        if(isset($captura)){
            $this->capturada        = $captura['codigo'];
            $this->valor_capturada  = substr_replace($captura['valor'], '.', -2, 0);
        }
    }

    /**
     * Armazena o código de resposta do cancelamento e o valor cancelado
     */
    private function setCancelamento(){
        $cancelamento = @$this->array['cancelamentos']['cancelamento'];
        if(isset($cancelamento)){
            $this->cancelamento         = $cancelamento['codigo'];
            $this->valor_cancelamento   = substr_replace($cancelamento['valor'], '.', -2, 0);
        }
    }

    /**
     * Armazena o código e a mensagem de erro
     */
    private function setErro(){
        $this->codigo     = $this->array['codigo'];
        $this->mensagem   = $this->array['mensagem'];
        if(isset($this->mensagem['@cdata'])){
            $this->mensagem = $this->mensagem['@cdata'];
        }
        throw new \UnexpectedValueException($this->codigo.": ".$this->mensagem);
    }

    /**
     * @return string
     */
    public function getTid(){
        return $this->tid;
    }

    /**
     * @return string
     */
    public function getNumero(){
        return $this->numero;
    }

    /**
     * @return integer
     */
    public function getValor(){
        return $this->valor;
    }

    /**
     * @return string
     */
    public function getBandeira(){
        return $this->bandeira;
    }

    /**
     * @return string
     */
    public function getProduto(){
        return $this->produto;
    }

    /**
     * @return integer
     */
    public function getParcelas(){
        return $this->parcelas;
    }

    /**
     * @return integer
     */
    public function getStatus(){
        return $this->status;
    }

    /**
     * @return integer
     */
    public function getAutenticacao(){
        return $this->autenticacao;
    }

    /**
     * @return integer
     */
    public function getEci(){
        return $this->eci;
    }

    /**
     * @return integer
     */
    public function getAutorizacao(){
        return $this->autorizacao;
    }

    /**
     * @return string
     */
    public function getLr(){
        return $this->lr;
    }

    /**
     * @return integer
     */
    public function getArp(){
        return $this->arp;
    }

    /**
     * @return integer
     */
    public function getNsu(){
        return $this->nsu;
    }

    /**
     * @return string
     */
    public function getToken(){
        return $this->token;
    }

    /**
     * @return string
     */
    public function getNumero_cartao(){
        return $this->numero_cartao;
    }

    /**
     * @return integer
     */
    public function getCapturada(){
        return $this->capturada;
    }

    /**
     * @return integer
     */
    public function getValor_capturada(){
        return $this->valor_capturada;
    }

    /**
     * @return integer
     */
    public function getCancelamento(){
        return $this->cancelamento;
    }

    /**
     * @return integer
     */
    public function getValor_cancelamento(){
        return $this->valor_cancelamento;
    }

    /**
     * @return integer
     */
    public function getCodigo(){
        return $this->codigo;
    }

    /**
     * @return string
     */
    public function getMensagem(){
        return $this->mensagem;
    }

}
