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
  final String? latKegiatan;
  final String? lonKegiatan;
  final int radGeofence;
  final String? image1;
  final String? image2;
  final String? image3;
  final String? image4;
  final String? image5;

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
    this.latKegiatan,
    this.lonKegiatan,
    this.radGeofence = 100,
    this.image1,
    this.image2,
    this.image3,
    this.image4,
    this.image5,
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
        latKegiatan: j['lat_kegiatan'],
        lonKegiatan: j['lon_kegiatan'],
        radGeofence: int.tryParse((j['rad_geofence'] ?? '100').toString()) ?? 100,
        image1: j['image_1'],
        image2: j['image_2'],
        image3: j['image_3'],
        image4: j['image_4'],
        image5: j['image_5'],
      );

  bool get sudahClockIn => ciAt != null && ciAt!.isNotEmpty;
  bool get sudahClockOut => coAt != null && coAt!.isNotEmpty;
  bool get sedangBerjalan => sudahClockIn && !sudahClockOut;
  bool get selesai => sudahClockOut;

  List<String> get clockOutPhotos {
    final list = <String>[];
    if (image1 != null && image1!.isNotEmpty) list.add(image1!);
    if (image2 != null && image2!.isNotEmpty) list.add(image2!);
    if (image3 != null && image3!.isNotEmpty) list.add(image3!);
    if (image4 != null && image4!.isNotEmpty) list.add(image4!);
    if (image5 != null && image5!.isNotEmpty) list.add(image5!);
    return list;
  }
}
