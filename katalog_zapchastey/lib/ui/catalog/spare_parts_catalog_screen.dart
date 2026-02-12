import 'package:flutter/material.dart';

import '../../data/onec_odata_client.dart';
import '../../models/spare_part.dart';
import '../stats/catalog_stats_screen.dart';
// import '../debug/odata_diagnostics_screen.dart';
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
  late final OnecOdataClient _client;

  List<SparePart>? _items;
  Map<String, double>? _pricesByNomen;
  Map<String, double>? _stocksByNomen;
  Object? _error;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);

    // TODO: при необходимости вынести в экран настроек.
    _client = const OnecOdataClient(
      baseUrl:
          'http://172.22.0.62/1R82821/1R82821_AVTOSERV30_73qj8uuuxp/odata/standard.odata',
      username: 'Администратор',
      password: '',
    );

    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final items = await _client.loadCatalogNomenklatura();

      // Параллельно подтягиваем цены и остатки.
      final pricesFuture = _client.loadPrices();
      final stocksFuture = _client.loadStocks();

      final prices = await pricesFuture;
      final stocks = await stocksFuture;

      setState(() {
        _items = items;
        _pricesByNomen = prices;
        _stocksByNomen = stocks;
        _loading = false;
      });
    } catch (e) {
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
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
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
                'Ошибка: $_error',
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
          client: _client,
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

  Widget? _buildSubtitle(SparePart item) {
    final parts = <String>[];
    if (item.article?.isNotEmpty == true) {
      parts.add('Артикул: ${item.article}');
    }
    if (item.comment?.isNotEmpty == true) {
      parts.add(item.comment!);
    }
    if (parts.isEmpty) return null;
    return Text(
      parts.join(' · '),
      style: TextStyle(fontSize: 12, color: Theme.of(context).colorScheme.onSurfaceVariant),
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

class _Thumbnail extends StatelessWidget {
  final OnecOdataClient client;
  final SparePart item;

  const _Thumbnail({required this.client, required this.item});

  @override
  Widget build(BuildContext context) {
    // Если у позиции нет прикреплённой картинки — показываем иконку.
    if (item.pictureFileKey == null) {
      return const CircleAvatar(
        child: Icon(Icons.build),
      );
    }

    final url = client.buildNomenklaturaThumbnailUrl(item.refKey);

    return ClipRRect(
      borderRadius: BorderRadius.circular(8),
      child: Image.network(
        url,
        // Важно: не просим JSON, иначе сервер может вернуть не-картинку.
        width: 44,
        height: 44,
        fit: BoxFit.cover,
        errorBuilder: (context, error, stackTrace) {
          return const CircleAvatar(
            child: Icon(Icons.broken_image),
          );
        },
      ),
    );
  }
}

