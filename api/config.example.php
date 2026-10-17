<?php
// คัดลอกเป็น config.php บนเซิร์ฟเวอร์เท่านั้น ห้ามอัปโหลดไฟล์จริงขึ้น GitHub
return [
 'dsn' => 'mysql:host=127.0.0.1;dbname=chonburi_travel;charset=utf8mb4',
 'db_user' => 'YOUR_DATABASE_USER',
 'db_password' => 'YOUR_DATABASE_PASSWORD',
 'admin_user' => 'admin',
 // สร้างด้วย php -r 'echo password_hash(readline("Password: "), PASSWORD_DEFAULT), PHP_EOL;'
 'admin_password_hash' => 'REPLACE_WITH_PASSWORD_HASH',
];
