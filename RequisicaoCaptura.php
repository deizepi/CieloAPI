<?php

class RequisicaoCaptura extends Cielo {

    /**
     * TID (Transaction Identifier): Identificada unicamente uma transação Cielo
     * @var string @tid
     */
    private $tid; 

    /**
     * Valor a ser capturado do pedido (esse é o valor que será descontado do cliente) 
     * @var integer $valor
     */
    private $valor; 

    /**
     * @param string $tid - TID da transação a ser capturada
     * @param integer $valor - Valor a ser capturado (se omitido, o valor total da transação será capturado)
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
