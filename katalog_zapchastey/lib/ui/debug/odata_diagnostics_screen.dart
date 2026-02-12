import 'package:flutter/material.dart';

import '../../data/onec_odata_client.dart';

class OdataDiagnosticsScreen extends StatefulWidget {
  final OnecOdataClient client;

  const OdataDiagnosticsScreen({super.key, required this.client});

  @override
  State<OdataDiagnosticsScreen> createState() => _OdataDiagnosticsScreenState();
}

class _OdataDiagnosticsScreenState extends State<OdataDiagnosticsScreen> {
  bool _loading = false;
  Object? _error;
  List<String>? _collections;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final collections = await widget.client.listCollections();
      if (!mounted) return;
      setState(() {
        _collections = collections;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Диагностика OData'),
        actions: [
          IconButton(
            tooltip: 'Обновить',
            onPressed: _load,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: _buildBody(context),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _load,
        icon: const Icon(Icons.refresh),
        label: const Text('Обновить'),
      ),
    );
  }

  Widget _buildBody(BuildContext context) {
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
                'Не удалось получить список разделов OData.',
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text('Ошибка: $_error', textAlign: TextAlign.center),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _load,
                child: const Text('Повторить'),
              ),
            ],
          ),
        ),
      );
    }

    final collections = _collections ?? const [];
    if (collections.isEmpty) {
      return const Center(child: Text('Разделы OData не найдены.'));
    }

    // Подсказка: что искать для цен и остатков.
    return Column(
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 12, 16, 12),
          child: Text(
            'Это список разделов, которые доступны в вашей 1С через OData.\n\n'
            'Чтобы добавить \"цены\" и \"остатки\" в приложение, нам нужно, чтобы '
            'в списке присутствовали разделы, похожие на:\n'
            '- InformationRegister_... (часто цены)\n'
            '- AccumulationRegister_... (часто остатки)\n\n'
            'Если найдёте в списке слова \"Цена\", \"Цены\", \"Остатки\", '
            '\"Склад\" — это хорошие кандидаты.',
          ),
        ),
        const Divider(height: 1),
        Expanded(
          child: ListView.builder(
            itemCount: collections.length,
            itemBuilder: (context, index) {
              final name = collections[index];
              return ListTile(
                dense: true,
                title: Text(name),
              );
            },
          ),
        ),
      ],
    );
  }
}

