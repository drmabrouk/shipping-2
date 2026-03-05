<?php if (!defined('ABSPATH')) exit; ?>
<div class="shipping-content-wrapper" dir="rtl">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <h3 style="margin:0; border:none; padding:0;">إدارة مستخدمي النظام (كافة الأدوار والأذونات)</h3>
        <?php if (current_user_can('manage_options')): ?>
            <div style="display:flex; gap:10px; flex-wrap: wrap;">
                <button onclick="UsersController.executeBulkDelete()" class="shipping-btn" style="width:auto; background:#e53e3e;">حذف المستخدمين المحددين</button>
                <button onclick="ShippingModal.open('unified-import-form')" class="shipping-btn" style="width:auto; background:var(--shipping-secondary-color);">استيراد جماعي (CSV)</button>
                <button onclick="ShippingModal.open('add-user-modal')" class="shipping-btn" style="width:auto;">+ إضافة مستخدم جديد</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Unified Import Form -->
    <div id="unified-import-form" class="shipping-modal-overlay">
        <div class="shipping-modal-content" style="max-width: 800px;">
            <div class="shipping-modal-header">
                <h3>مركز استيراد المستخدمين والعملاء</h3>
                <button class="shipping-modal-close" onclick="ShippingModal.close('unified-import-form')">&times;</button>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-top:0;">استيراد مستخدمين/مسؤولين (Staff)</h4>
                    <p style="font-size: 12px; color: #64748b; margin-bottom: 15px;">(اسم المستخدم، البريد، الاسم الأول، اسم العائلة، الكود، المسمى، الهاتف، كلمة المرور)</p>
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('shipping_admin_action', 'shipping_admin_nonce'); ?>
                        <input type="file" name="csv_file" accept=".csv" required style="margin-bottom:10px; width:100%;">
                        <button type="submit" name="shipping_import_staffs_csv" class="shipping-btn" style="background:var(--shipping-primary-color); width:100%;">بدء استيراد المسؤولين</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    $current_user = wp_get_current_user();
    $is_sys_manager = in_array('administrator', (array)$current_user->roles);
    ?>

    <div style="background: white; padding: 30px; border: 1px solid var(--shipping-border-color); border-radius: var(--shipping-radius); margin-bottom: 30px; box-shadow: var(--shipping-shadow);">
        <form method="get" style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 20px; align-items: end;">
            <input type="hidden" name="shipping_tab" value="advanced-settings">
            <input type="hidden" name="sub" value="users-management">

            <div class="shipping-form-group" style="margin-bottom:0;">
                <label class="shipping-label">بحث عن مستخدم (اسم/بريد/كود/اسم مستخدم):</label>
                <input type="text" name="user_search" class="shipping-input" value="<?php echo esc_attr(isset($_GET['user_search']) ? $_GET['user_search'] : ''); ?>" placeholder="أدخل بيانات البحث...">
            </div>

            <div class="shipping-form-group" style="margin-bottom:0;">
                <label class="shipping-label">تصفية حسب الدور:</label>
                <select name="role_filter" class="shipping-select">
                    <option value="">كل الأدوار الإدارية</option>
                    <option value="administrator" <?php selected($_GET['role_filter'] ?? '', 'administrator'); ?>>مديرو النظام (Administrators)</option>
                    <option value="subscriber" <?php selected($_GET['role_filter'] ?? '', 'subscriber'); ?>>العملاء (للمراجعة فقط)</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="shipping-btn">تطبيق البحث</button>
                <a href="<?php echo add_query_arg(array('shipping_tab'=>'advanced-settings', 'sub' => 'users-management'), remove_query_arg(array('user_search', 'role_filter', 'paged'))); ?>" class="shipping-btn shipping-btn-outline" style="text-decoration:none;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <div class="shipping-table-container">
        <table class="shipping-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" onclick="UsersController.toggleAll(this)"></th>
                    <th>اسم المستخدم / الكود</th>
                    <th>الاسم</th>
                    <th>الدور</th>
                    <th>رقم التواصل</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $role_labels = array(
                    'administrator' => 'مدير نظام',
                    'subscriber'    => 'عميل'
                );

                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $limit = 20;
                $offset = ($current_page - 1) * $limit;

                $args = array(
                    'number' => $limit,
                    'offset' => $offset,
                    'role__in' => array('administrator', 'subscriber')
                );

                if (!empty($_GET['role_filter'])) {
                    $args['role'] = sanitize_text_field($_GET['role_filter']);
                }

                if (!empty($_GET['user_search'])) {
                    $args['search'] = '*' . esc_attr($_GET['user_search']) . '*';
                    $args['search_columns'] = array('user_login', 'display_name', 'user_email');
                }

                $users = Shipping_DB::get_staff($args); // This already handles gov filtering for local admins

                if (empty($users)): ?>
                    <tr><td colspan="7" style="padding: 40px; text-align: center;">لا يوجد مستخدمون يطابقون البحث.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $u):
                        $role = (array)$u->roles;
                        $role_slug = reset($role);
                        $customer_id = null;
                        if ($role_slug === 'subscriber') {
                            global $wpdb;
                            $customer_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $u->ID));
                        }
                    ?>
                        <tr class="user-row" data-user-id="<?php echo $u->ID; ?>">
                            <td><input type="checkbox" class="user-cb" value="<?php echo $u->ID; ?>"></td>
                            <td style="font-weight: 700; color: var(--shipping-primary-color);">
                                <?php echo esc_html(get_user_meta($u->ID, 'shippingCustomerIdAttr', true) ?: $u->user_login); ?>
                            </td>
                            <td style="font-weight: 800;"><?php echo esc_html($u->display_name); ?></td>
                            <td><span class="shipping-badge <?php echo $role_slug == 'administrator' ? 'shipping-badge-high' : 'shipping-badge-low'; ?>"><?php echo $role_labels[$role_slug] ?? $role_slug; ?></span></td>
                            <td dir="ltr" style="text-align: right;"><?php echo esc_html(get_user_meta($u->ID, 'shipping_phone', true)); ?></td>
                            <td>
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <?php if ($customer_id): ?>
                                        <a href="<?php echo add_query_arg(['shipping_tab' => 'customer-profile', 'customer_id' => $customer_id]); ?>" onclick="ShippingState.setCustomer(<?php echo $customer_id; ?>)" class="shipping-btn shipping-btn-outline" style="padding: 5px 12px; font-size: 12px; height: 32px; text-decoration:none;">الملف</a>
                                    <?php endif; ?>
                                    <?php
                                    $u_first_name = get_user_meta($u->ID, 'first_name', true);
                                    $u_last_name = get_user_meta($u->ID, 'last_name', true);
                                    if (!$u_first_name && $u->display_name) {
                                        $parts = explode(' ', $u->display_name);
                                        $u_first_name = $parts[0];
                                        $u_last_name = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '';
                                    }
                                    ?>
                                    <button onclick='shippingEditUser(<?php echo esc_attr(wp_json_encode(array(
                                        "id" => $u->ID,
                                        "first_name" => $u_first_name,
                                        "last_name" => $u_last_name,
                                        "email" => $u->user_email,
                                        "login" => $u->user_login,
                                        "role" => $role_slug,
                                        "customer_id_attr" => get_user_meta($u->ID, "shippingCustomerIdAttr", true),
                                        "phone" => get_user_meta($u->ID, "shipping_phone", true),
                                        "status" => get_user_meta($u->ID, "shipping_account_status", true) ?: "active"
                                    ))); ?>)' class="shipping-btn shipping-btn-outline" style="padding: 5px 12px; font-size: 12px; height: 32px;">تعديل</button>
                                    <button onclick="UsersController.deleteUser(<?php echo $u->ID; ?>, '<?php echo esc_js($u->display_name); ?>')" class="shipping-btn" style="background:#e53e3e; padding: 5px 12px; font-size: 12px; height: 32px;">حذف</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php
    $total_users = count(Shipping_DB::get_staff(array_merge($args, ['number' => -1, 'offset' => 0])));
    $total_pages = ceil($total_users / $limit);
    if ($total_pages > 1):
    ?>
    <div class="shipping-pagination" style="margin-top: 20px; display: flex; gap: 5px; justify-content: center;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="<?php echo add_query_arg('paged', $i); ?>" class="shipping-btn <?php echo $i == $current_page ? '' : 'shipping-btn-outline'; ?>" style="padding: 5px 12px; min-width: 40px; text-align: center;"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <!-- Add User Modal -->
    <div id="add-user-modal" class="shipping-modal-overlay">
        <div class="shipping-modal-content" style="max-width: 800px;">
            <div class="shipping-modal-header">
                <h3>إضافة مستخدم جديد للنظام</h3>
                <button class="shipping-modal-close" onclick="ShippingModal.close('add-user-modal')">&times;</button>
            </div>
            <form id="add-user-form">
                <?php wp_nonce_field('shippingCustomerAction', 'shipping_nonce'); ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; padding: 25px;">
                    <div class="shipping-form-group">
                        <label class="shipping-label">الاسم الأول:</label>
                        <input type="text" name="first_name" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">اسم العائلة:</label>
                        <input type="text" name="last_name" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">اسم المستخدم / الكود:</label>
                        <input type="text" name="officer_id" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">اختيار الدور:</label>
                        <select name="role" class="shipping-select" onchange="UsersController.toggleCustomerFields(this.value)">
                            <option value="subscriber">عميل (Subscriber)</option>
                            <?php if ($is_sys_manager): ?>
                                <option value="administrator">مدير نظام (Administrator)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">رقم الهاتف:</label>
                        <input type="text" name="phone" class="shipping-input">
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">اسم المستخدم (Login):</label>
                        <input type="text" name="user_login" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">كلمة المرور (اختياري):</label>
                        <input type="password" name="user_pass" class="shipping-input" placeholder="********">
                    </div>
                </div>
                <div id="customer-specific-fields" style="display: block; padding: 0 25px 25px; border-top: 1px solid #eee; padding-top: 20px;">
                    <h4 style="margin-top:0;">بيانات الحساب (اختياري)</h4>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div class="shipping-form-group"><label class="shipping-label">رقم التعريف:</label><input name="id_number" type="text" class="shipping-input"></div>
                        <div class="shipping-form-group"><label class="shipping-label">حالة الحساب:</label>
                            <select name="account_status" class="shipping-select">
                                <?php foreach (Shipping_Settings::get_account_statuses() as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div style="padding: 25px; background: #f8fafc; text-align: left;">
                    <button type="submit" class="shipping-btn" style="width: auto; padding: 10px 40px;">إنشاء الحساب الآن</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-user-modal" class="shipping-modal-overlay">
        <div class="shipping-modal-content" style="max-width: 700px;">
            <div class="shipping-modal-header">
                <h3>تعديل بيانات الحساب</h3>
                <button class="shipping-modal-close" onclick="ShippingModal.close('edit-user-modal')">&times;</button>
            </div>
            <form id="edit-user-form">
                <?php wp_nonce_field('shippingCustomerAction', 'shipping_nonce'); ?>
                <input type="hidden" name="edit_officer_id" id="edit_user_db_id">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; padding: 25px;">
                    <div class="shipping-form-group">
                        <label class="shipping-label">الاسم الأول:</label>
                        <input type="text" name="first_name" id="edit_user_first_name" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">اسم العائلة:</label>
                        <input type="text" name="last_name" id="edit_user_last_name" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">اسم المستخدم / الكود:</label>
                        <input type="text" name="officer_id" id="edit_user_code" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">رقم الهاتف:</label>
                        <input type="text" name="phone" id="edit_user_phone" class="shipping-input">
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" id="edit_user_email" class="shipping-input" required>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">تغيير الدور:</label>
                        <select name="role" id="edit_user_role" class="shipping-select">
                            <option value="subscriber">عميل (Subscriber)</option>
                            <?php if ($is_sys_manager): ?>
                                <option value="administrator">مدير النظام (Administrator)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">حالة الحساب:</label>
                        <select name="account_status" id="edit_user_status" class="shipping-select">
                            <option value="active">نشط</option>
                            <option value="restricted">مقيد (لا يمكنه الدخول)</option>
                        </select>
                    </div>
                    <div class="shipping-form-group">
                        <label class="shipping-label">كلمة مرور جديدة (اختياري):</label>
                        <input type="password" name="user_pass" class="shipping-input" placeholder="اتركه فارغاً لعدم التغيير">
                    </div>
                </div>
                <div style="padding: 25px; background: #f8fafc; text-align: left;">
                    <button type="submit" class="shipping-btn" style="width: auto; padding: 10px 40px;">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    window.shippingEditUser = (u) => UsersController.editUser(u);
    </script>
</div>
