import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_database/firebase_database.dart';

/// Realtime Database handle. Keep URL same as admin/config.php FIREBASE_DB_URL.
/// Firebase Console → Realtime Database → copy URL at top.
const String kDbUrl = 'https://scan2chat-e7e20-default-rtdb.asia-southeast1.firebasedatabase.app';

class Db {
  Db._();
  static FirebaseDatabase get instance =>
      FirebaseDatabase.instanceFor(app: Firebase.app(), databaseURL: kDbUrl);

  static DatabaseReference ref([String? path]) => instance.ref(path);
}
