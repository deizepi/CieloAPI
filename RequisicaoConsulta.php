<?php

class RequisicaoConsulta extends Cielo {

    /**
     * TID (Transaction Identifier): Identificada unicamente uma transação Cielo
     * @var string @tid
     */
    private $tid;

    /**
     * @param string $tid - TID da transação a ser consultada
     */
    function __construct($tid){
        $this->tid = new TransacaoId($tid);
    }

    /**
     * @return string
     */
    public function getTid(){
    	return $this->tid;
    }

}
