# My Diller UZ - O'rnatish Ko'rsatmalari

## 1. Database O'rnatish

MySQL databaseni yaratib, quyidagi SQL skriptlarni bajaring:

```bash
# Database va jadvallarini yaratish
mysql -u root < database.sql

# Test ma'lumotlarini qo'shish
mysql -u root < insert-test-data.sql
```

## 2. Test Ma'lumotlari

### Admin hisobi:
- **Telefon:** 998901234567
- **Parol:** admin
- **Rol:** Admin

### Seller hisobi:
- **Telefon:** 998901234567
- **Parol:** 123456
- **Rol:** Seller

### Buyer hisobi:
- **ID:** u_buyer1
- **Telefon:** 998903234567
- **Parol:** 123456
- **Rol:** Buyer

## 3. Test Mahsulotlari

Databaseda 3 ta test mahsulot qo'shildi:
- Monitor 24" (MON-001) - 500,000 so'm
- Ofis Stoli (DSK-001) - 1,000,000 so'm
- Qurilish Mixi (BLD-001) - 250,000 so'm

Rasmlar `/uploads/products/` papkasida saqlangan.

## 4. Xususiyatlar

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

## 5. API Endpoints

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

## 6. Rasm Upload

- Mahsulot rasmlar: `/uploads/products/` 
- Hisobot rasmlar: `/uploads/reports/`
- Rasm formati: JPG, PNG
- Maksimal o'lcham: 5MB

## 7. Debug

Browser console-da `[v0]` xabarlarni ko'ring:
```javascript
console.log("[v0] Message")
```
