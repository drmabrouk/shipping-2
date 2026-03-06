<?php if (!defined('ABSPATH')) exit; ?>
<div class="shipping-admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
    <h2 style="margin: 0; font-weight: 800; color: var(--shipping-dark-color);">إدارة العملاء الموحدة</h2>
    <div style="display: flex; gap: 10px;">
        <a href="<?php echo admin_url('admin-ajax.php?action=shipping_export_csv&type=customers&nonce=' . wp_create_nonce('shipping_export_nonce')); ?>" class="shipping-btn" style="width:auto; background: #2f855a; text-decoration:none;">تصدير CSV</a>
        <button class="shipping-btn" onclick="ShippingModal.open('add-customer-modal')">+ إضافة عميل جديد</button>
    </div>
</div>

<!-- Professional Search Engine for Customers -->
<div class="shipping-search-engine-block">
    <form id="customer-advanced-search" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end;">
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b;">بحث شامل (الاسم، الهاتف، البريد، الكود):</label>
            <input type="text" id="customer-search-query" class="shipping-input" placeholder="أدخل بيانات البحث..." oninput="CustomersController.filterCustomers()">
        </div>
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b;">تصفية حسب التصنيف:</label>
            <select id="customer-filter-class" class="shipping-select" onchange="CustomersController.filterCustomers()">
                <option value="">كافة التصنيفات</option>
                <option value="regular">REGULAR</option>
                <option value="vip">VIP</option>
                <option value="corporate">CORPORATE</option>
            </select>
        </div>
        <div class="shipping-form-group" style="margin-bottom:0;">
            <label style="font-size: 12px; font-weight: 700; color: #64748b;">ترتيب العملاء:</label>
            <select id="customer-sort-order" class="shipping-select" onchange="CustomersController.filterCustomers()">
                <option value="newest">المسجلون حديثاً</option>
                <option value="oldest">الأقدم</option>
                <option value="name_asc">الاسم (أ-ي)</option>
                <option value="name_desc">الاسم (ي-أ)</option>
            </select>
        </div>
        <button type="button" onclick="CustomersController.resetFilters()" class="shipping-btn shipping-btn-outline" style="height: 45px; width: auto;">إعادة ضبط</button>
    </form>
</div>

<div class="shipping-card">
    <div class="shipping-table-container">
        <table class="shipping-table">
            <thead>
                <tr>
                    <th>العميل</th>
                    <th>بيانات الاتصال</th>
                    <th>الحساب</th>
                    <th>التصنيف</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody id="unified-customer-list">
                <?php
                global $wpdb;
                $customers = $wpdb->get_results("SELECT c.*, CONCAT(c.first_name, ' ', c.last_name) as name, u.user_email as wp_email FROM {$wpdb->prefix}shipping_customers c LEFT JOIN {$wpdb->prefix}users u ON c.wp_user_id = u.ID ORDER BY c.id DESC");
                if(empty($customers)): ?>
                    <tr><td colspan="5" style="text-align:center; padding:40px; color:#94a3b8;">لا يوجد عملاء مسجلين حالياً.</td></tr>
                <?php else: foreach($customers as $c): ?>
                    <tr class="customer-entry-row"
                        data-search="<?php echo esc_attr(strtolower($c->name . ' ' . $c->email . ' ' . $c->phone . ' ' . $c->username)); ?>"
                        data-class="<?php echo strtolower($c->classification ?: 'regular'); ?>"
                        data-name="<?php echo esc_attr($c->name); ?>"
                        data-id="<?php echo $c->id; ?>">
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <div style="width:40px; height:40px; border-radius:50%; background:#f1f5f9; display:flex; align-items:center; justify-content:center; overflow:hidden; border:1px solid #e2e8f0;">
                                    <?php if($c->photo_url): ?>
                                        <img src="<?php echo esc_url($c->photo_url); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <span class="dashicons dashicons-admin-users" style="color:#94a3b8;"></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight:800; color:var(--shipping-dark-color);"><?php echo esc_html($c->name); ?></div>
                                    <div style="font-size:11px; color:#64748b;">@<?php echo esc_html($c->username); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:13px;"><?php echo esc_html($c->email ?: $c->wp_email); ?></div>
                            <div style="font-size:11px; color:#64748b;"><?php echo esc_html($c->phone); ?></div>
                        </td>
                        <td>
                            <span class="shipping-badge <?php echo $c->account_status === 'active' ? 'shipping-badge-high' : 'shipping-badge-urgent'; ?>">
                                <?php echo $c->account_status === 'active' ? 'نشط' : 'مقيد'; ?>
                            </span>
                        </td>
                        <td><span class="shipping-badge" style="background:#edf2f7; color:#4a5568;"><?php echo esc_html(strtoupper($c->classification ?: 'REGULAR')); ?></span></td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <button onclick="CustomersController.viewCustomerDossier(<?php echo $c->id; ?>)" class="shipping-btn-outline" style="padding:6px 10px; font-size:11px;">الملف الشامل</button>
                                <button onclick="CustomersController.openEditSimple(<?php echo htmlspecialchars(json_encode($c)); ?>)" class="shipping-btn-outline" style="padding:6px 10px; font-size:11px; color:#2d3748; border-color:#2d3748;">تعديل سريع</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Unified Customer Dossier Modal -->
<div id="modal-customer-dossier" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 950px; height: 90vh; display: flex; flex-direction: column;">
        <div class="shipping-modal-header">
            <h3 id="dossier-customer-name">ملف العميل</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-customer-dossier')">&times;</button>
        </div>
        <div id="dossier-loading" style="padding:50px; text-align:center;"><div class="shipping-loader-mini"></div><br>جاري تجميع بيانات الملف...</div>
        <div id="dossier-content" style="display:none; flex:1; overflow:hidden;">
            <div style="display:grid; grid-template-columns: 300px 1fr; height:100%;">
                <!-- Right Menu (Fixed) -->
                <div style="background:#f8fafc; border-left:1px solid #e2e8f0; padding:20px; display:flex; flex-direction:column; gap:10px;">
                    <div id="dossier-avatar-container" style="text-align:center; margin-bottom:20px;">
                        <!-- Avatar via JS -->
                    </div>
                    <button class="dossier-nav-btn active" onclick="CustomersController.switchDossierTab('profile', this)">البيانات الشخصية</button>
                    <button class="dossier-nav-btn" onclick="CustomersController.switchDossierTab('account', this)">إدارة الحساب والأمان</button>
                    <button class="dossier-nav-btn" onclick="CustomersController.switchDossierTab('shipments', this)">سجل الشحنات</button>
                    <button class="dossier-nav-btn" onclick="CustomersController.switchDossierTab('addresses', this)">دفتر العناوين</button>
                    <button class="dossier-nav-btn" onclick="CustomersController.switchDossierTab('contracts', this)">العقود والاتفاقيات</button>
                    <div style="margin-top:auto; padding-top:20px; border-top:1px solid #e2e8f0;">
                        <button class="shipping-btn" style="background:#e53e3e; width:100%;" id="btn-delete-customer">حذف الحساب نهائياً</button>
                    </div>
                </div>

                <!-- Left Content (Scrollable) -->
                <div style="padding:30px; overflow-y:auto;" id="dossier-tab-container">
                    <!-- Tab: Profile -->
                    <div id="dossier-tab-profile" class="dossier-tab active">
                        <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">تعديل الملف الشخصي</h4>
                        <form id="form-update-profile">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                                <div class="shipping-form-group"><label>الاسم الأول</label><input type="text" name="first_name" class="shipping-input"></div>
                                <div class="shipping-form-group"><label>اسم العائلة</label><input type="text" name="last_name" class="shipping-input"></div>
                                <div class="shipping-form-group"><label>الهاتف</label><input type="text" name="phone" class="shipping-input"></div>
                                <div class="shipping-form-group"><label>الهاتف البديل</label><input type="text" name="alt_phone" class="shipping-input"></div>
                                <div class="shipping-form-group">
                                    <label>التصنيف</label>
                                    <select name="classification" class="shipping-select">
                                        <option value="regular">عادي</option>
                                        <option value="vip">VIP</option>
                                        <option value="corporate">شركات</option>
                                    </select>
                                </div>
                                <div class="shipping-form-group">
                                    <label>حالة الحساب</label>
                                    <select name="account_status" class="shipping-select">
                                        <option value="active">نشط</option>
                                        <option value="restricted">مقيد</option>
                                    </select>
                                </div>
                            </div>
                            <div class="shipping-form-group"><label>العنوان السكني</label><textarea name="residence_street" class="shipping-textarea" rows="2"></textarea></div>
                            <div class="shipping-form-group"><label>ملاحظات إدارية</label><textarea name="notes" class="shipping-textarea" rows="2"></textarea></div>
                            <button type="submit" class="shipping-btn">حفظ التغييرات</button>
                        </form>
                    </div>

                    <!-- Tab: Account -->
                    <div id="dossier-tab-account" class="dossier-tab" style="display:none;">
                        <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">إدارة الدخول والأمان</h4>
                        <form id="form-update-account-security" style="background:#f8fafc; padding:20px; border-radius:10px; border:1px solid #e2e8f0;">
                            <div class="shipping-form-group">
                                <label>البريد الإلكتروني المخصص للدخول</label>
                                <input type="email" name="account_email" class="shipping-input" required>
                                <small style="color:#64748b;">سيتم استخدام هذا البريد لاستعادة كلمة المرور والتنبيهات الرسمية.</small>
                            </div>
                            <div class="shipping-form-group">
                                <label>تعيين كلمة مرور جديدة</label>
                                <div style="display:flex; gap:10px;">
                                    <input type="text" name="new_password" id="dossier-new-pass" class="shipping-input" placeholder="اترك الحقل فارغاً لعدم التغيير">
                                    <button type="button" class="shipping-btn-outline" style="width:auto;" onclick="CustomersController.generateRandomPass()">توليد</button>
                                </div>
                            </div>
                            <button type="submit" class="shipping-btn" style="background:#2d3748;">تحديث بيانات الحساب</button>
                        </form>
                    </div>

                    <!-- Tab: Shipments -->
                    <div id="dossier-tab-shipments" class="dossier-tab" style="display:none;">
                        <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">سجل الشحنات المباشر</h4>
                        <div class="shipping-table-container">
                            <table class="shipping-table" style="font-size:12px;">
                                <thead><tr><th>رقم الشحنة</th><th>الوجهة</th><th>التاريخ</th><th>الحالة</th><th>إجراءات</th></tr></thead>
                                <tbody id="dossier-shipments-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab: Addresses -->
                    <div id="dossier-tab-addresses" class="dossier-tab" style="display:none;">
                        <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">دفتر العناوين المعتمد</h4>
                        <div id="dossier-address-list"></div>
                        <button class="shipping-btn-outline" style="margin-top:20px;" onclick="alert('ميزة قيد التطوير')">+ إضافة عنوان جديد</button>
                    </div>

                    <!-- Tab: Contracts -->
                    <div id="dossier-tab-contracts" class="dossier-tab" style="display:none;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;">
                            <h4 style="margin:0;">العقود والاتفاقيات</h4>
                            <button class="shipping-btn" style="width:auto; padding:5px 15px; font-size:12px;" onclick="ShippingModal.open('modal-add-contract')">+ إضافة عقد</button>
                        </div>
                        <div class="shipping-table-container">
                            <table class="shipping-table" style="font-size:12px;">
                                <thead><tr><th>رقم العقد</th><th>العنوان</th><th>تاريخ الانتهاء</th><th>الحالة</th></tr></thead>
                                <tbody id="dossier-contracts-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dossier-nav-btn {
    text-align: right; padding: 12px 15px; background: #fff; border: 1px solid #e2e8f0;
    border-radius: 10px; cursor: pointer; font-weight: 700; color: #4a5568; transition: 0.2s;
    font-family: inherit;
}
.dossier-nav-btn:hover { background: #edf2f7; color: var(--shipping-primary-color); }
.dossier-nav-btn.active { background: var(--shipping-primary-color); color: #fff; border-color: var(--shipping-primary-color); }
.dossier-tab { animation: shippingFadeIn 0.3s ease; }
</style>

<!-- Modals -->
<div id="add-customer-modal" class="shipping-modal-overlay">
    <div class="shipping-modal-content">
        <div class="shipping-modal-header"><h3>إضافة عميل جديد</h3><button class="shipping-modal-close" onclick="ShippingModal.close('add-customer-modal')">&times;</button></div>
        <form id="shipping-add-customer-form" style="padding:20px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="shipping-form-group"><label>الاسم الأول:</label><input type="text" name="first_name" class="shipping-input" required></div>
                <div class="shipping-form-group"><label>اسم العائلة:</label><input type="text" name="last_name" class="shipping-input" required></div>
            </div>
            <div class="shipping-form-group"><label>اسم المستخدم:</label><input type="text" name="username" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>البريد الإلكتروني:</label><input type="email" name="email" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>الهاتف:</label><input type="text" name="phone" class="shipping-input" required></div>
            <div class="shipping-form-group"><label>المدينة:</label><input type="text" name="residence_city" class="shipping-input"></div>
            <div class="shipping-form-group">
                <label>تصنيف العميل:</label>
                <select name="classification" class="shipping-select">
                    <option value="regular">عادي (Regular)</option>
                    <option value="vip">VIP</option>
                    <option value="corporate">شركات (Corporate)</option>
                </select>
            </div>
            <button type="submit" class="shipping-btn" style="width:100%;">حفظ بيانات العميل</button>
        </form>
    </div>
</div>

<div id="modal-add-contract" class="shipping-modal-overlay">
    <div class="shipping-modal-content" style="max-width: 550px;">
        <div class="shipping-modal-header">
            <h3>إضافة عقد جديد</h3>
            <button class="shipping-modal-close" onclick="ShippingModal.close('modal-add-contract')">&times;</button>
        </div>
        <form id="form-add-contract">
            <input type="hidden" name="action" value="shipping_add_contract">
            <?php wp_nonce_field('shipping_contract_action', 'nonce'); ?>
            <div class="shipping-modal-body">
                <div class="shipping-form-group">
                    <label>العميل</label>
                    <select name="customer_id" class="shipping-input" required>
                        <option value="">اختر العميل...</option>
                        <?php foreach($customers as $c) echo "<option value='{$c->id}'>".esc_html($c->first_name . ' ' . $c->last_name)."</option>"; ?>
                    </select>
                </div>
                <div class="shipping-form-group">
                    <label>عنوان العقد (مثال: اتفاقية توريد سنوية)</label>
                    <input type="text" name="title" class="shipping-input" required>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="shipping-form-group">
                        <label>تاريخ البدء</label>
                        <input type="date" name="start_date" class="shipping-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="shipping-form-group">
                        <label>تاريخ الانتهاء</label>
                        <input type="date" name="end_date" class="shipping-input" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                    </div>
                </div>
                <div class="shipping-form-group">
                    <label>رابط الملف (PDF)</label>
                    <input type="text" name="file_url" class="shipping-input" placeholder="https://...">
                </div>
                <div class="shipping-form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes" class="shipping-textarea" rows="2"></textarea>
                </div>
            </div>
            <div class="shipping-modal-footer">
                <button type="submit" class="shipping-btn">حفظ العقد</button>
            </div>
        </form>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    if ("<?php echo $sub; ?>" === 'contracts') CustomersController.loadContracts();
});
</script>
