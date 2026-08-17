/*
 * Accessibility Lite — frontend script (~3 KB, vanilla JS, no jQuery)
 */
(function () {
	'use strict';

	var KEY = 'al-a11y';
	var MIN = 100;
	var MAX = 200;
	var STEP = 10;

	var toolbar = document.getElementById('al-toolbar');
	if (!toolbar) {
		return;
	}

	var body = document.body;
	var opts = window.AlA11yOpts || {};
	var toggleBtn = toolbar.querySelector('.al-toggle button');
	var active = {};
	var scale = MIN;
	var schema = null;

	function btn(action) {
		return toolbar.querySelector('.al-items [data-action="' + action + '"]');
	}

	function setBtn(action, on) {
		var b = btn(action);
		if (b) {
			b.classList.toggle('active', !!on);
		}
		if (on) {
			active[action] = true;
		} else {
			delete active[action];
		}
	}

	function setClass(action, on) {
		body.classList.toggle('al-' + action, !!on);
	}

	function applyScale() {
		var activeScale = scale > MIN;
		body.classList.toggle('al-resize', activeScale);
		body.style.setProperty('--al-scale', (scale / 100).toFixed(2));
		setBtn('resize-plus', activeScale);
	}

	function setSchema(action) {
		if (schema) {
			setClass(schema, false);
			setBtn(schema, false);
		}
		schema = action || null;
		if (schema) {
			setClass(schema, true);
			setBtn(schema, true);
		}
	}

	function toggle(action) {
		var on = !active[action];
		setClass(action, on);
		setBtn(action, on);
	}

	function resize(dir) {
		var next = scale + dir * STEP;
		if (next < MIN || next > MAX) {
			return;
		}
		scale = next;
		applyScale();
		save();
	}

	function reset() {
		for (var a in active) {
			if (Object.prototype.hasOwnProperty.call(active, a)) {
				setClass(a, false);
				setBtn(a, false);
			}
		}
		active = {};
		schema = null;
		scale = MIN;
		body.classList.remove('al-resize');
		body.style.removeProperty('--al-scale');
		clearSave();
	}

	function save() {
		if (opts.save !== '1') {
			return;
		}
		var hours = parseInt(opts.saveExp, 10) || 720;
		try {
			localStorage.setItem(KEY, JSON.stringify({
				actions: active,
				scale: scale,
				schema: schema,
				expires: Date.now() + hours * 36e5
			}));
		} catch (e) { /* storage unavailable */ }
	}

	function clearSave() {
		try {
			localStorage.removeItem(KEY);
		} catch (e) { /* ignore */ }
	}

	function restore() {
		if (opts.save !== '1') {
			return;
		}
		var raw;
		try {
			raw = localStorage.getItem(KEY);
		} catch (e) {
			return;
		}
		if (!raw) {
			return;
		}
		var d;
		try {
			d = JSON.parse(raw);
		} catch (e) {
			return;
		}
		if (!d || !d.expires || d.expires < Date.now()) {
			clearSave();
			return;
		}
		var a = d.actions || {};
		scale = Math.min(MAX, Math.max(MIN, d.scale || MIN));
		applyScale();
		if (d.schema && a[d.schema]) {
			setSchema(d.schema);
		}
		['links-underline', 'readable-font'].forEach(function (k) {
			if (a[k]) {
				toggle(k);
			}
		});
	}

	function openToolbar() {
		toolbar.classList.add('al-open');
		toggleBtn.setAttribute('aria-expanded', 'true');
	}

	function closeToolbar() {
		toolbar.classList.remove('al-open');
		toggleBtn.setAttribute('aria-expanded', 'false');
	}

	var SCHEMA_ACTIONS = ['grayscale', 'high-contrast', 'negative-contrast', 'light-bg'];

	toolbar.addEventListener('click', function (e) {
		var b = e.target.closest('[data-action]');
		if (!b) {
			return;
		}
		var action = b.getAttribute('data-action');

		if (action === 'reset') {
			reset();
			return;
		}
		if (action === 'resize-plus') {
			resize(1);
			return;
		}
		if (action === 'resize-minus') {
			resize(-1);
			return;
		}
		if (SCHEMA_ACTIONS.indexOf(action) !== -1) {
			setSchema(active[action] ? null : action);
			save();
			return;
		}
		toggle(action);
		save();
	});

	toggleBtn.addEventListener('click', function () {
		if (toolbar.classList.contains('al-open')) {
			closeToolbar();
		} else {
			openToolbar();
		}
	});

	/* Tab onto the toggle button opens the toolbar (same as the original) */
	toggleBtn.addEventListener('keydown', function (e) {
		if (e.key === 'Tab') {
			openToolbar();
		}
	});

	/* Escape closes the toolbar */
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && toolbar.classList.contains('al-open')) {
			closeToolbar();
			toggleBtn.focus();
		}
	});

	restore();
})();
