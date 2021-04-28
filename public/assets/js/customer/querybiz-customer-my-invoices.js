var querybizCustomerMyInvoices = {
    CONST_elMsg: null,
    CONST_requiredMsg: null,
    CONST_invalidMsg : null,
    init: function (options) {

        querybizCustomerMyInvoices.CONST_elMsg = $('#customer-my-account-msg');
        querybizCustomerMyInvoices.CONST_requiredMsg = options.hasOwnProperty('requiredMessage') ? options.requiredMessage : '';
        querybizCustomerMyInvoices.CONST_invalidMsg = options.hasOwnProperty('invalidMessage') ? options.invalidMessage : '';

        var successMessage = options.hasOwnProperty('successMessage') ? options.successMessage : '';
        var errorMessage = options.hasOwnProperty('errorMessage') ? options.errorMessage : '';

        this.toggleFormFields(null,true);

        $('.btn-add-data').click(() =>{
            $('.btn-cancel').trigger('click');
            $('.spinner-border').remove();
            $('#modal_form').modal('show');
            $('.btn-new-data').attr('disabled', false);
            this.toggleFormFields($('.add-invoice'), false);
            $('#modal_form').on('shown.bs.modal', function () {
                $('.add-invoice').find('input[type=text]:first').focus();
            });
        })

        // 177 is Portugal, disable inputs and enable selects
        $( "body" ).on( "change", ".geo-countries", function() {
            let form = $(this).closest('form');
            $('.required').remove();
            $('input').removeClass('border-danger');
            if ($(this).val() == arDefaultOptions['countryBaseId'] ?? 177) {
                $(form).find('input.geo-pt-council, input.geo-pt-district').addClass('d-none').attr('disabled', true);
                $(form).find('select.geo-pt-council, select.geo-pt-district').removeClass('d-none').attr('disabled', false);
                $(form).find('select.geo-pt-council, select.geo-pt-district').next().removeClass('d-none');
            } else {
                $(form).find('input.geo-pt-district, input.geo-pt-council').removeClass('d-none').attr('disabled', false);
                $(form).find('select.geo-pt-district, select.geo-pt-council').addClass('d-none').attr('disabled', true);
                $(form).find('select.geo-pt-council, select.geo-pt-district').next().addClass('d-none');
            }
        });

        this.select2Workaround();

        $('.btn-edit').click(function(){
            $('.btn-cancel').trigger('click');
            form = $(this).closest('form');
            $(form).find('.btn-save').removeClass('d-none');
            $(form).find('.btn-cancel').removeClass('d-none');
            $(form).find('.btn-delete').removeClass('d-none');
            $(this).addClass('d-none');
            querybizCustomerMyInvoices.toggleFormFields(form, false);
        });

        $('.btn-cancel').click(function(){
            $('.required').remove();
            $('input').removeClass('border-danger');
            $('.spinner-border').remove();
            form = $(this).closest('form');
            $(form).find('.btn-save').addClass('d-none').attr('disabled', false);
            $(form).find('.btn-save').addClass('d-none');
            $(form).find('.btn-edit').removeClass('d-none');
            $(form).find('.btn-delete').addClass('d-none');
            $(this).addClass('d-none');
            querybizCustomerMyInvoices.toggleFormFields(form, true);
        });

        $('.btn-save').click(function(){

            elMsg = querybizCustomerMyInvoices.CONST_elMsg;
            form = $(this).closest('form');
            $('.spinner-border').remove();
            html = $(this).html();
            loader ='<sup class="ml-1 spinner-border spinner-border-sm"></sup>';
            $(form).find('.btn-save').removeClass('d-none');
            $(form).find('.btn-cancel').removeClass('d-none');
            $(form).find('.btn-delete').removeClass('d-none');
            $(this).html(html+loader).prop('disabled', true);

            if(querybizCustomerMyInvoices.checkEmptyFields(form)){
                return false;
            }

            if(!querybizCustomerMyInvoices.isValidPtPostalCode(form)){
                return false;
            }

            if(!querybizCustomerMyInvoices.isValidPtTin(form)){
                return false;
            }

            querybiz.post($(form), function(data) {
                $.each($(form), function(key, val) {
                    if ($(val).attr('data-prevent-cancel')) {
                        $(val).attr('data-prevent-cancel', $(val).val());
                    }
                });
                $('.btn-cancel').trigger('click');
                elMsg.text(successMessage).removeClass('d-none');
                elMsg.fadeIn().delay(2000).fadeOut();

            },
            function(data) {
                $('.spinner-border').remove();
                $(this).html(html).prop('disabled', true);
                elMsg.text(errorMessage).removeClass('d-none');
                elMsg.addClass('alert-danger, font-weight-bold');
                elMsg.fadeIn().delay(5000).fadeOut(function () {
                    elMsg.removeClass('alert-danger, font-weight-bold');
                });
            });
        });

        $('.btn-new-data').click(function(){

            elMsg = querybizCustomerMyInvoices.CONST_elMsg;
            $('.spinner-border').remove();
            html = $(this).html();
            loader ='<sup class="ml-1 spinner-border spinner-border-sm"></sup>';
            $(this).html(html+loader).prop('disabled', true);
            form = $('.add-invoice');

            if(querybizCustomerMyInvoices.checkEmptyFields(form)){
                return false;
            }

            if(!querybizCustomerMyInvoices.isValidPtTin(form)){
                return false;
            }

            if(!querybizCustomerMyInvoices.isValidPtPostalCode(form)){
                return false;
            }

            querybiz.post($(form), function(data) {
                $.each($(form), function(key, val) {
                    if ($(val).attr('data-prevent-cancel')) {
                        $(val).attr('data-prevent-cancel', $(val).val());
                    }
                });
                $('#modal_form').modal('hide');
                $('.spinner-border').remove();
                elMsg.text(successMessage).removeClass('d-none');
                //elMsg.fadeIn().delay(2000).fadeOut();
                $('body').append('<sup style="position:fixed;right:34px;top:78px;index:99999" class="ml-1 spinner-border spinner-border-lg text-primary"></sup>');
                setTimeout(function(){
                   location.reload();
                }, 2000);

            }, function(data) {
                elMsg.text(errorMessage).removeClass('d-none');
                elMsg.addClass('alert-danger').removeClass('alert-success');
                elMsg.addClass('font-weight-bold');
                elMsg.fadeIn().delay(5000).fadeOut(function () {
                    elMsg.removeClass('alert-danger').addClass('alert-success');
                    elMsg.removeClass('font-weight-bold');
                });
            });
        });

        $('.btn-delete').click(function(){
            form = $(this).closest('form');
            $('input[name=invoiceCustomerId]').val($(form).data('id'));
            $('#modal_delete').modal('show');
        });

        $('#modal_delete .btn-danger').click(function(){
            elMsg = querybizCustomerMyInvoices.CONST_elMsg;
            $('#modal_delete').modal('hide');
            form = $('.delete-invoice');
            id = $('.delete-invoice [name=invoiceCustomerId]').val();

            querybiz.post(form, function(data) {
                $.each($(this), function(key, val) {
                    if ($(val).attr('data-prevent-cancel')) {
                        $(val).attr('data-prevent-cancel', $(val).val());
                    }
                });

                $('.invoice-'+id).remove();
                elMsg.text(successMessage).removeClass('d-none');
                elMsg.fadeIn().delay(2000).fadeOut();
            },
            function(data) {
                elMsg.text(errorMessage).removeClass('d-none');
                elMsg.addClass('alert-danger').removeClass('alert-success');
                elMsg.addClass('font-weight-bold');
                elMsg.fadeIn().delay(5000).fadeOut(function () {
                    elMsg.removeClass('alert-danger').addClass('alert-success');
                    elMsg.removeClass('font-weight-bold');
                });
            });
        });
    },

    toggleFormFields:function(form, edit = false){

        elements = 'input:not([type=hidden]):not(.d-none), select:not(.d-none)';
        updateData = form ? $(form).find(elements) : $('form').find(elements);

        $.each(updateData, function (key, val) {
            $(val).removeClass('border-0').attr('disabled', edit);
        });

        elements = 'input.d-none, select.d-none';
        updateData = form ? $(form).find(elements) : $('form').find(elements);

        $.each(updateData, function (key, val) {
            $(val).removeClass('border-0').attr('disabled', true);
        });

        $(form).find('input[type=text]:first').focus();
    },

    select2Workaround: function(){
        $('.geo-countries').trigger('change');
        setTimeout(function(){
            $('input.geo-pt-council, input.geo-pt-district, select.geo-pt-district, select.geo-pt-council').attr('disabled', true);
        }, 250);
    },

    checkEmptyFields:function(form){
        $('.required').remove();
        $('input').removeClass('border-danger');
        required = '<span class="not-valid required text-danger small float-right">'+this.CONST_requiredMsg+'</span>';
        elements = 'input[type=text]:required';
        emptyData = $(form).find(elements);

        emptyField = false;
        focus = false;

        $.each(emptyData, function (key, val) {
            if(!$(val).val() && !$(val).hasClass('d-none')){
                emptyField = true;
                $(val).before(required).addClass('border-danger');

                //Set first input on focus
                if(!focus){
                    val.focus();
                    focus = true;
                }
            }
        });

        if(emptyField){
            $('.spinner-border').remove();
            $(form).find('.btn-save').attr('disabled', false);
            $('.btn-new-data').attr('disabled', false);
            return true;
        }
        return false;
    },


    isValidPtPostalCode:function(form){

        $('.required').remove();
        isValid = true;

        if($(form).find('.geo-countries').val() != 177){
            return true;
        }

        value = $(form).find('input[name=postalCode]').val();
        invalid = '<span class="not-valid required text-danger small float-right">'+this.CONST_invalidMsg+' Ex. 0000-000</span>';

        var patt = /^\d{4}-\d{3}?$/gm;

        if(!value.match(patt)){
            $(form).find('input[name=postalCode]').before(invalid).addClass('border-danger').focus();
            $('.spinner-border').remove();
            $(form).find('.btn-save').attr('disabled', false);
            $('.btn-new-data').attr('disabled', false);
            return false;
        }

        return true;
    },

    isValidPtTin:function(form){

        $('.required').remove();
        isValid = true;

        if($(form).find('.geo-countries').val() != 177 || !$(form).find('input[name=taxNumber]').length){
            return true;
        }

        value = $(form).find('input[name=taxNumber]').val();

        invalid = '<span class="not-valid required text-danger small float-right">'+this.CONST_invalidMsg+' Ex. 123456789</span>';
        const nif = typeof value === 'string' ? value : value.toString();
        const validationSets = {
            one: ['1', '2', '3', '5', '6', '8'],
            two: ['45', '70', '71', '72', '74', '75', '77', '79', '90', '91', '98', '99']
        };

        if (nif.length !== 9){
            isValid = false;
        }

        if (!validationSets.one.includes(nif.substr(0, 1)) && !validationSets.two.includes(nif.substr(0, 2))) {
            isValid = false;
        }

        const total = nif[0] * 9 + nif[1] * 8 + nif[2] * 7 + nif[3] * 6 + nif[4] * 5 + nif[5] * 4 + nif[6] * 3 + nif[7] * 2;
        const modulo11 = (Number(total) % 11);
        const checkDigit = modulo11 < 2 ? 0 : 11 - modulo11;

        if(checkDigit !== Number(nif[8])){
            isValid = false;
        }

        if(!isValid){
            $(form).find('input[name=taxNumber]').before(invalid).addClass('border-danger');
            $('.spinner-border').remove();
            $(form).find('.btn-save').attr('disabled', false);
            $('.btn-new-data').attr('disabled', false);
            return false;
        }

        return true;
    }

};
