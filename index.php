<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Strict']);
session_start();
function reply($data,int $code=200): void {http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
set_exception_handler(function(Throwable $e){error_log((string)$e);reply(['error'=>'ระบบขัดข้อง กรุณาตรวจสอบการตั้งค่าฐานข้อมูลบนเซิร์ฟเวอร์'],500);});
if(!file_exists(__DIR__.'/config.php')) reply(['error'=>'ยังไม่ได้ตั้งค่า api/config.php'],503);
$config=require __DIR__.'/config.php';
$action=$_GET['action']??'places';$method=$_SERVER['REQUEST_METHOD'];
$read=['places','session'];$write=['login','logout','save','delete'];
if(!in_array($action,array_merge($read,$write),true))reply(['error'=>'ไม่พบคำสั่ง'],404);
if(($method!=='GET' && in_array($action,$read,true))||($method!=='POST' && in_array($action,$write,true)))reply(['error'=>'Method not allowed'],405);
$input=[];
if($method==='POST') {if((int)($_SERVER['CONTENT_LENGTH']??0)>40000)reply(['error'=>'ข้อมูลมีขนาดใหญ่เกินไป'],413);$input=json_decode(file_get_contents('php://input'),true);if(!is_array($input))reply(['error'=>'ข้อมูลไม่ถูกต้อง'],400);}
if($action==='session')reply(['csrf'=>!empty($_SESSION['admin'])?($_SESSION['csrf']??null):null]);
if($action==='login') {
 // ลดการเดารหัสผ่านในแต่ละ session; โฮสติ้งจริงควรเพิ่ม rate limit ที่ reverse proxy ตาม IP
 if(($_SESSION['retry_after']??0)>time())reply(['error'=>'ลองเข้าสู่ระบบใหม่ในอีก 30 วินาที'],429);
 if(!is_string($input['username']??null)||!is_string($input['password']??null))reply(['error'=>'ข้อมูลไม่ถูกต้อง'],400);
 if(!hash_equals($config['admin_user'],$input['username'])||!password_verify($input['password'],$config['admin_password_hash'])){$_SESSION['retry_after']=time()+30;reply(['error'=>'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'],401);}
 session_regenerate_id(true);$_SESSION['admin']=true;$_SESSION['csrf']=bin2hex(random_bytes(32));reply(['csrf'=>$_SESSION['csrf']]);
}
if(in_array($action,['save','delete','logout'],true)) {
 if(empty($_SESSION['admin']))reply(['error'=>'กรุณาเข้าสู่ระบบ'],401);
 if(!hash_equals($_SESSION['csrf']??'',$_SERVER['HTTP_X_CSRF_TOKEN']??''))reply(['error'=>'สิทธิ์การทำรายการไม่ถูกต้อง'],403);
}
if($action==='logout'){$_SESSION=[];session_destroy();reply(['ok'=>true]);}
$db=new PDO($config['dsn'],$config['db_user'],$config['db_password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if($action==='places')reply($db->query('SELECT * FROM places ORDER BY name')->fetchAll());
if($action==='delete'){$id=$input['id']??'';if(!is_string($id)||!preg_match('/^[a-z0-9-]{1,64}$/',$id))reply(['error'=>'รหัสไม่ถูกต้อง'],422);$st=$db->prepare('DELETE FROM places WHERE id=?');$st->execute([$id]);if(!$st->rowCount())reply(['error'=>'ไม่พบสถานที่'],404);reply(['ok'=>true]);}
if($action==='save'){
 $limits=['name'=>200,'category'=>100,'district'=>100,'history'=>5000,'highlight'=>5000,'address'=>500,'source'=>2000,'image'=>2000,'keywords'=>2000];$values=[];
 foreach($limits as $field=>$limit){$v=$input[$field]??'';if(!is_string($v))reply(['error'=>'รูปแบบข้อมูลไม่ถูกต้อง'],422);$v=trim($v);if(($v===''&&!in_array($field,['image','keywords'],true))||mb_strlen($v)>$limit)reply(['error'=>'กรุณาตรวจสอบข้อมูล '.$field],422);$values[$field]=$v;}
 if(!in_array($values['category'],['ทะเลและเกาะ','ธรรมชาติ','วัฒนธรรม','ชุมชนและตลาด','นันทนาการ','วัดและศาสนสถาน','คาเฟ่','พิพิธภัณฑ์และแหล่งเรียนรู้'],true))reply(['error'=>'ประเภทไม่ถูกต้อง'],422);
 foreach(['image','source'] as $f){if($values[$f]!==''&&(!filter_var($values[$f],FILTER_VALIDATE_URL)||parse_url($values[$f],PHP_URL_SCHEME)!=='https'))reply(['error'=>'URL ต้องขึ้นต้นด้วย https://'],422);}
 $id=$input['id']??'';if(!is_string($id)||($id!==''&&!preg_match('/^[a-z0-9-]{1,64}$/',$id)))reply(['error'=>'รหัสไม่ถูกต้อง'],422);
 if($id===''){$id=bin2hex(random_bytes(12));$st=$db->prepare('INSERT INTO places (name,category,district,history,highlight,address,source,image,keywords,id,art) VALUES (?,?,?,?,?,?,?,?,?,?,?)');$st->execute([...array_values($values),$id,'wave']);}
 else{$check=$db->prepare('SELECT id FROM places WHERE id=?');$check->execute([$id]);if(!$check->fetch())reply(['error'=>'ไม่พบสถานที่'],404);$st=$db->prepare('UPDATE places SET name=?,category=?,district=?,history=?,highlight=?,address=?,source=?,image=?,keywords=? WHERE id=?');$st->execute([...array_values($values),$id]);}
 reply(['ok'=>true,'id'=>$id]);
}
