import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';
import '../utils/session_manager.dart';

class ApiResponse {
  final bool isSuccess;
  final int statusCode;
  final dynamic data;
  final String message;

  ApiResponse({
    required this.isSuccess,
    required this.statusCode,
    this.data,
    required this.message,
  });
}

class ApiClient {
  static Future<Map<String, String>> _getHeaders({bool isMultipart = false}) async {
    final token = await SessionManager.getToken();
    final headers = <String, String>{
      'Accept': 'application/json',
    };
    if (!isMultipart) {
      headers['Content-Type'] = 'application/json';
    }
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    return headers;
  }

  // GET
  static Future<ApiResponse> get(String endpoint, {Map<String, String>? queryParams}) async {
    try {
      final baseUrl = await SessionManager.getBaseUrl();
      var uri = Uri.parse('$baseUrl$endpoint');
      if (queryParams != null && queryParams.isNotEmpty) {
        uri = uri.replace(queryParameters: queryParams);
      }
      final headers = await _getHeaders();

      final response = await http.get(uri, headers: headers).timeout(const Duration(seconds: 15));
      return _handleResponse(response);
    } catch (e) {
      return ApiResponse(isSuccess: false, statusCode: 500, message: 'Gagal terhubung ke server: ${e.toString()}');
    }
  }

  // POST
  static Future<ApiResponse> post(String endpoint, {Map<String, dynamic>? body}) async {
    try {
      final baseUrl = await SessionManager.getBaseUrl();
      final uri = Uri.parse('$baseUrl$endpoint');
      final headers = await _getHeaders();

      final response = await http
          .post(uri, headers: headers, body: body != null ? jsonEncode(body) : null)
          .timeout(const Duration(seconds: 15));
      return _handleResponse(response);
    } catch (e) {
      return ApiResponse(isSuccess: false, statusCode: 500, message: 'Gagal terhubung ke server: ${e.toString()}');
    }
  }

  // PUT
  static Future<ApiResponse> put(String endpoint, {Map<String, dynamic>? body}) async {
    try {
      final baseUrl = await SessionManager.getBaseUrl();
      final uri = Uri.parse('$baseUrl$endpoint');
      final headers = await _getHeaders();

      final response = await http
          .put(uri, headers: headers, body: body != null ? jsonEncode(body) : null)
          .timeout(const Duration(seconds: 15));
      return _handleResponse(response);
    } catch (e) {
      return ApiResponse(isSuccess: false, statusCode: 500, message: 'Gagal terhubung ke server: ${e.toString()}');
    }
  }

  // MULTIPART (Upload Foto Kamera / Dokumen)
  static Future<ApiResponse> multipart({
    required String endpoint,
    required String method,
    Map<String, String>? fields,
    String? fileFieldName,
    File? file,
  }) async {
    try {
      final baseUrl = await SessionManager.getBaseUrl();
      final uri = Uri.parse('$baseUrl$endpoint');
      final headers = await _getHeaders(isMultipart: true);

      final request = http.MultipartRequest(method, uri);
      request.headers.addAll(headers);

      if (fields != null) {
        request.fields.addAll(fields);
      }

      if (file != null && fileFieldName != null) {
        final fileName = file.path.split(Platform.pathSeparator).last;
        String mimeType = 'image/jpeg';
        if (fileName.toLowerCase().endsWith('.png')) {
          mimeType = 'image/png';
        } else if (fileName.toLowerCase().endsWith('.pdf')) {
          mimeType = 'application/pdf';
        }

        final parts = mimeType.split('/');
        final multipartFile = await http.MultipartFile.fromPath(
          fileFieldName,
          file.path,
          filename: fileName,
          contentType: MediaType(parts[0], parts[1]),
        );
        request.files.add(multipartFile);
      }

      final streamedResponse = await request.send().timeout(const Duration(seconds: 30));
      final response = await http.Response.fromStream(streamedResponse);
      return _handleResponse(response);
    } catch (e) {
      return ApiResponse(isSuccess: false, statusCode: 500, message: 'Gagal mengunggah file: ${e.toString()}');
    }
  }

  // Handle Response helper
  static ApiResponse _handleResponse(http.Response response) {
    dynamic body;
    try {
      body = jsonDecode(response.body);
    } catch (_) {
      body = response.body;
    }

    final statusCode = response.statusCode;
    if (statusCode >= 200 && statusCode < 300) {
      String msg = 'Berhasil';
      if (body is Map && body.containsKey('message')) {
        msg = body['message'].toString();
      }
      return ApiResponse(isSuccess: true, statusCode: statusCode, data: body, message: msg);
    } else {
      String errorMsg = 'Terjadi kesalahan ($statusCode)';
      if (body is Map) {
        if (body.containsKey('message')) {
          errorMsg = body['message'].toString();
        } else if (body.containsKey('errors')) {
          final errors = body['errors'];
          if (errors is Map) {
            errorMsg = errors.values.map((e) => (e is List) ? e.join(', ') : e.toString()).join('\n');
          }
        }
      }
      return ApiResponse(isSuccess: false, statusCode: statusCode, data: body, message: errorMsg);
    }
  }
}
