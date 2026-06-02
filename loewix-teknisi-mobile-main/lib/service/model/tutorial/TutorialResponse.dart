class TutorialListResponse {
  final List<Tutorial> data;

  TutorialListResponse({required this.data});

  factory TutorialListResponse.fromJson(Map<String, dynamic> json) {
    return TutorialListResponse(
      data: (json['data'] as List<dynamic>?)
              ?.map((item) => Tutorial.fromJson(item as Map<String, dynamic>))
              .toList() ??
          [],
    );
  }
}

class TutorialDetailResponse {
  final Tutorial data;

  TutorialDetailResponse({required this.data});

  factory TutorialDetailResponse.fromJson(Map<String, dynamic> json) {
    return TutorialDetailResponse(
      data: Tutorial.fromJson(json['data'] as Map<String, dynamic>),
    );
  }
}

class Tutorial {
  final int id;
  final String title;
  final String? media1;
  final String? media2;
  final String description;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  Tutorial({
    required this.id,
    required this.title,
    this.media1,
    this.media2,
    required this.description,
    this.createdAt,
    this.updatedAt,
  });

  factory Tutorial.fromJson(Map<String, dynamic> json) {
    return Tutorial(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      media1: json['media_1'],
      media2: json['media_2'],
      description: json['description'] ?? '',
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'])
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'])
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'media_1': media1,
      'media_2': media2,
      'description': description,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  // Check if media1 is video
  bool get isMedia1Video {
    if (media1 == null) return false;
    final lower = media1!.toLowerCase();
    return lower.endsWith('.mp4') ||
        lower.endsWith('.mov') ||
        lower.endsWith('.avi') ||
        lower.endsWith('.mkv') ||
        lower.endsWith('.webm');
  }

  // Check if media1 is image
  bool get isMedia1Image {
    if (media1 == null) return false;
    final lower = media1!.toLowerCase();
    return lower.endsWith('.jpg') ||
        lower.endsWith('.jpeg') ||
        lower.endsWith('.png') ||
        lower.endsWith('.gif') ||
        lower.endsWith('.webp');
  }

  // Check if media2 is PDF
  bool get isMedia2Pdf {
    if (media2 == null) return false;
    return media2!.toLowerCase().endsWith('.pdf');
  }

  // Check if media2 is image
  bool get isMedia2Image {
    if (media2 == null) return false;
    final lower = media2!.toLowerCase();
    return lower.endsWith('.jpg') ||
        lower.endsWith('.jpeg') ||
        lower.endsWith('.png') ||
        lower.endsWith('.gif') ||
        lower.endsWith('.webp');
  }

  // Get media type icon
  String get mediaTypeLabel {
    if (isMedia1Video) return 'Video';
    if (isMedia1Image) return 'Gambar';
    if (media1 != null) return 'Media';
    return 'Artikel';
  }
}
