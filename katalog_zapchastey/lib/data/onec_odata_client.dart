import 'dart:convert';
import 'dart:typed_data';

import 'package:http/http.dart' as http;
import 'package:xml/xml.dart';

import '../models/spare_part.dart';

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

  /// Загружает весь каталог номенклатуры с сервера 1С.
  ///
  /// На первом этапе без фильтров и постраничности.
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
    final uri = Uri.parse(
      '$baseUrl/InformationRegister_ЦеныНоменклатуры?\$format=json',
    );

    final response = await http.get(uri, headers: jsonHeaders);

    if (response.statusCode == 401 || response.statusCode == 403) {
      throw Exception(
        'Нет доступа к регистру цен (InformationRegister_ЦеныНоменклатуры).',
      );
    }
    if (response.statusCode != 200) {
      throw Exception(
        'Не удалось получить цены номенклатуры (код ${response.statusCode}).',
      );
    }

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
    final uri = Uri.parse(
      '$baseUrl/AccumulationRegister_ЗапасыНаСкладах?\$format=json',
    );

    final response = await http.get(uri, headers: jsonHeaders);

    if (response.statusCode == 401 || response.statusCode == 403) {
      throw Exception(
        'Нет доступа к регистру остатков (AccumulationRegister_ЗапасыНаСкладах).',
      );
    }
    if (response.statusCode != 200) {
      throw Exception(
        'Не удалось получить остатки номенклатуры (код ${response.statusCode}).',
      );
    }

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
    final response = await http.get(uri, headers: xmlHeaders);

    if (response.statusCode == 401 || response.statusCode == 403) {
      throw Exception('Нет доступа к OData. Проверьте логин и пароль 1С.');
    }
    if (response.statusCode != 200) {
      throw Exception(
        'Не удалось получить список разделов OData (код ${response.statusCode}).',
      );
    }

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

