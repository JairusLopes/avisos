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


//Executando uma função Zend.
$pag1->drawText($bdnome_predio,330,355);
$pag1->drawText($bdccod,640,355);

$pdf->save('../pdfs/.AT.pdf',true); 

$arquivo_origem = "../pdfs/.AT.pdf";
$arquivo_destino = $c_arqatasimg . $bdgrupo . ".pdf";
copy ($arquivo_origem,$arquivo_destino);	
	
$arquivo_gif = $c_arqatasimg . $bdgrupo . ".gif";

shell_exec("convert -density 150 '$arquivo_destino' '$arquivo_gif'");
	
//Apagando os PDF's.
unlink ("$arquivo_destino");
	 
//Copiando o arquivo original.
$arquivo_ori_AT = "../pdfs/.pdfnew/AT.pdf";
$arquivo_des_AT = "../pdfs/.AT.pdf";
copy ($arquivo_ori_AT,$arquivo_des_AT);

//Atualização do campo "dt_importacao".
pg_query($dbconv, "UPDATE enviadoc SET dt_importacao = CURRENT_DATE WHERE categoria = '1' AND dt_importacao is null");

}

pg_close($dbconv);

//Transformando os arquivos docs em pdfs.
shell_exec("unoconv -f pdf -o $c_arqataspdfn $c_arqatasdocs*.doc 2>/dev/null");


foreach (glob("$c_arqataspdfn" . "*" . "pdf") as $arqalter){

$oldarq = substr($arqalter,61);
$localold = "$c_arqataspdfn$oldarq";
$arqaltercod = substr($arqalter,61,5);
$arqalterrest = substr(str_replace(' ','',$arqalter),66);
$nwarq = $arqaltercod . $arqalterrest;
$localnew = "$c_arqataspdf$nwarq";

copy($localold, $localnew);

}

//Apagando a pasta "docs".
shell_exec("rm $c_arqatasdocs*.doc 2>/dev/null");


//Apagando a pasta "docs".
shell_exec("rm $c_arqataspdfn*.pdf 2>/dev/null");	


?>