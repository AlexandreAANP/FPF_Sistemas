var querybizCustomerOrder = {
    CONST_elMsg: null,
    init: function(options) {

        querybizCustomerOrder.CONST_elMsg = $('#customer-my-account-msg');
        var errorMessage = options.hasOwnProperty('errorMessage') ? options.errorMessage : '';
        var currentPage = options.hasOwnProperty('currentPage') ? options.currentPage : 0;
        var totalPages = options.hasOwnProperty('totalPages') ? options.totalPages : 0;

        $('.btn-view-order').click(function(){
            const button = $(this);
            form = button.closest('form');
            //$(this).append('<sup class="ml-1 spinner-border text-primary spinner-border-sm"></sup>');

            querybiz.post($(form), function(data) {
                //$('.spinner-border').remove();
                querybiz.hideSpinnerButton(null, button);
                $('.order-info-container').html(data);
                $('#modal_order_info').modal('show');

            }, function(data){
                //$('.spinner-border').remove();
                querybiz.hideSpinnerButton(null, button);
                $('.order-info-container').html(data);
                $('#modal_order_info').modal('show');

                elMsg.text(errorMessage).removeClass('d-none');
                elMsg.addClass('alert-danger').removeClass('alert-success');
                elMsg.addClass('font-weight-bold');
                elMsg.fadeIn().delay(5000).fadeOut(function () {
                    elMsg.removeClass('font-weight-bold');
                });
            },

            {'returnType': 'html'}
            );
        });

        $('[name=dateFrom], [name=dateTo]').change(function(){
            $('[name=page]').val('')
        });

        $('.btn-get-orders').click(function(){
            $(this).append('<sup class="ml-1 spinner-border spinner-border-sm"></sup>');
        });

        $('.btn-next').click(function(){
            let nextPage = Number(currentPage)+1;

            if(nextPage > totalPages) {
                return false;
            }
            $('input[name=page]').val(nextPage);
            $('.btn-get-orders').trigger('click');
            $('.btn-get-orders, .btn-next').attr('disabled', true);
        });

        $('.btn-previous').click(function(){
            let previousPage = Number(currentPage)-1;

            if(previousPage < 1) {
                return false;
            }
            $('input[name=page]').val(previousPage);
            $('.btn-get-orders').trigger('click');
            $('.btn-get-orders, .btn-previous').attr('disabled', true);
        });

    },
};