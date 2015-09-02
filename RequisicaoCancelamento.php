<?php

class RequisicaoCancelamento extends Cielo {

    /**
     * TID (Transaction Identifier): Identificada unicamente uma transação Cielo
     * @var string @tid
     */
    private $tid; 

    /**
     * Valor a ser cancelado do pedido 
     *  NOTA: O valor não deve possuir pontuação: R$ 1.524,20 deve ser 152420
     * @var integer $valor
     */
    private $valor; 

    /**
     * @param string $tid - TID da transação a ser cancelada
     * @param integer $valor - Valor a ser cancelado (se omitido, cancela o valor total do pedido)
     */
    function __construct($tid, $valor = null){
        $this->tid = new TransacaoId($tid);
        if(isset($valor)){
            $this->valor = new Valor($valor);
        }
    }

    /** 
     * @return string
     */
    public function getTid(){
    	return $this->tid;
    }

    /** 
     * @return integer
     */
    public function getValor(){
    	return $this->valor;
    }
    
}
