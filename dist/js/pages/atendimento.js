/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function atualizar_lista_paciente_avulso(input_type, input_name, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'input_type' : input_type,
        'input_name' : input_name,
        'action'     : 'listar_pacientes'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_paciente.php',
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
                if(callback && typeof(callback) === "function") {
                    callback(retorno);
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

function pesquisar_atendimentos_hoje() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'cd_profissional': $('#cd_profissional').val(),
        'tp_filtro'      : $('#cd_tipo_filtro').val(),
        'dt_hoje': $('#dt_hoje').val(),
        'action' : 'pesquisar_atendimentos_hoje',
        'qt_registro' : $('#qtde-registros-atend').val()
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_atendimento.php',
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
        },
        // Colocamos o retorno na tela
        success : function(data){
            $('#box-tabela').html(data);
            document.body.style.cursor = "auto";
            if (typeof($('#tb-atendimentos_hoje')) !== "undefined") {
                configurar_tabela('#tb-atendimentos_hoje');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function carregar_registro_atendimento(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'codigo' : codigo,
        'action' : 'carregar_atendimento'
    };
    
    try {
        $('#operacao').val("editar");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
                    var file = "logs/json/atendimento_" + params.estacao + ".json";
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

function carregar_historico_clinico(id_atendimento, dt_atendimento, paciente, callback) {
    var params = {
        'token'   : $('#tokenID').val(),
        'estacao' : $('#estacaoID').val(),
        'usuario' : $('#userID').val(),
        'empresa' : $('#empresaID').val(),
        'atendimento'  : id_atendimento,
        'data'    : dt_atendimento,
        'paciente': paciente,
        'action'  : 'historico_clinico'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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

function gerar_agendamento(observacoes, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'action' : 'inserir_agendamento_avulso',
        'data'     : $('#dt_agenda_avulso').val(),
        'hora'     : $('#hr_agenda_avulso').val(),
        'situacao' : "2",
        'profissional' : $('#cd_profissional_avulso').val(),
        'convenio'     : $('#cd_convenio_avulso').val(),
        'tabela'       : $('#cd_tabela_avulso').val(),
        'paciente'     : $('#cd_paciente_avulso').val(),
        'observacoes'  : observacoes,
        'sn_avulso'    : '1'
    };
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
                    var file = "logs/json/atendimento_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        var newRow = $(data.registro[0].tr_table);
                        $("#tb-atendimentos_hoje").append(newRow);
                        $("#referencia").val(data.registro[0].referencia);
                        
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

function salvar_registro_atendimento(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo, //$('#id_agenda').val(),
        'action' : 'salvar_atendimento',
        'id_agenda'       : $('#id_agenda').val(),
        'st_agenda'       : $('#st_agenda').val(),
        'dt_agenda'       : $('#dt_agenda').val(),
        'hr_agenda'       : $('#hr_agenda').val(),
        'cd_paciente'     : $('#cd_paciente').html(),
        'cd_convenio'     : $('#cd_convenio').val(),
        'cd_especialidade': $('#cd_especialidade').val(),
        'cd_profissional' : $('#cd_profissional').val(),
        'ds_alergias'     : $('#ds_alergias').val(),
        'ds_observacoes'  : $('#ds_observacoes').val(),
        'id_atendimento'  : $('#id_atendimento').val(),
        'cd_atendimento'  : $('#cd_atendimento').html(),
        'dt_atendimento'  : $('#dt_atendimento').val(),
        'hr_atendimento'  : $('#hr_atendimento').val(),
        'st_atendimento'  : $('#st_atendimento').val(),
        'ds_historia'     : $('#ds_historia').val().trim(),
        'ds_prescricao'   : $('#ds_prescricao').val().trim(),
        'sn_avulso'       : '0'
    };
    
    if ( $('#sn_avulso').is(":checked") ) params.sn_avulso = $('#sn_avulso').val();
    if ( parseInt(params.st_agenda) === 22 ) params.st_agenda = "2"; // Apenas agendamento confirmado, o atendimento da agenda ocorrerá em outra rotina
    
    try {
        var inserir = ($('#operacao').val() === "inserir");
        var atendimento = $('#tp_atendimento option:selected').text();
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
                    var file = "logs/json/atendimento_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        if (inserir) {
                            $('#cd_atendimento').html( zero_esquerda(data.registro[0].codigo_atendimento, 7) );
                            var newRow = $(data.registro[0].tr_table);
                            $("#tb-atendimentos_hoje").append(newRow);
                        } else {
                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].referencia); //$(data.registro[0].tr_table);
                            var colunas  = tr_table.getElementsByTagName('td');
                            /*
                            colunas[1].firstChild.nodeValue = params.nm_paciente;
                            colunas[2].firstChild.nodeValue = data.registro[0].contato;
                            colunas[3].firstChild.nodeValue = atendimento;
                            colunas[4].firstChild.nodeValue = data.registro[0].especialidade;
                            */
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

function set_situacao_atendimento(codigo, situacao, titulo, mensagem, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'id_agenda': codigo,
        'st_agenda': situacao,
        'action'   : 'encerrar_atendimento'
    };
    
    try {
        show_confirmar(titulo, mensagem);
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_atendimento.php',
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

function inserir_exame_atendimento(codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo,
        'id_atendimento': $('#id_atendimento').val(),
        'dt_atendimento': $('#dt_atendimento').val(),
        'cd_paciente'   : $('#cd_paciente').html(),
        'id_exame'      : $('#id_exame').val(),
        'action'        : 'inserir_exame_atendimento'
    };
    
    if (params.cd_paciente === "") {
        params.cd_paciente = $('#cd_paciente').val();
    }
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
                if (retorno === "EXIST") {
                    show_informe("Novo Exame", "Exame <strong>" + descricao + "</strong> já está no controle de exames do paciente.");
                } else
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
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function carregar_controle_exames(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo,
        'id_atendimento': $('#id_atendimento').val(),
        'dt_atendimento': $('#dt_atendimento').val(),
        'cd_paciente'   : $('#cd_paciente').html(),
        'sn_todos_exames' : $('#sn_todos_exames').val(),
        'action'        : 'carregar_controle_exames'
    };
    
    if (params.cd_paciente === "") {
        params.cd_paciente = $('#cd_paciente').val();
    }
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
                
                if (callback && typeof(callback) === "function") {
                    callback(retorno);
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

function salvar_resultados_exames(exames, valores, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'referencia'    : $('#referencia').val(),
        'id_atendimento': $('#id_atendimento').val(),
        'dt_atendimento': $('#dt_atendimento').val(),
        'cd_paciente'   : $('#cd_paciente').html(),
        'dt_exame': $('#dt_exame').val(),
        'exames'  : exames,
        'valores' : valores,
        'action'  : 'salvar_resultados_exames'
    };
    
    if (params.cd_paciente === "") {
        params.cd_paciente = $('#cd_paciente').val();
    }
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function inserir_evolucao_atendimento(codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo,
        'id_atendimento': $('#id_atendimento').val(),
        'dt_atendimento': $('#dt_atendimento').val(),
        'cd_paciente'   : $('#cd_paciente').html(),
        'id_evolucao'   : $('#id_evolucao').val(),
        'action'        : 'inserir_evolucao_atendimento'
    };
    
    if (params.cd_paciente === "") {
        params.cd_paciente = $('#cd_paciente').val();
    }
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
                if (retorno === "EXIST") {
                    show_informe("Nova Evolução", "Evolução <strong>" + descricao + "</strong> já está no controle de evoluções do paciente.");
                } else
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
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

function carregar_controle_evolucoes(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'agenda' : codigo,
        'id_atendimento': $('#id_atendimento').val(),
        'dt_atendimento': $('#dt_atendimento').val(),
        'cd_paciente'   : $('#cd_paciente').html(),
        'sn_todas_medidas' : $('#sn_todas_medidas').val(),
        'action'        : 'carregar_controle_evolucoes'
    };
    
    if (params.cd_paciente === "") {
        params.cd_paciente = $('#cd_paciente').val();
    }
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
                
                if (callback && typeof(callback) === "function") {
                    callback(retorno);
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

function salvar_resultados_evolucoes(evolucoes, valores, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'empresa': $('#empresaID').val(),
        'referencia'    : $('#referencia').val(),
        'id_atendimento': $('#id_atendimento').val(),
        'dt_atendimento': $('#dt_atendimento').val(),
        'cd_paciente'   : $('#cd_paciente').html(),
        'dt_evolucao': $('#dt_evolucao').val(),
        'evolucoes'  : evolucoes,
        'valores'    : valores,
        'action'     : 'salvar_resultados_evolucoes'
    };
    
    if (params.cd_paciente === "") {
        params.cd_paciente = $('#cd_paciente').val();
    }
    
    try {
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_atendimento.php',
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
    } catch (e) {
        show_erro("Erro", e);
        return false;
    }
}

/*
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
*/
