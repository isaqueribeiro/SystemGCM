/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function montar_calendario() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'dia'    : $('#dia').val(),
        'mes'    : $('#mes').val(),
        'ano'    : $('#ano').val(),
        'action' : 'montar_calendario'
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_agendamento.php',
        // Definimos o tipo de requisição
        type: 'post',
        // Definimos o tipo de retorno
        dataType : 'html',
        // Colocamos os valores a serem enviados
        data: params,
        // Antes de enviar ele alerta para esperar
        beforeSend : function(){
            document.body.style.cursor = "wait";
            Pace.restart();
        },
        // Colocamos o retorno na tela
        success : function(data){
            $('#box-calendario-mes').html(data);
            document.body.style.cursor = "auto";
            
            // Destacar a célula do Dia Atual
            var dia_atual = "dia_" + parseInt($('#dia_hoje').val()) + "_" + parseInt($('#mes_hoje').val()) + "_" + parseInt($('#ano_hoje').val());
            var elemento  = document.getElementById(dia_atual);
            if (elemento !== null) {
                var td  = $(elemento).closest('td');
                if ( $(td).hasClass("bg-gray") ) $(td).removeClass("bg-gray");
                $(td).addClass("text-bold"); 
                $(td).addClass("bg-primary");
                
                $('#cel').val('#' + td.attr('id'));
                $(elemento).trigger('click'); // Disparar pesquisa do dia atual
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-calendario-mes').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function pesquisar_agendamentos() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'especialidade': $('#cd_especialidade_filtro').val(),
        'profissional' : $('#cd_profissional_filtro').val(),
        'atendimento'  : $('#cd_atendimento_filtro').val(),
        'dia'    : $('#dia').val(),
        'mes'    : $('#mes').val(),
        'ano'    : $('#ano').val(),
        'action' : 'pesquisar_agendamentos',
        'qt_registro' : $('#qtde-registros-agend').val()
    };
    
    var dia = Array("dia_", params.dia, params.mes, params.ano);
    montar_titulo(dia);
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_agendamento.php',
        // Definimos o tipo de requisição
        type: 'post',
        // Definimos o tipo de retorno
        dataType : 'html',
        // Colocamos os valores a serem enviados
        data: params,
        // Antes de enviar ele alerta para esperar
        beforeSend : function(){
            document.body.style.cursor = "wait";
            //$('#box-tabela').html("<i class='fa fa-spin fa-refresh'></i>&nbsp; Executando pesquisa, <strong>aguarde</strong>!");
            Pace.restart();
        },
        // Colocamos o retorno na tela
        success : function(data){
            $('#box-tabela').html(data);
            document.body.style.cursor = "auto";
            if (typeof($('#tb-agendamentos')) !== "undefined") {
                configurar_tabela('#tb-agendamentos');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function carregar_registro_agendamento(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'codigo' : codigo,
        'action' : 'carregar_agendamento'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_agendamento.php',
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
                    var file = "logs/json/agendamento_" + params.estacao + ".json";
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

function carregar_historico_atendimento(id_agenda, dt_agenda, paciente, callback) {
    var params = {
        'token'   : $('#tokenID').val(),
        'estacao' : $('#estacaoID').val(),
        'usuario' : $('#userID').val(),
        'empresa' : $('#empresaID').val(),
        'agenda'  : id_agenda,
        'data'    : dt_agenda,
        'paciente': paciente,
        'action'  : 'historico_atendimento'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_agendamento.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
            // Dolocamos os valores a serem enviados
            data: params,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
                //Pace.restart();
            },
            // Colocamos o retorno na tela
            success : function(data){
                document.body.style.cursor = "auto";
                if(callback && typeof(callback) === "function") {
                    callback(data);
                }

                return true;
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

function salvar_registro_agendamento(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo, //$('#id_agenda').val(),
        'action' : 'salvar_agendamento',
        'id_agenda' : $('#id_agenda').val(),
        'cd_agenda' : $('#cd_agenda').val(),
        'dt_agenda' : $('#dt_agenda').val(),
        'hr_agenda' : $('#hr_agenda').val(),
        'st_agenda' : $('#st_agenda').val(),
        'cd_paciente' : $('#cd_paciente_ag').val(),
        'nm_paciente' : $('#nm_paciente_ag').val(),
        'nr_celular'  : $('#nr_celular_ag').val(),
        'nr_telefone' : $('#nr_telefone_ag').val(),
        'ds_email'    : $('#ds_email_ag').val(),
        'tp_atendimento'  : $('#tp_atendimento').val(),
        'cd_convenio'     : $('#cd_convenio_ag').val(),
        'cd_tabela'       : $('#cd_tabela').val(),
        'cd_especialidade': $('#cd_especialidade').val(),
        'cd_profissional' : $('#cd_profissional').val(),
        'cd_servico'      : $('#cd_servico').val(),
        'vl_servico'      : 0.0,
        'ds_observacao'   : $('#ds_observacao').val(),
        'sn_avulso'       : '0'
    };
    
    if ( $('#sn_avulso').is(":checked") ) params.sn_avulso = $('#sn_avulso').val();
    
    try {
        var inserir = (params.sn_avulso === '1'); // ($('#operacao').val() === "inserir");
        var valor   = $('#vl_servico').val().trim().replace(".", "");
        var atendimento = $('#tp_atendimento option:selected').text();
        
        params.vl_servico = valor.replace(",", ".");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_agendamento.php',
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
                    var file = "logs/json/agendamento_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        if (inserir) {
                            $('#cd_agenda').val(data.registro[0].codigo);
                            var newRow = $(data.registro[0].tr_table);
                            $("#tb-agendamentos").append(newRow);
                        } else {
                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].referencia); //$(data.registro[0].tr_table);
                            var colunas  = tr_table.getElementsByTagName('td');
                            
                            colunas[1].firstChild.nodeValue = params.nm_paciente;
                            colunas[2].firstChild.nodeValue = data.registro[0].contato;
                            colunas[3].firstChild.nodeValue = atendimento;
                            colunas[4].firstChild.nodeValue = data.registro[0].especialidade;
                        }

                        if (callback && typeof(callback) === "function") {
                            callback(data);
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

function excluir_agendamento(codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo,
        'action' : 'excluir_agendamento'
    };
    
    try {
        show_confirmar("Excluir", "Você confirma a exclusão do agendamento para <strong>'" + descricao + "'</strong>?");
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_agendamento.php',
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

function set_situacao_agendamento(codigo, situacao, titulo, mensagem, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo,
        'st_agenda': situacao,
        'action'   : 'set_situacao_agendamento'
    };
    
    try {
        //show_confirmar("Situação Horário", "Deseja alterar para <strong>'" + descricao + "'</strong> a situação do agendamento selecioado?");
        show_confirmar(titulo, mensagem);
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_agendamento.php',
                // Definimos o tipo de requisição
                type: 'post',
                // Definimos o tipo de retorno
                dataType : 'html',
                // Dolocamos os valores a serem enviados
                data: params,
                // Antes de enviar ele alerta para esperar
                beforeSend : function(){
                    document.body.style.cursor = "wait";
                    //Pace.restart();
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

function buscar_ultimo_agendamento(codigo, data, paciente, callback) {
    var params = {
        'token'   : $('#tokenID').val(),
        'estacao' : $('#estacaoID').val(),
        'usuario' : $('#userID').val(),
        'empresa' : $('#empresaID').val(),
        'codigo'  : codigo,
        'data'    : data,
        'paciente': paciente,
        'action'  : 'ultimo_agendamento'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_agendamento.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
            // Dolocamos os valores a serem enviados
            data: params,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
            },
            // Colocamos o retorno na tela
            success : function(data){
                var retorno = data;
                document.body.style.cursor = "auto";
                if (retorno === "OK") { 
                    var file = "logs/json/ultimo_agendamento_" + params.estacao + ".json";
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

function buscar_disponibilidade_agenda(data, callback) {
    var params = {
        'token'   : $('#tokenID').val(),
        'estacao' : $('#estacaoID').val(),
        'usuario' : $('#userID').val(),
        'empresa' : $('#empresaID').val(),
        'data'    : data,
        'action'  : 'disponibilidade_agenda'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_agendamento.php',
            // Definimos o tipo de requisição
            type: 'post',
            // Definimos o tipo de retorno
            dataType : 'html',
            // Dolocamos os valores a serem enviados
            data: params,
            // Antes de enviar ele alerta para esperar
            beforeSend : function(){
                document.body.style.cursor = "wait";
            },
            // Colocamos o retorno na tela
            success : function(data){
                var retorno = data;
                document.body.style.cursor = "auto";
                if (retorno === "OK") { 
                    var file = "logs/json/agenda_disponivel_" + params.estacao + ".json";
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
