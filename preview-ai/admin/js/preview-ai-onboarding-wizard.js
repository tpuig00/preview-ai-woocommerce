(function($) {
    'use strict';
    
    $(function() {
        if (typeof previewAiOnboarding === 'undefined') {
            console.error('previewAiOnboarding is not defined');
            return;
        }

        var ajaxUrl = previewAiOnboarding.ajaxUrl;
        var nonce = previewAiOnboarding.nonce;
        
        var $bar = $('#onboarding-bar');
        var $status = $('#onboarding-status');
        var $result = $('#onboarding-result');
        var $progress = $('#onboarding-progress');
        
        if (!$bar.length) return;

        var currentWidth = 0;
        var progressInterval = setInterval(function() {
            if (currentWidth < 95) {
                var step = currentWidth < 60 ? Math.floor(Math.random() * 3) + 1 : (Math.random() < 0.5 ? 1 : 0);
                if (step > 0 || currentWidth < 20) {
                    currentWidth = Math.min(95, currentWidth + (step || 1));
                    $bar.css('width', currentWidth + '%');
                }
            }
        }, 800);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'preview_ai_learn_catalog',
                nonce: nonce
            },
            beforeSend: function() {
                $status.text(previewAiOnboarding.i18n.configuring);
            },
            success: function(response) {
                clearInterval(progressInterval);
                $bar.css('width', '100%');
                
                setTimeout(function() {
                    $progress.slideUp(300);
                    
                    if (response.success) {
                        var status = response.data.status || 'completed';
                        
                        if (status === 'scheduled') {
                            var totalProducts = response.data.total || 0;
                            var scheduledPdpNotice = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-top:16px;text-align:left;">' +
                                '<p style="color:#0369a1;margin:0 0 8px;font-size:13px;font-weight:600;">💡 ' + previewAiOnboarding.i18n.customTemplate + '</p>' +
                                '<p style="color:#0284c7;margin:0 0 8px;font-size:12px;">' + previewAiOnboarding.i18n.manualAdd + '</p>' +
                                '<ul style="color:#0284c7;margin:0 0 8px 16px;font-size:12px;list-style:disc;">' +
                                '<li><strong>Shortcode:</strong> <code style="background:#e0f2fe;padding:2px 6px;border-radius:3px;">[preview_ai]</code></li>' +
                                '<li><strong>Elementor:</strong> ' + previewAiOnboarding.i18n.elementorSearch + '</li>' +
                                '</ul>' +
                                '<p style="color:#0284c7;margin:0;font-size:12px;">⚙️ ' + previewAiOnboarding.i18n.configureIn + '</p>' +
                                '</div>';
                            $result.html(
                                '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:20px;margin-bottom:24px;">' +
                                '<p style="color:#1d4ed8;font-weight:600;margin:0;font-size:16px;">⏳ ' + 
                                previewAiOnboarding.i18n.analyzingBackground + '</p>' +
                                '<p style="color:#1e40af;margin:8px 0 0;font-size:14px;">' + totalProducts + ' ' + previewAiOnboarding.i18n.productsAnalyzed + '</p>' +
                                '<p style="color:#3b82f6;margin:12px 0 0;font-size:13px;">' + previewAiOnboarding.i18n.closeAndCheck + '</p>' +
                                '</div>' +
                                scheduledPdpNotice +
                                '<div style="text-align:center;margin-top:16px;">' +
                                '<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;font-size:14px;" onclick="location.reload()">' +
                                previewAiOnboarding.i18n.closeAndContinue + '</button>' +
                                '</div>'
                            ).slideDown(300);
                            return;
                        }
                        
                        var configured = response.data.configured || 0;
                        var total = response.data.total || 0;
                        var tryProductUrl = response.data.try_product_url || '';
                        
                        var customPdpNotice = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-top:12px;text-align:left;">' +
                            '<p style="color:#0369a1;margin:0 0 8px;font-size:13px;font-weight:600;">💡 ' + previewAiOnboarding.i18n.customTemplate + '</p>' +
                            '<p style="color:#0284c7;margin:0 0 8px;font-size:12px;">' + previewAiOnboarding.i18n.manualAdd + '</p>' +
                            '<ul style="color:#0284c7;margin:0 0 8px 16px;font-size:12px;list-style:disc;">' +
                            '<li><strong>Shortcode:</strong> <code style="background:#e0f2fe;padding:2px 6px;border-radius:3px;">[preview_ai]</code></li>' +
                            '<li><strong>Elementor:</strong> ' + previewAiOnboarding.i18n.elementorSearch + '</li>' +
                            '</ul>' +
                            '<p style="color:#0284c7;margin:0;font-size:12px;">⚙️ ' + previewAiOnboarding.i18n.configureIn + '</p>' +
                            '</div>';

                        var actionButtons = '';
                        if (tryProductUrl && configured > 0) {
                            actionButtons = '<div style="margin-bottom:16px;">' +
                                '<a href="' + tryProductUrl + '" target="_blank" class="button button-primary" style="height:auto;padding:14px 32px;font-size:15px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;box-shadow:0 4px 14px rgba(99,102,241,0.4);">' +
                                '✨ ' + previewAiOnboarding.i18n.tryNow + '</a>' +
                                '</div>' +
                                '<p style="color:#64748b;font-size:13px;margin:0;">' + previewAiOnboarding.i18n.experienceMagic + '</p>' +
                                customPdpNotice;
                        } else {
                            actionButtons = '<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;font-size:14px;" onclick="location.reload()">' +
                                previewAiOnboarding.i18n.closeAndConfigure + '</button>' +
                                customPdpNotice;
                        }
                        
                        $result.html(
                            '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;margin-bottom:24px;">' +
                            '<p style="color:#166534;font-weight:600;margin:0;font-size:16px;">✓ ' + 
                            previewAiOnboarding.i18n.catalogConfigured + '</p>' +
                            '<p style="color:#15803d;margin:8px 0 0;font-size:14px;">' + configured + ' ' + previewAiOnboarding.i18n.productsReady + '</p>' +
                            '</div>' +
                            '<div style="text-align:center;">' +
                            actionButtons +
                            '</div>'
                        ).slideDown(300);
                    } else {
                        var errorPdpNotice = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-top:16px;text-align:left;">' +
                            '<p style="color:#0369a1;margin:0 0 8px;font-size:13px;font-weight:600;">💡 ' + previewAiOnboarding.i18n.customTemplate + '</p>' +
                            '<p style="color:#0284c7;margin:0 0 8px;font-size:12px;">' + previewAiOnboarding.i18n.manualAddNow + '</p>' +
                            '<ul style="color:#0284c7;margin:0 0 8px 16px;font-size:12px;list-style:disc;">' +
                            '<li><strong>Shortcode:</strong> <code style="background:#e0f2fe;padding:2px 6px;border-radius:3px;">[preview_ai]</code></li>' +
                            '<li><strong>Elementor:</strong> ' + previewAiOnboarding.i18n.elementorSearch + '</li>' +
                            '</ul>' +
                            '<p style="color:#0284c7;margin:0;font-size:12px;">⚙️ ' + previewAiOnboarding.i18n.configureIn + '</p>' +
                            '</div>';
                        $result.html(
                            '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:20px;margin-bottom:24px;">' +
                            '<p style="color:#dc2626;font-weight:600;margin:0;">' + (response.data.message || previewAiOnboarding.i18n.couldNotAnalyze) + '</p>' +
                            '<p style="color:#b91c1c;margin:8px 0 0;font-size:14px;">' + previewAiOnboarding.i18n.manualConfig + '</p>' +
                            '</div>' +
                            errorPdpNotice +
                            '<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;margin-top:16px;" onclick="location.reload()">' +
                            previewAiOnboarding.i18n.continueToSettings + '</button>'
                        ).slideDown(300);
                    }
                }, 500);
            },
            error: function() {
                clearInterval(progressInterval);
                $bar.css('width', '100%');
                $progress.slideUp(300);
                
                var connectionPdpNotice = '<div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-top:16px;text-align:left;">' +
                    '<p style="color:#0369a1;margin:0 0 8px;font-size:13px;font-weight:600;">💡 ' + previewAiOnboarding.i18n.customTemplate + '</p>' +
                    '<p style="color:#0284c7;margin:0 0 8px;font-size:12px;">' + previewAiOnboarding.i18n.manualAddNow + '</p>' +
                    '<ul style="color:#0284c7;margin:0 0 8px 16px;font-size:12px;list-style:disc;">' +
                    '<li><strong>Shortcode:</strong> <code style="background:#e0f2fe;padding:2px 6px;border-radius:3px;">[preview_ai]</code></li>' +
                    '<li><strong>Elementor:</strong> ' + previewAiOnboarding.i18n.elementorSearch + '</li>' +
                    '</ul>' +
                    '<p style="color:#0284c7;margin:0;font-size:12px;">⚙️ ' + previewAiOnboarding.i18n.configureIn + '</p>' +
                    '</div>';
                $result.html(
                    '<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:20px;margin-bottom:24px;">' +
                    '<p style="color:#92400e;font-weight:600;margin:0;">' + previewAiOnboarding.i18n.couldNotConnect + '</p>' +
                    '<p style="color:#a16207;margin:8px 0 0;font-size:14px;">' + previewAiOnboarding.i18n.analyzeLater + '</p>' +
                    '</div>' +
                    connectionPdpNotice +
                    '<button type="button" class="button button-primary" style="height:auto;padding:12px 24px;margin-top:16px;" onclick="location.reload()">' +
                    previewAiOnboarding.i18n.continue + '</button>'
                ).slideDown(300);
            }
        });
        
        if (history.replaceState) {
            var cleanUrl = window.location.href
                .replace(/[?&]onboarding=complete/, '')
                .replace(/\?$/, '');
            history.replaceState(null, '', cleanUrl);
        }
        
        $('#preview-ai-onboarding-close').on('click', function() {
            $('#preview-ai-onboarding-wizard').fadeOut(300, function() {
                $(this).remove();
            });
        }).on('mouseenter', function() {
            $(this).css('background', '#f1f5f9');
        }).on('mouseleave', function() {
            $(this).css('background', 'none');
        });
    });
    
})(jQuery);
