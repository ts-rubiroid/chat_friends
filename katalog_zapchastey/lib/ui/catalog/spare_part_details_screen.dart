import 'dart:typed_data';

import 'package:flutter/material.dart';

import '../../data/onec_odata_client.dart';
import '../../models/spare_part.dart';

/// Экран карточки конкретной запчасти.
///
/// При открытии подгружает картинку из Catalog_НоменклатураПрисоединенныеФайлы
/// и отображает основные реквизиты номенклатуры.
class SparePartDetailsScreen extends StatelessWidget {
  final SparePart part;
  final OnecOdataClient client;
  final double? price;
  final double? stock;

  const SparePartDetailsScreen({
    super.key,
    required this.part,
    required this.client,
    this.price,
    this.stock,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

  final priceText = price == null
      ? '—'
      : '${price!.toStringAsFixed(2)} ₽';
  final stockText = stock == null
      ? '—'
      : stock!.toStringAsFixed(0);



    return Scaffold(
      appBar: AppBar(
        title: Text(part.description),
        actions: [
          IconButton(
            tooltip: 'Обновить',
            onPressed: () {
              // Перезагружаем экран для обновления картинки
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(
                  builder: (_) => SparePartDetailsScreen(
                    part: part,
                    client: client,
                  ),
                ),
              );
            },
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: _PartImage(client: client, part: part),
              ),
            ),
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      part.fullName?.isNotEmpty == true
                          ? part.fullName!
                          : part.description,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    if (part.article?.isNotEmpty == true)
                      _InfoRow(
                        icon: Icons.qr_code,
                        label: 'Артикул',
                        value: part.article!,
                      ),
                    const SizedBox(height: 8),
                    _InfoRow(
                      icon: Icons.tag,
                      label: 'Код',
                      value: part.code,
                    ),
                    if (part.comment?.isNotEmpty == true) ...[
                      const SizedBox(height: 8),
                      _InfoRow(
                        icon: Icons.description,
                        label: 'Комментарий',
                        value: part.comment!,
                      ),

                      _InfoRow(
                        icon: Icons.inventory_2,
                        label: 'Остаток',
                        value: stockText,
                      ),
                      const SizedBox(height: 8),
                      _InfoRow(
                        icon: Icons.attach_money,
                        label: 'Цена',
                        value: priceText,
                      ),
                      _InfoRow(
                        icon: Icons.tag,
                        label: 'Код',
                        value: part.code,
                      ),





                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PartImage extends StatelessWidget {
  final OnecOdataClient client;
  final SparePart part;

  const _PartImage({required this.client, required this.part});

  @override
  Widget build(BuildContext context) {
    // Если даже нет признаков прикреплённого файла, сразу показываем иконку.
    if (part.pictureFileKey == null) {
      return const _PlaceholderIcon();
    }

    return FutureBuilder<Uint8List?>(
      future: client.loadNomenklaturaAttachmentImage(part.refKey),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const SizedBox(
            height: 200,
            child: Center(child: CircularProgressIndicator()),
          );
        }

        if (snapshot.hasError || snapshot.data == null) {
          return const _PlaceholderIcon();
        }

        final bytes = snapshot.data!;

        return ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Image.memory(
            bytes,
            height: 240,
            fit: BoxFit.contain,
          ),
        );
      },
    );
  }
}

class _PlaceholderIcon extends StatelessWidget {
  const _PlaceholderIcon();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 200,
      child: Center(
        child: Icon(
          Icons.build,
          size: 96,
          color: Theme.of(context).colorScheme.primary,
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;

  const _InfoRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 20, color: Theme.of(context).colorScheme.primary),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

