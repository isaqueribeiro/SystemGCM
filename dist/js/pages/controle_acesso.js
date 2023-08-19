/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var home_host = "http://localhost/gcm/";

var tipoAcessoVisualizar    = "0100";
var tipoAcessoModificar     = "0200";
var tipoAcessoControleTotal = "0300";

var menus = new Array(
	["20001000000", "Controle", Array(
		["20001010000", "Usuários Médicos", Array(
			' Sem Permissão',
			' Visualizar',
			' Modificar',
                        ' Controle Total'
		)],
		["20001020000", "Plantões", Array(
			' Sem Permissão',
			' Visualizar',
			' Modificar',
                        ' Controle Total'
		)],
		["20001030000", "Configurar Notificação", Array(
			' Sem Permissão',
			' Visualizar',
			' Modificar',
                        ' Controle Total'
		)]
	)],
	["20002000000", "Geral", Array(
		["20002010000", "Solicitações de Parecer", Array(
			' Sem Permissão',
			' Visualizar',
			' Modificar',
                        ' Controle Total'
		)],
		["20002020000", "Notificações (Push)", Array(
			' Sem Permissão',
			' Visualizar',
			' Modificar',
                        ' Controle Total'
		)]
	)],
//	["20008000000", "Gráficos / Relatórios", Array(
//		["20008010000", "Gráficos", Array(
//                    ["20008010100", "Nutrição", Array(
//                        ["20008010101", "Triagem", Array(
//                                ' Sem Permissão',
//                                ' Visualizar'
//                        )]
//                    )],
//                    ["20008010200", "Dor Torácica", Array(
//                        ["20008010101", "Triagem", Array(
//                                ' Sem Permissão',
//                                ' Visualizar'
//                        )],
//                        ["20008010102", "Diagnósticos e Prescrições", Array(
//                                ' Sem Permissão',
//                                ' Visualizar'
//                        )]
//                    )]
//		)],
//		["20008020000", "Relatórios", Array(
//			' Sem Permissão',
//			' Visualizar'
//		)]
//	)]
	["20009000000", "Sistema", Array(
		["20009010000", "Controle de Acesso", Array(
			'Sem Permissão',
			'Visualizar',
			'Modificar',
                        'Controle Total'
		)]//,
//		["20009020000", "Premissão de Acesso", Array(
//			'Sem Permissão',
//			'Visualizar',
//			'Modificar',
//                        'Controle Total'
//		)]
	)]
);

function get_file_allow_user(token_id) {
    return home_host + "/logs/allow_user_gcm" + token_id + ".json";      
}

function get_file_allow_user_current(token_id) {
    return home_host + "/logs/allow_user_" + token_id + ".json";      
}

function get_allow_user(usuario, rotina, tipo_acesso, callback) {
//    var token_id    = "";
//    var file_acesso = get_file_allow_user_current(token_id);  
//    $.getJSON(file_acesso, function(data){
//        if (usuario === data.permissao[0].cd_usuario) {
//            var permissoes = data.permissao[0].direitos;
//            var localizar  = permissoes.indexOf(rotina);
//            if ( localizar !== -1 ) {
//                var acesso = permissoes.substr(localizar, 11);
//                acesso = acesso.replace(rotina, "");
//                if (strToInt(acesso) < strToInt(tipo_acesso)) {
//                    mensagem_alerta("Usuário <strong>'" + usuario +"'</strong> sem premissão para esta rotina!<br>Verificar junto ao suporte." + "<br>Tipo do acesso : " + acesso);
//                    return false;
//                } else {
//                    // verifica se o parâmetro callback é realmente uma função antes de executá-lo
//                    if(callback && typeof(callback) === "function") {
//                        callback();
//                    }
//                    return true;
//                }
//            } else {
//                permissoes = permissoes.replace('2|2|', '\n2|2|').replace('3|2|', '\n3|2|').replace('4|2|', '\n4|2|');
//                mensagem_alerta("Usuário <strong>'" + usuario +"'</strong> sem premissão para esta rotina!" + "<br><br>Localizador : " + localizar + "<br>Permissões : " + permissoes);
//                return false;
//            }
//        } else {
//            mensagem_alerta("Usuário <strong>'" + usuario +"'</strong> sem premissão para esta rotina!<br>Verificar junto ao suporte.");// + "<br>" + file_acesso);
//            return false;
//        }
//    });
    if(callback && typeof(callback) === "function") {
        callback();
    }
    return true;
}



