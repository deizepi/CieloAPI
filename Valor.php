<?php

class Valor {

    /**
     * Valor da compra, sem pontuação
     * @var integer $valor
     */
    private $valor;

    /**
     * @param integer $valor - Valor do pedido
     */
    function __construct($valor){
        $this->setValor($valor);
    }

    /**
     * Armazena o valor do pedido, removendo caracteres invalidos
     * @param integer $valor
     */
    private function setValor($valor){
        if (!is_int((int)$valor)) {
            throw new \UnexpectedValueException('O valor total do pedido deve ser informado como inteiro e já deve incluir valor de frete e outras despesas/taxas');
        }
        $valor = preg_replace("/[^0-9]/", "", $valor);
        $this->valor = $valor;
    }

    /**
     * @return integer
     */
    public function getValor(){
        return $this->valor;
    }

}
