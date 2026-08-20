/**
 * Hummingbird Editor — Admin JavaScript
 * PS8/9 compatible: defensive jQuery UI Sortable check, no global conflicts.
 */
/* global $, hbeAjaxUrl, hbeToken, hbeTrans, hbeImgUrl */

$(function () {
    'use strict';

    $.ajaxPrefilter(function (options, originalOptions) {
        if (typeof hbeLangId === 'undefined' || !hbeLangId) {
            return;
        }
        var data = typeof originalOptions.data !== 'undefined' ? originalOptions.data : options.data;

        // FormData (file uploads) — append directly, leave object as-is
        if (typeof FormData !== 'undefined' && data instanceof FormData) {
            if (!data.has('lang_id')) {
                data.append('lang_id', String(hbeLangId));
            }
            options.data = data;
            return;
        }

        // Array of {name,value} (from .serializeArray()) — convert to query string.
        // jQuery Migrate 3.4 mishandles array-form data and throws v.data.replace TypeError.
        if (Array.isArray(data)) {
            var hasLang = data.some(function (item) { return item && item.name === 'lang_id'; });
            if (!hasLang) {
                data.push({ name: 'lang_id', value: String(hbeLangId) });
            }
            options.data = $.param(data);
            options.processData = false;
            options.contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
            return;
        }

        // Plain object — convert to query string for the same reason.
        if (data && typeof data === 'object') {
            if (typeof data.lang_id === 'undefined') {
                data.lang_id = String(hbeLangId);
            }
            options.data = $.param(data);
            options.processData = false;
            options.contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
            return;
        }

        // String — append lang_id if missing
        if (typeof data === 'string') {
            if (data.indexOf('lang_id=') === -1) {
                options.data = data + (data.length ? '&' : '') + 'lang_id=' + encodeURIComponent(String(hbeLangId));
            }
            return;
        }

        // No data — set lang_id only
        options.data = 'lang_id=' + encodeURIComponent(String(hbeLangId));
        options.processData = false;
        options.contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
    });

    /* ── Diagnostics ──────────────────────────────────────────────────────── */
    console.log('[HBE] admin.js DOMReady. hbeAjaxUrl=', typeof hbeAjaxUrl !== 'undefined' ? hbeAjaxUrl : 'UNDEFINED');
    console.log('[HBE] #hbe-infobar-form found:', $('#hbe-infobar-form').length);
    console.log('[HBE] #hbe-topbar-form found:', $('#hbe-topbar-form').length);
    console.log('[HBE] hbeLangId=', typeof hbeLangId !== 'undefined' ? hbeLangId : 'UNDEFINED', ' hbeToken=', typeof hbeToken !== 'undefined' ? (hbeToken ? hbeToken.substring(0, 8) + '…' : 'EMPTY') : 'UNDEFINED');

    // Global ajax lifecycle hooks — to confirm jQuery actually dispatches.
    $(document).ajaxSend(function (e, jqxhr, settings) {
        if (settings && settings.url && settings.url.indexOf('AdminHbEditor') !== -1) {
            console.log('[HBE-DIAG] ajaxSend →', settings.type, settings.url);
        }
    });
    $(document).ajaxComplete(function (e, jqxhr, settings) {
        if (settings && settings.url && settings.url.indexOf('AdminHbEditor') !== -1) {
            console.log('[HBE-DIAG] ajaxComplete ←', jqxhr.status, settings.url, 'bodyLen=', jqxhr.responseText ? jqxhr.responseText.length : 0);
        }
    });
    $(document).ajaxError(function (e, jqxhr, settings, err) {
        if (settings && settings.url && settings.url.indexOf('AdminHbEditor') !== -1) {
            console.error('[HBE-DIAG] ajaxError', jqxhr.status, settings.url, err, jqxhr.responseText && jqxhr.responseText.substring(0, 300));
        }
    });

    // Catch ANY form submit anywhere in the doc — to see if the event even fires
    $(document).on('submit', 'form', function (e) {
        console.log('[HBE-DIAG] submit event fired on form id=', this.id, 'action=', this.action, 'defaultPrevented=', e.isDefaultPrevented());
    });
    // Catch clicks on submit buttons inside hbe-* forms
    $(document).on('click', 'form[id^="hbe-"] button[type="submit"], form[id^="hbe-"] input[type="submit"]', function (e) {
        var $form = $(this).closest('form');
        console.log('[HBE-DIAG] submit button clicked. form id=', $form.attr('id'), 'button=', this.outerHTML.substring(0, 120));
    });
    // Show a count of all hbe-* forms detected
    console.log('[HBE-DIAG] all hbe forms:', $('form[id^="hbe-"]').map(function () { return this.id; }).get());

    /* ── Sortable drag-to-reorder (html5sortable) ─────────────────────────── */
    // html5sortable fires 'sortupdate' on the container.
    // forcePlaceholderSize keeps the placeholder the same height as the dragged
    // item, preventing layout jumps that cause boundary flickering.
    if (typeof $.fn.sortable === 'function') {
        $('.hbe-sortable').sortable({ handle: '.hbe-handle', forcePlaceholderSize: true });
        $(document).on('sortupdate', '.hbe-sortable', function () {
            var hookName = $(this).data('hook');
            var ids = [];
            $(this).children('[data-id]').each(function () {
                ids.push(String($(this).data('id')));
            });
            $.post(hbeAjaxUrl + 'action=Reorder&ajax=1', {
                hook_name: hookName,
                ids: ids,
                token: hbeToken
            });
        });
    }

    /* ── Add block: open / close panel ────────────────────────────────────── */
    $(document).on('click', '#hbe-add-btn', function () {
        $('#hbe-add-panel').slideToggle(200);
    });
    $(document).on('click', '#hbe-add-cancel', function () {
        $('#hbe-add-panel').slideUp(200);
        $('#hbe-add-form')[0].reset();
    });

    /* ── Add block: submit ─────────────────────────────────────────────────── */
    $(document).on('submit', '#hbe-add-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data  = $form.serializeArray();
        // checkboxes that are unchecked won't appear in serializeArray — add 0 values
        if (!$form.find('[name=active]').is(':checked')) {
            data.push({ name: 'active', value: '0' });
        }
        if (!$form.find('[name=mobile_different]').is(':checked')) {
            data.push({ name: 'mobile_different', value: '0' });
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=CreateBlock&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function (xhr) { console.error('[HBE] SaveInfoBar error', xhr.status, xhr.responseText && xhr.responseText.substring(0, 500)); showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Top promo bar: save ───────────────────────────────────────────────── */
    $(document).on('submit', '#hbe-topbar-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serializeArray();
        if (!$form.find('[name=enabled]').is(':checked')) {
            data.push({ name: 'enabled', value: '0' });
        }

        $.ajax({
            url: hbeAjaxUrl + 'action=SaveTopBar&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Karta podarunkowa: save ───────────────────────────────────────────── */
    $(document).on('submit', '#hbe-giftcard-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serializeArray();
        // Unchecked checkboxes are absent from serializeArray — send explicit 0.
        ['menu_enabled', 'footer_enabled', 'float_enabled'].forEach(function (name) {
            if (!$form.find('[name=' + name + ']').is(':checked')) {
                data.push({ name: name, value: '0' });
            }
        });

        $.ajax({
            url: hbeAjaxUrl + 'action=SaveGiftcard&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Wydajność karuzel: ustawienia cache + ręczne czyszczenie ─────────── */

    function hbeRenderCacheStats(stats) {
        if (!stats) { return; }
        $('#hbe-cc-files').text(stats.files);
        $('#hbe-cc-size').text(stats.size);
        $('#hbe-cc-age').text(stats.age);
    }

    $(document).on('submit', '#hbe-carousel-cache-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serializeArray();
        // Odznaczone checkboxy nie trafiają do serializeArray — dosyłamy 0.
        ['enabled', 'lazy'].forEach(function (name) {
            if (!$form.find('[name=' + name + ']').is(':checked')) {
                data.push({ name: name, value: '0' });
            }
        });

        $.ajax({
            url: hbeAjaxUrl + 'action=SaveCarouselCache&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    hbeRenderCacheStats(resp.stats);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    $(document).on('click', '#hbe-cc-purge', function () {
        var $form = $('#hbe-carousel-cache-form');
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            url: hbeAjaxUrl + 'action=PurgeCarouselCache&ajax=1',
            type: 'POST',
            data: { token: $form.find('[name=token]').val() },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess('Usunięto wpisów: ' + resp.removed);
                    hbeRenderCacheStats(resp.stats);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    /* ── Info bar (below slider): save ────────────────────────────────────── */
    $(document).on('submit', '#hbe-infobar-form', function (e) {
        console.log('[HBE-DIAG] infobar handler ENTERED');
        e.preventDefault();
        var $form = $(this);
        var data = $form.serializeArray();
        if (!$form.find('[name=enabled]').is(':checked')) {
            data.push({ name: 'enabled', value: '0' });
        }
        console.log('[HBE-DIAG] infobar will POST to:', hbeAjaxUrl + 'action=SaveInfoBar&ajax=1', 'data:', data);
        // colour inputs are type=color — always present in serializeArray
        var jqxhr;
        try {
            jqxhr = $.ajax({
                url: hbeAjaxUrl + 'action=SaveInfoBar&ajax=1',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (resp) {
                    console.log('[HBE-DIAG] infobar SUCCESS', resp);
                    if (resp && resp.success) {
                        showGlobalSuccess(hbeTrans.saved);
                        clearFormErrors($form);
                    } else {
                        showFormError($form, resp ? resp.error : hbeTrans.error);
                    }
                },
                error: function (xhr) { console.error('[HBE-DIAG] infobar AJAX error', xhr.status, xhr.responseText && xhr.responseText.substring(0, 500)); showFormError($form, hbeTrans.error); },
                complete: function (xhr, status) { console.log('[HBE-DIAG] infobar AJAX complete. status=', status, 'http=', xhr.status); }
            });
        } catch (ex) {
            console.error('[HBE-DIAG] infobar $.ajax THREW SYNCHRONOUSLY', ex);
            return;
        }
        console.log('[HBE-DIAG] infobar $.ajax() returned. readyState=', jqxhr && jqxhr.readyState, 'state=', jqxhr && jqxhr.state && jqxhr.state());
        // Fallback: if jQuery's promise didn't actually dispatch, fire a raw XHR so we *see something* in Network.
        setTimeout(function () {
            if (jqxhr && jqxhr.readyState === 0) {
                console.warn('[HBE-DIAG] jqxhr stuck in readyState 0 — falling back to raw XHR.');
                var body = data.map(function (kv) { return encodeURIComponent(kv.name) + '=' + encodeURIComponent(kv.value); }).join('&');
                var xhr = new XMLHttpRequest();
                xhr.open('POST', hbeAjaxUrl + 'action=SaveInfoBar&ajax=1', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = function () { console.log('[HBE-DIAG] raw XHR done', xhr.status, xhr.responseText.substring(0, 300)); };
                xhr.onerror = function () { console.error('[HBE-DIAG] raw XHR error', xhr.status); };
                xhr.send(body);
            }
        }, 100);
    });

    /* ── Info bar 2: save ──────────────────────────────────────────────────── */
    $(document).on('submit', '#hbe-infobar2-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serializeArray();
        if (!$form.find('[name=enabled]').is(':checked')) {
            data.push({ name: 'enabled', value: '0' });
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveInfoBar2&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Carousel section headers: save ───────────────────────────────────── */
    $(document).on('submit', '#hbe-carousel-headers-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveCarouselHeaders&ajax=1',
            type: 'POST',
            data: $form.serializeArray(),
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Carousel source: category typeahead picker ───────────────────────── */
    (function () {
        var searchUrl = hbeAjaxUrl + 'action=SearchCategories&ajax=1';
        var timer = null;

        function menuFor($pick) { return $pick.find('[data-hbe-catpick-menu]'); }
        function closeAll() { $('[data-hbe-catpick-menu]').empty().hide(); }

        function render($pick, items) {
            var $menu = menuFor($pick).empty();
            if (!items.length) { $menu.hide(); return; }
            items.forEach(function (it) {
                $('<li/>', { 'class': 'hbe-catpick-item', text: it.label })
                    .attr('data-id', it.id)
                    .appendTo($menu);
            });
            $menu.show();
        }

        // Typing searches; every keystroke drops any previously chosen id until a
        // suggestion is clicked, so a hand-edited label can't keep a stale id.
        $(document).on('input', '[data-hbe-catpick-search]', function () {
            var $input = $(this);
            var $pick = $input.closest('[data-hbe-catpick]');
            var q = $.trim($input.val());
            $pick.find('[data-hbe-catpick-id]').val('0');
            if (timer) { clearTimeout(timer); }
            if (q.length < 1) { menuFor($pick).empty().hide(); return; }
            timer = setTimeout(function () {
                $.ajax({ url: searchUrl, type: 'POST', data: { q: q }, dataType: 'json' })
                    .done(function (resp) {
                        render($pick, (resp && resp.categories) ? resp.categories : []);
                    });
            }, 220);
        });

        $(document).on('click', '.hbe-catpick-item', function () {
            var $item = $(this);
            var $pick = $item.closest('[data-hbe-catpick]');
            $pick.find('[data-hbe-catpick-id]').val($item.attr('data-id'));
            $pick.find('[data-hbe-catpick-search]').val($item.text());
            menuFor($pick).empty().hide();
        });

        $(document).on('click', '[data-hbe-catpick-clear]', function () {
            var $pick = $(this).closest('[data-hbe-catpick]');
            $pick.find('[data-hbe-catpick-id]').val('0');
            $pick.find('[data-hbe-catpick-search]').val('');
            menuFor($pick).empty().hide();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('[data-hbe-catpick]').length) { closeAll(); }
        });
    })();

    /* ── Image hero banner: save ──────────────────────────────────────────── */
    $(document).on('submit', '#hbe-imghero-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveImgHero&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    // Show preview if image uploaded
                    if (resp.img_url) {
                        $('#hbe-imghero-img-preview').attr('src', resp.img_url);
                        $('#hbe-imghero-img-wrap').show();
                        $form.find('[name=image]').val('');
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Image hero banner: delete image ─────────────────────────────────── */
    $(document).on('click', '#hbe-imghero-del-img', function () {
        if (!confirm('Usunąć zdjęcie?')) { return; }
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteImgHeroImage&ajax=1',
            type: 'POST',
            data: { token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $('#hbe-imghero-img-wrap').hide();
                    $('#hbe-imghero-img-preview').attr('src', '');
                }
            }
        });
    });

    /* ── Image hero banner 2: save ───────────────────────────────────────── */
    $(document).on('submit', '#hbe-imghero2-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveImgHero2&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    if (resp.img_url) {
                        $('#hbe-imghero2-img-preview').attr('src', resp.img_url);
                        $('#hbe-imghero2-img-wrap').show();
                        $form.find('[name=image]').val('');
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Image hero banner 2: delete image ───────────────────────────────── */
    $(document).on('click', '#hbe-imghero2-del-img', function () {
        if (!confirm('Usunąć zdjęcie?')) { return; }
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteImgHero2Image&ajax=1',
            type: 'POST',
            data: { token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $('#hbe-imghero2-img-wrap').hide();
                    $('#hbe-imghero2-img-preview').attr('src', '');
                }
            }
        });
    });

    /* ── Image + text section (product page): save ───────────────────── */
    $(document).on('submit', '#hbe-imgtext-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveImgText&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    if (resp.img_url) {
                        $('#hbe-imgtext-img-preview').attr('src', resp.img_url);
                        $('#hbe-imgtext-img-wrap').show();
                        $form.find('[name=image]').val('');
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Listing banners (category pages): save ───────────────── */
    $(document).on('submit', '#hbe-listban-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        for (var i = 1; i <= 5; i++) {
            if (!$form.find('[name=enabled_' + i + ']').is(':checked')) {
                fd.set('enabled_' + i, '0');
            }
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveListingBanners&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    for (var i = 1; i <= 5; i++) {
                        if (resp['img_url_' + i]) {
                            $('#hbe-listban-img-' + i + '-preview').attr('src', resp['img_url_' + i]);
                            $('#hbe-listban-img-' + i + '-wrap').show();
                            $form.find('[name=HBE_LISTBAN_' + i + '_IMAGE]').val('');
                        }
                        if (resp['img_url_mobile_' + i]) {
                            $('#hbe-listban-img-m-' + i + '-preview').attr('src', resp['img_url_mobile_' + i]);
                            $('#hbe-listban-img-m-' + i + '-wrap').show();
                            $form.find('[name=HBE_LISTBAN_' + i + '_IMAGE_MOBILE]').val('');
                        }
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Listing banners: delete image ─────────────────────────── */
    $(document).on('click', '.hbe-listban-del', function () {
        if (!confirm('Usunąć zdjęcie?')) { return; }
        var slot = $(this).data('slot');
        var variant = $(this).data('variant');
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteListingBannerImage&ajax=1',
            type: 'POST',
            data: { token: hbeToken, slot: slot, variant: variant },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    var prefix = variant === 'mobile' ? '#hbe-listban-img-m-' : '#hbe-listban-img-';
                    $(prefix + slot + '-wrap').hide();
                    $(prefix + slot + '-preview').attr('src', '');
                }
            }
        });
    });

    /* ── 3-column text links block: save ─────────────────────────────────── */
    $(document).on('submit', '#hbe-cols3-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serializeArray();
        if (!$form.find('[name=enabled]').is(':checked')) {
            data.push({ name: 'enabled', value: '0' });
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveCols3&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── 3-column block with descriptions: save ──────────────────────────── */
    $(document).on('submit', '#hbe-cols3desc-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        // FormData so the optional per-column image file inputs are sent.
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveCols3Desc&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    for (var i = 1; i <= 3; i++) {
                        if (resp['img_url_' + i]) {
                            $('#hbe-cols3d-' + i + '-img-preview').attr('src', resp['img_url_' + i]);
                            $('#hbe-cols3d-' + i + '-img-wrap').show();
                            $form.find('[name=img_' + i + ']').val('');
                        }
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Tagline text block: save ─────────────────────────────────────────── */
    $(document).on('submit', '#hbe-tagline-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = $form.serializeArray();
        if (!$form.find('[name=enabled]').is(':checked')) {
            data.push({ name: 'enabled', value: '0' });
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveTagline&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Kategorie section: save ─────────────────────────────────────────── */
    $(document).on('submit', '#hbe-katcols-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveKatcols&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    if (resp.l_img_url) {
                        $('#hbe-katcols-l-img-preview').attr('src', resp.l_img_url);
                        $('#hbe-katcols-l-img-wrap').show();
                        $form.find('[name=l_image]').val('');
                    }
                    if (resp.r_img_url) {
                        $('#hbe-katcols-r-img-preview').attr('src', resp.r_img_url);
                        $('#hbe-katcols-r-img-wrap').show();
                        $form.find('[name=r_image]').val('');
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Kategorie section: delete image ─────────────────────────────────── */
    $(document).on('click', '.hbe-katcols-del-img', function () {
        if (!confirm('Usunąć zdjęcie?')) { return; }
        var side = $(this).data('side');
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteKatcolsImage&ajax=1',
            type: 'POST',
            data: { token: hbeToken, side: side },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $('#hbe-katcols-' + side + '-img-wrap').hide();
                    $('#hbe-katcols-' + side + '-img-preview').attr('src', '');
                }
            }
        });
    });

    /* ── Split-block (3 columns): save ──────────────────────────────────────*/
    $(document).on('submit', '#hbe-splitblock-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveSplitBlock&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    if (resp.m_img_url) {
                        $('#hbe-splitblock-m-img-preview').attr('src', resp.m_img_url);
                        $('#hbe-splitblock-m-img-wrap').show();
                        $form.find('[name=m_image]').val('');
                    }
                    if (resp.r_img_url) {
                        $('#hbe-splitblock-r-img-preview').attr('src', resp.r_img_url);
                        $('#hbe-splitblock-r-img-wrap').show();
                        $form.find('[name=r_image]').val('');
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Split-block: delete image ───────────────────────────────────────── */
    $(document).on('click', '.hbe-splitblock-del-img', function () {
        if (!confirm('Usunąć zdjęcie?')) { return; }
        var side = $(this).data('side');
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteSplitBlockImage&ajax=1',
            type: 'POST',
            data: { token: hbeToken, side: side },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $('#hbe-splitblock-' + side + '-img-wrap').hide();
                    $('#hbe-splitblock-' + side + '-img-preview').attr('src', '');
                }
            }
        });
    });

    /* ── Icons 4 columns: save ───────────────────────────────────────────── */
    $(document).on('submit', '#hbe-icons4-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveIcons4&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    for (var i = 1; i <= 4; i++) {
                        if (resp['img_url_' + i]) {
                            $('#hbe-icons4-' + i + '-img-preview').attr('src', resp['img_url_' + i]);
                            $('#hbe-icons4-' + i + '-img-wrap').show();
                            $form.find('[name=img_' + i + ']').val('');
                        }
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Inne sklepy online: save ────────────────────────────────────────── */
    $(document).on('submit', '#hbe-shops-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveShops&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    for (var i = 1; i <= 3; i++) {
                        for (var j = 1; j <= 3; j++) {
                            if (resp['img_url_' + i + '_' + j]) {
                                $('#hbe-shops-img-' + i + '-' + j + '-preview').attr('src', resp['img_url_' + i + '_' + j]);
                                $('#hbe-shops-img-' + i + '-' + j + '-wrap').show();
                                $form.find('[name=img_' + i + '_' + j + ']').val('');
                            }
                        }
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Inne sklepy online: delete gallery image ────────────────────────── */
    $(document).on('click', '.hbe-shops-del-img', function (e) {
        e.preventDefault();
        if (!confirm('Usunąć zdjęcie?')) { return; }
        var store = $(this).data('store');
        var idx = $(this).data('idx');
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteShopsImage&ajax=1',
            type: 'POST',
            data: { token: hbeToken, store: store, idx: idx },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $('#hbe-shops-img-' + store + '-' + idx + '-wrap').hide();
                    $('#hbe-shops-img-' + store + '-' + idx + '-preview').attr('src', '');
                }
            }
        });
    });

    /* ── Icons 4 columns: delete icon ───────────────────────────────────── */
    $(document).on('click', '.hbe-icons4-del-img', function (e) {
        e.preventDefault();
        if (!confirm('Usunąć ikonę?')) { return; }
        var col = $(this).data('col');
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteIcons4Image&ajax=1',
            type: 'POST',
            data: { token: hbeToken, col: col },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $('#hbe-icons4-' + col + '-img-wrap').hide();
                    $('#hbe-icons4-' + col + '-img-preview').attr('src', '');
                }
            }
        });
    });

    /* ── Edit panel: toggle open/close ────────────────────────────────────── */
    $(document).on('click', '.hbe-edit-btn', function () {
        var id = $(this).data('id');
        var $panel = $('#hbe-edit-panel-' + id);
        // close all other panels
        $('.hbe-edit-panel').not($panel).slideUp(150);
        $panel.slideToggle(200);
    });
    $(document).on('click', '.hbe-close-edit', function () {
        var id = $(this).data('id');
        $('#hbe-edit-panel-' + id).slideUp(200);
    });

    /* ── Edit form: type change → show/hide image/lang sections ───────────── */
    $(document).on('change', '.hbe-type-select', function () {
        var $form    = $(this).closest('.hbe-edit-form');
        var type     = $(this).val();
        var $imgSec  = $form.find('.hbe-image-section');
        var $langSec = $form.find('.hbe-lang-section');

        $imgSec.toggleClass('hbe-hidden', type !== 'image');
        // For image type without mobile-diff, hide lang section entirely
        var mobileDiff = $form.find('.hbe-mobile-diff-cb').is(':checked');
        if (type === 'image' && !mobileDiff) {
            $langSec.toggleClass('hbe-hidden', false); // always show for alt text
        } else {
            $langSec.removeClass('hbe-hidden');
        }
    });

    /* ── Edit form: mobile_different toggle ────────────────────────────────── */
    $(document).on('change', '.hbe-mobile-diff-cb', function () {
        var $form    = $(this).closest('.hbe-edit-form');
        var checked  = $(this).is(':checked');
        $form.find('.hbe-mobile-fields').toggleClass('hbe-hidden', !checked);
    });

    /* ── WYSIWYG: sync contenteditable → hidden input ─────────────────────── */
    $(document).on('input', '.hbe-wysiwyg', function () {
        var field = $(this).data('field');
        $('[name="' + field + '"]').val($(this).html());
    });

    /* ── WYSIWYG toolbar buttons ───────────────────────────────────────────── */
    $(document).on('click', '.hbe-wysiwyg-toolbar button', function (e) {
        e.preventDefault();
        var cmd    = $(this).data('cmd');
        var target = $(this).closest('.hbe-wysiwyg-toolbar').data('target');
        var $editor = $('#' + target);
        $editor.focus();
        if (cmd === 'createLink') {
            var url = prompt('URL:');
            if (url) document.execCommand('createLink', false, url);
        } else {
            document.execCommand(cmd, false, null);
        }
        $('[name="' + $editor.data('field') + '"]').val($editor.html());
    });

    /* ── Edit form: save ───────────────────────────────────────────────────── */
    $(document).on('submit', '.hbe-edit-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var id    = $form.data('id');

        // Sync all wysiwyg editors in this form
        $form.find('.hbe-wysiwyg').each(function () {
            var field = $(this).data('field');
            $form.find('[name="' + field + '"]').val($(this).html());
        });

        var data = $form.serializeArray();
        // Ensure unchecked checkboxes send 0
        if (!$form.find('[name=active]').is(':checked')) {
            data.push({ name: 'active', value: '0' });
        }
        if (!$form.find('[name=mobile_different]').is(':checked')) {
            data.push({ name: 'mobile_different', value: '0' });
        }

        var $status = $form.find('.hbe-save-status');
        $status.hide();

        $.ajax({
            url: hbeAjaxUrl + 'action=SaveBlock&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $status.fadeIn().delay(2500).fadeOut();
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Toggle active ─────────────────────────────────────────────────────── */
    $(document).on('click', '.hbe-toggle-active', function () {
        var $btn   = $(this);
        var id     = $btn.data('id');
        var active = $btn.hasClass('hbe-active') ? 0 : 1;
        $.ajax({
            url: hbeAjaxUrl + 'action=ToggleActive&ajax=1',
            type: 'POST',
            data: { id_block: id, active: active, token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $btn.toggleClass('btn-success hbe-active', active === 1)
                        .toggleClass('btn-warning hbe-inactive', active === 0);
                    var icon  = $btn.find('i');
                    var label = $btn.contents().filter(function () {
                        return this.nodeType === 3;
                    });
                    icon.attr('class', active ? 'icon-check' : 'icon-remove');
                    label.replaceWith(active ? ' ON' : ' OFF');
                    $btn.attr('title', active ? 'Disable' : 'Enable');
                    // sync hidden active checkbox in edit form
                    var $cb = $('#hbe-edit-panel-' + id).find('[name=active]');
                    $cb.prop('checked', active === 1);
                }
            }
        });
    });

    /* ── Duplicate block ──────────────────────────────────────────────────── */
    $(document).on('click', '.hbe-duplicate', function () {
        var $btn = $(this).prop('disabled', true);
        var id   = $btn.data('id');
        $.ajax({
            url: hbeAjaxUrl + 'action=DuplicateBlock&ajax=1',
            type: 'POST',
            data: { id_block: id, token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.duplicated);
                    setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    showGlobalError((resp && resp.error) || hbeTrans.error);
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showGlobalError(hbeTrans.error);
                $btn.prop('disabled', false);
            }
        });
    });

    /* ── Clone static section as DB block ─────────────────────────────────── */
    $(document).on('click', '.hbe-clone-static', function () {
        var $btn = $(this).prop('disabled', true);
        var slug = $btn.data('slug');
        $.ajax({
            url: hbeAjaxUrl + 'action=CloneStaticSection&ajax=1',
            type: 'POST',
            data: { slug: slug, token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.duplicated);
                    setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    showGlobalError((resp && resp.error) || hbeTrans.error);
                    $btn.prop('disabled', false);
                }
            },
            error: function () {
                showGlobalError(hbeTrans.error);
                $btn.prop('disabled', false);
            }
        });
    });

    /* ── Brands: live logo preview on manufacturer change ───────────────── */
    $(document).on('change', '.hbe-brand-manu', function () {
        var slot = $(this).data('slot');
        var logo = $(this).find('option:selected').data('logo') || '';
        var $img = $('#hbe-brand-preview-' + slot);
        if (logo) {
            $img.attr('src', logo).show();
        } else {
            $img.attr('src', '').hide();
        }
    });

    /* ── Save brands ────────────────────────────────────────────────────── */
    $(document).on('submit', '#hbe-brands-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var fd = new FormData($form[0]);
        if (!$form.find('[name=enabled]').is(':checked')) {
            fd.set('enabled', '0');
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveBrands&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                    for (var i = 1; i <= 8; i++) {
                        if (resp['img_url_' + i]) {
                            $('#hbe-brand-preview-' + i).attr('src', resp['img_url_' + i]).show();
                            $form.find('[name=HBE_BRANDS_IMG_' + i + ']').val('');
                        }
                    }
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Save section block (JSON editor) ─────────────────────────────────── */
    $(document).on('submit', '.hbe-section-edit-form', function (e) {
        e.preventDefault();
        var $form   = $(this);
        var $panel  = $form.closest('.hbe-edit-panel');
        var $status = $panel.find('.hbe-save-status');
        var $alerts = $panel.find('.hbe-alerts');
        $alerts.empty();

        var data = $form.serializeArray();

        // Karuzela produktowa ma zwykły formularz zamiast pola z JSON-em —
        // składamy z niego section_data i odsyłamy pomocnicze pola pc_*,
        // żeby backend dostał dokładnie to, czego się spodziewa.
        if ($form.data('section-type') === 'products') {
            var sd = {};
            try {
                sd = JSON.parse($form.find('[name=pc_original]').val() || '{}') || {};
            } catch (err) {
                sd = {};
            }
            sd.langs = {};
            data = data.filter(function (f) { return f.name.indexOf('pc_') !== 0; });

            sd.id_category = parseInt($form.find('[name=pc_id_category]').val(), 10) || 0;
            sd.number      = parseInt($form.find('[name=pc_number]').val(), 10) || 8;
            sd.randomized  = $form.find('[name=pc_randomized]').is(':checked') ? 1 : 0;

            $form.find('[name^="pc_title["], [name^="pc_text["]').each(function () {
                var m = this.name.match(/^pc_(title|text)\[(\d+)\]$/);
                if (!m) { return; }
                var field = m[1];
                var lid   = m[2];
                var val   = $.trim(this.value);
                if (val === '') { return; }
                if (!sd.langs[lid]) { sd.langs[lid] = {}; }
                sd.langs[lid][field] = val;
            });

            data.push({ name: 'section_data', value: JSON.stringify(sd) });
        }

        data.push({ name: 'action', value: 'SaveSectionBlock' });
        data.push({ name: 'ajax',   value: '1' });

        $.ajax({
            url: hbeAjaxUrl,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $status.show().delay(2500).fadeOut();
                } else {
                    $alerts.html('<div class="alert alert-danger">' + (resp && resp.error ? resp.error : hbeTrans.error) + '</div>');
                }
            },
            error: function () {
                $alerts.html('<div class="alert alert-danger">' + hbeTrans.error + '</div>');
            }
        });
    });

    /* ── Delete block ──────────────────────────────────────────────────────── */
    $(document).on('click', '.hbe-delete', function () {
        if (!confirm(hbeTrans.confirmDelete)) return;
        var $row = $(this).closest('.hbe-block-row');
        var id   = $(this).data('id');
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteBlock&ajax=1',
            type: 'POST',
            data: { id_block: id, token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $row.fadeOut(300, function () {
                        $(this).remove();
                        // Remove empty hook groups
                        $('.hbe-sortable').each(function () {
                            if ($(this).children('.hbe-block-row').length === 0) {
                                $(this).closest('.hbe-hook-group').fadeOut(200, function () {
                                    $(this).remove();
                                });
                            }
                        });
                    });
                }
            }
        });
    });

    /* ── Image upload ──────────────────────────────────────────────────────── */
    $(document).on('change', '.hbe-image-upload', function () {
        var file = this.files[0];
        if (!file) return;
        var id   = $(this).data('id');
        var side = $(this).data('side');
        var fd   = new FormData();
        fd.append('image', file);
        fd.append('id_block', id);
        fd.append('side', side);
        fd.append('token', hbeToken);

        var $preview = $('#hbe-img-prev-' + id + '-' + side);
        $.ajax({
            url: hbeAjaxUrl + 'action=UploadImage&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    $preview.attr('src', resp.url).removeClass('hbe-hidden');
                    // Show delete button
                    if (!$preview.next('.hbe-img-delete').length) {
                        $preview.after(
                            '<button type="button" class="btn btn-xs btn-danger hbe-img-delete"' +
                            ' data-id="' + id + '" data-side="' + side + '">' +
                            '<i class="icon-trash"></i></button>'
                        );
                    }
                }
            }
        });
    });

    /* ── Image delete ──────────────────────────────────────────────────────── */
    $(document).on('click', '.hbe-img-delete', function () {
        if (!confirm(hbeTrans.confirmImg)) return;
        var id   = $(this).data('id');
        var side = $(this).data('side');
        var $btn = $(this);
        $.ajax({
            url: hbeAjaxUrl + 'action=DeleteImage&ajax=1',
            type: 'POST',
            data: { id_block: id, side: side, token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    var $preview = $('#hbe-img-prev-' + id + '-' + side);
                    $preview.addClass('hbe-hidden').attr('src', '');
                    $btn.remove();
                }
            }
        });
    });

    /* ── Header toggles: save ─────────────────────────────────────────────── */
    $(document).on('submit', '#hbe-toggles-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = [
            { name: 'token', value: $form.find('[name=token]').val() },
            { name: 'hide_currency_desktop', value: $form.find('[name=hide_currency_desktop]').is(':checked') ? 1 : 0 },
            { name: 'hide_currency_mobile',  value: $form.find('[name=hide_currency_mobile]').is(':checked')  ? 1 : 0 },
            { name: 'hide_language_desktop', value: $form.find('[name=hide_language_desktop]').is(':checked') ? 1 : 0 },
            { name: 'hide_language_mobile',  value: $form.find('[name=hide_language_mobile]').is(':checked')  ? 1 : 0 },
            { name: 'hide_quickview',        value: $form.find('[name=hide_quickview]').is(':checked')        ? 1 : 0 },
            { name: 'wishlist_preview',      value: $form.find('[name=wishlist_preview]').is(':checked')      ? 1 : 0 }
        ];
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveHeaderToggles&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Miniatury produktu: gotowe zestawy, podglad na zywo, zapis ───────── */
    (function () {
        var $form = $('#hbe-mini-form');
        if (!$form.length) {
            return;
        }

        // Komplet wartosci kazdego zestawu (juz scalony z domyslnymi) — panel
        // dostaje go z kontrolera, zeby definicja zestawu byla w jednym miejscu.
        var presets = $form.data('presets') || {};
        var $preview = $('#hbe-mini-preview');
        var $stateName = $('#hbe-mini-state-name');
        var $body = $('#hbe-mini-body');
        var $themeNote = $('#hbe-mini-theme-note');
        var $enabled = $('#hbe-mini-enabled');

        // Nominalna szerokosc kafla na sklepie. Podglad jest kilka razy wezszy,
        // wiec marginesy, zaokraglenia i przerwy trzeba przeskalowac tym samym
        // wspolczynnikiem — inaczej 40 px paddingu zjadloby w nim caly kadr.
        var TILE_WIDTH = 300;

        function val(name) {
            return $form.find('[name="mini_' + name + '"]').val();
        }

        function read() {
            return {
                enabled:     parseInt($enabled.val(), 10) ? 1 : 0,
                pad:         parseInt(val('pad'), 10) || 0,
                ratio:       val('ratio') || '',
                fit:         val('fit') === 'contain' ? 'contain' : 'cover',
                radius:      parseInt(val('radius'), 10) || 0,
                gap:         parseInt(val('gap'), 10) || 0,
                car_desktop: parseInt(val('car_desktop'), 10) || 3,
                car_mobile:  parseInt(val('car_mobile'), 10) || 1,
                car_border:  $form.find('[name="mini_car_border"]').is(':checked') ? 1 : 0,
                list_cols:   parseInt(val('list_cols'), 10) || 0,
                zoom:        $form.find('[name="mini_zoom"]').is(':checked') ? 1 : 0
            };
        }

        function apply(values) {
            if (!values) {
                return;
            }
            $enabled.val(values.enabled ? 1 : 0);
            ['pad', 'ratio', 'fit', 'radius', 'gap', 'car_desktop', 'car_mobile', 'list_cols'].forEach(function (key) {
                if (typeof values[key] !== 'undefined') {
                    $form.find('[name="mini_' + key + '"]').val(String(values[key]));
                }
            });
            $form.find('[name="mini_car_border"]').prop('checked', !!values.car_border);
            $form.find('[name="mini_zoom"]').prop('checked', !!values.zoom);
        }

        // Nazwa zestawu nigdzie sie nie zapisuje — wynika z wartosci, wiec
        // reczna zmiana pojedynczego pola od razu daje "ustawienia wlasne".
        function detect(current) {
            if (!current.enabled) {
                return 'theme';
            }
            var found = 'custom';
            Object.keys(presets).forEach(function (key) {
                if (found !== 'custom' || key === 'theme') {
                    return;
                }
                var same = Object.keys(current).every(function (field) {
                    return String(current[field]) === String(presets[key][field]);
                });
                if (same) {
                    found = key;
                }
            });
            return found;
        }

        function paint(v) {
            var $tiles = $preview.children('.hbe-mini-tile');
            $tiles.each(function (i) {
                $(this).toggle(i < v.car_desktop);
            });

            // Zakladka bywa jeszcze ukryta (szerokosc 0) — wtedy skala z
            // ostatniego znanego rozmiaru, a wlasciwe malowanie przychodzi
            // z obslugi shown.bs.tab.
            var inner = $preview.width() || 340;
            var scale = Math.min(1, inner / (v.car_desktop * TILE_WIDTH));

            $preview.css('gap', Math.round(v.gap * scale) + 'px');
            $tiles.css('border', v.car_border ? '1px solid #dcdcdc' : '0');
            $tiles.find('.hbe-mini-tile__photo').css({
                padding: Math.round(v.pad * scale) + 'px',
                borderRadius: Math.round(v.radius * scale) + 'px',
                aspectRatio: v.ratio ? v.ratio.replace('/', ' / ') : '3 / 4',
                backgroundSize: v.ratio ? v.fit : 'cover'
            });
        }

        function refresh() {
            var v = read();
            var preset = detect(v);

            $('.hbe-mini-preset').each(function () {
                $(this).toggleClass('is-active', $(this).data('preset') === preset);
            });

            var label = $stateName.data('custom-label');
            if (preset !== 'custom') {
                var $active = $('.hbe-mini-preset[data-preset="' + preset + '"] .hbe-mini-preset__name');
                if ($active.length) {
                    label = $active.text();
                }
            }
            $stateName.text($.trim(label));

            $body.toggleClass('is-theme', !v.enabled);
            $themeNote.toggle(!v.enabled);
            // Dopasowanie kadru dziala tylko przy wymuszonej proporcji.
            $('.hbe-mini-row-fit').toggle(v.ratio !== '');

            paint(v);
        }

        $(document).on('click', '.hbe-mini-preset', function () {
            apply(presets[$(this).data('preset')]);
            refresh();
        });

        // Kazda reczna zmiana przejmuje kontrole nad kaflem: bez tego pola
        // dzialalyby "w prozni", bo przy wygladzie z motywu modul milczy.
        $form.on('change', 'select, input[type="checkbox"]', function () {
            $enabled.val(1);
            refresh();
        });

        $(document).on('shown.bs.tab', 'a[href="#hbe-tab-miniatures"]', refresh);
        refresh();

        $(document).on('submit', '#hbe-mini-form', function (e) {
            e.preventDefault();
            var v = read();
            var data = [{ name: 'token', value: $form.find('[name=token]').val() }];
            Object.keys(v).forEach(function (key) {
                data.push({ name: 'mini_' + key, value: v[key] });
            });
            $.ajax({
                url: hbeAjaxUrl + 'action=SaveMiniatures&ajax=1',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (resp) {
                    if (resp && resp.success) {
                        showGlobalSuccess(hbeTrans.saved);
                        clearFormErrors($form);
                        refresh();
                    } else {
                        showFormError($form, resp ? resp.error : hbeTrans.error);
                    }
                },
                error: function () { showFormError($form, hbeTrans.error); }
            });
        });
    })();

    /* ── Footer social links: save ────────────────────────────────────────── */
    $(document).on('submit', '#hbe-social-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveSocial&ajax=1',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Menu flat layout: save ───────────────────────────────────────────── */
    $(document).on('submit', '#hbe-menuflat-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = [
            { name: 'token', value: $form.find('[name=token]').val() }
        ];
        $form.find('select.hbe-menu-layout').each(function () {
            data.push({ name: 'menu_layout[' + $(this).data('item-id') + ']', value: $(this).val() });
        });
        $form.find('input[name="flat_items_mobile[]"]:checked').each(function () {
            data.push({ name: 'flat_items_mobile[]', value: $(this).val() });
        });
        ['hidden_items', 'featured_items', 'bottom_items'].forEach(function (field) {
            $form.find('input[name="' + field + '[]"]:checked').each(function () {
                data.push({ name: field + '[]', value: $(this).val() });
            });
        });
        data.push({ name: 'featured_style', value: $form.find('[name=featured_style]').val() || '' });
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveMenuFlat&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Cart preview: save ───────────────────────────────────────────────── */
    /* Manual amount only matters in "manual" mode — keep the row out of the way otherwise. */
    $(document).on('change', '#hbe-free-ship-mode', function () {
        $('#hbe-free-ship-manual-row').toggle($(this).val() === 'manual');
    });

    $(document).on('submit', '#hbe-cart-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = [
            { name: 'token', value: $form.find('[name=token]').val() },
            { name: 'cart_hover',         value: $form.find('[name=cart_hover]').is(':checked')         ? 1 : 0 },
            { name: 'cart_preview_modal', value: $form.find('[name=cart_preview_modal]').is(':checked') ? 1 : 0 },
            { name: 'cart_free_shipping_mode',      value: $form.find('[name=cart_free_shipping_mode]').val() },
            { name: 'cart_free_shipping_threshold', value: $form.find('[name=cart_free_shipping_threshold]').val() }
        ];
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveCartSettings&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Kasa (kroki zamówienia): save ────────────────────────────────────── */
    $(document).on('submit', '#hbe-checkout-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = [
            { name: 'token', value: $form.find('[name=token]').val() },
            { name: 'checkout_skin',         value: $form.find('[name=checkout_skin]').is(':checked')         ? 1 : 0 },
            { name: 'checkout_onepage',      value: $form.find('[name=checkout_onepage]').is(':checked')      ? 1 : 0 },
            { name: 'checkout_terms_bottom', value: $form.find('[name=checkout_terms_bottom]').is(':checked') ? 1 : 0 }
        ];
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveCheckoutSettings&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Karta produktu: save ─────────────────────────────────────────────── */
    $(document).on('submit', '#hbe-productcard-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = [
            { name: 'token', value: $form.find('[name=token]').val() },
            { name: 'product_summary_source', value: $form.find('[name=product_summary_source]').val() }
        ];
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveProductCardSettings&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Karta produktu: zoom na okladce ──────────────────────────────────── */
    $(document).on('submit', '#hbe-zoom-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = [
            { name: 'token', value: $form.find('[name=token]').val() },
            { name: 'zoom_enabled', value: $form.find('[name=zoom_enabled]').is(':checked') ? 1 : 0 },
            { name: 'zoom_level', value: $form.find('[name=zoom_level]').val() }
        ];
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveZoomSettings&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Rosenthal Care (cart block): save ────────────────────────────────── */
    $(document).on('submit', '#hbe-care-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var data = [
            { name: 'token', value: $form.find('[name=token]').val() },
            { name: 'care_enabled',        value: $form.find('[name=care_enabled]').is(':checked')        ? 1 : 0 },
            { name: 'care_product_id',     value: $form.find('[name=care_product_id]').val() },
            { name: 'care_heading',        value: $form.find('[name=care_heading]').val() },
            { name: 'care_text',           value: $form.find('[name=care_text]').val() },
            { name: 'care_button',         value: $form.find('[name=care_button]').val() },
            { name: 'care_login_required', value: $form.find('[name=care_login_required]').is(':checked') ? 1 : 0 }
        ];
        $.ajax({
            url: hbeAjaxUrl + 'action=SaveCareSettings&ajax=1',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess(hbeTrans.saved);
                    clearFormErrors($form);
                } else {
                    showFormError($form, resp ? resp.error : hbeTrans.error);
                }
            },
            error: function () { showFormError($form, hbeTrans.error); }
        });
    });

    /* ── Helpers ───────────────────────────────────────────────────────────── */

    function ensureToastContainer() {
        var $c = $('#hbe-toast-container');
        if (!$c.length) {
            $c = $('<div id="hbe-toast-container"></div>').css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: 99999,
                maxWidth: '380px',
                pointerEvents: 'none'
            }).appendTo('body');
        }
        return $c;
    }

    function showToast(msg, type) {
        var bg = type === 'error' ? '#d9534f' : '#28a745';
        var $t = $('<div class="hbe-toast"></div>')
            .text(msg)
            .css({
                background: bg,
                color: '#fff',
                padding: '12px 18px',
                marginBottom: '8px',
                borderRadius: '4px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.18)',
                fontSize: '14px',
                fontWeight: 500,
                opacity: 0,
                transform: 'translateX(20px)',
                transition: 'opacity .25s, transform .25s',
                pointerEvents: 'auto'
            });
        ensureToastContainer().append($t);
        // animate in
        requestAnimationFrame(function () {
            $t.css({ opacity: 1, transform: 'translateX(0)' });
        });
        var ttl = type === 'error' ? 5000 : 2500;
        setTimeout(function () {
            $t.css({ opacity: 0, transform: 'translateX(20px)' });
            setTimeout(function () { $t.remove(); }, 300);
        }, ttl);
    }

    function flashSubmitButton($form) {
        var $btn = $form.find('button[type=submit]').first();
        if (!$btn.length) { return; }
        var origHtml = $btn.data('hbeOrigHtml');
        if (!origHtml) {
            origHtml = $btn.html();
            $btn.data('hbeOrigHtml', origHtml);
        }
        var origBg = $btn.css('background-color');
        $btn.html('<i class="icon-check"></i> ' + (hbeTrans && hbeTrans.saved ? hbeTrans.saved : 'Zapisano')).css('background-color', '#28a745');
        clearTimeout($btn.data('hbeFlashT'));
        $btn.data('hbeFlashT', setTimeout(function () {
            $btn.html(origHtml).css('background-color', '');
        }, 1800));
    }

    function showGlobalSuccess(msg) {
        showToast(msg, 'success');
    }

    function showGlobalError(msg) {
        showToast(msg, 'error');
    }

    function showFormError($form, msg) {
        var $el = $('<div class="alert alert-danger">' + escHtml(msg || hbeTrans.error) + '</div>');
        $form.find('.hbe-alerts').html($el);
        showToast(msg || (hbeTrans && hbeTrans.error) || 'Error', 'error');
    }

    function clearFormErrors($form) {
        $form.find('.hbe-alerts').empty();
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ── Module management: add module to HBE control ─────────────────────── */
    $(document).on('click', '.hbe-add-module-btn', function () {
        var modName = $(this).data('module');
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: hbeAjaxUrl + 'action=AddManagedModule&ajax=1',
            type: 'POST',
            data: { module_name: modName, token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess('Moduł ' + escHtml(modName) + ' przeniesiony pod kontrolę HBE.');
                    setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    $btn.prop('disabled', false);
                    showGlobalError((resp && resp.error) || hbeTrans.error);
                }
            },
            error: function () { $btn.prop('disabled', false); }
        });
    });

    /* ── Module management: release module back to PS hook system ─────────── */
    $(document).on('click', '.hbe-release-module-btn', function () {
        var modName = $(this).data('module');
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: hbeAjaxUrl + 'action=ReleaseManagedModule&ajax=1',
            type: 'POST',
            data: { module_name: modName, token: hbeToken },
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    showGlobalSuccess('Moduł ' + escHtml(modName) + ' zwrócony do systemu hooków PS.');
                    setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    $btn.prop('disabled', false);
                    showGlobalError((resp && resp.error) || hbeTrans.error);
                }
            },
            error: function () { $btn.prop('disabled', false); }
        });
    });

    /* ── Backup import (ZIP/XML upload) ──────────────────────────────────── */
    function hbeImportStatsMsg(s) {
        s = s || {};
        var msg = 'Import OK: ' +
            'config=' + (s.configurations || 0) +
            ', config_lang=' + (s.config_lang || 0) +
            ', bloki=' + (s.blocks || 0) +
            ', bloki_lang=' + (s.block_lang || 0) +
            ', slajdy=' + (s.slides || 0) +
            ', slajdy_lang=' + (s.slide_lang || 0) +
            ', obrazki=' + (s.images || 0);
        if (s.hooks || s.hook_bindings || s.modules_enabled) {
            msg += ', hooki=' + (s.hooks || 0) +
                   ', przypięcia=' + (s.hook_bindings || 0) +
                   ', moduły_wł=' + (s.modules_enabled || 0);
        }
        return msg;
    }
    function hbeHandleImportResponse($ctl, resp) {
        if ($ctl) { $ctl.prop('disabled', false); }
        if (resp && resp.success) {
            showGlobalSuccess(hbeImportStatsMsg(resp.stats));
            if (resp.missing_modules && resp.missing_modules.length) {
                showGlobalError('Uwaga: te moduły z backupu nie są zainstalowane na tym sklepie (pomięto ich hooki): ' +
                    resp.missing_modules.join(', '));
            }
            setTimeout(function () { window.location.reload(); }, resp.missing_modules && resp.missing_modules.length ? 4000 : 1800);
        } else {
            showGlobalError((resp && resp.error) || 'Import nieudany');
        }
    }

    /* ── Export: honour the "hooks + modules" checkbox ───────────────────── */
    $(document).on('click', '#hbe-export-btn', function (e) {
        var base = $(this).data('export-url');
        if (!base) { return; }
        e.preventDefault();
        var url = base + ($('#hbe-opt-hooks').is(':checked') ? '&opt_hooks=1' : '');
        window.location.href = url;
    });

    $(document).on('click', '#hbe-import-btn', function () {
        $('#hbe-import-file').trigger('click');
    });
    $(document).on('change', '#hbe-import-file', function () {
        var file = this.files && this.files[0];
        if (!file) { return; }
        if (!confirm('Import nadpisze wszystkie ustawienia HBE, bloki oraz slider tego modułu na tym sklepie. Kontynuować?')) {
            this.value = '';
            return;
        }
        var fd = new FormData();
        fd.append('file', file);
        fd.append('token', hbeToken);
        fd.append('purge_blocks', '1');
        var $input = $(this).prop('disabled', true);
        showGlobalSuccess('Wysyłanie pliku, proszę czekać…');
        $.ajax({
            url: hbeAjaxUrl + 'action=ImportSettings&ajax=1',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resp) { $input.val(''); hbeHandleImportResponse($input, resp); },
            error: function (xhr) {
                $input.prop('disabled', false).val('');
                if (xhr && (xhr.status === 413 || xhr.status === 0)) {
                    showGlobalError('Plik za duży dla uploadu przeglądarki — wgraj ZIP przez FTP/SFTP i użyj „Backupy na serwerze".');
                } else {
                    showGlobalError('Błąd komunikacji podczas importu');
                }
            }
        });
    });

    /* ── Backup import from server (large ZIPs, no upload limit) ──────────── */
    $(document).on('click', '#hbe-server-import-btn', function () {
        var name = $('#hbe-server-backup-select').val();
        if (!name) { return; }
        if (!confirm('Import „' + name + '" nadpisze wszystkie ustawienia HBE, bloki oraz slider tego modułu na tym sklepie. Kontynuować?')) {
            return;
        }
        var $btn = $(this).prop('disabled', true);
        showGlobalSuccess('Importowanie backupu z serwera, proszę czekać…');
        $.ajax({
            url: hbeAjaxUrl + 'action=ImportServerBackup&ajax=1',
            type: 'POST',
            data: { token: hbeToken, backup_file: name, purge_blocks: '1' },
            dataType: 'json',
            success: function (resp) { hbeHandleImportResponse($btn, resp); },
            error: function () {
                $btn.prop('disabled', false);
                showGlobalError('Błąd komunikacji podczas importu z serwera');
            }
        });
    });

    /* ── Collapse panel chevron sync ── */
    $(document).on('show.bs.collapse', '.hbe-collapse-panel .panel-collapse', function () {
        $(this).closest('.hbe-collapse-panel').find('.hbe-chevron').addClass('hbe-chevron-open');
    });
    $(document).on('hide.bs.collapse', '.hbe-collapse-panel .panel-collapse', function () {
        $(this).closest('.hbe-collapse-panel').find('.hbe-chevron').removeClass('hbe-chevron-open');
    });

    /* ── ml_images checkbox toggle: show/hide base vs per-lang image fields ── */
    $(document).on('change', 'input[type=checkbox][name=ml_images]', function () {
        var checked = $(this).is(':checked');
        // Toggle inside the SAME form scope
        var $form = $(this).closest('form');
        $form.find('.hbe-base-img').toggle(!checked);
        $form.find('.hbe-ml-imgs').toggle(checked);
    });

    /* ── Mobile-version toggle: show/hide mobile-image upload block ── */
    $(document).on('change', '.hbe-mobile-toggle', function () {
        var checked = $(this).is(':checked');
        var $block = $(this).closest('[data-hbe-img-block]').find('.hbe-mobile-img-block').first();
        $block.toggle(checked);
        if (!checked) {
            // Clear any selected files so they're not submitted
            $block.find('input[type=file]').val('');
            // Mark hidden flag so backend knows to clear mobile config
            var $form = $(this).closest('form');
            var blockName = $block.find('input[type=file]').first().attr('name') || '';
            // strip _lang_X / _mobile suffixes to get base name
            var base = blockName.replace(/(_mobile_lang_\d+|_mobile)$/, '');
            if (base) {
                $form.find('input[type=hidden][name="' + base + '_mobile_clear"]').remove();
                $form.append('<input type="hidden" name="' + base + '_mobile_clear" value="1">');
            }
        } else {
            var $form = $(this).closest('form');
            var blockName = $block.find('input[type=file]').first().attr('name') || '';
            var base = blockName.replace(/(_mobile_lang_\d+|_mobile)$/, '');
            if (base) {
                $form.find('input[type=hidden][name="' + base + '_mobile_clear"]').remove();
            }
        }
    });

    /* ── Generic per-section image delete (data-action / data-lang / data-extra / data-variant) ── */
    $(document).on('click', '.hbe-img-del', function (e) {
        e.preventDefault();
        var $btn   = $(this);
        var action = $btn.data('action');
        var idLang = parseInt($btn.data('lang') || 0, 10);
        var extra  = String($btn.data('extra') || '');
        var variant = String($btn.data('variant') || 'desktop');
        if (!action) { return; }
        if (!confirm('Usunąć zdjęcie?')) { return; }
        var payload = { token: hbeToken, lang_id_target: idLang, variant: variant };
        // Parse "key1=val1&key2=val2" into payload
        if (extra) {
            extra.split('&').forEach(function (kv) {
                var eq = kv.indexOf('=');
                if (eq > -1) {
                    payload[kv.slice(0, eq)] = kv.slice(eq + 1);
                }
            });
        }
        $.ajax({
            url: hbeAjaxUrl + 'action=' + action + '&ajax=1',
            type: 'POST',
            data: payload,
            dataType: 'json',
            success: function (resp) {
                if (resp && resp.success) {
                    if (idLang > 0) {
                        // Remove per-lang preview row
                        $btn.closest('div').find('img').first().remove();
                        $btn.remove();
                    } else {
                        // Remove base preview
                        var $wrap = $btn.closest('[id$="-wrap"]');
                        if ($wrap.length) {
                            $wrap.hide().find('img').attr('src', '');
                        } else {
                            $btn.closest('div').find('img').first().attr('src', '').end().end().hide();
                        }
                    }
                    showGlobalSuccess(hbeTrans.saved);
                } else {
                    showGlobalError((resp && resp.error) || hbeTrans.error);
                }
            },
            error: function () { showGlobalError(hbeTrans.error); }
        });
    });

    /* ── Slider tab: open from URL hash + drag-to-reorder slides ───────────── */
    (function () {
        // Activate the correct main tab when the page is loaded with a hash
        // (e.g. after add/edit/save redirects to #hbe-tab-slider). The hash may
        // point at a section nested inside a tab (slider lives in the
        // "Strona główna" tab) — fall back to the enclosing tab-pane.
        if (window.location.hash) {
            var $tabLink = $('.hbe-main-tabs a[href="' + window.location.hash + '"]');
            if (!$tabLink.length) {
                var $pane = $(window.location.hash).closest('.hbe-tab-content > .tab-pane');
                if ($pane.length) {
                    $tabLink = $('.hbe-main-tabs a[href="#' + $pane.attr('id') + '"]');
                }
            }
            if ($tabLink.length) {
                $tabLink.tab('show');
            }
        }

        // jQuery UI Sortable on the slides list → persist new order via AJAX.
        var $list = $('#hbe-slides');
        if ($list.length && typeof $.fn.sortable === 'function') {
            $list.sortable({
                items: '> tr[data-id]',
                cursor: 'move',
                update: function () {
                    var slideIds = $list.find('> tr[data-id]').map(function () {
                        return $(this).data('id');
                    }).get();
                    $.post(hbeAjaxUrl + 'action=UpdateSlidesPosition&ajax=1', {
                        token: hbeToken,
                        slides: slideIds
                    });
                }
            });
        }
    })();
});
