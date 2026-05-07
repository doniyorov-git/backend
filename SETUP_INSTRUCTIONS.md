# My Diller UZ - O'rnatish Ko'rsatmalari

## 1. Database O'rnatish

MySQL databaseni yaratib, quyidagi SQL skriptni bajaring:

```bash
# Database va jadvallarini yaratish
mysql -u root < database.sql
```

## 2. Boshlang'ich kirish

### Admin hisobi:
- **Telefon:** 998901234567
- **Parol:** admin
- **Rol:** Admin

Sotuvchi va dilerlar tizimga ro'yxatdan o'tish orqali qo'shiladi. Mahsulotlar katalog orqali, buyurtmalar esa diler savati orqali yig'iladi.

## 3. Xususiyatlar

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

## 4. API Endpoints

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

## 5. Rasm Upload

- Mahsulot rasmlar: `/uploads/products/` 
- Hisobot rasmlar: `/uploads/reports/`
- Rasm formati: JPG, PNG
- Maksimal o'lcham: 5MB
