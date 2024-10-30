;(function ($) {
    $(document).on('change', '#linkz-enable', function () {
        let cb = $(this),
            enable = cb.prop('checked') ? 1 : 0
        cb.prop('disabled', true)

        $.post(
            linkzAi.ajaxurl,
            {
                action: 'linkz',
                linkz_enabled: enable,
                _ajax_nonce: linkzAi.ajaxNonce,
            },
            function (response) {
                // Handle the response if needed
            }
        ).always(function () {
            cb.prop('disabled', false)
        })
    })

    $(document).on('click', '.js-preview-switch', function () {
        let _switch = $('.js-preview-switch'),
            current = _switch.data('preview-type'),
            target = _switch
                .find(
                    `[data-preview-target]:not([data-preview-target="'${current}'"])`
                )
                .data('preview-target')

        _switch.addClass('loading')

        $.post(
            linkzAi.ajaxurl,
            {
                action: 'linkz',
                preview_type: target,
                _ajax_nonce: linkzAi.ajaxNonce,
            },
            function (response) {
                if (response.ok) {
                    _switch.attr('data-preview-type', response.preview_type)
                    _switch.data('preview-type', response.preview_type)
                }
            }
        ).always(function () {
            _switch.removeClass('loading')
        })
    })
})(jQuery)
