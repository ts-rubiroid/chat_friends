import 'package:chat_friends/utils/local_unread_helper.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/message.dart';
import 'package:chat_friends/models/user.dart';
import '../utils/api.dart';
import 'package:chat_friends/screens/image_viewer_screen.dart';
import 'package:chat_friends/services/download_service.dart';
import 'package:image_gallery_saver_plus/image_gallery_saver_plus.dart';




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
  File? _image;
  File? _file;
  bool _isSending = false;
  User? _currentUser;

  @override
  void initState() {
    super.initState();
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
      
      print('[ChatScreen] Загружено ${messages.length} сообщений');
      for (var msg in messages) {
        if (msg.hasImage) {
          print('[ChatScreen] ✅ Сообщение ${msg.id}: ЕСТЬ изображение: ${msg.imageUrl}');
        }
        if (msg.hasFile) {
          print('[ChatScreen] ✅ Сообщение ${msg.id}: ЕСТЬ файл: ${msg.fileUrl}');
        }
      }
      
      if (mounted) {
        setState(() {
          _messages = messages;
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

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final pickedFile = await picker.pickImage(source: ImageSource.gallery);
    
    if (pickedFile != null) {
      setState(() {
        _image = File(pickedFile.path);
        _file = null;
      });
      _focusNode.requestFocus();
    }
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles();
    
    if (result != null) {
      setState(() {
        _file = File(result.files.single.path!);
        _image = null;
      });
      _focusNode.requestFocus();
    }
  }

  Future<void> _sendMessage() async {
    if (_messageController.text.isEmpty && _image == null && _file == null) {
      return;
    }

    if (_isSending) return;

    final text = _messageController.text;
    
    setState(() {
      _isSending = true;
    });

    try {
      Message message;
      
      if (_image != null) {
        message = await ApiService.sendMessageWithFile(
          widget.chat.id,
          text.isNotEmpty ? text : 'Изображение',
          _image!,
          'image',
        );
      } else if (_file != null) {
        message = await ApiService.sendMessageWithFile(
          widget.chat.id,
          text.isNotEmpty ? text : 'Файл',
          _file!,
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
          _image = null;
          _file = null;
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
      print('Открытие файла: ${message.fileUrl}');
      // TODO: Реализовать открытие файла через open_filex
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Открытие файла: ${message.displayFileName}')),
      );
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


  // Скачивание изображения (для полноэкранного просмотра)
  Future<void> _downloadImage(String imageUrl, String fileName) async {
    final scaffoldMessenger = ScaffoldMessenger.of(context);
    
    try {
      scaffoldMessenger.showSnackBar(
        SnackBar(
          content: Text('Скачивание изображения...'),
          duration: Duration(seconds: 2),
        ),
      );
      
      print('[ChatScreen] Скачивание изображения: $imageUrl');
      
      // Сохраняем в галерею
      final success = await DownloadService.downloadImageToGallery(imageUrl);
      
      if (success) {
        scaffoldMessenger.showSnackBar(
          SnackBar(
            content: Text('✅ Изображение сохранено в галерею'),
            duration: Duration(seconds: 3),
          ),
        );
        print('[ChatScreen] ✅ Изображение сохранено в галерею');
      } else {
        throw Exception('Не удалось сохранить изображение');
      }
    } catch (e) {
      print('[ChatScreen] ❌ Ошибка скачивания изображения: $e');
      
      scaffoldMessenger.showSnackBar(
        SnackBar(
          content: Text('❌ Ошибка сохранения: ${e.toString()}'),
          duration: Duration(seconds: 4),
        ),
      );
    }
  }



  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.chat.name),
      ),
      body: Column(
        children: [
          if (_image != null || _file != null)
            Container(
              padding: EdgeInsets.all(10),
              color: Colors.grey.shade200,
              child: Row(
                children: [
                  Icon(_image != null ? Icons.image : Icons.insert_drive_file),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      _image?.path.split('/').last ??
                      _file?.path.split('/').last ??
                      '',
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  IconButton(
                    icon: Icon(Icons.close),
                    onPressed: () {
                      setState(() {
                        _image = null;
                        _file = null;
                      });
                      _focusNode.requestFocus();
                    },
                  ),
                ],
              ),
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
                          
                          return Container(
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
                                  child: Container(
                                    padding: EdgeInsets.all(12),
                                    decoration: BoxDecoration(
                                      color: isMe ? Colors.blue[100] : Colors.grey.shade200,
                                      borderRadius: BorderRadius.circular(16),
                                    ),
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
                                          Container(
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
                                                    // Иконка в зависимости от типа файла
                                                    _getFileIcon(message),
                                                    SizedBox(width: 12),
                                                    Expanded(
                                                      child: Column(
                                                        crossAxisAlignment: CrossAxisAlignment.start,
                                                        children: [
                                                          Text(
                                                            message.displayFileName,
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
                                          ),
                                        
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
                                            color: Colors.grey.shade600,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                                
                                if (isMe) SizedBox(width: 8),
                              ],
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
                  icon: Icon(Icons.image),
                  onPressed: _pickImage,
                  tooltip: 'Фото или галерея',
                ),
                
                IconButton(
                  icon: Icon(Icons.attach_file),
                  onPressed: _pickFile,
                  tooltip: 'Прикрепить файл',
                ),
                
                Expanded(
                  child: TextField(
                    controller: _messageController,
                    focusNode: _focusNode,
                    decoration: InputDecoration(
                      hintText: 'Сообщение...',
                      border: OutlineInputBorder(),
                      contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 8),
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
                        icon: Icon(Icons.send),
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