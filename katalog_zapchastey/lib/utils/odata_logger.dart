import 'dart:developer' as developer;

/// Логирование для отладки OData запросов.
class OdataLogger {
  static const bool _enabled = true; // Включить/выключить логи

  static void logRequest(String method, Uri uri, {Map<String, String>? headers}) {
    if (!_enabled) return;
    developer.log(
      'OData REQUEST: $method $uri',
      name: 'OData',
      level: 800, // INFO
    );
    if (headers != null && headers.containsKey('Authorization')) {
      final auth = headers['Authorization']!;
      developer.log(
        '  Authorization: ${auth.length > 20 ? "${auth.substring(0, 20)}..." : auth}',
        name: 'OData',
      );
    }
  }

  static void logResponse(Uri uri, int statusCode, int? bodyLength, Duration duration) {
    if (!_enabled) return;
    developer.log(
      'OData RESPONSE: $statusCode | ${duration.inMilliseconds}ms | ${bodyLength ?? 0} bytes | $uri',
      name: 'OData',
      level: statusCode >= 400 ? 1000 : 800, // ERROR if >= 400, else INFO
    );
  }

  static void logError(String operation, Object error, {StackTrace? stackTrace}) {
    if (!_enabled) return;
    developer.log(
      'OData ERROR in $operation: $error',
      name: 'OData',
      level: 1000, // ERROR
      error: error,
      stackTrace: stackTrace,
    );
  }

  static void logInfo(String message) {
    if (!_enabled) return;
    developer.log('OData INFO: $message', name: 'OData', level: 800);
  }

  static void logWarning(String message) {
    if (!_enabled) return;
    developer.log('OData WARNING: $message', name: 'OData', level: 900);
  }
}
