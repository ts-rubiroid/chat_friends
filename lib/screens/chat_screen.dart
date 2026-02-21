import 'dart:async';
import 'dart:io';
import 'package:chat_friends/utils/local_unread_helper.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'package:path_provider/path_provider.dart';
import 'package:record/record.dart';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/message.dart';
import 'package:chat_friends/models/user.dart';
import 'package:chat_friends/screens/image_viewer_screen.dart';
import 'package:chat_friends/screens/video_viewer_screen.dart';
import 'package:chat_friends/services/download_service.dart';
import 'package:chat_friends/services/notification_service.dart';
import 'package:chat_friends/widgets/audio_player_bubble.dart';
import 'package:chat_friends/widgets/message_bubble.dart';




class ChatScreen extends StatefulWidget {
  final Chat chat;

  ChatScreen({required this.chat});

  @override
  _ChatScreenState createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final _messageController = TextEditingController();
  final _focusNode = FocusNode();
  List<Message> _messages = [];
  bool _isLoading = true;
  _PendingAttachment? _pendingAttachment;
  bool _isSending = false;
  User? _currentUser;
  int _lastKnownMessageCount = 0;

  // Голосовые сообщения
  final AudioRecorder _audioRecorder = AudioRecorder();
  bool _isRecordingVoice = false;
  int _recordSeconds = 0;
  Timer? _recordTimer;

  @override
  void initState() {
    super.initState();
    NotificationService.currentChatId = widget.chat.id;
    _loadInitialData();
    _startPolling();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        _focusNode.requestFocus();
        _markChatAsRead();
      }
    });
  }

  @override
  void dispose() {
    _recordTimer?.cancel();
    if (_isRecordingVoice) {
      _audioRecorder.stop().catchError((_) => null);
    }
    _audioRecorder.dispose();
    NotificationService.currentChatId = null;
    _markChatAsRead();
    _messageController.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    try {
      final currentUser = await ApiService.getCurrentUser();
      final messages = await ApiService.getMessages(widget.chat.id);
      
      print('[DEBUG] === ЗАГРУЖЕНО СООБЩЕНИЙ: ${messages.length} ===');
      for (var i = 0; i < messages.length; i++) {
        final msg = messages[i];
        if (msg.hasImage || msg.hasFile) {
          print('[DEBUG] Сообщение #${i + 1} (ID: ${msg.id}):');
          print('[DEBUG]   Тип: ${msg.type}');
          print('[DEBUG]   imageUrl: "${msg.imageUrl}"');
          print('[DEBUG]   fileUrl: "${msg.fileUrl}"');
          print('[DEBUG]   fileName: "${msg.fileName}"');
          print('[DEBUG]   fileSize: "${msg.formattedFileSize}"');
        }
      }

      setState(() {
        _currentUser = currentUser;
        _messages = messages;
        _isLoading = false;
        _lastKnownMessageCount = messages.length;
      });
    } catch (e) {
      print('Ошибка загрузки данных: $e');
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _markChatAsRead() async {
    try {
      final chatId = widget.chat.id;
      
      String lastText = 'NO_MESSAGES';
      int messageCount = 0;
      
      if (_messages.isNotEmpty) {
        final lastMessage = _messages.last;
        lastText = lastMessage.text ?? '[МЕДИА]';
        messageCount = _messages.length;
      } else if (widget.chat.lastMessage != null) {
        lastText = widget.chat.lastMessage!.text ?? '[МЕДИА]';
        messageCount = 1;
      }
      
      await LocalUnreadHelper.saveChatState(
        chatId: chatId,
        lastText: lastText,
        messageCount: messageCount,
      );
      
    } catch (e) {
      print('[ChatScreen] Ошибка сохранения состояния: $e');
    }
  }

  Future<void> _loadMessages() async {
    try {
      final messages = await ApiService.getMessages(widget.chat.id);

      if (mounted && messages.length > _lastKnownMessageCount && _currentUser != null) {
        Message? lastNewFromOther;
        for (var i = _lastKnownMessageCount; i < messages.length; i++) {
          if (messages[i].senderId != _currentUser!.id) {
            lastNewFromOther = messages[i];
          }
        }
        if (lastNewFromOther != null) {
          print('[Notify] ChatScreen: новое сообщение от другого в чате ${widget.chat.id}, показываем уведомление');
          await NotificationService.showNewMessageNotification(
            chat: widget.chat,
            message: lastNewFromOther,
          );
        }
      }

      if (mounted) {
        setState(() {
          _messages = messages;
          _lastKnownMessageCount = messages.length;
        });
        _markChatAsRead();
      }
    } catch (e) {
      print('Ошибка обновления сообщений: $e');
    }
  }

  void _startPolling() {
    Future.delayed(Duration(seconds: 5), () {
      if (mounted) {
        _loadMessages();
        _startPolling();
      }
    });
  }

  String _getExtension(String pathOrName) {
    final idx = pathOrName.lastIndexOf('.');
    if (idx == -1) return '';
    return pathOrName.substring(idx + 1).toLowerCase();
  }

  bool _isVideoByNameOrMime({required String nameOrPath, String? mime}) {
    final ext = _getExtension(nameOrPath);
    final m = (mime ?? '').toLowerCase();
    if (m.startsWith('video/')) return true;
    return ['mp4', 'mov', 'webm', 'mkv', 'avi'].contains(ext);
  }

  bool _isAudioByNameOrMime({required String nameOrPath, String? mime}) {
    final ext = _getExtension(nameOrPath);
    final m = (mime ?? '').toLowerCase();
    if (m.startsWith('audio/')) return true;
    return ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'flac', 'opus'].contains(ext);
  }

  /// Обработка долгого нажатия на сообщение.
  /// Для своих сообщений показывает меню с действием «Удалить».
  void _onMessageLongPress(Message message) async {
    if (_currentUser == null || message.senderId != _currentUser!.id) {
      // Пока разрешаем удалять только свои сообщения.
      return;
    }

    final action = await showModalBottomSheet<String>(
      context: context,
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              ListTile(
                leading: Icon(Icons.delete, color: Colors.red),
                title: Text('Удалить сообщение'),
                onTap: () => Navigator.pop(context, 'delete'),
              ),
              ListTile(
                leading: Icon(Icons.close),
                title: Text('Отмена'),
                onTap: () => Navigator.pop(context, 'cancel'),
              ),
            ],
          ),
        );
      },
    );

    if (action == 'delete') {
      await _confirmAndDeleteMessage(message);
    }
  }

  Future<void> _confirmAndDeleteMessage(Message message) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text('Удалить сообщение?'),
          content: Text('Это действие удалит сообщение для всех участников чата.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text('Отмена'),
            ),
            TextButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(
                'Удалить',
                style: TextStyle(color: Colors.red),
              ),
            ),
          ],
        );
      },
    );

    if (confirmed != true) return;

    final success = await ApiService.deleteMessage(message.id);
    if (!mounted) return;

    if (success) {
      setState(() {
        _messages.removeWhere((m) => m.id == message.id);
        _lastKnownMessageCount = _messages.length;
      });
      _markChatAsRead();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Сообщение удалено')),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Не удалось удалить сообщение')),
      );
    }
  }

  Future<void> _pickPhotoFromGallery() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);

    if (pickedFile != null) {
      setState(() {
        _pendingAttachment = _PendingAttachment(
          type: _PendingAttachmentType.image,
          file: File(pickedFile.path),
        );
      });
      _focusNode.requestFocus();
    }
  }

  Future<void> _takePhotoWithCamera() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.camera);

    if (pickedFile != null) {
      setState(() {
        _pendingAttachment = _PendingAttachment(
          type: _PendingAttachmentType.image,
          file: File(pickedFile.path),
        );
      });
      _focusNode.requestFocus();
    }
  }

  Future<void> _pickVideoFromGallery() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickVideo(source: ImageSource.gallery);

    if (pickedFile != null) {
      setState(() {
        _pendingAttachment = _PendingAttachment(
          type: _PendingAttachmentType.video,
          file: File(pickedFile.path),
        );
      });
      _focusNode.requestFocus();
    }
  }

  Future<void> _pickAudioFile() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'flac', 'opus'],
    );

    if (result != null && result.files.single.path != null) {
      setState(() {
        _pendingAttachment = _PendingAttachment(
          type: _PendingAttachmentType.audio,
          file: File(result.files.single.path!),
        );
      });
      _focusNode.requestFocus();
    }
  }

  Future<void> _pickAnyFile() async {
    final result = await FilePicker.platform.pickFiles();

    if (result != null && result.files.single.path != null) {
      final f = result.files.single;
      final nameOrPath = f.name.isNotEmpty ? f.name : f.path!;
      final pickedType = _isVideoByNameOrMime(nameOrPath: nameOrPath, mime: null)
          ? _PendingAttachmentType.video
          : _isAudioByNameOrMime(nameOrPath: nameOrPath, mime: null)
              ? _PendingAttachmentType.audio
              : _PendingAttachmentType.file;

      setState(() {
        _pendingAttachment = _PendingAttachment(type: pickedType, file: File(f.path!));
      });
      _focusNode.requestFocus();
    }
  }

  Future<void> _startVoiceRecord() async {
    final hasPermission = await _audioRecorder.hasPermission();
    if (!hasPermission) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Нет доступа к микрофону. Разрешите запись в настройках.')),
      );
      return;
    }
    try {
      final dir = await getTemporaryDirectory();
      final path = '${dir.path}/voice_${DateTime.now().millisecondsSinceEpoch}.m4a';
      await _audioRecorder.start(const RecordConfig(encoder: AudioEncoder.aacLc), path: path);
      if (!mounted) return;
      setState(() {
        _isRecordingVoice = true;
        _recordSeconds = 0;
      });
      _recordTimer = Timer.periodic(const Duration(seconds: 1), (t) {
        if (!mounted) return;
        setState(() => _recordSeconds = t.tick);
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Ошибка записи: $e')),
      );
    }
  }

  Future<void> _stopVoiceRecord() async {
    if (!_isRecordingVoice) return;
    _recordTimer?.cancel();
    _recordTimer = null;
    try {
      final path = await _audioRecorder.stop();
      if (!mounted) return;
      if (path == null || path.isEmpty) {
        setState(() {
          _isRecordingVoice = false;
          _recordSeconds = 0;
        });
        return;
      }
      setState(() {
        _isRecordingVoice = false;
        _recordSeconds = 0;
        _pendingAttachment = _PendingAttachment(
          type: _PendingAttachmentType.audio,
          file: File(path),
        );
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _isRecordingVoice = false;
        _recordSeconds = 0;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Ошибка остановки записи: $e')),
      );
    }
  }

  void _showAttachmentPickerSheet() {
    showModalBottomSheet(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: Icon(Icons.photo_library, color: Colors.blue),
              title: Text('Выбрать фото'),
              subtitle: Text('Из галереи'),
              onTap: () async {
                Navigator.pop(context);
                await _pickPhotoFromGallery();
              },
            ),
            ListTile(
              leading: Icon(Icons.photo_camera, color: Colors.green),
              title: Text('Сделать фото'),
              subtitle: Text('Камера'),
              onTap: () async {
                Navigator.pop(context);
                await _takePhotoWithCamera();
              },
            ),
            ListTile(
              leading: Icon(Icons.videocam, color: Colors.deepPurple),
              title: Text('Выбрать видео'),
              subtitle: Text('Из галереи'),
              onTap: () async {
                Navigator.pop(context);
                await _pickVideoFromGallery();
              },
            ),
            ListTile(
              leading: Icon(Icons.mic, color: Colors.red),
              title: Text('Голосовое сообщение'),
              subtitle: Text('Записать с микрофона'),
              onTap: () async {
                Navigator.pop(context);
                await _startVoiceRecord();
              },
            ),
            ListTile(
              leading: Icon(Icons.audiotrack, color: Colors.orange),
              title: Text('Выбрать аудио'),
              subtitle: Text('MP3, M4A, WAV и др.'),
              onTap: () async {
                Navigator.pop(context);
                await _pickAudioFile();
              },
            ),
            ListTile(
              leading: Icon(Icons.attach_file, color: Colors.grey[700]),
              title: Text('Выбрать файл'),
              subtitle: Text('Любой тип'),
              onTap: () async {
                Navigator.pop(context);
                await _pickAnyFile();
              },
            ),
            SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  Future<void> _sendMessage() async {
    if (_messageController.text.isEmpty && _pendingAttachment == null) {
      return;
    }

    if (_isSending) return;

    final text = _messageController.text;
    
    setState(() {
      _isSending = true;
    });

    try {
      Message message;
      
      if (_pendingAttachment != null && _pendingAttachment!.type == _PendingAttachmentType.image) {
        message = await ApiService.sendMessageWithFile(
          widget.chat.id,
          text.isNotEmpty ? text : 'Изображение',
          _pendingAttachment!.file,
          'image',
        );
      } else if (_pendingAttachment != null) {
        final attachmentType = _pendingAttachment!.type;
        final defaultText = attachmentType == _PendingAttachmentType.video
            ? 'Видео'
            : attachmentType == _PendingAttachmentType.audio
                ? 'Аудио'
                : 'Файл';

        message = await ApiService.sendMessageWithFile(
          widget.chat.id,
          text.isNotEmpty ? text : defaultText,
          _pendingAttachment!.file,
          // ВАЖНО: на бэкенде сейчас есть только поля image/file,
          // поэтому видео/аудио отправляем как "file" и различаем по MIME/расширению на клиенте.
          'file',
        );
      } else {
        message = await ApiService.sendTextMessage(
          widget.chat.id,
          text,
        );
      }

      _messageController.clear();
      
      if (mounted) {
        setState(() {
          _messages.add(message);
          _pendingAttachment = null;
          _isSending = false;
        });
        _markChatAsRead();
      }
      
      _focusNode.requestFocus();
      
    } catch (e) {
      print('Ошибка отправки: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Ошибка отправки: $e')),
        );
        setState(() {
          _isSending = false;
        });
      }
    }
  }

  // Получение иконки для типа файла
  Widget _getFileIcon(Message message) {
    final fileType = message.fileType?.toLowerCase() ?? '';
    final fileName = message.displayFileName.toLowerCase();
    
    if (fileType.startsWith('video/') ||
        fileName.endsWith('.mp4') ||
        fileName.endsWith('.mov') ||
        fileName.endsWith('.webm') ||
        fileName.endsWith('.mkv') ||
        fileName.endsWith('.avi')) {
      return Icon(Icons.play_circle_filled, size: 36, color: Colors.deepPurple);
    }
    if (fileType.startsWith('audio/') ||
        fileName.endsWith('.mp3') ||
        fileName.endsWith('.m4a') ||
        fileName.endsWith('.aac') ||
        fileName.endsWith('.wav') ||
        fileName.endsWith('.ogg') ||
        fileName.endsWith('.flac') ||
        fileName.endsWith('.opus')) {
      return Icon(Icons.audiotrack, size: 36, color: Colors.orange);
    }
    if (fileType.contains('image') || 
        fileName.endsWith('.jpg') || 
        fileName.endsWith('.jpeg') || 
        fileName.endsWith('.png') || 
        fileName.endsWith('.gif')) {
      return Icon(Icons.image, size: 36, color: Colors.blue);
    } else if (fileType.contains('pdf') || fileName.endsWith('.pdf')) {
      return Icon(Icons.picture_as_pdf, size: 36, color: Colors.red);
    } else if (fileType.contains('word') || fileName.endsWith('.doc') || fileName.endsWith('.docx')) {
      return Icon(Icons.description, size: 36, color: Colors.blue[700]);
    } else if (fileType.contains('excel') || fileName.endsWith('.xls') || fileName.endsWith('.xlsx')) {
      return Icon(Icons.table_chart, size: 36, color: Colors.green);
    } else if (fileType.contains('text') || fileName.endsWith('.txt')) {
      return Icon(Icons.text_fields, size: 36, color: Colors.grey);
    } else {
      return Icon(Icons.insert_drive_file, size: 36, color: Colors.grey);
    }
  }

  // Обработка тапа по файлу
  Future<void> _handleFileTap(Message message) async {
    if (!message.hasFile || message.fileUrl.isEmpty) return;
    
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: Icon(Icons.download, color: Colors.blue),
              title: Text('Скачать файл'),
              subtitle: Text('Сохранить на устройство'),
              onTap: () async {
                Navigator.pop(context);
                await _downloadFile(message);
              },
            ),
            ListTile(
              leading: Icon(Icons.open_in_new, color: Colors.green),
              title: Text('Открыть файл'),
              subtitle: Text('Попробовать открыть в приложении'),
              onTap: () async {
                Navigator.pop(context);
                await _openFile(message);
              },
            ),
            ListTile(
              leading: Icon(Icons.share, color: Colors.orange),
              title: Text('Поделиться'),
              subtitle: Text('Поделиться файлом'),
              onTap: () async {
                Navigator.pop(context);
                await _shareFile(message);
              },
            ),
            SizedBox(height: 8),
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: Text('Отмена'),
            ),
          ],
        ),
      ),
    );
  }

  // Скачивание файла
  Future<void> _downloadFile(Message message) async {
    if (!message.hasFile || message.fileUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Нет URL файла для скачивания')),
      );
      return;
    }
    
    final scaffoldMessenger = ScaffoldMessenger.of(context);
    
    try {
      // Показываем начальное сообщение
      scaffoldMessenger.showSnackBar(
        SnackBar(
          content: Text('Начинаем скачивание "${message.displayFileName}"...'),
          duration: Duration(seconds: 2),
        ),
      );
      
      print('[ChatScreen] Скачивание файла: ${message.fileUrl}');
      
      // Скачиваем файл
      final file = await DownloadService.downloadFile(
        url: message.fileUrl,
        fileName: message.displayFileName,
        onProgress: (received, total) {
          if (total != -1) {
            final progress = (received / total * 100).toInt();
            print('[ChatScreen] Прогресс скачивания: $progress%');
          }
        },
      );
      
      if (file != null && await file.exists()) {
        // Успешно скачан
        scaffoldMessenger.showSnackBar(
          SnackBar(
            content: Text('✅ Файл "${message.displayFileName}" скачан'),
            duration: Duration(seconds: 3),
            action: SnackBarAction(
              label: 'Открыть',
              onPressed: () async {
                try {
                  await DownloadService.openFile(file.path);
                } catch (e) {
                  scaffoldMessenger.showSnackBar(
                    SnackBar(content: Text('Не удалось открыть файл: $e')),
                  );
                }
              },
            ),
          ),
        );
        
        print('[ChatScreen] ✅ Файл успешно скачан: ${file.path}');
      } else {
        throw Exception('Файл не был сохранен');
      }
    } catch (e) {
      print('[ChatScreen] ❌ Ошибка скачивания файла: $e');
      
      scaffoldMessenger.showSnackBar(
        SnackBar(
          content: Text('❌ Ошибка скачивания: ${e.toString()}'),
          duration: Duration(seconds: 4),
        ),
      );
    }
  }



  // Открытие файла
  Future<void> _openFile(Message message) async {
    try {
      if (!message.hasFile || message.fileUrl.isEmpty) return;

      final scaffoldMessenger = ScaffoldMessenger.of(context);
      scaffoldMessenger.showSnackBar(
        SnackBar(
          content: Text('Скачивание "${message.displayFileName}" для открытия...'),
          duration: Duration(seconds: 2),
        ),
      );

      final file = await DownloadService.downloadFile(
        url: message.fileUrl,
        fileName: message.displayFileName,
      );

      if (file != null && await file.exists()) {
        await DownloadService.openFile(file.path);
      } else {
        throw Exception('Файл не был сохранен');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Не удалось открыть файл: $e')),
      );
    }
  }

  // Поделиться файлом
  Future<void> _shareFile(Message message) async {
    try {
      print('Поделиться файлом: ${message.fileUrl}');
      // TODO: Реализовать шеринг файла
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Поделиться файлом: ${message.displayFileName}')),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Не удалось поделиться файлом: $e')),
      );
    }
  }


  String _basename(String path) {
    final parts = path.split(RegExp(r'[\/\\\\]'));
    return parts.isNotEmpty ? parts.last : path;
  }

  Widget _buildPendingAttachmentPreview(_PendingAttachment attachment) {
    final name = _basename(attachment.file.path);

    Widget preview;
    switch (attachment.type) {
      case _PendingAttachmentType.image:
        preview = ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: Image.file(
            attachment.file,
            width: 64,
            height: 64,
            fit: BoxFit.cover,
            errorBuilder: (context, error, stackTrace) => Container(
              width: 64,
              height: 64,
              color: Colors.grey.shade300,
              child: Icon(Icons.broken_image),
            ),
          ),
        );
        break;
      case _PendingAttachmentType.video:
        preview = GestureDetector(
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => VideoViewerScreen(
                  filePath: attachment.file.path,
                  title: name,
                ),
              ),
            );
          },
          child: Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: Colors.deepPurple.withOpacity(0.12),
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.deepPurple.withOpacity(0.25)),
            ),
            child: Center(
              child: Icon(Icons.play_circle_filled, color: Colors.deepPurple, size: 32),
            ),
          ),
        );
        break;
      case _PendingAttachmentType.audio:
        preview = Expanded(
          child: AudioPlayerBubble(
            title: name,
            filePath: attachment.file.path,
            dense: true,
          ),
        );
        break;
      case _PendingAttachmentType.file:
        preview = Container(
          width: 64,
          height: 64,
          decoration: BoxDecoration(
            color: Colors.grey.shade300,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(Icons.insert_drive_file, color: Colors.grey.shade800),
        );
        break;
    }

    return Row(
      children: [
        if (attachment.type != _PendingAttachmentType.audio) preview,
        if (attachment.type != _PendingAttachmentType.audio) SizedBox(width: 10),
        if (attachment.type != _PendingAttachmentType.audio)
          Expanded(
            child: Text(
              name,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        if (attachment.type == _PendingAttachmentType.audio) preview,
        IconButton(
          icon: Icon(Icons.close),
          onPressed: () {
            setState(() {
              _pendingAttachment = null;
            });
            _focusNode.requestFocus();
          },
        ),
      ],
    );
  }

  Widget _buildFileMessageBlock(Message message) {
    final fileName = message.displayFileName;
    final mime = message.fileType;
    final isVideo = _isVideoByNameOrMime(nameOrPath: fileName, mime: mime);
    final isAudio = _isAudioByNameOrMime(nameOrPath: fileName, mime: mime);

    if (isVideo) {
      return Container(
        margin: EdgeInsets.only(bottom: 8),
        child: GestureDetector(
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => VideoViewerScreen(
                  url: message.fileUrl,
                  title: fileName,
                ),
              ),
            );
          },
          child: Container(
            padding: EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.deepPurple.withOpacity(0.06),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.deepPurple.withOpacity(0.2)),
            ),
            child: Row(
              children: [
                Icon(Icons.play_circle_filled, size: 40, color: Colors.deepPurple),
                SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        fileName,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      SizedBox(height: 4),
                      Row(
                        children: [
                          Text(
                            'Видео',
                            style: TextStyle(fontSize: 12, color: Colors.deepPurple),
                          ),
                          if (message.formattedFileSize.isNotEmpty) ...[
                            Text(' • ', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                            Text(
                              message.formattedFileSize,
                              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.more_vert, color: Colors.grey.shade700),
                  onPressed: () async => _handleFileTap(message),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (isAudio) {
      return Container(
        margin: EdgeInsets.only(bottom: 8),
        child: AudioPlayerBubble(
          title: fileName,
          url: message.fileUrl,
          dense: false,
          trailing: IconButton(
            icon: Icon(Icons.more_vert, color: Colors.grey.shade700),
            onPressed: () async => _handleFileTap(message),
          ),
        ),
      );
    }

    // Fallback: обычный блок файлов (документы и т.д.)
    return Container(
      margin: EdgeInsets.only(bottom: 8),
      child: GestureDetector(
        onTap: () async {
          await _handleFileTap(message);
        },
        child: Container(
          padding: EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.grey.shade100,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: Row(
            children: [
              _getFileIcon(message),
              SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      fileName,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                    SizedBox(height: 4),
                    if (message.formattedFileSize.isNotEmpty)
                      Text(
                        message.formattedFileSize,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    if (message.fileType != null)
                      Text(
                        message.fileType!,
                        style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey.shade500,
                        ),
                      ),
                  ],
                ),
              ),
              IconButton(
                icon: Icon(Icons.download, color: Colors.blue),
                onPressed: () async {
                  await _downloadFile(message);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.chat.name),
      ),
      body: Column(
        children: [
          if (_isRecordingVoice)
            Container(
              padding: EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              color: Colors.red.shade50,
              child: Row(
                children: [
                  Icon(Icons.mic, color: Colors.red, size: 28),
                  SizedBox(width: 12),
                  Text(
                    'Запись ${_recordSeconds ~/ 60}:${(_recordSeconds % 60).toString().padLeft(2, '0')}',
                    style: TextStyle(fontWeight: FontWeight.w600, color: Colors.red.shade800),
                  ),
                  Spacer(),
                  TextButton.icon(
                    onPressed: _stopVoiceRecord,
                    icon: Icon(Icons.stop, color: Colors.white, size: 20),
                    label: Text('Стоп', style: TextStyle(color: Colors.white)),
                    style: TextButton.styleFrom(backgroundColor: Colors.red),
                  ),
                ],
              ),
            ),
          if (_pendingAttachment != null)
            Container(
              padding: EdgeInsets.all(10),
              color: Colors.grey.shade200,
              child: _buildPendingAttachmentPreview(_pendingAttachment!),
            ),
          
          Expanded(
            child: _isLoading
                ? Center(child: CircularProgressIndicator())
                : _messages.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.chat_bubble_outline, size: 64, color: Colors.grey),
                            SizedBox(height: 16),
                            Text(
                              'Нет сообщений',
                              style: TextStyle(fontSize: 18, color: Colors.grey),
                            ),
                            SizedBox(height: 8),
                            Text(
                              'Начните общение первым!',
                              style: TextStyle(color: Colors.grey),
                            ),
                          ],
                        ),
                      )
                      
                    : ListView.builder(
                        reverse: true,
                        itemCount: _messages.length,
                        itemBuilder: (context, index) {
                          final message = _messages[_messages.length - 1 - index];
                          
                          // Используем методы из модели Message
                          final hasImage = message.hasImage;
                          final hasFile = message.hasFile;
                          final imageUrl = message.imageUrl;
                          final fileUrl = message.fileUrl;
                          
                          final bool isMe = _currentUser != null && 
                                           message.senderId == _currentUser!.id;
                          
                          return GestureDetector(
                            onLongPress: () => _onMessageLongPress(message),
                            child: Container(
                              margin: EdgeInsets.symmetric(vertical: 4, horizontal: 8),
                              child: Row(
                                mainAxisAlignment: isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
                                children: [
                                  if (!isMe) ...[
                                    Container(
                                      width: 40,
                                      height: 40,
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        color: Colors.grey.shade200,
                                      ),
                                      child: Center(
                                        child: Text(
                                          '?',
                                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                                        ),
                                      ),
                                    ),
                                    SizedBox(width: 8),
                                  ],
                                  
                                  Flexible(
                                    child: MessageBubble(
                                      isFromMe: isMe,
                                      color: isMe
                                          ? const Color(0xFF4F8BFF)
                                          : const Color(0xFF222632),
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          // БЛОК ДЛЯ ИЗОБРАЖЕНИЙ
                                          if (hasImage && imageUrl.isNotEmpty)
                                            Container(
                                              margin: EdgeInsets.only(bottom: 8),
                                              child: GestureDetector(
                                                onTap: () {
                                                  // Открываем полноэкранный просмотр
                                                  Navigator.push(
                                                    context,
                                                    MaterialPageRoute(
                                                      builder: (context) => ImageViewerScreen(
                                                        imageUrl: imageUrl,
                                                        heroTag: 'image_${message.id}',
                                                        fileName: message.fileName ?? 'image_${message.id}.jpg',
                                                      ),
                                                    ),
                                                  );
                                                },
                                                child: Hero(
                                                  tag: 'image_${message.id}',
                                                  child: ClipRRect(
                                                    borderRadius: BorderRadius.circular(12),
                                                    child: Stack(
                                                      children: [
                                                        // Изображение
                                                        Container(
                                                          width: 250,
                                                          height: 180,
                                                          child: Image.network(
                                                            imageUrl,
                                                            fit: BoxFit.cover,
                                                            loadingBuilder: (context, child, loadingProgress) {
                                                              if (loadingProgress == null) return child;
                                                              return Container(
                                                                color: Colors.grey.shade200,
                                                                child: Center(
                                                                  child: CircularProgressIndicator(
                                                                    value: loadingProgress.expectedTotalBytes != null
                                                                        ? loadingProgress.cumulativeBytesLoaded /
                                                                            loadingProgress.expectedTotalBytes!
                                                                        : null,
                                                                  ),
                                                                ),
                                                              );
                                                            },
                                                            errorBuilder: (context, error, stackTrace) {
                                                              return Container(
                                                                width: 250,
                                                                height: 180,
                                                                color: Colors.grey.shade200,
                                                                child: Center(
                                                                  child: Column(
                                                                    mainAxisAlignment: MainAxisAlignment.center,
                                                                    children: [
                                                                      Icon(Icons.broken_image, size: 40),
                                                                      SizedBox(height: 8),
                                                                      Text('Ошибка загрузки'),
                                                                    ],
                                                                  ),
                                                                ),
                                                              );
                                                            },
                                                          ),
                                                        ),
                                                        // Информация поверх изображения
                                                        Positioned(
                                                          bottom: 0,
                                                          left: 0,
                                                          right: 0,
                                                          child: Container(
                                                            padding: EdgeInsets.all(8),
                                                            decoration: BoxDecoration(
                                                              gradient: LinearGradient(
                                                                begin: Alignment.bottomCenter,
                                                                end: Alignment.topCenter,
                                                                colors: [Colors.black54, Colors.transparent],
                                                              ),
                                                            ),
                                                            child: Row(
                                                              children: [
                                                                Icon(Icons.zoom_in, color: Colors.white, size: 16),
                                                                SizedBox(width: 4),
                                                                Text(
                                                                  'Нажмите для увеличения',
                                                                  style: TextStyle(
                                                                    color: Colors.white,
                                                                    fontSize: 12,
                                                                  ),
                                                                ),
                                                              ],
                                                            ),
                                                          ),
                                                        ),
                                                      ],
                                                    ),
                                                  ),
                                                ),
                                              ),
                                            ),
                                          
                                          // БЛОК ДЛЯ ФАЙЛОВ
                                          if (hasFile && fileUrl.isNotEmpty)
                                            _buildFileMessageBlock(message),
                                          
                                          // ТЕКСТ СООБЩЕНИЯ
                                          if (message.text?.isNotEmpty == true)
                                            Text(
                                              message.text!,
                                              style: TextStyle(fontSize: 16),
                                            ),
                                          
                                          // ВРЕМЯ СООБЩЕНИЯ
                                          SizedBox(height: 4),
                                          Text(
                                            message.formattedTime,
                                            style: TextStyle(
                                              fontSize: 12,
                                              color: isMe
                                                  ? Colors.white.withOpacity(0.85)
                                                  : Colors.white54,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                  
                                  if (isMe) SizedBox(width: 8),
                                ],
                              ),
                            ),
                          );
                        },
                      ),          
      
          ),
          
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: Row(
              children: [
                IconButton(
                  icon: Icon(Icons.add, color: Colors.white70),
                  onPressed: _showAttachmentPickerSheet,
                  tooltip: 'Вложение',
                ),
                
                Expanded(
                  child: TextField(
                    controller: _messageController,
                    focusNode: _focusNode,
                    decoration: InputDecoration(
                      hintText: 'Message',
                      filled: true,
                      fillColor: const Color(0xFF222632),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(24),
                        borderSide: BorderSide.none,
                      ),
                      contentPadding:
                          const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                      hintStyle: const TextStyle(color: Colors.white38),
                    ),
                    textInputAction: TextInputAction.send,
                    onSubmitted: (_) => _sendMessage(),
                  ),
                ),
                
                _isSending
                    ? Padding(
                        padding: const EdgeInsets.all(8.0),
                        child: SizedBox(
                          width: 24,
                          height: 24,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : IconButton(
                        icon: Icon(Icons.send, color: Color(0xFF4F8BFF)),
                        onPressed: _sendMessage,
                        tooltip: 'Отправить',
                      ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

enum _PendingAttachmentType { image, video, audio, file }

class _PendingAttachment {
  final _PendingAttachmentType type;
  final File file;

  _PendingAttachment({required this.type, required this.file});
}