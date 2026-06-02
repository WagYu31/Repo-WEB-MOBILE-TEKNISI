import 'package:flutter/material.dart';
import '../../service/api/ApiPelaksanaan.dart';
import '../../service/model/invoice/InvoiceModel.dart';

class CreateInvoicePage extends StatefulWidget {
  final String kegiatanId;
  final String kegiatanName;

  const CreateInvoicePage({
    required this.kegiatanId,
    this.kegiatanName = '',
    Key? key,
  }) : super(key: key);

  @override
  _CreateInvoicePageState createState() => _CreateInvoicePageState();
}

class _CreateInvoicePageState extends State<CreateInvoicePage> {
  final List<Item> _items = [];
  final _formKey = GlobalKey<FormState>();

  // Controllers untuk form input
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _qtyController = TextEditingController();
  final TextEditingController _priceController = TextEditingController();

  // State untuk loading submit
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _qtyController.dispose();
    _priceController.dispose();
    super.dispose();
  }

  void _addItem() {
    if (_formKey.currentState!.validate()) {
      setState(() {
        _items.add(Item(
          name: _nameController.text.trim(),
          qty: int.parse(_qtyController.text),
          price: double.parse(_priceController.text),
          kegiatanId: widget.kegiatanId,
        ));

        // Reset form
        _nameController.clear();
        _qtyController.clear();
        _priceController.clear();
      });
    }
  }

  void _removeItem(int index) {
    setState(() {
      _items.removeAt(index);
    });
  }

  Future<void> _submitItems() async {
    if (_items.isEmpty) return;

    setState(() => _isSubmitting = true);
    try {
      await ApiPelaksanaan.createItems(_items);
      _showSuccessSnackbar('${_items.length} item berhasil disimpan!');
      setState(() => _items.clear()); // Kosongkan list setelah submit
    } catch (e) {
      _showErrorSnackbar('Gagal menyimpan item: $e');
      print('ADA ERROR NIH BOS : $e');
    } finally {
      setState(() => _isSubmitting = false);
    }
  }

  void _showSuccessSnackbar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  void _showErrorSnackbar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  double _calculateTotal() {
    return _items.fold(0, (sum, item) => sum + (item.price * item.qty));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.kegiatanName.isNotEmpty
            ? 'Input Item - ${widget.kegiatanName}'
            : 'Input Item'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            // Form Input
            Card(
              elevation: 2,
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Form(
                  key: _formKey,
                  child: Column(
                    children: [
                      TextFormField(
                        controller: _nameController,
                        decoration: InputDecoration(
                          labelText: 'Nama Item',
                          border: OutlineInputBorder(),
                          filled: true,
                          fillColor: Colors.grey[50],
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Harap isi nama item';
                          }
                          return null;
                        },
                      ),
                      SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            flex: 2,
                            child: TextFormField(
                              controller: _qtyController,
                              decoration: InputDecoration(
                                labelText: 'Jumlah',
                                border: OutlineInputBorder(),
                                filled: true,
                                fillColor: Colors.grey[50],
                              ),
                              keyboardType: TextInputType.number,
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Harap isi jumlah';
                                }
                                if (int.tryParse(value) == null) {
                                  return 'Harap masukkan angka';
                                }
                                return null;
                              },
                            ),
                          ),
                          SizedBox(width: 12),
                          Expanded(
                            flex: 3,
                            child: TextFormField(
                              controller: _priceController,
                              decoration: InputDecoration(
                                labelText: 'Harga Satuan',
                                prefixText: 'Rp ',
                                border: OutlineInputBorder(),
                                filled: true,
                                fillColor: Colors.grey[50],
                              ),
                              keyboardType: TextInputType.number,
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Harap isi harga';
                                }
                                if (double.tryParse(value) == null) {
                                  return 'Harap masukkan angka';
                                }
                                return null;
                              },
                            ),
                          ),
                        ],
                      ),
                      SizedBox(height: 16),
                      ElevatedButton.icon(
                        onPressed: _addItem,
                        icon: Icon(Icons.add),
                        label: Text('Tambah Ke Daftar', style: TextStyle(color: Colors.white),),
                        style: ElevatedButton.styleFrom(
                          minimumSize: Size(double.infinity, 50),
                          backgroundColor: Theme.of(context).primaryColor,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),

            SizedBox(height: 16),

            // Info Jumlah Item dan Total
            if (_items.isNotEmpty) ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Jumlah Item: ${_items.length}',
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                  Text(
                    'Total: Rp ${_calculateTotal().toStringAsFixed(2)}',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.blue[700],
                    ),
                  ),
                ],
              ),
              SizedBox(height: 8),
            ],

            // Daftar Item
            Expanded(
              child: _items.isEmpty
                  ? Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.list_alt, size: 50, color: Colors.grey[400]),
                    SizedBox(height: 8),
                    Text(
                      'Daftar Item Kosong',
                      style: TextStyle(color: Colors.grey),
                    ),
                    Text(
                      'Tambahkan item menggunakan form di atas',
                      style: TextStyle(color: Colors.grey),
                    ),
                  ],
                ),
              )
                  : ListView.builder(
                itemCount: _items.length,
                itemBuilder: (context, index) {
                  final item = _items[index];
                  return Card(
                    margin: EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      contentPadding: EdgeInsets.symmetric(
                        vertical: 8,
                        horizontal: 16,
                      ),
                      leading: CircleAvatar(
                        child: Text('${index + 1}'),
                      ),
                      title: Text(
                        item.name,
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                      subtitle: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('${item.qty} x Rp ${item.price.toStringAsFixed(2)}'),
                          SizedBox(height: 4),
                          Container(
                            padding: EdgeInsets.symmetric(
                              vertical: 2,
                              horizontal: 6,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.blue[50],
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              'Rp ${(item.price * item.qty).toStringAsFixed(2)}',
                              style: TextStyle(
                                color: Colors.blue[800],
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ],
                      ),
                      trailing: IconButton(
                        icon: Icon(Icons.delete, color: Colors.red),
                        onPressed: () => _removeItem(index),
                      ),
                    ),
                  );
                },
              ),
            ),

            // Tombol Submit
            if (_items.isNotEmpty)
              Padding(
                padding: EdgeInsets.only(top: 8.0),
                child: ElevatedButton.icon(
                  onPressed: _isSubmitting ? null : _submitItems,
                  icon: _isSubmitting
                      ? SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                      color: Colors.white,
                      strokeWidth: 2,
                    ),
                  )
                      : Icon(Icons.save),
                  label: Text(_isSubmitting ? 'Menyimpan...' : 'Simpan Semua Item'),
                  style: ElevatedButton.styleFrom(
                    minimumSize: Size(double.infinity, 50),
                    backgroundColor: Colors.green,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}