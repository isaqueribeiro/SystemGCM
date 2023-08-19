<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function protocol() : string {
    $protocol = (!isset($_SERVER['HTTPS']) ? 'http' : (strtolower($_SERVER['HTTPS']) === 'on' ? 'https' : 'http'));
    return $protocol;
}

function guid() {
    if (function_exists('com_create_guid')){
        return com_create_guid();
    } else {
        mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
        
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid = chr(123)   // "{"
                .substr($charid,  0,  8).$hyphen
                .substr($charid,  8,  4).$hyphen
                .substr($charid, 12,  4).$hyphen
                .substr($charid, 16,  4).$hyphen
                .substr($charid, 20, 12)
                .chr(125); // "}"
        return $uuid;
    }
}

function encript($value) {
    $chave   = "d033e22ae348aeb5660fc2140aec35850c4da997"; // admin (md5)
    $data    = base64_encode($value);
    $tam     = strlen($data);
    $posic   = rand(0, $tam);
    $retorno = "";
    if ( $posic === $tam ) {
        $retorno = leftStr($data, $posic) . $chave;
    } else {
        $retorno = leftStr($data, $posic) . $chave . rightStr($data, $tam - $posic);
    }
    return base64_encode($retorno);
}

function decript($value) {
    $chave   = "d033e22ae348aeb5660fc2140aec35850c4da997"; // admin (md5)
    $data    = base64_decode($value);
    $retorno = str_replace($chave, "", $data);
    return base64_decode($retorno);
}

function estaEncript($value) {
    $str = decript($value);
    return ($str !== "");
}

function formatarTexto($mascara, $string) {
    $string = str_replace(" ", "", $string);
    for ($i = 0; $i < strlen($string); $i++) {
        $mascara[strpos($mascara, "#")] = $string[$i];
    }
    return $mascara;
}

function gerar_arquivo_json($file_name, $registros) {
    if (file_exists($file_name)) {
        unlink($file_name);
    }
    //$registros = array('formulario' => array());
    $json = json_encode($registros);
    file_put_contents($file_name, $json);
}

function painel_alerta_danger($mensagem) {
    $alerta =
          "<div class='alert alert-danger alert-dismissible'> \n"
        . "    <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button> \n"
        . "    <h4><i class='icon fa fa-ban'></i> Alerta!</h4> \n"
        . "    {$mensagem} \n"
        . "</div> \n";
    return $alerta;
}

function cpf_valido($cpf = null) {
    // Verifica se um número foi informado
    if(empty($cpf)) {
        return false;
    }
 
    // Elimina possivel mascara
    $cpf = preg_replace('[^0-9]', '', $cpf);
    $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);
     
    // Verifica se o numero de digitos informados é igual a 11 
    if (strlen($cpf) != 11) {
        return false;
    }
    // Verifica se nenhuma das sequências invalidas abaixo 
    // foi digitada. Caso afirmativo, retorna falso
    else if (
        $cpf == '00000000000' || 
        $cpf == '11111111111' || 
        $cpf == '22222222222' || 
        $cpf == '33333333333' || 
        $cpf == '44444444444' || 
        $cpf == '55555555555' || 
        $cpf == '66666666666' || 
        $cpf == '77777777777' || 
        $cpf == '88888888888' || 
        $cpf == '99999999999') {
        return false;
     // Calcula os digitos verificadores para verificar se o
     // CPF é válido
     } else {   
         
        for ($t = 9; $t < 11; $t++) {
             
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
 
        return true;
    }
}

function cnpj_valido($cnpj = null) {
    // Verifica se um número foi informado
    if(empty($cnpj)) {
        return false;
    }

    // Elimina possivel mascara
    $cnpj = preg_replace("/[^0-9]/", "", $cnpj);
    $cnpj = str_pad($cnpj, 14, '0', STR_PAD_LEFT);

    // Verifica se o numero de digitos informados é igual a 11 
    if (strlen($cnpj) != 14) {
        return false;
    }

    // Verifica se nenhuma das sequências invalidas abaixo 
    // foi digitada. Caso afirmativo, retorna falso
    else if (
        $cnpj == '00000000000000' || 
        $cnpj == '11111111111111' || 
        $cnpj == '22222222222222' || 
        $cnpj == '33333333333333' || 
        $cnpj == '44444444444444' || 
        $cnpj == '55555555555555' || 
        $cnpj == '66666666666666' || 
        $cnpj == '77777777777777' || 
        $cnpj == '88888888888888' || 
        $cnpj == '99999999999999') {
        return false;

     // Calcula os digitos verificadores para verificar se o
     // CPF é válido
     } else {   
        $j = 5;
        $k = 6;
        $soma1 = 0;
        $soma2 = 0;

        for ($i = 0; $i < 13; $i++) {
            $j = $j == 1 ? 9 : $j;
            $k = $k == 1 ? 9 : $k;

            $soma2 += ($cnpj[$i] * $k);

            if ($i < 12) {
                    $soma1 += ($cnpj[$i] * $j);
            }

            $k--;
            $j--;
        }

        $digito1 = $soma1 % 11 < 2 ? 0 : 11 - $soma1 % 11;
        $digito2 = $soma2 % 11 < 2 ? 0 : 11 - $soma2 % 11;

        return (($cnpj[12] == $digito1) and ($cnpj[13] == $digito2));
    }
}

function cpf_cnpj_valido($cpf_cnpj) {
    $str = preg_replace('/[^0-9]/', '', $cpf_cnpj);
    
    if(strlen($str) === 11) {
        return cpf_valido($str);
    } else {
        return cnpj_valido($str);
    }

}

function remover_acentos($string) {
    $what = array( 'ä','ã','à','á','â','ê','ë','è','é','ï','ì','í','ö','õ','ò','ó','ô','ü','ù','ú','û','Ã','À','Á','Ê','É','Í','Õ','Ó','Ú','ñ','Ñ','ç','Ç','Ý','Ÿ','Ý');
    $by   = array( 'a','a','a','a','a','e','e','e','e','i','i','i','o','o','o','o','o','u','u','u','u','A','A','A','E','E','I','O','O','U','n','N','c','C','y','y','Y');
    return str_replace($what, $by, $string);
}

function meta_fonema($nome) {
    // Remover acentos
    $aux = str_replace('Y', 'I', remover_acentos( strtoupper(trim($nome)) )) ;
    
    $aux = str_replace('   ', ' ', $aux); // Remover 3 espaços
    $aux = str_replace('  ', ' ', $aux);  // Remover 2 espaços
    $aux = str_replace(' ', '.', $aux);   // Substituir espaço por ponto (.) <-- DELIMITADOR
    
    // Retira E , DA, DE, DI e DO
    $aux = str_replace('.E.',   '.', str_replace('.DA.',  '.', str_replace('.DE.', '.', str_replace('.DI.', '.', str_replace('.DO.', '.', $aux)))));
    $aux = str_replace('.DAS.', '.', str_replace('.DOS.', '.', $aux));
    
    $aux       .= " "; 
    $metafonema = "";
    
    // Retira letras duplicadas
    $remover = "";
    for ($i = 0; $i < strlen($aux) - 1; $i++) {
        if ($aux[$i] !== $aux[$i + 1]) {
            $remover .= $aux[$i];
        }
    }
    $aux = trim($remover) . " ";
    
    for ($i = 1; $i < strlen($aux); $i++) {
        switch ($aux[$i]) {
            
            case 'B':
            case 'D':
            case 'F':
            case 'J':
            case 'K':
            case 'L':
            case 'M':
            case 'N':
            case 'R':
            case 'T':
            case 'V':
            case 'X':
            case '.': {
                $metafonema .= $aux[$i];
            } break;
        
            case 'C':  {
                if ($aux[$i + 1] === 'H') { // CH = X
                    $metafonema .= 'X';
                } else 
                if (($aux[$i + 1] === 'A') || ($aux[$i + 1] === 'O') || ($aux[$i + 1] === 'U')) { // Carol = Karol
                    $metafonema .= 'K';
                } else 
                if (($aux[$i + 1] === 'E') || ($aux[$i + 1] === 'I')) { // Celina = Selina
                    $metafonema .= 'S';
                } else
                if (($aux[$i - 1] === 'A') && ($aux[$i - 2] === 'A') || (($aux[$i - 1] === 'A') && ($aux[$i + 1] === ' '))) { // Isaac = Isaque, Isac
                    $metafonema .= 'K';
                }    
            } break;
            
            case 'G':  {
                if ($aux[$i + 1] === 'E') { // Jeferson = Geferson
                    $metafonema .= 'J';
                } else {
                    $metafonema .= 'G';
                }
            } break;
            
            case 'P':  {
                if ($aux[$i + 1] === 'H') { // Phelipe = Felipe
                    $metafonema .= 'F';
                } else {
                    $metafonema .= 'P';
                }
            } break;
            
            case 'Q':  {
                if ($aux[$i + 1] === 'U') { // Keila = Queila
                    $metafonema .= 'K';
                } else {
                    $metafonema .= 'Q';
                }
            } break;
            
            case 'S':  {
                switch ($aux[$i + 1]) {
                    case 'H':  { // SH = X
                        $metafonema .= 'X';
                    } break;
                    case 'A':
                    case 'E':
                    case 'I':
                    case 'O':
                    case 'U': {
                        if (($aux[$i - 1] === 'A') || ($aux[$i - 1] === 'E') || ($aux[$i - 1] === 'I') || ($aux[$i - 1] === 'O') || ($aux[$i - 1] === 'U')) {
                            $metafonema .= 'Z'; // S entre duas vogais = Z
                        } else {
                            $metafonema .= 'S';
                        }
                    } break;
                }
            } break;
            
            case 'W':  { // Walter = Valter
                $metafonema .= 'V'; 
            } break;
            
            case 'Z':  { // no final do nome tem som de S -> Luiz = Luis
                if (($i === strlen($aux)) || ($aux[$i + 1] === ' ')) { // Keila = Queila
                    $metafonema .= 'S';
                } else {
                    $metafonema .= 'Z';
                }
            } break;
        }
    }
    
    $metafonema .= ".";
    
    return trim($metafonema);
}

function set_format_date($date, $format) {
    // Formato : DD/MM/YYYY
    // Separa em Dia, Mês e Ano
    list($dia, $mes, $ano) = explode('/', $date);
    $retorno = $dia . "-" . $mes . "-" .$ano;
    
    switch ($format) {
        case 'Y-m-d' : {
            $retorno = $ano . "-" . $mes . "-" . $dia;
        } break;    
    }
    
    return $retorno;
}

function calcular_idade_atual($data_nascimento) {
    // Formato : DD/MM/YYYY
    // Separa em Dia, Mês e Ano
    list($dia, $mes, $ano) = explode('/', $data_nascimento);
    // Descobre que dia é hoje e retorna a unix timestamp
    $hoje = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    // Descobre a unix timestamp da Data de Nascimento 
    $nascimento = mktime( 0, 0, 0, $mes, $dia, $ano);
    $idade = floor((((($hoje - $nascimento) / 60) / 60) / 24) / 365.25);
    
    return $idade;
}

function calcular_idade($data_nascimento, $data_referencia) {
    // Formato : YYYY-MM-DD
    $data = new DateTime($data_nascimento);
    $ref  = new DateTime($data_referencia);
    $intervalo = $data->diff($ref); 
    
    $ano   = $intervalo->format('%y') . "a";
    $mes   = ((int)$intervalo->format('%m') !== 0?$intervalo->format('%m') . "m":"");
    $idade = $ano . $mes;
    
    return $idade;
}

// https://pt.stackoverflow.com/questions/14477/como-localizar-um-valor-em-um-array-com-uma-estrutura-espec%C3%ADfica
/*
 * Searches value inside a multidimensional array, returning its index
 *
 * Original function by "giulio provasi" (link below)
 *
 * @param mixed|array $haystack
 *   The haystack to search
 *
 * @param mixed $needle
 *   The needle we are looking for
 *
 * @param mixed|optional $index
 *   Allow to define a specific index where the data will be searched
 *
 * @return integer|string
 *   If given needle can be found in given haystack, its index will
 *   be returned. Otherwise, -1 will
 *
 * @see http://www.php.net/manual/en/function.array-search.php#97645
*/
function search( $haystack, $needle, $index = NULL ) {
    if( is_null( $haystack ) ) {
        return -1;
    }

    $arrayIterator = new \RecursiveArrayIterator( $haystack );
    $iterator = new \RecursiveIteratorIterator( $arrayIterator );

    while( $iterator -> valid() ) {
        if( ( ( isset( $index ) and ( $iterator -> key() == $index ) ) or
            ( ! isset( $index ) ) ) and ( $iterator -> current() == $needle ) ) {

            return $arrayIterator -> key();
        }

        $iterator -> next();
    }

    return -1;
}