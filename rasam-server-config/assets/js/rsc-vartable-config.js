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

	function getVariationSpec(productTitle, variationName, variationLabel) {
		var parent = decodeHtmlEntities(String(productTitle || '')).trim();
		var label = decodeHtmlEntities(String(variationLabel || '')).trim();
		var name = decodeHtmlEntities(String(variationName || '')).trim();
		var spec = label || name;
		if (!spec) {
			return '';
		}
		if (parent && spec.indexOf(parent) === 0) {
			spec = spec.slice(parent.length);
		}
		if (name && name !== parent && spec.indexOf(name) === 0) {
			spec = spec.slice(name.length);
		}
		spec = spec.replace(/^[\s,،\-–—:|]+/, '');
		spec = spec.replace(/سفارشی/g, '');
		spec = spec.replace(/کانفیگ\s*پیشنهادی\s*[:：]?\s*/g, '');
		spec = spec.replace(/کانفیگ\s*سفارشی\s*[:：]?\s*/g, '');
		spec = spec.replace(/^[\s\-–—]+/, '').replace(/\s{2,}/g, ' ').trim();
		return spec;
	}

	function buildProductConfigLine(productTitle, variationName, variationLabel) {
		var base = decodeHtmlEntities(String(productTitle || '')).trim();
		var spec = getVariationSpec(productTitle, variationName, variationLabel);
		var cfgLabel = getI18n('configLabel') || 'کانفیگ';
		if (!base) {
			return spec;
		}
		if (!spec) {
			return base;
		}
		if (/^کانفیگ\s*[:：]/.test(spec)) {
			return base + ' - ' + spec;
		}
		return base + ' - ' + cfgLabel + ': ' + spec;
	}

	function buildPartsCompact(lines, compsById) {
		var parts = [];
		lines.forEach(function (ln) {
			var c = compsById[ln.id];
			if (!c) {
				return;
			}
			parts.push(String(c.title) + ' × ' + ln.qty);
		});
		return parts.join('، ');
	}

	/** ASCII hyphen — TCPDF/IRANYekan گلیف em-dash (—) ندارد. */
	var QUOTATION_CONFIG_SEP = ' - ';

	function stripRtlScript(text) {
		var s = String(text == null ? '' : text);
		if (!s) {
			return '';
		}
		s = s.replace(/[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]+/g, '');
		s = s.replace(/[،؍؛؟«»]+/g, '');
		s = s.replace(/\s{2,}/g, ' ').trim();
		s = s.replace(/^[\s,;:\/\-–—|]+|[\s,;:\/\-–—|]+$/g, '');
		return s;
	}

	function splitQuotationConfigItems(spec) {
		spec = stripRtlScript(spec);
		if (!spec) {
			return [];
		}
		return spec
			.split(/\s*[,،\/|]+\s*|\s+[\-–—]\s+/)
			.map(function (chunk) {
				return chunk.trim();
			})
			.filter(function (chunk) {
				return chunk !== '';
			});
	}

	function implodeQuotationConfigItems(items) {
		return items
			.map(function (item) {
				return String(item || '').trim();
			})
			.filter(function (item) {
				return item !== '';
			})
			.join(QUOTATION_CONFIG_SEP);
	}

	function normalizeQuotationConfigBlob(configRaw) {
		var s = String(configRaw == null ? '' : configRaw).trim();
		if (!s) {
			return '';
		}
		s = s.replace(/[\u2014\u2013\u2212]/g, '-');
		var labels = [
			'قطعات\\s*اضافه',
			'اضافه\\s*قطعات',
			'کانفیگ\\s*پیشنهادی',
			'کانفیگ\\s*سفارشی',
			'کانفیگ'
		];
		labels.forEach(function (label) {
			s = s.replace(new RegExp('[\\s\\/|]*' + label + '[\\s\\/|]*[:：]?[\\s\\/|]*', 'gi'), QUOTATION_CONFIG_SEP);
		});
		s = s.replace(/\s*\/\s*/g, QUOTATION_CONFIG_SEP);
		s = s.replace(/\s*[:：]+\s*/g, ' ');
		return implodeQuotationConfigItems(splitQuotationConfigItems(s));
	}

	/** ستون کانفیگ پیش‌فاکتور: فقط لاتین، وریشن سپس قطعات، با «—». */
	function buildQuotationConfigText(variationSpec, lines, compsById) {
		var items = splitQuotationConfigItems(variationSpec);
		lines.forEach(function (ln) {
			var c = compsById[ln.id];
			if (!c) {
				return;
			}
			var part = stripRtlScript(String(c.title) + ' × ' + ln.qty);
			if (part) {
				items.push(part);
			}
		});
		return implodeQuotationConfigItems(items);
	}

	function normalizeQuotationProductName(productName) {
		var name = String(productName == null ? '' : productName).trim();
		if (!name) {
			return '';
		}
		var title = name;
		var configRaw = '';
		var paren = name.indexOf('(');
		if (paren !== -1) {
			title = name.slice(0, paren).trim();
			configRaw = name.slice(paren + 1).replace(/\)\s*$/, '').trim();
		}
		var config = normalizeQuotationConfigBlob(configRaw);
		if (!config) {
			return title;
		}
		return title + ' (' + config + ')';
	}

	/**
	 * همان قرارداد wc-request-quotation: قبل از «(» فقط عنوان محصول؛ داخل پرانتز کانفیگ لاتین.
	 */
	function buildQuotationProductName(productTitle, variationSpec, lines, compsById) {
		var title = decodeHtmlEntities(String(productTitle || '')).trim();
		var config = buildQuotationConfigText(variationSpec, lines, compsById);
		if (!config) {
			return title;
		}
		return normalizeQuotationProductName(title + ' (' + config + ')');
	}

	function computePartsTotal(lines, compsById) {
		var parts = 0;
		lines.forEach(function (ln) {
			var c = compsById[ln.id];
			if (c) {
				parts += (parseFloat(c.unit_price) || 0) * ln.qty;
			}
		});
		return parts;
	}

	/**
	 * متن ارسالی به پیش‌فاکتور (TCPDF + IRANYekan): حذف مارک‌های دوجهته و جداکننده‌هایی که گلیف ندارند.
	 */
	function sanitizeForRqPdf(str) {
		if (str == null || str === '') {
			return '';
		}
		var s = String(str);
		s = s.replace(/[\u200e\u200f\u061c\u202a-\u202e\u2066-\u2069\u200b-\u200d\ufeff]/g, '');
		s = s.replace(/[\u00a0\u1680\u2000-\u200a\u202f\u205f\u3000]/g, ' ');
		s = s.replace(/[\u2014\u2013\u2212\u2010\u2011]/g, '-');
		s = s.replace(/\|/g, '/');
		s = s.replace(/\s{2,}/g, ' ').trim();
		return s;
	}

	/** هم‌راستا با wc-request-quotation: فیلدهای مخفی #wc-rq-* و باز کردن اورلی. */
	function openWcRequestQuotationFromRsc(opts) {
		if (typeof wc_rq_ajax === 'undefined' || typeof jQuery === 'undefined') {
			return;
		}
		var $ = jQuery;
		var variationId = parseInt(opts.variationId, 10) || 0;
		var parentId = parseInt(opts.parentId, 10) || 0;
		var fullName = opts.fullName || '';
		var unitPrice = opts.unitPrice;
		$('#wc-rq-is-variable').val(variationId > 0 ? '1' : '0');
		var $pid = $('#wc-rq-product-id');
		$pid.val(String(variationId > 0 ? variationId : parentId));
		$pid.data('original-id', parentId);
		$pid.data('variation-id', variationId > 0 ? String(variationId) : '');
		$('#wc-rq-product-name').val(sanitizeForRqPdf(normalizeQuotationProductName(fullName)));
		$('#wc-rq-product-price').val(String(unitPrice));
		$('#wc-rq-qty').val(1);
		$('#noticFormsMy').text('').css({ color: '', 'font-size': '', 'margin-top': '' });
		$('#wc-rq-popup-overlay').fadeIn(200);
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

	function fillContextRow($summary, productConfigLine) {
		var $p = $summary.find('[data-rsc-context-product]');
		var $wrap = $summary.find('[data-rsc-context-variation-wrap]');
		var line = productConfigLine && String(productConfigLine).trim();
		$p.text(line || '—');
		$wrap.hide();
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
		var cartSvg =
			'<svg class="rsc-vt-modal__submit-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">' +
			'<path d="M7.53657 21.25H7.54758" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>' +
			'<path d="M17.9381 21.25H17.9491" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>' +
			'<path d="M4.62838 6.52571H9.60334C12.3104 6.52571 15.0175 6.52571 17.7246 6.52571C18.2498 6.50271 18.7743 6.58341 19.2682 6.76318C19.5116 6.85995 19.73 7.01065 19.9069 7.20397C20.0837 7.39727 20.2145 7.62817 20.2893 7.87927C20.3493 8.45024 20.2761 9.02733 20.0756 9.56528C19.9449 10.1351 19.8263 10.7526 19.7075 11.275C19.4462 12.4624 19.1851 13.6497 18.9714 14.8371C18.9205 15.4101 18.7016 15.9553 18.3421 16.4043C18.1157 16.6114 17.8494 16.7699 17.5594 16.8699C17.2693 16.9699 16.962 17.0095 16.6561 16.986C15.6349 16.986 14.602 16.986 13.569 16.986H9.80509C9.25234 17.0394 8.69568 17.0394 8.14288 16.986C7.8537 16.9546 7.5781 16.8469 7.34439 16.6736C7.11069 16.5004 6.9273 16.2681 6.81307 16.0006C6.61691 15.3381 6.46226 14.6639 6.35001 13.9822C6.25503 13.4478 6.13629 12.9136 6.01756 12.3793C5.60199 10.3964 5.12706 8.43731 4.62838 6.52571ZM4.62838 6.52571C4.31967 5.26714 3.9991 4.00857 3.69039 2.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>' +
			'<path d="M19.5531 11.9993H5.93447" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>' +
			'</svg>';
		var $btnSubmit = $(
			'<button type="button" class="button button-primary rsc-vt-modal__submit" />'
		).html(
			cartSvg +
				'<span class="rsc-vt-modal__submit-label">' +
				escapeHtml(getI18n('submitAddToCart')) +
				'</span><span class="rsc-vt-modal__submit-spinner" aria-hidden="true"></span>'
		);
		$btnSubmit.prop('disabled', true);
		var $btnReset = $('<button type="button" class="rsc-vt-modal__reset" />').text(
			getI18n('resetSelection')
		);
		var $btnQuotation = null;
		if (typeof wc_rq_ajax !== 'undefined') {
			var rqSvg =
				'<svg class="rsc-vt-modal__quotation-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">' +
				'<path d="M14.186 2.753v3.596c0 .487.194.955.54 1.3a1.85 1.85 0 0 0 1.306.539h4.125" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>' +
				'<path d="M20.25 8.568v8.568a4.25 4.25 0 0 1-1.362 2.97 4.28 4.28 0 0 1-3.072 1.14h-7.59a4.3 4.3 0 0 1-3.1-1.124 4.26 4.26 0 0 1-1.376-2.986V6.862a4.25 4.25 0 0 1 1.362-2.97 4.28 4.28 0 0 1 3.072-1.14h5.714a3.5 3.5 0 0 1 2.361.905l2.96 2.722a2.97 2.97 0 0 1 1.031 2.189" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>' +
				'<path d="M12 17.273v-6.774" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"></path>' +
				'<path d="m8.894 14.42 2.665 2.666a.62.62 0 0 0 .882 0l2.665-2.665" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>' +
				'</svg>';
			$btnQuotation = $('<button type="button" class="rsc-vt-modal__quotation" />').html(
				rqSvg + '<span class="rsc-vt-modal__quotation-label">' + escapeHtml(getI18n('requestQuotation')) + '</span>'
			);
			$btnQuotation.prop('disabled', true);
		}
		var $actions = $('<div class="rsc-vt-modal__foot-actions" />');
		$actions.append($btnSubmit);
		if ($btnQuotation) {
			$actions.append($btnQuotation);
		}
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
		var productConfigLineForRq = '';
		var productTitleForRq = '';
		var variationSpecForRq = '';
		var transitionMs = prefersReducedMotion ? 0 : 220;

		function modalMayDismiss() {
			return !$btnSubmit.hasClass('is-loading');
		}

		function animateRemove(onDone) {
			$(document).off('keydown.rscVtModal');
			$modal.off();
			$overlay.removeClass('is-visible');
			setTimeout(function () {
				$overlay.remove();
				rscVtUnlockScroll();
				if (typeof onDone === 'function') {
					onDone();
				}
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
				productTitleForRq = res.data.product_title || '';
				variationSpecForRq =
					res.data.variation_spec ||
					getVariationSpec(
						res.data.product_title,
						res.data.variation_name || '',
						res.data.variation_label || ''
					);
				productConfigLineForRq =
					res.data.product_config_line ||
					buildProductConfigLine(
						res.data.product_title,
						res.data.variation_name || '',
						res.data.variation_label || ''
					);
				fillContextRow($summary, productConfigLineForRq);

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
				if ($btnQuotation) {
					$btnQuotation.prop('disabled', false);
				}
			})
			.fail(function () {
				$err.text(getI18n('loadError')).addClass('is-visible');
				$body.empty();
			});

		if ($btnQuotation) {
			$btnQuotation.on('click', function (e) {
				e.preventDefault();
				if ($btnQuotation.prop('disabled') || $btnSubmit.prop('disabled')) {
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
				var linesRq = collectLines($modal);
				if (componentCount > 0 && linesRq.length === 0) {
					$err.text(getI18n('validationNeedPart')).addClass('is-visible');
					var $eb2 = $err[0];
					if ($eb2 && $eb2.scrollIntoView) {
						$eb2.scrollIntoView({ block: 'nearest', behavior: prefersReducedMotion ? 'auto' : 'smooth' });
					}
					return;
				}
				var $hiddenRq = getHiddenInput($form);
				if (linesRq.length) {
					$hiddenRq.val(JSON.stringify(linesRq));
				} else {
					$hiddenRq.val('');
				}
				var fullName = buildQuotationProductName(
					productTitleForRq,
					variationSpecForRq,
					linesRq,
					compsById
				);
				var unitPrice = fixedPrice + computePartsTotal(linesRq, compsById);
				if (isNaN(unitPrice)) {
					unitPrice = 0;
				}
				animateRemove(function () {
					openWcRequestQuotationFromRsc({
						variationId: variationId,
						parentId: (typeof rscVartable !== 'undefined' && rscVartable.productId) || 0,
						fullName: fullName,
						unitPrice: unitPrice
					});
				});
			});
		}

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
			$modal
				.find(
					'.rsc-vt-modal__reset, .rsc-vt-modal__close, .rsc-vt-summary__toggle, .rsc-vt-modal__quotation'
				)
				.prop('disabled', true);
			$btnSubmit.addClass('is-loading');
			$btnSubmit.find('.rsc-vt-modal__submit-label').text(getI18n('adding'));

			var $sub = $form.find('button[type="submit"]').first();
			if ($sub.length) {
				animateRemove(function () {
					$sub.trigger('click');
				});
			} else {
				$btnSubmit.prop('disabled', false);
				$modal
					.find(
						'.rsc-vt-modal__reset, .rsc-vt-modal__close, .rsc-vt-summary__toggle, .rsc-vt-modal__quotation'
					)
					.prop('disabled', false);
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
