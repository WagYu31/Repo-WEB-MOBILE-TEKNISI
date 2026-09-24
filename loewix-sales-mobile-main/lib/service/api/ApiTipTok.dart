import 'dart:convert';
import 'package:http/http.dart' as http;
import 'ApiLink.dart';
import '../model/TipTokModel.dart';

class ApiTipTok {
  final List<String> _endpoints = [
    'https://sales.id-giti.com/modul-aplikasi-sales/api/api_sales_tiptok.php',
    'https://jadwal.id-giti.com/staff/sales/api_tiptok.php',
    '${Api.Url}/api_sales_tiptok.php',
  ];

  Future<Map<String, dynamic>> _fetchJson(String queryParams) async {
    String lastError = 'Gagal menghubungi server';
    for (final base in _endpoints) {
      try {
        final separator = base.contains('?') ? '&' : '?';
        final url = Uri.parse('$base$separator$queryParams');
        final res = await http.get(url).timeout(const Duration(seconds: 10));
        if (res.statusCode == 200) {
          final decoded = jsonDecode(res.body);
          if (decoded is Map<String, dynamic>) {
            return decoded;
          }
        }
      } catch (e) {
        lastError = e.toString();
      }
    }
    return {'status': 'error', 'message': lastError};
  }

  Future<Map<String, dynamic>> _postJson(String action, Map<String, dynamic> body) async {
    body['action'] = action;
    String lastError = 'Gagal menghubungi server';
    for (final base in _endpoints) {
      try {
        final separator = base.contains('?') ? '&' : '?';
        final url = Uri.parse('$base${separator}action=$action');
        final res = await http.post(
          url,
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode(body),
        ).timeout(const Duration(seconds: 12));
        if (res.statusCode == 200) {
          final decoded = jsonDecode(res.body);
          if (decoded is Map<String, dynamic>) {
            return decoded;
          }
        }
      } catch (e) {
        lastError = e.toString();
      }
    }
    return {'status': 'error', 'message': lastError};
  }

  /// Get Dashboard & List of Consignment
  Future<({TipTokMetricsModel metrics, List<TipTokPenitipanModel> list})> getDashboard(int salesId) async {
    final data = await _fetchJson('action=get_dashboard&sales_id=$salesId');
    if (data['status'] == 'success') {
      final metrics = TipTokMetricsModel.fromJson(data['data']['metrics'] ?? {});
      final rawList = data['data']['penitipan_list'] as List? ?? [];
      final list = rawList.map((e) => TipTokPenitipanModel.fromJson(e)).toList();
      return (metrics: metrics, list: list);
    }
    throw Exception(data['message'] ?? 'Gagal memuat data TIP TOK');
  }

  /// Get Dealers for Search / Autocomplete (Filtered by Sales Schedule)
  Future<List<Map<String, dynamic>>> getDealers({int salesId = 0, String search = ''}) async {
    final data = await _fetchJson('action=get_dealers&sales_id=$salesId&q=${Uri.encodeComponent(search)}');
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
    if (data['status'] == 'success' || data['success'] == true) {
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
    if (data['status'] == 'success' || data['success'] == true) {
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
    if (data['status'] == 'success' || data['success'] == true) {
      return data;
    }
    throw Exception(data['message'] ?? 'Gagal mengajukan klaim insentif');
  }

  /// Get Claims History
  Future<List<TipTokClaimModel>> getClaims(int salesId) async {
    final data = await _fetchJson('action=get_claims&sales_id=$salesId');
    if (data['status'] == 'success') {
      final rawList = data['data'] as List? ?? [];
      return rawList.map((e) => TipTokClaimModel.fromJson(e)).toList();
    }
    return [];
  }
}
