class PencapaianResponse {
  final int teknisiId;
  final String namaTeknisi;
  final int bulan;
  final int tahun;
  final int totalSelesai;
  final Map<String, int> rincian;

  PencapaianResponse({
    required this.teknisiId,
    required this.namaTeknisi,
    required this.bulan,
    required this.tahun,
    required this.totalSelesai,
    required this.rincian,
  });

  factory PencapaianResponse.fromJson(Map<String, dynamic> json) {
    return PencapaianResponse(
      teknisiId: json['teknisi_id'] ?? 0,
      namaTeknisi: json['nama_teknisi'] ?? '',
      bulan: json['bulan'] ?? 0,
      tahun: json['tahun'] ?? 0,
      totalSelesai: json['total_selesai'] ?? 0,
      rincian: json['rincian'] != null && json['rincian'] is Map
          ? (json['rincian'] as Map<String, dynamic>).map(
              (key, value) => MapEntry(key, value is int ? value : int.tryParse(value.toString()) ?? 0),
            )
          : {},
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'teknisi_id': teknisiId,
      'nama_teknisi': namaTeknisi,
      'bulan': bulan,
      'tahun': tahun,
      'total_selesai': totalSelesai,
      'rincian': rincian,
    };
  }
}
