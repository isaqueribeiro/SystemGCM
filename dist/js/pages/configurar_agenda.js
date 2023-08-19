/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function pesquisar_configuracoes_agenda() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'tipo'   : $('#cd_tipo_filtro').val(),
        'filtro' : $('#ds_filtro').val(),
        'action' : 'pesquisar_configuracoes',
        'qt_registro' : $('#qtde-registros-conf').val()
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_configurar_agenda.php',
        // Definimos o tipo de requisição
        type: 'post',
        // Definimos o tipo de retorno
        dataType : 'html',
        // Colocamos os valores a serem enviados
        data: params,
        // Antes de enviar ele alerta para esperar
        beforeSend : function(){
            document.body.style.cursor = "wait";
            $('#box-tabela').html("<i class='fa fa-spin fa-refresh'></i>&nbsp; Executando pesquisa, <strong>aguarde</strong>!");
            Pace.restart();
        },
        // Colocamos o retorno na tela
        success : function(data){
            $('#box-tabela').html(data);
            document.body.style.cursor = "auto";
            if (typeof($('#tb-configuracoes')) !== "undefined") {
                configurar_tabela('#tb-configuracoes');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function carregar_configuracao_agenda(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'codigo' : codigo,
        'action' : 'carregar_configuracao'
    };
    
    try {
        $('#operacao').val("editar");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_configurar_agenda.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
            // Dolocamos os valores a serem enviados
            data: params,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
                Pace.restart();
            },
            // Colocamos o retorno na tela
            success : function(data){
                var retorno = data;
                document.body.style.cursor = "auto";
                if (retorno === "OK") { 
                    var file = "logs/json/configurar_agenda_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        if(callback && typeof(callback) === "function") {
                            callback(data);
                        }
                        
                        return true;
                    });
                } else {
                    show_alerta("Alerta", retorno);
                    return false;
                }
            },
            error: function (request, status, error) {
                document.body.style.cursor = "auto";
                show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
                return false;
            }
        });  
        // Finalizamos o Ajax
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function salvar_registro_configuracao_agenda(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo,
        'action' : 'salvar_configuracao',
        'cd_agenda' : $('#cd_agenda').val(),
        'nm_agenda' : $('#nm_agenda').val(),
        'ds_observacoes'  : $('#ds_observacoes').val(),
        'cd_especialidade': $('#cd_especialidade').val(),
        'cd_profissional' : $('#cd_profissional').val(),
        'dt_inicial': $('#dt_inicial').val(),
        'dt_final'  : $('#dt_final').val(),
        'hr_divisao_agenda': $('#hr_divisao_agenda').val(),
        'sn_domingo' : '0',
        'sn_segunda' : '0',
        'sn_terca'   : '0',
        'sn_quarta'  : '0',
        'sn_quinta'  : '0',
        'sn_sexta'   : '0',
        'sn_sabado'  : '0',
        // Domingo
        'hr_dom_ini_manha' : $('#hr_dom_ini_manha').val(),
        'hr_dom_fim_manha' : $('#hr_dom_fim_manha').val(),
        'hr_dom_ini_tarde' : $('#hr_dom_ini_tarde').val(),
        'hr_dom_fim_tarde' : $('#hr_dom_fim_tarde').val(),
        // Segunda
        'hr_seg_ini_manha' : $('#hr_seg_ini_manha').val(),
        'hr_seg_fim_manha' : $('#hr_seg_fim_manha').val(),
        'hr_seg_ini_tarde' : $('#hr_seg_ini_tarde').val(),
        'hr_seg_fim_tarde' : $('#hr_seg_fim_tarde').val(),
        // Terça
        'hr_ter_ini_manha' : $('#hr_ter_ini_manha').val(),
        'hr_ter_fim_manha' : $('#hr_ter_fim_manha').val(),
        'hr_ter_ini_tarde' : $('#hr_ter_ini_tarde').val(),
        'hr_ter_fim_tarde' : $('#hr_ter_fim_tarde').val(),
        // Quarta
        'hr_qua_ini_manha' : $('#hr_qua_ini_manha').val(),
        'hr_qua_fim_manha' : $('#hr_qua_fim_manha').val(),
        'hr_qua_ini_tarde' : $('#hr_qua_ini_tarde').val(),
        'hr_qua_fim_tarde' : $('#hr_qua_fim_tarde').val(),
        // Quinta
        'hr_qui_ini_manha' : $('#hr_qui_ini_manha').val(),
        'hr_qui_fim_manha' : $('#hr_qui_fim_manha').val(),
        'hr_qui_ini_tarde' : $('#hr_qui_ini_tarde').val(),
        'hr_qui_fim_tarde' : $('#hr_qui_fim_tarde').val(),
        // Sexta
        'hr_sex_ini_manha' : $('#hr_sex_ini_manha').val(),
        'hr_sex_fim_manha' : $('#hr_sex_fim_manha').val(),
        'hr_sex_ini_tarde' : $('#hr_sex_ini_tarde').val(),
        'hr_sex_fim_tarde' : $('#hr_sex_fim_tarde').val(),
        // Sábado
        'hr_sab_ini_manha' : $('#hr_sab_ini_manha').val(),
        'hr_sab_fim_manha' : $('#hr_sab_fim_manha').val(),
        'hr_sab_ini_tarde' : $('#hr_sab_ini_tarde').val(),
        'hr_sab_fim_tarde' : $('#hr_sab_fim_tarde').val(),
        'sn_ativo'   : '0'
    };
    
    if ( $('#sn_domingo').is(":checked") ) params.sn_domingo = $('#sn_domingo').val();
    if ( $('#sn_segunda').is(":checked") ) params.sn_segunda = $('#sn_segunda').val();
    if ( $('#sn_terca').is(":checked") )   params.sn_terca = $('#sn_terca').val();
    if ( $('#sn_quarta').is(":checked") )  params.sn_quarta = $('#sn_quarta').val();
    if ( $('#sn_quinta').is(":checked") )  params.sn_quinta = $('#sn_quinta').val();
    if ( $('#sn_sexta').is(":checked") )   params.sn_sexta = $('#sn_sexta').val();
    if ( $('#sn_sabado').is(":checked") )  params.sn_sabado = $('#sn_sabado').val();
    
    if ( $('#sn_ativo').is(":checked") ) params.sn_ativo = $('#sn_ativo').val();
    
    try {
        var inserir = ($('#operacao').val() === "inserir");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_configurar_agenda.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
            // Dolocamos os valores a serem enviados
            data: params,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
                Pace.restart();
            },
            // Colocamos o retorno na tela
            success : function(data){
                var retorno = data;
                document.body.style.cursor = "auto";
                if (retorno === "OK") {
                    var file = "logs/json/configurar_agenda_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        if (inserir) {
                            $('#cd_agenda').val(data.registro[0].agenda);
                            var newRow = $(data.registro[0].tr_table);
                            $('#tb-configuracoes').append(newRow);
                        } else {
                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].agenda); //$(data.registro[0].tr_table);
                            var colunas  = tr_table.getElementsByTagName('td');
                            
                            colunas[1].firstChild.nodeValue = params.nm_agenda;
                            colunas[2].firstChild.nodeValue = data.registro[0].especialidade;
                        }

                        if (callback && typeof(callback) === "function") {
                            callback();
                        }
                    });                            

                    return true;
                } else {
                    show_alerta("Alerta", retorno);
                    return false;
                }
            },
            error: function (request, status, error) {
                document.body.style.cursor = "auto";
                show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
                return false;
            }
        });  
        // Finalizamos o Ajax
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function excluir_configuracao_agenda(codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'configuracao': codigo,
        'action'      : 'excluir_configuracao'
    };
    
    try {
        show_confirmar("Excluir", "Você confirma a exclusão da configuração <strong>'" + descricao + "'</strong>?");
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_configurar_agenda.php',
                // Definimos o tipo de requisição
                type: 'post',
                // Definimos o tipo de retorno
                dataType : 'html',
                // Dolocamos os valores a serem enviados
                data: params,
                // Antes de enviar ele alerta para esperar
                beforeSend : function(){
                    document.body.style.cursor = "wait";
                    Pace.restart();
                },
                // Colocamos o retorno na tela
                success : function(data){
                    var retorno = data;
                    document.body.style.cursor = "auto";
                    if (retorno === "OK") {

                        if (callback && typeof(callback) === "function") {
                            callback();
                        }

                        return true;
                    } else {
                        show_alerta("Alerta", retorno);
                        return false;
                    }
                },
                error: function (request, status, error) {
                    document.body.style.cursor = "auto";
                    show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
                    return false;
                }
            });  
            // Finalizamos o Ajax
        }
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}