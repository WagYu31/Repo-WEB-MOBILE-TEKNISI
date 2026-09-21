import 'dart:convert';
import 'package:http/http.dart' as http;
import 'ApiLink.dart';
import '../model/TipTokModel.dart';

class ApiTipTok {
  final String _base = Api.Url;
  final String _fallbackBase = 'https://jadwal.id-giti.com/staff/sales';

  Future<Map<String, dynamic>> _fetchJson(String endpoint) async {
    try {
      final res = await http.get(Uri.parse('$_base/$endpoint')).timeout(const Duration(seconds: 12));
      if (res.statusCode == 200) {
        return jsonDecode(res.body);
      }
    } catch (_) {}

    // Fallback URL
    final resFallback = await http.get(Uri.parse('$_fallbackBase/api_tiptok.php?${endpoint.split('?').length > 1 ? endpoint.split('?')[1] : ''}')).timeout(const Duration(seconds: 12));
    return jsonDecode(resFallback.body);
  }

  Future<Map<String, dynamic>> _postJson(String action, Map<String, dynamic> body) async {
    body['action'] = action;
    try {
      final res = await http.post(
        Uri.parse('$_base/api_sales_tiptok.php?action=$action'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(body),
      ).timeout(const Duration(seconds: 15));
      if (res.statusCode == 200) {
        return jsonDecode(res.body);
      }
    } catch (_) {}

    // Fallback URL
    final resFallback = await http.post(
      Uri.parse('$_fallbackBase/api_tiptok.php?action=$action'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(body),
    ).timeout(const Duration(seconds: 15));
    return jsonDecode(resFallback.body);
  }

  /// Get Dashboard & List of Consignment
  Future<({TipTokMetricsModel metrics, List<TipTokPenitipanModel> list})> getDashboard(int salesId) async {
    final data = await _fetchJson('api_sales_tiptok.php?action=get_dashboard&sales_id=$salesId');
    if (data['status'] == 'success') {
      final metrics = TipTokMetricsModel.fromJson(data['data']['metrics'] ?? {});
      final rawList = data['data']['penitipan_list'] as List? ?? [];
      final list = rawList.map((e) => TipTokPenitipanModel.fromJson(e)).toList();
      return (metrics: metrics, list: list);
    }
    throw Exception(data['message'] ?? 'Gagal memuat data TIP TOK');
  }

  /// Get Dealers for Search / Autocomplete
  Future<List<Map<String, dynamic>>> getDealers({String search = ''}) async {
    final data = await _fetchJson('api_sales_tiptok.php?action=get_dealers&q=${Uri.encodeComponent(search)}');
    if (data['status'] == 'success') {
      return List<Map<String, dynamic>>.from(data['data'] ?? []);
    }
    return [];
  }

  /// Create new consignment
  Future<Map<String, dynamic>> createPenitipan({
    required int idCustomer,
    required int idSales,
    required String namaSales,
    required String tglTitip,
    required String catatan,
    required List<Map<String, dynamic>> items,
  }) async {
    final data = await _postJson('create_penitipan', {
      'id_customer': idCustomer,
      'id_sales': idSales,
      'nama_sales': namaSales,
      'tgl_titip': tglTitip,
      'catatan': catatan,
      'items': items,
    });
    if (data['status'] == 'success') {
      return data;
    }
    throw Exception(data['message'] ?? 'Gagal menyimpan penitipan barang');
  }

  /// Audit store visit & stock count
  Future<Map<String, dynamic>> auditKunjungan({
    required int idPenitipan,
    required int idSales,
    required String namaSales,
    required String tglKunjungan,
    required String catatan,
    required List<Map<String, dynamic>> items,
  }) async {
    final data = await _postJson('audit_kunjungan', {
      'id_penitipan': idPenitipan,
      'id_sales': idSales,
      'nama_sales': namaSales,
      'tgl_kunjungan': tglKunjungan,
      'catatan': catatan,
      'items': items,
    });
    if (data['status'] == 'success') {
      return data;
    }
    throw Exception(data['message'] ?? 'Gagal menyimpan audit kunjungan');
  }

  /// Submit Incentive Claim
  Future<Map<String, dynamic>> claimInsentif({
    required int salesId,
    required String namaSales,
    required String catatanClaim,
  }) async {
    final data = await _postJson('claim_insentif', {
      'sales_id': salesId,
      'nama_sales': namaSales,
      'catatan_claim': catatanClaim,
    });
    if (data['status'] == 'success') {
      return data;
    }
    throw Exception(data['message'] ?? 'Gagal mengajukan klaim insentif');
  }

  /// Get Claims History
  Future<List<TipTokClaimModel>> getClaims(int salesId) async {
    final data = await _fetchJson('api_sales_tiptok.php?action=get_claims&sales_id=$salesId');
    if (data['status'] == 'success') {
      final rawList = data['data'] as List? ?? [];
      return rawList.map((e) => TipTokClaimModel.fromJson(e)).toList();
    }
    return [];
  }
}
