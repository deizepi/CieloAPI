<?php

class TransacaoId {

    /**
     * TID (Transaction Identifier): Identificada unicamente uma transação Cielo
     * @var string @tid
     */
    private $tid; 

    /**
     * @param string $tid - TID da transação a ser armazenada
     */
    function __construct($tid){
        $this->setTid($tid);
    }

    /**
     * Armazena um TID válido
     * @param string $tid
     */
    private function setTid($tid){
        if(!is_string($tid) OR strlen($tid) < 1 OR strlen($tid) > 40){
            throw new \UnexpectedValueException('O identificador da transação não é válido.');
        }
        $this->tid = $tid;
    }

    /**
     * @return string
     */
    public function getTid(){
        return $this->tid;
    }

}
