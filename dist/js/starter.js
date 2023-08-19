/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function preventDefault() {
    return false;
}

function body_sizer_starter() {
//    var windowHeight = $(window).height();
//    var headerHeight = $('#page-header').height();
//    var contentHeight = windowHeight - headerHeight - 12;
//
//    $('#page-sidebar').css('height', contentHeight);
//    $('.scroll-sidebar').css('height', contentHeight);
//    $('#page-content').css('min-height', contentHeight);
    var windowHeight = $(window).height();
    var headerHeight = $('#page-header').height();
    var contentHeight = windowHeight - headerHeight - 52;

    $('#page-sidebar').css('height', contentHeight);
    $('.sidebar').css('height', contentHeight);
    $('.content-wrapper, .right-side').css('min-height', contentHeight);
};

function content_wrapper_sizer_starter(height_content_wrapper) {
    $(".content-wrapper, .right-side").css('min-height', height_content_wrapper);
}

function remover_active_elemento(elemento) {
    if (typeof($(elemento)) !== "undefined") {
        if ($(elemento).hasClass('active')) $(elemento).removeClass('active')
    }
}

function remover_actives() {
    remover_active_elemento('#page-home');
    remover_active_elemento('#page-agendamento');
    remover_active_elemento('#page-dados_profissionais');
    remover_active_elemento('#page-configurar_agenda');
    remover_active_elemento('#page-cidade');
    remover_active_elemento('#page-cep');
    remover_active_elemento('#page-convenio');
    remover_active_elemento('#page-profissao');
    remover_active_elemento('#page-grupoarquivo');
    remover_active_elemento('#page-especialidade');
    remover_active_elemento('#page-tabela_preco');
    remover_active_elemento('#page-tabela_exame');
    remover_active_elemento('#page-tabela_evolucao');
    remover_active_elemento('#page-paciente');
    remover_active_elemento('#page-constrole_usuario');
}

function sleep(milliseconds) {
    var start = new Date().getTime();
    for (var i = 0; i < 1e7; i++) {
        if ((new Date().getTime() - start) > milliseconds) {
            break;
        }
    }
}

function set_status_fa(elemento, valor) {
    if (typeof($(elemento)) !== "undefined") {
        if ($(elemento).hasClass('fa-check-square-o')) $(elemento).removeClass('fa-check-square-o');
        if ($(elemento).hasClass('fa-square-o'))       $(elemento).removeClass('fa-square-o');
        if ($(elemento).hasClass('text-green'))        $(elemento).removeClass('text-green');
        if ($(elemento).hasClass('text-red'))          $(elemento).removeClass('text-red');
        
        if ( valor === true ) {
            $(elemento).addClass('fa-check-square-o');
            $(elemento).addClass('text-green');
        } else {
            $(elemento).addClass('fa-square-o');
            $(elemento).addClass('text-red');
        }    
    }
}

function get_value(data, retorno) {
    if (data !== null && data !== undefined) {
        return data;
    } else {
        return retorno;
    }
}

function get_mes(mes) {
    var meses = new Array(
              ""
            , "Janeiro"
            , "Fevereiro"
            , "Março"
            , "Abril"
            , "Maio"
            , "Junho"
            , "Julho"
            , "Agosto"
            , "Setembro"
            , "Outubro"
            , "Novembro"
            , "Dezembro"
        );
    
    if ((mes >= 1) && (mes <= 12)) {
        return meses[mes];
    } else {
        return "...";
    }
}
function zero_esquerda(str, qtde) {
//    var foo = "";
//    var tam = qtde - str.length;
//    
//    while (foo.length < tam) {
//        foo = "0" + foo;
//    }
//    
//    var str = foo.concat(str);
//    
//    return str;
    var foo = "";
    var qte = qtde * (-1);
    while (foo.length < qtde) {
        foo = "0" + foo;
    }
    
    return (foo + str).slice(qte);
} 

function set_focus(elemento) {
    if (typeof($(elemento)) !== "undefined") {
        $(elemento).focus();
    }
}

function proximo_campo (e) {
    var keycode = e.which ? e.which : event.keyCode;
    alert(keycode);
    if (keycode == 110 || keycode == 188) {
        //e.preventDefault();
    }
 }

function validar_data(data) { 
    var str = data + "00/00/0000";
    // DD/MM/AAAA
    // 0123456789
    // 1234567890
    str = str.replace("d", "0");
    str = str.replace("m", "0");
    str = str.replace("y", "0");
    
    var dia = str.substring(0,2);
    var mes = str.substring(3,5);
    var ano = str.substring(6,10);
 
    //Criando um objeto Date usando os valores ano, mes e dia.
    var novaData = new Date(ano, (mes-1), dia); 
    
    var mesmoDia = parseInt(dia, 10) === parseInt(novaData.getDate());
    var mesmoMes = parseInt(mes, 10) === parseInt(novaData.getMonth()) + 1;
    var mesmoAno = parseInt(ano) === parseInt(novaData.getFullYear());
 
    if (!((mesmoDia) && (mesmoMes) && (mesmoAno))) {
        return false;
    } else {  
        return true;
    }
}

function validar_hora(hora) { 
    var str = hora + "99:99";
    // HH:MM
    // 01234
    // 12345
    var hr = str.substring(0,2);
    var mm = str.substring(3,5);
    
    if ( (parseInt(hr, 10) < 0) || (parseInt(hr, 10) > 23) || (parseInt(mm, 10) < 0) || (parseInt(mm, 10) > 59) )  {
        return false;
    } else {  
        return true;
    }
}

function somente_numero(e){
    var tecla = (window.event)?event.keyCode:e.which;
    if( (tecla > 47 && tecla < 58) ) return true;
    else {
        if ( tecla === 8 || tecla === 0) return true;
        else  return false;
    }
}

function somente_numero_decimal(e){
    var tecla = (window.event)?event.keyCode:e.which;
    if( (tecla > 47 && tecla < 58) ) return true;
    else{
        if ( tecla === 44 || tecla === 8 || tecla === 0) return true;
        else  return false;
    }
}

function format_moeda(value) {
    // Documentação :
    // http://www.emidioleite.com.br/2013/03/30/numberformat-com-javascript-formatando-numeros-com-javascript/
    var tmp = value + "";
    var num = new NumberFormat();
    
    num.setInputDecimal('.');
    num.setNumber(tmp); 
    num.setPlaces('2', false);
    num.setCurrencyValue('');
    num.setCurrency(true);
    num.setCurrencyPosition(num.LEFT_OUTSIDE);
    num.setNegativeFormat(num.LEFT_DASH);
    num.setNegativeRed(false);
    num.setSeparators(true, '.', ',');

    return num.toFormatted();
}

function texto_maiusculo(o) {
    return o.value.toUpperCase();
}

function texto_minusculo(o) {
    return o.value.toLowerCase();
}

function page_home() {
    var params = {
        'token' : $('#tokenID').val(),
        'action': 'home',
        'exec'  : 'reload'
    };
    
    remover_actives();
    
    // Iniciamos o Ajax 
    $.ajax({
        // Definimos a url
        url : 'views/_home.php',
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
            document.body.style.cursor = "auto";
            $('#content-wrapper').html(data);
            $('#page-home').addClass("active");
            $('#page-click').trigger('click');
            
            $('body').resize();
            getCharts();
        },
        error: function (request, status, error) {
            document.body.style.cursor = "auto";
            $('#content-wrapper').html(status + "<br>" + error + "<br>" + request.responseText);
        }
    });  
    // Finalizamos o Ajax
}

function page_agendamentos(usuario) {
    if( $('#content-wrapper').text().indexOf('Agenda de atendimentos dos pacientes') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_agendamentos',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_agendamento.php',
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
                    $('#page-agendamento').addClass("active");
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

function page_dados_profissionais(usuario) {
    if( $('#content-wrapper').text().indexOf('Painel de manutenção dos dados de profissionais médicos') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_profissional',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_profissional.php',
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
                    $('#page-dados_profissionais').addClass("active");
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

function page_configurar_agendas(usuario) {
    if( $('#content-wrapper').text().indexOf('Painel de configurações para disponibilidades das agendas') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_configurar_agendas',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_configurar_agenda.php',
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
                    $('#page-configurar_agenda').addClass("active");
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

function page_cidades(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de cidades disponíveis') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_cidades',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_cidade.php',
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
                    $('#page-cidade').addClass("active");
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

function page_ceps(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de ceps disponíveis') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_ceps',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_cep.php',
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
                    $('#page-cep').addClass("active");
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

function page_convenios(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de convênios disponíveis') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_convenios',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_convenio.php',
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
                    $('#page-convenio').addClass("active");
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

function page_profissoes(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de profissões disponíveis') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_profissoes',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_profissao.php',
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
                    $('#page-profissao').addClass("active");
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

function page_grupoarquivos(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de grupos de arquivos') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_grupos_arquivos',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_grupo_arquivo.php',
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
                    $('#page-grupoarquivo').addClass("active");
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

function page_especialidades(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de especialidades disponíveis') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_especialidades',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_especialidade.php',
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
                    $('#page-especialidade').addClass("active");
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

function page_tabela_precos(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de valores a serem cobrados nos atendimentos') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_tabela_precos',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_tabela_preco.php',
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
                    $('#page-tabela_preco').addClass("active");
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

function page_tabela_exames(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de exames solicitados aos pacientes') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_tabela_exames',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_tabela_exame.php',
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
                    $('#page-tabela_exame').addClass("active");
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

function page_tabela_evolucoes(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de evoluções de medidas aplicadas aos pacientes') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_tabela_evolucoes',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_tabela_evolucao.php',
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
                    $('#content-wrapper').html(data);
                    $('#page-tabela_evolucao').addClass("active");
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

function page_pacientes(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de pacientes cadastrados') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_pacientes',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_paciente_custom.php',
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
                    $('#page-paciente').addClass("active");
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

function page_medical(usuario, profissional) {
    setProfissional(profissional.replace('profissional_', ''));
}

function page_medical_v2(event, usuario, profissional) {
    event.preventDefault;
    var id_usuario = usuario.replace('user_', '');
    var cd_profissional = profissional.replace('profissional_', '');
    
    setProfissional(cd_profissional);
}

function page_controle_usuarios(usuario) {
    if( $('#content-wrapper').text().indexOf('Relação de usuários cadastros para acesso ao sistema') === -1 ) {
        var rotina = ""; //menus[menuSistemaID][2][rotinaControleAcessoID][0].substr(0, 7);
        var acesso = "0100";
        get_allow_user(usuario, rotina, acesso, function(){
            var id_usuario = usuario.replace("user_", "");
            var params = {
                'token'  : $('#tokenID').val(),
                'action' : 'vw_usuario',
                'usuario': id_usuario
            };

            remover_actives();

            // Iniciamos o Ajax 
            $.ajax({
                // Definimos a url
                url : 'views/vw_usuario.php',
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
                    $('#content-wrapper').html(data);
                    $('#page-constrole_usuario').addClass("active");
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

// Escopo da Declaração de Funções jQuery
(function($) {
  RemoveTableRow = function(handler) {
    var tr = $(handler).closest('tr');

    tr.fadeOut(400, function(){ 
      tr.remove(); 
    }); 

    return false;
  };
})(jQuery);
