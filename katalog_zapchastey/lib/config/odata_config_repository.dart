import 'package:shared_preferences/shared_preferences.dart';

import 'odata_config.dart';

const String _keyBaseUrl = 'odata_base_url';
const String _keyUsername = 'odata_username';
const String _keyPassword = 'odata_password';

/// Загрузка и сохранение настроек OData в локальное хранилище.
class OdataConfigRepository {
  OdataConfigRepository([SharedPreferences? prefs]) : _prefs = prefs;

  SharedPreferences? _prefs;

  Future<SharedPreferences> _getPrefs() async {
    _prefs ??= await SharedPreferences.getInstance();
    return _prefs!;
  }

  /// Загружает сохранённую конфигурацию или возвращает значения по умолчанию.
  Future<OdataConfig> load() async {
    final prefs = await _getPrefs();
    final baseUrl = prefs.getString(_keyBaseUrl);
    final username = prefs.getString(_keyUsername);
    final password = prefs.getString(_keyPassword);

    if (baseUrl == null || baseUrl.isEmpty) {
      return OdataConfig.defaultConfig;
    }
    return OdataConfig(
      baseUrl: baseUrl.trim(),
      username: (username ?? OdataConfig.defaultConfig.username).trim(),
      password: password ?? OdataConfig.defaultConfig.password,
    );
  }

  /// Сохраняет конфигурацию.
  Future<void> save(OdataConfig config) async {
    final prefs = await _getPrefs();
    await prefs.setString(_keyBaseUrl, config.baseUrl.trim());
    await prefs.setString(_keyUsername, config.username.trim());
    await prefs.setString(_keyPassword, config.password);
  }
}
