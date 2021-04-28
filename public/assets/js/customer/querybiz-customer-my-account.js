var querybizCustomerMyAccount = {
    CONST_elMsg: null,
    CONST_requiredMsg: null,
    CONST_invalidMsg: null,
    init: function (options) {
        querybizCustomerMyAccount.CONST_requiredMsg = options.hasOwnProperty('requiredMessage') ? options.requiredMessage : '';
        querybizCustomerMyAccount.CONST_invalidMsg = options.hasOwnProperty('invalidMessage') ? options.invalidMessage : '';
        querybizCustomerMyAccount.CONST_elMsg = $('#customer-my-account-msg');
        var successMessage = options.hasOwnProperty('successMessage') ? options.successMessage : '';
        var errorMessage = options.hasOwnProperty('errorMessage') ? options.errorMessage : '';
        var invalidPassMessage = options.hasOwnProperty('invalidPassMessage') ? options.invalidPassMessage : '';
        var diferentPassMessage = options.hasOwnProperty('diferentPassMessage') ? options.diferentPassMessage : '';

        elMsg = querybizCustomerMyAccount.CONST_elMsg;

        this.toggleFormFields(null, true);

        $('.btn-add-data').click(function(){
            $('.btn-form-cancel').trigger('click');
            let form = $(this).data('form');
            let modal = $(this).data('modal');

            $('#' + modal).show();

            $('#modal_form').modal('show');
            $('.btn-form-submit').data('form', form).attr('disabled', false);

            querybizCustomerMyAccount.toggleFormFields($('#form_' + form), false);

            $('#modal_form').on('shown.bs.modal', function () {
                $('#form_' + form).find('input[type=text]:first').focus();
            });

            $('#modal_form').on('hidden.bs.modal', function () {
                $('#' + modal).hide();
            });

        });

        $( "body" ).on( "change", ".geo-countries", function() {
            let form = $(this).closest('form');
            $('.required').remove();
            $('input').removeClass('border-danger');
            if ($(this).val() == 177) {
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

        $( 'body').on( 'click', '.btn-add-address', function() {
            const button = $(this);
            form = $('#form_add_address');

            if(querybizCustomerMyAccount.checkEmptyFields(form)){
                return false;
            }

            if(!querybizCustomerMyAccount.isValidPtPostalCode(form)){
                return false;
            }

            querybiz.post(form, function(data) {
                window.location.reload();
            },
            function(data) {
                alert('error');
                querybiz.hideSpinnerButton(null, button);
            });
        });

        $( 'body').on( 'click', '.btn-add-contact', function() {
            const button = $(this);
            form = $('#form_add_contact');

            if(querybizCustomerMyAccount.checkEmptyFields(form)){
                return false;
            }

            querybiz.post(form, function(data) {
                window.location.reload();
            },
            function(data) {
                alert('error');
                querybiz.hideSpinnerButton(null, button);
            });
        });


        $('.btn-form-edit').click(function(){
            $('.btn-form-cancel').trigger('click');
            let form = $(this).closest('form');
            $(form).find('.btn-form-save').removeClass('d-none').attr('disabled', false);
            $(form).find('.btn-form-delete').attr('disabled', false).removeClass('d-none');
            $(form).find('.btn-form-cancel').removeClass('d-none');
            $(this).addClass('d-none');
            querybizCustomerMyAccount.toggleFormFields(form, false);
        });

        $('.btn-form-cancel').click(function(){
            $('.required').remove();
            $('.password-fields-container').addClass('d-none');
            $('input [type=password]').val('');
            $('input').removeClass('border-danger');
            $('.img-loader').remove();
            form = $(this).closest('form');
            $(form).find('.btn-form-save').addClass('d-none').attr('disabled', true);
            $(form).find('.btn-form-delete').addClass('d-none').attr('disabled', true);
            $(form).find('.btn-form-edit, .btn-form-change').removeClass('d-none');
            $(this).addClass('d-none');
            querybizCustomerMyAccount.toggleFormFields(form, true);
        });


        $('.btn-form-save').click(function(e){
            e.preventDefault();
            let button = $(this);
            let form = button.closest('form');

            if(querybizCustomerMyAccount.checkEmptyFields(form)){
                return false;
            }

            if(!querybizCustomerMyAccount.isValidPtPostalCode(form)){
                return false;
            }

            querybiz.post($(form), function(data) {
                $.each($(form), function(key, val) {
                    if ($(val).attr('data-prevent-cancel')) {
                        $(val).attr('data-prevent-cancel', $(val).val());
                    }
                });
                $('.btn-form-cancel').trigger('click').addClass('d-none');
                elMsg.text(successMessage).removeClass('d-none');
                elMsg.fadeIn().delay(2000).fadeOut();

                querybiz.hideSpinnerButton(null, button);
            },
            function(data) {
                if (data.msg === 'invalid-password') {
                    elMsg.text(invalidPassMessage);
                } else if (data.msg === 'passwords-different') {
                    elMsg.text(diferentPassMessage);
                } else {
                    elMsg.text(errorMessage);
                }

                $(form).find('.btn-form-edit, .btn-form-change').trigger('click');
                elMsg.removeClass('d-none');
                elMsg.addClass('alert-danger').removeClass('alert-success');
                elMsg.addClass('font-weight-bold');
                elMsg.fadeIn().delay(5000).fadeOut(function () {
                    elMsg.removeClass('font-weight-bold');
                });
            });
        });

        $('.btn-form-delete').click(function(){
            $('.delete input[name=id]').val($(this).data('id'));
            $('.delete input[name=action]').val('delete_' + $(this).data('action'));
            $('#modal_delete').modal('show');
        });

        $('#modal_delete .btn-danger').click(function(){
            let elMsg = querybizCustomerMyAccount.CONST_elMsg;
            $('#modal_delete').modal('hide');
            let form = $('.delete');
            let id = $('.delete [name=id]').val();
            let removeItem = $('.delete [name="action"]').val().replace('delete_', '');

            querybiz.post(form, function(data) {
                $.each($(this), function(key, val) {
                    if ($(val).attr('data-prevent-cancel')) {
                        $(val).attr('data-prevent-cancel', $(val).val());
                    }
                });

                $('.' + removeItem + '-' + id).remove();
                $('.btn-form-cancel').trigger('click');
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

        $('.btn-form-change').click(function() {
            $('.btn-form-cancel').trigger('click');
            $('.password-fields-container').removeClass('d-none');
            let form = $(this).closest('form');
            $(form).find('.btn-form-save').removeClass('d-none').attr('disabled', false);
            $(form).find('.btn-form-delete').attr('disabled', false).removeClass('d-none');
            $(form).find('.btn-form-cancel').removeClass('d-none');
            $(this).addClass('d-none');
            querybizCustomerMyAccount.toggleFormFields(form, false);
        });
    },

    preventFormCancel: function(el) {
        let field = el.find('INPUT');
        $.each(field, function(key, val) {
            let attr = $(val).attr('data-prevent-cancel');
            if (typeof attr !== typeof undefined && attr !== false) {
                $(val).val($(val).attr('data-prevent-cancel'));
            } else {
                $(val).attr('data-prevent-cancel', $(val).val());
            }
        });
    },

    select2Workaround: function(){
        $('.geo-countries').trigger('change');
        setTimeout(function(){
            $('input.geo-pt-council, input.geo-pt-district, select.geo-pt-district, select.geo-pt-council').attr('disabled', true);
        }, 250);
    },

    toggleFormFields: function(form, edit = false) {
        let elements = 'input:not([type=hidden]):not(.d-none), select:not(.d-none)';
        let updateData = form ? $(form).find(elements) : $('form').find(elements);

        $.each(updateData, function (key, val) {
            $(val).removeClass('border-0').attr('disabled', edit);
        });

        elements = 'input.d-none, select.d-none';
        updateData = form ? $(form).find(elements) : $('form').find(elements);
        $(form).find('input[type=text]:first').focus();
    },

    checkEmptyFields:function(form){
        $('.required').remove();
        $('input').removeClass('border-danger');
        required = '<span class="not-valid required text-danger small float-right">'+this.CONST_requiredMsg+'</span>';
        elements = 'input[type=text]:required';
        emptyData = $(form).find(elements);

        emptyField = false;

        $.each(emptyData, function (key, val) {
            if(!$(val).val() && !$(val).hasClass('d-none')){
                emptyField = true;
                $(val).before(required).addClass('border-danger').focus();
            }
        });

        if(emptyField){
            $('.img-loader').remove();
            $(form).find('.btn-save').attr('disabled', false);
            $('.btn-new-data').attr('disabled', false);
            return true;
        }
        return false;
    },

    isValidPtPostalCode:function(form){

        $('.required').remove();
        isValid = true;

        if($(form).find('.geo-countries').val() != 177 ){
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

    isValidPtTin:function(form) {
        $('.required').remove();

        let isValid = true;

        if(!$(form).find('input[name=taxNumber]').length || !$(form).find('input[name=taxNumber]').val() ){
            return true;
        }

        let value = $(form).find('input[name=taxNumber]').val();
        let invalid = '<span class="not-valid required text-danger small float-right">' + this.CONST_invalidMsg + 'Ex. 123456789</span>';
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

        if (checkDigit !== Number(nif[8])) {
            isValid = false;
        }

        if (!isValid) {
            $(form).find('input[name=taxNumber]').before(invalid).addClass('border-danger');
            $('.spinner-border').remove();
            $(form).find('.btn-form-save').attr('disabled', false);
            $('.btn-new-data').attr('disabled', false);
            return false;
        }

        return true;
    }
};