import 'dart:convert';
import 'dart:io';

/// Uploads a photo to imgbb.com, returns the public image URL (or null on failure).
class ImgbbService {
  ImgbbService._();

  // NOTE: key lives inside the app. Anyone can extract it from the APK.
  static const String _apiKey = 'e099f2a9b904eec11a75f4b013cbbdbb';

  static Future<String?> upload(File file) async {
    final client = HttpClient()..connectionTimeout = const Duration(seconds: 15);
    try {
      final bytes = await file.readAsBytes();
      final body = utf8.encode('image=${Uri.encodeQueryComponent(base64Encode(bytes))}');

      final req = await client.postUrl(Uri.parse('https://api.imgbb.com/1/upload?key=$_apiKey'));
      req.headers.contentType =
          ContentType('application', 'x-www-form-urlencoded', charset: 'utf-8');
      req.contentLength = body.length;
      req.add(body);

      final res = await req.close().timeout(const Duration(seconds: 60));
      final text = await res.transform(utf8.decoder).join();
      if (res.statusCode != 200) return null;

      final json = jsonDecode(text);
      final url = json?['data']?['url'];
      return url is String && url.isNotEmpty ? url : null;
    } catch (_) {
      return null;
    } finally {
      client.close(force: true);
    }
  }
}
