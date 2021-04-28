var querybiz = {
	CONST_baseUri: '',
	CONST_apiUrl: '',
	CONST_cdnUrl: '',
	CONST_messageContainer: '',
	CONST_showSocialMediaIcons: 'slideDown', // slideDown, expand, fadeIn
	lastTextLoadingButton: [],
	TloopSocialMediaAnimation: 0,

	lastModalProductItemStockId: 0,
	lastModalProductItemStockColorPosition: null,
	lastModalProductItemStockSizePosition: null,
	arProductItemsStockColor: [],
	arProductItemsStockColorHex: [],
	arProductItemsStockSize: [],

// ########### SweetAlert2 Icons: success: error, warning, info, question ################ //

	init: function(options) {
		querybiz.CONST_baseUri = options.hasOwnProperty('baseUri') ? options.baseUri : '';
		querybiz.CONST_apiUrl = options.hasOwnProperty('apiUrl') ? options.apiUrl : '';
		querybiz.CONST_cdnUrl = options.hasOwnProperty('cdnUrl') ? options.cdnUrl : '';
		querybiz.CONST_messageContainer = options.hasOwnProperty('messageContainer') ? options.messageContainer : '';

		querybiz.listenCookieConsent();

		querybiz.addProductToCart();

		querybiz.listenBtnModalProdutSearch();
		querybiz.listenLoadingClickedElement();
		querybiz.contentSendmailTemplate();
		querybiz.redirectLogin();

		querybiz.hideLoadingPage();
	},

	hideLoadingPage: function() {
		$('#body_loading_first').fadeOut();
	},

	showLoadingPage: function() {
		$('#body_loading_first').fadeIn();
	},

	isMobile: function() {
		if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
			return true;
		}
		return false;
	},

	listenCookieConsent: function() {
		$('#cookie_consent').find('[data-action]').click(function (e) {
			e.preventDefault();
			$.post($(this).data('action'), {agree: true}, function (data) {
				$('#cookie_consent').slideUp('normal', function () {
					$(this).remove();
				});

				if (data.gtmHead) {
					$('head').prepend(data.gtmHead);
				}
				if (data.gtmBody) {
					$('body').prepend(data.gtmBody);
				}
			}, 'json');
		});
	},

	listenLoadingClickedElement: function() {
		$('a').on('click', function(e) {
			let prevText = $(this).text();
			let text = $(this).attr('data-loading-text');
			$(this).attr('prev-data-loading-text', prevText);
			if (typeof text === 'string' && text !== '') {
				querybiz.setTextLoading($(this), text);
			}
		});

		$('button').on('click', function(e) {
			let prevText = $(this).text();
			let text = $(this).attr('data-loading-text');
			$(this).attr('prev-data-loading-text', prevText);
			if (typeof text === 'string' && text !== '') {
				querybiz.setTextLoading($(this), text);
			}
		});

		$('form').on('submit', function(e) {
			let btnSubmit = $(this).find(':submit');
			let prevText = btnSubmit.text();
			let text = btnSubmit.attr('data-loading-text');
			btnSubmit.attr('prev-data-loading-text', prevText);
			if (typeof text === 'string' && text !== '') {
				querybiz.setTextLoading(btnSubmit, text);
			}
		});

		// show button loading, put an spinner on it!
		// to remove the loading call the hideSpinnerButton() function
		$(document).on('click', '[data-spinner]', function () {
			const button = $(this);

			let spinnerStyle;
			switch (button.data('spinner')) {
				case 'border':
					spinnerStyle = 'border';
					break;
				default:
					spinnerStyle = 'grow';
			}

			const spinner = `<span class="spinner-${spinnerStyle} spinner-${spinnerStyle}-sm text-white mr-2" role="status" aria-hidden="true"></span>`;

			if (button.prop('tagName').toLowerCase() !== 'button') {
				button.attr({'tabindex': '-1', 'aria-disabled': 'true'});
			}

			button.html(spinner + button.text());
			//button.prop('disabled', true); // [BUG] faz com que a hideSpinnerButton() não funcione...
			button.css({opacity: 0.5, cursor: 'progress'});
		});
	},

	setLoadingClickedElement(el) {
		let isElementA = el.is('a');
		let isElementInput = el.is('input');
		let isElementButton = el.is('button');

		if (isElementA) {
			let prevText = el.text();
			let text = el.attr('data-loading-text');
			el.attr('prev-data-loading-text', prevText);
			if (typeof text === 'string' && text !== '') {
				querybiz.setTextLoading(el, text);
			}
		} else if (isElementInput) {
			let btnSubmit = el.find(':submit');
			let prevText = btnSubmit.text();
			let text = btnSubmit.attr('data-loading-text');
			btnSubmit.attr('prev-data-loading-text', prevText);
			if (typeof text === 'string' && text !== '') {
				querybiz.setTextLoading(btnSubmit, text);
			}
		} else if (isElementButton) {
			let prevText = el.text();
			let text = el.attr('data-loading-text');
			el.attr('prev-data-loading-text', prevText);
			if (typeof text === 'string' && text !== '') {
				querybiz.setTextLoading(el, text);
			}
		}
	},

	listenBtnModalProdutSearch: function() {
		$('.btn-open-modal-product-search').on('click', function() {
			let modalForm = $('#modal_product_search');

			modalForm.modal('show');

			let focused = $('#formProductSearch [name="search"]').attr('data-focused');
			if (!focused) {
				$('#formProductSearch [name="search"]').focus();
				$('#formProductSearch [name="search"]').attr('data-focused', 'true');
			}

			modalForm.unbind('shown.bs.modal');
			modalForm.on('shown.bs.modal', function () {
				$('#formProductSearch [name="search"]').focus();
			});
		});
	},

	contentSendmailTemplate: function() {
		$('.sendmail-template').on('submit', function(e) {
			e.preventDefault();

			const privacyPolicy = $(this).find('.privacy-policy-consent');
			if (privacyPolicy.length && !privacyPolicy.is(':checked')) {
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: querybiz.trans('You must agree to our Privacy Policy'),
				});
				return false;
			}

			const gdprConsent = $(this).find('.gdpr-consent');
			if (gdprConsent.length && !gdprConsent.is(':checked')) {
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: querybiz.trans('You must agree to our GDPR Privacy Policy'),
				});
				return false;
			}

			const termsConsent = $(this).find('.terms-consent');
			if (termsConsent.length && !termsConsent.is(':checked')) {
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: querybiz.trans('You must agree to our Terms and Conditions'),
				});
				return false;
			}

			var formId = $(this).attr('id');
			var formSendId = formId + '_sent';
			var formSubmitId = formId + '_submit';
			var formButtonBack = formId + '_form_back';

			if (querybiz.formValidated(e, $(this), {'formSubmitId': formSubmitId}) === false) {
				return false;
			}

			querybiz.post($(this), function(data) {
				$('#' + formId).hide();
				$('#' + formSendId).fadeIn();
				querybiz.stopLoadingText(formSubmitId);
				querybiz.hideSpinnerButton(formSubmitId);

				$('#' + formButtonBack).off('click');
				$('#' + formButtonBack).on('click', function() {
					$('#' + formSendId).hide();
					$('#' + formId).fadeIn();

					$('#' + formId).each(function () {
						this.reset();
					});
				});

			}, function(data) {
				querybiz.stopLoadingText(formSubmitId);
				querybiz.hideSpinnerButton(formSubmitId);

				let msg = data.msg;
				if (msg == 'invalid-input-response') {
					msg = querybiz.trans('Invalid reCAPTCHA');
				}

				Swal.fire('Atention!', msg, 'warning');
				return false;
			});
		});
	},

	redirectLogin: function () {
		$('.btn-login').on('click', function(e) {
			e.preventDefault();

			const swalWithBootstrapButtons = Swal.mixin({
				customClass: {
					confirmButton: 'btn btn-success ml-2',
					denyButton: 'btn btn-info ml-2',
					cancelButton: 'btn btn-danger',
				},
				buttonsStyling: false
			});

			swalWithBootstrapButtons.fire({
				title: 'Login is required to proceed!',
				showDenyButton: true,
				showCancelButton: true,
				text: 'Please choose one option?',
				icon: 'warning',
				confirmButtonText: 'Login',
				cancelButtonText: 'Close',
				denyButtonText: 'Create Account',
				reverseButtons: true

			}).then((result) => {
				if (result.isConfirmed) {
					let redirect = arDefaultOptions['requestUriB64'];
					document.location.href = '/login/' + redirect;
				}
				if (result.isDenied) {
					document.location.href = '/customer/signup';
				}
			});
		});
	},

	formValidated: function(e, self, options = null) {
		let fields = self.find('input,textarea,checkbox,radiobox');

		let ret = true;
		for (let i = 0; i < fields.length; i++) {
			if ($(fields[i]).prop('required') && $(fields[i]).val() == '') {
				fields[i].focus();
				ret = false;
				break;
			}
		}

		if (!ret) {
			if (options && options.hasOwnProperty('formSubmitId')) {
				querybiz.stopLoadingText(options.formSubmitId);
				querybiz.hideSpinnerButton(formSubmitId);
			}

			Swal.fire('Atention!', 'There are required fields not filled in!', 'warning');
			return false;
		}

		return ret;
	},

	addProductToCart: function() {
		$('.btn-add-product-cart').unbind();
		$('.btn-add-product-cart').click(function(e) {
			e.preventDefault();
			var btnSelf = $(this);
			var productCardRandom = $(this).data('product-card-random');

			let productItemStockId = $(this).attr('data-product-item-stock-id');

			if ($(this).hasClass('modal-product-color-size')) {
				let arColorStockId = [];
				let arSizeStockId = [];

				if (querybiz.lastModalProductItemStockColorPosition !== null) {
					arColorStockId = $('#modal_product_specification .modal-product-item-stock-color[data-color-position="' + querybiz.lastModalProductItemStockColorPosition + '"]').data('stock-id');
				}

				if (querybiz.lastModalProductItemStockSizePosition !== null) {
					arSizeStockId = $('#modal_product_specification .modal-product-item-stock-size[data-size-position="' + querybiz.lastModalProductItemStockSizePosition + '"]').data('stock-id');
				}

				let itemStockId = 0;
				for (let i = 0; i < arColorStockId.length; i++) {
					for (let j = 0; j < arSizeStockId.length; j++) {
						if (arColorStockId[i] === arSizeStockId[j]) {
							itemStockId = arColorStockId[i];
						}
					}
				}

				productItemStockId = itemStockId;
			}

			if (!productItemStockId) {
				alert('There is no Product Selected');

			} else {
				if ($(this).hasClass('modal-product-color-size')) {
					let isInModal = $(this).closest('.modal-content');

					let btnSubmitMarginTop = -32;
					if (isInModal.length) {
						btnSubmitMarginTop = -5;
					}

					let btnSubmitWidth = parseInt($(this).width()) + parseInt($(this).css('padding-left')) + parseInt($(this).css('padding-right')) + 20;
					let btnSubmitHeight = parseInt($(this).height()) + 20;
					btnSelf.closest('div').prepend('<div id="btn_add_to_cart_loading" style="position: absolute; margin-top: ' + btnSubmitMarginTop + 'px; margin-left: -10px; border-radius: 5px; padding: 12px; width: ' + btnSubmitWidth + 'px; height: ' + btnSubmitHeight + 'px; background-color: rgba(245, 245, 245, 1); opacity: .4; text-align: center"><div class="spinner-border spinner-border-sm" role="status"><span class="sr-only">Loading...</span></div></div>');
				}

				btnSelf.prop('disabled', true);

				let htmlLoading = '<div class="products-card-image-loading d-flex w-100 h-100 justify-content-center align-items-center" style="background-color: rgba(245, 245, 245, 1); opacity: .4">' +
					'            	<div class="spinner-border" role="status">' +
					'               	<span class="sr-only">Loading...</span>' +
					'            	</div>' +
					'        	</div>';

				var productCard = $(this).closest('.products-card-overflowed').siblings('.products-card-wrapper').find('.products-card-image');
				productCard.append(htmlLoading);

				var url = '/addProductToCart?id=' + productItemStockId;

				querybiz.post(url, function(data) {
					productCard.find('.products-card-image-loading').remove();
					btnSelf.closest('div').find('#btn_add_to_cart_loading').remove();
					btnSelf.prop('disabled', false);

					const swalWithBootstrapButtons = Swal.mixin({
						customClass: {confirmButton: 'btn btn-success ml-2', cancelButton: 'btn btn-danger'},
						buttonsStyling: false
					});
					swalWithBootstrapButtons.fire({
						title: 'Product successfully added!',
						text: 'Do you want to go to the cart or stay on this page?',
						icon: 'success',
						showCancelButton: true,
						confirmButtonText: 'Go to the cart!',
						cancelButtonText: 'stay',
						reverseButtons: true
					}).then((result) => {
						if (result.isConfirmed) {
							document.location.href = arDefaultOptions['baseUri'] + '/product-cart';
						}
					});

				}, function(data) {
					productCard.find('.products-card-image-loading').remove();
					btnSelf.closest('div').find('#btn_add_to_cart_loading').remove();
					btnSelf.prop('disabled', false);

					if (data.hasOwnProperty('msg')) {
						const swalWithBootstrapButtons = Swal.mixin({
							buttonsStyling: true
						});
						swalWithBootstrapButtons.fire({
							title: 'Attention',
							text: data.msg,
							icon: 'info',
						}).then((result) => {
							//
						});
					}
				});
			}
		});

		$('.btn-modal-product-stock-zero').click(function() {
			alert('sem estoque');
		});

		$('.btn-modal-product-specification').click(function () {
			let productId = $(this).data('product-id');

			let modalForm = $('#modal_product_specification');
			modalForm.modal('show');
			$('BODY').css('padding-right', '0');

			querybiz.getProductItemStock(productId);

			modalForm.unbind('shown.bs.modal');
			modalForm.on('shown.bs.modal', function () {
			});

			/*
			var el = document.getElementById('layout_modal_items');
			var frameWin = querybizUtil.getIframeWindow(el);

			if (frameWin) {
				frameWin.openModal(productId);
			}*/
		});
	},

	getProductItemStock: function(productId) {
		let win = window.document; // let win = window.parent.document;
		$('#modal_product_specification', win).find('.modal-title').text(querybiz.trans('Loading Product Items...'));
		$('#modal_product_specification', win).find('.modal-body').html('<div class="text-center p-4"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');

		querybiz.lastModalProductItemStockId = 0;
		querybiz.lastModalProductItemStockColor = 0;
		querybiz.lastModalProductItemStockSize = 0;

		$('#modal_product_specification', win).find('.btn-add-product-cart').attr('data-product-item-stock-id', '');
		$('#modal_product_specification', win).find('.btn-add-product-cart').prop('disabled', false);

		querybiz.post('/getProductItemStock/' + productId, function(data) {
			let html = querybiz.buildLayoutModalProductItemStock(data);
			let productName = data.name;

			$('#modal_product_specification', win).find('.modal-title').text(productName);
			$('#modal_product_specification', win).find('.modal-body').html(html);

			querybiz.listenerModalProductItemStock();

			$($('.modal-product-item-stock-color', win)[0]).click();

		}, function(data) {
			alert('erro');
		});
	},

	buildLayoutModalProductItemStock: function(data) {
		let arColItems = data.colItems;

		querybiz.arProductItemsStockColor = [];
		querybiz.arProductItemsStockColorHex = [];
		querybiz.arProductItemsStockSize = [];

		for (let i in arColItems) {
			let stockId = arColItems[i].id;
			let color = arColItems[i].color;
			let colorHex = arColItems[i].colorHex;
			let size = arColItems[i].size;
			let price = arColItems[i].price;
			let image = arColItems[i].image;

			if (!querybiz.arProductItemsStockColor.hasOwnProperty(color)) {
				querybiz.arProductItemsStockColorHex[color] = colorHex;
				querybiz.arProductItemsStockColor[color] = [];
			}
			querybiz.arProductItemsStockColor[color].push({stockId: stockId, size: size});

			if (!querybiz.arProductItemsStockSize.hasOwnProperty(size)) {
				querybiz.arProductItemsStockSize[size] = [];
			}
			querybiz.arProductItemsStockSize[size].push({stockId: stockId, color: color});
		}

		let html = '<div>';

		let arKeyColorDefault = [];
		if (typeof querybiz.arProductItemsStockColor === 'object') {
			let objKey = Object.keys(querybiz.arProductItemsStockColor);

			if (objKey.length) {
				html += '<div class="d-flex" id="modal_product_item_stock_color_list">';
				for (let i = 0; i < objKey.length; i++) {
					let key = objKey[i];
					let arColor = querybiz.arProductItemsStockColor[key];
					let hex = querybiz.arProductItemsStockColorHex[key];

					let bgcolor = i === 0 ? '#eee' : 'transparent';

					if (i === 0) {
						arKeyColorDefault = arColor;
						querybiz.lastModalProductItemStockColorPosition = 0;
					}

					let strDataStockId = '';
					for (let i = 0; i < arColor.length; i++) {
						if (strDataStockId !== '') {
							strDataStockId += ',';
						}
						strDataStockId += arColor[i].stockId;
					}
					strDataStockId = '[' + strDataStockId + ']';

					html += '<div data-color="' + key + '" class="modal-product-item-stock-color border card p-2 m-1" data-color-position="' + i + '" data-stock-id="' + strDataStockId + '" style="cursor: pointer; background-color: ' + bgcolor + '">';
					html += '	<div class="d-block w-100 text-center">';
					html += '		<div class="d-inline-block rounded-circle mr-2 border" style="width: 20px; height: 20px; background-color: ' + hex + '" title="' + key + '"></div>';
					html += '	</div>';
					html += '	<div class="d-block w-100 text-center">';
					html += 		key;
					html += '	</div>';
					html += '</div>';
				}
				html += '</div>';
			}

		}

		if (typeof querybiz.arProductItemsStockSize === 'object') {
			let objKey = Object.keys(querybiz.arProductItemsStockSize);

			if (objKey.length) {
				html += '<div class="d-flex" id="modal_product_item_stock_size_list">';
				for (let i = 0; i < objKey.length; i++) {
					let key = objKey[i];
					let arSize = querybiz.arProductItemsStockSize[key];

					let bgcolor = 'transparent';
					let textColor = '#ccc';
					let textBold = 'normal';

					let strDataStockId = '';
					for (let i = 0; i < arSize.length; i++) {
						if (strDataStockId !== '') {
							strDataStockId += ',';
						}
						strDataStockId += arSize[i].stockId;
					}
					strDataStockId = '[' + strDataStockId + ']';

					html += '<div data-color="' + key + '" data-stock-id="' + strDataStockId + '" data-size-position="' + i + '" class="modal-product-item-stock-size border card p-2 px-3 m-1" style="cursor: default; background-color: ' + bgcolor + '; color: ' + textColor + '; font-weight: ' + textBold + '">' + key + '</div>';
				}
				html += '</div>';
			}
		}

		return html;
	},

	listenerModalProductItemStock: function() {
		let win = window.document; // let win = window.parent.document;

		$('.modal-product-item-stock-color', win).on('click', function() {
			let arStockId = [];
			arStockId = $(this).data('stock-id');

			$('.modal-product-item-stock-size[data-size-position="' + querybiz.lastModalProductItemStockSizePosition + '"]', win).css('background', 'transparent');

			let firstSelected = false;

			let arSizeList = $('#modal_product_item_stock_size_list').find('.modal-product-item-stock-size');

			for (let j = 0; j < arSizeList.length; j++) {
				$('.modal-product-item-stock-size[data-size-position="' + j + '"]', win).css('background', 'transparent');
				$('.modal-product-item-stock-size[data-size-position="' + j + '"]', win).css('color', '#ccc');
				$('.modal-product-item-stock-size[data-size-position="' + j + '"]', win).css('cursor', 'default');
				$('.modal-product-item-stock-size[data-size-position="' + j + '"]', win).css('font-weight', 'normal');
			}

			for (let i = 0; i < arStockId.length; i++) {
				let search = arStockId[i];

				for (let j = 0; j < arSizeList.length; j++) {
					let strStockIdSize = $(arSizeList[j]).data('stock-id');
					if (strStockIdSize.indexOf(search) !== -1) {
						if (!firstSelected) {
							$('.modal-product-item-stock-size[data-size-position="' + j + '"]', win).css('background', '#eee');

							firstSelected = true;
							querybiz.lastModalProductItemStockSizePosition = j;
						}
						$('.modal-product-item-stock-size[data-size-position="' + j + '"]', win).css('color', '#000');
						$('.modal-product-item-stock-size[data-size-position="' + j + '"]', win).css('font-weight', 'bold');
						$('.modal-product-item-stock-size[data-size-position="' + j + '"]', win).css('cursor', 'pointer');
					}
				}
			}

			let colorPosition = $(this).data('color-position');
			if (colorPosition !== querybiz.lastModalProductItemStockColorPosition) {
				$(this).css('background', '#eee');
				$('.modal-product-item-stock-color[data-color-position="' + querybiz.lastModalProductItemStockColorPosition + '"]', win).css('background', 'transparent');
				querybiz.lastModalProductItemStockColorPosition = colorPosition;
			}
		});

		$('.modal-product-item-stock-size', win).on('click', function() {
			let sizePosition = $(this).data('size-position');
			let cursor = $(this).css('cursor');
			if (sizePosition !== querybiz.lastModalProductItemStockSizePosition && cursor === 'pointer') {
				$(this).css('background', '#eee');
				$('.modal-product-item-stock-size[data-size-position="' + querybiz.lastModalProductItemStockSizePosition + '"]', win).css('background', 'transparent');
				querybiz.lastModalProductItemStockSizePosition = sizePosition;
			}
		});
	},

	showSocialMediaIcons: function() {
		$.getJSON(querybiz.CONST_APIUrl + '/api/getSocialMedia', function(data) {
			let html = '';

			let k = Object.keys(data);
			k.forEach(function(key) {
				let href = data[key].href;
				let src = data[key].src;
				let icon = data[key].icon;

				if (icon === 'email') {
					href = 'mailto:' + href;

				} else if (icon === 'whatsapp') {
					href = 'https://api.whatsapp.com/send?phone=' + href + '&text=';
				}

				html += '<a href="' + href + '" target="_blank" title="' + querybiz.trans('Click to go to') + ' ' + key.toLowerCase() + '"><img src="' + src + '" style="width: 0; margin-right: 2px; display: none"></a>';
			});

			$('#footer_social_media_icons').html(html);
			querybiz.showSocialMediaIconsAnimation();
		});
	},

	showSocialMediaIconsAnimation: function() {
		let children = $('#footer_social_media_icons').children('a');
		if (querybiz.CONST_showSocialMediaIcons === 'expand') {
			let total = children.length;
			querybiz.showSocialMediaIconsAnimationLoop('socialMedia', 0, total, function(cont) {
				let el = $(children[cont]).find('img');
				el.animate({
					opacity: 1,
					height: '28px',
					width: '28px'
				});
			});

		} else if (querybiz.CONST_showSocialMediaIcons === 'slideDown') {
			let total = children.length;
			children.find('img').css('width', '28px');
			querybiz.showSocialMediaIconsAnimationLoop('socialMedia', 0, total, function(cont) {
				let el = $(children[cont]).find('img');
				el.css('position','relative');
				el.css('margin-bottom','50px');
				el.show();
				el.animate({
					marginBottom: '0px',
				});
			});

		} else if (querybiz.CONST_showSocialMediaIcons === 'fadeIn') {
			children.find('img').css('width', '28px');
			children.find('img').fadeIn();
		}
	},

	showSocialMediaIconsAnimationLoop: function(Tloop, cont, total, callback, callend) {
		if (cont < total) {
			querybiz.TloopSocialMediaAnimation[Tloop] = setTimeout(function() {
				callback(cont);
				cont++;
				querybiz.showSocialMediaIconsAnimationLoop(Tloop, cont, total, callback, callend);

			}, 100);
		} else {
			clearTimeout(querybiz.TloopSocialMediaAnimation[Tloop]);
			if (callend) {
				callend();
			}
		}
	},

	errorMsg: function (arMsg, arErrorMsg = null, messageContainer = null) {
		let selfArErrorMsg = [];

		if (arErrorMsg && arErrorMsg.length > 0) {
			messageContainer = arErrorMsg;
		} else {
			arErrorMsg = [];
		}

		if (!messageContainer) {
			messageContainer = this.CONST_messageContainer;
		}

		if (typeof arMsg === 'string') {
			arMsg = [];
			arMsg['return'] = 'error';
			arMsg['msg'] = arMsg;
		}

		selfArErrorMsg['user not found'] = 'ucwords()';
		selfArErrorMsg['invalid-email'] = 'Email is not valid';
		selfArErrorMsg['passwords-different'] = 'The Password and Password Confirm can\'t be different';

		let errorKeys = Object.keys(arErrorMsg);
		errorKeys.forEach(function(val) {
			selfArErrorMsg[val] = arErrorMsg[val];
		});

		if (arMsg.return === 'error' && arMsg.hasOwnProperty('msg')) {
			querybiz.showTemporaryMsg(arMsg.msg, selfArErrorMsg, messageContainer);
		}
	},

	showFormMsg: function(msg, elemRef = null) {
		var strMsg = '<div class="layout-form-card-message">';

		strMsg += '    <div class="layout-form-card-message-title">' + trans('ALERT MESSAGE') + '</div>';
		strMsg += '    <div>';
		strMsg += '        <ul>';

		if (typeof msg === 'object') {
			var strMsgLi = '';
			for (let i = 0; i < msg.length; i++) {
				if (msg[i].trim() !== '') {
					strMsgLi += '        <li class="layout-form-card-message-row">' + msg[i] + '</li>';
				}
			}

			if (strMsgLi === '') {
				strMsgLi += '        <li class="layout-form-card-message-row">Unknown object error</li>';
			}

			strMsg += strMsgLi;

		} else {
			if (msg == '') {
				msg = 'Unknown error';
			}
			strMsg += '        <li class="layout-form-card-message-row">' + msg + '</li>';
		}
		strMsg += '        </ul>';
		strMsg += '    </div>';
		strMsg += '</div>';

		if (elemRef) {
			elemRef.find('.card-header').find('.card-title').hide();
			elemRef.find('.card-header').find('.card-message').html(strMsg);
			elemRef.find('.card-header').find('.card-message').show();

		} else {
			$('.card-header').find('.card-title').hide();
			$('.card-header').find('.card-message').html(strMsg);
			$('.card-header').find('.card-message').show();

			$(window).scrollTop(0);
		}
	},

	showTemporaryMsg: function(arMsg, arErrorMsg, messageContainer = null) {
		let duration = 3000;
		let html = '';
		let maxTime = 0;

		if (!arErrorMsg) {
			arErrorMsg = [];
		}

		if (typeof arMsg === 'object') {
			arMsg.forEach(function (val) {
				let msg = '';
				if (arErrorMsg.hasOwnProperty(val)) {
					msg = arErrorMsg[val];
				}

				if (!msg) {
					msg = val;
				}

				if (msg === 'MSG-ERROR' || msg === 'MSG-SUCCESS') {
					html += '<li style="list-style-type: none; margin-left: -20px; font-weight: bold">' + querybiz.trans(msg) + '</li>';
				} else {
					html += '<li>' + msg + '</li>';
				}

				maxTime = maxTime + duration;
			});

			html = '<ul style="margin: 0; margin-left: -20px">' + html + '</ul>';

		} else if (typeof arMsg === 'string') {
			html = '<ul style="margin: 0; margin-left: -20px"><li>' + arMsg + '</li></ul>';
			maxTime = maxTime + duration;
		}

		if (!messageContainer) {
			messageContainer = querybiz.CONST_messageContainer;
		}

		if (messageContainer !== '') {
			messageContainer.html(html);

			if (messageContainer.attr('is-inline-block') == 1) {
				messageContainer.addClass('d-inline-block');
				messageContainer.hide();
			}

			messageContainer.fadeIn().delay(maxTime).fadeOut(function() {
				$(this).removeClass('d-inline-block');
				$(this).attr('is-inline-block', 1);
			});

		} else {
			dd(arMsg);
		}
	},

	setBgColorRGBA: function(el, alpha) {
		let bgCol = el.css('backgroundColor');
		bgCol = bgCol.replace('rgb', 'rgba');
		bgCol = bgCol.replace(')', ', ' + alpha + ')');

		querybiz.removeClassStartingWith(el, 'bg-');
		el.css('background-color', bgCol);
	},

	get: function(url, callbackSuccess = null, callbackError = null, options = null) {
		return this.request(url, 'GET', callbackSuccess, callbackError, options);
	},

	post: function(url, callbackSuccess = null, callbackError = null, options = null) {
		return this.request(url, 'POST', callbackSuccess, callbackError, options);
	},

	request: function(url, method = 'POST', callbackSuccess = null, callbackError = null, options = null) {
		var formData = null;

		var v_processData = true;
		var v_contentType = false;
		var v_cache = false;

		if (typeof url === 'object') {
			v_processData = false;

			formData = new FormData(url[0]);
			url = url.attr('action');

		} else if (typeof url === 'string' && url.indexOf('?') !== -1) {
			v_contentType = 'application/x-www-form-urlencoded; charset=UTF-8';

			formData = url.substring(url.indexOf('?') + 1, url.length);
			url = url.substring(0, url.indexOf('?'));

		} else {
			formData = '';
		}

		if (method === 'GET') {
			v_processData = null;
			v_contentType = null;
			v_cache = null;
		}

		var returnType = 'json';
		if (options && options.hasOwnProperty('returnType') && options.returnType !== '') {
			returnType = options.returnType;
		}

		$.ajax({
			url: querybiz.CONST_baseUri  + url,
			type: method,
			data: formData,

			processData: v_processData,
			contentType: v_contentType,
			cache: v_cache,

			beforeSend: function() {
				//$("body").addClass("form_disabled");
			},
			success: function(data) {
				//$("body").removeClass("form_disabled");
				if (returnType === 'json' && typeof (data) === 'object') {
					if (data.return == 'success') {
						if (callbackSuccess && callbackSuccess !== '') {
							callbackSuccess(data);
						}
					} else if (data.return == 'error') {
						if (callbackError && callbackError !== '') {
							callbackError(data);
						}
					} else {
						console.log('Nothing to do ;-)');
					}
				} else if (returnType == 'html') {
					if (callbackSuccess && callbackSuccess !== '') {
						callbackSuccess(data);
					}

				} else {
					querybiz.showTemporaryMsg('error-data-type');
				}
			},
			error:function(data) {
				//$("body").removeClass("form_disabled");

				querybiz.showTemporaryMsg('error-ajax-post');
			}
		});
	},

	removeClassStartingWith: function (el, className) {
		let arBootstrapClassGroup = [];
		arBootstrapClassGroup['btn-'] = [
			'btn-primary', 'btn-secondary', 'btn-success', 'btn-danger', 'btn-warning', 'btn-info', 'btn-light', 'btn-dark', 'btn-link',
			'btn-outline-primary', 'btn-outline-secondary', 'btn-outline-success', 'btn-outline-danger', 'btn-outline-warning', 'btn-outline-info', 'btn-outline-light', 'btn-outline-dark', 'btn-outline-link',
		];

		arBootstrapClassGroup['bg-'] = [
			'bg-primary', 'bg-secondary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-light', 'bg-dark', 'bg-white',
		];

		let classList = el.attr('class');
		let arClassList = classList.split(' ');
		arClassList.forEach (function (name) {
			if (arBootstrapClassGroup[className].indexOf(name) !== -1) {
				el.removeClass(name);
			}
		});
	},

	setTextLoading: function(el, text) {
		this.setLoadingLoop('text', text, el);
	},

	setLoadingLoop: function(prop, text, el, dot) {
		if (!dot) {
			dot = '.';
		}

		let newText = '';
		newText += '<div class="d-block position-absolute">';
		newText += '	<span class="d-inline-block float-right" style="margin-top: -4px; height: 10px; font-size: 5px"><img src="/assets/images/btn-loading.gif" style="object-fit: cover; width: 40px; height: 10px; margin-right: -4px"></span>';
		newText += '	<div class="d-block w-100" style="visibility: hidden">' + text + '</div>';
		newText += '</div>';
		newText += '<div class="d-block" style="margin-bottom: -2px">' + text + '</div>';

		if (prop === 'text') {
			let isElementA = el.is('a');
			let isElementInput = el.is('input');
			let isElementButton = el.is('button');
			if (isElementInput || isElementButton || isElementA) {
				let id = el.attr('id');
				let elInput = document.getElementById(id);
				if (elInput) {
					let arAttr = this.getAttributes(elInput);

					if (!isElementA) {
						arAttr['element-type'] = isElementInput ? 'input' : 'button';
					}

					querybiz.lastTextLoadingButton = arAttr;

					let aButton = '<a';

					$.each(arAttr, function (key, val) {
						aButton += ' ' + key + '="' + val + '"';
					});

					let value = '';
					if (arAttr.hasOwnProperty('value')) {
						value = arAttr['value'];
					}
					aButton += '>' + value + '</a>';

					el.replaceWith(aButton);
					el = $('#' + id);
				}

			} else {
				querybiz.lastTextLoadingButton = [];
			}

			el.html(newText);
		}
	},

	stopLoadingText: function(elId) {
		if (querybiz.lastTextLoadingButton.hasOwnProperty('id') && querybiz.lastTextLoadingButton['id'] === elId) {
			let elementType = querybiz.lastTextLoadingButton['element-type'];

			let inputButton = '';

			if (elementType == 'a') {
				inputButton = '<a';

			} else if (elementType == 'button') {
				inputButton = '<button';

			} else {
				inputButton = '<input';
			}

			$.each(querybiz.lastTextLoadingButton, function(key, val) {
				inputButton += ' '  + key + '="' + val + '"';
			});
			inputButton += '>';

			$('#' + elId).replaceWith(inputButton);

			if (querybiz.lastTextLoadingButton.hasOwnProperty('prev-data-loading-text')) {
				$('#' + elId).text(querybiz.lastTextLoadingButton['prev-data-loading-text']);
			}
		}
	},

	hideSpinnerButton: function (elementId = null, button = null) {
		// button id or own button
		if (elementId) {
			button = $('#' + elementId);
		}

		if (button.prop('tagName').toLowerCase() !== 'button') {
			button.attr({'tabindex': '1', 'aria-disabled': 'false'});
		}

		button.html(button.text());
		button.prop('disabled', false);
		button.css({opacity: 1, cursor: 'pointer'});
	},

	getAttributes: function(node) {
		var i,
			attributeNodes = node.attributes,
			length = attributeNodes.length,
			attrs = {};

		for (let i = 0; i < length; i++) {
			attrs[attributeNodes[i].name] = attributeNodes[i].value;
		}

		return attrs;
	},

	trans: function(str) {
		let arTranslationsFile = [];

		if (arDefaultOptions.hasOwnProperty('translationFiles')) {
			if (arDefaultOptions['translationFiles'].hasOwnProperty(arDefaultOptions['language'])) {
				arTranslationsFile = arDefaultOptions['translationFiles'][arDefaultOptions['language']];
			}

			if (arTranslationsFile.hasOwnProperty(str)) {
				return arTranslationsFile[str];
			}
		}

		return str;
	}
};

var querybizCheckout = {
	CONST_elMsg: null,
	CONST_elMsgAuthLogin: null,
	CONST_elMsgAuthSignup: null,

	init: function (options) {
		querybizCheckout.CONST_elMsgAuthLogin = $('#checkout_auth_msg_login');
		querybizCheckout.CONST_elMsgAuthSignup = $('#checkout_auth_msg_signup');

		$('#form_login').on('submit',function(e) {
			e.preventDefault();

			let formSubmitId = 'form_checkout_login_submit';

			if (querybiz.formValidated(e, $(this), {'formSubmitId': formSubmitId}) === false) {
				return false;
			}

			querybiz.post($(this), function(data) {
				window.open(arDefaultOptions['baseUri'] + '/checkout', '_self');

			}, function(data) {
				querybiz.stopLoadingText(formSubmitId);
				querybiz.hideSpinnerButton(formSubmitId);
				querybiz.errorMsg(data, querybizCheckout.CONST_elMsgAuthLogin);
			});
		});

		$('#form_signup').on('submit',function(e) {
			e.preventDefault();

			let formSubmitId = 'form_checkout_signup_submit';

			if (querybiz.formValidated(e, $(this), {'formSubmitId': formSubmitId}) === false) {
				return false;
			}

			querybiz.post($(this), function(data) {
				window.open(arDefaultOptions['baseUri'] + '/checkout', '_self');

			}, function(data) {
				querybiz.stopLoadingText(formSubmitId);
				querybiz.hideSpinnerButton(formSubmitId);
				querybiz.errorMsg(data, querybizCheckout.CONST_elMsgAuthSignup);
			});
		});
	}
};

var querybizProduct = {
	CONST_listCategories: '',
	CONST_listProducts: '',
	CONST_shoppingType: '',

	init: function(options) {
		querybizProduct.CONST_listCategories = options.hasOwnProperty('listCategories') ? options.listCategories : '';
		querybizProduct.CONST_listProducts = options.hasOwnProperty('listProducts') ? options.listProducts : '';

		$('#spf_btn_list_categories').click(function() {
			querybizProduct.listCategories();
		});

		$('#spf_btn_list_products').click(function() {
			querybizProduct.listProducts();
		});

		$('#spf_btn_view_cart').click(function() {
			querybizProduct.viewCart();
		});

		$('#spf_btn_view_summary').click(function() {
			querybizProduct.viewSummary();
		});

		$('#btn_cart_checkout').click(function(e) {
			e.preventDefault();
			querybizProduct.checkout();
		});

		$('.btn-checkout-next').on('click', function(e) {
			e.preventDefault();

			let arCheckoutItems = $('#form_checkout_gateway .collapse-item');

			let itemOpened = 0;
			for (let i = 0; i < arCheckoutItems.length; i++) {
				let objItem = $(arCheckoutItems[i]);
				let isVisible = objItem.is(':visible');

				if (isVisible === true) {
					itemOpened = i;
				}
			}

			$(arCheckoutItems[itemOpened]).removeClass('show');
			let iconMinus = $(arCheckoutItems[itemOpened]).closest('.cart-box').find('.mdi:first');
			iconMinus.removeClass('mdi-minus');
			iconMinus.addClass('mdi-plus');
			$(arCheckoutItems[itemOpened]).closest('.cart-box').addClass('closed');

			$(arCheckoutItems[itemOpened + 1]).addClass('show');
			let iconPlus = $(arCheckoutItems[itemOpened + 1]).closest('.cart-box').find('.mdi:first');
			iconPlus.removeClass('mdi-plus');
			iconPlus.addClass('mdi-minus');
			$(arCheckoutItems[itemOpened + 1]).closest('.cart-box').removeClass('closed');

		});

		this.getItemsInCart();
	},

	initCart: function() {
		$('.btn-form-open-availability').click(function() {
			let productItemStockId = $(this).attr('data-product-item-stock-id');
			let quantity = $(this).attr('data-quantity');
			let dateCalendar = $(this).attr('data-date-calendar');
			let productName = $(this).closest('.order-info').find('.order-info-title').text();

			$('#modal_calendar_quantity').val(quantity);
			$('#modal_calendar_product_name').text(productName);
			$('#btn_modal_calendar_save').attr('data-product-item-stock-id', productItemStockId);

			let hourCalendar = dateCalendar.substring(dateCalendar.indexOf(' '), dateCalendar.length);
			hourCalendar = hourCalendar.trim();
			dateCalendar = dateCalendar.substring(0, dateCalendar.indexOf(' '));

			let calendarYear = 0;
			let calendarMonth = 0;
			let calendarDay = 0;
			if (dateCalendar.indexOf('-') !== -1) {
				let arDate = dateCalendar.split('-');

				calendarYear = parseInt(arDate[0]);
				calendarMonth = parseInt(arDate[1]) - 1;
				calendarDay = parseInt(arDate[2]);
			}

			$('#modal_calendar_time').val(hourCalendar);

			let objDatePicker = $('#modal_product_availability .date_picker');
			objDatePicker.datepicker('setDate', new Date(calendarYear,calendarMonth,calendarDay));

			$('#modal_product_availability').modal('show');

			$('#btn_modal_calendar_save').off();
			$('#btn_modal_calendar_save').on('click', function() {
				let objDate = $('#modal_product_availability .date_picker').datepicker('getDate');
				let calendarYear = objDate.getFullYear();
				let calendarMonth = objDate.getMonth();
				let calendarMonthView = calendarMonth;

				calendarMonth = calendarMonth + 1;

				if (calendarMonth < 10) {
					calendarMonth = '0' + calendarMonth;
					calendarMonthView = '0' + calendarMonthView;
				}
				let calendarDay = objDate.getDate();
				if (calendarDay < 10) {
					calendarDay = '0' + calendarDay;
				}

				let hour = $('#modal_calendar_time').val();

				let date = calendarYear + '-' + calendarMonth + '-' + calendarDay + ' ' + hour;
				let dateView = calendarDay + '/' + calendarMonthView + '/' + calendarYear + ' ' + hour;

				let quantity = $('#modal_calendar_quantity').val();
				let btnOpenAvailability = $('#product_' + productItemStockId + ' .btn-form-open-availability');
				btnOpenAvailability.attr('data-date-calendar', date);
				btnOpenAvailability.text(dateView);
				btnOpenAvailability.removeClass('btn-danger');
				btnOpenAvailability.addClass('btn-outline-success');
				btnOpenAvailability.attr('data-date-in-session', 1);

				$('#product_' + productItemStockId + ' .cart-products-quantity').val(quantity);

				$('#modal_product_availability').modal('hide');

				if (querybizProduct.checkSelectedAvailability()) {
					$('#DIV_cart_msg_warning').fadeOut();

					querybiz.showLoadingPage();

					querybizProduct.cartQuantityUpdate(function() {
						window.location.reload();
					});
				}
			});
		});

		$('.btn-form-go-to-checkout').on('click', function() {
			if (!querybizProduct.checkSelectedAvailability()) {
				$('#DIV_cart_msg_warning').show();
			} else {
				let btn = $('#btn_cart_go_to_checkout');
				let text = btn.attr('data-prev-loading-text');
				btn.attr('data-loading-text', text);

				querybiz.setLoadingClickedElement(btn);

				querybizProduct.cartQuantityUpdate(function () {
					window.location.href = '/checkout';
				});
			}
		});
	},

	checkSelectedAvailability: function() {
		let hasAllProductCalendar = true;
		let btnFormAvailability = $('.btn-form-open-availability');
		for (let i = 0; i < btnFormAvailability.length; i++) {
			let isDateInSession = $(btnFormAvailability[i]).attr('data-date-in-session');
			let calendar = $(btnFormAvailability[i]).attr('data-date-calendar');

			if (!parseInt(isDateInSession)) {
				hasAllProductCalendar = false;
			}
		}

		return hasAllProductCalendar;
	},

	listenerBtnCartProductQuantityUpdate: function() {
		$('.cart-products-quantity').on('change', function() {
			querybiz.showLoadingPage();

			querybizProduct.cartQuantityUpdate(function() {
				window.location.reload();
			});
		});

		$('.btn-cart-products-quantity-update').on('click', function() {
			querybiz.showLoadingPage();

			querybizProduct.cartQuantityUpdate(function() {
				window.location.reload();
			});
		});
	},

	listCategories: function() {
		var html = '';
		$.each(querybizProduct.CONST_listCategories, function(key, val) {
			html += '[ <a href="javascript:;" class="btn_show_products_in_category" data-category-id="' + val.id + '">' + val.name + '</a> ]';
		});

		$('#spf_list_categories_container').html(html);

		$('.btn_show_products_in_category').click(function() {
			let categoryId = $(this).attr('data-category-id');
			querybizProduct.listProductInCategory(categoryId);
		});
	},

	listProducts: function(data = null) {
		var html = '';

		if (!data) {
			data = querybizProduct.CONST_listProducts;
		}

		html += '<ul>';
		$.each(data, function(key, val) {
			html += '<li><b>' + val.name + '</b> (' + val.price + ') - <a href="' + querybiz.CONST_baseUri + '/' + val.referenceKey + '" target="_blank">Product Page</a> | <a href="javascript:;" class="btn_view_products_detail" data-product-id="' + val.id + '">View</a> | <a href="javascript:;" class="btn_add_products_to_cart" data-product-id="' + val.id + '">Add</a></li>';
		});
		html += '</ul>';

		$('#spf_list_products_container').html(html);

		$('.btn_add_products_to_cart').click(function() {
			let productId = $(this).attr('data-product-id');
			querybizProduct.addProductToCart(productId);
		});

		$('.btn_view_products_detail').click(function() {
			let productId = $(this).attr('data-product-id');
			querybizProduct.viewProductDetail(productId);
		});
	},

	listProductInCategory: function(categoryId) {
		this.post('/getProductInCategory/' + categoryId, function(data) {
			querybizProduct.listProducts(data);
		});
	},

	viewProductDetail: function (id) {
		let arProduct = [];
		for(let i = 0; i < querybizProduct.CONST_listProducts.length; i++) {
			if (querybizProduct.CONST_listProducts[i].id === id) {
				arProduct = querybizProduct.CONST_listProducts[i];
			}
		}

		$('#spf_detail_name').text(arProduct.name);
		$('#spf_detail_price').text(arProduct.price);

		$('#spf_detail').removeClass('d-none');
		$('#spf_btn_detail_add_to_cart').attr('data-product-id', id);

		$('#spf_btn_detail_close').unbind();
		$('#spf_btn_detail_close').click(function() {
			$('#spf_detail').addClass('d-none');
		});

		$('#spf_btn_detail_add_to_cart').unbind();
		$('#spf_btn_detail_add_to_cart').click(function() {
			querybizProduct.addProductToCart(id);
			$('#spf_detail').addClass('d-none');
		});

		// getProductDetail()
		$('#spf_detail_container').html('');
		$('#spf_btn_detail_get_product_detail').unbind();
		$('#spf_btn_detail_get_product_detail').click(function() {
			querybizProduct.getProductDetail(id);
		});

		// getProductText()
		$('#spf_detail_text').html('');
		$('#spf_btn_detail_get_product_text').unbind();
		$('#spf_btn_detail_get_product_text').click(function() {
			querybizProduct.getProductText(id);
		});

		// getProductFile()
		$('#spf_detail_file').html('');
		$('#spf_btn_detail_get_product_file').unbind();
		$('#spf_btn_detail_get_product_file').click(function() {
			querybizProduct.getProductFile(id);
		});

		// getProductSession()
		$('#spf_detail_session').html('');
		$('#spf_btn_detail_get_product_session').unbind();
		$('#spf_btn_detail_get_product_session').click(function() {
			querybizProduct.getProductSession(id);
		});
	},

	showCart: function(origin) {
		if (querybiz.isMobile()) {
			$('.product-cart-title-line-1').removeClass('text-center');
			$('.product-cart-title-line-1').addClass('col-3');
			$('.product-cart-title-line-2').addClass('col-9');
			$('.product-cart-title-line-3').hide();
			$('.product-cart-title-line-4').hide();

			$('.product-cart-line-1').addClass('col-3');
			$('.product-cart-line-1').removeClass('text-center');
			$('.product-cart-line-1').addClass('px-1');
			$('.product-cart-line-2').addClass('col-9');
			$('.product-cart-line-2').addClass('px-1');

			if (origin === 'cart') {
				$('.product-cart-line-3').addClass('col-3');
				$('.product-cart-line-4').addClass('col-3');

				$('.product-cart-line-0').addClass('bg-light border p-2 mt-1 mb-2');
				$('.product-cart-line-0').show();

			} else if (origin === 'checkout/auth') {
				$('.product-cart-line-3').addClass('col-4');
				$('.product-cart-line-4').addClass('col-4');

				$('.product-cart-line-0').removeClass('col-3');
				$('.product-cart-line-0').addClass('col-4');
				$('.product-cart-line-0').addClass('bg-light border p-2 mt-1 mb-2');
				$('.product-cart-line-0').show();
			}

		} else {
			$('.product-cart-title-line-1').addClass('col-1');
			$('.product-cart-title-line-2').addClass('col-4');
			$('.product-cart-title-line-3').addClass('col-2');
			$('.product-cart-title-line-4').addClass('col-1');

			$('.product-cart-line-1').addClass('col-1');
			$('.product-cart-line-2').addClass('col-4');
			$('.product-cart-line-3').addClass('col-2');
			$('.product-cart-line-4').addClass('col-1');
		}

		$('#product_cart_items_loading').hide();
		$('#product_cart_items').fadeIn();
	},

	getProductDetail: function(id) {
		this.post('/getProductDetail/' + id, function(data) {
			var html = '';

			html += '<ul>';

			if (data.hasOwnProperty('text')) {
				html += '<BR>' + data.text;
			}

			html += '<HR>';

			if (data.hasOwnProperty('session')) {
				$.each(data.session, function (key, val) {
					html += '<li>';
					html += '<b>' + val.name + '</b>';

					if (val.hasOwnProperty('file')) {
						html += '<ul>';
						$.each(val.file, function (key2, val2) {
							let url = querybiz.CONST_cdnUrl + val2.filename;
							if (val2.isImage) {
								html += '<li><img src="' + url + '" style="width: 100px"></li>';
							} else {
								html += '<li>' + url + '</li>';
							}
						});
						html += '</ul>';
						html += '<BR>';
					}

					html += '</li>';
				});
			}
			html += '</ul>';

			$('#spf_detail_container').html(html);
		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});
	},

	getProductText: function(id) {
		this.post('/getProductText/' + id, function(data) {
			$('#spf_detail_text').html(data.text);
		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});
	},

	getProductFile: function(id) {
		this.post('/getProductFile/' + id, function(data) {
			var html = '';

			html += '<ul>';
			$.each(data, function(key, val) {
				let url = querybiz.CONST_cdnUrl + val.filename;
				if (val.isImage) {
					html += '<li><img src="' + url + '" style="width: 100px"></li>';
				} else {
					html += '<li>' + url + '</li>';
				}
			});
			html += '</ul>';

			$('#spf_detail_file').html(html);
		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});
	},

	getProductSession: function(id) {
		this.post('/getProductSession/' + id, function(data) {
			var html = '';

			html += '<ul>';
			$.each(data, function(key, val) {
				html += '<li><b>' + val.name + '</b> ' + val.description + '</li>';
			});
			html += '</ul>';

			$('#spf_detail_session').html(html);
		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});
	},

	addProductToCart: function(id) {
		this.post('/addProductToCart?id=' + id, function(data) {
			dd(data);
			querybizProduct.viewCart();

		}, function(data) {
			dd(data);
			querybizProduct.showMsg(data.msg);
		});
	},

	getItemsInCart: function() {
		this.post('/getItemsInCart', function(data) {
			$('#spf_cart_items').text(data.totalProductInCart);

		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});
	},

	viewCart: function() {
		this.post('/getCart', function(data) {
			var html = '<ul>';

			$('#spf_cart_items').text(data.listProduct.length);

			$.each(data.listProduct, function (key, val) {
				html += '<li>' + val.name + ' <input type="text" class="cart-products-quantity" data-product-id="' + val.id + '" value="' + data.productQuantity[val.id] + '" style="width: 30px; text-align: center"> => ' + data.productPriceQuantity[val.id] + ' (<a href="javascript:;" class="btn-cart-products-remove" data-product-id="' + val.id + '">Remove</a>)</li>';
			});

			html += '</ul>';
			if (data.listProduct.length) {
				html += '<BR><button class="btn-cart-products-quantity-update">Update</button> <button id="btn_cart_products_clear">Clear All</button>';
			} else {
				html += 'The Cart is Empty!';
			}

			$('#spf_cart_container').html(html);

			$('.btn-cart-products-quantity-update').unbind();
			$('.btn-cart-products-quantity-update').click(function() {
				querybizProduct.cartQuantityUpdate();
			});

			$('#btn_cart_products_clear').unbind();
			$('#btn_cart_products_clear').click(function() {
				querybizProduct.clearCart();
			});

			$('.btn-cart-products-remove').unbind();
			$('.btn-cart-products-remove').click(function() {
				let productId = $(this).attr('data-product-id');
				querybizProduct.deleteProductFromCart(productId);
			});

		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});
	},

	getProductInCart: function() {
		this.post('/getProductInCart', function (data) {
			if (data.return === 'success') {
				let arProductQuantity = data.productQuantity;
				let arProductPriceQuantity = data.productPriceQuantity;
				let arProduct = data.listProduct;

				if (arProduct.length) {
					let html = '';
					arProduct.forEach(function (product) {
						html += '<div class="row">';
						html += '	<div class="col-1 mr-1 mb-1 p-1 border">';
						html += '		<img src="' + querybiz.CONST_cdnUrl + product.filename + '" class="w-100">';
						html += '	</div>';
						html += '	<div class="col mr-1 mb-1 p-1 border d-flex align-items-center">';
						html += product.name;
						html += '	</div>';
						html += '	<div class="col-1 mr-1 mb-1 p-1 border d-flex align-items-center text-center">';
						html += '		<div class="w-100 text-center">';
						html += '			<form action="' + arDefaultOptions['baseUri'] + '/deleteProductFromCart" method="POST">';
						html += '				<input type="hidden" name="redirect" value="/product-cart">';
						html += '				<button name="id" value="' + product.id + '" class="btn btn-outline-success"><i class="fas fa-trash-alt"></i></button>';
						html += '			</form>';
						html += '		</div>';
						html += '	</div>';
						html += '	<div class="col-1 mr-1 mb-1 p-1 border d-flex align-items-center">';
						html += '		<div class="w-100 text-center">';

						if (product.allowQuantity == 1) {
							html += '		<input type="text" value="' + arProductQuantity[product.id] + '" class="text-center w-100">';
						} else {
							html += '1';
						}

						html += '		</div>';
						html += '	</div>';
						html += '	<div class="col-1 mb-1 p-1 border d-flex align-items-center">';
						html += '		<div class="w-100 text-right">' + arProductPriceQuantity[product.id] + '</div>';
						html += '	</div>';
						html += '</div>';
					});

					html += '<div class="row">';
					html += '	<div class="col"></div>';
					html += '	<div class="col-2 mb-1 p-1 text-right font-weight-bold">';
					html += '		Total';
					html += '	</div>';
					html += '	<div class="col-1 mb-1 p-1 text-right font-weight-bold">';
					html += data.totalPrice;
					html += '	</div>';
					html += '</div>';

					$('#product-cart-content').html(html);
					$('#btn_product_cart_checkout').fadeIn();
					$('#btn_back_to_product').fadeIn();

				} else {
					$('#product-cart-content').html('&nbsp;');
					$('#product-cart-empty').fadeIn();
				}

			} else if (data.return === 'error') {
				alert('show msg de erro');
			}
		});
	},

	cartQuantityUpdate: function(callback = '') {
		var strId = '';
		var strQuantity = '';
		var strCalendar = '';

		let cartProductsQuantity = $('.cart-products-quantity');
		let calendarAvailability = $('.btn-form-open-availability');

		$.each(cartProductsQuantity, function(key, val) {
			let productItemStockId = $(val).attr('data-product-item-stock-id');
			let productQuantity = $(val).val();

			strId += productItemStockId + ',';
			strQuantity += productQuantity + ',';

			let calendar = $(calendarAvailability[key]);
			if (calendar.length) {
				strCalendar += calendar.attr('data-date-calendar') + ',';
			}
		});

		let calendar = '';
		if (strCalendar !== '') {
			calendar = '&calendar=' + strCalendar;
		}

		querybizProduct.post('/sendQuantityToCart?id=' + strId + '&quantity=' + strQuantity + calendar, function(data) {
			if (querybizProduct.CONST_shoppingType === 'ajax') {
				querybizProduct.viewCart();
			}

			if (callback !== '') {
				callback();
			}

		}, function(data) {
			querybizProduct.showFormMsg(data.msg);
		});
	},

	deleteProductFromCart: function(id) {
		this.post('/deleteProductFromCart?id=' + id, function(data) {
			querybizProduct.viewCart();
		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});
	},

	clearCart: function() {
		this.post('/clearCart', function(data) {
			querybizProduct.viewCart();
			querybizProduct.viewSummary();

		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});

	},

	delProductFromCart: function () {
		$('.btn-delete-product-cart').unbind();
		$('.btn-delete-product-cart').click(function(evt) {
			var btnSelf = $(this);
			btnSelf.prop('disabled',true);
			btnSelf.append('<span id="spinner-loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

			let redirect  = $(this).data('redirect');
			let productId = $(this).data('productid');
			let url	= '/deleteProductFromCart?id=' + productId;
			Swal.fire({
				title: 'Are you sure?',
				text: 'You won`t be able to revert this!',
				icon: 'question',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Yes, delete it!'
			}).then((result) => {
				if (result.isConfirmed) {
					querybiz.showLoadingPage();
					querybiz.post(url, function(data) {
						window.location.reload();
					}, function(data) {
						dd(data);
					});
				} else {
					btnSelf.prop('disabled', false);
					$('span#spinner-loading').remove();
				}
			});
		});
	},

	checkout: function() {
		let isCartQuantityUpdate = false;
		let cartProductsQuantity = $('.cart-products-quantity');
		$.each(cartProductsQuantity, function(key, val) {
			let productDataValue = $(val).attr('data-value');
			let productValue = $(val).val();
			if (productDataValue != productValue) {
				isCartQuantityUpdate = true;
			}

		});

		if (isCartQuantityUpdate) {
			querybizProduct.cartQuantityUpdate(function() {
				window.location.href = arDefaultOptions['baseUri'] + '/checkout';
			});
		} else {
			window.location.href = arDefaultOptions['baseUri'] + '/checkout';
		}
	},

	viewSummary: function() {
		this.post('/getCart', function(data) {
			var html = '<ul>';

			$.each(data.listProduct, function(key, val) {
				html += '<li>' + val.name + ' [' + data.productQuantity[val.id] + '] => ' + data.productPriceQuantity[val.id] +'</li>';
			});

			html += '</ul>';
			if (data.listProduct.length) {
				html += '<BR><button id="btn_summary_go_to_login">Login</button> <button id="btn_summary_go_to_signup">Sign Up</button>';
			} else {
				html += 'The Cart is Empty!';
			}

			$('#spf_summary_container').html(html);

		}, function(data) {
			querybizProduct.showMsg(data.msg);
		});
	},

	showMsg: function(msg) {
		let elMsgContainer = $('#spf_message');
		querybiz.showTemporaryMsg(msg, elMsgContainer);
	},

	post: function(url, callbackSuccess, callbackError) {
		querybiz.post(url, callbackSuccess, callbackError);
	}
};

var querybizProductTemplate = {
	// These variables values have been got from PHP/Twig variables through this.init() ----- //
	CONST_btnAddCart: '',
	CONST_btnAdded: '',
	CONST_btnViewDetail: '',
	CONST_btnInCartLabel: '',
	CONST_cardMessageTitle: '',

	listProducts: [],
	baseUri: '',

	// -------------------------------------------------------------------------------------- //

	CONST_htmlProduct: '',
	CONST_htmlCart: '',
	CONST_htmlSummary: '',
	CONST_htmlDetails: '',

	TloopCalcTimeToChangeQuantity: 0,
	TloopShowStatusMsg: 0,
	arCartProducts: [],
	isSendingProductQuantity: false,
	isChangedProductQuantity: false,
	CART_listProducts: [],
	CART_productQuantity: [],
	CART_productPriceQuantity: [],

	CHECKOUT_done: false,

	init: function(options) {
		querybizProductTemplate.listProducts = options.hasOwnProperty('listProducts') ? options.listProducts : '';
		querybizProductTemplate.baseUri = options.hasOwnProperty('baseUri') ? options.baseUri : '';

		querybizProductTemplate.CONST_btnAddCart = options.hasOwnProperty('btnAddCartLabel') ? options.btnAddCartLabel : '';
		querybizProductTemplate.CONST_btnAdded = options.hasOwnProperty('btnAddedLabel') ? options.btnAddedLabel : '';
		querybizProductTemplate.CONST_btnInCartLabel = options.hasOwnProperty('btnInCartLabel') ? options.btnInCartLabel : '';

		querybizProductTemplate.CONST_btnViewDetail = options.hasOwnProperty('btnViewDetail') ? options.btnViewDetail : '';

		querybizProductTemplate.CONST_cardMessageTitle = options.hasOwnProperty('cardMessageTitle') ? options.cardMessageTitle : '';

		querybizProductTemplate.CONST_htmlProduct = $('.DIV_content_order').html();
		querybizProductTemplate.CONST_htmlCart = $('.DIV_content_cart').html();
		querybizProductTemplate.CONST_htmlSummary = $('.DIV_content_summary').html();
		querybizProductTemplate.CONST_htmlDetails = $('.DIV_content_details').html();

		console.log(this.listProducts);
	},

	showStatusMsg: function(msg) {
		var strMsg = '<div class="order-info-card-message">';
		strMsg += '<div class="order-info-card-message-title">' + this.CONST_cardMessageTitle + '</div>';
		strMsg += '<div>';
		strMsg += ' <ul>';
		if (typeof (msg) == 'object') {
			var strMsgLi = '';
			for (var i = 0; i < msg.length; i++) {
				if (msg[i].trim() != '') {
					strMsgLi += '<li class="order-info-card-message-row">' + msg[i] + '</li>';
				}
			}

			if (strMsgLi == '') {
				strMsgLi += '<li class="order-info-card-message-row">Unknown object error</li>';
			}

			strMsg += strMsgLi;

		} else {
			if (msg == '') {
				msg = 'Unknown error';
			}
			strMsg += '<li class="order-info-card-message-row">' + msg + '</li>';
		}
		strMsg += ' </ul>';
		strMsg += '</div>';
		strMsg += '</div>';

		$('#order_info_status').css('display', 'block');
		$('#order_info_status').css('width', '100%');
		$('#order_info_status').css('height', 'auto');
		$('#order_info_status').html(strMsg);

		this.TloopShowStatusMsg = setTimeout(function() {
			$('.order-info-card-message').fadeOut(function() {
				$('#order_info_status').html('');
			});
			clearTimeout(querybizProductTemplate.TloopShowStatusMsg);
		}, 5000);
	},

	printProduct: function(data) {
		var html = '';
		var hasAddedToCart = false;

		for (var i = 0; i < data.length; i++) {
			var id = data[i].id;
			var price = data[i].price;
			var name = data[i].name;
			var description = data[i].description;
			var fileTitle = data[i].fileTitle;
			var filename = data[i].filename;
			var addedToCart = data[i].addedToCart;
			var addToCartOnHome = data[i].addToCartOnHome;

			var viewDetail = this.CONST_btnViewDetail;
			var addCart = this.CONST_btnAddCart;
			if (addedToCart == true) {
				addedToCart = 'DISABLED';
				addCart = this.CONST_btnInCartLabel;
				hasAddedToCart = true;
			} else {
				addedToCart = '';
			}

			filename = querybiz.CONST_cdnUrl + filename;

			addCart += ' <i class="fas fa-shopping-cart"></i>';

			var htmlProduct = this.CONST_htmlProduct;

			htmlProduct = this.htmlReplace('<%id%>', id, htmlProduct);
			htmlProduct = this.htmlReplace('<%price%>', price, htmlProduct);
			htmlProduct = this.htmlReplace('<%name%>', name, htmlProduct);
			htmlProduct = this.htmlReplace('<%description%>', description, htmlProduct);
			htmlProduct = this.htmlReplace('<%fileTitle%>', fileTitle, htmlProduct);
			htmlProduct = this.htmlReplace('<%filename%>', filename, htmlProduct);
			htmlProduct = this.htmlReplace('<%addToCartOnHome%>', addToCartOnHome, htmlProduct);

			if (addToCartOnHome == 1) {
				htmlProduct = this.htmlReplace('<%addedToCart%>', addedToCart, htmlProduct);
				htmlProduct = this.htmlReplace('<%addCart%>', addCart, htmlProduct);
			} else {
				if (addedToCart == 'DISABLED') {
					addedToCart = 1;
				} else {
					addedToCart = 0;
				}

				htmlProduct = this.htmlReplace('<%addedToCart%>', 'data-view-detail-added="' + addedToCart + '"', htmlProduct);
				htmlProduct = this.htmlReplace('<%addCart%>', viewDetail, htmlProduct);
			}

			htmlProduct = this.htmlReplace('<!--', '', htmlProduct);
			htmlProduct = this.htmlReplace('-->', '', htmlProduct);

			html += htmlProduct;
		}

		$('.DIV_content_order').html(html);

		this.toggleElementsView('printProduct');

		let btnViewDetailAdded = $('.order-info-product-add-cart[data-view-detail-added="1"]');
		btnViewDetailAdded.css('background-color', '#eee');
		btnViewDetailAdded.css('border', '#ccc 1px solid');
		btnViewDetailAdded.attr('title', this.CONST_btnInCartLabel);

		if (hasAddedToCart) {
			$('.btn_view_cart').prop('disabled', false);
			$('#btn_clear_cart').show();
		}

		querybizProductTemplate.listProductAddCart(addToCartOnHome);
	},

	showDetails: function(data) {
		var htmlDetails = this.CONST_htmlDetails;

		let addCart = this.CONST_btnInCartLabel;
		addCart += ' <i class="fas fa-shopping-cart"></i>';

		htmlDetails = this.htmlReplace('<%id%>', data.id, htmlDetails);
		htmlDetails = this.htmlReplace('<%name%>', data.name, htmlDetails);
		htmlDetails = this.htmlReplace('<%addedToCart%>', '', htmlDetails);
		htmlDetails = this.htmlReplace('<%addCart%>', addCart, htmlDetails);
		htmlDetails = this.htmlReplace('<!--', '', htmlDetails);
		htmlDetails = this.htmlReplace('-->', '', htmlDetails);

		$('.DIV_content_details').html(htmlDetails);
		$('.DIV_content_details').show();

		querybizProductTemplate.listProductAddCart(0);
	},

	listProductAddCart: function(addToCartOnHome) {
		$('.order-info-product-add-cart').click(function() {
			let origin = $(this).attr('data-origin');
			if (addToCartOnHome == 1 || origin === 'details') {
				let id = 0;
				if (origin == 'details') {
					id = $(this).attr('data-product-id');
				} else {
					id = $(this).closest('.order-info-product-list').attr('data-product-id');
				}
				querybizProductTemplate.addProductToCart(id);
			} else {
				let id = $(this).closest('.order-info-product-list').attr('data-product-id');

				querybiz.post('/getProductDetail/' + id, function(data) {
					querybizProductTemplate.showDetails(data);
				}, function(data) {
					alert('erro em getDetails');
				});
			}
		});
	},

	addProductToCart: function(id) {
		$.ajax({
			url: querybizProductTemplate.baseUri + '/addProductToCart',
			type: "POST",
			data:'id=' + id,
			beforeSend: function() {
				startToastWorking();
			},
			success: function(data) {
				$("body").removeClass("form_disabled");
				finishToastWorking();

				if (typeof(data) == 'object') {
					if (data.return == 'success') {
						$('.btn_view_cart').prop('disabled', false);
						$('#btn_clear_cart').show();

						let productName = $('.order-info-product-list[data-product-id="' + id + '"]').find('.order-info-product-name').text();
						let productImage = $('.order-info-product-list[data-product-id="' + id + '"]').find('.image');

						let productBackground = productImage.css('background-image');
						let productFileTitle = productImage.attr('title');
						let productImg = productImage.html();

						let html = '<div class="d-inline-block text-center image" style=\'background-image: ' + productBackground + '; background-size: 100%\' title="' + productFileTitle + '">' + productImg + '</div>';

						$('#img_keep_buying_decide').html(html);
						$('#label_keep_buying_decide').text(productName);

						$('#modal_keep_buying').modal('show');

						$('.order-info-product-list[data-product-id="' + id + '"]').find('.order-info-product-add-cart').html(querybizProductTemplate.CONST_btnAdded + ' <i class="fas fa-shopping-cart"></i>');
						$('.order-info-product-list[data-product-id="' + id + '"]').find('.order-info-product-add-cart').prop('disabled', true);

					} else if (data.return == 'error') {
						querybizProductTemplate.showStatusMsg(data.msg);
					}
				} else {
					querybiz.showFormMsg('error-data-type');
				}
			},
			error:function(data) {
				querybiz.showFormMsg('error-ajax-post');
				$("body").removeClass("form_disabled");
			}
		});
	},

	getProducts: function(categoryId) {
		$.ajax({
			url: querybizProductTemplate.baseUri + '/getProductInCategory/' + categoryId,
			type: "POST",
			data:'id=' + categoryId,
			beforeSend: function() {
				$("body").addClass("form_disabled");
				startToastWorking();
			},
			success: function(data) {
				finishToastWorking();
				$("body").removeClass("form_disabled");

				if (typeof(data) == 'object') {
					querybizProductTemplate.printProduct(data);
				} else {
					querybiz.showFormMsg(data.return);
				}
			},
			error:function(data) {
				finishToastWorking();
				$("body").removeClass("form_disabled");

				querybiz.showFormMsg(data.return);
			}
		});
	},

	loopingQuantity: function() {
		querybizProductTemplate.isChangedProductQuantity = true;
		this.calcTimeToChangeQuantity();
	},

	calcTimeToChangeQuantity: function() {
		if (querybizProductTemplate.isSendingProductQuantity == false && querybizProductTemplate.isChangedProductQuantity == true) {
			var arOrderInfoCart = $('.order-info-cart');

			var strId = '';
			var strQuantity = '';
			for (var i = 0; i < arOrderInfoCart.length; i++) {
				var id = arOrderInfoCart[i].getAttribute('data-cart-product-id');
				var quantity = $('.order-info-cart[data-cart-product-id="' + id + '"').find('.order-info-cart-product-quantity')[0].value;

				strId += id + ',';
				strQuantity += quantity + ',';
			}

			querybizProductTemplate.sendQuantityToCart(strId, strQuantity);

			querybizProductTemplate.TloopCalcTimeToChangeQuantity = setTimeout(function() {
				querybizProductTemplate.calcTimeToChangeQuantity();
			}, 2000);

		} else if (querybizProductTemplate.isSendingProductQuantity == true && querybizProductTemplate.isChangedProductQuantity == true) {
			querybizProductTemplate.TloopCalcTimeToChangeQuantity = setTimeout(function() {
				querybizProductTemplate.calcTimeToChangeQuantity();
			}, 2000);
		}
	},

	viewCart: function() {
		var listProducts = querybizProductTemplate.CART_listProducts;
		var productQuantity = querybizProductTemplate.CART_productQuantity;
		var productPriceQuantity = querybizProductTemplate.CART_productPriceQuantity;

		this.toggleElementsView('viewCart');

		var html = '';
		for (var i = 0; i < listProducts.length; i++) {
			var id = listProducts[i].id;
			var price = listProducts[i].price;
			var name = listProducts[i].name;
			var description = listProducts[i].description;
			var fileTitle = listProducts[i].fileTitle;
			var filename = listProducts[i].filename;

			var quantity = productQuantity[id];
			var maxQuantity = listProducts[i].maxQuantity;

			var htmlCart = this.CONST_htmlCart;

			filename = querybiz.CONST_cdnUrl + filename;

			if (description != '') {
				name += ': ';
			}

			htmlCart = this.htmlReplace('<%id%>', id, htmlCart);
			htmlCart = this.htmlReplace('<%price%>', price, htmlCart);
			htmlCart = this.htmlReplace('<%name%>', name, htmlCart);
			htmlCart = this.htmlReplace('<%description%>', description, htmlCart);
			htmlCart = this.htmlReplace('<%fileTitle%>', fileTitle, htmlCart);
			htmlCart = this.htmlReplace('<%filename%>', filename, htmlCart);
			htmlCart = this.htmlReplace('<%quantity%>', quantity, htmlCart);
			htmlCart = this.htmlReplace('<%maxQuantity%>', maxQuantity, htmlCart);

			htmlCart = this.htmlReplace('<!--', '', htmlCart);
			htmlCart = this.htmlReplace('-->', '', htmlCart);

			html += htmlCart;

			this.setAddedToCart(id);
		}

		$('.DIV_content_cart').html(html);

		$('.btn_cart_product_delete').click(function() {
			var id = $(this).closest('.order-info-cart').attr('data-cart-product-id');
			var name = $(this).closest('.order-info-cart').find('.order-info-cart-product-name').text();

			name = name.trim();
			if (name.substring(name.length -1, name.length) == ':') {
				name = name.substring(0, name.length -1);
			}

			$('#label_delete_cart_confirm').text(name);
			$('#modal_delete_cart').modal('show');

			$('#btn_delete_cart_confirm').attr('data-product-id', id);

			setTimeout(function() {
				$('#btn_delete_cart_confirm').focus();
			}, 100);
		});

		$('.btn_cart_product_minus').click(function() {
			var quantity = $(this).parent().find('.order-info-cart-product-quantity').val();
			quantity = parseInt(quantity);
			quantity = quantity - 1;
			if (quantity > 0) {
				$(this).parent().find('.order-info-cart-product-quantity').val(quantity);
				querybizProductTemplate.loopingQuantity();
			}
		});

		$('.btn_cart_product_plus').click(function() {
			var quantity = $(this).parent().find('.order-info-cart-product-quantity').val();
			quantity = parseInt(quantity);
			quantity = quantity + 1;

			var keepSum = true;
			var maxQuantity = $(this).parent().find('.order-info-cart-product-quantity').attr('data-product-quantity');
			if ($.isNumeric(maxQuantity) && parseInt(maxQuantity) > 0) {
				if (quantity > parseInt(maxQuantity)) {
					keepSum = false;
				}
			}

			if (keepSum == true) {
				$(this).parent().find('.order-info-cart-product-quantity').val(quantity);
				querybizProductTemplate.loopingQuantity();
			}
		});
	},

	writeCartLoading: function() {
		let html = '';

		html += '<div class="w-100 text-center">';
		html += '  <div class="spinner-border" role="status">';
		html += '    <span class="sr-only">Loading...</span>';
		html += '  </div>';
		html += '  <div class="text-center">Loading</div>';
		html += '</div>';

		$('.DIV_content_cart').html(html);
	},

	getCart: function(go) {
		this.toggleElementsView('showContentCart');
		this.writeCartLoading();

		$.ajax({
			url: querybizProductTemplate.baseUri + '/getCart',
			type: "POST",
			beforeSend: function() {
				startToastWorking();
			},
			success: function(data) {
				$("body").removeClass("form_disabled");
				finishToastWorking();

				if (typeof(data) == 'object') {
					if (data.return == 'success') {
						querybizProductTemplate.setProductSession(data.listProduct, data.productQuantity, data.productPriceQuantity);

						if (go == 'viewCart') {
							querybizProductTemplate.viewCart();
						} else if (go == 'viewSummary') {
							querybizProductTemplate.viewSummary();
						}
					} else if (data.return == 'error') {
						querybiz.showFormMsg(data.msg);
					}
				} else {
					querybiz.showFormMsg('error-data-type');
				}
			},
			error:function(data) {
				querybiz.showFormMsg('error-ajax-post');
				$("body").removeClass("form_disabled");
			}
		});
	},

	setProductSession: function(listProducts, productQuantity, productPriceQuantity) {
		querybizProductTemplate.CART_listProducts = listProducts;
		querybizProductTemplate.CART_productQuantity = productQuantity;
		querybizProductTemplate.CART_productPriceQuantity = productPriceQuantity;
	},

	setAddedToCart: function(id) {
		if (this.arCartProducts.indexOf(id) == -1) {
			this.arCartProducts[this.arCartProducts.length] = id;
		}

		for (var i = 0; i < querybizProductTemplate.listProducts.length; i++) {
			if (querybizProductTemplate.listProducts[i]['id'] == id) {
				querybizProductTemplate.listProducts[i]['addedToCart'] = true;
			}
		}
	},

	sendQuantityToCart: function(strId, strQuantity) {
		querybizProductTemplate.isSendingProductQuantity = true;
		querybizProductTemplate.isChangedProductQuantity = false;

		$.ajax({
			url: querybizProductTemplate.baseUri + '/sendQuantityToCart',
			type: "POST",
			data:'id=' + strId + '&quantity=' + strQuantity,
			beforeSend: function() {
				startToastWorking();
			},
			success: function(data) {
				$("body").removeClass("form_disabled");
				finishToastWorking();

				querybizProductTemplate.isSendingProductQuantity = false;

				if (typeof(data) == 'object') {
					if (data.return == 'success') {
						// success
					} else if (data.return == 'error') {
						querybiz.showFormMsg(data.msg);
					}
				} else {
					querybiz.showFormMsg('error-data-type');
				}
			},
			error:function(data) {
				querybiz.showFormMsg('error-ajax-post');
				$("body").removeClass("form_disabled");
			}
		});
	},

	removeProductFromCart: function(id) {
		$('.order-info-cart[data-cart-product-id="' + id + '"').remove();

		for (var i = 0; i < querybizProductTemplate.listProducts.length; i++) {
			if (querybizProductTemplate.listProducts[i].id == id) {
				querybizProductTemplate.listProducts[i].addedToCart = false;
			}
		}
	},

	viewSummary: function() {
		var listProducts = querybizProductTemplate.CART_listProducts;
		var productQuantity = querybizProductTemplate.CART_productQuantity;
		var productPriceQuantity = querybizProductTemplate.CART_productPriceQuantity;

		var html = '';
		for (var i = 0; i < listProducts.length; i++) {
			var id = listProducts[i].id;
			var price = listProducts[i].price;
			var name = listProducts[i].name;
			var description = listProducts[i].description;
			var fileTitle = listProducts[i].fileTitle;
			var filename = listProducts[i].filename;

			var priceQuantity = productPriceQuantity[id];
			var quantity = productQuantity[id];

			var htmlSummary = this.CONST_htmlSummary;

			filename = querybiz.CONST_cdnUrl + filename;

			if (description != '') {
				name += ': ';
			}

			htmlSummary = this.htmlReplace('<%id%>', id, htmlSummary);
			htmlSummary = this.htmlReplace('<%price%>', price, htmlSummary);
			htmlSummary = this.htmlReplace('<%name%>', name, htmlSummary);
			htmlSummary = this.htmlReplace('<%description%>', description, htmlSummary);
			htmlSummary = this.htmlReplace('<%fileTitle%>', fileTitle, htmlSummary);
			htmlSummary = this.htmlReplace('<%filename%>', filename, htmlSummary);
			htmlSummary = this.htmlReplace('<%quantity%>', quantity, htmlSummary);
			htmlSummary = this.htmlReplace('<%priceQuantity%>', priceQuantity, htmlSummary);

			htmlSummary = this.htmlReplace('<!--', '', htmlSummary);
			htmlSummary = this.htmlReplace('-->', '', htmlSummary);

			html += htmlSummary;

			this.setAddedToCart(id);
		}
		$('.DIV_content_summary').html(html);
		this.toggleElementsView('viewSummary');

	},

	viewCheckout: function() {
		querybizProductTemplate.toggleElementsView('viewCheckout');
	},

	confirmCheckout: function(orderId) {
		alert('mostrar mensagem final de pedido concluido (limpar session) - order: ' + orderId);
	},

	htmlReplace: function(from, to, text) {
		var exec = 'text.replace(/' + from + '/g, to)';
		return eval(exec);
	},

	toggleElementsView: function(action) {
		if (action === 'viewCart') {
			$('#btn_back_to_product').removeClass('d-none');
			$('.btn_view_cart').addClass('d-none');
			$('#btn_clear_cart').hide();

			$('.DIV_content_details').hide();

			$('.DIV_content_category').removeClass('d-block');
			$('.DIV_content_order').removeClass('d-block');

			$('.DIV_content_category').hide();
			$('.DIV_content_order').hide();

			$('.DIV_content_cart').fadeIn(function() {
				$('.DIV_content_menu_after').removeClass('d-none');
				$('.DIV_content_menu_after').fadeIn();
			});

		} else if (action == 'showContentCart') {
			$('.DIV_content_cart').removeClass('d-none');
			$('.DIV_content_cart').show();

		} else if (action == 'printProduct') {
			$('.DIV_content_order').removeClass('d-none');
			$('.DIV_content_order').show();

		} else if (action == 'viewCheckout') {
			$('.DIV-login').removeClass('d-inline-block');
			$('.DIV-login').hide();

			$('.DIV-checkout').removeClass('d-none');
			$('.DIV-checkout').addClass('d-inline-block');
			$('.DIV-checkout').hide();
			$('.DIV-checkout').fadeIn();

		} else if (action == 'viewSummary') {
			$('.DIV_content_cart').hide();
			$('#btn_go_to_summary').hide();
			$('#btn_back_to_product').hide();

			$('.DIV_content_summary').removeClass('d-none');
			$('.DIV_content_summary').show(function() {
				$('#btn_back_to_cart').removeClass('d-none');
				$('#btn_back_to_cart').fadeIn();
			});

			$('.DIV-box-login-out').removeClass('d-none');
			$('.DIV-box-login-out').show();

		} else if (action == 'backToProduct') {
			$('#btn_back_to_product').addClass('d-none');
			$('.btn_view_cart').removeClass('d-none');
			$('#btn_clear_cart').hide();

			$('.DIV_content_cart').removeClass('d-block');
			$('.DIV_content_cart').hide();
			$('.DIV_content_menu_after').hide();

			$('.DIV_content_category').fadeIn();

			$('.DIV_content_order').html('');
			$('.DIV_content_order').fadeIn();

		} else if (action == 'backToCart') {
			$('.DIV_content_summary').hide();
			$('.DIV-box-login-out').hide();
			$('.btn_back_to_cart').hide();

			$('.DIV-login').removeClass('d-inline-block');
			$('.DIV-login').hide();
			$('.DIV-signup').removeClass('d-inline-block');
			$('.DIV-signup').hide();
			$('.DIV-checkout').removeClass('d-inline-block');
			$('.DIV-checkout').hide();

			$('#btn_back_to_cart').hide();
			$('#btn_go_to_summary').fadeIn();
			$('#btn_back_to_product').fadeIn();

			$('.DIV_content_summary').html('');

		} else if (action == 'goToSummary') {
			$('.DIV-box-login').show();
			$('.DIV-box-signup').show();
			$('#form_login')[0].reset();
			$('#form_signup')[0].reset();
			$('#form_checkout')[0].reset();

		} else if (action == 'goToLogin') {
			$('.DIV-box-login').hide();
			$('.DIV-box-signup').hide();

			$('.DIV-login .card-header > .card-message').hide();
			$('.DIV-login .card-header > .card-title').show();

			$('.DIV-login').removeClass('d-none');
			$('.DIV-login').addClass('d-inline-block');
			$('.DIV-login').hide();
			$('.DIV-login').fadeIn();

		} else if (action == 'goToSignup') {
			$('.DIV-box-login').hide();
			$('.DIV-box-signup').hide();

			$('.DIV-signup .card-header > .card-message').hide();
			$('.DIV-signup .card-header > .card-title').show();

			$('.DIV-signup').removeClass('d-none');
			$('.DIV-signup').addClass('d-inline-block');
			$('.DIV-signup').hide();
			$('.DIV-signup').fadeIn();

		} else if (action == 'showLogin') {
			$('.DIV-login .card-header > .card-message').hide();
			$('.DIV-login .card-header > .card-title').show();

			$('.DIV-checkout .card-header > .card-message').hide();
			$('.DIV-checkout .card-header > .card-title').show();

		} else if (action == 'btnCheckout') {
			$('.DIV-checkout .card-header > .card-message').hide();
			$('.DIV-checkout .card-header > .card-title').show();

			$('.DIV-checkout').fadeOut(function() {
				$('.DIV-checkout').hide();
				$('.DIV-checkout').removeClass('d-inline-block');
				$('.DIV-checkout').addClass('d-none');
			});

			$('#btn_back_to_cart').fadeOut();
			$('#btn_show_products_cancel').fadeOut(function() {
				$('#btn_show_products_cancel').css('visibility', 'hidden');
			});

			$('#btn_show_products_close').removeClass('d-none');
			$('#btn_show_products_close').hide();
			$('#btn_show_products_close').fadeIn(function() {
				$('#btn_show_products_close').parent().removeClass('justify-content-between');
			});

		} else if (action == 'showProducts') {
			$('.DIV_content_summary').html('');
			$('.DIV_content_summary').hide();

			$('.DIV-box-login-out').hide();
			$('.btn_back_to_cart').hide();

			$('.DIV-login').removeClass('d-inline-block');
			$('.DIV-login').hide();
			$('.DIV-signup').removeClass('d-inline-block');
			$('.DIV-signup').hide();
			$('.DIV-checkout').removeClass('d-inline-block');
			$('.DIV-checkout').hide();

			$('#btn_back_to_cart').hide();
			$('#btn_go_to_summary').fadeIn();
			$('#btn_back_to_product').fadeIn();

			$('#btn_back_to_product').addClass('d-none');
			$('.btn_view_cart').removeClass('d-none');
			$('#btn_clear_cart').show();

			$('.DIV_content_cart').removeClass('d-block');
			$('.DIV_content_cart').hide();

			$('.DIV_content_menu_after').hide();
			$('.DIV_content_category').show();
			$('.DIV_content_order').show();

			$('#btn_show_products_cancel').css('visibility', 'visible');
			$('#btn_show_products_cancel').show();

			$('#btn_show_products_close').addClass('d-none');

		} else if (action == 'backToSummary') {
			$('.DIV-login').removeClass('d-inline-block');
			$('.DIV-login').hide();

			$('.DIV-signup').removeClass('d-inline-block');
			$('.DIV-signup').hide();

			$('.DIV-box-login').fadeIn();
			$('.DIV-box-signup').fadeIn();
		}

	},

	loadJQEvent: function() {
		$('#category').change(function() {
			var categoryId = this.value;
			if (categoryId != '') {
				querybizProductTemplate.getProducts(categoryId);
			} else {
				querybizProductTemplate.printProduct(querybizProductTemplate.listProducts);
			}
		});

		$('.btn_view_cart').click(function() {
			$('.DIV_content_category').hide();
			$('.DIV_content_order').hide();

			querybizProductTemplate.getCart('viewCart');
		});

		$('#btn_back_to_product').click(function() {
			querybizProductTemplate.toggleElementsView('backToProduct');

			var categoryId = $('#category').val();
			if (categoryId != '') {
				querybizProductTemplate.getProducts(categoryId);
			} else {
				querybizProductTemplate.printProduct(querybizProductTemplate.listProducts);
			}
		});

		$('#btn_back_to_cart').click(function() {
			querybizProductTemplate.toggleElementsView('backToCart');
			querybizProductTemplate.getCart('viewCart');
		});

		$('#btn_go_to_summary').click(function() {
			querybizProductTemplate.toggleElementsView('goToSummary');
			querybizProductTemplate.getCart('viewSummary');
		});

		$('#btn_go_to_login').click(function() {
			querybizProductTemplate.toggleElementsView('goToLogin');
		});

		$('#btn_go_to_signup').click(function() {
			querybizProductTemplate.toggleElementsView('goToSignup');
		});

		$('.btn_back_to_summary').click(function(e) {
			querybizProductTemplate.toggleElementsView('backToSummary');
		});

		$('#form_login').on('submit', function(e) {
			e.preventDefault();

			let el = $(this);
			querybiz.post(el, function(data) {
				if (data.return === 'success') {
					querybizProductTemplate.toggleElementsView('showLogin');
					querybizProductTemplate.viewCheckout();
				}
			}, function(data) {
				alert('erro no login');
			});
		});

		$('#form_checkout').on('submit', function(e) {
			e.preventDefault();

			let el = $(this);
			querybiz.post(el, function(data) {
				if (typeof(data) == 'object') {
					if (data.return == 'error') {
						querybiz.showFormMsg(data.msg);

					} else if (data.return > 0) {
						querybizProductTemplate.toggleElementsView('btnCheckout');
						querybizProductTemplate.CHECKOUT_done = true;
						querybizProductTemplate.confirmCheckout(data.return);
					}
				} else {
					querybiz.showFormMsg('error-data-type');
				}
			}, function(data) {
				alert('erro no login');
			});

			return false;
		});

		$('#btn_delete_cart_confirm').click(function (e) {
			e.preventDefault();

			var id = $(this).attr('data-product-id');

			$('body').addClass('form_disabled');

			$.ajax({
				url: querybizProductTemplate.baseUri + '/deleteProductFromCart',
				type: 'POST',
				data: 'id=' + id,
				beforeSend: function() {
					$("body").addClass("form_disabled");
					startToastWorking();
				},
				success: function(data) {
					finishToastWorking();
					$("body").removeClass("form_disabled");

					if (typeof(data) == 'object') {
						if (data.return == 'success') {
							querybizProductTemplate.removeProductFromCart(id);

							if (data.totalProducts == 0) {
								$('.btn_view_cart').prop('disabled', true);
								$('#btn_clear_cart').fadeOut();
								$('#btn_back_to_product').click();
							}

						} else if (data.return == 'error') {
							querybiz.showFormMsg(data.msg);
						}
					} else {
						querybiz.showFormMsg('error-data-type');
					}
				},
				error:function(data) {
					finishToastWorking();
					$("body").removeClass("form_disabled");

					querybiz.showFormMsg('error-ajax-post');
				}
			});
		});

		$('#btn_show_products_close').click(function() {
			window.location.reload();
		});

		$('#modal_show_products').on('hidden.bs.modal', function () {
			if (querybizProductTemplate.CHECKOUT_done == true) {
				querybizProductTemplate.toggleElementsView('showProducts');
			}
		});

		$('#btn_clear_cart').click(function (e) {
			e.preventDefault();

			$('body').addClass('form_disabled');

			$.ajax({
				url: querybizProductTemplate.baseUri + '/clearCart',
				type: 'POST',
				beforeSend: function() {
					$("body").addClass("form_disabled");
					startToastWorking();
				},
				success: function(data) {
					finishToastWorking();
					$("body").removeClass("form_disabled");

					if (typeof(data) == 'object') {
						if (data.return == 'success') {
							$.each(querybizProductTemplate.listProducts, function(key, val) {
								querybizProductTemplate.listProducts[key].addedToCart = false;
							});

							$('.btn_view_cart').prop('disabled', true);
							$('#btn_clear_cart').hide();

							$('#category').val('');
							$('#btn_back_to_product').click();

						} else if (data.return == 'error') {
							querybiz.showFormMsg(data.msg);
						}
					} else {
						querybiz.showFormMsg('error-data-type');
					}
				},
				error:function(data) {
					finishToastWorking();
					$("body").removeClass("form_disabled");

					querybiz.showFormMsg('error-ajax-post');
				}
			});
		});
	}
};

var querybizCustomer = {
	CONST_elMsg: null,
	CONST_elMsgCustomerLogin: null,
	CONST_elMsgCustomerSignup: null,

	init: function (options) {
		querybizCustomer.CONST_elMsgCustomerLogin = $('#customer_msg_login');
		querybizCustomer.CONST_elMsgCustomerSignup = $('#customer_msg_signup');

		$('#form_login').on('submit',function(e) {
			e.preventDefault();

			let formSubmitId = 'form_customer_login_submit';

			if (querybiz.formValidated(e, $(this), {'formSubmitId': formSubmitId}) === false) {
				return false;
			}

			querybiz.post($(this), function(data) {
				$('#form_login').fadeOut();
				$('#customer_login_redirect').fadeIn();

				if (data.redirectUrl !== '') {
					window.open(data.redirectUrl, '_self');
				} else {
					window.open(arDefaultOptions['baseUri'] + '/customer', '_self');
				}

			}, function(data) {
				querybiz.stopLoadingText(formSubmitId);
				querybiz.hideSpinnerButton(formSubmitId);
				querybiz.errorMsg(data, querybizCustomer.CONST_elMsgCustomerLogin);
			});
		});

		$('#form_signup').on('submit',function(e) {
			e.preventDefault();

			let formSubmitId = 'form_customer_signup_submit';

			if (querybiz.formValidated(e, $(this), {'formSubmitId': formSubmitId}) === false) {
				return false;
			}

			querybiz.post($(this), function(data) {
				$('#form_signup').fadeOut();
				$('#customer_signup_redirect').fadeIn();
				window.open(arDefaultOptions['baseUri'] + '/customer', '_self');

			}, function(data) {
				querybiz.stopLoadingText(formSubmitId);
				querybiz.hideSpinnerButton(formSubmitId);
				querybiz.errorMsg(data, querybizCustomer.CONST_elMsgCustomerSignup);
			});
		});
	}
};

var querybizCustomerDrive = {
	Tloop: 0,
	dir: '',
	showDriveList: false,
	execAfterUpload: '',
	progressBar: null,
	currentProgress: 0,
	totalFiles: 0,
	lastUploadData: '',

	init: function(options) {
		querybizCustomerDrive.showDriveList = options.hasOwnProperty('showDriveList') ? options.showDriveList : false;
		querybizCustomerDrive.execAfterUpload = options.hasOwnProperty('execAfterUpload') ? options.execAfterUpload : '';
		querybizCustomerDrive.dir = options.hasOwnProperty('dir') ? options.dir : '';
		querybiz.CONST_messageContainer = $('#customer_drive_msg');
		querybizCustomerDrive.progressBar = $('#progress_bar');

		$('#select_file').click(function() {
			$('#file').click();
		});

		$('#file').on('change', function(e) {
			e.preventDefault();
			$(this).attr('changed', 1);
			$('#formUploadFile').submit();
		});

		$('#formUploadFile').submit(function(e) {
			e.preventDefault();

			let len = 0;

			let file = $(this).find('#file')[0].files;
			if (file) {
				len = file.length;
			}

			let changed = $(this).find('#file').attr('changed');
			if (!changed) {
				changed = 0;
			}

			if (changed == 1 && len > 0) {
				querybizCustomerDrive.totalFiles = len;

				querybizCustomerDrive.progressBar.fadeIn();

				querybizCustomerDrive.progressBar.width(0).addClass('active');
				querybizCustomerDrive.uploadFile(e);
			}

			$(this).find('#file').attr('changed', 0);
			return false;
		});
	},

	trackUploadPosition: function() {
		let windowSize = window.innerWidth;
		let w = querybizCustomerDrive.progressBar.width();

		if (w >= querybizCustomerDrive.progressBar.parent().width()) {
			if (querybizCustomerDrive.showDriveList) {
				querybizCustomerDrive.getDrive();
			}
			clearTimeout(querybizCustomerDrive.Tloop);
		} else {
			querybizCustomerDrive.Tloop = setTimeout(function() {
				querybizCustomerDrive.trackUploadPosition();
			}, 200);
		}
	},

	trackUploadProgress: function (e) {
		if (e.lengthComputable) {
			querybizCustomerDrive.currentProgress = (e.loaded / e.total) * 100; // Amount uploaded in percent
			querybizCustomerDrive.progressBar.width(querybizCustomerDrive.currentProgress + '%');

			if (querybizCustomerDrive.currentProgress == 100) {
				querybizCustomerDrive.trackUploadPosition();
			}
		}
	},

	uploadFile: function(e) {
		var formdata = new FormData($('form')[0]);
		$.ajax({
			url: querybiz.CONST_apiUrl + '/api/uploadDriveCustomer',
			type: 'POST',
			data: formdata,
			xhr: function() {
				var appXhr = $.ajaxSettings.xhr();

				if (appXhr.upload) {
					appXhr.upload.addEventListener('progress', querybizCustomerDrive.trackUploadProgress, false);
				}
				return appXhr;
			},
			success: function(data) {
				querybizCustomerDrive.lastUploadData = data;

				if (querybizCustomerDrive.execAfterUpload !== '') {
					eval('querybizCustomerDrive.' + querybizCustomerDrive.execAfterUpload + '()');
				}

				let arMsg = [];

				let arError =  data.error;
				let arSuccess = data.success;

				let msgArError = Array('MSG-ERROR');
				if (arError.length) {
					arMsg = msgArError.concat(arError);

					if (querybiz.CONST_messageContainer.hasClass('bg-light')) {
						querybiz.CONST_messageContainer.removeClass('bg-light');
					}
					if (querybiz.CONST_messageContainer.hasClass('bg-success')) {
						querybiz.CONST_messageContainer.removeClass('bg-success');
					}
					if (!querybiz.CONST_messageContainer.hasClass('bg-warning')) {
						querybiz.CONST_messageContainer.addClass('bg-warning');
					}
				}

				if (arSuccess.length) {
					let msgArSuccess = Array('MSG-SUCCESS');
					if (arError.length) {
						msgArSuccess = msgArSuccess.concat(arSuccess);
						arMsg = msgArError.concat(arError.concat(msgArSuccess));

						if (querybiz.CONST_messageContainer.hasClass('bg-warning')) {
							querybiz.CONST_messageContainer.removeClass('bg-warning');
						}
						if (querybiz.CONST_messageContainer.hasClass('bg-success')) {
							querybiz.CONST_messageContainer.removeClass('bg-success');
						}
						if (!querybiz.CONST_messageContainer.hasClass('bg-info')) {
							querybiz.CONST_messageContainer.addClass('bg-info');
						}
					} else {
						arMsg = msgArSuccess.concat(arSuccess);

						if (querybiz.CONST_messageContainer.hasClass('bg-info')) {
							querybiz.CONST_messageContainer.removeClass('bg-info');
						}
						if (querybiz.CONST_messageContainer.hasClass('bg-warning')) {
							querybiz.CONST_messageContainer.removeClass('bg-warning');
						}
						if (!querybiz.CONST_messageContainer.hasClass('bg-success')) {
							querybiz.CONST_messageContainer.addClass('bg-success');
						}
					}
				}

				querybiz.setBgColorRGBA(querybiz.CONST_messageContainer, 0.3);
				querybiz.showTemporaryMsg(arMsg);
			},
			error: function() {
				querybiz.showTemporaryMsg(querybiz.trans('Error Gettings List of Files'));
			},

			contentType: false,
			processData: false
		});
	},

	getDrive: function() {
		let htmlLoading = '';
		htmlLoading += '<div class="text-center">';
		htmlLoading += '    <div class="spinner-border" role="status">';
		htmlLoading += '        <span class="sr-only">Loading...</span>';
		htmlLoading += '    </div>';
		htmlLoading += '</div>';

		$('#drive_list').html(htmlLoading);

		querybiz.get('/customer/getDrive', function(data) {
			querybizCustomerDrive.writeList(data.files);
		}, function(data) {
			querybiz.showTemporaryMsg(querybiz.trans('Error Gettings List of Files'))
		});
	},

	writeList: function(arList) {
		let html = '';

		html += '<div class="row m-0 p-0">';
		arList.forEach(function(filename) {
			if (filename.isImage === true) {
				src = filename.file;
			} else {
				src = filename.fileType;
			}

			html += '<div class="card border-0" style="width: 7rem; margin: 1px;">';
			html += '   <div class="card h-100">';
			html += '       <div class="card-body h-100 p-1">';
			html += '           <img src="' + src + '" title="' + filename.fileType +'" class="w-100">';
			html += '       </div>';
			//html += '       <div class="card-footer p-1 text-center">' + filename.name + '</div>';
			html += '   </div>';
			html += '</div>';
			//html += '<div class="col-sm-2 p-0">';
			//html += '</div>';
		});
		html += '</div>';

		$('#drive_list').html(html);
	},

    updateProfilePicture: function() {
        let data = querybizCustomerDrive.lastUploadData;
        if (data.return === 'success') {
            if (!data.colError.length) {
                let filename = querybiz.CONST_apiUrl + '/data/customer/' + data.colSuccess[0];
                $('#select_file').attr('src', filename);
            } else {
                dd(data.colError);
            }
        }
    }
};




/*
var querybizCustomerMyAccount = {
	CONST_elMsg: null,
	CONST_requiredMsg: null,

	init: function (options) {
		querybizCustomerMyAccount.CONST_requiredMsg = options.hasOwnProperty('requiredMessage') ? options.requiredMessage : '';
		querybizCustomerMyAccount.CONST_invalidTinMsg = options.hasOwnProperty('invalidTinMessage') ? options.invalidTinMessage : '';
		querybizCustomerMyAccount.CONST_elMsg = $('#customer-my-account-msg');
		var successMessage = options.hasOwnProperty('successMessage') ? options.successMessage : '';
		var errorMessage = options.hasOwnProperty('errorMessage') ? options.errorMessage : '';
		var invalidPassMessage = options.hasOwnProperty('invalidPassMessage') ? options.invalidPassMessage : '';
		var diferentPassMessage = options.hasOwnProperty('diferentPassMessage') ? options.diferentPassMessage : '';

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

		$('.geo-countries').change(function(){
			let form = $(this).closest('form');
			if ($(this).val() == arDefaultOptions['countryBaseId']) {
				$(form).find('input.geo-pt-council, input.geo-pt-district').addClass('d-none').attr('disabled', true);
				$(form).find('select.geo-pt-council, select.geo-pt-district').removeClass('d-none').attr('disabled', false);
			} else {
				$(form).find('input.geo-pt-district, input.geo-pt-council').removeClass('d-none').attr('disabled', false);
				$(form).find('select.geo-pt-district, select.geo-pt-council').addClass('d-none').attr('disabled', true);
			}
		});

		$('#form_add_contact, #form_add_address').on('submit', function(e) {
			e.preventDefault();

			querybiz.post($(this), function(data) {
					window.location.reload();
				},
				function(data) {
					alert('error');
					dd(data);
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
			$('.spinner-border').remove();
			form = $(this).closest('form');
			$(form).find('.btn-form-save').addClass('d-none').attr('disabled', true);
			$(form).find('.btn-form-delete').addClass('d-none').attr('disabled', true);
			$(form).find('.btn-form-edit, .btn-form-change').removeClass('d-none');
			$(this).addClass('d-none');
			querybizCustomerMyAccount.toggleFormFields(form, true);
		});

		$('.btn-form-save').click(function(e){
			e.preventDefault();

			let form = $(this).closest('form');

			//TODO: no country to check is nif is from PT
            if(!querybizCustomerMyAccount.isValidPtTin(form)){
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

	toggleFormFields: function(form, edit = false) {
		let elements = 'input:not([type=hidden]):not(.d-none), select:not(.d-none)';
		let updateData = form ? $(form).find(elements) : $('form').find(elements);

		$.each(updateData, function (key, val) {
			$(val).removeClass('border-0').attr('disabled', edit);
		});

		elements = 'input.d-none, select.d-none';
		updateData = form ? $(form).find(elements) : $('form').find(elements);

		$.each(updateData, function (key, val) {
			//$(val).removeClass('border-0').attr('disabled', true);
		});

		$(form).find('input[type=text]:first').focus();
	},

	isValidPtTin:function(form) {
		$('.required').remove();

		let isValid = true;

		if(!$(form).find('input[name=taxNumber]').length || !$(form).find('input[name=taxNumber]').val() ){
			return true;
		}

		let value = $(form).find('input[name=taxNumber]').val();
		let invalid = '<span class="not-valid required text-danger small float-right">' + this.CONST_invalidMsg + ' Ex. 123456789</span>';
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
*/


var querybizCustomerSupport = {
	submitOn: false,

	init: function (options) {
		querybiz.CONST_messageContainer = $('#customer_support_msg');

		$('.btn_open_new_ticket').click(function() {
			$('#support_no_tickets').hide();

			$('#DIV_cutomer_support_list_orders').hide();
			$('#DIV_cutomer_support_form').removeClass('d-none');
			$('#DIV_cutomer_support_form').hide();
			$('#DIV_cutomer_support_form').fadeIn();
			$('#form_title').focus();
		});

		$('#btn_customer_support_form_cancel').click(function() {
			$('#DIV_cutomer_support_form').hide();
			$('#DIV_cutomer_support_list_orders').fadeIn();

			$('#support_no_tickets').fadeIn();

		});

		$('.btn-support-history').click(function() {
			let supportId = $(this).attr('data-support-id');
			let show = $(this).attr('data-support-details-show');

			if (show == 0) {
				querybizCustomerSupport.getSupportDetails(supportId);
				querybiz.setTextLoading($(this), querybiz.trans('Reading'));

			} else {
				let btnHistory = $(this);

				btnHistory.attr('data-support-details-show', 0);
				btnHistory.text(querybiz.trans('Read Details'));
				btnHistory.removeClass('btn-outline-secondary');
				btnHistory.addClass('btn-outline-primary');

				$(this).text(querybiz.trans('Read Details'));

				$('#details_' + supportId).slideUp(function() {
					$(this).html('');
					$(this).show();
				});

			}
		});

		$('#form_support').on('submit', function (e) {
			e.preventDefault();

			$(this).addClass('disabled');

			if (querybizCustomerSupport.submitOn == true) {
				//return false;
			}

			querybizCustomerSupport.submitOn = true;

			var el = $(this);
			var btnCancel = $('#btn_customer_support_form_cancel');
			var formSubmitId = 'form_submit';

			btnCancel.prop('disabled', true);

			querybiz.post($(this), function(data) {
				$('#DIV_cutomer_support_form').hide();
				$('#DIV_cutomer_support_form_sent').removeClass('d-none');
				$('#DIV_cutomer_support_form_sent').hide();
				$('#DIV_cutomer_support_form_sent').fadeIn();

				btnCancel.prop('disabled', false);
				querybiz.hideSpinnerButton(formSubmitId);

				$('#form_support')[0].reset();

			}, function(data) {
				querybiz.errorMsg(data.msg);

				btnCancel.prop('disabled', false);
				querybiz.hideSpinnerButton(formSubmitId);
			});
		});
	},

	getSupportDetails: function(supportId) {
		let contentLoading = '<div class="pt-2 mb-2"><div class="spinner-grow spinner-grow-sm" role="status"><span class="sr-only">Loading...</span></div> <div class="d-inline-block" style="font-size: 80%">' + querybiz.trans('Loading') +'</div></div>';
		$('#details_' + supportId).html(contentLoading);

		querybiz.get('/customer/getSupportHistory/' + supportId, function (data) {
			querybizCustomerSupport.supportDetailsData(data);

			let btnHistory = $('.btn-support-history[data-support-id="' + supportId + '"]');
			btnHistory.text(querybiz.trans('Hide Details'));
			btnHistory.attr('data-support-details-show', 1);
			btnHistory.text(querybiz.trans('Hide Details'));
			btnHistory.removeClass('btn-outline-primary');
			btnHistory.addClass('btn-outline-secondary');

		}, function (data) {
			querybiz.errorMsg(data.msg);
		});
	},

	supportDetailsData: function(data) {
		let id = data.details.id;

		let html = '';
		html += '<div class="support-details" id="details_' + id + '_content" data-id="' + id + '" style="width: 100%">';
		html += '   <div class="my-2" style="font-size: 20px">';
		html += '       <span class="support-details-title" style="border-bottom: #aaa 2px solid; padding-right: 10px">' + data.details.title + '</span>';
		html += '       <a href="javascript:;" class="btn btn-info btn-sm float-right mr-1 btn-support-ticket-close">Close Ticket</a>';
		html += '   </div>';
		html += '   <div>';
		html += '       <div class="support-details-text">';
		if (data.details.text.length > 200) {
			html += '<span class="support-details-text-summary">' + data.details.text.substring(0, 200) + '...</span>';
			html += '<span class="support-details-text-full" style="display: none">' + data.details.text + '</span>';
			html += '<a href="javascript:;" class="btn btn-outline-info btn-sm btn-support-details-full-text" style="margin-left: 5px; margin-top: -2px">' + querybiz.trans('Read more') + '</a>';
		} else {
			html += data.details.text;
		}
		html += '       </div>';
		html += '   </div>';
		html += '   <div style="padding-top: 10px; padding-bottom: 10px; font-size: 20px">';
		html += '       <span style="border-bottom: #aaa 2px solid; padding-right: 10px">Histórico</span>';
		html += '       <div class="float-right"><a href="javascript:;" class="btn btn btn-outline-primary btn-support-history-reply" data-id="' + id + '" data-write-reply="0">' + querybiz.trans('Comment') + '</a></div>';
		html += '   </div>';
		html += '   <form class="support-details-form-reply" action="' + arDefaultOptions['baseUri'] + '/customer/addSupportHistory" method="POST">';
		html += '       <input type="hidden" name="supportId" value="' + id + '">';
		html += '       <textarea name="text" class="support-details-reply" style="display: none; width: 100%; height: 150px; padding: 5px" placeholder="' + querybiz.trans('Write here your commentary') + '"></textarea>';
		html += '   </form>';

		html += '   <div>';
		html +=         querybizCustomerSupport.supportDetailsHistory(data.details, data.historyList, data.historyLength);
		html += '   </div>';
		html += '</div>';

		$('#details_' + id).hide();
		$('#details_' + id).html(html);
		$('#details_' + id).slideDown();

		$('.btn-support-details-full-text').unbind();
		$('.btn-support-details-full-text').click(function() {
			$(this).hide();

			let supportDetails = $(this).closest('.support-details');
			supportDetails.find('.support-details-text-summary').hide();//slideUp().fadeOut();
			supportDetails.find('.support-details-text-full').slideDown();
		});

		$('.btn-support-history-reply').unbind();
		$('.btn-support-history-reply').click(function() {
			let supportId = $(this).attr('data-id');
			let writeReply = $(this).attr('data-write-reply');

			let supportDetails = $(this).closest('.support-details');
			let reply = supportDetails.find('.support-details-reply');

			if (writeReply == -1) {
				querybiz.removeClassStartingWith($(this), 'btn-');
				querybiz.setTextLoading($(this), querybiz.trans('Send'));
				btnReply.attr('data-write-reply', 0);

			} else if (writeReply == 0) {
				reply.slideDown().focus();

				$(this).text(querybiz.trans('Send commentary'));
				querybiz.removeClassStartingWith($(this), 'btn-');
				$(this).addClass('btn-danger');

				$(this).attr('data-write-reply', 1);
			} else {
				querybiz.setTextLoading($(this), querybiz.trans('Sending'));

				supportDetails.find('.support-details-form-reply').submit();

				querybiz.removeClassStartingWith($(this), 'btn-');
				$(this).addClass('btn-outline-success');

				$(this).attr('data-write-reply', 0);
			}
		});

		$('.support-details-form-reply').unbind();
		$('.support-details-form-reply').on('submit', function(e) {
			e.preventDefault();

			let form = $(this);
			let supportId = form.find('[name="supportId"]').val();
			var supportDetails = $('#details_' + id + '_content');

			let text = form.find('[name="text"]').val();
			var btnReply = $('.btn-support-history-reply[data-id="' + supportId + '"]');

			if (text.trim() == '') {
				querybiz.showFormMsg(querybiz.trans('You have to write a text to send this message') + '!');
				btnReply.text(querybiz.trans('Failure! Tray Again'));
				querybiz.removeClassStartingWith(btnReply, 'btn-');
				btnReply.addClass('btn-danger');
				btnReply.attr('data-write-reply', -1);

				return false;
			}

			querybiz.post($(this), function (data) {
				let reply = supportDetails.find('.support-details-reply');
				reply.slideUp(function() {
					querybizCustomerSupport.getSupportDetails(supportId);
				});

				btnReply.text(querybiz.trans('Success') + '!');
				btnReply.removeClass('btn-outline-success');
				btnReply.addClass('btn-success');

			}, function (data) {
				querybiz.errorMsg(data.msg);

				btnReply.text(querybiz.trans('Failure') + '!');
				querybiz.removeClassStartingWith(btnReply, 'btn-');
				btnReply.addClass('btn-danger');
			});

			return false;
		});

		$('.btn-support-details-history').click(function() {
			let supportId = $(this).closest('.support-details').attr('data-id');
			let limit = $(this).attr('data-limit');
			querybiz.get('/customer/getSupportHistory/' + supportId + '/' + limit, function (data) {
				querybizCustomerSupport.supportDetailsData(data);

			}, function (data) {
				querybiz.errorMsg(data.msg);
			});
		});

		$('.btn-support-ticket-close').click(function() {
			let supportId = $(this).closest('.support-details').attr('data-id');
			let supportLine = $('#support_' + supportId);
			let supportStatus = supportLine.find('.support-status-name').attr('data-status-closed');

			if (supportStatus == 'closed') {
				querybiz.showTemporaryMsg(querybiz.trans('This Support Ticket is already closed') + '!');
			} else {
				querybiz.post('/customer/closeSupport?supportId=' + supportId, function (data) {
					if (data.return == 'success') {
						$('#support_' + supportId).fadeOut(function() { $(this).hide(); });
						$('#details_' + supportId).fadeOut(function() { $(this).hide(); });
						$('#division_' + supportId).fadeOut(function() { $(this).hide(); });
					}
				}, function (data) {
					querybiz.errorMsg(data.msg);
				});
			}
		});
	},

	supportDetailsHistory: function(details, historyList, historyLength) {
		//support_0 support-status-name
		if (details.statusName != '') {
			$('#support_' + details.id).find('.support-status-name').text(details.statusName);
		}

		var html = '';
		historyLength = parseInt(historyLength);
		$.each(historyList, function (key, val) {
			if ((key + 1) === historyLength) { // Stop the loop to do not show the first commentary, because it is already on title
				return false;
			}

			let name = '';
			let bgClass = '';
			let dateInserted = querybizUtil.dateDecode(val.dateInserted, {hour:true});
			let dateInsertedShow = querybizUtil.dateDecode(val.dateInserted);
			if (val.userId == 0) {
				name = val.customerName;
				bgClass = 'bg-danger';
			} else {
				name = val.userName;
				bgClass = 'bg-info';
			}
			html += '<div class="d-block ' + bgClass + '" style="height: 31px; color: #fff">';
			html += '   <div class="d-inline-block p-1 text-left" style="width: 150px; height: 30px" title="'+ dateInserted +'">' + dateInsertedShow + '</div>';
			html += '   <div class="d-inline-block p-1 text-left" style="height: 31px">' + name + '</div>';
			html += '</div>';
			html += '<div class="d-block w-100 mb-3 p-1 text-left" style="border: #fff 1px solid; border-top: 0; white-space: initial">' + val.text + '</div>';
		});

		if (historyList.length < historyLength) {
			html += '<div class="text-center"><button class="btn btn-outline-primary btn-support-details-history" data-limit="' + historyLength + '">' + querybiz.trans('More History') + '</button></div>';
		}
		return html;
	}
};

var querybizWishlist = {
	CONST_elMsg: null,
	init: function (options) {

		querybizWishlist.CONST_elMsg = $('#customer-my-account-msg');
		var successMessage = options.hasOwnProperty('successMessage') ? options.successMessage : '';
		var errorMessage = options.hasOwnProperty('errorMessage') ? options.errorMessage : '';

		$('.btn-add-wishlist').click(function() {
			$(this).find('.spinner-border').remove();
			let loader ='<div style="margin-top: -19px; margin-left: -4px"><sup class="ml-1 spinner-border text-info spinner-border text-danger"></sup></div>';
			$(this).prepend(loader);
			let productId = $(this).data('product-id');

			let url = '';
			let action = 0;
			if ($(this).hasClass('fas')) {
				url = '/customer/my-wishlist/delete?id=' + productId;
			} else {
				action = 1;
				url = '/customer/my-wishlist/save?id=' + productId;
			}

			var selfButton = $(this);
			querybiz.post(url, function(data) {
					selfButton.find('.spinner-border').remove();
					let wishListId = $('.wishlist-' + productId);

					if (action) {
						wishListId.removeClass('far').addClass('fas');
					} else {
						wishListId.removeClass('fas').addClass('far');
					}

					let titleOn = wishListId.data('title-on');
					wishListId.prop('title', titleOn);
				},
				function(data){
					selfButton.find('.spinner-border').remove();
					alert(errorMessage);
				});
		});

		$('.btn-delete-wishlist').click(function(){
			$('#modal_delete').modal('show');
			let productId = $(this).data('product-id');
			$('.delete-wishlist input[name=id]').val(productId);
		});

		$('#modal_delete .btn-danger').click(function(){
			let elMsg = querybizWishlist.CONST_elMsg;
			let form = $('.delete-wishlist');
			let wishlistId = $('input[name=id]').val();

			let html = $(this).html();
			let loader ='<sup class="ml-1 spinner-border spinner-border-sm"></sup>';
			$(this).html(html + loader);

			querybiz.post($(form), function(data) {
					let id = form.find('id').val();

					$('#modal_delete').modal('hide');
					$('.wishlist-' + wishlistId).remove();
					elMsg.text(successMessage).removeClass('d-none').addClass('alert-success');
					elMsg.fadeIn().delay(2000).fadeOut(function () {
						elMsg.removeClass('alert-success');
					});

					$('#modal_delete').find('.btn-danger sup').remove();
					$('.wishlist-' + id).fadeOut();
				},
				function(data) {
					$('#modal_delete').modal('hide');
					$('.spinner-border').remove();
					elMsg.text(errorMessage).removeClass('d-none').addClass('alert-danger');
					elMsg.fadeIn().delay(5000).fadeOut(function () {
						elMsg.removeClass('alert-danger');
					});
				});
		});
	}
};

var querybizCustomerReview = {
	CONST_elMsg: null,
	init: function (options) {

		querybizCustomerReview.CONST_elMsg = $('#customer-my-account-msg');
		var successMsg = options.hasOwnProperty('successMessage') ? options.successMessage : '';
		var errorMsg = options.hasOwnProperty('errorMessage') ? options.errorMessage : '';
		var requiredMsg = options.hasOwnProperty('requiredMessage') ? options.requiredMessage : '';
		var requiredHtml = options.hasOwnProperty('requiredHtml') ? options.requiredHtml : '';
		var progressHtml = options.hasOwnProperty('progressHtml') ? options.progressHtml : '';
		var fileMaxSize = options.hasOwnProperty('fileMaxSize') ? options.fileMaxSize : '';
		var imageExtension = options.hasOwnProperty('imageExtension') ? options.imageExtension : '';
		var fileTypeMsg = options.hasOwnProperty('fileTypeMsg') ? options.fileTypeMsg : '';
		var fileSizeMsg = options.hasOwnProperty('fileSizeMsg') ? options.fileSizeMsg : '';

		$('textarea[name=observations]').bind('keyup', function(e) {
			let minTotal = 10;
			let maxTotal = 499;
			let charsTotal = $(this).val().length;

			if(charsTotal == 0 || charsTotal >= minTotal && charsTotal <= maxTotal) {
				$(this).removeClass('border-danger');
				$('.required').remove();
			}

			let html = '';
			if (charsTotal < minTotal || charsTotal >= maxTotal) {
				html = '<sup class="text-danger">' + charsTotal + '</sup>';
			} else {
				html = '<sup class="text-success">' + charsTotal + '</sup>';
			}

			$(this).prev().children('span').html(charsTotal > 0 ? html : '');
		});

		$('.icon-star').click(function() {
			if ($(this).data('disabled')) {
				return false;
			}

			$('.required, .progress').remove();
			let form = (this).closest('form');
			$(form).find('textarea[name=observations]').removeClass('border-danger');
			let nr = $(this).data('index');
			let id = $(this).data('id');
			let c = 0;
			$('.star-'+id).removeClass('fas fa-star').addClass('far fa-star');

			while (c != nr) {
				c++;
				$('.star-' + id + '.star-nr-' + c).addClass('fas fa-star');
			}

			$(form).find('input[name=rate]').val(nr);
			$(form).find('.btn-success').attr('disabled', (nr > 0 ? false : true));
		});

		$('.btn-clear').click(function() {
			$('.required, .progress').remove();
			let form = (this).closest('form');
			$(form).find('textarea[name=observations]').removeClass('border-danger');
			let id = $(form).find('input[name=productId]').val();
			$('.star-'+id).removeClass('fas fa-star').addClass('far fa-star');
			$('.count-message').empty();
			$('input[name=rate]').val(0);
			$(form).find('.set-image-container').html('<i class="fas fa-camera-retro"></i>');
			$(form).trigger('reset');
		});

		$('.btn-save').click(function() {
			$('.required, .progress, .spinner').remove();
			let loader = '<sup class="ml-1 spinner spinner-border spinner-border-sm"></sup>';
			let flag = false;
			let form = $(this).closest('form');
			let rate = $(form).find('input[name=rate]');
			let rateValue = $(rate).val();
			let observations = $(form).find('textarea[name=observations]');
			$(observations).removeClass('border-danger');
			let observationsValue = $(observations).val();

			if(rateValue == 0 && observationsValue.length < 1) {
				flag = true;
				$(rate).before(requiredHtml);
				$(observations).prev().before(requiredHtml).addClass('border-danger');
				$(observations).addClass('border-danger');
				$('.required').text(requiredMsg);
			}
			if(observationsValue.length > 0 && observationsValue.length < 10 || observationsValue.length > 499) {
				flag = true;
				$(observations).prev().before(requiredHtml).addClass('border-danger');
				$(observations).addClass('border-danger');
				$('.required').text(requiredMsg);
			}

			if(flag) {
				$('.progress, .spinner').remove();
				return false;
			}

			var button = $(this);
			var files = form.find('input[type=file]')[0].files;
			let bytes = 0;

			//Files validate size and format
			for (var i = 0; i < files.length; i++) {
				if (!imageExtension.find((e) => e == files[i].type)) {
					form.find('input[type=file]').prev().before(requiredHtml);
					$('.required').text(fileTypeMsg);
					return false;
				}
				bytes = files[i].size;
				if(bytes >= fileMaxSize){
					form.find('input[type=file]').prev().before(requiredHtml);
					$('.required').text(fileSizeMsg + '' + fileMaxSize/1000000 + 'MB');
					return false;
				}
			}

			form.find('input[type=file]').attr('disabled', true);
			button.html(button.html()+loader);
			querybiz.post($(form), function(data){
					form.find('input[type=file]').attr('disabled', false);
					$('.spinner').remove();
					//Image upload
					if(files.length){
						button.html(button.html()+progressHtml);
						querybizCustomerReview.uploadFile(data,form);
					}
					else{
						let id = $(form).find('input[name=productId]').val();
						$('.product-'+id).append('<sup class="ml-1">'+$(form).find('.stars-container').html()+'</sup><i class="float-right fa fa-check text-info fa-2x"></i>');
						$(form).remove();
					}
				},
				function(data) {
					$('#modal_delete').modal('hide');
					$('.spinner').remove();
					elMsg.text(errorMessage).removeClass('d-none').addClass('alert-danger');
					elMsg.fadeIn().delay(5000).fadeOut(function () {
						elMsg.removeClass('alert-danger');
					});
				});
		});

		$('.set-image-container').click(function() {
			let form = $(this).closest('form');
			$('.required').remove();
			form.find('.set-image').trigger('click');
		});

		$('.set-image').change(function(e) {
			let form = $(this).closest('form');
			let html = '';
			if (e.target.files[0] != undefined) {
				html = '<img class="output" style="max-height: 36px" src=' + URL.createObjectURL(e.target.files[0]) + '>';
			} else {
				html = '<i class="fas fa-camera-retro"></i>';
			}
			form.find('.set-image-container').html(html);
		})
	},

	uploadFile: function (data, form) {
		let id = $(form).find('input[name=productId]').val();
		$.ajax({
			url: querybiz.CONST_APIUrl + '/api/uploadProductReviewFile/' + data.id,
			type: 'POST',
			data: new FormData(form[0]),
			contentType: false,
			processData: false,
			xhr: function() {
				var appXhr = $.ajaxSettings.xhr();
				if (appXhr.upload) {
					appXhr.upload.addEventListener('progress', querybizCustomerReview.trackUploadProgress, false);
				}
				return appXhr;
			},
			success: function(data) {
				$('.progress').remove();
				if (data.return == 'success') {
					$('.product-' + id).append('<sup class="ml-1">' + $(form).find('.stars-container').html() + '</sup><i class="float-right fa fa-check text-info fa-2x"></i>');
					$(form).remove();
				}
			},
			error: function() {
				$('.progress').remove();
				querybiz.showTemporaryMsg(querybiz.trans('Error Gettings List of Files'));
			},
		});
	},

	trackUploadProgress: function (e) {
		if (e.lengthComputable) {
			querybizCustomerDrive.currentProgress = (e.loaded / e.total) * 100; // Amount uploaded in percent
			$('.progress-bar').width(querybizCustomerDrive.currentProgress + '%');
		}
	}
};

var querybizUtil = {
	getIframeWindow: function(iframe_object) {
		var doc;

		if (iframe_object.contentWindow) {
			return iframe_object.contentWindow;
		}

		if (iframe_object.window) {
			return iframe_object.window;
		}

		if (!doc && iframe_object.contentDocument) {
			doc = iframe_object.contentDocument;
		}

		if (!doc && iframe_object.document) {
			doc = iframe_object.document;
		}

		if (doc && doc.defaultView) {
			return doc.defaultView;
		}

		if (doc && doc.parentWindow) {
			return doc.parentWindow;
		}

		return undefined;
	},

	dateDecode(str, options = null) {
		let date = '';
		let hour = '';
		if (str && str.indexOf(' ') !== -1) {
			date = str.substring(0, str.indexOf(' '));
			hour = str.substring(str.indexOf(' ') + 1, str.length);
		}
		if (str && date.indexOf('-') !== -1) {
			let colDate = date.split('-');
			if (colDate.length === 3) {
				if (colDate[0].length === 4 && colDate[1].length === 2 && colDate[2].length === 2) {
					str = colDate[2] + '/' + colDate[1] + '/' + colDate[0];
				}
			}
			if (options && options.hasOwnProperty('hour')) {
				str += ' ' + hour;
			}
		}
		return str;
	}
};