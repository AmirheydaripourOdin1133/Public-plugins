/**
 * مودال کانفیگ سفارشی — گروه تاکسونومی، چندخطی + دراپ‌داون، قیمت زنده.
 */
(function ($) {
	'use strict';

	var lastVtForm = null;
	var rscVtScrollY = 0;

	function rscVtLockScroll() {
		rscVtScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
		document.documentElement.classList.add('rsc-vt-scroll-lock');
		document.body.classList.add('rsc-vt-scroll-lock');
		document.body.style.top = '-' + rscVtScrollY + 'px';
	}

	function rscVtUnlockScroll() {
		document.documentElement.classList.remove('rsc-vt-scroll-lock');
		document.body.classList.remove('rsc-vt-scroll-lock');
		document.body.style.top = '';
		window.scrollTo(0, rscVtScrollY);
	}

	var prefersReducedMotion =
		typeof window.matchMedia === 'function' &&
		window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function getI18n(key) {
		var i = typeof rscVartable !== 'undefined' && rscVartable.i18n ? rscVartable.i18n : {};
		return i[key] || '';
	}

	function maxGroupLines() {
		var m = typeof rscVartable !== 'undefined' ? parseInt(rscVartable.maxGroupLines, 10) : 8;
		return isNaN(m) || m < 1 ? 8 : Math.min(32, m);
	}

	function formatPrice(n) {
		if (typeof rscVartable === 'undefined' || !rscVartable.priceFormat) {
			return String(n);
		}
		var f = rscVartable.priceFormat;
		var num = parseFloat(n);
		if (isNaN(num)) {
			num = 0;
		}
		var parts = num.toFixed(f.decimals).split('.');
		var intp = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, f.thousand);
		var out = intp;
		if (f.decimals > 0) {
			out += f.decimal + parts[1];
		}
		if (f.symbol_pos === 'left') {
			out = f.symbol + out;
		} else {
			out = out + f.symbol;
		}
		if (f.suffix) {
			out += ' ' + f.suffix;
		}
		return out;
	}

	function getHiddenInput($form) {
		var $h = $form.find('input[name="rsc_config_json"]');
		if (!$h.length) {
			$h = $('<input type="hidden" name="rsc_config_json" value="" />');
			$form.append($h);
		}
		return $h;
	}

	function buildCompsById(groups) {
		var map = {};
		if (!groups || !groups.length) {
			return map;
		}
		groups.forEach(function (g) {
			(g.components || []).forEach(function (c) {
				map[c.id] = c;
			});
		});
		return map;
	}

	function collectLines($modal) {
		var lines = [];
		$modal.find('.rsc-vt-group').each(function () {
			var multi = $(this).attr('data-multi') === '1';
			if (multi) {
				$(this)
					.find('.rsc-vt-group__line')
					.each(function () {
						var id = parseInt($(this).find('.rsc-vt-group__select').val(), 10) || 0;
						var qty = parseInt($(this).find('.rsc-vt-stepper__input').val(), 10) || 0;
						if (qty < 0) {
							qty = 0;
						}
						if (id > 0 && qty > 0) {
							lines.push({ id: id, qty: qty });
						}
					});
			} else {
				$(this)
					.find('.rsc-vt-stepper__input')
					.each(function () {
						var $i = $(this);
						var id = parseInt($i.data('rsc-cid'), 10) || 0;
						var qty = parseInt($i.val(), 10) || 0;
						if (qty < 0) {
							qty = 0;
						}
						if (id > 0 && qty > 0) {
							lines.push({ id: id, qty: qty });
						}
					});
			}
		});
		return lines;
	}

	function hasInvalidMultiLine($modal) {
		var bad = false;
		$modal.find('.rsc-vt-group[data-multi="1"] .rsc-vt-group__line').each(function () {
			var id = parseInt($(this).find('.rsc-vt-group__select').val(), 10) || 0;
			var qty = parseInt($(this).find('.rsc-vt-stepper__input').val(), 10) || 0;
			if (qty > 0 && id === 0) {
				bad = true;
				return false;
			}
		});
		return bad;
	}

	function buildSummaryHtml(lines, compsById) {
		if (!lines.length) {
			return '<p class="rsc-vt-summary__empty">' + escapeHtml(getI18n('summaryEmpty')) + '</p>';
		}
		var groups = {};
		lines.forEach(function (ln) {
			var c = compsById[ln.id];
			if (!c) {
				return;
			}
			var t = c.type_label || getI18n('typeOther');
			if (!groups[t]) {
				groups[t] = [];
			}
			var lineTotal = (parseFloat(c.unit_price) || 0) * ln.qty;
			groups[t].push(
				'<li><span class="rsc-vt-summary__title">' +
					escapeUserText(c.title) +
					'</span> <span class="rsc-vt-summary__qty">×' +
					ln.qty +
					'</span> <span class="rsc-vt-summary__line">' +
					escapeHtml(formatPrice(lineTotal)) +
					'</span></li>'
			);
		});
		var keys = Object.keys(groups).sort(function (a, b) {
			return a.localeCompare(b, 'fa');
		});
		var html = '';
		keys.forEach(function (k) {
			html +=
				'<div class="rsc-vt-summary__group"><div class="rsc-vt-summary__type">' +
				escapeUserText(k) +
				'</div><ul class="rsc-vt-summary__list">' +
				groups[k].join('') +
				'</ul></div>';
		});
		return html;
	}

	function decodeHtmlEntities(str) {
		if (str == null) {
			return '';
		}
		var el = document.createElement('textarea');
		el.innerHTML = String(str);
		return el.value;
	}

	function escapeHtml(s) {
		var d = document.createElement('div');
		d.textContent = s == null ? '' : String(s);
		return d.innerHTML;
	}

	function escapeUserText(str) {
		return escapeHtml(decodeHtmlEntities(str == null ? '' : String(str)));
	}

	function countSelectedPieces(lines) {
		var t = 0;
		lines.forEach(function (ln) {
			t += parseInt(ln.qty, 10) || 0;
		});
		return t;
	}

	function syncAccordionStrip($modal, lines, total) {
		var $sum = $modal.find('.rsc-vt-modal__summary');
		if (!$sum.length) {
			return;
		}
		$sum.find('[data-rsc-accordion-total]').text(formatPrice(total));
		var pieces = countSelectedPieces(lines);
		var $meta = $sum.find('[data-rsc-accordion-meta]');
		if (pieces < 1) {
			$meta.text(getI18n('summaryAccordionZero'));
		} else {
			$meta.text(getI18n('summaryLineCount').replace('%d', String(pieces)));
		}
	}

	function updateLiveSummary($modal, fixedPrice, compsById) {
		var lines = collectLines($modal);
		var parts = 0;
		lines.forEach(function (ln) {
			var c = compsById[ln.id];
			if (c) {
				parts += (parseFloat(c.unit_price) || 0) * ln.qty;
			}
		});
		var total = fixedPrice + parts;
		$modal.find('.rsc-vt-summary__groups').html(buildSummaryHtml(lines, compsById));
		$modal.find('[data-rsc-live="base"]').text(formatPrice(fixedPrice));
		$modal.find('[data-rsc-live="parts"]').text(formatPrice(parts));
		$modal.find('[data-rsc-live="total"]').text(formatPrice(total));
		syncAccordionStrip($modal, lines, total);
	}

	function syncMultiRemoveButtons($group) {
		var $lines = $group.find('.rsc-vt-group__line');
		var n = $lines.length;
		$lines.find('.rsc-vt-group__remove').prop('disabled', n <= 1);
	}

	function buildSelectOptions(components) {
		var ph = escapeHtml(getI18n('selectPlaceholder'));
		var html =
			'<option value="">' + ph + '</option>';
		components.forEach(function (c) {
			html +=
				'<option value="' +
				escapeHtml(String(c.id)) +
				'">' +
				escapeUserText(c.title) +
				' — ' +
				escapeHtml(formatPrice(c.unit_price)) +
				'</option>';
		});
		return html;
	}

	function renderMultiGroup($section, grp) {
		var $lines = $('<div class="rsc-vt-group__lines" />');
		var $line0 = $('<div class="rsc-vt-group__line" />');
		var $sel = $('<select class="rsc-vt-group__select" aria-label="' + escapeUserText(grp.label) + '"></select>');
		$sel.html(buildSelectOptions(grp.components));
		var $wrap = $('<div class="rsc-vt-stepper" />');
		var $minus = $(
			'<button type="button" class="rsc-vt-stepper__btn" data-dir="-1" aria-label="decrease">−</button>'
		);
		var $inp = $(
			'<input type="number" class="rsc-vt-stepper__input" min="0" step="1" value="0" inputmode="numeric" />'
		);
		var $plus = $(
			'<button type="button" class="rsc-vt-stepper__btn" data-dir="1" aria-label="increase">+</button>'
		);
		var $rm = $('<button type="button" class="rsc-vt-group__remove" />').text(getI18n('removeLine'));
		$wrap.append($minus, $inp, $plus);
		$line0.append($sel, $wrap, $rm);
		$lines.append($line0);
		var $add = $('<button type="button" class="rsc-vt-group__add button" />').text(getI18n('addLine'));
		$section.append($lines, $add);
		syncMultiRemoveButtons($section);
	}

	function renderSingleGroup($section, grp) {
		var c = grp.components[0];
		var $row = $('<div class="rsc-vt-group__single" />');
		var $main = $('<div class="rsc-vt-row__main" />').append($('<div class="rsc-vt-row__title" />').text(c.title));
		if (c.type_label && String(c.type_label).trim() !== '') {
			$main.append($('<span class="rsc-vt-row__type" />').text(c.type_label));
		}
		$row.append($main, $('<div class="rsc-vt-row__unit" />').text(formatPrice(c.unit_price)));
		var $wrap = $('<div class="rsc-vt-stepper" />');
		var $minus = $(
			'<button type="button" class="rsc-vt-stepper__btn" data-dir="-1" aria-label="decrease">−</button>'
		);
		var $inp = $(
			'<input type="number" class="rsc-vt-stepper__input" min="0" step="1" value="0" inputmode="numeric" />'
		);
		$inp.attr('data-rsc-cid', c.id);
		var $plus = $(
			'<button type="button" class="rsc-vt-stepper__btn" data-dir="1" aria-label="increase">+</button>'
		);
		$wrap.append($minus, $inp, $plus);
		$row.append($wrap);
		$section.append($row);
	}

	function fillContextRow($summary, productTitle, variationName, variationLabel) {
		var $p = $summary.find('[data-rsc-context-product]');
		var $v = $summary.find('[data-rsc-context-variation]');
		var $wrap = $summary.find('[data-rsc-context-variation-wrap]');
		$p.text(decodeHtmlEntities(String(productTitle || '')) || '—');
		var name = variationName && String(variationName).trim();
		var attrs = variationLabel && String(variationLabel).trim();
		var line = '';
		if (name && attrs && name !== attrs) {
			line = decodeHtmlEntities(name) + ' — ' + decodeHtmlEntities(attrs);
		} else if (name) {
			line = decodeHtmlEntities(name);
		} else if (attrs) {
			line = decodeHtmlEntities(attrs);
		}
		if (line) {
			$v.text(line);
			$wrap.show();
		} else {
			$v.text('');
			$wrap.hide();
		}
	}

	function resetModalBody($modal) {
		$modal.find('.rsc-vt-group').each(function () {
			var multi = $(this).attr('data-multi') === '1';
			if (multi) {
				var $lines = $(this).find('.rsc-vt-group__lines');
				var $first = $lines.find('.rsc-vt-group__line').first();
				$lines.find('.rsc-vt-group__line').slice(1).remove();
				$first.find('.rsc-vt-group__select').val('');
				$first.find('.rsc-vt-stepper__input').val(0);
				syncMultiRemoveButtons($(this));
			} else {
				$(this).find('.rsc-vt-stepper__input').val(0);
			}
		});
	}

	function openModal(variationId, $form) {
		getHiddenInput($form).val('');

		var $overlay = $('<div class="rsc-vt-modal-overlay" role="dialog" aria-modal="true" />');
		var $modal = $('<div class="rsc-vt-modal" />');
		var $err = $('<div class="rsc-vt-modal__err" role="alert" aria-live="polite" />');
		var accBtnId = 'rsc-vt-acc-btn-' + variationId;
		var accPanelId = 'rsc-vt-acc-panel-' + variationId;
		var $summary = $(
			'<div class="rsc-vt-modal__summary">' +
				'<div class="rsc-vt-modal__context">' +
				'<span class="rsc-vt-context__label">' +
				escapeHtml(getI18n('summaryHeading')) +
				'</span><span class="rsc-vt-context__colon"> :</span> ' +
				'<span class="rsc-vt-context__value">' +
				'<span class="rsc-vt-context__product" data-rsc-context-product></span>' +
				'<span class="rsc-vt-context__variation-wrap" data-rsc-context-variation-wrap style="display:none">' +
				'<span class="rsc-vt-context__sep"> — </span>' +
				'<span class="rsc-vt-context__variation" data-rsc-context-variation></span>' +
				'</span></span></div>' +
				'<div class="rsc-vt-summary__accordion">' +
				'<button type="button" class="rsc-vt-summary__toggle" id="' +
				accBtnId +
				'" aria-expanded="true" aria-controls="' +
				accPanelId +
				'">' +
				'<span class="rsc-vt-summary__toggle-main">' +
				'<span class="rsc-vt-summary__toggle-title">' +
				escapeHtml(getI18n('summaryAccordionHead')) +
				'</span>' +
				'<span class="rsc-vt-summary__toggle-stats">' +
				'<span class="rsc-vt-summary__toggle-meta" data-rsc-accordion-meta></span>' +
				'<strong class="rsc-vt-summary__toggle-total" data-rsc-accordion-total>—</strong>' +
				'</span></span>' +
				'<span class="rsc-vt-summary__toggle-chevron" aria-hidden="true"></span>' +
				'</button>' +
				'<div class="rsc-vt-summary__panel-outer" id="' +
				accPanelId +
				'" role="region" aria-labelledby="' +
				accBtnId +
				'">' +
				'<div class="rsc-vt-summary__panel-inner">' +
				'<div class="rsc-vt-summary__scroll">' +
				'<div class="rsc-vt-summary__groups"></div>' +
				'</div>' +
				'<div class="rsc-vt-modal__prices">' +
				'<div class="rsc-vt-modal__price-row"><span>' +
				escapeHtml(getI18n('priceBase')) +
				'</span><strong data-rsc-live="base">—</strong></div>' +
				'<div class="rsc-vt-modal__price-row"><span>' +
				escapeHtml(getI18n('priceParts')) +
				'</span><strong data-rsc-live="parts">—</strong></div>' +
				'<div class="rsc-vt-modal__price-row rsc-vt-modal__price-row--total"><span>' +
				escapeHtml(getI18n('priceTotal')) +
				'</span><strong data-rsc-live="total">—</strong></div>' +
				'<p class="rsc-vt-modal__price-hint">' +
				escapeHtml(getI18n('perUnitHint')) +
				'</p>' +
				'</div></div></div></div></div>'
		);
		var $body = $('<div class="rsc-vt-modal__body" />');
		var $foot = $('<div class="rsc-vt-modal__foot" />');

		$modal.append(
			$('<div class="rsc-vt-modal__head" />').append(
				$('<span class="rsc-vt-modal__head-title" />').text(getI18n('modalTitle')),
				$(
					'<button type="button" class="rsc-vt-modal__close" aria-label="' +
						escapeHtml(getI18n('close')) +
						'">&times;</button>'
				)
			),
			$err,
			$body,
			$summary
		);
		var $btnSubmit = $(
			'<button type="button" class="button button-primary rsc-vt-modal__submit" />'
		).html(
			'<span class="rsc-vt-modal__submit-label">' +
				escapeHtml(getI18n('submitAddToCart')) +
				'</span><span class="rsc-vt-modal__submit-spinner" aria-hidden="true"></span>'
		);
		$btnSubmit.prop('disabled', true);
		var $btnReset = $('<button type="button" class="rsc-vt-modal__reset" />').text(
			getI18n('resetSelection')
		);
		var $actions = $('<div class="rsc-vt-modal__foot-actions" />').append($btnSubmit);
		$foot.append($btnReset, $actions);
		$modal.append($foot);
		$overlay.append($modal);
		$('body').append($overlay);
		rscVtLockScroll();

		var $accToggle = $summary.find('.rsc-vt-summary__toggle');
		var accNarrow =
			typeof window.matchMedia === 'function' && window.matchMedia('(max-width: 640px)').matches;
		if (accNarrow) {
			$summary.removeClass('is-accordion-open');
			$accToggle
				.attr('aria-expanded', 'false')
				.attr('aria-label', getI18n('summaryAccordionExpand'));
		} else {
			$summary.addClass('is-accordion-open');
			$accToggle
				.attr('aria-expanded', 'true')
				.attr('aria-label', getI18n('summaryAccordionCollapse'));
		}

		$modal.on('click', '.rsc-vt-summary__toggle', function (e) {
			e.preventDefault();
			var $btn = $(this);
			var nextOpen = !$summary.hasClass('is-accordion-open');
			$summary.toggleClass('is-accordion-open', nextOpen);
			$btn.attr('aria-expanded', nextOpen ? 'true' : 'false');
			$btn.attr(
				'aria-label',
				nextOpen ? getI18n('summaryAccordionCollapse') : getI18n('summaryAccordionExpand')
			);
		});

		var fixedPrice = 0;
		var compsById = {};
		var componentCount = 0;
		var transitionMs = prefersReducedMotion ? 0 : 220;

		function modalMayDismiss() {
			return !$btnSubmit.hasClass('is-loading');
		}

		function animateRemove() {
			$(document).off('keydown.rscVtModal');
			$modal.off();
			$overlay.removeClass('is-visible');
			setTimeout(function () {
				$overlay.remove();
				rscVtUnlockScroll();
			}, transitionMs);
		}

		if (!prefersReducedMotion) {
			requestAnimationFrame(function () {
				requestAnimationFrame(function () {
					$overlay.addClass('is-visible');
				});
			});
		} else {
			$overlay.addClass('is-visible');
		}

		$overlay.on('click', function (e) {
			if (e.target === $overlay[0] && modalMayDismiss()) {
				animateRemove();
			}
		});
		$modal.on('click', '.rsc-vt-modal__close', function (e) {
			e.preventDefault();
			if (modalMayDismiss()) {
				animateRemove();
			}
		});
		$(document).on('keydown.rscVtModal', function (e) {
			if (e.key === 'Escape' && modalMayDismiss()) {
				animateRemove();
			}
		});

		function clearValidationError() {
			$err.removeClass('is-visible').empty();
		}

		$foot.on('click', '.rsc-vt-modal__reset', function (e) {
			e.preventDefault();
			if ($btnSubmit.prop('disabled')) {
				return;
			}
			resetModalBody($modal);
			getHiddenInput($form).val('');
			clearValidationError();
			updateLiveSummary($modal, fixedPrice, compsById);
		});

		$body.html('<p class="rsc-vt-modal__loading">' + escapeHtml(getI18n('loading')) + '</p>');

		$.post(rscVartable.ajaxUrl, {
			action: 'rsc_variation_config_payload',
			nonce: rscVartable.nonce,
			variation_id: variationId,
			product_id: rscVartable.productId || 0
		})
			.done(function (res) {
				var groups = res && res.success && res.data && res.data.groups;
				if (!groups || !groups.length) {
					$err.text(getI18n('noComponents')).addClass('is-visible');
					$body.empty();
					return;
				}
				fixedPrice = parseFloat(res.data.fixed_price) || 0;
				compsById = buildCompsById(groups);
				componentCount = Object.keys(compsById).length;
				fillContextRow(
					$summary,
					res.data.product_title,
					res.data.variation_name || '',
					res.data.variation_label || ''
				);

				$body.empty();
				clearValidationError();

				groups.forEach(function (grp) {
					var multi = !!grp.multi;
					var $sec = $('<section class="rsc-vt-group" />');
					$sec.attr('data-multi', multi ? '1' : '0');
					$sec.attr('data-group-key', String(grp.key || ''));
					var gl =
						grp.label != null && String(grp.label).trim() !== ''
							? decodeHtmlEntities(String(grp.label)).trim()
							: '';
					$sec.append($('<h3 class="rsc-vt-group__title" />').text(gl || getI18n('typeOther')));
					if (multi) {
						renderMultiGroup($sec, grp);
					} else if (grp.components && grp.components.length === 1) {
						renderSingleGroup($sec, grp);
					}
					$body.append($sec);
				});

				$modal.on('click', '.rsc-vt-stepper__btn', function (e) {
					e.preventDefault();
					var dir = parseInt($(this).attr('data-dir'), 10) || 0;
					var $inpLocal = $(this).siblings('.rsc-vt-stepper__input');
					if (!$inpLocal.length) {
						return;
					}
					var v = parseInt($inpLocal.val(), 10) || 0;
					v = Math.max(0, v + dir);
					$inpLocal.val(v).trigger('change');
				});

				$modal.on('change input', '.rsc-vt-stepper__input', function () {
					var $i = $(this);
					var v = parseInt($i.val(), 10);
					if (isNaN(v) || v < 0) {
						v = 0;
					}
					$i.val(v);
					if (collectLines($modal).length > 0) {
						clearValidationError();
					}
					updateLiveSummary($modal, fixedPrice, compsById);
				});

				$modal.on('change', '.rsc-vt-group__select', function () {
					var $line = $(this).closest('.rsc-vt-group__line');
					var id = parseInt($(this).val(), 10) || 0;
					var $q = $line.find('.rsc-vt-stepper__input');
					if (id > 0) {
						var q = parseInt($q.val(), 10) || 0;
						if (q < 1) {
							$q.val(1);
						}
					} else {
						$q.val(0);
					}
					if (collectLines($modal).length > 0) {
						clearValidationError();
					}
					updateLiveSummary($modal, fixedPrice, compsById);
				});

				$modal.on('click', '.rsc-vt-group__add', function (e) {
					e.preventDefault();
					var $g = $(this).closest('.rsc-vt-group');
					var $lines = $g.find('.rsc-vt-group__lines');
					var $first = $lines.find('.rsc-vt-group__line').first();
					var maxL = maxGroupLines();
					if ($lines.find('.rsc-vt-group__line').length >= maxL) {
						return;
					}
					var $clone = $first.clone(false, false);
					$clone.find('.rsc-vt-group__select').val('');
					$clone.find('.rsc-vt-stepper__input').val(0);
					$lines.append($clone);
					syncMultiRemoveButtons($g);
					updateLiveSummary($modal, fixedPrice, compsById);
				});

				$modal.on('click', '.rsc-vt-group__remove', function (e) {
					e.preventDefault();
					var $g = $(this).closest('.rsc-vt-group');
					var $lines = $g.find('.rsc-vt-group__lines');
					var $line = $(this).closest('.rsc-vt-group__line');
					if ($lines.find('.rsc-vt-group__line').length <= 1) {
						return;
					}
					$line.remove();
					syncMultiRemoveButtons($g);
					updateLiveSummary($modal, fixedPrice, compsById);
				});

				updateLiveSummary($modal, fixedPrice, compsById);
				$btnSubmit.prop('disabled', false);
			})
			.fail(function () {
				$err.text(getI18n('loadError')).addClass('is-visible');
				$body.empty();
			});

		$btnSubmit.on('click', function () {
			if ($btnSubmit.prop('disabled')) {
				return;
			}
			if (hasInvalidMultiLine($modal)) {
				$err.text(getI18n('validationSelectModel')).addClass('is-visible');
				var $eb = $err[0];
				if ($eb && $eb.scrollIntoView) {
					$eb.scrollIntoView({ block: 'nearest', behavior: prefersReducedMotion ? 'auto' : 'smooth' });
				}
				return;
			}
			var lines = collectLines($modal);
			if (componentCount > 0 && lines.length === 0) {
				$err.text(getI18n('validationNeedPart')).addClass('is-visible');
				var $eb2 = $err[0];
				if ($eb2 && $eb2.scrollIntoView) {
					$eb2.scrollIntoView({ block: 'nearest', behavior: prefersReducedMotion ? 'auto' : 'smooth' });
				}
				return;
			}

			var $hidden = getHiddenInput($form);
			if (lines.length) {
				$hidden.val(JSON.stringify(lines));
			} else {
				$hidden.val('');
			}

			$btnSubmit.prop('disabled', true);
			$modal.find('.rsc-vt-modal__reset, .rsc-vt-modal__close, .rsc-vt-summary__toggle').prop('disabled', true);
			$btnSubmit.addClass('is-loading');
			$btnSubmit.find('.rsc-vt-modal__submit-label').text(getI18n('adding'));

			var $sub = $form.find('button[type="submit"]').first();
			if ($sub.length) {
				animateRemove();
				setTimeout(function () {
					$sub.trigger('click');
				}, Math.max(0, transitionMs - 40));
			} else {
				$btnSubmit.prop('disabled', false);
				$modal.find('.rsc-vt-modal__reset, .rsc-vt-modal__close, .rsc-vt-summary__toggle').prop('disabled', false);
				$btnSubmit.removeClass('is-loading');
				$btnSubmit.find('.rsc-vt-modal__submit-label').text(getI18n('submitAddToCart'));
			}
		});
	}

	$(document).on('click', '.rsc-vt-config-btn', function (e) {
		e.preventDefault();
		if (typeof rscVartable === 'undefined') {
			return;
		}
		var $form = $(this).closest('form.vtajaxform');
		var vid = parseInt($(this).data('rsc-variation-id'), 10);
		if (vid && $form.length) {
			openModal(vid, $form);
		}
	});

	document.addEventListener(
		'submit',
		function (e) {
			if (e.target && e.target.matches && e.target.matches('form.vtajaxform')) {
				lastVtForm = e.target;
			}
		},
		true
	);

	if (typeof jQuery !== 'undefined' && jQuery.ajaxPrefilter) {
		jQuery.ajaxPrefilter(function (options) {
			if (!options || !options.data) {
				return;
			}
			var d = options.data;
			if (typeof d !== 'string') {
				return;
			}
			if (d.indexOf('action=add_variation_to_cart') === -1) {
				return;
			}
			var form = lastVtForm;
			if (!form) {
				return;
			}
			var inp = form.querySelector('input[name="rsc_config_json"]');
			if (!inp || !inp.value) {
				return;
			}
			options.data += '&rsc_config_json=' + encodeURIComponent(inp.value);
		});
	}
})(jQuery);
