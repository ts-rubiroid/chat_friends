import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import '../../models/spare_part.dart';

/// Экран статистики по каталогу номенклатуры.
///
/// Показывает:
/// 1) Количество позиций по верхним группам (как было раньше);
/// 2) Распределение позиций по ценовым диапазонам;
/// 3) ТОП-группы по суммарным остаткам;
/// 4) ТОП-группы по суммарной стоимости (цена * остаток).
class CatalogStatsScreen extends StatelessWidget {
  final List<SparePart> items;
  final Map<String, double> pricesByNomen;
  final Map<String, double> stocksByNomen;

  const CatalogStatsScreen({
    super.key,
    required this.items,
    required this.pricesByNomen,
    required this.stocksByNomen,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Expanded(
            child: ListView(
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: _buildCountByGroupChart(context),
                  ),
                ),
                const SizedBox(height: 16),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: _buildPriceHistogram(context),
                  ),
                ),
                const SizedBox(height: 16),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: _buildStockByGroupChart(context),
                  ),
                ),
                const SizedBox(height: 16),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: _buildTotalValueByGroupChart(context),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  /// Диаграмма: количество позиций по верхним группам.
  Widget _buildCountByGroupChart(BuildContext context) {
    final folders = items.where((e) => e.isFolder).toList();
    final leaves = items.where((e) => !e.isFolder).toList();

    const rootParent = '00000000-0000-0000-0000-000000000000';
    final rootFolders =
        folders.where((f) => f.parentKey == rootParent).toList();

    final countsByRoot = <String, int>{};

    for (final leaf in leaves) {
      final parent = folders.firstWhere(
        (f) => f.refKey == leaf.parentKey,
        orElse: () => const SparePart(
          refKey: 'unknown',
          code: '',
          description: 'Без группы',
          parentKey: rootParent,
          isFolder: true,
        ),
      );

      final root = _findRootFolder(parent, rootFolders, folders) ?? parent;
      countsByRoot.update(root.description, (value) => value + 1,
          ifAbsent: () => 1);
    }

    final sortedEntries = countsByRoot.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));

    if (sortedEntries.isEmpty) {
      return const Text('Недостаточно данных для статистики по группам.');
    }

    final topEntries = sortedEntries.take(8).toList();

    return SizedBox(
      height: 220,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Количество позиций по маркам / группам',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Expanded(
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                gridData: const FlGridData(show: false),
                borderData: FlBorderData(show: false),
                titlesData: FlTitlesData(
                  leftTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: true),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final index = value.toInt();
                        if (index < 0 || index >= topEntries.length) {
                          return const SizedBox.shrink();
                        }
                        final label = topEntries[index].key;
                        return Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: RotatedBox(
                            quarterTurns: 1,
                            child: Text(
                              label,
                              style: const TextStyle(fontSize: 10),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                barGroups: [
                  for (var i = 0; i < topEntries.length; i++)
                    BarChartGroupData(
                      x: i,
                      barRods: [
                        BarChartRodData(
                          toY: topEntries[i].value.toDouble(),
                          width: 14,
                          borderRadius: BorderRadius.circular(4),
                          color: Theme.of(context).colorScheme.primary,
                        ),
                      ],
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Диаграмма: распределение позиций по ценовым диапазонам.
  Widget _buildPriceHistogram(BuildContext context) {
    final prices = <double>[];
    for (final part in items.where((e) => !e.isFolder)) {
      final price = pricesByNomen[part.refKey];
      if (price != null && price > 0) {
        prices.add(price);
      }
    }

    if (prices.isEmpty) {
      return const Text('Цены по номенклатуре не найдены в OData.');
    }

    prices.sort();
    final maxPrice = prices.last;

    // Простые диапазоны: 0–1000, 1000–5000, 5000–20000, 20000–...
    final ranges = <String, int>{
      '≤ 1 000': 0,
      '1 000–5 000': 0,
      '5 000–20 000': 0,
      '> 20 000': 0,
    };

    for (final p in prices) {
      if (p <= 1000) {
        ranges['≤ 1 000'] = ranges['≤ 1 000']! + 1;
      } else if (p <= 5000) {
        ranges['1 000–5 000'] = ranges['1 000–5 000']! + 1;
      } else if (p <= 20000) {
        ranges['5 000–20 000'] = ranges['5 000–20 000']! + 1;
      } else {
        ranges['> 20 000'] = ranges['> 20 000']! + 1;
      }
    }

    final entries = ranges.entries.toList();

    return SizedBox(
      height: 220,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'Распределение позиций по ценовым диапазонам\n'
            '(макс. цена ≈ ${maxPrice.toStringAsFixed(0)})',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Expanded(
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                gridData: const FlGridData(show: false),
                borderData: FlBorderData(show: false),
                titlesData: FlTitlesData(
                  leftTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: true),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final index = value.toInt();
                        if (index < 0 || index >= entries.length) {
                          return const SizedBox.shrink();
                        }
                        final label = entries[index].key;
                        return Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(
                            label,
                            style: const TextStyle(fontSize: 10),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                barGroups: [
                  for (var i = 0; i < entries.length; i++)
                    BarChartGroupData(
                      x: i,
                      barRods: [
                        BarChartRodData(
                          toY: entries[i].value.toDouble(),
                          width: 18,
                          borderRadius: BorderRadius.circular(4),
                          color: Theme.of(context).colorScheme.secondary,
                        ),
                      ],
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Диаграмма: ТОП-группы по суммарным остаткам (штуки на складах).
  Widget _buildStockByGroupChart(BuildContext context) {
    final folders = items.where((e) => e.isFolder).toList();
    final leaves = items.where((e) => !e.isFolder).toList();

    const rootParent = '00000000-0000-0000-0000-000000000000';
    final rootFolders =
        folders.where((f) => f.parentKey == rootParent).toList();

    final stocksByRoot = <String, double>{};

    for (final leaf in leaves) {
      final stock = stocksByNomen[leaf.refKey];
      if (stock == null || stock <= 0) continue;

      final parent = folders.firstWhere(
        (f) => f.refKey == leaf.parentKey,
        orElse: () => const SparePart(
          refKey: 'unknown',
          code: '',
          description: 'Без группы',
          parentKey: rootParent,
          isFolder: true,
        ),
      );

      final root = _findRootFolder(parent, rootFolders, folders) ?? parent;

      stocksByRoot.update(root.description, (value) => value + stock,
          ifAbsent: () => stock);
    }

    final sortedEntries = stocksByRoot.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));

    if (sortedEntries.isEmpty) {
      return const Text('Нет данных по остаткам для построения диаграммы.');
    }

    final topEntries = sortedEntries.take(8).toList();

    return SizedBox(
      height: 220,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'ТОП групп по суммарным остаткам (штуки)',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Expanded(
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                gridData: const FlGridData(show: false),
                borderData: FlBorderData(show: false),
                titlesData: FlTitlesData(
                  leftTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: true),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final index = value.toInt();
                        if (index < 0 || index >= topEntries.length) {
                          return const SizedBox.shrink();
                        }
                        final label = topEntries[index].key;
                        return Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: RotatedBox(
                            quarterTurns: 1,
                            child: Text(
                              label,
                              style: const TextStyle(fontSize: 10),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                barGroups: [
                  for (var i = 0; i < topEntries.length; i++)
                    BarChartGroupData(
                      x: i,
                      barRods: [
                        BarChartRodData(
                          toY: topEntries[i].value,
                          width: 14,
                          borderRadius: BorderRadius.circular(4),
                          color: Theme.of(context).colorScheme.tertiary,
                        ),
                      ],
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  /// Диаграмма: ТОП-группы по суммарной стоимости (цена * остаток).
  Widget _buildTotalValueByGroupChart(BuildContext context) {
    final folders = items.where((e) => e.isFolder).toList();
    final leaves = items.where((e) => !e.isFolder).toList();

    const rootParent = '00000000-0000-0000-0000-000000000000';
    final rootFolders =
        folders.where((f) => f.parentKey == rootParent).toList();

    final valueByRoot = <String, double>{};

    for (final leaf in leaves) {
      final price = pricesByNomen[leaf.refKey];
      final stock = stocksByNomen[leaf.refKey];
      if (price == null || stock == null || price <= 0 || stock <= 0) {
        continue;
      }

      final total = price * stock;

      final parent = folders.firstWhere(
        (f) => f.refKey == leaf.parentKey,
        orElse: () => const SparePart(
          refKey: 'unknown',
          code: '',
          description: 'Без группы',
          parentKey: rootParent,
          isFolder: true,
        ),
      );

      final root = _findRootFolder(parent, rootFolders, folders) ?? parent;

      valueByRoot.update(root.description, (value) => value + total,
          ifAbsent: () => total);
    }

    final sortedEntries = valueByRoot.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));

    if (sortedEntries.isEmpty) {
      return const Text(
        'Нет достаточных данных (цены * остатки) для диаграммы стоимости.',
      );
    }

    final topEntries = sortedEntries.take(8).toList();

    return SizedBox(
      height: 220,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            'ТОП групп по суммарной стоимости запаса',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          const SizedBox(height: 8),
          Expanded(
            child: BarChart(
              BarChartData(
                alignment: BarChartAlignment.spaceAround,
                gridData: const FlGridData(show: false),
                borderData: FlBorderData(show: false),
                titlesData: FlTitlesData(
                  leftTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: true),
                  ),
                  rightTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  topTitles: const AxisTitles(
                    sideTitles: SideTitles(showTitles: false),
                  ),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(
                      showTitles: true,
                      getTitlesWidget: (value, meta) {
                        final index = value.toInt();
                        if (index < 0 || index >= topEntries.length) {
                          return const SizedBox.shrink();
                        }
                        final label = topEntries[index].key;
                        return Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: RotatedBox(
                            quarterTurns: 1,
                            child: Text(
                              label,
                              style: const TextStyle(fontSize: 10),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),
                barGroups: [
                  for (var i = 0; i < topEntries.length; i++)
                    BarChartGroupData(
                      x: i,
                      barRods: [
                        BarChartRodData(
                          toY: topEntries[i].value,
                          width: 14,
                          borderRadius: BorderRadius.circular(4),
                          color: Theme.of(context).colorScheme.primaryContainer,
                        ),
                      ],
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  SparePart? _findRootFolder(
    SparePart folder,
    List<SparePart> rootFolders,
    List<SparePart> allFolders,
  ) {
    if (rootFolders.any((f) => f.refKey == folder.refKey)) {
      return folder;
    }

    final parent = allFolders
        .firstWhere((f) => f.refKey == folder.parentKey, orElse: () => folder);

    if (parent.refKey == folder.refKey) {
      return folder;
    }

    return _findRootFolder(parent, rootFolders, allFolders);
  }
}

