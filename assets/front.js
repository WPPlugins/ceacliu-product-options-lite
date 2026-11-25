/* Ceacliu Product Options Lite – live total, limits and checks on the product page. */
(function ($) {
	'use strict';
	var S = window.ceacliuPOL || {};

	function money(n) {
		var d = parseInt(S.decimals, 10) || 0;
		var parts = Math.abs(n).toFixed(d).split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, S.thou || '');
		var out = (S.format || '%1$s%2$s').replace('%1$s', S.symbol || '').replace('%2$s', parts.join(S.dec || '.'));
		return (n < 0 ? '-' : '') + out;
	}

	function num(v) {
		v = parseFloat(v);
		return isNaN(v) ? 0 : v;
	}

	function init($box) {
		var $form = $box.closest('form');
		var base = $box.data('base') === '' ? null : num($box.data('base'));

		function update() {
			var extra = 0;
			$box.find('input:checked, select option:selected').each(function () {
				extra += num(this.getAttribute('data-price'));
			});
			$box.find('.cpol-field').each(function () {
				var max = parseInt($(this).data('max'), 10) || 0;
				if (max) { // at most N: grey out the rest
					var full = $(this).find('input[type=checkbox]:checked').length >= max;
					$(this).find('input[type=checkbox]').not(':checked').prop('disabled', full)
						.closest('.cpol-choice').toggleClass('is-off', full);
				}
			});
			$box.find('.cpol-choice').each(function () {
				$(this).toggleClass('is-on', $(this).find('input').is(':checked'));
			});
			var $t = $box.find('.cpol-total');
			if (base === null) {
				$t.prop('hidden', true);
				return;
			}
			var qty = Math.max(1, parseInt($form.find('input[name=quantity]').val(), 10) || 1);
			$t.find('strong').text(money((base + extra) * qty));
			$t.prop('hidden', false);
		}

		function check() {
			var first = null;
			$box.find('.cpol-field').each(function () {
				var $f = $(this), msg = '';
				if (String($f.data('required')) === '1') {
					var n = $f.find('input:checked').length +
						$f.find('select').filter(function () { return this.value !== ''; }).length +
						$f.find('textarea').filter(function () { return $.trim(this.value) !== ''; }).length;
					if (!n) {
						msg = $f.find('textarea').length ? (S.fill || '') : (S.required || '');
					}
				}
				$f.toggleClass('has-error', !!msg).find('.cpol-error').text(msg).prop('hidden', !msg);
				if (msg && !first) {
					first = $f;
				}
			});
			if (first) {
				$('html, body').animate({ scrollTop: first.offset().top - 90 }, 200);
				return false;
			}
			return true;
		}

		$box.on('change input', 'input, select, textarea', function () {
			$(this).closest('.cpol-field').removeClass('has-error').find('.cpol-error').prop('hidden', true);
			update();
		});
		$form.on('change input', 'input[name=quantity]', update);
		$form.on('found_variation', function (e, variation) {
			base = num(variation.display_price);
			update();
		});
		$form.on('reset_data hide_variation', function () {
			if ($box.data('base') === '') {
				base = null;
			}
			update();
		});
		$form.on('submit', function (e) {
			if (!check()) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
		});
		update();
	}

	$(function () {
		$('.ceacliu-pol').each(function () {
			init($(this));
		});
	});
})(jQuery);
