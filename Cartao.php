<?php

class Cartao {

    const VISA          = 'visa';
    const MASTERCARD    = 'mastercard';
    const DINERS        = 'diners';
    const DISCOVER      = 'discover';
    const ELO           = 'elo';
    const AMEX          = 'amex';
    const JCB           = 'jcb';
    const AURA          = 'aura';

    const CODIGO_NAO_INFORMADO  = 0;
    const CODIGO_INFORMADO      = 1;
    const CODIGO_INVALIDO       = 2;
    const CODIGO_NAO_EXISTENTE  = 9;

    /**
     * Token que deve ser utilizado em substituição aos dados do cartão para uma autorização 
     *  direta ou uma transação recorrente. Não é permitido o envio do token junto com os 
     *  dados do cartão na mesma transação
     * @var string $token  
     */
    private $token; 

    /**     
     * Número do cartão de crédito
     * @var string $cartao      
     */            
    private $cartao; 

    /**
     * A validade do cartão de cartão, pode ser no formato MM/AAAA ou AAAAMM 
     * @var string $validade    
     */           
    private $validade; 

    /**
     * Código se segurança (CVC) do cartão
     * @var string $codigo      
     */         
    private $codigo; 

    /**
     * Nome impresso no cartão (opcional)
     * @var string $nome        
     */           
    private $nome;  

    /**
     * Bandeira do cartão
     * @var string $bandeira    
     */        
    private $bandeira;  

    /**
     * Indicador do código de segurança
     * @var int $indicador      
     */
    private $indicador; 

    /**
     * Seis primeiros dígitos do cartão
     * @var string $bin         
     */
    private $bin;       

    /**
     * @param string $cartao - Número do cartão ou Token
     * @param string $validade - Validade do cartão 
     * @param string $codigo - Código de segurança (CVC) o cartão
     */
    function __construct($cartao, $validade = null, $codigo = null){
        if($validade AND $codigo){
            $this->setCartao($cartao);
            $this->setValidade($validade);
            $this->setCodigo($codigo);
        } else {
            $this->setToken($cartao);
            $bandeira = new Selecionar("clientes", array('coluna' => 'token', 'valor' => $this->getToken()));
            if($bandeira->getTotal()){
                $this->setBandeira($bandeira->getResultado()->bandeira);
                $this->setCartao($bandeira->getResultado()->numero);
            }
        }
    }

    /**
     * Armazena o token do cartão
     * @param string $token
     */
    public function setToken($token){
        if (!is_string($token) || strlen($token) > 100) {
            throw new \UnexpectedValueException('O token deve ser uma string com, no máximo, 100 caracteres');
        }
        $this->token = $token;
    }

    /**
     * Número do cartão, deve conter entre 13 e 19 caracteres e passar na validação do algoritmo de Luhn.
     * A partir desse parâmetro serão obtidos o BIN e a bandeira do cartão
     * @param string $cartao
     */
    public function setCartao($cartao){
        $cartao = preg_replace("/[^0-9]/", "", $cartao);
        if(!$this->validarCartao($cartao)){
            throw new \UnexpectedValueException('O número do cartão não é válido.');
        }
        if(!$bandeira = $this->obterBandeira($cartao)){
            throw new \UnexpectedValueException('Não foi possível identificar a bandeira do cartão.');
        }
        $this->setBandeira($bandeira);
        $this->bin    = substr($cartao, 0, 6);  
        $this->cartao = $cartao;
    }

    /** 
     * Nome da bandeira do cartão em minúsculo
     * @param string $bandeira
     */
    public function setBandeira($bandeira){
        switch($bandeira){
            case Cartao::VISA:
            case Cartao::MASTERCARD:
            case Cartao::DINERS:
            case Cartao::DISCOVER:
            case Cartao::ELO:
            case Cartao::AMEX:
            case Cartao::JCB:
            case Cartao::AURA:
                $this->bandeira = $bandeira;
                break;
            default: 
                throw new \UnexpectedValueException('O nome da bandeira deve ser uma string em minúsculo: visa, mastercard, diners, discover, elo, amex, jcb e aura');
        }
    }

    /**
     * Data de validade do cartão, formato: MM/AAAA ou AAAAMM
     * @param string $validade
     */
    private function setValidade($validade){
        if(strpos($validade, "/") !== false){
            $validade = explode("/", $validade);
            $mes = str_pad($validade[0], 2, "0", STR_PAD_LEFT);
            $ano = $validade[1]; 
            if (!is_numeric($ano) || strlen($ano) != 4) {
                throw new \UnexpectedValueException('O ano de expiração do cartão deve ser um número de 4 dígitos');
            }
            if (!is_numeric($mes) || $mes < 1 || $mes > 12) {
                throw new \UnexpectedValueException('O mês de expiração do cartão deve ser um número entre 1 e 12');
            }
            $this->validade = $ano.$mes;
        } else {
            $validade = preg_replace("/[^0-9]/", "", $validade);
            if(strlen($validade) != 6){
                throw new \UnexpectedValueException('Formato de vencimento inválido.');
            }
            $this->validade = $validade;
        }
    }

    /** 
     * Código de segurança (CVC) do cartão
     * @param integer $codigo
     */
    private function setCodigo($codigo){
        if(!isset($codigo)){
            $this->indicador = Cartao::CODIGO_NAO_INFORMADO;
        } else if(strlen($codigo) != 3 AND strlen($codigo) != 4){
            $this->indicador = Cartao::CODIGO_INVALIDO;
        } else {
            $this->indicador = Cartao::CODIGO_INFORMADO;
            $this->codigo    = $codigo;
        }
    }

    /**
     * Nome impresso no cartão
     * @param string $nome
     */
    public function setNome($nome){
        if (!is_string($nome) || strlen($nome) > 50) {
            throw new \UnexpectedValueException('O nome do portador deve ser uma string com, no máximo, 50 caracteres');
        }

        $this->nome = $nome;
    }

    /** 
     * Função que valida o número do cartão através do Algoritmo de Luhn
     * @link https://en.wikipedia.org/wiki/Luhn_algorithm
     * @param string $numero - Número do cartão, deve ter entre 13 e 19 caracteres numéricos
     * @return bool - TRUE caso o número seja válido, FALSE caso contrário
    */
    private function validarCartao($numero){
        $numero = preg_replace("/[^0-9]/", "", $numero); //remove caracteres não numéricos
        if(strlen($numero) < 13 || strlen($numero) > 19)
            return false;
        $soma = '';
        foreach(array_reverse(str_split($numero)) as $i => $n){ 
            $soma .= ($i % 2) ? $n * 2 : $n; 
        }
        return array_sum(str_split($soma)) % 10 == 0;
    }

    /** 
     * Função que procura a bandeira do cartão a partir do seu número
     * Para a criação das expressões foram utilizadas listas encontradas na internet, abaixo link com as listas
     *   @link https://gist.github.com/erikhenrique/5931368
     *   @link http://pt.stackoverflow.com/questions/3715/express%C3%A3o-regular-para-detectar-a-bandeira-do-cart%C3%A3o-de-cr%C3%A9dito#answer-16779
     *   @param string $numero - Número do cartão, deve ter entre 13 e 19 caracteres numéricos
     *   @return string - Retorna uma string com o nome da bandeira do cartão ou FALSE caso não encontre
     *   @version 1.0
     */
    private function obterBandeira($numero){
        $numero = preg_replace("/[^0-9]/", "", $numero); //remove caracteres não numéricos
        if(strlen($numero) < 13 || strlen($numero) > 19)
            return false;
        //O BIN do Elo é relativamente grande, por isso a separação em outra variável
        $elo_bin = implode("|", array(636368,438935,504175,451416,636297,506699,509048,509067,509049,509069,509050,09074,509068,509040,509045,509051,509046,509066,509047,509042,509052,509043,509064,509040));
        $expressoes = array(
            "elo"           => "/^((".$elo_bin."[0-9]{10})|(36297[0-9]{11})|(5067|4576|4011[0-9]{12}))/",
            "discover"      => "/^((6011[0-9]{12})|(622[0-9]{13})|(64|65[0-9]{14}))/",
            "diners"        => "/^((301|305[0-9]{11,13})|(36|38[0-9]{12,14}))/",
            "amex"          => "/^((34|37[0-9]{13}))/",
            "hipercard"     => "/^((38|60[0-9]{11,14,17}))/",
            "aura"          => "/^((50[0-9]{14}))/",
            "jcb"           => "/^((35[0-9]{14}))/",
            "mastercard"    => "/^((5[0-9]{15}))/",
            "visa"          => "/^((4[0-9]{12,15}))/"
        );
        foreach($expressoes as $bandeira => $expressao){
            if(preg_match($expressao, $numero)){
                return $bandeira;
            }
        }
        return false;
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
    public function getCartao(){
        return $this->cartao;
    }

    /**
     * @return string
     */
    public function getValidade(){
        return $this->validade;
    }

    /**
     * @return string
     */
    public function getCodigo(){
        return $this->codigo;
    }

    /**
     * @return string
     */
    public function getNome(){
        return $this->nome;
    }

    /**
     * @return string
     */
    public function getBandeira(){
        return $this->bandeira;
    }

    /**
     * @return integer
     */
    public function getIndicador(){
        return $this->indicador;
    }

    /**
     * @return string
     */
    public function getBin(){
        return $this->bin;
    }  

}
