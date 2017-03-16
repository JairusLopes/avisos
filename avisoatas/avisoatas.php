<?php

/*

Data		: 12/01/2017
Função	: Criar arquivos de "AVISOS" de ATAS, CONVOCAÇÕES e etc...
			  para envio via email, a partir da leitura de diretórios
			  contendo os documentos necessários.

Autor		: Jairus Lopes

*/

//error_reporting(0);

//Conexão com o BD.
include_once '../conexao.php';


// Inclui o arquivo class.phpmailer.php localizado na pasta class
require_once("../class/class.phpmailer.php");

// Inicia a classe PHPMailer
$mail = new PHPMailer(true);


function logMsg( $msg, $level = 'info', $file = 'log/main.log' )
{
    // variável que vai armazenar o nível do log (INFO, WARNING ou ERROR)
    $levelStr = '';
 
    // verifica o nível do log
    switch ( $level )
    {
        case 'info':
            // nível de informação
            $levelStr = 'INFO';
            break;
 
        case 'warning':
            // nível de aviso
            $levelStr = 'WARNING';
            break;
 
        case 'error':
            // nível de erro
            $levelStr = 'ERROR';
            break;
    }
 
    // data atual
    $date = date( 'Y-m-d H:i:s' );
 
    // formata a mensagem do log
    // 1o: data atual
    // 2o: nível da mensagem (INFO, WARNING ou ERROR)
    // 3o: a mensagem propriamente dita
    // 4o: uma quebra de linha
    $msg = sprintf( "[%s] [%s]: %s%s", $date, $levelStr, $msg, PHP_EOL );
 
    // escreve o log no arquivo
    // é necessário usar FILE_APPEND para que a mensagem seja escrita no final do arquivo, preservando o conteúdo antigo do arquivo
    file_put_contents( $file, $msg, FILE_APPEND );
}

//Localização dos diretórios e arquivos utilizados.

require_once("/var/www/public/ferramentas/avisos/localizacao.php");

//Importa o Zend Framework para PDF
require_once ('Zend/Pdf.php');

//Copiando o arquivo original.

$arquivo_ori_AT = "../pdfs/.pdfnew/AT.pdf";
$arquivo_des_AT= "../pdfs/.AT.pdf";
copy ($arquivo_ori_AT,$arquivo_des_AT);

// Carrega o formulário padrão de comprovante
$pdf = Zend_Pdf::load('../pdfs/.AT.pdf');

//Captura uma pagina
$pag1 = $pdf->pages[0];

//Capturo o tamanho da pagina pois a origem no rodapé
$h = $pag1->getHeight();
$w = $pag1->getWidth();

//Define uma fonte:
$font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD);

// aplica a fonte na pagina:
$pag1->setFont($font, 12.0);

$qu="";
$qu = pg_query($dbconv, "SELECT * FROM enviadoc WHERE categoria = '1' AND dt_importacao is null");

while ($data = pg_fetch_object($qu)) {
$bdccod = $data->predio;
$bdarquivo = $data->arquivo;
$bdarquivonew = substr($bdarquivo,0,-4);
$bdstatus = $data->status;
$bdtimport = $data->dt_import;
$bddtimportacao = $data->dt_importacao;
$bdsindico = $data->sindico;
$bdnome_predio = $data->nome_predio;
$bdgrupo = $data->grupo;

// Conexão com o servidor via FTP.
$local_file = 'docs/' . $bdarquivo;
$server_file = '/home/hicom/intranet/public_html/docs/' . $bdarquivo;
$ftp_server = '1.0.0.5';
$ftp_user_name = 'hicom';
$ftp_user_pass ='hi1419';

// set up basic connection
$conn_id = ftp_connect($ftp_server);

// login with username and password
$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

// Conectando no servidor e fazendo o download dos arquivos.
ftp_get($conn_id, $local_file, $server_file, FTP_BINARY);

// close the connection
ftp_close($conn_id);

//Preparar o Espelho (Arquivos - GIFs).

// Carrega o formulário padrão de comprovante
$pdf = Zend_Pdf::load('../pdfs/.AT.pdf');

//Captura uma pagina
$pag1 = $pdf->pages[0];

//Capturo o tamanho da pagina pois a origem no rodapé
$h = $pag1->getHeight();
$w = $pag1->getWidth();

//Define uma fonte:
$font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER_BOLD);

// aplica a fonte na pagina:
$pag1->setFont($font, 18.0);

//Transformando os arquivos docs em pdfs.
shell_exec("unoconv -f pdf -o $c_arqataspdf $c_arqatasdocs*.doc 2>/dev/null");

//shell_exec("mv $c_arqataspdfn*.doc $c_arqataspdf 2>/dev/null");

foreach(glob($c_arqataspdf . "*" ) as $arqvos) {

$codarqv1 = substr($arqvos,56,5);

$codarqv2 = substr(str_replace(" ","",$arqvos),61,-4);

$codarqv3 = $codarqv1 . substr($arqvos,61);

$codarqv4 = $codarqv1 . $codarqv2 . ".pdf";

$quA="";
$quA = pg_query($dbimageview, "SELECT * FROM email_teste WHERE ccod = '$codarqv1' AND sc IN ('1','0')");

while ($data = pg_fetch_object($quA)) {

//Dados dos campos da tabela "c_email01global". 
$bdccodac = $data->ccod;
$bdncondac = $data->ncond;	
$bdemailac = $data->e_mail;
$bdnnomeac = $data->nnome;
$bdaptoac = $data->uapto;
$bdsc = $data->sc;

//echo $bdccodac;

//Executando uma função Zend.
$pag1->drawText($bdncondac,330,355);
$pag1->drawText($bdccodac,640,355);

$pdf->save('../pdfs/.AT.pdf',true); 

$arquivo_origem = "../pdfs/.AT.pdf";
$arquivo_destino = $c_arqatasimg . $bdgrupo . ".pdf";
copy ($arquivo_origem,$arquivo_destino);	
	
$arquivo_gif = $c_arqatasimgbk . $bdgrupo . ".gif";

shell_exec("convert -density 150 '$arquivo_destino' '$arquivo_gif'");
	
//Apagando os PDF's.
unlink ("$arquivo_destino");

//Copiando o arquivo original.
$arquivo_ori_AT = "../pdfs/.pdfnew/AT.pdf";
$arquivo_des_AT = "../pdfs/.AT.pdf";
copy ($arquivo_ori_AT,$arquivo_des_AT);

//Nome do arquivo GIF.
$arquivopasta = $bdccodac . $codarqv2 . ".gif";

//Nome do arquivo "PDF".
$arquivopastac = $bdccodac . $codarqv2 . ".pdf";

//Copiando o arquivo PDF.
$arquivo_ori_PDF = "$c_arqataspdf$codarqv3";
$arquivo_des_PDF = "$c_arqataspdfbk$arquivopastac";
copy ($arquivo_ori_PDF,$arquivo_des_PDF);

//Apagando os PDF's.
unlink ("$arquivo_ori_PDF");

//Localização do "PDF", na pasta .backup.
$arquivopastap = "/opt/pastas_de_predios/avisos/acatas/pdf/.backup/" . $bdccodac . $codarqv2 . ".pdf";

//Criptografando link.
$bdimgcon = (md5($arquivopastac));

$acharimgcon = shell_exec("find /var/www/public/ferramentas/avisos/avisoatas/acatas/advb/ -maxdepth 1 -mindepth 1 -type l -name '$bdimgcon'");

if ( $acharimgcon = null ) {

//Criando o link.
shell_exec("ln -s $arquivopastap acatas/advb/$bdimgcon");

}else {
	
//Apagando e criando o link.
shell_exec("rm /var/www/public/ferramentas/avisos/avisoatas/acatas/advb/$bdimgcon");
shell_exec("ln -s $arquivopastap acatas/advb/$bdimgcon");

}

//Data atual
$date1 = date('d-m-Y');

//Configuração do Email.
	
	$mail->IsHTML(true); //Define Mensagem em formato HTML
	$mail->IsSMTP(); //Define envio via SMTP
	$mail->Host = '172.16.0.4'; // Endereço do servidor SMTP (Autenticação, utilize o host smtp.seudomínio.com.br)
	$mail->SMTPAuth   = false;  // Usar autenticação SMTP (obrigatório para smtp.seudomínio.com.br)
	$mail->Port       = 25; //  Usar 587 porta SMTP
	
	//Define o remetente
	$mail->SetFrom('crase@crasemail.com.br', 'naoresponda@crasemail.com.br'); //Seu e-mail
	$mail->AddBCC('crasesigma2@gmail.com', ''); // Email com Cópia oculta
		
	//Email - Assunto do Email.
	$mail->Subject = 'COMUNICADO ATA ASSEMBLEIA - ' . $bdccodac . ' - ' . $date1;//Assunto do e-mail

	//Define os destinatário(s)
	$mail->AddAddress("$bdemailac");
	
	$TEXTO="    
	
	<img src='http://crasemail.com.br:9080/imagens/avisos/acatas/img/.backup/$arquivopasta' alt='' width='1025'  border='0' usemap='#Map'>

	<map name='Map'>

	<area shape='rect' coords='200,595,819,540' href='http://crasemail.com.br:9080/imagens/avisos/acatas/advb/$bdimgcon' target='_blank'>

	</map>

	";

 	//Define o corpo do email
	$mail->MsgHTML("
	<html>
	<body>

	 $TEXTO

	</body>
	</html>
"); 

	//Enviando Mensagem.
	//$mail->Send();
	
//Atualização da tabela "log_email".
pg_query ($dbimageview, "INSERT INTO log_email (apto,predio,email,sindico,modelo_doc,aplicacao,dt_criacao,chave,pdf) VALUES ('$bdaptoac','$bdccodac','$bdemailac','$bdsc','COMUNICADO ATA ASSEMBLEIA','PHP',CURRENT_TIMESTAMP,'10','$bdccodac$codarqv2')");

// Limpa os destinatários e os anexos.
$mail->ClearAllRecipients();
$mail->ClearAttachments();

echo $arquivo_ori_PDF;

sleep("10");

				} //Fim do While (tabela c_email01global)
				

		} //Fim do FOREACH

//Atualização do campo "dt_importacao".
//pg_query($dbconv, "UPDATE enviadoc SET dt_importacao = '19000101' WHERE categoria = '1' AND dt_importacao is null");

} //Fim do While (tabela enviadoc)

pg_close($dbconv);
pg_close($dbimageview);

shell_exec("rm $c_arqatasdocs*.doc 2>/dev/null");

?>