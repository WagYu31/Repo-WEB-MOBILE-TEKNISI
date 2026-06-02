class Item {
  final String name;
  final int qty;
  final double price;
  final String kegiatanId;

  Item({
    required this.name,
    required this.qty,
    required this.price,
    required this.kegiatanId,
  });

  Map<String, dynamic> toJson() {
    return {
      'name': name,
      'qty': qty,
      'price': price,
      'kegiatan_id': kegiatanId,
    };
  }
}