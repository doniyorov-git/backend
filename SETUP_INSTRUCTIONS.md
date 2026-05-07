# My Diller UZ - O'rnatish Ko'rsatmalari

## 1. Database o'rnatish

MySQL databaseni yaratib, jadval va boshlang'ich admin yozuvini qo'shish uchun quyidagi skriptni bajaring:

```bash
mysql -u root < database.sql
```

Sotuvchi, diler, mahsulot va buyurtma ma'lumotlari tizimdan ro'yxatdan o'tish, katalog va buyurtma jarayonlari orqali yaratiladi.

## 2. Xususiyatlar

### Admin Panel:
- Barcha mahsulotlarni ko'rish
- Foydalanuvchilarni boshqarish
- Buyurtmalarni kuzatish
- Foto hisobotlarni ko'rish
- Komissiyalarni boshqarish

### Seller Panel:
- O'z mahsulotlarini yuklash (rasm bilan)
- Buyurtmalarni kuzatish
- Moliya hisobotlari
- Foto hisobotlarni kuylash

### Buyer (Diler):
- Mahsulotlar vitrinasida qidirish
- Savatga qo'shish
- Buyurtma berish
- Buyurtmalarni kuzatish
- Foto hisobotlarni kuylash

## 3. API Endpoints

### Authentication
- `POST /api/auth/login.php` - Kirish
- `POST /api/auth/register.php` - Ro'yxatdan o'tish
- `POST /api/auth/logout.php` - Chiqish
- `GET /api/auth/me.php` - Joriy foydalanuvchi ma'lumoti

### Admin
- `GET /api/admin/dashboard.php` - Dashboard ma'lumotlari
- `GET /api/admin/users.php` - Foydalanuvchilar ro'yxati
- `GET /api/admin/products.php` - Barcha mahsulotlar
- `GET /api/admin/commissions.php` - Komissiyalar

### Seller
- `GET /api/seller/products.php` - O'z mahsulotlari
- `POST /api/seller/products.php` - Mahsulot qo'shish
- `GET /api/seller/orders.php` - Buyurtmalar
- `GET /api/seller/reports.php` - Foto hisobotlar

### Buyer
- `GET /api/buyer/products.php` - Mahsulotlar qidirish
- `POST /api/buyer/orders.php` - Buyurtma berish
- `GET /api/buyer/orders.php` - O'z buyurtmalari

## 4. Rasm upload

- Mahsulot rasmlar: `/uploads/products/`
- Hisobot rasmlar: `/uploads/reports/`
- Rasm formati: JPG, PNG
- Maksimal o'lcham: 5MB
