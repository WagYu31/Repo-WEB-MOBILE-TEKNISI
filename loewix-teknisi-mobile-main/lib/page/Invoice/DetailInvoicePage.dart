import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:teknisi_loewix/page/Invoice/InvoicePage.dart';
import 'package:teknisi_loewix/service/api/ApiPelaksanaan.dart';

import '../../service/model/invoice/DetailInvoiceModel.dart'; // Untuk formatting date dan currency

class DetailInvoicePage extends StatefulWidget {
  final String invoiceId;

  const DetailInvoicePage({Key? key, required this.invoiceId}) : super(key: key);

  @override
  _DetailInvoicePageState createState() => _DetailInvoicePageState();
}

class _DetailInvoicePageState extends State<DetailInvoicePage> {
  late Future<DetailInvoiceResponse> _invoiceFuture;
  final NumberFormat _currencyFormat = NumberFormat.currency(
    locale: 'id_ID',
    symbol: 'Rp',
    decimalDigits: 0,
  );
  final DateFormat _dateFormat = DateFormat('dd MMMM yyyy HH:mm', 'id_ID');

  @override
  void initState() {
    super.initState();
    _invoiceFuture = ApiPelaksanaan().getDetailInvoice(widget.invoiceId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detail Invoice'),
        centerTitle: true,
        actions: [
          IconButton(
            onPressed: (){
              Navigator.push(context, MaterialPageRoute(builder: (_) => CreateInvoicePage(kegiatanId: widget.invoiceId)));
            }, 
            icon: Icon(Icons.edit)
          )
        ],
      ),
      body: FutureBuilder<DetailInvoiceResponse>(
        future: _invoiceFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          } else if (snapshot.hasError) {
            return Center(
              child: Text('Belum ada invoice di kegiatan ini'),
            );
          } else if (!snapshot.hasData || snapshot.data!.data.isEmpty) {
            return const Center(child: Text('Tidak ada data invoice'));
          } else {
            final invoiceItems = snapshot.data!.data;
            final totalAmount = invoiceItems.fold(
              0,
              (sum, item) => sum + (item.qty * item.price),
            );

            return Column(
              children: [
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: invoiceItems.length,
                    itemBuilder: (context, index) {
                      final item = invoiceItems[index];
                      return _buildInvoiceItem(item);
                    },
                  ),
                ),
                _buildTotalSection(totalAmount),
              ],
            );
          }
        },
      ),
    );
  }

  Widget _buildInvoiceItem(DataInvoice item) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  item.name,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(
                  'Kode: ${item.kegiatanKode}',
                  style: TextStyle(
                    color: Colors.grey[600],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${item.qty} x ${_currencyFormat.format(item.price)}',
                  style: const TextStyle(fontSize: 14),
                ),
                Text(
                  _currencyFormat.format(item.qty * item.price),
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              'Dibuat: ${_dateFormat.format(item.createdAt)}',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
              ),
            ),
            Text(
              'Diupdate: ${_dateFormat.format(item.updatedAt)}',
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTotalSection(int totalAmount) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        border: Border(
          top: BorderSide(
            color: Colors.grey[300]!,
            width: 1,
          ),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          const Text(
            'Total:',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            _currencyFormat.format(totalAmount),
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.blue,
            ),
          ),
        ],
      ),
    );
  }
}