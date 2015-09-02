<?php

class Pedido {

    /**
     * Número do pedido da loja. Recomenda-se que seja um valor único por pedido
     * @var string $pedido
     */
    private $pedido;    

    /**
     * Valor a ser cobrado pelo pedido (já deve incluir valores de frete, embrulho, custos extras, taxa de embarque,
     * etc). Esse valor é o que será debitado do consumidor
     *  NOTA: O valor não deve possuir pontuação: R$ 1.524,20 deve ser 152420
     * @var integer $valor
     */
    private $valor;     

    /**
     *  data/hora deverá seguir o formato: aaaa-MM-ddTHH24:mm:ss. Exemplo: 2011-12-21T11:32:45
     * @var string $data_hora
     */ 
    private $data_hora;

    /**
     * Código numérico da moeda na norma ISO 4217. Para o Real, o código é 986
     * @var integer $moeda
     */
    private $moeda = 986;

    /**
     * Descrição do pedido, máximo de 1024 caracteres.
     * @var string $descricao
     */
    private $descricao = "";

    /**
     * Idioma do pedido: PT (português), EN (inglês) ou ES (espanhol). Com base nessa informação é definida a
     * língua a ser utilizada nas telas da Cielo. Caso não seja enviado, o sistema assumirá “PT”
     * @var string $idioma
     */
    private $idioma = "PT";

    /**
     * Texto de até 13 caracteres que será exibido na fatura do portador, após o nome do Estabelecimento Comercial
     * @var string $soft_descriptor
     */
    private $soft_descriptor;

    /**
     * Montante do valor da autorização que deve ser destinado à taxa de embarque
     * @deprecated Por algum motivo o Webservice da Cielo não está reconhecendo esse campo
     * @var integer $taxa_embarque
     */
    private $taxa_embarque;

    /**
     * @param string $pedido - Número do pedido da loja
     * @param integer $valor - Valor total do pedido
     */
    function __construct($pedido, $valor){
        $this->setPedido($pedido);
        $this->valor = new Valor($valor);
    }

    /**
     * Armazena o número do pedido, se este possuir um valor válido
     * @param string $pedido
     */
    private function setPedido($pedido){
        if (strlen($pedido) < 1 || strlen($pedido) > 20) {
            throw new \UnexpectedValueException('O número do pedido deve ter entre 1 e 20 caracteres');
        }
        $this->pedido = $pedido;
    }

    /**
     * Método acessado apenas internamente, responsável por gerar uma data atual válida
     */
    private function setData_hora(){
        $this->data_hora = date("Y-m-d\TH:i:s", time());
    }

    /**
     * Caso a transação não seja em real, armazena o novo valor da moeda
     * @param integer @moeda
     */
    public function setMoeda($moeda){
        if (!is_int($moeda)) {
            throw new \UnexpectedValueException('A moeda deve ser informada utilizando o código ISO 4217');
        }
        $this->moeda = $moeda;
    }

    /**
     * Armazena o valor da descrição do pedido
     * @param string @descricao
     */
    public function setDescricao($descricao){
        if (strlen($descricao) > 1024) {
            throw new \UnexpectedValueException('A descrição deve ser uma string com até 1024 caracteres');
        }
        $this->descricao = $descricao;
    }

    /**
     * Altera o idioma padrão, se passado um novo idioma válido
     * @param string $idioma
     */
    public function setIdioma($idioma){
        switch ($idioma){
            case 'PT':
            case 'EN':
            case 'ES':
                $this->idioma = $idioma;
                break;
            default:
                throw new \UnexpectedValueException('O idioma deve ser informado como PT (português), EN (inglês) ou ES (espanhol)');
        }
    }

    /**
     * Armazena uma descrição curta que aparecerá na fatura do cartão do cliente
     * @param string @soft_descriptor
     */
    public function setSoft_descriptor($soft_descriptor){
        if (strlen($soft_descriptor) > 13) {
            throw new \UnexpectedValueException('O texto que será exibido na fatura do portador deve ser uma string com até 13 caracteres');
        }
        $this->soft_descriptor = $soft_descriptor;
    }

    /**
     * Armazena um valor para a taxa de embarque
     * @deprecated Ao passar esse valor no XML, o resultado é um erro
     * @param integer @taxa_embarque
     */
    public function setTaxa_embarque($taxa_embarque){
        if (!is_int($taxa_embarque)){
            throw new \UnexpectedValueException('O valor da autorização que deve ser destinado à taxa de embarque deve ser informada como inteiro');
        }
        $this->taxa_embarque = $taxa_embarque;
    }

    /**
     * @return string
     */
    public function getPedido(){
        return $this->pedido;
    }

    /**
     * @return integer
     */
    public function getValor(){
        return $this->valor->getValor();
    }

    /**
     * @return integer
     */
    public function getMoeda(){
        return $this->moeda;
    }

    /**
     * @return string
     */
    public function getData_hora(){
        $this->data_hora = date("Y-m-d\TH:i:s");
        return $this->data_hora;
    }

    /**
     * @return string
     */
    public function getDescricao(){
        return $this->descricao;
    }

    /**
     * @return string
     */
    public function getIdioma(){
        return $this->idioma;
    }

    /**
     * @return string
     */
    public function getSoft_descriptor(){
        return $this->soft_descriptor;
    }

    /**
     * @return integer
     */
    public function getTaxa_embarque(){
        return $this->taxa_embarque;
    }

}
