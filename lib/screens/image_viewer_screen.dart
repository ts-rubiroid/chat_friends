import 'package:flutter/material.dart';
import 'package:photo_view/photo_view.dart';
import 'package:chat_friends/services/download_service.dart'; // Добавлен импорт

class ImageViewerScreen extends StatefulWidget {
  final String imageUrl;
  final String? heroTag;
  final String? fileName;

  const ImageViewerScreen({
    Key? key,
    required this.imageUrl,
    this.heroTag,
    this.fileName,
  }) : super(key: key);

  @override
  _ImageViewerScreenState createState() => _ImageViewerScreenState();
}

class _ImageViewerScreenState extends State<ImageViewerScreen> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black.withOpacity(0.5),
        elevation: 0,
        leading: IconButton(
          icon: Icon(Icons.close, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          IconButton(
            icon: Icon(Icons.download, color: Colors.white),
            onPressed: _downloadImage,
          ),
          IconButton(
            icon: Icon(Icons.share, color: Colors.white),
            onPressed: _shareImage,
          ),
        ],
      ),
      body: Center(
        child: PhotoView(
          imageProvider: NetworkImage(widget.imageUrl),
          heroAttributes: widget.heroTag != null
              ? PhotoViewHeroAttributes(tag: widget.heroTag!)
              : null,
          backgroundDecoration: BoxDecoration(color: Colors.black),
          minScale: PhotoViewComputedScale.contained,
          maxScale: PhotoViewComputedScale.covered * 2.0,
          initialScale: PhotoViewComputedScale.contained,
          loadingBuilder: (context, event) => Center(
            child: Container(
              width: 40,
              height: 40,
              child: CircularProgressIndicator(
                value: event == null
                    ? 0
                    : event.cumulativeBytesLoaded / event.expectedTotalBytes!,
              ),
            ),
          ),
          errorBuilder: (context, error, stackTrace) => Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.error, color: Colors.white, size: 50),
                SizedBox(height: 16),
                Text(
                  'Не удалось загрузить изображение',
                  style: TextStyle(color: Colors.white),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _downloadImage() async {
    try {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Скачивание изображения...')),
      );
      
      print('Скачивание изображения: ${widget.imageUrl}');
      
      // Используем DownloadService
      final success = await DownloadService.downloadImageToGallery(widget.imageUrl);
      
      if (success) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('✅ Изображение сохранено в галерею'),
            duration: Duration(seconds: 3),
          ),
        );
      } else {
        throw Exception('Не удалось сохранить изображение');
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('❌ Ошибка скачивания: ${e.toString()}'),
          duration: Duration(seconds: 4),
        ),
      );
    }
  }

  Future<void> _shareImage() async {
    // TODO: Реализовать шеринг изображения
    print('Поделиться изображением: ${widget.imageUrl}');
  }
}