import 'package:flutter/material.dart';

import '../../config/odata_config.dart';
import '../../config/odata_config_repository.dart';
import '../../data/onec_odata_client.dart';
import '../../utils/odata_logger.dart';

/// Экран настроек подключения к 1С (OData): URL, логин, пароль,
/// проверка подключения и сохранение.
class OdataSettingsScreen extends StatefulWidget {
  const OdataSettingsScreen({
    super.key,
    required this.configRepository,
    required this.initialConfig,
  });

  final OdataConfigRepository configRepository;
  final OdataConfig initialConfig;

  @override
  State<OdataSettingsScreen> createState() => _OdataSettingsScreenState();
}

class _OdataSettingsScreenState extends State<OdataSettingsScreen> {
  late final TextEditingController _baseUrlController;
  late final TextEditingController _usernameController;
  late final TextEditingController _passwordController;

  String? _connectionMessage;
  bool _connectionSuccess = false;
  bool _testing = false;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _baseUrlController = TextEditingController(text: widget.initialConfig.baseUrl);
    _usernameController =
        TextEditingController(text: widget.initialConfig.username);
    _passwordController =
        TextEditingController(text: widget.initialConfig.password);
  }

  @override
  void dispose() {
    _baseUrlController.dispose();
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  OdataConfig get _currentConfig => OdataConfig(
        baseUrl: _baseUrlController.text.trim(),
        username: _usernameController.text.trim(),
        password: _passwordController.text,
      );

  Future<void> _testConnection() async {
    final baseUrl = _baseUrlController.text.trim();
    if (baseUrl.isEmpty) {
      setState(() {
        _connectionMessage = 'Введите URL сервера 1С.';
        _connectionSuccess = false;
      });
      return;
    }

    setState(() {
      _testing = true;
      _connectionMessage = null;
    });

    OdataLogger.logInfo('=== Проверка подключения к 1С ===');
    OdataLogger.logInfo('URL: $baseUrl');
    OdataLogger.logInfo('Username: ${_usernameController.text.trim()}');

    final stopwatch = Stopwatch()..start();
    try {
      final client = OnecOdataClient(
        baseUrl: baseUrl,
        username: _usernameController.text.trim(),
        password: _passwordController.text,
      );
      OdataLogger.logInfo('Вызов listCollections()...');
      await client.listCollections();
      stopwatch.stop();
      OdataLogger.logInfo('Проверка подключения успешна за ${stopwatch.elapsedMilliseconds}ms');
      if (!mounted) return;
      setState(() {
        _connectionMessage = 'Подключение успешно. OData доступен.';
        _connectionSuccess = true;
        _testing = false;
      });
    } catch (e, stackTrace) {
      stopwatch.stop();
      OdataLogger.logError(
        '_testConnection',
        e,
        stackTrace: stackTrace,
      );
      OdataLogger.logInfo('Проверка подключения завершилась ошибкой через ${stopwatch.elapsedMilliseconds}ms');
      if (!mounted) return;
      setState(() {
        _connectionMessage = _formatConnectionError(e);
        _connectionSuccess = false;
        _testing = false;
      });
    }
  }

  String _formatConnectionError(Object e) {
    final s = e.toString();
    if (s.contains('SocketException') || s.contains('Connection')) {
      return 'Нет связи с сервером. Проверьте интернет и URL.';
    }
    if (s.contains('401') || s.contains('403')) {
      return 'Неверный логин или пароль 1С.';
    }
    if (s.contains('cleartext') || s.contains('Operation not permitted')) {
      return 'Сервер недоступен (возможна блокировка HTTP). Используйте HTTPS-URL.';
    }
    return 'Ошибка: $e';
  }

  Future<void> _save() async {
    final config = _currentConfig;
    if (config.baseUrl.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Введите URL сервера 1С.')),
      );
      return;
    }

    setState(() => _saving = true);
    try {
      await widget.configRepository.save(config);
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Не удалось сохранить: $e')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Настройки 1С'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextField(
              controller: _baseUrlController,
              decoration: const InputDecoration(
                labelText: 'URL OData 1С',
                hintText:
                    'https://.../odata/standard.odata или http://... для локальной сети',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.url,
              autocorrect: false,
              onChanged: (_) => setState(() => _connectionMessage = null),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _usernameController,
              decoration: const InputDecoration(
                labelText: 'Пользователь 1С',
                border: OutlineInputBorder(),
              ),
              autocorrect: false,
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _passwordController,
              decoration: const InputDecoration(
                labelText: 'Пароль',
                border: OutlineInputBorder(),
              ),
              obscureText: true,
              autocorrect: false,
            ),
            const SizedBox(height: 24),
            if (_connectionMessage != null)
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: Text(
                  _connectionMessage!,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: _connectionSuccess
                        ? theme.colorScheme.primary
                        : theme.colorScheme.error,
                  ),
                ),
              ),
            FilledButton.icon(
              onPressed: _testing ? null : _testConnection,
              icon: _testing
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.wifi_tethering),
              label: Text(_testing ? 'Проверка...' : 'Проверить подключение'),
            ),
            const SizedBox(height: 12),
            FilledButton.icon(
              onPressed: _saving ? null : _save,
              icon: _saving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.save),
              label: const Text('Сохранить'),
            ),
          ],
        ),
      ),
    );
  }
}
