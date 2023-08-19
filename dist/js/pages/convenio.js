/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function pesquisar_convenios() {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'tipo'   : $('#cd_tipo_filtro').val(),
        'filtro' : $('#ds_filtro').val(),
        'action' : 'pesquisar_convenios',
        'qt_registro' : $('#qtde-registros-conv').val()
    };
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : './dist/dao/dao_convenio.php',
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
            if (typeof($('#tb-convenios')) !== "undefined") {
                configurar_tabela('#tb-convenios');
            }
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#box-tabela').html(request.responseText + "<br>" + error + " (" + status + ")");
        }
    });  
    // Finalizamos o Ajax
}

function carregar_registro_convenio(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'codigo' : codigo,
        'action' : 'carregar_convenio'
    };
    
    try {
        $('#operacao').val("editar");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_convenio.php',
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
                    var file = "logs/json/convenio_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        $('#cd_convenio').val(zero_esquerda(data.registro[0].codigo, 3));
                        $('#nm_convenio').val(data.registro[0].nome);
                        $('#nm_resumido').val(data.registro[0].resumo);
                        $('#nr_cnpj_cpf').val(data.registro[0].cpf_cnpj);
                        $('#nr_registro_ans').val(data.registro[0].ans);
                        $('#sn_ativo').prop('checked', (parseInt(data.registro[0].ativo) === 1)).iCheck('update');
                        
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

function salvar_registro_convenio(codigo, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'convenio' : codigo,
        'action'   : 'salvar_convenio',
        'cd_convenio' : $('#cd_convenio').val(),
        'nm_convenio' : $('#nm_convenio').val(),
        'nm_resumido' : $('#nm_resumido').val(),
        'nr_cnpj_cpf' : $('#nr_cnpj_cpf').val(),
        'nr_registro_ans' : $('#nr_registro_ans').val(),
        'sn_ativo'        : '0'
    };
    
    if ( $('#sn_ativo').is(":checked") ) params.sn_ativo = $('#sn_ativo').val();
    
    try {
        var inserir = ($('#operacao').val() === "inserir");
        
        // Iniciamos o Ajax 
        $.ajax({
            // Definimos a url
            url : './dist/dao/dao_convenio.php',
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
                    var file = "logs/json/convenio_" + params.estacao + ".json";
                    $.getJSON(file, function(data){
                        if (inserir) {
                            $('#cd_convenio').val(data.registro[0].convenio);
                            var newRow = $(data.registro[0].tr_table);
                            $("#tb-convenios").append(newRow);
                        } else {
                            var tr_table = document.getElementById("tr-linha_" + data.registro[0].convenio); //$(data.registro[0].tr_table);
                            var colunas  = tr_table.getElementsByTagName('td');
                            
                            colunas[1].firstChild.nodeValue = params.nm_resumido;
                            colunas[2].firstChild.nodeValue = params.nm_convenio;
                            colunas[3].firstChild.nodeValue = params.cnpj_cpf;
                            colunas[4].firstChild.nodeValue = data.registro[0].ans;
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

function excluir_convenio (codigo, descricao, callback) {
    var params = {
        'token'  : $('#tokenID').val(),
        'estacao': $('#estacaoID').val(),
        'usuario': $('#userID').val(),
        'convenio' : codigo,
        'action'   : 'excluir_convenio'
    };
    
    try {
        show_confirmar("Excluir", "Você confirma a exclusão do convênio <strong>'" + descricao + "'</strong>?");
        
        var botao = document.getElementById("primary_confirm");
        botao.onclick = function() {
            $('#btn_msg_primario').trigger('click');
            
            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : './dist/dao/dao_convenio.php',
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