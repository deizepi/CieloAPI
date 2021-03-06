# CieloAPI
API para uso do Webservice da Cielo e-commerce

O uso da API é bastante simples, após baixar todos os arquivos basta importar a classe "Cielo.php" e então instanciar algum dos tipos de requisição.

**Não se esqueça de ler o documento oficial da Cielo.**

[Cielo Webservice V2.5.4](https://www.cielo.com.br/wps/wcm/connect/c682298e-4518-4e2b-8945-cef23e04b5ec/Cielo-E-commerce-Manual-do-Desenvolvedor-WebService-PT-V2.5.4.pdf?MOD=AJPERES&CONVERT_TO=url&CACHEID=c682298e-4518-4e2b-8945-cef23e04b5ec)


## requisicao-transacao
	
	<?php
    require_once("Cielo.php");

    try { 

		$pedido     = "5621658400001"; 
		$valor      = "89,00"; 
		$produto    = "2";
		$parcelas   = "2"; 
		$cartao     = "5453010000066167";
		$validade   = "05/2018"; 
		$codigo     = "123";

		$cep 			= "04538-132";
		$endereco 		= "Av. Brigadeiro Faria Lima";
		$numero 		= "3477";
		$complemento 	= "";
		$bairro 		= "Itaim Bibi";

		$transacao = new RequisicaoTransacao($pedido, $valor, $produto, $parcelas, $cartao, $validade, $codigo);
		$transacao->setAVS($cep, $endereco, $numero, $complemento, $bairro); 

		$retorno = $transacao->enviar();
		print_r($retorno);

	} catch(Exception $erro){
		echo "Ocorreu o seguinte erro: ".$erro->getMessage()."\n";
	}
	?>
	
## requisicao-token

	<?php
    require_once("Cielo.php");

    try { 

		$cartao     = "5453010000066167";
		$validade   = "05/2018"; 
		$codigo     = "123";

		$t = new RequisicaoToken($cartao, $validade, $codigo);

		$retorno = $transacao->enviar();
		
		echo $retorno->getCartao()->getToken();

	} catch(Exception $erro){
		echo "Ocorreu o seguinte erro: ".$erro->getMessage()."\n";
	}
	?>
	
## requisicao-autorizacao-tid

	<?php
    require_once("Cielo.php");

    try { 

		$pedido     = "5621658400001"; 
		$valor      = "89,00"; 
		$produto    = "2";
		$parcelas   = "2"; 
		$cartao     = "5453010000066167";
		$validade   = "05/2018"; 
		$codigo     = "123";

		$transacao = new RequisicaoTransacao($pedido, $valor, $produto, $parcelas, $cartao, $validade, $codigo);
		
		$retorno = $transacao->enviar();
		$tid = ($retorno->getTid()) ? $retorno->getTid() : false;

		if($tid){

			$a = new RequisicaoAutorizacaoTid($tid);
			
			if(isset($a)){
				$resposta = $a->enviar();
				print_r($resposta);
			}
		}

	} catch(Exception $erro){
		echo "Ocorreu o seguinte erro: ".$erro->getMessage()."\n";
	}
	?>

## requisicao-captura

	<?php
    require_once("Cielo.php");

    try { 

		$pedido     = "5621658400001"; 
		$valor      = "89,00"; 
		$produto    = "2";
		$parcelas   = "2"; 
		$cartao     = "5453010000066167";
		$validade   = "05/2018"; 
		$codigo     = "123";

		$transacao = new RequisicaoTransacao($pedido, $valor, $produto, $parcelas, $cartao, $validade, $codigo);
		$transacao->setCapturar(false); //Captura manual
		$transacao->setGerarToken(false); //gerar token do cartão

		$retorno = $transacao->enviar();
		$tid = ($retorno->getTid()) ? $retorno->getTid() : false;

		if($tid){

			print_r($retorno);

			$a = new RequisicaoCaptura($tid);
			
			if(isset($a)){
				$resposta = $a->enviar();
				print_r($resposta);
			}
		}

	} catch(Exception $erro){
		echo "Ocorreu o seguinte erro: ".$erro->getMessage()."\n";
	}
	?>
	
## requisicao-cancelamento

	<?php
    require_once("Cielo.php");

    try { 

		$pedido     = "5621658400001"; 
		$valor      = "89,00"; 
		$produto    = "2";
		$parcelas   = "2"; 
		$cartao     = "5453010000066167";
		$validade   = "05/2018"; 
		$codigo     = "123";

		$transacao = new RequisicaoTransacao($pedido, $valor, $produto, $parcelas, $cartao, $validade, $codigo);

		$retorno = $transacao->enviar();
		$tid = ($retorno->getTid()) ? $retorno->getTid() : false;

		if($tid){

			print_r($retorno);
			
			$a = new RequisicaoCancelamento($tid);
			
			if(isset($a)){
				$resposta = $a->enviar();
				print_r($resposta);
			}
		}

	} catch(Exception $erro){
		echo "Ocorreu o seguinte erro: ".$erro->getMessage()."\n";
	}
	?>

## requisicao-consulta

	<?php
    require_once("Cielo.php");

    try { 

		$pedido     = "5621658400001"; 
		$valor      = "89,00"; 
		$produto    = "2";
		$parcelas   = "2"; 
		$cartao     = "5453010000066167";
		$validade   = "05/2018"; 
		$codigo     = "123";

		$transacao = new RequisicaoTransacao($pedido, $valor, $produto, $parcelas, $cartao, $validade, $codigo);

		$retorno = $transacao->enviar();
		$tid = ($retorno->getTid()) ? $retorno->getTid() : false;

		if($tid){

			print_r($retorno);

			$a = new RequisicaoConsulta($tid);
			
			if(isset($a)){
				$resposta = $a->enviar();
				print_r($resposta);
			}
		}

	} catch(Exception $erro){
		echo "Ocorreu o seguinte erro: ".$erro->getMessage()."\n";
	}
	?>
	
Com o sistema homologado e devidamente cadastrado na Cielo, você deve alterar as constantes da classe Empresa.

    const NUMERO = "SEU NUMERO DE AFILIAÇÃO COM A CIELO";
    const CHAVE  = "SUA CHAVE DE AFILIAÇÃO COM A CIELO";
    
