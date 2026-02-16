/// Конфигурация подключения к OData 1С.
class OdataConfig {
  final String baseUrl;
  final String username;
  final String password;

  const OdataConfig({
    required this.baseUrl,
    required this.username,
    required this.password,
  });

  /// Значения по умолчанию (текущий внутренний адрес; после получения
  /// HTTPS-URL от провайдера его можно прописать здесь как default).
  static const OdataConfig defaultConfig = OdataConfig(
    baseUrl:
        'http://172.22.0.62/1R82821/1R82821_AVTOSERV30_73qj8uuuxp/odata/standard.odata',
    username: 'Администратор',
    password: '',
  );

  OdataConfig copyWith({
    String? baseUrl,
    String? username,
    String? password,
  }) {
    return OdataConfig(
      baseUrl: baseUrl ?? this.baseUrl,
      username: username ?? this.username,
      password: password ?? this.password,
    );
  }
}
