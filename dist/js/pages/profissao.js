/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function pesquisar_profissoes() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'tipo'   : $('#cd_tipo_filtro').val(),
        'filtro' : $('#ds_filtro').val(),
        'action' : 'pesquisar_profissoes',
        'qt_registro' : $('#qtde-registros-prof').val()
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_profissao.php',
        // Definimos o tipo de requisição
        type: 'post',
        // Definimos o tipo de retorno
        dataType : 'html',
        // Dolocamos os valores a serem enviados
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
            if (typeof($('#tb-profissoes')) !== "undefined") {
                configurar_tabela('#tb-profissoes');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function carregar_registro_profissao(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'codigo' : codigo,
        'action' : 'carregar_profissao'
    };
    
    try {
        $('#operacao').val("editar");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_profissao.php',
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
                    var file = "logs/json/profissao_" + params.estacao + ".json";
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

function salvar_registro_profissao(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'convenio' : codigo,
        'action'   : 'salvar_profissao',
        'cd_profissao' : $('#cd_profissao').val(),
        'ds_profissao' : $('#ds_profissao').val(),
        'nr_cbo'       : $('#nr_cbo').val(),
        'sn_ativo'     : '0'
    };
    
    if ( $('#sn_ativo').is(":checked") ) params.sn_ativo = $('#sn_ativo').val();
    
    try {
        var inserir = ($('#operacao').val() === "inserir");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_profissao.php',
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
                    var file = "logs/json/profissao_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        if (inserir) {
                            $('#cd_profissao').val(data.registro[0].profissao);
                            var newRow = $(data.registro[0].tr_table);
                            $("#tb-profissoes").append(newRow);
                        } else {
                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].profissao); //$(data.registro[0].tr_table);
                            var colunas  = tr_table.getElementsByTagName('td');
                            
                            colunas[1].firstChild.nodeValue = params.ds_profissao;
                            colunas[2].firstChild.nodeValue = params.nr_cbo;
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

function excluir_profissao(codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'profissao': codigo,
        'action'   : 'excluir_profissao'
    };
    
    try {
        show_confirmar("Excluir", "Você confirma a exclusão da profissão <strong>'" + descricao + "'</strong>?");
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_profissao.php',
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