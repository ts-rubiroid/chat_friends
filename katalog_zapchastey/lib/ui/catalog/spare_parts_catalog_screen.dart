import 'package:flutter/material.dart';

import '../../config/odata_config.dart';
import '../../config/odata_config_repository.dart';
import '../../data/onec_odata_client.dart';
import '../../models/spare_part.dart';
import '../../utils/odata_logger.dart';
import '../settings/odata_settings_screen.dart';
import '../stats/catalog_stats_screen.dart';
import 'spare_part_details_screen.dart';

/// Главный экран: вкладка каталога и вкладка статистики.
class SparePartsApp extends StatefulWidget {
  const SparePartsApp({super.key});

  @override
  State<SparePartsApp> createState() => _SparePartsAppState();
}

class _SparePartsAppState extends State<SparePartsApp>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  final OdataConfigRepository _configRepository = OdataConfigRepository();

  OnecOdataClient? _client;
  OdataConfig? _config;
  bool _configLoading = true;

  List<SparePart>? _items;
  Map<String, double>? _pricesByNomen;
  Map<String, double>? _stocksByNomen;
  Object? _error;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadConfigAndData();
  }

  static String _formatErrorMessage(Object e) {
    final s = e.toString().toLowerCase();
    if (s.contains('socketexception') ||
        s.contains('connection') ||
        s.contains('failed host lookup') ||
        s.contains('network is unreachable')) {
      return 'Нет связи с сервером. Проверьте интернет и URL в настройках. '
          'Убедитесь, что URL доступен с этого устройства (не используйте localhost на телефоне).';
    }
    if (s.contains('401') || s.contains('403')) {
      return 'Неверный логин или пароль 1С. Проверьте настройки.';
    }
    if (s.contains('cleartext') || s.contains('operation not permitted')) {
      return 'HTTP заблокирован Android. Используйте HTTPS-URL в настройках или прокси с HTTPS.';
    }
    if (s.contains('timeout') || s.contains('превышено время')) {
      return 'Превышено время ожидания ответа. Сервер 1С долго не отвечает. '
          'Проверьте доступность сервера и интернет.';
    }
    if (s.contains('connection refused') || s.contains('connection reset')) {
      return 'Сервер недоступен. Проверьте URL и что сервер 1С запущен.';
    }
    return 'Ошибка: $e';
  }

  Future<void> _loadConfigAndData() async {
    setState(() {
      _configLoading = true;
      _error = null;
    });
    try {
      OdataLogger.logInfo('Загрузка конфигурации из хранилища');
      final config = await _configRepository.load();
      if (!mounted) return;
      OdataLogger.logInfo('Конфиг загружен: baseUrl=${config.baseUrl}, username=${config.username}');
      setState(() {
        _config = config;
        _client = OnecOdataClient(
          baseUrl: config.baseUrl,
          username: config.username,
          password: config.password,
        );
        _configLoading = false;
      });
      _loadData();
    } catch (e, stackTrace) {
      OdataLogger.logError('_loadConfigAndData', e, stackTrace: stackTrace);
      if (!mounted) return;
      setState(() {
        _configLoading = false;
        _error = Exception('Не удалось загрузить настройки: $e');
      });
    }
  }

  Future<void> _loadData() async {
    final client = _client;
    if (client == null) {
      OdataLogger.logWarning('_loadData вызван, но клиент не инициализирован');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    final stopwatch = Stopwatch()..start();
    OdataLogger.logInfo('=== Начало загрузки данных каталога ===');

    try {
      OdataLogger.logInfo('Загрузка дерева «Кронштейны»...');
      final items = await client.loadKronshteinyTree();
      OdataLogger.logInfo('Дерево загружено: ${items.length} элементов');

      // Параллельно подтягиваем цены и остатки.
      OdataLogger.logInfo('Параллельная загрузка цен и остатков...');
      final pricesFuture = client.loadPrices();
      final stocksFuture = client.loadStocks();

      final prices = await pricesFuture;
      final stocks = await stocksFuture;

      stopwatch.stop();
      OdataLogger.logInfo(
        '=== Загрузка завершена за ${stopwatch.elapsedMilliseconds}ms: '
        '${items.length} позиций, ${prices.length} цен, ${stocks.length} остатков ===',
      );

      if (!mounted) return;
      setState(() {
        _items = items;
        _pricesByNomen = prices;
        _stocksByNomen = stocks;
        _loading = false;
      });
    } catch (e, stackTrace) {
      stopwatch.stop();
      OdataLogger.logError(
        '_loadData',
        e,
        stackTrace: stackTrace,
      );
      OdataLogger.logInfo(
        'Загрузка завершилась ошибкой через ${stopwatch.elapsedMilliseconds}ms',
      );
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(

      appBar: AppBar(
        title: const Text('Каталог запчастей'),
        actions: [
          IconButton(
            tooltip: 'Настройки 1С',
            onPressed: _client == null
                ? null
                : () async {
                    final saved = await Navigator.of(context).push<bool>(
                      MaterialPageRoute(
                        builder: (_) => OdataSettingsScreen(
                          configRepository: _configRepository,
                          initialConfig:
                              _config ?? OdataConfig.defaultConfig,
                        ),
                      ),
                    );
                    if (saved == true && mounted) _loadConfigAndData();
                  },
            icon: const Icon(Icons.settings),
          ),
          IconButton(
            tooltip: 'Обновить данные',
            onPressed: _loadData,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      
      body: _buildBody(theme),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _loadData,
        icon: const Icon(Icons.refresh),
        label: const Text('Обновить'),
      ),
    );
  }

  Widget _buildBody(ThemeData theme) {
    if (_configLoading || _client == null) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      final message = _formatErrorMessage(_error!);
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Не удалось загрузить данные из 1С.',
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                message,
                style: theme.textTheme.bodySmall
                    ?.copyWith(color: theme.colorScheme.error),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _loadData,
                child: const Text('Повторить попытку'),
              ),
            ],
          ),
        ),
      );
    }

    final items = _items;
    if (items == null || items.isEmpty) {
      return const Center(child: Text('Данные каталога пусты.'));
    }

    final prices = _pricesByNomen ?? const {};
    final stocks = _stocksByNomen ?? const {};

    return TabBarView(
      controller: _tabController,
      children: [
        _CatalogTreeView(
          items: items,
          client: _client!,
          pricesByNomen: prices,
          stocksByNomen: stocks,
        ),
        CatalogStatsScreen(
          items: items,
          pricesByNomen: prices,
          stocksByNomen: stocks,
        ),
      ],
    );
  }
}

/// Простой древовидный список каталога номенклатуры.
class _CatalogTreeView extends StatefulWidget {
  final List<SparePart> items;
  final OnecOdataClient client;
  final Map<String, double> pricesByNomen;
  final Map<String, double> stocksByNomen;

  const _CatalogTreeView({
    required this.items,
    required this.client,
    required this.pricesByNomen,
    required this.stocksByNomen,
  });

  @override
  State<_CatalogTreeView> createState() => _CatalogTreeViewState();
}

class _CatalogTreeViewState extends State<_CatalogTreeView> {
  String _searchQuery = '';
  String? _selectedGroupFilter;
  String _sortBy = 'name'; // 'name', 'price', 'stock', 'code'

  @override
  Widget build(BuildContext context) {
    final folders = widget.items.where((e) => e.isFolder).toList();
    final byParent = <String, List<SparePart>>{};

    for (final item in widget.items.where((e) => !e.isFolder)) {
      byParent.putIfAbsent(item.parentKey, () => []).add(item);
    }

    // Фильтр: только группа "Кронштейны" и её подгруппы
    const rootParent = '00000000-0000-0000-0000-000000000000';
    final allRootFolders =
        folders.where((f) => f.parentKey == rootParent).toList();
    
    final kronshteynyFolder = allRootFolders.firstWhere(
      (f) => f.description.toLowerCase().contains('кронштейн'),
      orElse: () => allRootFolders.first,
    );

    // Собираем все подгруппы "Кронштейнов"
    final kronshteynySubfolders = <SparePart>[kronshteynyFolder];
    final kronshteynyKeys = <String>{kronshteynyFolder.refKey};
    
    void collectSubfolders(String parentKey) {
      for (final folder in folders) {
        if (folder.parentKey == parentKey && !kronshteynyKeys.contains(folder.refKey)) {
          kronshteynySubfolders.add(folder);
          kronshteynyKeys.add(folder.refKey);
          collectSubfolders(folder.refKey);
        }
      }
    }
    collectSubfolders(kronshteynyFolder.refKey);

    // Фильтруем позиции: только те, что принадлежат группе "Кронштейны"
    final filteredItems = widget.items.where((item) {
      if (item.isFolder) {
        return kronshteynyKeys.contains(item.refKey);
      }
      // Проверяем, что позиция принадлежит группе "Кронштейны" или её подгруппам
      return kronshteynyKeys.contains(item.parentKey);
    }).toList();

    // Применяем поиск
    final searchFiltered = filteredItems.where((item) {
      if (_searchQuery.isEmpty) return true;
      final query = _searchQuery.toLowerCase();
      return item.description.toLowerCase().contains(query) ||
          item.code.toLowerCase().contains(query) ||
          (item.article?.toLowerCase().contains(query) ?? false);
    }).toList();

    // Применяем фильтр по группе (если выбран)
    final groupFiltered = _selectedGroupFilter == null
        ? searchFiltered
        : searchFiltered.where((item) {
            if (item.isFolder) return item.description == _selectedGroupFilter;
            final parent = folders.firstWhere(
              (f) => f.refKey == item.parentKey,
              orElse: () => const SparePart(
                refKey: '',
                code: '',
                description: '',
                parentKey: '',
                isFolder: true,
              ),
            );
            return parent.description == _selectedGroupFilter;
          }).toList();

    // Сортировка
    final sorted = List<SparePart>.from(groupFiltered);
    sorted.sort((a, b) {
      if (a.isFolder != b.isFolder) {
        return a.isFolder ? -1 : 1; // Папки всегда сверху
      }
      switch (_sortBy) {
        case 'price':
          final priceA = widget.pricesByNomen[a.refKey] ?? 0;
          final priceB = widget.pricesByNomen[b.refKey] ?? 0;
          return priceB.compareTo(priceA);
        case 'stock':
          final stockA = widget.stocksByNomen[a.refKey] ?? 0;
          final stockB = widget.stocksByNomen[b.refKey] ?? 0;
          return stockB.compareTo(stockA);
        case 'code':
          return a.code.compareTo(b.code);
        default: // 'name'
          return a.description.compareTo(b.description);
      }
    });

    // Пересобираем структуру после фильтрации
    final filteredFolders = sorted.where((e) => e.isFolder).toList();
    final filteredByParent = <String, List<SparePart>>{};
    for (final item in sorted.where((e) => !e.isFolder)) {
      filteredByParent.putIfAbsent(item.parentKey, () => []).add(item);
    }

    final displayFolders = filteredFolders
        .where((f) => f.parentKey == rootParent || kronshteynyKeys.contains(f.parentKey))
        .toList()
      ..sort((a, b) => a.description.compareTo(b.description));

    // Получаем список всех доступных групп для фильтра
    final availableGroups = kronshteynySubfolders.map((f) => f.description).toList();

    return Column(
      children: [
        // Поиск и фильтры
        Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              TextField(
                decoration: const InputDecoration(
                  hintText: 'Поиск по названию, коду, артикулу...',
                  prefixIcon: Icon(Icons.search),
                ),
                onChanged: (value) {
                  setState(() {
                    _searchQuery = value;
                  });
                },
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        hintText: 'Фильтр по группе',
                        prefixIcon: Icon(Icons.filter_list),
                      ),
                      value: _selectedGroupFilter,
                      items: [
                        const DropdownMenuItem(
                          value: null,
                          child: Text('Все группы'),
                        ),
                        ...availableGroups.map(
                          (group) => DropdownMenuItem(
                            value: group,
                            child: Text(group),
                          ),
                        ),
                      ],
                      onChanged: (value) {
                        setState(() {
                          _selectedGroupFilter = value;
                        });
                      },
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      decoration: const InputDecoration(
                        hintText: 'Сортировка',
                        prefixIcon: Icon(Icons.sort),
                      ),
                      value: _sortBy,
                      items: const [
                        DropdownMenuItem(value: 'name', child: Text('По названию')),
                        DropdownMenuItem(value: 'code', child: Text('По коду')),
                        DropdownMenuItem(value: 'price', child: Text('По цене')),
                        DropdownMenuItem(value: 'stock', child: Text('По остатку')),
                      ],
                      onChanged: (value) {
                        if (value != null) {
                          setState(() {
                            _sortBy = value;
                          });
                        }
                      },
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        Expanded(
          child: displayFolders.isEmpty
              ? Center(
                  child: Text(
                    _searchQuery.isNotEmpty
                        ? 'Ничего не найдено по запросу "$_searchQuery"'
                        : 'Нет данных в группе "Кронштейны"',
                  ),
                )
              : ListView.builder(
                  itemCount: displayFolders.length,
                  itemBuilder: (context, index) {
                    final folder = displayFolders[index];
                    final children = filteredByParent[folder.refKey] ?? [];

                    return Card(
                      margin: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 6,
                      ),
                      child: ExpansionTile(
                        leading: CircleAvatar(
                          backgroundColor: Theme.of(context).colorScheme.primaryContainer,
                          child: Icon(
                            Icons.folder,
                            color: Theme.of(context).colorScheme.onPrimaryContainer,
                          ),
                        ),
                        title: Text(
                          folder.description,
                          style: const TextStyle(fontWeight: FontWeight.w600),
                        ),
                        subtitle: Text('Позиций: ${children.length}'),
                        children: [
                          if (children.isEmpty)
                            const Padding(
                              padding: EdgeInsets.all(16),
                              child: Text('Нет позиций в этой группе'),
                            )
                          else
                            ...children.map((item) => _buildItemTile(context, item)),
                        ],
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }

  Widget _buildItemTile(BuildContext context, SparePart item) {
    final price = widget.pricesByNomen[item.refKey];
    final stock = widget.stocksByNomen[item.refKey];

    final priceText = price == null
        ? '—'
        : '${price.toStringAsFixed(2)} ₽';
    final stockText = stock == null
        ? '—'
        : stock.toStringAsFixed(0);

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        leading: _Thumbnail(
          client: widget.client,
          item: item,
        ),
        title: Text(
          item.description,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 4),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (item.article?.isNotEmpty == true)
                Text(
                  'Артикул: ${item.article}',
                  style: const TextStyle(fontSize: 12),
                ),
              Text(
                'Остаток: $stockText',
                style: const TextStyle(fontSize: 12),
              ),
              Text(
                'Цена: $priceText',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.primary,
                ),
              ),
              Text(
                'Код: ${item.code}',
                style: TextStyle(
                  fontSize: 11,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ),
        ),
        // trailing больше не нужен
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => SparePartDetailsScreen(
                part: item,
                client: widget.client,
                price: price,
                stock: stock,
              ),
            ),
          );
        },
      ),
    );
  }


}

/// Временно показываем иконку шестерёнки вместо загрузки картинок из 1С.
class _Thumbnail extends StatelessWidget {
  final OnecOdataClient client;
  final SparePart item;

  const _Thumbnail({required this.client, required this.item});

  @override
  Widget build(BuildContext context) {
    return const CircleAvatar(
      child: Icon(Icons.build),
    );
  }
}

