(function () {
  'use strict';

  var config = window.esBookingJourney || {};
  var fields = config.fields || {};
  var params = new URLSearchParams(window.location.search);
  var attributionKey = 'es_first_touch';
  var firstTouch = {};

  try {
    firstTouch = JSON.parse(window.sessionStorage.getItem(attributionKey) || '{}');
    if (!Object.keys(firstTouch).length) {
      firstTouch = {
        utm_source: params.get('utm_source') || '',
        utm_medium: params.get('utm_medium') || '',
        utm_campaign: params.get('utm_campaign') || '',
        gclid: params.get('gclid') || '',
        fbclid: params.get('fbclid') || '',
        landing_url: window.location.href
      };
      window.sessionStorage.setItem(attributionKey, JSON.stringify(firstTouch));
    }
  } catch (error) {
    firstTouch = {};
  }

  function newSubmissionId() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return window.crypto.randomUUID();
    }
    return 'web-' + Date.now() + '-' + Math.random().toString(16).slice(2);
  }

  var submissionId = newSubmissionId();

  function setField(form, name, value) {
    if (!name || value === undefined || value === null || value === '') return;
    var field = form.querySelector('[name="' + CSS.escape(name) + '"]');
    if (!field) {
      field = document.createElement('input');
      field.type = 'hidden';
      field.name = name;
      form.appendChild(field);
    }
    field.value = String(value);
    field.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function ensureVisibleField(form, name, label, type, attributes) {
    if (!name || form.querySelector('[name="' + CSS.escape(name) + '"]')) return;

    var row = document.createElement('div');
    row.className = 'wf-row es-added-booking-field';
    var labelElement = document.createElement('label');
    labelElement.className = 'wf-label';
    labelElement.textContent = label;
    var fieldWrapper = document.createElement('div');
    fieldWrapper.className = 'wf-field';
    var fieldInner = document.createElement('div');
    fieldInner.className = 'wf-field-inner';
    var input = document.createElement('input');
    input.name = name;
    input.type = type;
    input.className = 'wf-field-item wf-field-input';
    Object.keys(attributes || {}).forEach(function (key) {
      input.setAttribute(key, attributes[key]);
    });
    fieldInner.appendChild(input);
    fieldWrapper.appendChild(fieldInner);
    row.appendChild(labelElement);
    row.appendChild(fieldWrapper);

    var submitRow = form.querySelector('.wform-btn-wrap');
    form.querySelector('.wf-form-wrapper, [id^="elementDiv"]')?.insertBefore(row, submitRow || null);
  }

  function populateForm(form) {
    if (!form || form.dataset.esJourneyReady === 'true') return;

    setField(form, fields.property, config.propertyName || '');
    setField(form, fields.referrer, window.location.href);
    setField(form, fields.offer, config.offerName || document.title);
    setField(form, fields.productCode, config.productCode || '');
    setField(form, fields.promoCode, config.promoCode || '');
    setField(form, fields.landingUrl, firstTouch.landing_url || window.location.href);
    setField(form, fields.utmSource, firstTouch.utm_source || params.get('utm_source') || '');
    setField(form, fields.utmMedium, firstTouch.utm_medium || params.get('utm_medium') || '');
    setField(form, fields.utmCampaign, firstTouch.utm_campaign || params.get('utm_campaign') || '');
    setField(form, fields.channel, 'Website');
    setField(form, fields.submissionId, submissionId);
	setField(form, fields.metaClickId, firstTouch.fbclid || params.get('fbclid') || '');
    setField(form, fields.googleClickId, firstTouch.gclid || params.get('gclid') || '');

    ensureVisibleField(form, fields.checkoutDate, 'Check-out date', 'date');
    ensureVisibleField(form, fields.numberRooms, 'Number of rooms', 'number', { min: '1', max: '20', value: '1' });

    [fields.property, fields.referrer, fields.offer, fields.productCode, fields.promoCode,
      fields.landingUrl, fields.utmSource, fields.utmMedium, fields.utmCampaign,
	  fields.channel, fields.submissionId, fields.metaClickId, fields.googleClickId].filter(Boolean).forEach(function (name) {
      var input = form.querySelector('[name="' + CSS.escape(name) + '"]');
      var container = input && input.closest('.wf-row, .wf-field');
      if (container) container.hidden = true;
    });

    form.addEventListener('submit', function () {
      pushEvent('generate_lead', { method: 'Bigin form' });
    }, { once: true });

    form.dataset.esJourneyReady = 'true';
  }

  function populateForms() {
    document.querySelectorAll('form[id^="BiginWebToRecordForm"]').forEach(populateForm);
  }

  function pushEvent(name, extra) {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(Object.assign({
      event: name,
      promotion_name: config.offerName || document.title,
      creative_name: config.productCode || '',
      promotion_id: config.promoCode || '',
      property_name: config.propertyName || ''
    }, extra || {}));
  }

  function prepareBookingLinks() {
    document.querySelectorAll('a[href*="nebulacrs.hti.app/extraordinary/"]').forEach(function (link) {
      if (config.expired) {
        link.setAttribute('aria-disabled', 'true');
        link.classList.add('es-disabled-link');
        link.addEventListener('click', function (event) { event.preventDefault(); });
        return;
      }
      if (config.bookingUrl && /book/i.test(link.textContent || '')) {
        link.href = config.bookingUrl;
      }
      link.addEventListener('click', function () {
        pushEvent('select_promotion', { destination: 'eRes booking engine' });
      });
    });
  }

  function init() {
    populateForms();
    prepareBookingLinks();
    if (config.offerName) pushEvent('view_promotion');

    var observer = new MutationObserver(function () { populateForms(); });
    observer.observe(document.body, { childList: true, subtree: true });
    window.setTimeout(function () { observer.disconnect(); }, 15000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
