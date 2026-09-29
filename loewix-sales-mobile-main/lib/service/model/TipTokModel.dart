class TipTokItemModel {
  final int id;
  final int idPenitipan;
  final String kodeTitip;
  final String namaBarang;
  final String tipeBarang;
  final int qtyTitip;
  final int qtySisa;
  final int qtyTerjual;
  final double insentifPerUnit;
  final double totalInsentif;
  final String statusItem;

  TipTokItemModel({
    required this.id,
    required this.idPenitipan,
    required this.kodeTitip,
    required this.namaBarang,
    required this.tipeBarang,
    required this.qtyTitip,
    required this.qtySisa,
    required this.qtyTerjual,
    required this.insentifPerUnit,
    required this.totalInsentif,
    required this.statusItem,
  });

  factory TipTokItemModel.fromJson(Map<String, dynamic> json) {
    return TipTokItemModel(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      idPenitipan: int.tryParse(json['id_penitipan']?.toString() ?? '0') ?? 0,
      kodeTitip: json['kode_titip']?.toString() ?? '',
      namaBarang: json['nama_barang']?.toString() ?? '',
      tipeBarang: json['tipe_barang']?.toString() ?? '',
      qtyTitip: int.tryParse(json['qty_titip']?.toString() ?? '0') ?? 0,
      qtySisa: int.tryParse(json['qty_sisa']?.toString() ?? '0') ?? 0,
      qtyTerjual: int.tryParse(json['qty_terjual']?.toString() ?? '0') ?? 0,
      insentifPerUnit: double.tryParse(json['insentif_per_unit']?.toString() ?? '0') ?? 0.0,
      totalInsentif: double.tryParse(json['total_insentif']?.toString() ?? '0') ?? 0.0,
      statusItem: json['status_item']?.toString() ?? 'titip',
    );
  }
}

class TipTokPenitipanModel {
  final int id;
  final String kodeTitip;
  final int idCustomer;
  final String namaToko;
  final String alamatToko;
  final String kotaToko;
  final String telpToko;
  final String kategoriCustomer;
  final String tglTitip;
  final String status;
  final String catatan;
  final int sumTitip;
  final int sumSisa;
  final int sumTerjual;
  final double sumInsentif;
  final String lastNoInv;
  final String lastKunjungan;
  final int unclaimedUnits;
  final double unclaimedNominal;
  final bool isClaimEligible;
  final double claimProgress;
  final int sisaUnitKlaim;
  final List<TipTokItemModel> items;

  TipTokPenitipanModel({
    required this.id,
    required this.kodeTitip,
    required this.idCustomer,
    required this.namaToko,
    required this.alamatToko,
    required this.kotaToko,
    required this.telpToko,
    required this.kategoriCustomer,
    required this.tglTitip,
    required this.status,
    required this.catatan,
    required this.sumTitip,
    required this.sumSisa,
    required this.sumTerjual,
    required this.sumInsentif,
    required this.lastNoInv,
    required this.lastKunjungan,
    this.unclaimedUnits = 0,
    this.unclaimedNominal = 0.0,
    this.isClaimEligible = false,
    this.claimProgress = 0.0,
    this.sisaUnitKlaim = 50,
    required this.items,
  });

  factory TipTokPenitipanModel.fromJson(Map<String, dynamic> json) {
    var rawItems = json['items'] as List? ?? [];
    List<TipTokItemModel> itemList = rawItems.map((e) => TipTokItemModel.fromJson(e)).toList();

    final unUnits = int.tryParse(json['unclaimed_units']?.toString() ?? '0') ?? 0;
    final unNominal = double.tryParse(json['unclaimed_nominal']?.toString() ?? '0') ?? 0.0;
    final isEligible = json['is_claim_eligible'] == true || unUnits >= 50;
    final progress = double.tryParse(json['claim_progress']?.toString() ?? '') ?? ((unUnits / 50.0) * 100.0).clamp(0.0, 100.0);
    final sisa = int.tryParse(json['sisa_unit_klaim']?.toString() ?? '') ?? (50 - unUnits).clamp(0, 50);

    return TipTokPenitipanModel(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      kodeTitip: json['kode_titip']?.toString() ?? '',
      idCustomer: int.tryParse(json['id_customer']?.toString() ?? '0') ?? 0,
      namaToko: json['nama_toko']?.toString() ?? 'Toko Tanpa Nama',
      alamatToko: json['alamat_toko']?.toString() ?? '',
      kotaToko: json['kota_toko']?.toString() ?? '',
      telpToko: json['telp_toko']?.toString() ?? '',
      kategoriCustomer: json['kategori_customer']?.toString() ?? 'Dealer',
      tglTitip: json['tgl_titip']?.toString() ?? '',
      status: json['status']?.toString() ?? 'aktif',
      catatan: json['catatan']?.toString() ?? '',
      sumTitip: int.tryParse(json['sum_titip']?.toString() ?? '0') ?? 0,
      sumSisa: int.tryParse(json['sum_sisa']?.toString() ?? '0') ?? 0,
      sumTerjual: int.tryParse(json['sum_terjual']?.toString() ?? '0') ?? 0,
      sumInsentif: double.tryParse(json['sum_insentif']?.toString() ?? '0') ?? 0.0,
      lastNoInv: json['last_no_inv']?.toString() ?? '',
      lastKunjungan: json['last_kunjungan']?.toString() ?? '',
      unclaimedUnits: unUnits,
      unclaimedNominal: unNominal,
      isClaimEligible: isEligible,
      claimProgress: progress,
      sisaUnitKlaim: sisa,
      items: itemList,
    );
  }
}

class TipTokMetricsModel {
  final int totalTokoAktif;
  final int totalTitip;
  final int totalSisa;
  final int totalTerjual;
  final double totalInsentif;
  final int unclaimedUnits;
  final double unclaimedNominal;
  final int eligibleStoresCount;
  final double eligibleNominalTotal;
  final int claimTarget;
  final double claimProgress;
  final bool isClaimEligible;

  TipTokMetricsModel({
    required this.totalTokoAktif,
    required this.totalTitip,
    required this.totalSisa,
    required this.totalTerjual,
    required this.totalInsentif,
    required this.unclaimedUnits,
    required this.unclaimedNominal,
    this.eligibleStoresCount = 0,
    this.eligibleNominalTotal = 0.0,
    required this.claimTarget,
    required this.claimProgress,
    required this.isClaimEligible,
  });

  factory TipTokMetricsModel.fromJson(Map<String, dynamic> json) {
    final unUnits = int.tryParse(json['unclaimed_units']?.toString() ?? '0') ?? 0;
    final elCount = int.tryParse(json['eligible_stores_count']?.toString() ?? '0') ?? (unUnits >= 50 ? 1 : 0);
    return TipTokMetricsModel(
      totalTokoAktif: int.tryParse(json['total_toko_aktif']?.toString() ?? '0') ?? 0,
      totalTitip: int.tryParse(json['total_titip']?.toString() ?? '0') ?? 0,
      totalSisa: int.tryParse(json['total_sisa']?.toString() ?? '0') ?? 0,
      totalTerjual: int.tryParse(json['total_terjual']?.toString() ?? '0') ?? 0,
      totalInsentif: double.tryParse(json['total_insentif']?.toString() ?? '0') ?? 0.0,
      unclaimedUnits: unUnits,
      unclaimedNominal: double.tryParse(json['unclaimed_nominal']?.toString() ?? '0') ?? 0.0,
      eligibleStoresCount: elCount,
      eligibleNominalTotal: double.tryParse(json['eligible_nominal_total']?.toString() ?? '0') ?? 0.0,
      claimTarget: int.tryParse(json['claim_target']?.toString() ?? '50') ?? 50,
      claimProgress: double.tryParse(json['claim_progress']?.toString() ?? '0') ?? 0.0,
      isClaimEligible: json['is_claim_eligible'] == true || elCount > 0,
    );
  }
}

class TipTokClaimModel {
  final int id;
  final String kodeClaim;
  final int? idPenitipan;
  final String? namaToko;
  final String tglClaim;
  final int totalUnitTerjual;
  final double totalNominalInsentif;
  final String statusClaim;
  final String? tglCair;
  final String? catatanClaim;
  final String? catatanAdmin;

  TipTokClaimModel({
    required this.id,
    required this.kodeClaim,
    this.idPenitipan,
    this.namaToko,
    required this.tglClaim,
    required this.totalUnitTerjual,
    required this.totalNominalInsentif,
    required this.statusClaim,
    this.tglCair,
    this.catatanClaim,
    this.catatanAdmin,
  });

  factory TipTokClaimModel.fromJson(Map<String, dynamic> json) {
    return TipTokClaimModel(
      id: int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      kodeClaim: json['kode_claim']?.toString() ?? '',
      idPenitipan: int.tryParse(json['id_penitipan']?.toString() ?? ''),
      namaToko: json['nama_toko']?.toString() ?? json['display_nama_toko']?.toString(),
      tglClaim: json['tgl_claim']?.toString() ?? '',
      totalUnitTerjual: int.tryParse(json['total_unit_terjual']?.toString() ?? '0') ?? 0,
      totalNominalInsentif: double.tryParse(json['total_nominal_insentif']?.toString() ?? '0') ?? 0.0,
      statusClaim: json['status_claim']?.toString() ?? 'menunggu_approval',
      tglCair: json['tgl_cair']?.toString(),
      catatanClaim: json['catatan_claim']?.toString(),
      catatanAdmin: json['catatan_admin']?.toString(),
    );
  }
}
