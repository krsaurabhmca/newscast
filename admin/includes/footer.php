        </div><!-- .main-content -->
    </div><!-- .admin-wrapper -->

    <!-- Scripts -->
    <script>
        // Initialize Feather Icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        // Add Titles (Tooltips) to all RTF Editor (Quill) Toolbar Buttons
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var tooltips = {
                    'ql-header': 'Heading/Title Level',
                    'ql-bold': 'Bold',
                    'ql-italic': 'Italic',
                    'ql-underline': 'Underline',
                    'ql-strike': 'Strikethrough',
                    'ql-color': 'Text Color',
                    'ql-background': 'Background Color',
                    'ql-list[value="ordered"]': 'Numbered List',
                    'ql-list[value="bullet"]': 'Bullet List',
                    'ql-blockquote': 'Blockquote',
                    'ql-code-block': 'Code Block',
                    'ql-link': 'Insert Link',
                    'ql-image': 'Insert Image',
                    'ql-video': 'Insert Video',
                    'ql-align': 'Text Alignment',
                    'ql-clean': 'Remove Formatting'
                };
                for (var cls in tooltips) {
                    // Fix: Selector was malformed for list[value="ordered"]
                    // The class selector already handles the button identification
                    var selector = '.ql-toolbar .' + cls;
                    var els = document.querySelectorAll(selector);
                    if (els.length > 0) {
                        els.forEach(function(el) {
                            el.setAttribute('title', tooltips[cls]);
                        });
                    }
                }
                
                // For Pickers (like header, color, background, align)
                var pickers = document.querySelectorAll('.ql-picker');
                pickers.forEach(function(picker) {
                    if (picker.classList.contains('ql-header')) {
                        picker.setAttribute('title', 'Select Heading/Title Level');
                        // Optional: title for inner dropdown items
                    } else if (picker.classList.contains('ql-color')) {
                        picker.setAttribute('title', 'Text Color');
                    } else if (picker.classList.contains('ql-background')) {
                        picker.setAttribute('title', 'Background Color');
                    } else if (picker.classList.contains('ql-align')) {
                        picker.setAttribute('title', 'Text Alignment');
                    }
                });

                // Also Add explicit title mapping for inner dropdown items if needed
            }, 1000); // Small delay to wait for Quill to render
        });

        // Admin Language Switcher Logic
        function setAdminLang(lang) {
            const domain = window.location.hostname;
            if (lang === 'hi') {
                document.cookie = "googtrans=/en/hi; path=/";
                document.cookie = "googtrans=/en/hi; path=/; domain=" + domain;
            } else {
                document.cookie = "googtrans=/en/en; path=/";
                document.cookie = "googtrans=/en/en; path=/; domain=" + domain;
                // Also clear it
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + domain;
            }
            window.location.reload();
        }

        document.addEventListener('DOMContentLoaded', () => {
            const isHindi = document.cookie.indexOf('googtrans=/en/hi') !== -1;
            const btnHi = document.getElementById('btn-lang-hi');
            const btnEn = document.getElementById('btn-lang-en');
            
            if (btnHi && btnEn) {
                if (isHindi) {
                    btnHi.style.background = 'white';
                    btnHi.style.color = '#0f172a';
                    btnHi.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                    
                    btnEn.style.background = 'transparent';
                    btnEn.style.color = '#64748b';
                    btnEn.style.boxShadow = 'none';
                } else {
                    btnEn.style.background = 'white';
                    btnEn.style.color = '#0f172a';
                    btnEn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';
                    
                    btnHi.style.background = 'transparent';
                    btnHi.style.color = '#64748b';
                    btnHi.style.boxShadow = 'none';
                }
            }
        });
        
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({pageLanguage: 'en', includedLanguages: 'hi', autoDisplay: false}, 'google_translate_element');
        }
    </script>
    <div id="google_translate_element" style="display:none;"></div>
    <script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    
    <style>
        /* Hide Google Translate top banner & tooltips aggressively */
        iframe.goog-te-banner-frame { display: none !important; }
        .goog-te-banner-frame { display: none !important; }
        body { top: 0 !important; position: static !important; }
        html { top: 0 !important; position: static !important; }
        .goog-text-highlight { background-color: transparent !important; box-shadow: none !important; }
        #goog-gt-tt, .goog-te-balloon-frame { display: none !important; }
        .goog-te-gadget { display: none !important; }
    </style>
    
    <?php include '../includes/feedback_drawer.php'; ?>
</body>
</html>
