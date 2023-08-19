/* 
 * Eventos para forçar o foco ficar no botão "Fechar" e assim a tecla ESC funcionar
 * para fechar as modals de mensagem em exibição.
 */

            $('#modal-default').on('shown.bs.modal', function(event) {
                $('#default_close').focus();
            });

            $('#modal-warning').on('shown.bs.modal', function(event) {
                $('#warning_close').focus();
            });

            $('#modal-danger').on('shown.bs.modal', function(event) {
                $('#danger_close').focus();
            });

            $('#modal-info').on('shown.bs.modal', function(event) {
                $('#info_close').focus();
            });

            $('#modal-primary').on('shown.bs.modal', function(event) {
                $('#primary_close').focus();
            });

            $('#modal-success').on('shown.bs.modal', function(event) {
                $('#success_close').focus();
            });

