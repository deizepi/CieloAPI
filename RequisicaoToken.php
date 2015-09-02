<?php

class RequisicaoToken extends Cielo {

    /**
     * Todos os dados do cartão (número, validade, cód. segurança, nome, bandeira, BIN ou token)
     * @var Cartao $cartao
     */
    private $cartao;           

    /**
     * @param string $cartao - Número do cartão ou Token
     * @param string $validade - Validade do cartão 
     * @param string $codigo - Código de segurança (CVC) o cartão
     */
    function __construct($cartao, $validade = null, $codigo = null){
        $this->cartao = new Cartao($cartao, $validade, $codigo);
    }

    /**
     * @return Cartao
     */
    public function getCartao(){
    	return $this->cartao;
    }

}
