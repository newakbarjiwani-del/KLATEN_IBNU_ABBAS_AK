-- Tambah kolom password pada cyber_key.
-- Aman untuk data yang sudah ada: hanya menambah kolom, tidak menghapus,
-- tidak mengubah, dan tidak mengosongkan baris atau kolom lain.
-- Kolom dibiarkan NULL supaya akun lama tetap utuh sampai password diisi.
-- Jalankan sekali. Jika kolom sudah ada, perintah ini gagal tanpa mengubah data.

ALTER TABLE `cyber_key`
    ADD COLUMN `password` VARCHAR(255) NULL DEFAULT NULL AFTER `kunci`;
