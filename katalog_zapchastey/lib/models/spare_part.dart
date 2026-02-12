class SparePart {
  final String refKey;
  final String code;
  final String description;
  final String? fullName;
  final String? article;
  final String? comment;
  final String? pictureFileKey;
  final String parentKey;
  final bool isFolder;

  const SparePart({
    required this.refKey,
    required this.code,
    required this.description,
    required this.parentKey,
    required this.isFolder,
    this.fullName,
    this.article,
    this.comment,
    this.pictureFileKey,
  });

  factory SparePart.fromJson(Map<String, dynamic> json) {
    final rawPictureKey = json['ФайлКартинки_Key'] as String?;
    final pictureKey = (rawPictureKey == null ||
            rawPictureKey == '00000000-0000-0000-0000-000000000000')
        ? null
        : rawPictureKey;

    return SparePart(
      refKey: json['Ref_Key'] as String,
      code: json['Code'] as String? ?? '',
      description: json['Description'] as String? ?? '',
      fullName: json['НаименованиеПолное'] as String?,
      article: json['Артикул'] as String?,
      comment: json['Комментарий'] as String?,
      pictureFileKey: pictureKey,
      parentKey: json['Parent_Key'] as String? ??
          '00000000-0000-0000-0000-000000000000',
      isFolder: json['IsFolder'] as bool? ?? false,
    );
  }
}

