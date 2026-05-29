# Deploy ke Vercel

## Prasyarat
- Akun [Vercel](https://vercel.com) (gratis)
- Akun [GitHub](https://github.com) (gratis)
- [Git](https://git-scm.com) terinstall di komputer

---

## Langkah Deploy

### 1. Inisialisasi Git & Push ke GitHub

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/sikado

git init
git add .
git commit -m "first commit"
```

Buat repository baru di GitHub (jangan centang "Add README"), lalu:

```bash
git remote add origin https://github.com/USERNAME/NAMA-REPO.git
git branch -M main
git push -u origin main
```

---

### 2. Deploy ke Vercel

1. Buka [vercel.com](https://vercel.com) → **Sign in with GitHub**
2. Klik **Add New → Project**
3. Pilih repository yang baru kamu push
4. Di halaman konfigurasi:
   - **Framework Preset**: Other
   - **Root Directory**: `.` (biarkan default)
   - Tidak perlu mengubah apapun lagi
5. Klik **Deploy**

Vercel akan otomatis:
- Menjalankan `composer install`
- Mendeteksi `vercel.json` dan menggunakan PHP runtime
- Memberi URL publik seperti `https://nama-project.vercel.app`

---

## Catatan Penting

| Hal | Lokal (XAMPP) | Vercel |
|-----|---------------|--------|
| `dgsign_db.json` | Disimpan di `/tmp` OS lokal | Disimpan di `/tmp` Vercel (ephemeral) |
| File `.p12`, `.sig`, `.txt` | Disimpan di `/tmp` OS lokal | Disimpan di `/tmp` Vercel (ephemeral) |
| `.htaccess` | Aktif (Apache) | Tidak dipakai (Vercel handles routing) |

> ⚠️ Karena Vercel adalah serverless, file di `/tmp` (termasuk `dgsign_db.json`) bisa hilang saat *cold start* (~15 menit tidak ada request). Ini normal untuk demo/tugas.
>
> Untuk persistensi permanen di Vercel, gunakan layanan eksternal seperti **Vercel KV** (Redis) atau **Neon** (PostgreSQL).

---

## Update Setelah Perubahan

Setiap kali kamu ubah kode, cukup:

```bash
git add .
git commit -m "update"
git push
```

Vercel akan otomatis redeploy.
