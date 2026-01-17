import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/message.dart';
import '../utils/api.dart';

class ChatScreen extends StatefulWidget {
  final Chat chat;

  ChatScreen({required this.chat});

  @override
  _ChatScreenState createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final _messageController = TextEditingController();
  List<Message> _messages = [];
  bool _isLoading = true;
  File? _image;
  File? _file;

  @override
  void initState() {
    super.initState();
    _loadMessages();
    _startPolling();
  }

  Future<void> _loadMessages() async {
    try {
      final messages = await ApiService.getMessages(widget.chat.id);
      setState(() {
        _messages = messages;
        _isLoading = false;
      });
    } catch (e) {
      print('Ошибка загрузки сообщений: $e');
      setState(() {
        _isLoading = false;
      });
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
    }
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles();
    
    if (result != null) {
      setState(() {
        _file = File(result.files.single.path!);
        _image = null;
      });
    }
  }

  void _sendMessage() async {
    if (_messageController.text.isEmpty && _image == null && _file == null) {
      return;
    }

    final text = _messageController.text;
    _messageController.clear();

    try {
      Message message;
      
      if (_image != null) {
        // Отправка изображения
        message = await ApiService.sendMessageWithFile(
          widget.chat.id,
          text.isNotEmpty ? text : 'Изображение',
          _image!,
          'image',
        );
      } else if (_file != null) {
        // Отправка файла
        message = await ApiService.sendMessageWithFile(
          widget.chat.id,
          text.isNotEmpty ? text : 'Файл',
          _file!,
          'file',
        );
      } else {
        // Отправка текста
        message = await ApiService.sendTextMessage(
          widget.chat.id,
          text,
        );
      }

      setState(() {
        _messages.add(message);
        _image = null;
        _file = null;
      });
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Ошибка отправки: $e')),
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
              color: Colors.grey[200],
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
                    },
                  ),
                ],
              ),
            ),
          Expanded(
            child: _isLoading
                ? Center(child: CircularProgressIndicator())
                : ListView.builder(
                    reverse: true,
                    itemCount: _messages.length,
                    itemBuilder: (context, index) {
                      final message = _messages[_messages.length - 1 - index];
                      
                      // Функция для получения URL изображения
                      String? getImageUrl() {
                        // Проверяем разные возможные поля
                        if (message.image != null && message.image!.isNotEmpty) {
                          return message.image!.startsWith('http') 
                            ? message.image
                            : '${ApiConfig.uploadsUrl}/${message.image}';
                        }
                        return null;
                      }
                      
                      // Функция для получения URL файла
                      String? getFileUrl() {
                        if (message.file != null && message.file!.isNotEmpty) {
                          return message.file!.startsWith('http')
                            ? message.file
                            : '${ApiConfig.uploadsUrl}/${message.file}';
                        }
                        return null;
                      }
                      
                      return ListTile(
                        title: Text(message.text ?? ''),
                        subtitle: Text(
                          message.createdAt != null 
                            ? '${message.createdAt!.hour}:${message.createdAt!.minute.toString().padLeft(2, '0')}'
                            : '',
                        ),
                        leading: getImageUrl() != null
                            ? Image.network(
                                getImageUrl()!,
                                width: 50,
                                height: 50,
                                errorBuilder: (context, error, stackTrace) {
                                  return Icon(Icons.image, size: 50);
                                },
                              )
                            : getFileUrl() != null
                              ? Icon(Icons.insert_drive_file, size: 50)
                              : null,
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
                ),
                IconButton(
                  icon: Icon(Icons.attach_file),
                  onPressed: _pickFile,
                ),
                Expanded(
                  child: TextField(
                    controller: _messageController,
                    decoration: InputDecoration(
                      hintText: 'Сообщение...',
                      border: OutlineInputBorder(),
                    ),
                    onSubmitted: (_) => _sendMessage(),
                  ),
                ),
                IconButton(
                  icon: Icon(Icons.send),
                  onPressed: _sendMessage,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}