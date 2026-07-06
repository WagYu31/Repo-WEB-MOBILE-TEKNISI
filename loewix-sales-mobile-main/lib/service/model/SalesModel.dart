class SalesProfile {
  final int id;
  final String nik;
  final String nama;
  final String noTlp;
  final String jabatan;

  SalesProfile({
    required this.id,
    required this.nik,
    required this.nama,
    required this.noTlp,
    required this.jabatan,
  });

  factory SalesProfile.fromJson(Map<String, dynamic> j) => SalesProfile(
        id: int.parse(j['id'].toString()),
        nik: j['nik'] ?? '',
        nama: j['nama'] ?? '',
        noTlp: j['no_tlp'] ?? '',
        jabatan: j['jabatan'] ?? 'Sales',
      );
}

class VisitTask {
  final int kegiatanId;
  final String jadwal;
  final String keterangan;
  final String statusKegiatan;
  final String? kode;
  final int customerId;
  final String namaCustomer;
  final String telpCustomer;
  final String alamatCustomer;
  final String kotaCustomer;
  final int? pelaksanaanId;
  final String? statusKunjungan;
  final String? ciAt;
  final String? coAt;
  final String? latCi;
  final String? lonCi;
  final String? latCo;
  final String? lonCo;
  final String? catatanVisit;
  final String? fotoCustomer;
  final String? latCustomer;
  final String? lonCustomer;

  VisitTask({
    required this.kegiatanId,
    required this.jadwal,
    required this.keterangan,
    required this.statusKegiatan,
    this.kode,
    required this.customerId,
    required this.namaCustomer,
    required this.telpCustomer,
    required this.alamatCustomer,
    required this.kotaCustomer,
    this.pelaksanaanId,
    this.statusKunjungan,
    this.ciAt,
    this.coAt,
    this.latCi,
    this.lonCi,
    this.latCo,
    this.lonCo,
    this.catatanVisit,
    this.fotoCustomer,
    this.latCustomer,
    this.lonCustomer,
  });

  factory VisitTask.fromJson(Map<String, dynamic> j) => VisitTask(
        kegiatanId: int.parse(j['kegiatan_id'].toString()),
        jadwal: j['jadwal'] ?? '',
        keterangan: j['keterangan'] ?? '',
        statusKegiatan: j['status_kegiatan'] ?? 'dijadwalkan',
        kode: j['kode'],
        customerId: int.parse((j['customer_id'] ?? 0).toString()),
        namaCustomer: j['nama_customer'] ?? '-',
        telpCustomer: j['telp_customer'] ?? '',
        alamatCustomer: j['alamat_customer'] ?? '-',
        kotaCustomer: j['kota_customer'] ?? '',
        pelaksanaanId: j['pelaksanaan_id'] != null
            ? int.parse(j['pelaksanaan_id'].toString())
            : null,
        statusKunjungan: j['status_kunjungan'],
        ciAt: j['ci_at'],
        coAt: j['co_at'],
        latCi: j['lat_ci'],
        lonCi: j['lon_ci'],
        latCo: j['lat_co'],
        lonCo: j['lon_co'],
        catatanVisit: j['catatan_visit'],
        fotoCustomer: j['foto_customer'],
        latCustomer: j['lat_customer'],
        lonCustomer: j['lon_customer'],
      );

  bool get sudahClockIn => ciAt != null && ciAt!.isNotEmpty;
  bool get sudahClockOut => coAt != null && coAt!.isNotEmpty;
  bool get sedangBerjalan => sudahClockIn && !sudahClockOut;
  bool get selesai => sudahClockOut;
}
