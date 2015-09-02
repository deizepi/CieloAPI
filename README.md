# CieloAPI
API para uso do Webservice da Cielo e-commerce

O uso da API é bastante simples, após baixar todos os arquivos basta importar a classe "Cielo.php" e então instanciar algum dos tipos de requisição.
**Não se esqueça de ler o documento oficial da Cielo.**

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

		//----------------------------------------------------------
		$transacao = new RequisicaoTransacao($pedido, $valor, $produto, $parcelas, $cartao, $validade, $codigo);
		$transacao->setCapturar(false); //Captura manual
		$transacao->setCampoLivre("Cliente Premium"); //Campo livre
		$transacao->setGerarToken(false); //gerar token do cartão
		$transacao->setAVS($cep, $endereco, $numero, $complemento, $bairro); //serviço AVS (Address verification service)
		$tPedido = $transacao->getPedido();
		$tPedido->setMoeda(220); //dolar estadunidense
		$tPedido->setIdioma("ES"); //idioma espanhol
		$tPedido->setSoft_descriptor("compra teste"); //aparecerá na fatura do cliente
		$tPedido->setDescricao("entregar até amanhã"); //descrição do pedido
		#----------------------------------------------------------*/
		/*/----------------------------------------------------------
		$t = new RequisicaoToken($cartao, $validade, $codigo);
		#----------------------------------------------------------*/

		$retorno = $transacao->enviar();
		$tid = ($retorno->getTid()) ? $retorno->getTid() : false;

		if($tid){

			print_r($retorno);

			//----------------------------------------------------------
			$a = new RequisicaoCaptura($tid);
			#----------------------------------------------------------*/
			/*/----------------------------------------------------------
			$a = new RequisicaoAutorizacaoTid($tid);
			#----------------------------------------------------------*/
			/*/----------------------------------------------------------
			$a = new RequisicaoConsulta($tid);
			#----------------------------------------------------------*/
			/*/----------------------------------------------------------
			$a = new RequisicaoCancelamento($tid);
			#----------------------------------------------------------*/
			if(isset($a)){
				$resposta = $a->enviar();
				print_r($resposta);
			}
		}

	} catch(Exception $erro){
		echo "Ocorreu o seguinte erro: ".$erro->getMessage()."\n";
	}
	
Com o sistema homologado e devidamente cadastrado na Cielo, você deve alterar as constantes da classe Empresa.

    const NUMERO = "SEU NUMERO DE AFILIAÇÃO COM A CIELO";
    const CHAVE  = "SUA CHAVE DE AFILIAÇÃO COM A CIELO";
    
