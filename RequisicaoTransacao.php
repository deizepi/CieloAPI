<?php

/**
 * Efetua um pedido de compra do portador do cartão na Cielo
 */
class RequisicaoTransacao extends Cielo {

    const AUTENTICAR                = 0;
    const AUTORIZACAO_AUTENTICADA   = 1;
    const AUTORIZACAO               = 2;
    const AUTORIZACAO_DIRETA        = 3;
    const AUTORIZACAO_RECORRENTE    = 4;

    const CREDITO_VISTA     = 1;
    const CREDITO_PARCELADO = 2;
    const DEBITO            = 'A';

    /**
     * Todos os dados do cartão (número, validade, cód. segurança, nome, bandeira, BIN ou token)
     * @var Cartao $cartao
     */
    private $cartao;    

    /**
     * Todos os dados do pedido e informações comerciais (Nº pedido, valor, data, idioma, moeda, descrição)
     * @var Pedido $pedido
     */
    private $pedido;    

    /**
     * Forma de pagamento, sendo válidos apenas os valores:
     *  1 – Crédito à Vista.
     *  2 – Parcelado loja.
     *  A – Débito.
     * @var string $produto
     */
    private $produto;   

    /**
     * Número de parcelas da compras 
     *  NOTA 1: O valor por parcela não pode ser inferior à R$ 5,00
     *  NOTA 2: Para DEBITO e CREDITO_VISTA o número de parcelas deve ser 1
     * @var integer $parcelas
     */
    private $parcelas;  

    /**
     * URL da página de retorno. É para essa página que a Cielo vai direcionar o browser ao fim da autenticação ou da
     *  autorização. Não é obrigatório apenas para autorização direta, porém o campo dever ser inserido como “null”
     * @var string $url_retorno
     */
    private $url_retorno;

    /**
     * Autenticação: processo para assegurar que o comprador é realmente aquele quem diz ser
     * (portador legítimo), geralmente ocorre no banco emissor com uso de um token digital ou
     * cartão com chaves de segurança
     * A autenticação é obrigatória para transações de débito e opcional para o crédito.
     *  0 – Não autorizar (somente autenticar).
     *  1 – Autorizar somente se autenticada.
     *  2 – Autorizar autenticada e não autenticada.
     *  3 – Autorizar sem passar por autenticação (somente para crédito) – também conhecida como “Autorização Direta”.
     *      Obs.: Para Diners, Discover, Elo, Amex, Aura e JCB o valor será sempre “3”, pois estas bandeiras não possuem
     *      programa de autenticação.
     *  4 – Transação Recorrente.
     * @var integer $autorizar
     */
    private $autorizar      = RequisicaoTransacao::AUTORIZACAO_DIRETA;  

    /**
     * Define se a transação será automaticamente capturada caso seja autorizada. Recebe "true" ou "false"
     * @var bool $capturar
     */
    private $capturar       = true; 

    /**
     *  Define se a transação atual deve gerar um token associado ao cartão. Recebe "true" ou "false"
     * @var bool $gerar_token
     */
    private $gerar_token    = true;

    /**
     * Campo livre disponível para o Estabelecimento
     * @var string $campo_livre
     */
    private $campo_livre; 

    /**
     * String contendo um bloco XML, encapsulado pelo CDATA, contendo as informações necessárias para realizar a
     *  consulta ao serviço. (endereço, complemento, número, bairro, CEP)
     * @var array $avs
     */
    private $avs;

    /**
     * @param string $pedido - Número do pedido
     * @param string $valor - Valor do pedido
     * @param string $produto - Forma de pagamento (1 = CREDITO A VISTA; 2 = CREDITO PARCELADO; A = DEBITO)
     * @param string $parcelas - Numero de parcelas (1 para débito ou crédito a vista)
     * @param string $cartao - Número do cartão ou Token
     * @param string $validade - Validade do cartão (ou null caso seja via token)
     * @param string $codigo - Código de segurança do cartão (ou null caso seja via token)
     */
    function __construct($pedido, $valor, $produto, $parcelas, $cartao, $validade = null, $codigo = null){
        $this->cartao = new Cartao($cartao, $validade, $codigo);
        $this->pedido = new Pedido($pedido, $valor);
        $this->setFormaPagamento($produto);
        $this->setParcelas($parcelas);
    }
    
    /**
     * Armazena a forma de pagamento
     *  NOTA 1: Somente Visa e Mastercard possuem a função Débito ativa
     *  NOTA 2: Discover não aceita crédito parcelado
     * @param string $produto - Forma de pagamento (1 = CREDITO A VISTA; 2 = CREDITO PARCELADO; A = DEBITO)
     */
    public function setFormaPagamento($produto){
        $this->setUrlRetorno();
        switch($produto){
            case RequisicaoTransacao::DEBITO:
                if(!in_array($this->cartao->getBandeira(), array('visa', 'mastercard'))){
                    throw new \UnexpectedValueException('Somente as bandeiras VISA e MASTERCARD aceitam débito.');
                }
            case RequisicaoTransacao::CREDITO_PARCELADO:
                if($this->cartao->getBandeira() == 'discover'){
                    throw new \UnexpectedValueException('A bandeira DISCOVER não aceita crédito parcelado.');
                }
            case RequisicaoTransacao::CREDITO_VISTA:
                $this->produto = $produto;
                break;
            default:
                throw new \UnexpectedValueException('A forma de pagamento não é válida.');
        }
    }

    /**
     * Armazena a quantidade de parcelas
     *  NOTA 1: O número de parcelas para débito e crédito a vista deve ser sempre 1
     *  NOTA 2: O número de parcelas não pode ter mais de 2 casas decimais (> 99)
     *  NOTA 3: O valor por parcela não pode ser inferior a 5 reais
     * @param $parcelas - Número de parcelas da compra (número inteiro)
     */
    private function setParcelas($parcelas){
        $parcelas = preg_replace("/[^0-9]/", "", $parcelas);
        $vista = array(RequisicaoTransacao::CREDITO_VISTA, RequisicaoTransacao::DEBITO);
        if (in_array($this->produto, $vista) && $parcelas != 1) {
            throw new \UnexpectedValueException('Para crédito à vista ou débito, o número de parcelas deve ser 1.');
        }

        if ($parcelas < 1 || strlen($parcelas) > 2) {
            throw new \UnexpectedValueException('O número de parcelas deve ser maior ou igual a 1 e deve ter no máximo 2 dígitos');
        }

        $valor = substr_replace($this->pedido->getValor(), '.', -2, 0);
        if(($valor / $parcelas) < 5){
            throw new \UnexpectedValueException('O valor das parcelas não pode ser inferior a R$ 5,00.');
        }
        $this->parcelas = $parcelas;
    }

    /**
     * Armazena a URL de retorno, caso não seja passada por parâmetro uma URL será criada a partir do Nº pedido
     * @param string $url_retorno
     */
    private function setUrlRetorno($url_retorno = null){
        if(isset($url_retorno)){
            if (!filter_var($url_retorno, FILTER_VALIDATE_URL)) {
                throw new \UnexpectedValueException('URL de retorno inválida');
            }
            $this->url_retorno = $url_retorno;
        } else {
            $retorno = substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], "/") + 1);
            $arquivo = "retorno.php?pedido=".$this->pedido->getPedido();
            $url_retorno = "http://".$_SERVER['SERVER_NAME'].$retorno.$arquivo;
            $this->url_retorno = $url_retorno;
        }
    }

    /**
     * Armazena o tipo de autorização que será usada no pagamento.
     * @param integer $autorizar
     */
    private function setAutorizar($autorizar){
        switch ($autorizar) {
            case RequisicaoTransacao::AUTENTICAR:
            case RequisicaoTransacao::AUTORIZACAO_AUTENTICADA:
            case RequisicaoTransacao::AUTORIZACAO:
            case RequisicaoTransacao::AUTORIZACAO_DIRETA:
            case RequisicaoTransacao::AUTORIZACAO_RECORRENTE:
                $this->autorizar = $autorizar;
                break;
            default:
                throw new \UnexpectedValueException('Indicador de autorização inválido');
        }
    }

    /**
     * Armazena o valor da captura automática
     * @param bool $capturar
     */
    public function setCapturar($capturar){
        if (!is_bool($capturar)) {
            throw new \UnexpectedValueException('Indicador de captura deve ser um booleano');
        }

        $this->capturar = $capturar;
    }

    /**
     * Armazena um valor para o campo livre do estabelecimento
     * @param string $campo_livre
     */
    public function setCampoLivre($campo_livre){
        if (strlen($campo_livre) > 128) {
            throw new \UnexpectedValueException('O campo livre deve ter, no máximo, 128 caracteres');
        }

        $this->campo_livre = $campo_livre;
    }

    /**
     * Armazena o valor booleano do gerar token
     * @param bool $gerar_token
     */
    public function setGerarToken($gerar_token){
        if (!is_bool($gerar_token)) {
            throw new \UnexpectedValueException('O campo generate-token deve ser um booleano');
        }

        $this->gerar_token = $gerar_token;

    }

    /**
     * Armezena o AVS (Address Verification Service) que confronta o endereço fornecido pelo portador com as 
     *  informações armazenadas nos computadores dos emissores.
     * @param $cep - CEP do endereço no formato 00000-000
     * @param $endereco - string contendo o endereço completo
     * @param $numero - Número da casa
     * @param $complemento - Complemento do endereço
     * @param $bairro - Bairro do endereço
     */
    public function setAVS($cep, $endereco, $numero, $complemento, $bairro){
        if(!preg_match("/[0-9]{5}-[0-9]{3}/", $cep)){
            throw new Exception("O CEP informado não é válido, deve estar no formato: 00000-000");
        }
        if(strlen($endereco) < 3){
            throw new Exception("O endereço deve possuir ao menos 3 caracteres.");
        }
        if($numero < 0){
            throw new Exception("O número informado não é válido, o número deve ser maior que zero.");
        }
        if(strlen($bairro) < 3){
            throw new Exception("O bairro deve possuir ao menos 3 caracteres.");
        }

        $this->avs['@cdata'] = '
            <dados-avs>
                <endereco>'.$endereco.'</endereco>
                <complemento>'.$complemento.'</complemento>
                <numero>'.$numero.'</numero>
                <bairro>'.$bairro.'</bairro>
                <cep>'.$cep.'</cep>
            </dados-avs>
        ';

    }

    /** 
     * @return Cartao 
     */
    public function getCartao(){
        return $this->cartao;
    }

    /** 
     * @return Pedido 
     */
    public function getPedido(){
        return $this->pedido;
    }

    /** 
     * @return valor do pedido sem pontuação: 5,00 retornará 500.
     */
    public function getValor(){
        return $this->valor;
    }

    /** 
     * @return forma de pagamento da compra 
     */
    public function getProduto(){
        return $this->produto;
    }

    /** 
     * @return quantidade de parcelas da 
     */
    public function getParcelas(){
        return $this->parcelas;
    }

    /** 
     * @return url de retorno 
     */
    public function getUrl_retorno(){
        return $this->url_retorno;
    }

    /** 
     * @return autorizar  
     */
    public function getAutorizar(){
        return $this->autorizar;
    }

    /** 
     * @return bool capturar 
     */
    public function getCapturar(){
        return $this->capturar;
    }

    /** 
     * @return bool gerar token 
     */
    public function getGerar_token(){
        return $this->gerar_token;
    }

    /** 
     * @deprecated Quando passado esse elemento, a Cielo devolve um erro no XML 
     * @return Taxa de embarque
     */
    public function getTaxa_embarque(){
        return $this->taxa_embarque;
    }

    /**
     * @return campo livre
     */
    public function getCampo_livre(){
        return $this->campo_livre;
    }

    /** 
     * @return CData AVS 
     */
    public function getAvs(){
        return $this->avs;
    }

}
