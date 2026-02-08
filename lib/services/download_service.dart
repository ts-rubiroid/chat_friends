import 'dart:io';
import 'package:dio/dio.dart';
import 'package:path_provider/path_provider.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:open_filex/open_filex.dart';

class DownloadService {
  static final Dio _dio = Dio();
  
  // Скачать файл
  static Future<File?> downloadFile({
    required String url,
    required String fileName,
    bool showNotification = true,
  }) async {
    try {
      // Проверяем разрешение на запись
      final status = await Permission.storage.request();
      if (!status.isGranted) {
        throw Exception('Разрешение на доступ к хранилищу не предоставлено');
      }
      
      // Получаем директорию для скачивания
      final directory = await getExternalStorageDirectory();
      if (directory == null) {
        throw Exception('Не удалось получить директорию для сохранения');
      }
      
      // Создаем путь для сохранения
      final savePath = '${directory.path}/Download/$fileName';
      
      // Скачиваем файл
      await _dio.download(
        url,
        savePath,
        options: Options(
          headers: {
            'Accept': '*/*',
          },
        ),
        onReceiveProgress: (received, total) {
          if (total != -1) {
            final progress = (received / total * 100).toStringAsFixed(0);
            print('Прогресс скачивания: $progress%');
          }
        },
      );
      
      final file = File(savePath);
      
      if (await file.exists()) {
        print('Файл сохранен: $savePath');
        return file;
      } else {
        throw Exception('Файл не был сохранен');
      }
    } catch (e) {
      print('Ошибка скачивания файла: $e');
      rethrow;
    }
  }
  
  // Скачать изображение в галерею
  static Future<void> downloadImageToGallery({
    required String url,
    required String fileName,
  }) async {
    try {
      // TODO: Реализовать сохранение в галерею через image_gallery_saver_plus
      print('Скачивание изображения в галерею: $url');
      
      // Временное решение - скачиваем в Download
      final file = await downloadFile(url: url, fileName: fileName);
      
      if (file != null) {
        // Открываем файл после скачивания
        await OpenFilex.open(file.path);
      }
    } catch (e) {
      print('Ошибка сохранения изображения: $e');
      rethrow;
    }
  }
  
  // Открыть файл
  static Future<void> openFile(String filePath) async {
    try {
      final result = await OpenFilex.open(filePath);
      print('Результат открытия файла: ${result.type} - ${result.message}');
    } catch (e) {
      print('Ошибка открытия файла: $e');
      rethrow;
    }
  }
  
  // Проверить существование файла
  static Future<bool> fileExists(String fileName) async {
    try {
      final directory = await getExternalStorageDirectory();
      if (directory == null) return false;
      
      final filePath = '${directory.path}/Download/$fileName';
      final file = File(filePath);
      
      return await file.exists();
    } catch (e) {
      return false;
    }
  }
}