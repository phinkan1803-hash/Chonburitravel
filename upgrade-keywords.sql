-- สำรองฐานข้อมูลก่อน ใช้กับฐานข้อมูลเวอร์ชันเดิมเพียงครั้งเดียว หากมี keywords อยู่แล้ว ให้ข้ามไฟล์นี้
USE chonburi_travel;
ALTER TABLE places ADD COLUMN keywords TEXT NULL;
