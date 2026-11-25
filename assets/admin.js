/* Ceacliu Product Options Lite – the field editor of an option group. */
(function ($) {
	'use strict';
	var A = window.ceacliuPOLAdmin || { types: {}, t: {} };
	var T = A.t;
	var $root = $('#ceacliu-pol-editor');
	if (!$root.length) {
		return;
	}
	var fields = [];
	try {
		fields = JSON.parse($root.attr('data-fields') || '[]') || [];
	} catch (e) {
		fields = [];
	}

	function esc(s) {
		return String(s === undefined || s === null ? '' : s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function isList(f) {
		return f.type !== 'textarea';
	}
	function newId() {
		return 'f' + Math.random().toString(36).slice(2, 10);
	}
	function blank() {
		return { label: '', price: '', 'default': false };
	}
	function save() {
		$('#ceacliu-pol-json').val(JSON.stringify(fields));
	}
	function typeOptions(cur) {
		return Object.keys(A.types).map(function (k) {
			return '<option value="' + k + '"' + (k === cur ? ' selected' : '') + '>' + esc(A.types[k]) + '</option>';
		}).join('');
	}
	function btn(cls, label, icon) {
		return '<button type="button" class="button-link ' + cls + '" title="' + esc(label) + '" aria-label="' + esc(label) + '"><span class="dashicons dashicons-' + icon + '"></span></button>';
	}

	function choiceRow(f, c, i) {
		return '<tr data-i="' + i + '">' +
			'<td><input type="text" class="c-label" value="' + esc(c.label) + '"></td>' +
			'<td><input type="text" inputmode="decimal" class="c-price" value="' + esc(c.price || '') + '" placeholder="0"></td>' +
			'<td class="c-def"><input type="' + (f.type === 'checkbox' ? 'checkbox' : 'radio') + '" class="c-default"' + (c['default'] ? ' checked' : '') + '></td>' +
			'<td class="c-act">' + btn('c-up', T.up, 'arrow-up-alt2') + btn('c-down', T.down, 'arrow-down-alt2') + btn('c-del', T.remove, 'no-alt') + '</td></tr>';
	}

	function fieldCard(f, i) {
		var h = '<div class="cpol-card" data-i="' + i + '">';
		h += '<div class="cpol-card-head"><span class="cpol-num">' + (i + 1) + '</span>' +
			'<input type="text" class="f-label" value="' + esc(f.label) + '" placeholder="' + esc(T.labelHint) + '" aria-label="' + esc(T.label) + '">' +
			'<select class="f-type" aria-label="' + esc(T.type) + '">' + typeOptions(f.type) + '</select>' +
			'<span class="cpol-head-btns">' + btn('f-up', T.up, 'arrow-up-alt2') + btn('f-down', T.down, 'arrow-down-alt2') + btn('f-del', T.remove, 'trash') + '</span></div>';
		h += '<div class="cpol-card-body">';
		h += '<p><input type="text" class="f-desc widefat" value="' + esc(f.desc) + '" placeholder="' + esc(T.desc) + '"></p>';
		h += '<p class="cpol-opts">';
		if (f.type === 'checkbox') {
			h += '<label>' + esc(T.max) + ' <input type="number" min="0" class="f-max small-text" value="' + esc(f.max || 0) + '"></label>';
		} else {
			h += '<label><input type="checkbox" class="f-required"' + (f.required ? ' checked' : '') + '> ' + esc(T.required) + '</label>';
		}
		if (!isList(f)) {
			h += '<label>' + esc(T.maxlen) + ' <input type="number" min="0" class="f-maxlen small-text" value="' + esc(f.maxlen || 0) + '"></label>';
		}
		h += '</p>';
		if (isList(f)) {
			h += '<div class="cpol-table-wrap"><table class="cpol-choices widefat"><thead><tr><th>' + esc(T.choice) + '</th><th>' + esc(T.priceAdd) + '</th><th>' + esc(T['default']) + '</th><th></th></tr></thead><tbody>';
			(f.choices || []).forEach(function (c, ci) {
				h += choiceRow(f, c, ci);
			});
			h += '</tbody></table></div><p><button type="button" class="button c-add">+ ' + esc(T.addChoice) + '</button></p>';
		}
		h += '</div></div>';
		return h;
	}

	function render() {
		var h = fields.length ? fields.map(fieldCard).join('') : '<p class="cpol-empty">' + esc(T.empty) + '</p>';
		h += '<p class="cpol-add"><select class="new-type">' + typeOptions('checkbox') + '</select> <button type="button" class="button button-primary f-add">+ ' + esc(T.addField) + '</button></p>';
		$root.html(h);
		save();
	}

	function fieldOf(el) {
		return fields[parseInt($(el).closest('.cpol-card').data('i'), 10)];
	}
	function move(arr, i, d) {
		var j = i + d;
		if (j < 0 || j >= arr.length) {
			return;
		}
		var t = arr[i];
		arr[i] = arr[j];
		arr[j] = t;
	}

	$root.on('click', '.f-add', function () {
		var type = $root.find('.new-type').val();
		fields.push({ id: newId(), type: type, label: '', desc: '', required: false, max: 0, maxlen: 0, choices: type === 'textarea' ? [] : [blank()] });
		render();
		$root.find('.cpol-card').last().find('.f-label').trigger('focus');
	});
	$root.on('click', '.f-del', function () {
		if (window.confirm(T.confirm)) {
			fields.splice(parseInt($(this).closest('.cpol-card').data('i'), 10), 1);
			render();
		}
	});
	$root.on('click', '.f-up, .f-down', function () {
		move(fields, parseInt($(this).closest('.cpol-card').data('i'), 10), $(this).hasClass('f-up') ? -1 : 1);
		render();
	});
	$root.on('change', '.f-type', function () {
		var f = fieldOf(this);
		f.type = this.value;
		if (isList(f) && (!f.choices || !f.choices.length)) {
			f.choices = [blank()];
		}
		render();
	});
	$root.on('input change', '.f-label, .f-desc, .f-max, .f-maxlen, .f-required', function () {
		var f = fieldOf(this);
		var $c = $(this).closest('.cpol-card');
		f.label = $c.find('.f-label').val();
		f.desc = $c.find('.f-desc').val();
		f.required = $c.find('.f-required').is(':checked');
		f.max = parseInt($c.find('.f-max').val(), 10) || 0;
		f.maxlen = parseInt($c.find('.f-maxlen').val(), 10) || 0;
		save();
	});

	$root.on('click', '.c-add', function () {
		var f = fieldOf(this);
		f.choices.push(blank());
		render();
		$root.find('.cpol-card[data-i="' + fields.indexOf(f) + '"] .c-label').last().trigger('focus');
	});
	$root.on('click', '.c-del, .c-up, .c-down', function () {
		var f = fieldOf(this);
		var ci = parseInt($(this).closest('tr').data('i'), 10);
		if ($(this).hasClass('c-del')) {
			f.choices.splice(ci, 1);
		} else {
			move(f.choices, ci, $(this).hasClass('c-up') ? -1 : 1);
		}
		render();
	});
	$root.on('input change', '.c-label, .c-price, .c-default', function () {
		var f = fieldOf(this);
		var $tr = $(this).closest('tr');
		var c = f.choices[parseInt($tr.data('i'), 10)];
		c.label = $tr.find('.c-label').val();
		c.price = $tr.find('.c-price').val();
		if ($(this).hasClass('c-default')) {
			var on = this.checked;
			if (f.type !== 'checkbox') { // one default only
				f.choices.forEach(function (x) {
					x['default'] = false;
				});
				$(this).closest('tbody').find('.c-default').not(this).prop('checked', false);
			}
			c['default'] = on;
		}
		save();
	});
	// Enter in a choice adds the next one (fast typing of long topping lists).
	$root.on('keydown', '.c-label, .c-price', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			$(this).closest('.cpol-card').find('.c-add').trigger('click');
		}
	});
	$('#post').on('submit', save);
	render();
})(jQuery);
