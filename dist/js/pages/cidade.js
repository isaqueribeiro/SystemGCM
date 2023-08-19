/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function pesquisar_cidades() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'estado' : $('#cd_estado_filtro').val(),
        'filtro' : $('#ds_filtro').val(),
        'action' : 'pesquisar_cidades',
        'qt_registro' : $('#qtde-registros-cidade').val()
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_cidade.php',
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
            if (typeof($('#tb-cidades')) !== "undefined") {
                configurar_tabela('#tb-cidades');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function carregar_registro_cidade(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'cidade' : codigo,
        'action' : 'carregar_cidade'
    };
    
    try {
        $('#operacao').val("editar");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_cidade.php',
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
                    var file = "logs/json/cidade_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        $('#cd_cidade').val(data.registro[0].codigo);
                        $('#nm_cidade').val(data.registro[0].nome);
                        $('#cd_estado').val(data.registro[0].estado);
                        $('#nr_cep_inicial').val(data.registro[0].cep_inicial);
                        $('#nr_cep_final').val(data.registro[0].cep_final);
                        $('#nr_ddd').val(data.registro[0].ddd);
                        
                        if(callback && typeof(callback) === "function") {
                            callback();
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

function listar_cidades_select(id_estado, id_div, id_select, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'estado' : $(id_estado).val(),
        'id_obj' : id_select,
        'action' : 'listar_cidades_select'
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_cidade.php',
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
            if (typeof($(id_div)) !== "undefined") {
                $(id_div).html(data);
                if(callback && typeof(callback) === "function") {
                    callback();
                }
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            show_erro("Erro", request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function salvar_registro_cidade(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'cidade' : codigo,
        'action' : 'salvar_cidade'
    };
    
    try {
        var operacao = $('#operacao').val();
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_cidade.php',
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
                    if(callback && typeof(callback) === "function") {
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