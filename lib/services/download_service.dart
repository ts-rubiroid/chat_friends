import 'dart:io';
import 'dart:async';
import 'package:dio/dio.dart';
import 'package:path_provider/path_provider.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:open_filex/open_filex.dart';
import 'package:image_gallery_saver_plus/image_gallery_saver_plus.dart';
import 'dart:typed_data';

class DownloadService {
  static final Dio _dio = Dio();
  
  // Скачать файл в папку Downloads
  static Future<File?> downloadFile({
    required String url,
    required String fileName,
    Function(int, int)? onProgress,
  }) async {
    try {
      print('[DownloadService] Начало скачивания: $fileName');
      
      // 1. Проверяем разрешения
      if (await _requestStoragePermission() == false) {
        throw Exception('Разрешение на доступ к хранилищу не предоставлено');
      }
      
      // 2. Получаем директорию для скачивания
      final directory = await getExternalStorageDirectory();
      if (directory == null) {
        throw Exception('Не удалось получить директорию для сохранения');
      }
      
      // 3. Создаем папку Download если её нет
      final downloadDir = Directory('${directory.path}/Download');
      if (!await downloadDir.exists()) {
        await downloadDir.create(recursive: true);
      }
      
      // 4. Формируем путь для сохранения
      String savePath = '${downloadDir.path}/$fileName';
      
      // 5. Проверяем, не существует ли уже файл
      int counter = 1;
      while (await File(savePath).exists()) {
        final nameWithoutExt = fileName.substring(0, fileName.lastIndexOf('.'));
        final extension = fileName.substring(fileName.lastIndexOf('.'));
        savePath = '${downloadDir.path}/${nameWithoutExt}_($counter)$extension';
        counter++;
      }
      
      print('[DownloadService] Сохранение в: $savePath');
      
      // 6. Скачиваем файл
      await _dio.download(
        url,
        savePath,
        options: Options(
          headers: {
            'Accept': '*/*',
          },
          receiveTimeout: Duration(seconds: 30),
        ),
        onReceiveProgress: onProgress ?? (received, total) {
          if (total != -1) {
            final progress = (received / total * 100).toStringAsFixed(0);
            print('[DownloadService] Прогресс: $progress% ($received/$total байт)');
          }
        },
      );
      
      final file = File(savePath);
      
      if (await file.exists()) {
        final fileSize = await file.length();
        print('[DownloadService] ✅ Файл сохранен: $savePath ($fileSize байт)');
        return file;
      } else {
        throw Exception('Файл не был сохранен');
      }
    } catch (e) {
      print('[DownloadService] ❌ Ошибка скачивания файла: $e');
      rethrow;
    }
  }
  
  // Скачать изображение в галерею
  static Future<bool> downloadImageToGallery(String url) async {
    try {
      print('[DownloadService] Скачивание изображения в галерею: $url');
      
      // 1. Проверяем разрешения
      if (await _requestStoragePermission() == false) {
        throw Exception('Разрешение на доступ к хранилищу не предоставлено');
      }
      
      // 2. Скачиваем изображение
      final response = await _dio.get(
        url,
        options: Options(responseType: ResponseType.bytes),
      );
      
      if (response.statusCode == 200 && response.data != null) {
        // 3. Сохраняем в галерею
        final result = await ImageGallerySaverPlus.saveImage(
          Uint8List.fromList(response.data),
          quality: 100,
          name: 'image_${DateTime.now().millisecondsSinceEpoch}',
        );
        
        print('[DownloadService] Результат сохранения в галерею: $result');
        
        if (result['isSuccess'] == true) {
          print('[DownloadService] ✅ Изображение сохранено в галерею');
          return true;
        } else {
          throw Exception('Не удалось сохранить в галерею: $result');
        }
      } else {
        throw Exception('Не удалось загрузить изображение: ${response.statusCode}');
      }
    } catch (e) {
      print('[DownloadService] ❌ Ошибка сохранения изображения: $e');
      rethrow;
    }
  }
  
  // Открыть файл
  static Future<void> openFile(String filePath) async {
    try {
      print('[DownloadService] Открытие файла: $filePath');
      
      final result = await OpenFilex.open(filePath);
      print('[DownloadService] Результат открытия: ${result.type} - ${result.message}');
      
      if (result.type != ResultType.done) {
        throw Exception('Не удалось открыть файл: ${result.message}');
      }
    } catch (e) {
      print('[DownloadService] Ошибка открытия файла: $e');
      rethrow;
    }
  }
  
  // Проверить разрешения
  static Future<bool> _requestStoragePermission() async {
    try {
      // Для Android 13+ нужны разные разрешения
      if (await Permission.manageExternalStorage.isGranted) {
        return true;
      }
      
      final status = await Permission.storage.request();
      if (status.isGranted) {
        return true;
      }
      
      // Пробуем запросить управление внешним хранилищем
      if (await Permission.manageExternalStorage.request().isGranted) {
        return true;
      }
      
      print('[DownloadService] Разрешение не предоставлено: $status');
      return false;
    } catch (e) {
      print('[DownloadService] Ошибка при запросе разрешений: $e');
      return false;
    }
  }
  
  // Получить путь к папке Downloads
  static Future<String> getDownloadsPath() async {
    final directory = await getExternalStorageDirectory();
    return '${directory?.path}/Download';
  }
}