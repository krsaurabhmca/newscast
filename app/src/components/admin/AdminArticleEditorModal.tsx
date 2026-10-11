import React, { useState, useRef, useEffect, useCallback } from 'react';
import {
  Modal,
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  SafeAreaView,
  StatusBar,
  ScrollView,
  TextInput,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { WebView } from 'react-native-webview';
import {
  ArrowLeft,
  Check,
  Eye,
  FileEdit,
  Code,
  ImageIcon,
} from '@/components/ui/icons';
import { AdminMediaPickerModal } from '@/components/admin/AdminMediaPickerModal';
import { MediaItem } from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminArticleEditorModalProps {
  visible: boolean;
  initialContent: string;
  portalUrl: string;
  onClose: () => void;
  onApply: (formattedContent: string) => void;
}

export function AdminArticleEditorModal({
  visible,
  initialContent,
  portalUrl,
  onClose,
  onApply,
}: AdminArticleEditorModalProps) {
  const theme = useTheme();
  const webViewRef = useRef<WebView>(null);

  const [currentHtml, setCurrentHtml] = useState(initialContent || '');
  const [activeScreenTab, setActiveScreenTab] = useState<'rtf' | 'html' | 'preview'>('rtf');
  const [mediaPickerVisible, setMediaPickerVisible] = useState(false);
  const [wordsCount, setWordsCount] = useState(0);
  const [charsCount, setCharsCount] = useState(0);
  const [editorReady, setEditorReady] = useState(false);

  // Sync initial content on open
  useEffect(() => {
    if (visible) {
      const init = initialContent || '';
      setCurrentHtml(init);
      setActiveScreenTab('rtf');
      setEditorReady(false);
      calculateStats(init);
    }
  }, [visible, initialContent]);

  const calculateStats = (htmlStr: string) => {
    const text = htmlStr.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    setWordsCount(text ? text.split(' ').length : 0);
    setCharsCount(text.length);
  };

  // Handle messages from Quill inside WebView
  const handleWebViewMessage = (event: any) => {
    try {
      const data = JSON.parse(event.nativeEvent.data);
      if (data.type === 'ready') {
        setEditorReady(true);
      } else if (data.type === 'change') {
        setCurrentHtml(data.html || '');
        if (typeof data.words === 'number') setWordsCount(data.words);
        if (typeof data.chars === 'number') setCharsCount(data.chars);
      } else if (data.type === 'open_media_picker') {
        setMediaPickerVisible(true);
      }
    } catch (e) {
      console.warn('Error parsing webview message', e);
    }
  };

  // Insert image selected from AdminMediaPickerModal into Quill
  const handleSelectMediaImage = (image: MediaItem) => {
    if (webViewRef.current) {
      const script = `window.insertQuillImage(${JSON.stringify(image.file_url)}, ${JSON.stringify(image.original_name || '')}); true;`;
      webViewRef.current.injectJavaScript(script);
    }
    // Also update currentHtml fallback
    setMediaPickerVisible(false);
  };

  // Done / Apply
  const handleApply = () => {
    onApply(currentHtml);
    onClose();
  };

  // Quill HTML Template with Snow Theme & Full Web Admin Toolbars
  const quillHtmlTemplate = `
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <!-- Quill Snow Theme matching web admin portal -->
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
    }
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      background: #ffffff;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }
    #editor-wrapper {
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    /* Sticky Top Toolbar matching Web Admin Portal */
    .ql-toolbar.ql-snow {
      position: sticky;
      top: 0;
      background: #f8fafc;
      z-index: 1000;
      border-top: none !important;
      border-left: none !important;
      border-right: none !important;
      border-bottom: 1px solid #e2e8f0 !important;
      padding: 8px 6px !important;
      display: flex;
      flex-wrap: wrap;
      gap: 2px;
    }
    .ql-toolbar.ql-snow .ql-formats {
      margin-right: 6px !important;
      margin-bottom: 4px !important;
    }
    .ql-snow .ql-picker {
      font-size: 13px !important;
    }
    .ql-container.ql-snow {
      flex: 1;
      border: none !important;
      font-size: 16px;
    }
    .ql-editor {
      padding: 16px !important;
      min-height: 100%;
      font-size: 16px !important;
      line-height: 1.7 !important;
      color: #1e293b;
    }
    .ql-editor.ql-blank::before {
      color: #94a3b8 !important;
      font-style: normal !important;
      font-size: 15px !important;
      left: 16px !important;
    }
    .ql-editor img {
      max-width: 100%;
      border-radius: 8px;
      margin: 8px 0;
      display: block;
    }
    .ql-editor iframe {
      width: 100%;
      min-height: 220px;
      border-radius: 8px;
      margin: 8px 0;
    }
    .ql-editor blockquote {
      border-left: 4px solid #dc2626 !important;
      padding-left: 14px !important;
      margin: 12px 0 !important;
      color: #475569 !important;
      font-style: italic !important;
      background: #fef2f2 !important;
      padding-top: 6px !important;
      padding-bottom: 6px !important;
    }
    .ql-editor pre.ql-syntax {
      background: #0f172a !important;
      color: #38bdf8 !important;
      border-radius: 8px !important;
      padding: 12px !important;
    }
  </style>
</head>
<body>
  <div id="editor-wrapper">
    <div id="editor"></div>
  </div>

  <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
  <script>
    var quill = new Quill('#editor', {
      theme: 'snow',
      placeholder: 'Write your story, report, or article body here...',
      modules: {
        toolbar: {
          container: [
            [{ 'font': [] }, { 'size': ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'script': 'sub' }, { 'script': 'super' }],
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'indent': '-1' }, { 'indent': '+1' }],
            [{ 'align': [] }],
            ['blockquote', 'code-block'],
            ['link', 'image', 'video'],
            ['clean']
          ],
          handlers: {
            image: function () {
              if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'open_media_picker' }));
              }
            }
          }
        }
      }
    });

    // Populate existing HTML content perfectly in rich mode
    var initialHtml = ${JSON.stringify(initialContent || '')};
    if (initialHtml) {
      quill.root.innerHTML = initialHtml;
    }

    // Notify React Native on load
    if (window.ReactNativeWebView) {
      window.ReactNativeWebView.postMessage(JSON.stringify({ type: 'ready' }));
    }

    // Function called by React Native when a media image is selected
    window.insertQuillImage = function(url, alt) {
      var range = quill.getSelection(true);
      var index = range ? range.index : quill.getLength();
      quill.insertEmbed(index, 'image', url);
      quill.setSelection(index + 1);
    };

    // Send content changes to React Native
    quill.on('text-change', function () {
      if (window.ReactNativeWebView) {
        var html = quill.root.innerHTML;
        var text = quill.getText().trim();
        var words = text ? text.split(/\\s+/).length : 0;
        var chars = text.length;
        window.ReactNativeWebView.postMessage(JSON.stringify({
          type: 'change',
          html: html,
          words: words,
          chars: chars
        }));
      }
    });
  </script>
</body>
</html>
  `;

  return (
    <Modal visible={visible} animationType="slide" transparent={false} onRequestClose={onClose}>
      <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]}>
        <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />

        {/* Top Header */}
        <View
          style={[
            styles.header,
            { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <TouchableOpacity style={styles.headerBtn} onPress={onClose}>
            <ArrowLeft size={18} color={theme.text} strokeWidth={2.4} />
          </TouchableOpacity>

          <View style={styles.headerTitleGroup}>
            <Text style={[styles.headerTitle, { color: theme.text }]}>Rich Editor</Text>
            <Text style={[styles.counterBadge, { color: theme.textSecondary }]}>
              {wordsCount} W · {charsCount} C
            </Text>
          </View>

          {/* Mode Switcher: RTF Editor | HTML Code | Preview */}
          <View style={[styles.modeSwitch, { backgroundColor: theme.backgroundSelected }]}>
            <TouchableOpacity
              style={[styles.modeTab, activeScreenTab === 'rtf' && styles.modeTabActive]}
              onPress={() => setActiveScreenTab('rtf')}>
              <FileEdit size={11} color={activeScreenTab === 'rtf' ? '#fff' : theme.textSecondary} />
              <Text
                style={[
                  styles.modeTabText,
                  activeScreenTab === 'rtf' ? { color: '#fff' } : { color: theme.textSecondary },
                ]}>
                RTF
              </Text>
            </TouchableOpacity>

            {/* <TouchableOpacity
              style={[styles.modeTab, activeScreenTab === 'html' && styles.modeTabActive]}
              onPress={() => setActiveScreenTab('html')}>
              <Code size={11} color={activeScreenTab === 'html' ? '#fff' : theme.textSecondary} />
              <Text
                style={[
                  styles.modeTabText,
                  activeScreenTab === 'html' ? { color: '#fff' } : { color: theme.textSecondary },
                ]}>
                HTML
              </Text>
            </TouchableOpacity> */}

            <TouchableOpacity
              style={[styles.modeTab, activeScreenTab === 'preview' && styles.modeTabActive]}
              onPress={() => setActiveScreenTab('preview')}>
              <Eye size={11} color={activeScreenTab === 'preview' ? '#fff' : theme.textSecondary} />
              <Text
                style={[
                  styles.modeTabText,
                  activeScreenTab === 'preview' ? { color: '#fff' } : { color: theme.textSecondary },
                ]}>
                Preview
              </Text>
            </TouchableOpacity>
          </View>

          <TouchableOpacity style={styles.applyBtn} onPress={handleApply}>
            <Check size={14} color="#fff" strokeWidth={2.5} style={{ marginRight: 4 }} />
            <Text style={styles.applyBtnText}>Done</Text>
          </TouchableOpacity>
        </View>

        {/* 1. RTF (Rich Text Format) WYSIWYG Mode - NO raw HTML tags, full visual editor matching Web Portal */}
        <View style={[styles.editorView, activeScreenTab !== 'rtf' && styles.hiddenView]}>
          <WebView
            ref={webViewRef}
            source={{ html: quillHtmlTemplate }}
            originWhitelist={['*']}
            javaScriptEnabled={true}
            domStorageEnabled={true}
            onMessage={handleWebViewMessage}
            style={styles.webView}
            scrollEnabled={true}
            keyboardDisplayRequiresUserAction={false}
            hideKeyboardAccessoryView={false}
          />
        </View>

        {/* 2. HTML Raw Code Viewer/Editor */}
        {activeScreenTab === 'html' && (
          <View style={[styles.editorContainer, { backgroundColor: '#0f172a' }]}>
            <View style={styles.codeBanner}>
              <Text style={styles.codeBannerText}>Raw HTML Source (Saved to Database)</Text>
            </View>
            <TextInput
              style={styles.codeTextInput}
              multiline
              value={currentHtml}
              onChangeText={(text) => {
                setCurrentHtml(text);
                calculateStats(text);
              }}
              placeholder="<!-- HTML code -->"
              placeholderTextColor="#64748b"
              textAlignVertical="top"
              autoCapitalize="none"
              autoCorrect={false}
            />
          </View>
        )}

        {/* 3. Live Preview Mode */}
        {activeScreenTab === 'preview' && (
          <View style={styles.previewContainer}>
            <WebView
              source={{
                html: `
                  <!DOCTYPE html>
                  <html>
                  <head>
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                      body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 16px; line-height: 1.7; color: #1e293b; }
                      img { max-width: 100%; border-radius: 8px; margin: 10px 0; }
                      iframe { width: 100%; min-height: 220px; border-radius: 8px; }
                      blockquote { border-left: 4px solid #dc2626; padding-left: 12px; margin: 12px 0; color: #475569; font-style: italic; background: #fef2f2; padding-top: 6px; padding-bottom: 6px; }
                      pre { background: #0f172a; color: #38bdf8; padding: 12px; border-radius: 8px; overflow-x: auto; }
                    </style>
                  </head>
                  <body>
                    ${currentHtml || '<p style="color: #94a3b8; font-style: italic;">No article content</p>'}
                  </body>
                  </html>
                `,
              }}
              originWhitelist={['*']}
              style={styles.webView}
            />
          </View>
        )}

        {/* Media Picker Modal (Integrated with Quill image insertion) */}
        <AdminMediaPickerModal
          visible={mediaPickerVisible}
          portalUrl={portalUrl}
          title="Insert Photo into Article"
          onClose={() => setMediaPickerVisible(false)}
          onSelectImage={handleSelectMediaImage}
        />
      </SafeAreaView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.four,
    paddingVertical: 10,
    borderBottomWidth: 1,
  },
  headerBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerTitleGroup: {
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 15,
    fontWeight: '800',
  },
  counterBadge: {
    fontSize: 10,
    marginTop: 1,
  },
  modeSwitch: {
    flexDirection: 'row',
    padding: 2,
    borderRadius: 8,
  },
  modeTab: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 7,
    paddingVertical: 4,
    borderRadius: 6,
    gap: 3,
  },
  modeTabActive: {
    backgroundColor: '#dc2626',
  },
  modeTabText: {
    fontSize: 10,
    fontWeight: '700',
  },
  applyBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#16a34a',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
  },
  applyBtnText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
  editorView: {
    flex: 1,
  },
  hiddenView: {
    display: 'none',
  },
  webView: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  editorContainer: {
    flex: 1,
  },
  codeBanner: {
    backgroundColor: '#1e293b',
    paddingHorizontal: 14,
    paddingVertical: 6,
  },
  codeBannerText: {
    color: '#94a3b8',
    fontSize: 11,
    fontFamily: 'monospace',
    fontWeight: '700',
  },
  codeTextInput: {
    flex: 1,
    padding: Spacing.four,
    fontSize: 13,
    lineHeight: 20,
    color: '#38bdf8',
    fontFamily: 'monospace',
  },
  previewContainer: {
    flex: 1,
  },
});
