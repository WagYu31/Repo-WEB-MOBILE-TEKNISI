class PendapatanResponse {
  final int teknisiId;
  final String namaTeknisi;
  final int bulan;
  final int tahun;
  final int target;
  final int jumlahKegiatan;
  final int selesai;
  final int invoice;
  final int fee;
  final int totalPendapatan;
  final int totalKeseluruhan;
  final int bonus;

  PendapatanResponse({
    required this.teknisiId,
    required this.namaTeknisi,
    required this.bulan,
    required this.tahun,
    required this.target,
    required this.jumlahKegiatan,
    required this.selesai,
    required this.invoice,
    required this.fee,
    required this.totalPendapatan,
    required this.totalKeseluruhan,
    required this.bonus,
  });

  factory PendapatanResponse.fromJson(Map<String, dynamic> json) {
    return PendapatanResponse(
      teknisiId: json['teknisi_id'] ?? 0,
      namaTeknisi: json['nama_teknisi'] ?? '',
      bulan: json['bulan'] ?? 0,
      tahun: json['tahun'] ?? 0,
      target: json['target'] ?? 0,
      jumlahKegiatan: json['jumlah_kegiatan'] ?? 0,
      selesai: json['selesai'] ?? 0,
      invoice: json['invoice'] ?? 0,
      fee: json['fee'] ?? 0,
      totalPendapatan: json['total_pendapatan'] ?? 0,
      totalKeseluruhan: json['total_keseluruhan'] ?? 0,
      bonus: json['bonus'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'teknisi_id': teknisiId,
      'nama_teknisi': namaTeknisi,
      'bulan': bulan,
      'tahun': tahun,
      'target': target,
      'jumlah_kegiatan': jumlahKegiatan,
      'selesai': selesai,
      'invoice': invoice,
      'fee': fee,
      'total_pendapatan': totalPendapatan,
      'total_keseluruhan': totalKeseluruhan,
      'bonus': bonus,
    };
  }

  String formatRupiah(int value) {
    return 'Rp ${value.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]}.',
    )}';
  }
}
