<?php

class RequisicaoAutorizacaoTid extends Cielo {

	/**
	 * TID (Transaction Identifier): Identificada unicamente uma transação Cielo
	 * @var string @tid
	 */
    private $tid; 

    /**
     * @param string $tid - TID retornado na transação
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
