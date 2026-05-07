const STORAGE_KEY = "myDillerUzStateV2";
        const SESSION_KEY = "myDillerUzCurrentUser";
        const PAGE_TYPE = document.body.dataset.page || "auth";
        const PAGE_ROLE = document.body.dataset.role || "";
        const ROOT_PATH = document.body.dataset.root || ".";

        function rootUrl(path = "") {
            const root = ROOT_PATH.replace(/\/$/, "");
            const cleanPath = String(path || "").replace(/^\//, "");
            return cleanPath ? `${root}/${cleanPath}` : root;
        }

        function imageUrl(imagePath = "") {
            if (!imagePath) return "";
            // If it's an absolute URL, return as is
            if (imagePath.startsWith("http")) return imagePath;
            // If it's a relative path, prepend root
            return rootUrl(imagePath);
        }

        function dashboardUrl(role) {
            const folder = role === "admin" ? "admin" : role;
            return rootUrl(`dashboard/${folder}/index.html`);
        }

        function authUrl() {
            return rootUrl("index.html");
        }
        const ADMIN_USER = {
            id: "admin",
            role: "admin",
            inn: "000000000",
            name: "Tizim Administratori",
            status: "active",
            phone: "+998000000000",
            address: "Toshkent"
        };

        const DEFAULT_CATEGORIES = [
            { value: "electronics", label: "Elektronika", icon: "ri-computer-line" },
            { value: "furniture", label: "Mebel", icon: "ri-armchair-line" },
            { value: "appliances", label: "Maishiy texnika", icon: "ri-fridge-line" },
            { value: "clothes", label: "Kiyim-kechak", icon: "ri-shirt-line" },
            { value: "food", label: "Oziq-ovqat", icon: "ri-restaurant-line" },
            { value: "beauty", label: "Go'zallik", icon: "ri-magic-line" },
            { value: "building", label: "Qurilish mollari", icon: "ri-hammer-line" },
            { value: "auto", label: "Avto ehtiyot qism", icon: "ri-car-line" },
            { value: "stationery", label: "Kanselyariya", icon: "ri-pencil-ruler-line" },
            { value: "toys", label: "O'yinchoqlar", icon: "ri-bear-smile-line" },
            { value: "other", label: "Boshqa", icon: "ri-box-3-line" }
        ];

        const REGION_OPTIONS = [
            "Toshkent shahri", "Toshkent viloyati", "Samarqand", "Buxoro", "Andijon", "Farg'ona", "Namangan",
            "Qashqadaryo", "Surxondaryo", "Jizzax", "Sirdaryo", "Navoiy", "Xorazm", "Qoraqalpog'iston"
        ];

        const ORDER_FLOW = [
            { value: "pending_seller_accept", label: "Buyurtma berildi" },
            { value: "seller_accepted", label: "Sotuvchi qabul qildi" },
            { value: "dispatched", label: "Yetkazib berishga berildi" },
            { value: "delivered", label: "Yetkazildi" },
            { value: "buyer_accepted", label: "Magazin qabul qildi" },
            { value: "buyer_paid", label: "Xaridor to'ladi" },
            { value: "trade_closed", label: "Savdo yakunlandi" },
            { value: "seller_paid_comm", label: "Komissiya to'landi" },
            { value: "paid", label: "Yakunlandi" }
        ];

        const MENU_CONFIG = {
            admin: [
                { id: "admin-dash", icon: "ri-dashboard-3-line", title: "Dashboard" },
                { id: "admin-users", icon: "ri-group-line", title: "Foydalanuvchilar" },
                { id: "admin-orders", icon: "ri-file-list-3-line", title: "Buyurtmalar" },
                { id: "admin-products", icon: "ri-store-2-line", title: "Mahsulotlar" },
                { id: "admin-moderation", icon: "ri-shield-check-line", title: "Moderatsiya" },
                { id: "admin-comm", icon: "ri-money-dollar-circle-line", title: "Komissiya" },
                { id: "admin-reports", icon: "ri-camera-lens-line", title: "Foto Hisobotlar" },
                { id: "contracts", icon: "ri-file-shield-2-line", title: "Shartnomalar" },
                { id: "tickets", icon: "ri-scales-3-line", title: "Yordam" }
            ],
            seller: [
                { id: "seller-dash", icon: "ri-dashboard-line", title: "Dashboard" },
                { id: "seller-catalog", icon: "ri-function-line", title: "Mening Katalogim" },
                { id: "seller-orders", icon: "ri-shopping-bag-3-line", title: "Sotuvlar" },
                { id: "seller-finance", icon: "ri-wallet-3-line", title: "Moliya" },
                { id: "contracts", icon: "ri-file-shield-2-line", title: "Shartnomalar" },
                { id: "tickets", icon: "ri-scales-3-line", title: "Yordam" }
            ],
            buyer: [
                { id: "buyer-vitrina", icon: "ri-store-2-line", title: "Mahsulotlar" },
                { id: "buyer-cart", icon: "ri-shopping-cart-line", title: "Savat" },
                { id: "buyer-orders", icon: "ri-file-list-3-line", title: "Buyurtmalarim" },
                { id: "buyer-reports", icon: "ri-camera-line", title: "Foto Hisobot" },
                { id: "contracts", icon: "ri-file-shield-2-line", title: "Shartnomalar" },
                { id: "tickets", icon: "ri-scales-3-line", title: "Yordam" }
            ]
        };

        const DB = {
            users: [], products: [], orders: [], reports: [], tickets: [], notifications: [], contracts: [], categories: DEFAULT_CATEGORIES,
            cart: JSON.parse(localStorage.getItem(STORAGE_KEY + "_cart") || "[]")
        };
        const STATE = {
            currentUser: null,
            currentView: "",
            editingProductId: null,
            pendingProductFormData: null,
            filters: {
                users: { search: "", role: "all", status: "all" },
                orders: { search: "", status: "all" },
                products: { search: "", category: "all", model: "all", seller: "all" },
                sellerProducts: { search: "", category: "all", model: "all" },
                reports: { search: "", status: "all" },
                comm: { search: "", status: "all" }
            }
        };

        async function apiFetch(endpoint, method = 'GET', data = null, isFormData = false) {
            const options = { method };
            if (data) {
                if (isFormData) {
                    options.body = data;
                } else {
                    options.headers = { 'Content-Type': 'application/json' };
                    options.body = JSON.stringify(data);
                }
            }
            const res = await fetch(rootUrl(endpoint), options);
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || 'Server xatosi');
            }
            const resData = await res.json();
            if (resData && resData.success === false) {
                throw new Error(resData.message || 'Xatolik yuz berdi');
            }
            return resData;
        }

        function parseNumberOrDefault(value, fallback = 0) {
            const number = Number(value);
            return value === null || value === undefined || value === "" || Number.isNaN(number) ? fallback : number;
        }

        async function refreshDB() {
            if (!STATE.currentUser) return;
            try {
                const role = STATE.currentUser.role;
                const mapUser = u => ({ ...u, bankAccount: u.bank_account, bankMfo: u.mfo });
                const mapProduct = p => ({
                    ...p,
                    sellerId: p.seller_id,
                    sellerName: p.seller_name,
                    sellerPhone: p.seller_phone,
                    prepayPercent: parseNumberOrDefault(p.prepay_percent ?? p.prepayPercent, p.model === "prepayment" ? 30 : 0),
                    realDays: parseNumberOrDefault(p.real_days ?? p.realDays, 30),
                    photoDays: parseNumberOrDefault(p.photo_days ?? p.photoDays, 15),
                    moderationNote: p.moderation_note,
                    moderatedAt: p.moderated_at,
                    viewCount: p.view_count,
                    createdAt: p.created_at
                });
                const mapOrderItem = item => ({ ...item, prodId: item.product_id || item.prodId, qty: Number(item.quantity || item.qty || 0), price: Number(item.price || 0) });
                const mapOrder = o => ({ ...o, buyerId: o.buyer_id, sellerId: o.seller_id, commStatus: o.comm_status, dispatchReport: o.dispatch_report, createdAt: o.created_at, updatedAt: o.updated_at, items: (o.items || []).map(mapOrderItem) });
                const mapReport = r => ({ ...r, sellerId: r.seller_id, orderId: r.order_id, prodId: r.prod_id, dueDate: r.due_date, createdAt: r.created_at });
                const mapTicket = t => ({
                    ...t,
                    userId: t.user_id,
                    createdAt: t.created_at,
                    replies: (t.replies || []).map(reply => ({
                        ...reply,
                        createdAt: reply.created_at || reply.createdAt || reply.date
                    }))
                });
                const mapNotification = n => ({ ...n, isRead: Number(n.is_read) === 1, createdAt: n.created_at });
                const mapContract = c => ({
                    ...c,
                    contractNumber: Number(c.contract_number || 0),
                    contractType: c.contract_type,
                    signerId: c.signer_id,
                    signerName: c.signer_name,
                    signerRole: c.signer_role,
                    counterpartyId: c.counterparty_id,
                    counterpartyName: c.counterparty_name,
                    counterpartyRole: c.counterparty_role,
                    productId: c.product_id,
                    productName: c.product_name,
                    productSku: c.product_sku,
                    orderId: c.order_id,
                    orderTotal: c.order_total,
                    signedAt: c.signed_at
                });

                if (role === 'admin') {
                    const [usersRes, dashRes, prodRes, tickRes, notifRes, contractRes] = await Promise.all([
                        apiFetch('api/admin/users.php'),
                        apiFetch('api/admin/dashboard.php'),
                        apiFetch('api/admin/products.php'),
                        apiFetch('api/tickets.php'),
                        apiFetch('api/notifications.php'),
                        apiFetch('api/contracts.php')
                    ]);
                    DB.users = (usersRes.data || []).map(mapUser);
                    DB.orders = (dashRes.data || []).map(mapOrder);
                    DB.products = (prodRes.data || []).map(mapProduct);
                    DB.tickets = (tickRes.data || []).map(mapTicket);
                    DB.notifications = (notifRes.data || []).map(mapNotification);
                    DB.contracts = (contractRes.data || []).map(mapContract);
                } else if (role === 'seller') {
                    const [prodRes, orderRes, repRes, tickRes, notifRes, contractRes] = await Promise.all([
                        apiFetch('api/seller/products.php'),
                        apiFetch('api/seller/orders.php'),
                        apiFetch('api/seller/reports.php'),
                        apiFetch('api/tickets.php'),
                        apiFetch('api/notifications.php'),
                        apiFetch('api/contracts.php')
                    ]);
                    DB.products = (prodRes.data || []).map(mapProduct);
                    DB.orders = (orderRes.data || []).map(mapOrder);
                    DB.reports = (repRes.data || []).map(mapReport);
                    DB.tickets = (tickRes.data || []).map(mapTicket);
                    DB.notifications = (notifRes.data || []).map(mapNotification);
                    DB.contracts = (contractRes.data || []).map(mapContract);
                } else if (role === 'buyer') {
                    const [prodRes, orderRes, tickRes, notifRes, contractRes] = await Promise.all([
                        apiFetch('api/buyer/products.php'),
                        apiFetch('api/buyer/orders.php'),
                        apiFetch('api/tickets.php'),
                        apiFetch('api/notifications.php'),
                        apiFetch('api/contracts.php')
                    ]);
                    DB.products = (prodRes.data || []).map(mapProduct);
                    DB.orders = (orderRes.data || []).map(mapOrder);
                    DB.tickets = (tickRes.data || []).map(mapTicket);
                    DB.notifications = (notifRes.data || []).map(mapNotification);
                    DB.contracts = (contractRes.data || []).map(mapContract);
                }
                renderNotificationBell();
            } catch (e) {
                console.error("Failed to refresh DB", e);
                showToast("Ma'lumotlarni yuklashda xatolik: " + e.message, "danger");
            }
        }

        function saveCart() {
            localStorage.setItem(STORAGE_KEY + "_cart", JSON.stringify(DB.cart));
        }

        function saveState() {
            saveCart();
        }

        const money = amount => new Intl.NumberFormat("uz-UZ").format(Number(amount) || 0) + " UZS";
        const today = () => new Date().toISOString().slice(0, 10);
        const uid = prefix => `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2, 7)}`;
        const normalize = value => String(value || "").toLowerCase().trim();
        const escapeHtml = value => String(value ?? "").replace(/[&<>"']/g, char => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char]));
        const formatPercent = value => {
            const number = parseNumberOrDefault(value, 0);
            return Number.isInteger(number) ? String(number) : String(number).replace(/(\.\d*?)0+$/, "$1").replace(/\.$/, "");
        };
        const phoneDigits = value => String(value || "").replace(/\D/g, "").slice(0, 9);
        const fullPhone = value => "+998" + phoneDigits(value);
        function accountDigits(value) {
            return String(value || "").replace(/\D/g, "").slice(0, 20);
        }

        function mfoDigits(value) {
            return String(value || "").replace(/\D/g, "").slice(0, 5);
        }

        function normalizeSessionUser(user) {
            if (!user) return user;
            return {
                ...user,
                bankAccount: user.bankAccount ?? user.bank_account ?? "",
                bankMfo: user.bankMfo ?? user.mfo ?? ""
            };
        }

        function formatDateTime(value) {
            const raw = String(value || "").trim();
            const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
            if (match) {
                const [, year, month, day, hour, minute] = match;
                return `${day}.${month}.${year} ${hour}:${minute}`;
            }
            return raw;
        }

        function formatPhoneInput(input) {
            const digits = phoneDigits(input.value);
            input.value = digits.replace(/(\d{2})(\d{3})(\d{2})(\d{0,2})/, (_, a, b, c, d) => [a, b, c, d].filter(Boolean).join(" "));
        }

        function slugify(text) {
            const latin = String(text || "")
                .toLowerCase()
                .replace(/g'/g, "g")
                .replace(/o'/g, "o")
                .replace(/sh/g, "sh")
                .replace(/ch/g, "ch")
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/^-+|-+$/g, "");
            return latin || `category-${Date.now()}`;
        }

        function categoryByValue(value) {
            return DB.categories.find(category => category.value === value) || DB.categories.find(category => category.value === "other") || DEFAULT_CATEGORIES.at(-1);
        }

        function userById(id) {
            if (id === ADMIN_USER.id) return ADMIN_USER;
            const direct = DB.users.find(user => user.id === id);
            if (direct) return direct;
            const order = DB.orders.find(item => item.sellerId === id || item.buyerId === id);
            if (order) {
                if (order.sellerId === id) return { id, name: order.seller_name || order.sellerName || "Sotuvchi", role: "seller", bankAccount: order.bank_account, bankMfo: order.mfo };
                if (order.buyerId === id) return { id, name: order.buyer_name || order.buyerName || "Diler", role: "buyer", inn: order.buyer_inn, phone: order.buyer_phone };
            }
            const product = DB.products.find(item => item.sellerId === id);
            if (product) return { id, name: product.sellerName || product.seller_name || "Sotuvchi", role: "seller", phone: product.sellerPhone || product.seller_phone };
            return { id, name: "Noma'lum", role: "", status: "" };
        }

        function productById(id) {
            return DB.products.find(product => product.id === id);
        }

        function roleLabel(role) {
            return { admin: "Admin", seller: "Sotuvchi", buyer: "Diler" }[role] || role;
        }

        function statusBadge(status) {
            const map = {
                active: ["Faol", "badge-active"],
                approved: ["Tasdiqlangan", "badge-active"],
                rejected: ["Rad etildi", "badge-danger"],
                inactive: ["Nofaol", "badge-muted"],
                blocked: ["Blok", "badge-danger"],
                pending_seller_accept: ["Buyurtma berildi", "badge-warning"],
                seller_accepted: ["Qabul qilindi", "badge-info"],
                dispatched: ["Yetkazishga berildi", "badge-info"],
                delivered: ["Yetkazildi", "badge-info"],
                buyer_accepted: ["Qabul qilindi", "badge-success"],
                buyer_paid: ["Xaridor to'ladi", "badge-success"],
                trade_closed: ["Savdo yakunlandi", "badge-active"],
                seller_paid_comm: ["Komissiya to'landi", "badge-info"],
                pending_admin: ["Tasdiq kutilmoqda", "badge-warning"],
                paid: ["Yakunlandi", "badge-active"],
                pending: ["Kutilmoqda", "badge-warning"],
                overdue: ["Kechikkan", "badge-danger"],
                done: ["Bajarildi", "badge-active"],
                open: ["Ochiq", "badge-info"]
            };
            const item = map[status] || [status || "Noma'lum", "badge-muted"];
            return `<span class="badge ${item[1]}">${item[0]}</span>`;
        }

        function showToast(message, type = "success") {
            const icons = { success: "ri-checkbox-circle-fill", warning: "ri-error-warning-fill", info: "ri-information-fill", danger: "ri-close-circle-fill" };
            const colors = { success: "var(--success)", warning: "var(--warning)", info: "var(--info)", danger: "var(--danger)" };
            const toast = document.createElement("div");
            toast.className = "toast";
            toast.style.borderLeftColor = colors[type] || "var(--primary)";
            toast.innerHTML = `<i class="${icons[type] || icons.info}" style="color:${colors[type] || "var(--primary)"};font-size:1.35rem;"></i><span>${escapeHtml(message)}</span>`;
            document.getElementById("toast-container").appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = "0";
                setTimeout(() => toast.remove(), 220);
            }, 2800);
        }

        function switchAuth(tab) {
            document.getElementById("login-form").classList.toggle("hidden", tab !== "login");
            document.getElementById("register-form").classList.toggle("hidden", tab !== "register");
            if (tab === "register") {
                nextRegStep(1);
            }
        }

        function nextRegStep(step) {
            if (step === 2) {
                const name = document.getElementById("reg-name")?.value.trim();
                const inn = document.getElementById("reg-inn")?.value.trim();
                if (!name || !inn || inn.length !== 9) {
                    showToast("Kompaniya nomi va INN (9 ta raqam) ni to'g'ri kiriting", "warning");
                    return;
                }
            } else if (step === 3) {
                const bankAcc = document.getElementById("reg-bank-acc")?.value.trim();
                const bankMfo = document.getElementById("reg-bank-mfo")?.value.trim();
                if (!bankAcc || bankAcc.length !== 20 || !bankMfo || bankMfo.length !== 5) {
                    showToast("Bank rekvizitlarini to'g'ri kiriting (Hisob: 20 ta, MFO: 5 ta raqam)", "warning");
                    return;
                }
            }

            document.getElementById("reg-step-1")?.classList.add("hidden");
            document.getElementById("reg-step-2")?.classList.add("hidden");
            document.getElementById("reg-step-3")?.classList.add("hidden");
            document.getElementById("reg-step-" + step)?.classList.remove("hidden");
            
            const dot1 = document.getElementById("step-dot-1");
            const dot2 = document.getElementById("step-dot-2");
            const dot3 = document.getElementById("step-dot-3");
            
            if (dot1) {
                dot1.style.background = step >= 1 ? "var(--primary)" : "white";
                dot1.style.color = step >= 1 ? "white" : "var(--muted)";
                dot1.style.borderColor = step >= 1 ? "var(--primary)" : "var(--border)";
            }
            if (dot2) {
                dot2.style.background = step >= 2 ? "var(--primary)" : "white";
                dot2.style.color = step >= 2 ? "white" : "var(--muted)";
                dot2.style.borderColor = step >= 2 ? "var(--primary)" : "var(--border)";
            }
            if (dot3) {
                dot3.style.background = step >= 3 ? "var(--primary)" : "white";
                dot3.style.color = step >= 3 ? "white" : "var(--muted)";
                dot3.style.borderColor = step >= 3 ? "var(--primary)" : "var(--border)";
            }
            
            const desc = document.getElementById("reg-step-desc");
            if (desc) {
                if (step === 1) desc.textContent = "Kompaniya ma'lumotlari";
                if (step === 2) desc.textContent = "Bank rekvizitlari";
                if (step === 3) desc.textContent = "Aloqa va xavfsizlik";
            }
        }

        function requisitesPreview(title, party) {
            return `<div class="contract-party">
                <b>${escapeHtml(title)}</b><br>
                Nomi: ${escapeHtml(party.name || "Kiritilmagan")}<br>
                STIR: ${escapeHtml(party.inn || "Kiritilmagan")}<br>
                Telefon: ${escapeHtml(party.phone || "Kiritilmagan")}<br>
                H/r: ${escapeHtml(party.bankAccount || party.bank_account || "Kiritilmagan")}<br>
                MFO: ${escapeHtml(party.bankMfo || party.mfo || "Kiritilmagan")}
            </div>`;
        }

        function currentUserParty() {
            const user = normalizeSessionUser(STATE.currentUser || {});
            const regPhoneRaw = document.getElementById("reg-phone")?.value || "";
            const regPhone = phoneDigits(regPhoneRaw);
            return {
                name: user.name || document.getElementById("reg-name")?.value.trim() || "",
                inn: user.inn || document.getElementById("reg-inn")?.value.trim() || "",
                phone: user.phone || (regPhone.length === 9 ? "998" + regPhone : ""),
                role: user.role || document.getElementById("reg-role")?.value || "",
                bankAccount: user.bankAccount || document.getElementById("reg-bank-acc")?.value.trim() || "",
                bankMfo: user.bankMfo || document.getElementById("reg-bank-mfo")?.value.trim() || ""
            };
        }

        function platformParty() {
            return {
                name: "RoboTexnika MCHJ",
                inn: "Kiritilmagan",
                phone: "Kiritilmagan",
                bankAccount: "Kiritilmagan",
                bankMfo: "Kiritilmagan"
            };
        }

        function platformContractHtml(source = "register") {
            const user = currentUserParty();
            return `<div class="contract-document">
                <h3>PLATFORMA OFERTASI VA XIZMAT KO'RSATISH SHARTNOMASI</h3>
                <p>Ushbu shartnoma RoboTexnika MCHJ va foydalanuvchi o'rtasida elektron tarzda tuziladi.</p>
                <h4>1. TOMONLAR</h4>
                <p>1.1. Platforma: RoboTexnika MCHJ, direktor Mirzayev Sardor.</p>
                <p>1.2. Foydalanuvchi: ${escapeHtml(user.name || "Kiritilmagan")}, rol: ${escapeHtml(roleLabel(user.role))}.</p>
                <h4>2. XIZMATLAR</h4>
                <p>2.1. Platforma kabinet, katalog, buyurtma, hisob-kitob, bildirishnoma va yordam servislaridan foydalanish imkonini beradi.</p>
                <p>2.2. Foydalanuvchi kiritilgan kompaniya, STIR, telefon va bank rekvizitlari to'g'riligiga shaxsan javob beradi.</p>
                <h4>3. ELEKTRON ROZILIK</h4>
                <p>3.1. Ro'yxatdan o'tish vaqtida "Roziman" tugmasini bosish shartnomani elektron imzolash bilan teng kuchga ega.</p>
                <p>3.2. Rozilik manbasi: ${escapeHtml(source)}.</p>
                <h4>4. MAXFIYLIK VA JAVOBGARLIK</h4>
                <p>4.1. Tomonlar shartnoma doirasida olingan tijorat, shaxsiy va moliyaviy ma'lumotlarni uchinchi shaxslarga asossiz oshkor qilmaydi.</p>
                <p>4.2. Tizimdagi barcha operatsiyalar, buyurtmalar va bildirishnomalar elektron dalil sifatida qabul qilinadi.</p>
                <h4>5. REKVIZITLAR</h4>
                <div class="contract-parties">${requisitesPreview("PLATFORMA", platformParty())}${requisitesPreview("FOYDALANUVCHI", user)}</div>
            </div>`;
        }

        function sellerListingContractHtml(product = {}) {
            const seller = currentUserParty();
            const tradeTerms = product.model
                ? `${product.model === "prepayment" ? `, oldindan to'lov: ${formatPercent(product.prepayPercent)}%` : ", savdo modeli: realizatsiya"}, realizatsiya muddati: ${parseNumberOrDefault(product.realDays, 30)} kun`
                : "";
            return `<div class="contract-document">
                <h4>1. SHARTNOMA TOMONLARI</h4>
                <p>1.1. "RoboTexnika" MCHJ, keyingi o'rinlarda "Platforma" deb yuritiladi, direktor Mirzayev Sardor nomidan bir tomondan, va</p>
                <p>1.2. "${escapeHtml(seller.name || "Kiritilmagan")}", keyingi o'rinlarda "Ishlab chiqaruvchi" deb yuritiladi, mazkur shartnomani quyidagilar to'g'risida tuzdilar:</p>
                ${product.name ? `<p><b>Mahsulot:</b> ${escapeHtml(product.name)}, narx: ${money(product.price)}, hudud: ${escapeHtml(product.region)}${tradeTerms}.</p>` : ""}
                <h4>2. SHARTNOMA PREDMETI</h4>
                <p>2.1. Platforma Ishlab chiqaruvchining tovarlarini chakana savdo nuqtalariga sotishda vositachilik va axborot-texnologik xizmatlarini ko'rsatadi.</p>
                <p>2.2. Platforma Mijozlar bazasini shakllantirish, tovarni targ'ib qilish, sotuvlar, yetkazib berish va to'lovlarning elektron hisobini yuritish, bozor tahlili va reyting ko'rsatkichlarini taqdim etish majburiyatlarini oladi.</p>
                <h4>3. TOMONLARNING HUQUQ VA MAJBURIYATLARI</h4>
                <p>3.1. Ishlab chiqaruvchi tovarlarning sifati va amaldagi standartlarga mosligini ta'minlaydi, Platforma orqali kelgan buyurtmalarni o'z vaqtida va to'liq hajmda yetkazib beradi.</p>
                <p>3.2. Platforma reyting pasayganda xizmat ko'rsatishni vaqtincha to'xtatish hamda to'lov intizomini nazorat qilish huquqiga ega.</p>
                <h4>4. KOMISSIYA MUKOFOTI VA HISOB-KITOBLAR</h4>
                <p>4.1. Platforma xizmat haqi Mijoz tomonidan to'langan tovar qiymatining 5% miqdorini tashkil etadi.</p>
                <p>4.2. Hisob-fakturalar har oy yakunida taqdim etiladi, komissiya 5 bank ish kuni ichida to'lanadi.</p>
                <h4>5. KAFOLAT VA MOLIYAVIY QO'LLAB-QUVVATLASH</h4>
                <p>5.1. Platforma Mijozlarning to'lov qobiliyatini ichki tizim orqali tahlil qiladi va alohida kelishuvga asosan qarzdorlikni vaqtinchalik qoplashi mumkin.</p>
                <h4>6. REYTING VA ELEKTRON TIZIM</h4>
                <p>6.1. Barcha operatsiyalar Platformaning dasturiy ta'minoti orqali qayd etiladi va hisob-kitob uchun asos hisoblanadi.</p>
                <h4>7. FORS-MAJOR VA JAVOBGARLIK</h4>
                <p>7.1. Tomonlar majburiyatlarini bajarmagan taqdirda O'zbekiston Respublikasi qonunchiligiga muvofiq javobgar bo'ladilar.</p>
                <h4>8. NIZOLARNI HAL ETISH</h4>
                <p>8.1. Kelishmovchiliklar muzokaralar orqali, kelishuv bo'lmasa Platforma joylashgan hududdagi iqtisodiy sudda hal etiladi.</p>
                <h4>9. YAKUNIY QOIDALAR</h4>
                <p>9.1. Shartnoma 12 oy amal qiladi va "Roziman" tugmasini bosish elektron imzo kuchiga ega.</p>
                <h4>10. TOMONLARNING REKVIZITLARI</h4>
                <div class="contract-parties">${requisitesPreview("PLATFORMA", platformParty())}${requisitesPreview("ISHLAB CHIQARUVCHI", seller)}</div>
            </div>`;
        }

        function buyerOrderContractHtml(total = 0) {
            const buyer = currentUserParty();
            return `<div class="contract-document">
                <h4>1. SHARTNOMA TOMONLARI</h4>
                <p>1.1. "RoboTexnika" MCHJ, keyingi o'rinlarda "Platforma" deb yuritiladi, direktor Mirzayev Sardor nomidan, va</p>
                <p>1.2. "${escapeHtml(buyer.name || "Kiritilmagan")}", keyingi o'rinlarda "Xaridor" deb yuritiladi, mazkur shartnomani quyidagilar to'g'risida tuzdilar:</p>
                <p><b>Buyurtma summasi:</b> ${money(total)}</p>
                <h4>2. SHARTNOMA PREDMETI</h4>
                <p>2.1. Platforma Xaridorga tizimdagi Ishlab chiqaruvchilarning mahsulotlarini tanlash, buyurtma berish va yetkazib berishni tashkil qilish xizmatlarini ko'rsatadi.</p>
                <p>2.2. Xaridor Platforma orqali buyurtma qilingan tovarlarni qabul qilish va ularning haqini belgilangan muddatlarda to'lash majburiyatini oladi.</p>
                <h4>3. BUYURTMA VA YETKAZIB BERISH TARTIBI</h4>
                <p>3.1. Xaridor buyurtmani Platformaning elektron tizimi orqali amalga oshiradi. Tovarlar savdo nuqtasiga Ishlab chiqaruvchi yoki logistika hamkorlari tomonidan yetkaziladi.</p>
                <p>3.2. Tovar qabul qilinganda Xaridor sifat va miqdorni tekshiradi hamda elektron yoki qog'oz yuk xatini imzolaydi.</p>
                <h4>4. HISOB-KITOB TARTIBI</h4>
                <p>4.1. Tovar narxi Platforma tizimida buyurtma berilgan vaqtdagi narx bo'yicha belgilanadi.</p>
                <p>4.2. To'lov oldindan, bo'lib to'lash yoki kechiktirilgan to'lov shaklida amalga oshirilishi mumkin.</p>
                <h4>5. PLATFORMANING KAFOLATLARI</h4>
                <p>5.1. Platforma Xaridor va Ishlab chiqaruvchi o'rtasidagi hisob-kitoblarning shaffofligini ta'minlaydi.</p>
                <p>5.2. Yaroqsiz tovar bo'yicha Xaridor 24 soat ichida Platformaga ariza beradi.</p>
                <h4>6. TOMONLARNING JAVOBGARLIGI</h4>
                <p>6.1. To'lov kechiktirilganda Xaridor har bir kun uchun to'lanmagan summaning 0,1% miqdorida penya to'laydi, lekin bu jami summaning 10%idan oshmaydi.</p>
                <h4>7. REYTING TIZIMI</h4>
                <p>7.1. Xaridorning to'lov intizomi asosida Platformada shaxsiy reyting yuritiladi.</p>
                <h4>8. SHARTNOMANING AMAL QILISHI</h4>
                <p>8.1. Shartnoma 12 oy amal qiladi. "Roziman" tugmasi elektron oferta qabul qilinganini bildiradi.</p>
                <h4>9. TOMONLARNING REKVIZITLARI</h4>
                <div class="contract-parties">${requisitesPreview("PLATFORMA", platformParty())}${requisitesPreview("XARIDOR", buyer)}</div>
            </div>`;
        }

        function openContractModal(e, target = "register") {
            e?.preventDefault();
            modal("Platforma shartnomasi", platformContractHtml(target), `<button class="btn btn-outline" onclick="closeModal()">Yopish</button><button class="btn btn-primary" onclick="acceptContract()">Roziman</button>`);
        }

        function acceptContract() {
            closeModal();
            const terms = document.getElementById("register-terms");
            if (terms) {
                terms.disabled = false;
                terms.checked = true;
                const btn = document.getElementById("register-btn");
                if (btn) btn.disabled = false;
            }
        }

        async function doLogin() {
            const password = document.getElementById("login-password").value.trim();
            let phone = document.getElementById("login-phone").value.replace(/\D/g, "");
            
            if (phone.length === 9) phone = "998" + phone;

            if (!phone || !password) return showToast("Iltimos, ma'lumotlarni to'ldiring", "warning");

            try {
                const btn = document.getElementById("login-btn");
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `<i class="ri-loader-4-line ri-spin"></i> Kuting...`;
                }
                
                const res = await apiFetch('api/auth/login.php', 'POST', { phone, password });
                if (res.success) {
                    STATE.currentUser = normalizeSessionUser(res.user);
                    localStorage.setItem(SESSION_KEY, JSON.stringify(STATE.currentUser));
                    showToast("Tizimga kirdingiz", "success");
                    setTimeout(() => window.location.href = dashboardUrl(STATE.currentUser.role), 300);
                }
            } catch (e) {
                showToast(e.message, "danger");
                const btn = document.getElementById("login-btn");
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = "Kirish";
                }
            }
        }

        async function doRegister() {
            const name = document.getElementById("reg-name").value.trim();
            const inn = document.getElementById("reg-inn").value.trim();
            let phone = document.getElementById("reg-phone").value.replace(/\D/g, "");
            const password = document.getElementById("reg-password").value.trim();
            const role = document.getElementById("reg-role").value;
            const bankAcc = document.getElementById("reg-bank-acc") ? document.getElementById("reg-bank-acc").value.trim() : "";
            const bankMfo = document.getElementById("reg-bank-mfo") ? document.getElementById("reg-bank-mfo").value.trim() : "";
            const terms = document.getElementById("register-terms") ? document.getElementById("register-terms").checked : true;

            if (!name || !inn || phone.length !== 9 || password.length < 4 || !bankAcc || !bankMfo) {
                showToast("Barcha maydonlarni to'g'ri to'ldiring", "warning");
                return;
            }
            if (!terms) {
                showToast("Shartnomaga rozi bo'lishingiz kerak", "warning");
                return;
            }
            
            phone = "998" + phone;

            try {
                const btn = document.getElementById("register-btn");
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `<i class="ri-loader-4-line ri-spin"></i> Kuting...`;
                }
                
                const res = await apiFetch('api/auth/register.php', 'POST', {
                    role, name, inn, bank_account: bankAcc, mfo: bankMfo, phone, password,
                    contract_accepted: true,
                    contract_source: 'register'
                });
                
                if (res.success) {
                    STATE.currentUser = normalizeSessionUser(res.user);
                    localStorage.setItem(SESSION_KEY, JSON.stringify(STATE.currentUser));
                    showToast("Muvaffaqiyatli ro'yxatdan o'tdingiz", "success");
                    setTimeout(() => window.location.href = dashboardUrl(STATE.currentUser.role), 300);
                }
            } catch(e) {
                showToast(e.message, "danger");
                const btn = document.getElementById("register-btn");
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = "Yakunlash";
                }
            }
        }

        async function doLogout() {
            try {
                await apiFetch('api/auth/logout.php', 'POST');
            } catch(e) {}
            STATE.currentUser = null;
            localStorage.removeItem(SESSION_KEY);
            window.location.href = authUrl();
        }

        function renderProfileMenu() {
            const dropdown = document.getElementById("profile-dropdown");
            if (!dropdown || !STATE.currentUser) return;

            const user = normalizeSessionUser(STATE.currentUser);
            const bankAccount = user.bankAccount || "";
            const bankMfo = user.bankMfo || "";
            dropdown.innerHTML = `
                <div class="profile-head">
                    <div class="profile-head-avatar">${escapeHtml((user.name || "U").charAt(0).toUpperCase())}</div>
                    <div>
                        <b>${escapeHtml(user.name || "Foydalanuvchi")}</b>
                        <div class="text-xs text-muted">${escapeHtml(roleLabel(user.role))} · ${escapeHtml(user.phone || "")}</div>
                    </div>
                </div>
                <button class="profile-menu-item" onclick="toggleProfileSection('profile-password-section')">
                    <i class="ri-lock-password-line"></i>
                    <span>Parolni almashtirish</span>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div class="profile-form hidden" id="profile-password-section">
                    <div class="input-group">
                        <label>Yangi parol</label>
                        <input id="profile-new-password" type="password" class="input-control" placeholder="Kamida 4 belgi">
                    </div>
                    <div class="input-group">
                        <label>Parolni takrorlang</label>
                        <input id="profile-confirm-password" type="password" class="input-control" placeholder="Qayta kiriting">
                    </div>
                    <button class="btn btn-primary w-full" id="profile-password-btn" onclick="updateProfilePassword()">Saqlash</button>
                </div>
                <button class="profile-menu-item" onclick="toggleProfileSection('profile-bank-section')">
                    <i class="ri-bank-card-line"></i>
                    <span>Bank hisob raqamini almashtirish</span>
                    <i class="ri-arrow-down-s-line"></i>
                </button>
                <div class="profile-form hidden" id="profile-bank-section">
                    <div class="input-group">
                        <label>Bank hisob raqami</label>
                        <input id="profile-bank-account" class="input-control" inputmode="numeric" maxlength="20" value="${escapeHtml(bankAccount)}" placeholder="20 xonali raqam" oninput="this.value=accountDigits(this.value)">
                    </div>
                    <div class="input-group">
                        <label>Bank MFO</label>
                        <input id="profile-bank-mfo" class="input-control" inputmode="numeric" maxlength="5" value="${escapeHtml(bankMfo)}" placeholder="5 xonali raqam" oninput="this.value=mfoDigits(this.value)">
                    </div>
                    <button class="btn btn-primary w-full" id="profile-bank-btn" onclick="updateProfileBank()">Saqlash</button>
                </div>
                <button class="profile-menu-item profile-logout" onclick="doLogout()">
                    <i class="ri-logout-box-r-line"></i>
                    <span>Chiqish</span>
                </button>
            `;
        }

        function toggleProfileMenu(event) {
            event?.stopPropagation();
            toggleMobileMenu(false);
            document.getElementById("notification-dropdown")?.classList.remove("open");
            renderProfileMenu();
            document.getElementById("profile-dropdown")?.classList.toggle("open");
        }

        function toggleProfileSection(id) {
            const section = document.getElementById(id);
            if (!section) return;
            section.classList.toggle("hidden");
        }

        async function updateProfilePassword() {
            const password = document.getElementById("profile-new-password")?.value.trim() || "";
            const confirm = document.getElementById("profile-confirm-password")?.value.trim() || "";
            if (password.length < 4) {
                showToast("Parol kamida 4 belgidan iborat bo'lishi kerak", "warning");
                return;
            }
            if (password !== confirm) {
                showToast("Parollar bir xil emas", "warning");
                return;
            }

            const btn = document.getElementById("profile-password-btn");
            try {
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `<i class="ri-loader-4-line ri-spin"></i> Saqlanmoqda...`;
                }
                const res = await apiFetch('api/auth/profile.php', 'POST', { action: 'update_password', password });
                STATE.currentUser = normalizeSessionUser(res.user);
                localStorage.setItem(SESSION_KEY, JSON.stringify(STATE.currentUser));
                renderProfileMenu();
                showToast("Parol yangilandi", "success");
            } catch (e) {
                showToast(e.message, "danger");
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = "Saqlash";
                }
            }
        }

        async function updateProfileBank() {
            const bankAccount = accountDigits(document.getElementById("profile-bank-account")?.value || "");
            const bankMfo = mfoDigits(document.getElementById("profile-bank-mfo")?.value || "");
            if (bankAccount.length !== 20 || bankMfo.length !== 5) {
                showToast("Hisob raqam 20 ta, MFO 5 ta raqam bo'lishi kerak", "warning");
                return;
            }

            const btn = document.getElementById("profile-bank-btn");
            try {
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = `<i class="ri-loader-4-line ri-spin"></i> Saqlanmoqda...`;
                }
                const res = await apiFetch('api/auth/profile.php', 'POST', {
                    action: 'update_bank',
                    bank_account: bankAccount,
                    mfo: bankMfo
                });
                STATE.currentUser = normalizeSessionUser(res.user);
                localStorage.setItem(SESSION_KEY, JSON.stringify(STATE.currentUser));
                renderProfileMenu();
                showToast("Bank rekvizitlari yangilandi", "success");
            } catch (e) {
                showToast(e.message, "danger");
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = "Saqlash";
                }
            }
        }

        async function initApp() {
            STATE.currentUser = normalizeSessionUser(JSON.parse(localStorage.getItem(SESSION_KEY) || "null"));
            
            if (PAGE_TYPE === "auth") {
                document.getElementById("auth-layout")?.classList.remove("hidden");
                if (STATE.currentUser) {
                    window.location.replace(dashboardUrl(STATE.currentUser.role));
                    return;
                }
                const logPhone = document.getElementById("login-phone");
                if (logPhone) formatPhoneInput(logPhone);
                const regPhone = document.getElementById("reg-phone");
                if (regPhone) formatPhoneInput(regPhone);
            } else if (PAGE_TYPE === "dashboard") {
                document.getElementById("app-layout")?.classList.remove("hidden");
                if (!STATE.currentUser || STATE.currentUser.role !== PAGE_ROLE) {
                    window.location.replace(authUrl());
                    return;
                }
                const nameEl = document.getElementById("topbar-user-name");
                if (nameEl) nameEl.textContent = STATE.currentUser.name;
                const roleEl = document.getElementById("topbar-user-role");
                if (roleEl) roleEl.textContent = roleLabel(STATE.currentUser.role);
                const avatarEl = document.getElementById("topbar-avatar");
                if (avatarEl && STATE.currentUser.name) avatarEl.textContent = STATE.currentUser.name.charAt(0).toUpperCase();
                renderProfileMenu();
                
                renderMenus();
                
                await refreshDB();

                const menu = MENU_CONFIG[STATE.currentUser.role];
                if (menu && menu.length) {
                    navigate(menu[0].id);
                }
            }
        }

        function renderMenus() {
            const menu = MENU_CONFIG[STATE.currentUser.role] || [];
            const html = menu.map(item => `
                <button class="menu-item" data-view="${item.id}" onclick="navigate('${item.id}')">
                    <i class="${item.icon}"></i><span>${item.title}</span>
                </button>
            `).join("");
            const mobileHtml = menu.map(item => `
                <button class="mobile-nav-item" data-view="${item.id}" onclick="navigate('${item.id}')" aria-label="${escapeHtml(item.title)}" title="${escapeHtml(item.title)}">
                    <i class="${item.icon}"></i>
                </button>
            `).join("");
            document.getElementById("sidebar-menu").innerHTML = html;
            const mobileNav = document.getElementById("mobile-bottom-nav");
            if (mobileNav) mobileNav.innerHTML = mobileHtml;
        }

        function toggleMobileMenu(force) {
            const mobileNav = document.getElementById("mobile-bottom-nav");
            if (!mobileNav) return;
            mobileNav.classList.toggle("open", typeof force === "boolean" ? force : !mobileNav.classList.contains("open"));
        }

        function notificationIcon(type) {
            return {
                success: "ri-checkbox-circle-line",
                danger: "ri-close-circle-line",
                warning: "ri-error-warning-line",
                info: "ri-information-line"
            }[type] || "ri-notification-3-line";
        }

        function renderNotificationBell() {
            const countEl = document.getElementById("notification-count");
            const dropdown = document.getElementById("notification-dropdown");
            if (!countEl || !dropdown) return;

            const unread = DB.notifications.filter(item => !item.isRead).length;
            countEl.textContent = unread > 9 ? "9+" : String(unread);
            countEl.classList.toggle("hidden", unread === 0);

            dropdown.innerHTML = `
                <div class="notification-head">
                    <b>Bildirishnomalar</b>
                    <button onclick="markAllNotificationsRead()">O'qildi</button>
                </div>
                <div class="notification-list">
                    ${DB.notifications.length ? DB.notifications.map(item => `
                        <button class="notification-item ${item.isRead ? "" : "unread"}" onclick="openNotification('${item.id}', '${escapeHtml(item.link || "")}')">
                            <i class="${notificationIcon(item.type)}"></i>
                            <span><b>${escapeHtml(item.title)}</b><small>${escapeHtml(item.message)}</small></span>
                        </button>
                    `).join("") : `<div class="notification-empty">Yangi bildirishnoma yo'q</div>`}
                </div>`;
        }

        function toggleNotifications(event) {
            event?.stopPropagation();
            toggleMobileMenu(false);
            document.getElementById("profile-dropdown")?.classList.remove("open");
            document.getElementById("notification-dropdown")?.classList.toggle("open");
        }

        async function openNotification(id, link) {
            try {
                await apiFetch('api/notifications.php', 'POST', { action: 'mark_read', id });
                const item = DB.notifications.find(notification => notification.id === id);
                if (item) item.isRead = true;
                renderNotificationBell();
            } catch (e) {
                showToast(e.message, "danger");
            }
            document.getElementById("notification-dropdown")?.classList.remove("open");
            if (link && MENU_CONFIG[STATE.currentUser.role]?.some(item => item.id === link)) {
                navigate(link);
            }
        }

        async function markAllNotificationsRead() {
            try {
                await apiFetch('api/notifications.php', 'POST', { action: 'mark_all_read' });
                DB.notifications = DB.notifications.map(item => ({ ...item, isRead: true }));
                renderNotificationBell();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        function navigate(viewId) {
            STATE.currentView = viewId;
            const menu = MENU_CONFIG[STATE.currentUser.role] || [];
            const item = menu.find(entry => entry.id === viewId);
            document.getElementById("page-title").textContent = item ? item.title : "Dashboard";
            document.querySelectorAll("[data-view]").forEach(el => el.classList.toggle("active", el.dataset.view === viewId));
            renderCurrentView();
        }

        function renderCurrentView() {
            const container = document.getElementById("view-area");
            const renderers = {
                "admin-dash": renderAdminDash,
                "admin-users": renderAdminUsers,
                "admin-orders": renderAdminOrders,
                "admin-products": renderAdminProducts,
                "admin-moderation": renderAdminModeration,
                "admin-comm": renderAdminComm,
                "admin-reports": renderAdminReports,
                "seller-dash": renderSellerDash,
                "seller-catalog": renderSellerCatalog,
                "seller-orders": renderSellerOrders,
                "seller-finance": renderSellerFinance,
                "buyer-vitrina": renderBuyerVitrina,
                "buyer-cart": renderBuyerCart,
                "buyer-orders": renderBuyerOrders,
                "buyer-reports": renderBuyerReports,
                contracts: renderContracts,
                tickets: renderTickets
            };
            if (renderers[STATE.currentView]) renderers[STATE.currentView](container);
        }

        function filterSelect(id, value, options, onChange) {
            return `<select class="input-control" id="${id}" onchange="${onChange}(this.value)">
                ${options.map(option => `<option value="${escapeHtml(option.value)}" ${option.value === value ? "selected" : ""}>${escapeHtml(option.label)}</option>`).join("")}
            </select>`;
        }

        function categoryOptions(includeAll = true) {
            const options = DB.categories.map(category => ({ value: category.value, label: category.label }));
            return includeAll ? [{ value: "all", label: "Barcha kategoriyalar" }, ...options] : options;
        }

        function sellerOptions(includeAll = true) {
            const sellers = DB.users.filter(user => user.role === "seller");
            const options = sellers.map(user => ({ value: user.id, label: user.name }));
            return includeAll ? [{ value: "all", label: "Barcha sotuvchilar" }, ...options] : options;
        }

        function orderStatusOptions(includeAll = true) {
            const options = ORDER_FLOW.map(item => ({ value: item.value, label: item.label }));
            return includeAll ? [{ value: "all", label: "Barcha holatlar" }, ...options] : options;
        }

        function modal(title, body, footer = `<button class="btn btn-outline" onclick="closeModal()">Yopish</button>`) {
            document.getElementById("modal-title").innerHTML = title;
            document.getElementById("modal-body").innerHTML = body;
            document.getElementById("modal-footer").innerHTML = footer;
            document.getElementById("main-modal").classList.add("active");
        }

        function closeModal() {
            document.getElementById("main-modal").classList.remove("active");
        }

        function statCard(icon, label, value, tone = "text-primary") {
            return `<div class="card">
                <div class="text-sm text-muted flex items-center gap-2"><i class="${icon} ${tone}"></i>${label}</div>
                <div class="font-bold mt-2" style="font-size:1.45rem;">${value}</div>
            </div>`;
        }

        function monthBuckets(count = 6) {
            const names = ["Yan", "Fev", "Mar", "Apr", "May", "Iyun", "Iyul", "Avg", "Sen", "Okt", "Noy", "Dek"];
            const now = new Date();
            return Array.from({ length: count }, (_, index) => {
                const date = new Date(now.getFullYear(), now.getMonth() - (count - 1 - index), 1);
                const month = date.getMonth() + 1;
                return {
                    key: `${date.getFullYear()}-${String(month).padStart(2, "0")}`,
                    label: names[date.getMonth()]
                };
            });
        }

        function orderMonthKey(order) {
            const date = new Date(order.date || order.createdAt || order.updatedAt || Date.now());
            if (Number.isNaN(date.getTime())) return "";
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
        }

        function monthlyOrderTotals(orders, field = "total") {
            return monthBuckets().map(month => ({
                label: month.label,
                value: orders
                    .filter(order => orderMonthKey(order) === month.key)
                    .reduce((sum, order) => sum + Number(order[field] || 0), 0)
            }));
        }

        function dayBuckets(count = 7) {
            const names = ["Yak", "Dush", "Sesh", "Chor", "Pay", "Jum", "Shan"];
            const now = new Date();
            return Array.from({ length: count }, (_, index) => {
                const date = new Date();
                date.setDate(now.getDate() - (count - 1 - index));
                return {
                    key: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`,
                    label: names[date.getDay()]
                };
            });
        }

        function orderDayKey(order) {
            const date = new Date(order.date || order.createdAt || order.updatedAt || Date.now());
            if (Number.isNaN(date.getTime())) return "";
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
        }

        function dailyOrderTotals(orders, field = "total") {
            return dayBuckets().map(day => ({
                label: day.label,
                value: orders
                    .filter(order => orderDayKey(order) === day.key)
                    .reduce((sum, order) => sum + Number(order[field] || 0), 0)
            }));
        }

        function barChartCard(title, subtitle, rows, tone = "") {
            const max = Math.max(1, ...rows.map(row => Number(row.value) || 0));
            const bars = rows.map(row => {
                const width = Math.max(row.value > 0 ? 8 : 0, Math.round((Number(row.value) || 0) / max * 100));
                return `<div class="chart-row">
                    <span>${escapeHtml(row.label)}</span>
                    <div class="chart-track"><div class="chart-fill ${tone}" style="width:${width}%;"></div></div>
                    <b>${money(row.value)}</b>
                </div>`;
            }).join("");
            return `<div class="card chart-card">
                <div>
                    <h3>${escapeHtml(title)}</h3>
                    <p class="text-sm text-muted mt-2">${escapeHtml(subtitle)}</p>
                </div>
                <div class="bar-chart">${bars}</div>
            </div>`;
        }

        function commissionStatusCard(orders) {
            const paid = orders.filter(order => order.commStatus === "paid").reduce((sum, order) => sum + Number(order.comm || 0), 0);
            const pending = orders.filter(order => order.commStatus !== "paid").reduce((sum, order) => sum + Number(order.comm || 0), 0);
            const total = paid + pending;
            const paidAngle = total ? Math.round(paid / total * 360) : 0;
            return `<div class="card chart-card">
                <div>
                    <h3>Komissiya holati</h3>
                    <p class="text-sm text-muted mt-2">To'langan va to'lanmagan komissiyalar</p>
                </div>
                <div class="donut-wrap">
                    <div class="donut-chart" style="--paid-angle:${paidAngle}deg;">
                        <div class="donut-center">
                            <div class="font-bold">${total ? Math.round(paid / total * 100) : 0}%</div>
                            <div class="text-xs text-muted">to'langan</div>
                        </div>
                    </div>
                    <div class="legend-list">
                        <div class="legend-item"><span class="legend-name"><span class="legend-dot" style="background:var(--success)"></span> To'langan</span><b>${money(paid)}</b></div>
                        <div class="legend-item"><span class="legend-name"><span class="legend-dot" style="background:var(--warning)"></span> To'lanmagan</span><b>${money(pending)}</b></div>
                        <div class="legend-item"><span class="legend-name"><span class="legend-dot"></span> Jami</span><b>${money(total)}</b></div>
                    </div>
                </div>
            </div>`;
        }

        function adminProfitChartCard(orders) {
            const totalCommission = orders.reduce((sum, order) => sum + Number(order.comm || 0), 0);
            const companyProfit = orders.filter(order => order.commStatus === "paid").reduce((sum, order) => sum + Number(order.comm || 0), 0);
            const pendingCommission = totalCommission - companyProfit;
            return barChartCard("Komissiya va kompaniya foydasi", "Kompaniya foydasi to'langan komissiya asosida hisoblanadi", [
                { label: "Komissiya", value: totalCommission },
                { label: "Foyda", value: companyProfit },
                { label: "Kutilmoqda", value: pendingCommission }
            ], "info");
        }

        function weeklyProfitChartCard(orders) {
            return barChartCard("Haftalik savdo hajmi", "Oxirgi 7 kunlik buyurtmalar summasi", dailyOrderTotals(orders), "success");
        }

        function lineChartCard(orders) {
            const months = monthlyOrderTotals(orders, "total");
            const comms = monthlyOrderTotals(orders, "comm");
            
            let maxVal = Math.max(1, ...months.map(m => m.value));
            
            const width = 800;
            const height = 180;
            const stepX = width / Math.max(1, (months.length - 1));
            
            const points1 = months.map((m, i) => `${i * stepX},${height - (m.value / maxVal) * height}`).join(" ");
            const points2 = comms.map((m, i) => `${i * stepX},${height - (m.value / maxVal) * height}`).join(" ");
            
            return `<div class="card chart-card full-width-chart">
                <div>
                    <h3>Savdo va Komissiya dinamikasi</h3>
                    <p class="text-sm text-muted mt-2">Oylik buyurtmalar hajmi va komissiya o'sishi</p>
                </div>
                <div style="overflow-x:auto; padding-top:1rem; padding-bottom: 0.5rem; width:100%;">
                    <svg viewBox="-20 -10 ${width + 40} ${height + 30}" style="width:100%; height:auto; min-width:500px; overflow:visible;">
                        <polyline fill="none" stroke="var(--primary)" stroke-width="3" points="${points1}" stroke-linecap="round" stroke-linejoin="round"/>
                        <polyline fill="none" stroke="var(--warning)" stroke-width="3" points="${points2}" stroke-linecap="round" stroke-linejoin="round"/>
                        ${months.map((m, i) => `<circle cx="${i * stepX}" cy="${height - (m.value / maxVal) * height}" r="5" fill="white" stroke="var(--primary)" stroke-width="2"/>
                                                <text x="${i * stepX}" y="${height + 20}" font-size="12" fill="var(--muted)" font-weight="700" text-anchor="middle">${m.label}</text>`).join("")}
                        ${comms.map((m, i) => `<circle cx="${i * stepX}" cy="${height - (m.value / maxVal) * height}" r="5" fill="white" stroke="var(--warning)" stroke-width="2"/>`).join("")}
                    </svg>
                </div>
                <div class="legend-list" style="display:flex;gap:1.5rem;justify-content:center;margin-top:0.5rem;">
                    <div class="legend-item"><span class="legend-dot" style="background:var(--primary)"></span> Savdo hajmi</div>
                    <div class="legend-item"><span class="legend-dot" style="background:var(--warning)"></span> Komissiya</div>
                </div>
            </div>`;
        }

        function dashboardChartsHtml(orders, includeAdminProfit = false) {
            return `<div class="chart-grid mb-6">
                ${barChartCard("Oylik aylanma", "Oxirgi 6 oy buyurtma summalari", monthlyOrderTotals(orders), "")}
                ${commissionStatusCard(orders)}
                ${includeAdminProfit ? adminProfitChartCard(orders) : ""}
                ${includeAdminProfit ? weeklyProfitChartCard(orders) : ""}
                ${includeAdminProfit ? lineChartCard(orders) : ""}
            </div>`;
        }

        function renderAdminDash(container) {
            const activeOrders = DB.orders.filter(order => order.status !== "paid").length;
            const pendingComm = DB.orders.filter(order => order.commStatus === "pending").reduce((sum, order) => sum + order.comm, 0);
            container.innerHTML = `
                <div class="section-head">
                    <div>
                        <h2>Admin dashboard</h2>
                        <p class="text-sm text-muted mt-2">Boshlang'ich ma'lumotlar bo'sh. Register, katalog va buyurtmalar orqali ma'lumot yig'iladi.</p>
                    </div>
                </div>
                <div class="grid-cards mb-6">
                    ${statCard("ri-group-line", "Foydalanuvchilar", `${DB.users.length} ta`, "text-info")}
                    ${statCard("ri-store-2-line", "Mahsulotlar", `${DB.products.length} ta`, "text-success")}
                    ${statCard("ri-file-list-3-line", "Faol buyurtmalar", `${activeOrders} ta`, "text-warning")}
                    ${statCard("ri-money-dollar-circle-line", "Kutilayotgan komissiya", money(pendingComm), "text-primary")}
                </div>
                ${dashboardChartsHtml(DB.orders, true)}
                <div class="card">
                    <h3 class="mb-2">Ma'lumotlar holati</h3>
                    ${DB.users.length || DB.products.length || DB.orders.length
                        ? `<p class="text-muted">Tizimda foydalanuvchi, mahsulot yoki buyurtma mavjud. Menyudan kerakli bo'limga o'tib filterlardan foydalaning.</p>`
                        : `<div class="empty-state"><i class="ri-inbox-line"></i><b>Admin ma'lumotlari bo'sh</b><div class="mt-2">Foydalanuvchilar, buyurtmalar, komissiya va hisobotlar hozircha yo'q.</div></div>`}
                </div>
            `;
        }

        function renderAdminUsers(container) {
            const f = STATE.filters.users;
            const users = DB.users.filter(user => {
                const haystack = normalize([user.name, user.inn, user.phone, user.address].join(" "));
                return (!f.search || haystack.includes(normalize(f.search)))
                    && (f.role === "all" || user.role === f.role)
                    && (f.status === "all" || user.status === f.status);
            });
            container.innerHTML = `
                <div class="card">
                    <div class="section-head">
                        <div>
                            <h3>Foydalanuvchilar</h3>
                            <p class="text-sm text-muted">${users.length} ta natija</p>
                        </div>
                    </div>
                    <div class="filter-toolbar">
                        <div class="input-group" style="margin:0;">
                            <label>Qidirish</label>
                            <div class="search-field"><i class="ri-search-line"></i><input class="input-control" value="${escapeHtml(f.search)}" oninput="setFilter('users','search',this.value)" placeholder="Kompaniya, INN, telefon"></div>
                        </div>
                        <div class="input-group" style="margin:0;">
                            <label>Rol</label>
                            ${selectInline(f.role, [
                                { value: "all", label: "Barcha rollar" },
                                { value: "seller", label: "Sotuvchi" },
                                { value: "buyer", label: "Diler" }
                            ], "setFilter('users','role',this.value)")}
                        </div>
                        <div class="input-group" style="margin:0;">
                            <label>Holat</label>
                            ${selectInline(f.status, [
                                { value: "all", label: "Barcha holatlar" },
                                { value: "active", label: "Faol" },
                                { value: "blocked", label: "Blok" }
                            ], "setFilter('users','status',this.value)")}
                        </div>
                    </div>
                    ${users.length ? userTable(users) : emptyHtml("ri-user-search-line", "Foydalanuvchi topilmadi")}
                </div>
            `;
        }

        function userTable(users) {
            return `<div class="table-responsive"><table>
                <thead><tr><th>Kompaniya</th><th>Rol</th><th>Telefon</th><th>Holat</th><th>Amal</th></tr></thead>
                <tbody>${users.map(user => `
                    <tr>
                        <td><b>${escapeHtml(user.name)}</b><div class="text-xs text-muted">INN: ${escapeHtml(user.inn)}</div></td>
                        <td><span class="badge badge-info">${roleLabel(user.role)}</span></td>
                        <td>${escapeHtml(user.phone)}</td>
                        <td>${statusBadge(user.status)}</td>
                        <td><div class="flex gap-2">
                            <button class="btn btn-outline btn-icon" onclick="openUserDetails('${user.id}')" title="Ko'rish"><i class="ri-eye-line"></i></button>
                            <button class="btn ${user.status === "active" ? "btn-danger" : "btn-success"} btn-icon" onclick="toggleUserStatus('${user.id}')" title="Holatni o'zgartirish"><i class="${user.status === "active" ? "ri-forbid-2-line" : "ri-lock-unlock-line"}"></i></button>
                        </div></td>
                    </tr>`).join("")}</tbody>
            </table></div>`;
        }

        function renderAdminOrders(container) {
            const f = STATE.filters.orders;
            const orders = DB.orders.filter(order => {
                const haystack = normalize([order.id, userById(order.sellerId).name, userById(order.buyerId).name].join(" "));
                return (!f.search || haystack.includes(normalize(f.search))) && (f.status === "all" || order.status === f.status);
            });
            container.innerHTML = `
                <div class="card">
                    <div class="section-head"><div><h3>Buyurtmalar</h3><p class="text-sm text-muted">${orders.length} ta natija</p></div></div>
                    ${orderFilterHtml("orders")}
                    ${orders.length ? orderTable(orders) : emptyHtml("ri-file-list-3-line", "Buyurtmalar hozircha yo'q")}
                </div>`;
        }

        function renderAdminProducts(container) {
            const f = STATE.filters.products;
            const products = DB.products.filter(product => productMatches(product, f));
            container.innerHTML = `
                <div class="card">
                    <div class="section-head"><div><h3>Barcha mahsulotlar</h3><p class="text-sm text-muted">${products.length} ta natija</p></div></div>
                    ${productFilterHtml("products")}
                    ${products.length ? productGrid(products, false) : emptyHtml("ri-store-2-line", "Mahsulotlar hozircha yo'q")}
                </div>`;
        }

        function renderAdminModeration(container) {
            const products = DB.products.filter(product => ["pending", "rejected"].includes(product.status));
            const pendingCount = products.filter(product => product.status === "pending").length;
            container.innerHTML = `
                <div class="card">
                    <div class="section-head">
                        <div>
                            <h3>Mahsulot moderatsiyasi</h3>
                            <p class="text-sm text-muted">${pendingCount} ta mahsulot tasdiq kutmoqda</p>
                        </div>
                    </div>
                    ${products.length ? moderationTable(products) : emptyHtml("ri-shield-check-line", "Moderatsiya kutayotgan mahsulot yo'q")}
                </div>`;
        }

        function moderationTable(products) {
            return `<div class="table-responsive"><table>
                <thead><tr><th>Mahsulot</th><th>Sotuvchi</th><th>SKU</th><th>Holat</th><th>Izoh</th><th>Amal</th></tr></thead>
                <tbody>${products.map(product => `
                    <tr>
                        <td><b>${escapeHtml(product.name)}</b><div class="text-xs text-muted">${escapeHtml(categoryByValue(product.category).label)} · ${money(product.price)}</div></td>
                        <td>${escapeHtml(product.sellerName || userById(product.sellerId).name)}</td>
                        <td>${escapeHtml(product.sku)}</td>
                        <td>${statusBadge(product.status)}</td>
                        <td class="text-sm text-muted">${escapeHtml(product.moderationNote || "")}</td>
                        <td><div class="flex gap-2">
                            <button class="btn btn-success btn-icon" onclick="approveProduct('${product.id}')" title="Tasdiqlash"><i class="ri-check-line"></i></button>
                            <button class="btn btn-danger btn-icon" onclick="openRejectProduct('${product.id}')" title="Rad etish"><i class="ri-close-line"></i></button>
                        </div></td>
                    </tr>`).join("")}</tbody>
            </table></div>`;
        }

        function renderAdminComm(container) {
            const f = STATE.filters.comm;
            const orders = DB.orders.filter(order => {
                const haystack = normalize([order.id, userById(order.sellerId).name, userById(order.buyerId).name].join(" "));
                return (!f.search || haystack.includes(normalize(f.search))) && (f.status === "all" || order.commStatus === f.status);
            });
            container.innerHTML = `
                <div class="card">
                    <div class="section-head"><div><h3>Komissiya reyestri</h3><p class="text-sm text-muted">${orders.length} ta natija</p></div></div>
                    <div class="filter-toolbar">
                        <div class="input-group" style="margin:0;"><label>Qidirish</label><div class="search-field"><i class="ri-search-line"></i><input class="input-control" value="${escapeHtml(f.search)}" oninput="setFilter('comm','search',this.value)" placeholder="Buyurtma yoki sotuvchi"></div></div>
                        <div class="input-group" style="margin:0;"><label>Holat</label>${selectInline(f.status, [{ value: "all", label: "Barchasi" }, { value: "pending", label: "Kutilmoqda" }, { value: "pending_admin", label: "Tasdiq kutilmoqda" }, { value: "paid", label: "To'langan" }], "setFilter('comm','status',this.value)")}</div>
                    </div>
                    ${orders.length ? `<div class="table-responsive"><table><thead><tr><th>Buyurtma</th><th>Sotuvchi</th><th>Komissiya</th><th>Holat</th><th>Amal</th></tr></thead><tbody>${orders.map(order => `
                        <tr><td>#${order.id}</td><td>${escapeHtml(userById(order.sellerId).name)}</td><td class="font-bold text-primary">${money(order.comm)}</td><td>${statusBadge(order.commStatus)}</td>
                        <td>${order.commStatus === 'pending_admin' || order.status === 'seller_paid_comm' ? `<button class="btn btn-success" style="padding:0.4rem 0.8rem; font-size:0.8rem; min-height:0;" onclick="confirmCommPayment('${order.id}')">Qabul qildim</button>` : ''}</td></tr>`).join("")}</tbody></table></div>` : emptyHtml("ri-money-dollar-circle-line", "Komissiya ma'lumotlari yo'q")}
                </div>`;
        }

        function renderAdminReports(container) {
            const f = STATE.filters.reports;
            const reports = DB.reports.filter(report => {
                const product = productById(report.prodId);
                const order = DB.orders.find(item => item.id === report.orderId);
                const haystack = normalize([report.id, report.orderId, product?.name, userById(order?.buyerId).name].join(" "));
                return (!f.search || haystack.includes(normalize(f.search))) && (f.status === "all" || report.status === f.status);
            });
            container.innerHTML = `
                <div class="card">
                    <div class="section-head"><div><h3>Foto hisobotlar</h3><p class="text-sm text-muted">${reports.length} ta natija</p></div></div>
                    ${reportFilterHtml()}
                    ${reports.length ? reportGrid(reports, false) : emptyHtml("ri-camera-lens-line", "Foto hisobotlar hozircha yo'q")}
                </div>`;
        }

        function renderSellerDash(container) {
            const products = DB.products.filter(product => product.sellerId === STATE.currentUser.id);
            const orders = DB.orders.filter(order => order.sellerId === STATE.currentUser.id);
            container.innerHTML = `
                <div class="grid-cards mb-6">
                    ${statCard("ri-store-2-line", "Mahsulotlarim", `${products.length} ta`, "text-success")}
                    ${statCard("ri-file-list-3-line", "Buyurtmalar", `${orders.length} ta`, "text-info")}
                    ${statCard("ri-money-dollar-circle-line", "Aylanma", money(orders.reduce((sum, order) => sum + order.total, 0)), "text-primary")}
                </div>
                ${dashboardChartsHtml(orders)}
                <div class="card">${orders.length ? orderTable(orders.slice(0, 5)) : emptyHtml("ri-inbox-line", "Sizda buyurtmalar yo'q")}</div>`;
        }

        function renderSellerCatalog(container) {
            const f = STATE.filters.sellerProducts;
            const products = DB.products.filter(product => product.sellerId === STATE.currentUser.id && productMatches(product, f));
            container.innerHTML = `
                <div class="section-head">
                    <div><h2>Mening katalogim</h2><p class="text-sm text-muted">${products.length} ta mahsulot</p></div>
                    <button class="btn btn-primary" onclick="openProductForm()"><i class="ri-add-line"></i> Yangi qo'shish</button>
                </div>
                <div class="card">
                    ${productFilterHtml("sellerProducts")}
                    ${products.length ? productGrid(products, true) : emptyHtml("ri-store-2-line", "Mos mahsulot topilmadi")}
                </div>`;
        }

        function renderSellerOrders(container) {
            const orders = DB.orders.filter(order => order.sellerId === STATE.currentUser.id);
            container.innerHTML = `<div class="card"><div class="section-head"><div><h3>Sotuvlar</h3><p class="text-sm text-muted">${orders.length} ta buyurtma</p></div></div>${orders.length ? orderTable(orders) : emptyHtml("ri-shopping-bag-3-line", "Buyurtmalar yo'q")}</div>`;
        }

        function renderSellerFinance(container) {
            const orders = DB.orders.filter(order => order.sellerId === STATE.currentUser.id);
            container.innerHTML = `<div class="card"><div class="section-head"><div><h3>Moliya va komissiya</h3><p class="text-sm text-muted">5% platforma komissiyasi</p></div></div>
                ${orders.length ? `<div class="table-responsive"><table><thead><tr><th>Buyurtma</th><th>Summa</th><th>Komissiya</th><th>Holat</th><th>Amal</th></tr></thead><tbody>${orders.map(order => `<tr><td>#${order.id}</td><td>${money(order.total)}</td><td class="font-bold text-primary">${money(order.comm)}</td><td>${statusBadge(order.commStatus)}</td><td>${(order.commStatus === 'pending' || !order.commStatus) ? `<button class="btn btn-primary" style="padding:0.4rem 0.8rem; font-size:0.8rem; min-height:0;" title="Komissiya to'lash" onclick="openFinancePaymentModal('${order.id}')">To'lash</button>` : ''}</td></tr>`).join("")}</tbody></table></div>` : emptyHtml("ri-wallet-3-line", "Moliya ma'lumotlari yo'q")}
            </div>`;
        }

        function renderBuyerVitrina(container) {
            const f = STATE.filters.sellerProducts;
            const products = DB.products.filter(product => productMatches(product, f));
            container.innerHTML = `
                <div class="card">
                    <div class="section-head"><div><h3>Mahsulotlar bazasi</h3><p class="text-sm text-muted">${products.length} ta natija</p></div></div>
                    ${productFilterHtml("sellerProducts")}
                    ${products.length ? productGrid(products, false, true) : emptyHtml("ri-store-2-line", "Mahsulotlar hozircha yo'q")}
                </div>`;
        }

        function renderBuyerCart(container) {
            const items = DB.cart.filter(item => productById(item.prodId));
            DB.cart = items;
            saveState();
            if (!items.length) {
                container.innerHTML = `<div class="card">${emptyHtml("ri-shopping-cart-line", "Savatingiz bo'sh")}<div class="text-center mt-4"><button class="btn btn-primary" onclick="navigate('buyer-vitrina')">Mahsulot tanlash</button></div></div>`;
                return;
            }
            const total = items.reduce((sum, item) => sum + productById(item.prodId).price * item.qty, 0);
            container.innerHTML = `
                <div class="card">
                    <div class="section-head"><div><h3>Savat</h3><p class="text-sm text-muted">${items.length} turdagi mahsulot</p></div></div>
                    <div class="table-responsive"><table><thead><tr><th>Mahsulot</th><th>Miqdor</th><th>Jami</th><th>Amal</th></tr></thead><tbody>${items.map((item, index) => {
                        const product = productById(item.prodId);
                        return `<tr>
                            <td><b>${escapeHtml(product.name)}</b><div class="text-xs text-muted">${escapeHtml(userById(product.sellerId).name)}</div></td>
                            <td><div class="flex items-center gap-2"><button class="btn btn-outline btn-icon" onclick="updateCart(${index},-1)"><i class="ri-subtract-line"></i></button><b>${item.qty}</b><button class="btn btn-outline btn-icon" onclick="updateCart(${index},1)"><i class="ri-add-line"></i></button></div></td>
                            <td class="font-bold text-primary">${money(product.price * item.qty)}</td>
                            <td><button class="btn btn-danger btn-icon" onclick="removeFromCart(${index})"><i class="ri-delete-bin-line"></i></button></td>
                        </tr>`;
                    }).join("")}</tbody></table></div>
                    <div class="flex justify-between items-center gap-4 mt-4" style="flex-wrap:wrap;border-top:1px solid var(--border);padding-top:1rem;">
                        <div>Jami: <span class="font-bold text-primary" style="font-size:1.25rem;">${money(total)}</span></div>
                        <button class="btn btn-success" onclick="checkout()"><i class="ri-check-double-line"></i> Buyurtma berish</button>
                    </div>
                </div>`;
        }

        function renderBuyerOrders(container) {
            const orders = DB.orders.filter(order => order.buyerId === STATE.currentUser.id);
            container.innerHTML = `<div class="card"><div class="section-head"><div><h3>Mening buyurtmalarim</h3><p class="text-sm text-muted">${orders.length} ta buyurtma</p></div></div>${orders.length ? orderTable(orders) : emptyHtml("ri-file-list-3-line", "Buyurtmalar yo'q")}</div>`;
        }

        function renderBuyerReports(container) {
            const reports = DB.reports.filter(report => {
                const order = DB.orders.find(item => item.id === report.orderId);
                return order && order.buyerId === STATE.currentUser.id;
            });
            container.innerHTML = `<div class="card"><div class="section-head"><div><h3>Foto hisobotlar</h3><p class="text-sm text-muted">${reports.length} ta vazifa</p></div></div>${reports.length ? reportGrid(reports, true) : emptyHtml("ri-camera-line", "Foto hisobot vazifalari yo'q")}</div>`;
        }

        function contractTypeLabel(type) {
            return {
                platform_terms: "Platforma shartnomasi",
                seller_listing: "Sotuvchi mahsulot shartnomasi",
                buyer_order: "Xaridor buyurtma shartnomasi"
            }[type] || type || "Shartnoma";
        }

        function contractRelationText(contract) {
            if (contract.contractType === "buyer_order") {
                return `Xaridor: ${contract.signerName || "Noma'lum"}${contract.counterpartyName ? ` · Sotuvchi: ${contract.counterpartyName}` : ""}`;
            }
            if (contract.contractType === "seller_listing") {
                return `Sotuvchi: ${contract.signerName || "Noma'lum"}${contract.productName ? ` · Mahsulot: ${contract.productName}` : ""}`;
            }
            return `Foydalanuvchi: ${contract.signerName || "Noma'lum"}`;
        }

        function renderContracts(container) {
            const contracts = DB.contracts || [];
            container.innerHTML = `
                <div class="card">
                    <div class="section-head">
                        <div>
                            <h3>Shartnomalar</h3>
                            <p class="text-sm text-muted">${contracts.length} ta imzolangan shartnoma. Rekvizitlar DB ma'lumotlari asosida saqlangan.</p>
                        </div>
                    </div>
                    ${contracts.length ? `<div class="table-responsive"><table>
                        <thead><tr><th>№</th><th>Shartnoma</th><th>Tomonlar</th><th>Bog'lanish</th><th>Imzolangan vaqt</th><th>Amal</th></tr></thead>
                        <tbody>${contracts.map(contract => `
                            <tr>
                                <td class="font-bold">${contract.contractNumber || "-"}</td>
                                <td><b>${escapeHtml(contract.title || contractTypeLabel(contract.contractType))}</b><div class="text-xs text-muted">${escapeHtml(contractTypeLabel(contract.contractType))}</div></td>
                                <td>${escapeHtml(contractRelationText(contract))}</td>
                                <td class="text-sm text-muted">
                                    ${contract.orderId ? `Buyurtma #${escapeHtml(contract.orderId)}` : ""}
                                    ${contract.productName ? `<div>${escapeHtml(contract.productName)} ${contract.productSku ? `(${escapeHtml(contract.productSku)})` : ""}</div>` : ""}
                                    ${!contract.orderId && !contract.productName ? "Platforma" : ""}
                                </td>
                                <td>${escapeHtml(formatDateTime(contract.signedAt || contract.created_at))}</td>
                                <td><button class="btn btn-outline btn-icon" onclick="openContractDetails('${contract.id}')" title="Ko'rish"><i class="ri-eye-line"></i></button></td>
                            </tr>
                        `).join("")}</tbody>
                    </table></div>` : emptyHtml("ri-file-shield-2-line", "Shartnomalar hozircha yo'q")}
                </div>`;
        }

        function openContractDetails(id) {
            const contract = DB.contracts.find(item => item.id === id);
            if (!contract) return;
            modal(escapeHtml(contract.title || contractTypeLabel(contract.contractType)), `
                <div class="details-list mb-4">
                    <div class="details-row"><span class="details-label">Shartnoma raqami:</span><span class="details-value font-bold">${contract.contractNumber || "-"}</span></div>
                    <div class="details-row"><span class="details-label">Turi:</span><span class="details-value">${escapeHtml(contractTypeLabel(contract.contractType))}</span></div>
                    <div class="details-row"><span class="details-label">Imzolovchi:</span><span class="details-value">${escapeHtml(contract.signerName || "Noma'lum")} (${escapeHtml(roleLabel(contract.signerRole))})</span></div>
                    ${contract.counterpartyName ? `<div class="details-row"><span class="details-label">Qarshi tomon:</span><span class="details-value">${escapeHtml(contract.counterpartyName)} (${escapeHtml(roleLabel(contract.counterpartyRole))})</span></div>` : ""}
                    ${contract.orderId ? `<div class="details-row"><span class="details-label">Buyurtma:</span><span class="details-value">#${escapeHtml(contract.orderId)}</span></div>` : ""}
                    ${contract.productName ? `<div class="details-row"><span class="details-label">Mahsulot:</span><span class="details-value">${escapeHtml(contract.productName)}</span></div>` : ""}
                    <div class="details-row"><span class="details-label">Imzolangan vaqt:</span><span class="details-value">${escapeHtml(formatDateTime(contract.signedAt || contract.created_at))}</span></div>
                </div>
                ${contract.content || emptyHtml("ri-file-text-line", "Shartnoma matni topilmadi")}
            `, `<button class="btn btn-outline" onclick="closeModal()">Yopish</button>`);
        }

        function renderTickets(container) {
            const tickets = STATE.currentUser.role === "admin" ? DB.tickets : DB.tickets.filter(ticket => ticket.userId === STATE.currentUser.id);
            container.innerHTML = `
                <div class="card mb-4">
                    <div class="grid-2">
                        <div class="input-group"><label>Mavzu</label><input id="ticket-subject" class="input-control" placeholder="Masalan: Buyurtma muammosi"></div>
                        <div class="input-group"><label>Xabar</label><input id="ticket-message" class="input-control" placeholder="Muammo haqida yozing"></div>
                    </div>
                    <button class="btn btn-primary" onclick="createTicket()"><i class="ri-add-circle-line"></i> So'rov ochish</button>
                </div>
                <div class="ticket-grid">${tickets.length ? tickets.map(ticketCard).join("") : `<div class="card">${emptyHtml("ri-question-answer-line", "Yordam so'rovlari yo'q")}</div>`}</div>`;
        }

        function emptyHtml(icon, text) {
            return `<div class="empty-state"><i class="${icon}"></i><b>${escapeHtml(text)}</b></div>`;
        }

        function selectInline(value, options, action) {
            return `<select class="input-control" onchange="${action}">${options.map(option => `<option value="${escapeHtml(option.value)}" ${option.value === value ? "selected" : ""}>${escapeHtml(option.label)}</option>`).join("")}</select>`;
        }

        function setFilter(group, key, value) {
            STATE.filters[group][key] = value;
            renderCurrentView();
        }

        function productMatches(product, filter) {
            const haystack = normalize([product.name, product.sku, userById(product.sellerId).name, product.region].join(" "));
            return (!filter.search || haystack.includes(normalize(filter.search)))
                && (!filter.category || filter.category === "all" || product.category === filter.category)
                && (!filter.model || filter.model === "all" || product.model === filter.model)
                && (!filter.seller || filter.seller === "all" || product.sellerId === filter.seller);
        }

        function productFilterHtml(group) {
            const f = STATE.filters[group];
            const includeSeller = group === "products" && STATE.currentUser.role !== "seller";
            return `<div class="filter-toolbar">
                <div class="input-group" style="margin:0;"><label>Qidirish</label><div class="search-field"><i class="ri-search-line"></i><input class="input-control" value="${escapeHtml(f.search)}" oninput="setFilter('${group}','search',this.value)" placeholder="Mahsulot, SKU yoki sotuvchi"></div></div>
                <div class="input-group" style="margin:0;"><label>Kategoriya</label>${selectInline(f.category, categoryOptions(true), `setFilter('${group}','category',this.value)`)}</div>
                <div class="input-group" style="margin:0;"><label>Model</label>${selectInline(f.model, [{ value: "all", label: "Barcha modellar" }, { value: "realization", label: "Realizatsiya" }, { value: "prepayment", label: "Oldindan to'lov" }], `setFilter('${group}','model',this.value)`)}</div>
                ${includeSeller ? `<div class="input-group" style="margin:0;"><label>Sotuvchi</label>${selectInline(f.seller, sellerOptions(true), `setFilter('${group}','seller',this.value)`)}</div>` : ""}
            </div>`;
        }

        function orderFilterHtml(group) {
            const f = STATE.filters[group];
            return `<div class="filter-toolbar">
                <div class="input-group" style="margin:0;"><label>Qidirish</label><div class="search-field"><i class="ri-search-line"></i><input class="input-control" value="${escapeHtml(f.search)}" oninput="setFilter('${group}','search',this.value)" placeholder="Buyurtma, sotuvchi yoki diler"></div></div>
                <div class="input-group" style="margin:0;"><label>Holat</label>${selectInline(f.status, orderStatusOptions(true), `setFilter('${group}','status',this.value)`)}</div>
            </div>`;
        }

        function reportFilterHtml() {
            const f = STATE.filters.reports;
            return `<div class="filter-toolbar">
                <div class="input-group" style="margin:0;"><label>Qidirish</label><div class="search-field"><i class="ri-search-line"></i><input class="input-control" value="${escapeHtml(f.search)}" oninput="setFilter('reports','search',this.value)" placeholder="Buyurtma yoki mahsulot"></div></div>
                <div class="input-group" style="margin:0;"><label>Holat</label>${selectInline(f.status, [{ value: "all", label: "Barchasi" }, { value: "pending", label: "Kutilmoqda" }, { value: "overdue", label: "Kechikkan" }, { value: "done", label: "Bajarilgan" }], "setFilter('reports','status',this.value)")}</div>
            </div>`;
        }

        function productGrid(products, editable = false, buyerActions = false) {
            return `<div class="grid-products">${products.map(product => {
                const category = categoryByValue(product.category);
                const seller = userById(product.sellerId);
                const sellerName = product.sellerName || product.seller_name || seller.name;
                const realDays = parseNumberOrDefault(product.realDays ?? product.real_days, 30);
                const photoDays = parseNumberOrDefault(product.photoDays ?? product.photo_days, 15);
                const tradeLabel = product.model === "prepayment" ? `Oldindan to'lov ${formatPercent(product.prepayPercent ?? product.prepay_percent)}%` : "Realizatsiya";
                return `<div class="product-card">
                    <div class="product-img">${product.image ? `<img src="${imageUrl(product.image)}" alt="${escapeHtml(product.name)}">` : `<i class="${category.icon}"></i>`}</div>
                    <div class="product-info">
                        <div class="flex justify-between items-center gap-2"><span class="badge badge-info">${escapeHtml(category.label)}</span><span class="text-xs text-muted">${escapeHtml(product.sku)}</span></div>
                        <div class="flex justify-between items-center gap-2">${statusBadge(product.status)}${product.status === "rejected" && product.moderationNote ? `<span class="text-xs text-danger">${escapeHtml(product.moderationNote)}</span>` : ""}</div>
                        <div class="product-title">${escapeHtml(product.name)}</div>
                        <div class="product-price">${money(product.price)}</div>
                        <div class="specs">
                            <span><i class="ri-store-2-line"></i>${escapeHtml(sellerName)}</span>
                            <span><i class="ri-map-pin-line"></i>${escapeHtml(product.region || "Tanlanmagan")}</span>
                            <span><i class="ri-refresh-line"></i>${tradeLabel}</span>
                            <span><i class="ri-calendar-check-line"></i>Realizatsiya: ${realDays} kun</span>
                            <span><i class="ri-camera-line"></i>Foto hisobot: har ${photoDays} kun</span>
                        </div>
                        ${editable ? `<div class="grid-2 mt-2"><button class="btn btn-outline" onclick="openProductForm('${product.id}')"><i class="ri-edit-line"></i> Tahrir</button><button class="btn btn-danger" onclick="deleteProduct('${product.id}')"><i class="ri-delete-bin-line"></i> O'chirish</button></div>` : ""}
                        ${buyerActions ? `<button class="btn btn-primary w-full mt-2" onclick="addToCart('${product.id}')"><i class="ri-shopping-cart-2-line"></i> Savatga</button>` : ""}
                    </div>
                </div>`;
            }).join("")}</div>`;
        }

        function orderTable(orders) {
            return `<div class="table-responsive"><table>
                <thead><tr><th>Buyurtma</th><th>Sotuvchi / Diler</th><th class="hide-sm">Sana</th><th>Summa</th><th>Holat</th><th>Amal</th></tr></thead>
                <tbody>${orders.map(order => `<tr>
                    <td>#${order.id}</td>
                    <td><b>${escapeHtml(userById(order.sellerId).name)}</b><div class="text-xs text-muted">${escapeHtml(userById(order.buyerId).name)}</div></td>
                    <td class="hide-sm">${escapeHtml(order.date || order.createdAt || "")}</td>
                    <td class="font-bold text-primary">${money(order.total)}</td>
                    <td>${statusBadge(order.status)}</td>
                    <td><button class="btn btn-outline btn-icon" onclick="openOrderDetails('${order.id}')"><i class="ri-eye-line"></i></button></td>
                </tr>`).join("")}</tbody>
            </table></div>`;
        }

        function reportGrid(reports, uploadEnabled) {
            return `<div class="grid-reports">${reports.map(report => {
                const product = productById(report.prodId) || { name: "O'chirilgan mahsulot", category: "other" };
                return `<div class="report-card ${report.status}">
                    <div class="report-thumb">${report.image ? `<img src="${imageUrl(report.image)}" alt="${escapeHtml(product.name)}">` : `<i class="${categoryByValue(product.category).icon}"></i>`}</div>
                    <div class="flex justify-between items-center gap-2"><b>${escapeHtml(product.name)}</b>${statusBadge(report.status)}</div>
                    <div class="details-row"><span class="details-label">Buyurtma:</span><span class="details-value">#${report.orderId}</span></div>
                    <div class="details-row"><span class="details-label">Muddat:</span><span class="details-value">${escapeHtml(report.dueDate)}</span></div>
                    ${report.note ? `<div class="text-sm text-muted">${escapeHtml(report.note)}</div>` : ""}
                    ${uploadEnabled ? `<button class="btn ${report.status === "done" ? "btn-outline" : "btn-primary"}" onclick="openReportUpload('${report.id}')"><i class="ri-camera-line"></i> ${report.status === "done" ? "Yangilash" : "Yuklash"}</button>` : ""}
                </div>`;
            }).join("")}</div>`;
        }

        function openUserDetails(id) {
            const user = userById(id);
            modal("Foydalanuvchi ma'lumoti", `
                <div class="details-list">
                    <div class="details-row"><span class="details-label">Kompaniya:</span><span class="details-value">${escapeHtml(user.name)}</span></div>
                    <div class="details-row"><span class="details-label">INN:</span><span class="details-value">${escapeHtml(user.inn)}</span></div>
                    <div class="details-row"><span class="details-label">Rol:</span><span class="details-value">${roleLabel(user.role)}</span></div>
                    <div class="details-row"><span class="details-label">Telefon:</span><span class="details-value">${escapeHtml(user.phone)}</span></div>
                    <div class="details-row"><span class="details-label">Holat:</span><span class="details-value">${statusBadge(user.status)}</span></div>
                </div>`,
                `<button class="btn btn-outline" onclick="closeModal()">Yopish</button><button class="btn ${user.status === "active" ? "btn-danger" : "btn-success"}" onclick="toggleUserStatus('${id}', '${user.status === 'active' ? 'blocked' : 'active'}')">${user.status === "active" ? "Bloklash" : "Ochish"}</button>`);
        }


        function openProductForm(id = null) {
            STATE.editingProductId = id;
            const product = id ? productById(id) : null;
            const categoryValue = product?.category || DB.categories[0]?.value || "other";
            const region = product?.region || REGION_OPTIONS[0];
            modal(product ? "Mahsulotni tahrirlash" : "Yangi mahsulot", `
                ${product?.sku ? `<div class="input-group"><label>SKU</label><input class="input-control" value="${escapeHtml(product.sku)}" disabled></div>` : `<div class="text-sm text-muted mb-4"><i class="ri-barcode-line"></i> SKU avtomatik yaratiladi: RDP-001, RDP-002...</div>`}
                <div class="input-group"><label>Mahsulot nomi</label><input id="p-name" class="input-control" value="${escapeHtml(product?.name || "")}" placeholder="Nom"></div>
                <div class="grid-2">
                    <div class="input-group"><label>Kategoriya</label>${selectInline(categoryValue, categoryOptions(false), "")}</div>
                    <div class="input-group"><label>Yangi kategoriya</label><div class="flex gap-2"><input id="new-category" class="input-control" placeholder="Kategoriya qo'shish"><button class="btn btn-outline" onclick="addCategoryFromForm()">Qo'shish</button></div></div>
                </div>
                <div class="grid-2">
                    <div class="input-group"><label>Narx (UZS)</label><input id="p-price" type="number" class="input-control" value="${escapeHtml(product?.price || "")}" placeholder="0"></div>
                    <div class="input-group"><label>Viloyat</label>${selectInline(region, REGION_OPTIONS.map(item => ({ value: item, label: item })), "")}</div>
                </div>
                <div class="grid-2">
                    <div class="input-group"><label>Savdo modeli</label><select id="p-model" class="input-control" onchange="togglePrepayInput()"><option value="realization" ${product?.model !== "prepayment" ? "selected" : ""}>Realizatsiya</option><option value="prepayment" ${product?.model === "prepayment" ? "selected" : ""}>Oldindan to'lov</option></select></div>
                    <div class="input-group" id="prepay-wrap"><label>Oldindan to'lov (%)</label><input id="p-prepay" type="number" class="input-control" value="${escapeHtml(product?.prepayPercent || 30)}"></div>
                </div>
                <div class="grid-2">
                    <div class="input-group"><label>Realizatsiya muddati (kun)</label><input id="p-real-days" type="number" class="input-control" value="${escapeHtml(product?.realDays || 30)}"></div>
                    <div class="input-group"><label>Foto hisobot davri (kun)</label><input id="p-photo-days" type="number" class="input-control" value="${escapeHtml(product?.photoDays || 15)}"></div>
                </div>
                <div class="input-group"><label>Rasm</label><input id="p-image-file" type="file" accept="image/*" class="input-control"><input id="p-image-existing" type="hidden" value="${escapeHtml(product?.image || "")}"></div>
            `, `<button class="btn btn-outline" onclick="closeModal()">Bekor qilish</button><button class="btn btn-primary" onclick="saveProductForm()">Saqlash</button>`);
            const selects = document.querySelectorAll("#modal-body select");
            selects[0].id = "p-category";
            selects[1].id = "p-region";
            togglePrepayInput();
        }

        function togglePrepayInput() {
            const wrap = document.getElementById("prepay-wrap");
            const model = document.getElementById("p-model")?.value;
            if (wrap) wrap.classList.toggle("hidden", model !== "prepayment");
        }

        function addCategoryFromForm() {
            const input = document.getElementById("new-category");
            const label = input.value.trim();
            if (!label) {
                showToast("Kategoriya nomini yozing", "warning");
                return;
            }
            const value = slugify(label);
            let category = DB.categories.find(item => item.value === value || normalize(item.label) === normalize(label));
            if (!category) {
                category = { value, label, icon: "ri-price-tag-3-line" };
                DB.categories.push(category);
                saveState();
            }
            const select = document.getElementById("p-category");
            select.innerHTML = categoryOptions(false).map(option => `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`).join("");
            select.value = category.value;
            input.value = "";
            showToast("Kategoriya qo'shildi", "success");
        }

        function readFileAsDataUrl(file) {
            return new Promise((resolve, reject) => {
                if (!file) {
                    resolve("");
                    return;
                }
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        async function saveProductForm() {
            const name = document.getElementById("p-name").value.trim();
            const price = Number(document.getElementById("p-price").value);
            const category = document.getElementById("p-category").value;
            const region = document.getElementById("p-region").value;
            const model = document.getElementById("p-model").value;
            const prepayPercent = Number(document.getElementById("p-prepay").value);
            const realDays = Number(document.getElementById("p-real-days").value);
            const photoDays = Number(document.getElementById("p-photo-days").value);
            if (!name || !price || price <= 0) {
                showToast("Mahsulot nomi va narxni to'g'ri kiriting", "warning");
                return;
            }
            if (model === "prepayment" && (!prepayPercent || prepayPercent <= 0 || prepayPercent > 100)) {
                showToast("Oldindan to'lov foizini 1 dan 100 gacha kiriting", "warning");
                return;
            }
            if (!realDays || realDays <= 0 || !photoDays || photoDays <= 0) {
                showToast("Realizatsiya va foto hisobot kunlarini to'g'ri kiriting", "warning");
                return;
            }
            
            const file = document.getElementById("p-image-file").files[0];
            const formData = new FormData();
            formData.append("name", name);
            formData.append("price", price);
            formData.append("category", category);
            formData.append("region", region);
            formData.append("model", model);
            formData.append("prepay_percent", model === "prepayment" ? prepayPercent : "");
            formData.append("real_days", realDays);
            formData.append("photo_days", photoDays);
            
            if (file) formData.append("image", file);
            if (STATE.editingProductId) formData.append("id", STATE.editingProductId);

            if (!STATE.editingProductId) {
                STATE.pendingProductFormData = formData;
                modal("Mahsulot joylash shartnomasi", sellerListingContractHtml({ name, price, region, model, prepayPercent, realDays }), `
                    <button class="btn btn-outline" onclick="closeModal()">Bekor qilish</button>
                    <button class="btn btn-primary" onclick="acceptSellerListingContract()">Roziman va saqlash</button>
                `);
                return;
            }

            await submitProductForm(formData);
        }

        async function acceptSellerListingContract() {
            const formData = STATE.pendingProductFormData;
            if (!formData) {
                closeModal();
                return;
            }
            formData.set("contract_accepted", "1");
            await submitProductForm(formData);
            STATE.pendingProductFormData = null;
        }

        async function submitProductForm(formData) {
            try {
                await apiFetch('api/seller/products.php', 'POST', formData, true);
                await refreshDB();
                closeModal();
                showToast("Mahsulot saqlandi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        async function deleteProduct(id) {
            if (!confirm("Mahsulot o'chirilsinmi?")) return;
            try {
                const formData = new FormData();
                formData.append("action", "delete");
                formData.append("id", id);
                await apiFetch('api/seller/products.php', 'POST', formData, true);
                DB.cart = DB.cart.filter(item => item.prodId !== id);
                saveCart();
                await refreshDB();
                showToast("Mahsulot o'chirildi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        function addToCart(productId) {
            const product = productById(productId);
            if (!product) return;
            const existing = DB.cart.find(item => item.prodId === productId);
            if (existing) existing.qty += 1;
            else DB.cart.push({ prodId: productId, qty: 1 });
            saveCart();
            showToast("Mahsulot savatga qo'shildi", "success");
            renderCurrentView();
        }

        function updateCart(index, change) {
            const item = DB.cart[index];
            if (!item) return;
            item.qty += change;
            if (item.qty <= 0) DB.cart.splice(index, 1);
            saveCart();
            renderBuyerCart(document.getElementById("view-area"));
        }

        function removeFromCart(index) {
            DB.cart.splice(index, 1);
            saveCart();
            renderBuyerCart(document.getElementById("view-area"));
        }

        async function checkout(contractAccepted = false) {
            if (!DB.cart.length) return;
            const grouped = DB.cart.reduce((acc, item) => {
                const product = productById(item.prodId);
                if (!product) return acc;
                if (!acc[product.sellerId]) acc[product.sellerId] = [];
                acc[product.sellerId].push(item);
                return acc;
            }, {});
            const cartTotal = DB.cart.reduce((sum, item) => {
                const product = productById(item.prodId);
                return product ? sum + product.price * item.qty : sum;
            }, 0);

            if (!contractAccepted) {
                modal("Mahsulot yetkazib berish shartnomasi", buyerOrderContractHtml(cartTotal), `
                    <button class="btn btn-outline" onclick="closeModal()">Bekor qilish</button>
                    <button class="btn btn-primary" onclick="checkout(true)">Roziman va buyurtma berish</button>
                `);
                return;
            }
            
            try {
                for (const [sellerId, items] of Object.entries(grouped)) {
                    const total = items.reduce((sum, item) => sum + productById(item.prodId).price * item.qty, 0);
                    const formattedItems = items.map(item => ({
                        prodId: item.prodId,
                        qty: item.qty,
                        price: productById(item.prodId).price
                    }));
                    
                    await apiFetch('api/buyer/orders.php', 'POST', {
                        action: 'create_order',
                        sellerId,
                        total,
                        items: formattedItems,
                        contract_accepted: true
                    });
                }
                
                DB.cart = [];
                saveCart();
                await refreshDB();
                showToast("Buyurtma sotuvchiga yuborildi", "success");
                navigate("buyer-orders");
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        function addDays(days) {
            const date = new Date();
            date.setDate(date.getDate() + Number(days || 0));
            return date.toISOString().slice(0, 10);
        }

        function openOrderDetails(id) {
            const order = DB.orders.find(item => item.id === id);
            if (!order) return;
            const items = (order.items || []).map(item => {
                const product = productById(item.prodId) || { name: "O'chirilgan mahsulot", price: 0 };
                return `<div class="details-row"><span class="details-label">${escapeHtml(product.name)} x ${item.qty}</span><span class="details-value">${money(product.price * item.qty)}</span></div>`;
            }).join("") || `<div class="text-sm text-muted">Mahsulotlar ro'yxati topilmadi</div>`;
            const actions = orderActions(order);
            const orderContract = DB.contracts.find(contract => contract.contractType === "buyer_order" && contract.orderId === order.id);
            modal(`Buyurtma #${order.id}`, `
                <div class="details-list mb-4">
                    <div class="details-row"><span class="details-label">Sotuvchi:</span><span class="details-value">${escapeHtml(userById(order.sellerId).name)}</span></div>
                    <div class="details-row"><span class="details-label">Diler:</span><span class="details-value">${escapeHtml(userById(order.buyerId).name)}</span></div>
                    <div class="details-row"><span class="details-label">Sana:</span><span class="details-value">${escapeHtml(order.date || order.createdAt || "")}</span></div>
                    <div class="details-row"><span class="details-label">Holat:</span><span class="details-value">${statusBadge(order.status)}</span></div>
                    <div class="details-row"><span class="details-label">Jami:</span><span class="details-value text-primary">${money(order.total)}</span></div>
                </div>
                <h4 class="mb-2">Mahsulotlar</h4>
                <div class="details-list">${items}</div>
                ${orderContract ? `<div class="mt-4"><button class="btn btn-outline" onclick="openContractDetails('${orderContract.id}')"><i class="ri-file-shield-2-line"></i> Xaridor imzolagan shartnoma</button></div>` : ""}
            `, `<button class="btn btn-outline" onclick="closeModal()">Yopish</button>${actions}`);
        }

        function orderActions(order) {
            if (STATE.currentUser.role === "seller" && order.sellerId === STATE.currentUser.id) {
                if (order.status === "pending_seller_accept") return `<button class="btn btn-primary" onclick="updateOrderStatus('${order.id}','seller_accepted')">Qabul qildim</button>`;
                if (order.status === "seller_accepted") return `<button class="btn btn-primary" onclick="updateOrderStatus('${order.id}','dispatched')">Yetkazishga berildi</button>`;
                if (order.status === "dispatched") return `<button class="btn btn-primary" onclick="updateOrderStatus('${order.id}','delivered')">Yetkazildi</button>`;
                if (order.status === "buyer_paid") return `<button class="btn btn-success" onclick="updateOrderStatus('${order.id}','trade_closed')">To'lovni qabul qildim</button>`;
                if (order.status === "trade_closed") {
                    return `<button class="btn btn-primary" onclick="openPaymentModal('${order.id}', 'admin')">To'lash (Komissiya)</button>`;
                }
            }
            if (STATE.currentUser.role === "buyer" && order.buyerId === STATE.currentUser.id) {
                if (order.status === "delivered") {
                    return `<button class="btn btn-success" onclick="updateOrderStatus('${order.id}','buyer_accepted')">Qabul qildim (Mahsulotni)</button>`;
                }
                if (order.status === "buyer_accepted") {
                    return `<button class="btn btn-primary" onclick="openPaymentModal('${order.id}', 'seller')">To'lov qilish</button>`;
                }
            }
            if (STATE.currentUser.role === "admin" && order.status === "seller_paid_comm") {
                return `<button class="btn btn-success" onclick="updateOrderStatus('${order.id}','paid')">Komissiyani qabul qildim (Yakunlash)</button>`;
            }
            return "";
        }

        function getOrderContract(orderId) {
            return (DB.contracts || []).find(c => c.contractType === "buyer_order" && c.orderId === orderId);
        }

        function paymentPurposeText(contract, yattName) {
            const d = contract ? new Date(contract.signedAt || contract.created_at) : new Date();
            const day = d.getDate();
            const months = ["yanvar", "fevral", "mart", "aprel", "may", "iyun", "iyul", "avgust", "sentyabr", "oktyabr", "noyabr", "dekabr"];
            const month = months[d.getMonth()];
            const year = d.getFullYear();
            const num = contract ? contract.contractNumber : "___";
            const nameStr = yattName ? ` ${yattName} XK` : "";
            return `${year} yil «${day}» ${month}dagi ${num}-sonli shartnomaga asosan${nameStr} platforma xizmatlari uchun to'lov.`;
        }

        function openPaymentModal(orderId, type) {
            const order = DB.orders.find(item => item.id === orderId);
            if (!order) return;
            
            let title = "";
            let body = "";
            let action = "";
            const contract = getOrderContract(orderId);
            
            if (type === "seller") {
                const seller = userById(order.sellerId);
                const buyer = userById(order.buyerId);
                const buyerName = buyer.name || STATE.currentUser.name || "";
                const purposeText = paymentPurposeText(contract, buyerName);
                title = "To'lov cheki (Sotuvchiga)";
                body = `<div class="card" style="box-shadow:none;background:#f8fafc;padding:2rem;border:1px solid #dbeafe;text-align:center;">
                    <div style="font-size:3.5rem;color:var(--primary);margin-bottom:1rem;"><i class="ri-secure-payment-line"></i></div>
                    <h3 class="mb-3">Sotuvchi rekvizitlari</h3>
                    <div class="text-sm text-muted mb-4" style="background:white;padding:1rem;border-radius:8px;border:1px dashed #cbd5e1;">
                        <div style="margin-bottom:.5rem;">Bank hisob raqami:<br><b style="font-size:1.1rem;color:var(--text-dark);">${escapeHtml(seller.bankAccount || "Kiritilmagan")}</b></div>
                        <div>MFO:<br><b style="font-size:1.1rem;color:var(--text-dark);">${escapeHtml(seller.bankMfo || "Kiritilmagan")}</b></div>
                    </div>
                    <div class="mt-4 mb-2">
                        <div class="text-sm text-muted">To'lanadigan summa:</div>
                        <div class="font-bold text-primary" style="font-size:2rem;">${money(order.total)}</div>
                    </div>
                    <div class="mt-3 mb-2" style="background:#fffbeb;padding:1rem;border-radius:8px;border:1px solid #fbbf24;text-align:left;">
                        <div class="text-sm font-bold mb-1" style="color:#92400e;"><i class="ri-file-text-line"></i> To'lov maqsadi:</div>
                        <div class="text-sm" style="color:#78350f;">${escapeHtml(purposeText)}</div>
                    </div>
                    <p class="text-xs text-muted mt-4">Iltimos, to'lovni yuqoridagi rekvizitlarga amalga oshiring va tasdiqlang.</p>
                </div>`;
                action = `<button class="btn btn-primary" onclick="updateOrderStatus('${order.id}','buyer_paid')"><i class="ri-check-double-line"></i> To'ladim</button>`;
            } else if (type === "admin") {
                const seller = userById(order.sellerId);
                const sellerName = seller.name || STATE.currentUser.name || "";
                const purposeText = paymentPurposeText(contract, sellerName);
                title = "To'lov cheki (Komissiya)";
                body = `<div class="card" style="box-shadow:none;background:#f8fafc;padding:2rem;border:1px solid #dbeafe;text-align:center;">
                    <div style="font-size:3.5rem;color:var(--info);margin-bottom:1rem;"><i class="ri-bank-card-line"></i></div>
                    <h3 class="mb-3">Admin rekvizitlari (5% Komissiya)</h3>
                    <div class="text-sm text-muted mb-4" style="background:white;padding:1rem;border-radius:8px;border:1px dashed #cbd5e1;">
                        <div style="margin-bottom:.5rem;">INN:<br><b style="font-size:1.1rem;color:var(--text-dark);">310938488</b></div>
                        <div style="margin-bottom:.5rem;">X/R:<br><b style="font-size:1.1rem;color:var(--text-dark);">20208000905719313001</b></div>
                        <div>MFO:<br><b style="font-size:1.1rem;color:var(--text-dark);">00446</b></div>
                    </div>
                    <div class="mt-4 mb-2">
                        <div class="text-sm text-muted">To'lanadigan komissiya:</div>
                        <div class="font-bold text-primary" style="font-size:2rem;">${money(order.total * 0.05)}</div>
                    </div>
                    <div class="mt-3 mb-2" style="background:#fffbeb;padding:1rem;border-radius:8px;border:1px solid #fbbf24;text-align:left;">
                        <div class="text-sm font-bold mb-1" style="color:#92400e;"><i class="ri-file-text-line"></i> To'lov maqsadi:</div>
                        <div class="text-sm" style="color:#78350f;">${escapeHtml(purposeText)}</div>
                    </div>
                    <p class="text-xs text-muted mt-4">Iltimos, komissiya to'lovini yuqoridagi rekvizitlarga amalga oshiring va tasdiqlang.</p>
                </div>`;
                action = `<button class="btn btn-primary" onclick="updateOrderStatus('${order.id}','seller_paid_comm')"><i class="ri-check-double-line"></i> To'ladim</button>`;
            }
            
            modal(title, body, `<button class="btn btn-outline" onclick="openOrderDetails('${order.id}')"><i class="ri-arrow-left-line"></i> Orqaga</button>${action}`);
        }

        async function updateOrderStatus(id, status) {
            try {
                const role = STATE.currentUser.role;
                const endpoint = role === 'buyer' ? 'api/buyer/orders.php' : 'api/seller/orders.php';
                
                await apiFetch(endpoint, 'POST', { action: 'update_status', id, status });
                await refreshDB();
                closeModal();
                showToast("Buyurtma holati yangilandi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        function openFinancePaymentModal(orderId) {
            const order = DB.orders.find(item => item.id === orderId);
            if (!order) return;
            
            const contract = getOrderContract(orderId);
            const sellerName = STATE.currentUser.name || "";
            const purposeText = paymentPurposeText(contract, sellerName);
            
            const title = "To'lov cheki (Komissiya)";
            const body = `<div class="card" style="box-shadow:none;background:#f8fafc;padding:2rem;border:1px solid #dbeafe;text-align:center;">
                <div style="font-size:3.5rem;color:var(--info);margin-bottom:1rem;"><i class="ri-bank-card-line"></i></div>
                <h3 class="mb-3">Admin rekvizitlari (5% Komissiya)</h3>
                <div class="text-sm text-muted mb-4" style="background:white;padding:1rem;border-radius:8px;border:1px dashed #cbd5e1;">
                    <div style="margin-bottom:.5rem;">INN:<br><b style="font-size:1.1rem;color:var(--text-dark);">310938488</b></div>
                    <div style="margin-bottom:.5rem;">X/R:<br><b style="font-size:1.1rem;color:var(--text-dark);">20208000905719313001</b></div>
                    <div>MFO:<br><b style="font-size:1.1rem;color:var(--text-dark);">00446</b></div>
                </div>
                <div class="mt-4 mb-2">
                    <div class="text-sm text-muted">To'lanadigan komissiya:</div>
                    <div class="font-bold text-primary" style="font-size:2rem;">${money(order.comm || order.total * 0.05)}</div>
                </div>
                <div class="mt-3 mb-2" style="background:#fffbeb;padding:1rem;border-radius:8px;border:1px solid #fbbf24;text-align:left;">
                    <div class="text-sm font-bold mb-1" style="color:#92400e;"><i class="ri-file-text-line"></i> To'lov maqsadi:</div>
                    <div class="text-sm" style="color:#78350f;">${escapeHtml(purposeText)}</div>
                </div>
                <p class="text-xs text-muted mt-4">Iltimos, komissiya to'lovini yuqoridagi rekvizitlarga amalga oshiring va tasdiqlang.</p>
            </div>`;
            const action = `<button class="btn btn-primary" onclick="markCommPaidBySeller('${order.id}')"><i class="ri-check-double-line"></i> To'ladim</button>`;
            
            modal(title, body, `<button class="btn btn-outline" onclick="closeModal()">Orqaga</button>${action}`);
        }

        async function markCommPaidBySeller(orderId) {
            try {
                await apiFetch('api/seller/orders.php', 'POST', { action: 'update_status', id: orderId, status: 'seller_paid_comm' });
                await refreshDB();
                closeModal();
                showToast("To'lov qabul qilindi, adminga yuborildi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        async function confirmCommPayment(orderId) {
            try {
                await apiFetch('api/admin/commissions.php', 'POST', { action: 'confirm_comm', id: orderId });
                await refreshDB();
                showToast("Komissiya to'lovi tasdiqlandi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        async function approveProduct(productId) {
            try {
                await apiFetch('api/admin/products.php', 'POST', { action: 'approve', id: productId });
                await refreshDB();
                showToast("Mahsulot tasdiqlandi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        function openRejectProduct(productId) {
            const product = productById(productId);
            if (!product) return;
            modal("Mahsulotni rad etish", `
                <div class="input-group"><label>Mahsulot</label><input class="input-control" value="${escapeHtml(product.name)}" disabled></div>
                <div class="input-group"><label>Rad etish sababi</label><textarea id="reject-note" class="input-control" rows="4" placeholder="Masalan: rasm sifati past yoki ma'lumot yetarli emas"></textarea></div>
            `, `<button class="btn btn-outline" onclick="closeModal()">Bekor qilish</button><button class="btn btn-danger" onclick="rejectProduct('${productId}')">Rad etish</button>`);
        }

        async function rejectProduct(productId) {
            const note = document.getElementById("reject-note")?.value.trim();
            if (!note) {
                showToast("Rad etish sababini yozing", "warning");
                return;
            }
            try {
                await apiFetch('api/admin/products.php', 'POST', { action: 'reject', id: productId, note });
                await refreshDB();
                closeModal();
                showToast("Mahsulot rad etildi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        async function toggleUserStatus(userId, status) {
            try {
                await apiFetch('api/admin/users.php', 'POST', { action: 'toggle_status', id: userId, status });
                await refreshDB();
                closeModal();
                showToast("Foydalanuvchi holati o'zgartirildi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        function openReportUpload(id) {
            const report = DB.reports.find(item => item.id === id);
            if (!report) return;
            const product = productById(report.prodId) || { name: "Mahsulot" };
            modal("Foto hisobot yuklash", `
                <div class="report-thumb mb-4">${report.image ? `<img src="${imageUrl(report.image)}" alt="${escapeHtml(product.name)}">` : `<i class="ri-camera-line"></i>`}</div>
                <div class="input-group"><label>Mahsulot</label><input class="input-control" value="${escapeHtml(product.name)}" disabled></div>
                <div class="input-group"><label>Foto</label><input id="report-file" type="file" accept="image/*" class="input-control"></div>
                <div class="input-group"><label>Izoh</label><textarea id="report-note" class="input-control" rows="3" placeholder="Masalan: mahsulot vitrinada joylandi">${escapeHtml(report.note || "")}</textarea></div>
            `, `<button class="btn btn-outline" onclick="closeModal()">Bekor qilish</button><button class="btn btn-primary" onclick="saveReportUpload('${id}')">Yuborish</button>`);
        }

        async function saveReportUpload(id) {
            const report = DB.reports.find(item => item.id === id);
            if (!report) return;
            const file = document.getElementById("report-file").files[0];
            const note = document.getElementById("report-note").value.trim();
            
            if (!file && !report.image) {
                showToast("Foto tanlang", "warning");
                return;
            }

            try {
                const formData = new FormData();
                formData.append("id", id);
                formData.append("note", note);
                if (file) formData.append("file", file);

                await apiFetch('api/seller/reports.php', 'POST', formData, true);
                await refreshDB();
                closeModal();
                showToast("Foto hisobot yuborildi", "success");
                renderCurrentView();
            } catch (e) {
                showToast(e.message, "danger");
            }
        }

        async function createTicket() {
            const subject = document.getElementById("ticket-subject").value.trim();
            const message = document.getElementById("ticket-message").value.trim();
            if (!subject || !message) {
                showToast("Mavzu va xabarni kiriting", "warning");
                return;
            }
            try {
                await apiFetch('api/tickets.php', 'POST', { action: 'create', subject, message });
                await refreshDB();
                showToast("So'rov ochildi", "success");
                renderCurrentView();
            } catch(e) {
                showToast(e.message, "danger");
            }
        }

        function ticketCard(ticket) {
            const createdAt = formatDateTime(ticket.createdAt);
            const author = userById(ticket.userId);
            return `<article class="ticket-card">
                <div class="ticket-title-row">
                    <b class="ticket-subject">${escapeHtml(ticket.subject)}</b>
                    ${statusBadge(ticket.status)}
                </div>
                <div class="ticket-meta">
                    <span class="ticket-user-chip"><i class="ri-user-3-line"></i><span>${escapeHtml(author.name)}</span></span>
                    <span><i class="ri-time-line"></i> Ochilgan: ${escapeHtml(createdAt || "Sana ko'rsatilmagan")}</span>
                </div>
                <p class="ticket-message">${escapeHtml(ticket.message)}</p>
                <div class="ticket-reply-box">
                    <input class="input-control" id="reply-${ticket.id}" placeholder="Javob yozish">
                    <button class="btn btn-primary" onclick="replyTicket('${ticket.id}')">Javob</button>
                </div>
                <div class="ticket-replies">${(ticket.replies || []).map(reply => {
                    const replyCreatedAt = formatDateTime(reply.createdAt);
                    return `<div class="ticket-reply">
                        <div class="ticket-title-row"><b>${escapeHtml(reply.author)}</b><span class="text-xs text-muted">${escapeHtml(replyCreatedAt)}</span></div>
                        <div class="text-sm text-muted">${escapeHtml(reply.text)}</div>
                    </div>`;
                }).join("")}</div>
            </article>`;
        }

        async function replyTicket(id) {
            const ticket = DB.tickets.find(item => item.id === id);
            const input = document.getElementById(`reply-${id}`);
            const text = input?.value.trim();
            if (!ticket || !text) {
                showToast("Javob matnini yozing", "warning");
                return;
            }
            try {
                await apiFetch('api/tickets.php', 'POST', { action: 'reply', ticket_id: id, message: text });
                await refreshDB();
                showToast("Javob qo'shildi", "success");
                renderCurrentView();
            } catch(e) {
                showToast(e.message, "danger");
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll("#login-phone,#reg-phone").forEach(input => input.addEventListener("input", () => formatPhoneInput(input)));
            const registerTerms = document.getElementById("register-terms");
            if (registerTerms) registerTerms.addEventListener("change", event => document.getElementById("register-btn").disabled = !event.target.checked);
            document.addEventListener("click", event => {
                if (!event.target.closest(".topbar-left")) toggleMobileMenu(false);
                if (!event.target.closest(".notification-wrapper")) document.getElementById("notification-dropdown")?.classList.remove("open");
                if (!event.target.closest(".profile-wrapper")) document.getElementById("profile-dropdown")?.classList.remove("open");
            });
            initApp();
        });
