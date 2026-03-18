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
                    var els = document.querySelectorAll('.ql-toolbar .' + cls + ', .ql-toolbar button[class="' + cls + '"]');
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
    </script>
    <?php include '../includes/feedback_drawer.php'; ?>
</body>
</html>
