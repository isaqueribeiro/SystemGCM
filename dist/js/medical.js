/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var cd_profissional = 0;

function setProfissional(value) {
    cd_profissional = value;
}

function getProfissional() {
    return cd_profissional;
}

function remover_actives_medical() {
    remover_active_elemento('#page-home');
    remover_active_elemento('#page-atendimento_hoje');
}

function page_atendimentos_hoje(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de pacientes agendados para atendimento') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_atendimento_hoje',
                'usuario': id_usuario
            };

            remover_actives_medical();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_atendimento_hoje.php',
                // Definimos o tipo de requisição
                type: 'post',
                // Definimos o tipo de retorno
                dataType : 'html',
                // Dolocamos os valores a serem enviados
                data: params,
                // Antes de enviar ele alerta para esperar
                beforeSend : function(){
                    //removerMarcadores();
                    document.body.style.cursor = "wait";
                    Pace.restart();
                },
                // Colocamos o retorno na tela
                success : function(data){
                    document.body.style.cursor = "auto";
                    $('#content-wrapper').html(data);
                    $('#page-atendimento_hoje').addClass("active");
                    $('#page-click').trigger('click');
                },
                error: function (request, status, error) {
                    document.body.style.cursor = "auto";
                    $('#content-wrapper').html(status + "<br>" + error + "<br>" + request.responseText);
                }
            });  
            // Finalizamos o Ajax
        });
    }
}
