import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;
import 'package:xml/xml.dart';

import '../models/spare_part.dart';
import '../utils/odata_logger.dart';

/// Простейший клиент для чтения каталога номенклатуры из 1С по OData.
class OnecOdataClient {
  /// Базовый адрес OData без конкретного справочника.
  ///
  /// Пример:
  /// http://172.22.0.62/1R82821/1R82821_AVTOSERV30_73qj8uuuxp/odata/standard.odata
  final String baseUrl;

  /// Имя пользователя 1С (например, Администратор).
  final String username;

  /// Пароль пользователя 1С (может быть пустым).
  final String password;

  const OnecOdataClient({
    required this.baseUrl,
    required this.username,
    required this.password,
  });

  Map<String, String> get _authHeaders {
    final authString = '$username:$password';
    final authHeader = 'Basic ${base64Encode(utf8.encode(authString))}';
    return {
      'Authorization': authHeader,
    };
  }

  /// Заголовки для JSON-запросов.
  Map<String, String> get jsonHeaders => {
        ..._authHeaders,
        'Accept': 'application/json',
      };

  /// Заголовки для XML-запросов.
  Map<String, String> get xmlHeaders => {
        ..._authHeaders,
        'Accept': 'application/xml',
      };

  /// Таймаут для HTTP запросов (60 секунд).
  static const Duration _requestTimeout = Duration(seconds: 60);

  /// Выполняет HTTP GET запрос с логированием, таймаутом и обработкой ошибок.
  Future<http.Response> _performGet(
    Uri uri,
    Map<String, String> headers,
    String operation,
  ) async {
    final stopwatch = Stopwatch()..start();
    OdataLogger.logRequest('GET', uri, headers: headers);

    try {
      final response = await http
          .get(uri, headers: headers)
          .timeout(_requestTimeout, onTimeout: () {
        OdataLogger.logError(
          operation,
          'Timeout после ${_requestTimeout.inSeconds} секунд',
        );
        throw Exception(
          'Превышено время ожидания ответа от сервера (${_requestTimeout.inSeconds} сек). '
          'Проверьте интернет и доступность сервера 1С.',
        );
      });

      stopwatch.stop();
      OdataLogger.logResponse(
        uri,
        response.statusCode,
        response.bodyBytes.length,
        stopwatch.elapsed,
      );

      if (response.statusCode == 401 || response.statusCode == 403) {
        OdataLogger.logError(
          operation,
          'HTTP ${response.statusCode}: Неверный логин или пароль',
        );
        throw Exception(
          'Нет доступа к OData (HTTP ${response.statusCode}). Проверьте логин и пароль 1С.',
        );
      }

      if (response.statusCode != 200) {
        OdataLogger.logError(
          operation,
          'HTTP ${response.statusCode}: ${response.reasonPhrase}',
        );
        throw Exception(
          'Ошибка сервера 1С (HTTP ${response.statusCode}): ${response.reasonPhrase}. '
          'Проверьте URL и доступность сервера.',
        );
      }

      return response;
    } on http.ClientException catch (e) {
      stopwatch.stop();
      OdataLogger.logError(operation, e, stackTrace: StackTrace.current);
      final message = _formatNetworkError(e.toString());
      throw Exception(message);
    } on Exception catch (e) {
      stopwatch.stop();
      if (e.toString().contains('Timeout') ||
          e.toString().contains('превышено время')) {
        rethrow;
      }
      OdataLogger.logError(operation, e, stackTrace: StackTrace.current);
      rethrow;
    } catch (e, stackTrace) {
      stopwatch.stop();
      OdataLogger.logError(operation, e, stackTrace: stackTrace);
      throw Exception('Неожиданная ошибка: $e');
    }
  }

  /// Форматирует сообщение об ошибке сети для пользователя.
  String _formatNetworkError(String error) {
    final lower = error.toLowerCase();
    if (lower.contains('socketexception') ||
        lower.contains('failed host lookup') ||
        lower.contains('network is unreachable')) {
      return 'Нет связи с сервером. Проверьте интернет и URL в настройках. '
          'Убедитесь, что URL доступен с этого устройства (не используйте localhost на телефоне).';
    }
    if (lower.contains('connection refused') ||
        lower.contains('connection reset')) {
      return 'Сервер недоступен. Проверьте URL и что сервер 1С запущен.';
    }
    if (lower.contains('cleartext') ||
        lower.contains('operation not permitted')) {
      return 'HTTP заблокирован Android. Используйте HTTPS-URL в настройках или прокси с HTTPS.';
    }
    return 'Ошибка сети: $error';
  }

  /// Проверяет, что URL не localhost (для телефона это не сработает).
  void _validateUrl() {
    final uri = Uri.tryParse(baseUrl);
    if (uri == null) {
      OdataLogger.logWarning('Некорректный URL: $baseUrl');
      return;
    }
    final host = uri.host.toLowerCase();
    if (host == 'localhost' || host == '127.0.0.1') {
      OdataLogger.logWarning(
        'URL содержит localhost — на телефоне это не сработает. '
        'Используйте IP адрес ПК или доменное имя.',
      );
    }
  }

  /// Минимальный набор полей для запросов по дереву (чтобы не тянуть лишние данные).
  static const String _selectFields =
      'Ref_Key,Code,Description,Parent_Key,IsFolder,НаименованиеПолное,Артикул,Комментарий,ФайлКартинки_Key';

  /// Загружает только ветку каталога «Кронштейны» и её подгруппы/позиции по родителю,
  /// без полной выгрузки всего Catalog_Номенклатура (он слишком большой).
  Future<List<SparePart>> loadKronshteinyTree() async {
    _validateUrl();
    OdataLogger.logInfo('Начало загрузки дерева «Кронштейны»');

    try {
      final rootRefKey = await _findRootFolderRefKey(
        parentKey: rootParent,
        nameContains: 'кронштейн',
      );
      if (rootRefKey == null) {
        // Раньше приложение уже умело работать с полным каталогом и
        // успешно находило нужную группу на клиенте.
        // Если не удалось найти корневую группу «Кронштейны» на сервере,
        // возвращаемся к старому поведению: загружаем весь каталог и даём
        // UI самому отфильтровать нужную ветку.
        OdataLogger.logWarning(
          'Не найдена корневая группа «Кронштейны» на уровне OData, '
          'возвращаемся к полной загрузке каталога.',
        );
        return loadCatalogNomenklatura();
      }

      OdataLogger.logInfo('Найден корень «Кронштейны»: $rootRefKey');

      final result = <SparePart>[];
      final rootItem = await _loadSingleNomenklatura(rootRefKey);
      if (rootItem != null) {
        result.add(rootItem);
      }

      final queue = <String>[rootRefKey];
      int batchCount = 0;

      while (queue.isNotEmpty) {
        final parentKey = queue.removeAt(0);
        batchCount++;
        OdataLogger.logInfo('Загрузка пакета $batchCount, родитель: $parentKey');
        final batch = await _loadNomenklaturaPageByParent(
          parentKey: parentKey,
          select: _selectFields,
          top: 1000,
        );
        for (final item in batch) {
          result.add(item);
          if (item.isFolder) {
            queue.add(item.refKey);
          }
        }
        OdataLogger.logInfo('Пакет $batchCount: загружено ${batch.length} элементов');
      }

      OdataLogger.logInfo('Загрузка завершена: всего ${result.length} элементов');
      return result;
    } catch (e, stackTrace) {
      OdataLogger.logError('loadKronshteinyTree', e, stackTrace: stackTrace);
      rethrow;
    }
  }

  /// Ищет Ref_Key папки верхнего уровня по подстроке в наименовании.
  Future<String?> _findRootFolderRefKey({
    required String parentKey,
    required String nameContains,
  }) async {
    final filter = "Parent_Key eq guid'$parentKey'";
    final uri = Uri.parse(
      '$baseUrl/Catalog_Номенклатура?\$format=json&\$filter=$filter&\$select=Ref_Key,Description&\$top=500',
    );
    try {
      final response = await _performGet(uri, jsonHeaders, '_findRootFolderRefKey');
      final decoded = json.decode(utf8.decode(response.bodyBytes))
          as Map<String, dynamic>;
      final list = decoded['value'] as List<dynamic>? ?? [];
      final lower = nameContains.toLowerCase();
      for (final e in list) {
        if (e is! Map<String, dynamic>) continue;
        final desc = (e['Description'] as String? ?? '').toLowerCase();
        if (desc.contains(lower)) {
          final key = e['Ref_Key'] as String?;
          if (key != null && key != rootParent) return key;
        }
      }
      return null;
    } catch (e) {
      OdataLogger.logError('_findRootFolderRefKey', e);
      return null;
    }
  }

  static const String rootParent = '00000000-0000-0000-0000-000000000000';

  /// Загружает одну запись номенклатуры по Ref_Key.
  /// 1С по ключу возвращает один объект, не обёртку value.
  Future<SparePart?> _loadSingleNomenklatura(String refKey) async {
    final uri = Uri.parse(
      "$baseUrl/Catalog_Номенклатура(guid'$refKey')?\$format=json&\$select=$_selectFields",
    );
    try {
      final response = await _performGet(uri, jsonHeaders, '_loadSingleNomenklatura');
      final decoded = json.decode(utf8.decode(response.bodyBytes));
      if (decoded is! Map<String, dynamic>) return null;
      return SparePart.fromJson(decoded);
    } catch (e) {
      OdataLogger.logError('_loadSingleNomenklatura', e);
      return null;
    }
  }

  /// Загружает одну страницу номенклатуры по родителю.
  Future<List<SparePart>> _loadNomenklaturaPageByParent({
    required String parentKey,
    required String select,
    int top = 1000,
    int skip = 0,
  }) async {
    final filter = "Parent_Key eq guid'$parentKey'";
    final uri = Uri.parse(
      '$baseUrl/Catalog_Номенклатура?\$format=json&\$filter=$filter&\$select=$select&\$top=$top&\$skip=$skip',
    );
    final response = await _performGet(uri, jsonHeaders, '_loadNomenklaturaPageByParent');
    final decoded = json.decode(utf8.decode(response.bodyBytes))
        as Map<String, dynamic>;
    final list = decoded['value'] as List<dynamic>? ?? [];
    return list
        .whereType<Map<String, dynamic>>()
        .map(SparePart.fromJson)
        .toList();
  }

  /// Загружает весь каталог номенклатуры с сервера 1С.
  /// Не используйте при большом объёме данных — предпочтительно [loadKronshteinyTree].
  Future<List<SparePart>> loadCatalogNomenklatura() async {
    final uri = Uri.parse('$baseUrl/Catalog_Номенклатура?\$format=json');

    final response = await http.get(
      uri,
      headers: jsonHeaders,
    );

    if (response.statusCode == 401 || response.statusCode == 403) {
      throw Exception('Нет доступа к OData. Проверьте логин и пароль 1С.');
    }

    if (response.statusCode != 200) {
      throw Exception(
        'Ошибка при обращении к 1С (код ${response.statusCode}). '
        'Проверьте URL и подключение через VPN.',
      );
    }

    final decoded = json.decode(utf8.decode(response.bodyBytes))
        as Map<String, dynamic>;

    final List<dynamic> items = decoded['value'] as List<dynamic>;

    return items
        .whereType<Map<String, dynamic>>()
        .map(SparePart.fromJson)
        .toList();
  }

  /// Загружает информацию о ценах номенклатуры.
  ///
  /// Ожидается, что в регистре InformationRegister_ЦеныНоменклатуры есть поля
  /// "Номенклатура_Key" и "Цена". На каждую номенклатуру берём одну цену
  /// (например, последнюю по порядку в ответе).
  Future<Map<String, double>> loadPrices() async {
    OdataLogger.logInfo('Загрузка цен');
    final uri = Uri.parse(
      '$baseUrl/InformationRegister_ЦеныНоменклатуры?\$format=json',
    );

    final response = await _performGet(uri, jsonHeaders, 'loadPrices');

    final decoded = json.decode(utf8.decode(response.bodyBytes))
        as Map<String, dynamic>;

    final List<dynamic> rows = decoded['value'] as List<dynamic>;

    final result = <String, double>{};

    for (final row in rows.whereType<Map<String, dynamic>>()) {
      final nomenKey = row['Номенклатура_Key'] as String?;
      if (nomenKey == null ||
          nomenKey == '00000000-0000-0000-0000-000000000000') {
        continue;
      }

      final dynamic priceRaw = row['Цена'];
      if (priceRaw is num) {
        // Перезаписываем, чтобы в итоге осталось "последнее" значение.
        result[nomenKey] = priceRaw.toDouble();
      }
    }

    return result;
  }

  /// Загружает информацию об остатках номенклатуры на складах.
  ///
  /// Ожидается, что в регистре AccumulationRegister_ЗапасыНаСкладах есть поля
  /// "Номенклатура_Key" и одно из полей остатков: "Остаток" или "Количество".
  Future<Map<String, double>> loadStocks() async {
    OdataLogger.logInfo('Загрузка остатков');
    final uri = Uri.parse(
      '$baseUrl/AccumulationRegister_ЗапасыНаСкладах?\$format=json',
    );

    final response = await _performGet(uri, jsonHeaders, 'loadStocks');

    final decoded = json.decode(utf8.decode(response.bodyBytes))
        as Map<String, dynamic>;

    final List<dynamic> rows = decoded['value'] as List<dynamic>;

    final result = <String, double>{};

    for (final row in rows.whereType<Map<String, dynamic>>()) {
      final nomenKey = row['Номенклатура_Key'] as String?;
      if (nomenKey == null ||
          nomenKey == '00000000-0000-0000-0000-000000000000') {
        continue;
      }

      dynamic stockRaw;
      if (row.containsKey('Остаток')) {
        stockRaw = row['Остаток'];
      } else if (row.containsKey('Количество')) {
        stockRaw = row['Количество'];
      } else {
        continue;
      }

      if (stockRaw is num) {
        // Складываем остатки по всем складам.
        result.update(nomenKey, (value) => value + stockRaw.toDouble(),
            ifAbsent: () => stockRaw.toDouble());
      }
    }

    return result;
  }

  /// Список доступных разделов (collections) в OData.
  ///
  /// Это полезно, чтобы понять, есть ли в вашей базе разделы для цен и остатков.
  Future<List<String>> listCollections() async {
    final uri = Uri.parse('$baseUrl/');
    final response = await _performGet(uri, xmlHeaders, 'listCollections');

    final doc = XmlDocument.parse(utf8.decode(response.bodyBytes));
    final hrefs = <String>[];

    for (final node in doc.findAllElements('collection')) {
      final href = node.getAttribute('href');
      if (href != null && href.isNotEmpty) {
        hrefs.add(href);
      }
    }
    hrefs.sort();
    return hrefs;
  }

  /// URL для мини-картинки номенклатуры (если она задана в 1С).
  ///
  /// Примечание: в разных конфигурациях 1С путь к картинке может отличаться.
  /// Этот путь соответствует типичному случаю, когда у номенклатуры есть ссылка
  /// "ФайлКартинки" и у него можно получить "$value".
  String buildNomenklaturaThumbnailUrl(String refKey) {
    return "$baseUrl/Catalog_Номенклатура(guid'$refKey')/ФайлКартинки/\$value";
  }

  /// Загружает байты картинки номенклатуры из каталога
  /// Catalog_НоменклатураПрисоединенныеФайлы.
  ///
  /// В этой конфигурации файл хранится в базе в полях
  /// "ТекстХранилище_Base64Data" или "ФайлХранилище_Base64Data".
  /// Формат:
  ///  - внешний Base64 → XML <String>...</String>
  ///  - внутренний текст XML → Base64 самой JPG/PNG.
  Future<Uint8List?> loadNomenklaturaAttachmentImage(String refKey) async {
    final uri = Uri.parse(
      "$baseUrl/Catalog_НоменклатураПрисоединенныеФайлы?"
      "\$format=json&"
      "\$filter=ВладелецФайла_Key eq guid'$refKey'&"
      "\$top=1",
    );

    final response = await http.get(uri, headers: jsonHeaders);

    if (response.statusCode == 401 || response.statusCode == 403) {
      throw Exception(
        'Нет доступа к Catalog_НоменклатураПрисоединенныеФайлы.',
      );
    }
    if (response.statusCode != 200) {
      throw Exception(
        'Не удалось получить присоединённые файлы номенклатуры '
        '(код ${response.statusCode}).',
      );
    }

    final decoded = json.decode(utf8.decode(response.bodyBytes))
        as Map<String, dynamic>;
    final List<dynamic> rows = decoded['value'] as List<dynamic>;

    if (rows.isEmpty) {
      return null;
    }

    final row = rows.firstWhere(
      (r) =>
          r is Map<String, dynamic> &&
          (r['ТекстХранилище_Base64Data'] as String?)?.isNotEmpty == true,
      orElse: () => rows.first,
    );

    if (row is! Map<String, dynamic>) {
      return null;
    }

    String? outerBase64 = row['ТекстХранилище_Base64Data'] as String?;

    // Если текстовое хранилище пустое, пробуем ФайлХранилище.
    outerBase64 ??= row['ФайлХранилище_Base64Data'] as String?;

    if (outerBase64 == null || outerBase64.isEmpty) {
      return null;
    }

    try {
      // 1. Декодируем внешний Base64 в XML.
      final xmlBytes = base64Decode(outerBase64);
      final xmlString = utf8.decode(xmlBytes);
      final xmlDoc = XmlDocument.parse(xmlString);

      // 2. Внутренний текст узла <String> — это Base64 самой картинки.
      final innerBase64 = xmlDoc.rootElement.innerText.trim();
      if (innerBase64.isEmpty) {
        return null;
      }

      // 3. Декодируем картинку.
      final imageBytes = base64Decode(innerBase64);
      return Uint8List.fromList(imageBytes);
    } catch (_) {
      // Если что-то пошло не так при декодировании, просто вернём null.
      return null;
    }
  }
}

