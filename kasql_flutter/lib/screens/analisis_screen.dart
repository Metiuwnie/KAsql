import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:dio/dio.dart';
import 'package:flutter_markdown/flutter_markdown.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../services/auth_service.dart';
import '../services/api_service.dart';
import '../config/api_config.dart';
import '../config/app_theme.dart';
import '../widgets/app_drawer.dart';

class AnalisisScreen extends StatefulWidget {
  const AnalisisScreen({super.key});

  @override
  State<AnalisisScreen> createState() => _AnalisisScreenState();
}

class _AnalisisScreenState extends State<AnalisisScreen> {
  final _apiKeyController = TextEditingController();
  final _storage = const FlutterSecureStorage();
  
  String _selectedType = 'jurnal'; // jurnal, laba_rugi, neraca
  String _selectedTahun = DateTime.now().year.toString();
  String _selectedBulan = DateTime.now().month.toString().padLeft(2, '0');
  
  bool _isLoading = false;
  String? _resultMarkdown;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    // Menghapus API key yang mungkin sebelumnya tersimpan
    _storage.delete(key: 'gemini_api_key');
  }
  
  @override
  void dispose() {
    _apiKeyController.dispose();
    super.dispose();
  }

  Future<void> _generateAnalysis() async {
    final auth = context.read<AuthService>();
    if (!auth.isLoggedIn) return;
    
    final apiKey = _apiKeyController.text.trim();
    
    setState(() {
      _isLoading = true;
      _resultMarkdown = null;
      _errorMessage = null;
    });
    
    try {
      final dio = ApiService().dio;
      
      String endpoint = '';
      if (_selectedType == 'jurnal') endpoint = ApiConfig.analisisJurnal;
      else if (_selectedType == 'laba_rugi') endpoint = ApiConfig.analisisLabaRugi;
      else if (_selectedType == 'neraca') endpoint = ApiConfig.analisisNeraca;
      
      final response = await dio.post(
        endpoint,
        data: {
          'tahun': _selectedTahun,
          'bulan': _selectedBulan,
          'api_key': apiKey,
        },
      );
      
      if (response.data != null && response.data['status'] == 'success') {
        setState(() {
          _resultMarkdown = response.data['data'] as String;
        });
      } else {
        setState(() {
          _errorMessage = response.data['message'] ?? 'Gagal menghasilkan analisis';
        });
      }
    } on DioException catch (e) {
      setState(() {
        _errorMessage = e.response?.data?['message'] ?? e.message ?? 'Terjadi kesalahan jaringan';
      });
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    
    final bulanList = [
      {'value': '', 'label': 'Semua Bulan (Tahunan)'},
      {'value': '01', 'label': 'Januari'},
      {'value': '02', 'label': 'Februari'},
      {'value': '03', 'label': 'Maret'},
      {'value': '04', 'label': 'April'},
      {'value': '05', 'label': 'Mei'},
      {'value': '06', 'label': 'Juni'},
      {'value': '07', 'label': 'Juli'},
      {'value': '08', 'label': 'Agustus'},
      {'value': '09', 'label': 'September'},
      {'value': '10', 'label': 'Oktober'},
      {'value': '11', 'label': 'November'},
      {'value': '12', 'label': 'Desember'},
    ];
    
    final tahunList = List.generate(10, (index) => (DateTime.now().year - 5 + index).toString());

    return Scaffold(
      drawer: const AppDrawer(currentRoute: '/analisis'),
      appBar: AppBar(
        title: const Text('Analisis AI'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Settings Card
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: isDark ? AppTheme.surfaceCardDark : AppTheme.surfaceCardLight,
                borderRadius: BorderRadius.circular(20),
                boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 10, offset: Offset(0, 4))],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Parameter Analisis',
                    style: GoogleFonts.dmSans(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  // Tipe Laporan
                  const Text('Jenis Laporan', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    isExpanded: true,
                    value: _selectedType,
                    decoration: InputDecoration(
                      filled: true,
                      fillColor: isDark ? Colors.black26 : Colors.grey[100],
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    ),
                    items: const [
                      DropdownMenuItem(value: 'jurnal', child: Text('Jurnal Umum')),
                      DropdownMenuItem(value: 'laba_rugi', child: Text('Laba Rugi')),
                      DropdownMenuItem(value: 'neraca', child: Text('Neraca')),
                    ],
                    onChanged: (val) => setState(() => _selectedType = val!),
                  ),
                  const SizedBox(height: 16),
                  
                  // Tahun & Bulan
                  Row(
                    children: [
                      Expanded(
                        flex: 3,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Tahun', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            const SizedBox(height: 8),
                            DropdownButtonFormField<String>(
                              isExpanded: true,
                              value: _selectedTahun,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor: isDark ? Colors.black26 : Colors.grey[100],
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                              ),
                              items: tahunList.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
                              onChanged: (val) => setState(() => _selectedTahun = val!),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        flex: 4,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Bulan', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                            const SizedBox(height: 8),
                            DropdownButtonFormField<String>(
                              isExpanded: true,
                              value: _selectedBulan,
                              decoration: InputDecoration(
                                filled: true,
                                fillColor: isDark ? Colors.black26 : Colors.grey[100],
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                              ),
                              items: bulanList.map((b) => DropdownMenuItem(value: b['value'], child: Text(b['label']!))).toList(),
                              onChanged: (val) => setState(() => _selectedBulan = val!),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  
                  // API Key
                  const Text('Gemini API Key (Bila server belum mengatur)', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  TextField(
                    controller: _apiKeyController,
                    obscureText: true,
                    decoration: InputDecoration(
                      hintText: 'API key dari aistudio.google.com',
                      filled: true,
                      fillColor: isDark ? Colors.black26 : Colors.grey[100],
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  // Model Text
                  RichText(
                    text: TextSpan(
                      style: GoogleFonts.dmSans(fontSize: 13, color: isDark ? AppTheme.textSecondaryDark : AppTheme.textSecondaryLight),
                      children: const [
                        TextSpan(text: 'Akan dianalisis menggunakan model: '),
                        TextSpan(text: 'gemini-3.5-flash-lite', style: TextStyle(fontWeight: FontWeight.w700)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),
                  
                  // Disclaimer
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF3CD),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: const Color(0xFFFFE69C)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(Icons.info_outline_rounded, color: Color(0xFF664D03), size: 20),
                        const SizedBox(width: 10),
                        Expanded(
                          child: RichText(
                            text: TextSpan(
                              style: GoogleFonts.dmSans(fontSize: 12, color: const Color(0xFF664D03), height: 1.5),
                              children: const [
                                TextSpan(text: 'Disclaimer AI: ', style: TextStyle(fontWeight: FontWeight.w700)),
                                TextSpan(text: 'Laporan hasil AI ini merupakan analisis teks otomatis. AI mungkin mengalami "halusinasi" logika atau salah perhitungan matematis. Mohon jadikan hasil AI hanya sebagai '),
                                TextSpan(text: 'saran opini kualitatif', style: TextStyle(fontWeight: FontWeight.w700)),
                                TextSpan(text: '. Selalu berpatokan pada validasi angka dan status balance yang ada di sistem ini.'),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  
                  // Tombol Generate
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton.icon(
                      onPressed: _isLoading ? null : _generateAnalysis,
                      icon: _isLoading ? const SizedBox.shrink() : const Icon(Icons.auto_awesome_rounded, color: Colors.white, size: 20),
                      label: _isLoading 
                        ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Text('Analisis dengan Gemini AI', style: GoogleFonts.dmSans(fontWeight: FontWeight.w700, fontSize: 15)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF6366F1), // Indigo/Gemini-ish color
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 24),
            
            // Result Area
            if (_errorMessage != null)
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppTheme.danger.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppTheme.danger.withValues(alpha: 0.3)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.error_outline_rounded, color: AppTheme.danger),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(_errorMessage!, style: const TextStyle(color: AppTheme.danger)),
                    ),
                  ],
                ),
              ),
              
            if (_resultMarkdown != null)
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: isDark ? AppTheme.surfaceCardDark : AppTheme.surfaceCardLight,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 10, offset: Offset(0, 4))],
                  border: Border.all(color: AppTheme.teal.withValues(alpha: 0.2)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.auto_awesome_rounded, color: AppTheme.teal),
                        const SizedBox(width: 12),
                        Text(
                          'Hasil Analisis AI',
                          style: GoogleFonts.dmSans(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.teal,
                          ),
                        ),
                      ],
                    ),
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      child: Divider(),
                    ),
                    MarkdownBody(
                      data: _resultMarkdown!,
                      selectable: true,
                      styleSheet: MarkdownStyleSheet(
                        p: GoogleFonts.dmSans(fontSize: 14, height: 1.6),
                        h1: GoogleFonts.dmSans(fontSize: 20, fontWeight: FontWeight.w700),
                        h2: GoogleFonts.dmSans(fontSize: 18, fontWeight: FontWeight.w700),
                        h3: GoogleFonts.dmSans(fontSize: 16, fontWeight: FontWeight.w700),
                        strong: GoogleFonts.dmSans(fontWeight: FontWeight.w700),
                        listBullet: const TextStyle(height: 1.6),
                      ),
                    ),
                  ],
                ),
              ),
              
            // Spacing for scrolling
            const SizedBox(height: 40),
          ],
        ),
      ),
    );
  }
}
