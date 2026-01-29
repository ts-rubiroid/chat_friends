import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import 'package:chat_friends/services/api_service.dart';
import 'package:chat_friends/models/chat.dart';
import 'package:chat_friends/models/message.dart';
import 'package:chat_friends/models/user.dart'; // ДОБАВЛЕН ИМПОРТ
import '../utils/api.dart';

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
      }
    });
  }

  @override
  void dispose() {
    _messageController.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    try {
      final currentUser = await ApiService.getCurrentUser();
      final messages = await ApiService.getMessages(widget.chat.id);
      
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

  Future<void> _loadMessages() async {
    try {
      final messages = await ApiService.getMessages(widget.chat.id);
      if (mounted) {
        setState(() {
          _messages = messages;
        });
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
                          
                          String? getImageUrl() {
                            if (message.image != null && message.image!.isNotEmpty) {
                              return message.image!.startsWith('http') 
                                ? message.image
                                : '${ApiConfig.uploadsUrl}/${message.image}';
                            }
                            return null;
                          }
                          
                          String? getFileUrl() {
                            if (message.file != null && message.file!.isNotEmpty) {
                              return message.file!.startsWith('http')
                                ? message.file
                                : '${ApiConfig.uploadsUrl}/${message.file}';
                            }
                            return null;
                          }
                          
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
                                      color: Colors.grey[300],
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
                                      color: isMe ? Colors.blue[100] : Colors.grey[200],
                                      borderRadius: BorderRadius.circular(16),
                                    ),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        if (getImageUrl() != null)
                                          Container(
                                            margin: EdgeInsets.only(bottom: 8),
                                            child: Image.network(
                                              getImageUrl()!,
                                              width: 200,
                                              height: 150,
                                              fit: BoxFit.cover,
                                              errorBuilder: (context, error, stackTrace) {
                                                return Container(
                                                  width: 200,
                                                  height: 150,
                                                  color: Colors.grey[300],
                                                  child: Center(
                                                    child: Icon(Icons.broken_image, size: 40),
                                                  ),
                                                );
                                              },
                                            ),
                                          ),
                                        
                                        if (getFileUrl() != null)
                                          Container(
                                            margin: EdgeInsets.only(bottom: 8),
                                            child: Row(
                                              children: [
                                                Icon(Icons.insert_drive_file),
                                                SizedBox(width: 8),
                                                Expanded(
                                                  child: Text(
                                                    message.file?.split('/').last ?? 'Файл',
                                                    overflow: TextOverflow.ellipsis,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        
                                        if (message.text?.isNotEmpty == true)
                                          Text(
                                            message.text!,
                                            style: TextStyle(fontSize: 16),
                                          ),
                                        
                                        SizedBox(height: 4),
                                        Text(
                                          message.createdAt != null 
                                            ? '${message.createdAt!.hour.toString().padLeft(2, '0')}:${message.createdAt!.minute.toString().padLeft(2, '0')}'
                                            : '',
                                          style: TextStyle(
                                            fontSize: 12,
                                            color: Colors.grey[600],
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
                ),
                
                IconButton(
                  icon: Icon(Icons.attach_file),
                  onPressed: _pickFile,
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
                      ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}