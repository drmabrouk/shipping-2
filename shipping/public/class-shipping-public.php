<?php

class Shipping_Public {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function hide_admin_bar_for_non_admins($show) {
        if (!current_user_can('administrator')) {
            return false;
        }
        return $show;
    }

    private function can_manage_user($target_user_id) {
        if (current_user_can('manage_options')) return true;
        return false;
    }

    private function can_access_customer($customer_id) {
        if (current_user_can('manage_options')) return true;

        $customer = Shipping_DB::get_customer_by_id($customer_id);
        if (!$customer) return false;

        $user = wp_get_current_user();

        // Customers can access their own record
        if ($customer->wp_user_id == $user->ID) {
            return true;
        }

        return false;
    }

    public function restrict_admin_access() {
        if (is_user_logged_in()) {
            $status = get_user_meta(get_current_user_id(), 'shipping_account_status', true);
            if ($status === 'restricted') {
                wp_logout();
                wp_redirect(home_url('/shipping-login?login=failed'));
                exit;
            }
        }

        if (is_admin() && !defined('DOING_AJAX') && !current_user_can('manage_options')) {
            wp_redirect(home_url('/shipping-admin'));
            exit;
        }
    }

    public function enqueue_styles() {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";', 'before');
        wp_enqueue_style('dashicons');
        wp_enqueue_style('google-font-rubik', 'https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;700;800;900&display=swap', array(), null);
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.1', true);
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
        wp_enqueue_style($this->plugin_name, SHIPPING_PLUGIN_URL . 'assets/css/shipping-public.css', array('dashicons'), $this->version, 'all');

        // Modular JS Controllers
        wp_enqueue_script('shipping-core', SHIPPING_PLUGIN_URL . 'assets/js/shipping-core.js', array('jquery'), $this->version, true);
        wp_enqueue_script('shipping-orders', SHIPPING_PLUGIN_URL . 'assets/js/orders-controller.js', array('shipping-core'), $this->version, true);
        wp_enqueue_script('shipping-customers', SHIPPING_PLUGIN_URL . 'assets/js/customers-controller.js', array('shipping-core'), $this->version, true);
        wp_enqueue_script('shipping-shipments', SHIPPING_PLUGIN_URL . 'assets/js/shipments-controller.js', array('shipping-core'), $this->version, true);
        wp_enqueue_script('shipping-logistics', SHIPPING_PLUGIN_URL . 'assets/js/logistics-controller.js', array('shipping-core', 'leaflet-js', 'chart-js'), $this->version, true);
        wp_enqueue_script('shipping-billing', SHIPPING_PLUGIN_URL . 'assets/js/billing-controller.js', array('shipping-core', 'chart-js'), $this->version, true);
        wp_enqueue_script('shipping-customs', SHIPPING_PLUGIN_URL . 'assets/js/customs-controller.js', array('shipping-core'), $this->version, true);
        wp_enqueue_script('shipping-users', SHIPPING_PLUGIN_URL . 'assets/js/users-controller.js', array('shipping-core'), $this->version, true);
        wp_enqueue_script('shipping-tickets', SHIPPING_PLUGIN_URL . 'assets/js/tickets-controller.js', array('shipping-core'), $this->version, true);
        wp_enqueue_script('shipping-admin', SHIPPING_PLUGIN_URL . 'assets/js/admin-controller.js', array('shipping-core'), $this->version, true);
        wp_enqueue_script('shipping-public-ctrl', SHIPPING_PLUGIN_URL . 'assets/js/public-controller.js', array('shipping-core'), $this->version, true);

        $info = Shipping_Settings::get_shipping_info();
        wp_localize_script('shipping-core', 'shippingVars', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url('admin.php?page=shipping-admin'),
            'currency' => $info['currency'] ?? 'SAR',
            'nonce' => wp_create_nonce('shipping_admin_action'),
            'shipmentNonce' => wp_create_nonce('shipping_shipment_action'),
            'orderNonce' => wp_create_nonce('shipping_order_action'),
            'logisticNonce' => wp_create_nonce('shipping_logistic_action'),
            'billingNonce' => wp_create_nonce('shipping_billing_action'),
            'customsNonce' => wp_create_nonce('shipping_customs_action'),
            'ticketNonce' => wp_create_nonce('shipping_ticket_action'),
            'customerNonce' => wp_create_nonce('shipping_add_customer'),
            'deleteCustomerNonce' => wp_create_nonce('shipping_delete_customer'),
            'pricingNonce' => wp_create_nonce('shipping_pricing_action'),
            'messageNonce' => wp_create_nonce('shipping_message_action'),
            'staffNonce' => wp_create_nonce('shippingCustomerAction'),
            'profileNonce' => wp_create_nonce('shipping_profile_action'),
            'photoNonce' => wp_create_nonce('shipping_photo_action'),
            'contractNonce' => wp_create_nonce('shipping_contract_action'),
            'publicNonce' => wp_create_nonce('shipping_public_action'),
        ));

        // Legacy global for inline scripts still being migrated
        wp_add_inline_script('shipping-core', 'window.shippingCurrency = "' . ($info['currency'] ?? 'SAR') . '"; window.shippingAdminUrl = "' . admin_url('admin.php?page=shipping-admin') . '";', 'before');

        $appearance = Shipping_Settings::get_appearance();
        $custom_css = "
            :root {
                --shipping-primary-color: {$appearance['primary_color']};
                --shipping-secondary-color: {$appearance['secondary_color']};
                --shipping-accent-color: {$appearance['accent_color']};
                --shipping-dark-color: {$appearance['dark_color']};
                --shipping-radius: {$appearance['border_radius']};
            }
            .shipping-content-wrapper, .shipping-admin-dashboard, .shipping-container,
            .shipping-content-wrapper *:not(.dashicons), .shipping-admin-dashboard *:not(.dashicons), .shipping-container *:not(.dashicons) {
                font-family: 'Rubik', sans-serif !important;
            }
            .shipping-admin-dashboard { font-size: {$appearance['font_size']}; }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function register_shortcodes() {
        // New Shortcodes
        add_shortcode('shipping_login', array($this, 'shortcode_login'));
        add_shortcode('shipping_register', array($this, 'shortcode_register'));
        add_shortcode('shipping_admin', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('shipping_verify', array($this, 'shortcode_verify'));
        add_shortcode('shipping_home', array($this, 'shortcode_home'));
        add_shortcode('shipping_about', array($this, 'shortcode_about'));
        add_shortcode('shipping_contact', array($this, 'shortcode_contact'));
        add_shortcode('shipping_blog', array($this, 'shortcode_blog'));
        add_shortcode('shipping_public_tracking', array($this, 'shortcode_public_tracking'));

        // Backward Compatibility Mapping
        add_shortcode('sm_login', array($this, 'shortcode_login'));
        add_shortcode('sm_admin', array($this, 'shortcode_admin_dashboard'));
        add_shortcode('verify', array($this, 'shortcode_verify'));
        add_shortcode('smhome', array($this, 'shortcode_home'));
        add_shortcode('smabout', array($this, 'shortcode_about'));
        add_shortcode('smcontact', array($this, 'shortcode_contact'));
        add_shortcode('smblog', array($this, 'shortcode_blog'));

        add_filter('authenticate', array($this, 'custom_authenticate'), 20, 3);
        add_filter('auth_cookie_expiration', array($this, 'custom_auth_cookie_expiration'), 10, 3);
    }

    public function custom_auth_cookie_expiration($expiration, $user_id, $remember) {
        if ($remember) {
            return 30 * DAY_IN_SECONDS; // 30 days
        }
        return $expiration;
    }

    public function custom_authenticate($user, $username, $password) {
        if (empty($username) || empty($password)) return $user;

        // If already authenticated by standard means, return
        if ($user instanceof WP_User) return $user;

        // 1. Check for Shipping Admin/Customer ID Code (meta)
        $code_query = new WP_User_Query(array(
            'meta_query' => array(
                array('key' => 'shippingCustomerIdAttr', 'value' => $username)
            ),
            'number' => 1
        ));
        $found = $code_query->get_results();
        if (!empty($found)) {
            $u = $found[0];
            if (wp_check_password($password, $u->user_pass, $u->ID)) return $u;
        }

        // 2. Check for Username in shipping_customers table (if user_login is different)
        global $wpdb;
        $customer_wp_id = $wpdb->get_var($wpdb->prepare("SELECT wp_user_id FROM {$wpdb->prefix}shipping_customers WHERE username = %s", $username));
        if ($customer_wp_id) {
            $u = get_userdata($customer_wp_id);
            if ($u && wp_check_password($password, $u->user_pass, $u->ID)) return $u;
        }

        return $user;
    }

    public function shortcode_verify() {
        ob_start();
        include SHIPPING_PLUGIN_DIR . 'templates/public-verification.php';
        return ob_get_clean();
    }

    public function shortcode_register() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/shipping-admin'));
            exit;
        }
        wp_redirect(add_query_arg('auth', 'register', home_url('/shipping-login')));
        exit;
    }


    public function shortcode_home() {
        $shipping = Shipping_Settings::get_shipping_info();
        $page = Shipping_DB::get_page_by_shortcode('shipping_home');
        ob_start();
        ?>
        <div class="shipping-public-page shipping-home-page" dir="rtl">
            <div class="shipping-hero-section">
                <?php if ($shipping['shipping_logo']): ?>
                    <img src="<?php echo esc_url($shipping['shipping_logo']); ?>" alt="Logo" class="shipping-hero-logo">
                <?php endif; ?>
                <h1><?php echo esc_html($shipping['shipping_name']); ?></h1>
                <p class="shipping-hero-subtitle"><?php echo esc_html($page->instructions ?? 'مرحباً بكم في البوابة الرسمية'); ?></p>
            </div>
            <div class="shipping-content-container">
                <div class="shipping-info-grid">
                    <div class="shipping-info-card">
                        <span class="dashicons dashicons-admin-site"></span>
                        <h4>من نحن</h4>
                        <p>نعمل على تقديم أفضل الخدمات لعملاء Shipping وتطوير المنظومة المهنية.</p>
                    </div>
                    <div class="shipping-info-card">
                        <span class="dashicons dashicons-awards"></span>
                        <h4>أهدافنا</h4>
                        <p>الارتقاء بالمستوى المهني والاجتماعي لكافة العملاء المسجلين.</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_about() {
        $shipping = Shipping_Settings::get_shipping_info();
        $page = Shipping_DB::get_page_by_shortcode('shipping_about');
        ob_start();
        ?>
        <div class="shipping-public-page shipping-about-page" dir="rtl">
            <div class="shipping-page-header">
                <h2><?php echo esc_html($page->title ?? 'عن Shipping'); ?></h2>
            </div>
            <div class="shipping-content-container">
                <div class="shipping-about-content">
                    <h3><?php echo esc_html($shipping['shipping_name']); ?></h3>
                    <div class="shipping-text-block">
                        <?php echo nl2br(esc_html($shipping['extra_details'] ?: 'تفاصيل Shipping الرسمية والرؤية المستقبلية للمهنة.')); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_contact() {
        $shipping = Shipping_Settings::get_shipping_info();
        $page = Shipping_DB::get_page_by_shortcode('shipping_contact');
        ob_start();
        ?>
        <div class="shipping-public-page shipping-contact-page" dir="rtl">
            <div class="shipping-page-header">
                <h2><?php echo esc_html($page->title ?? 'اتصل بنا'); ?></h2>
            </div>
            <div class="shipping-content-container">
                <div class="shipping-contact-grid">
                    <div class="shipping-contact-info">
                        <h3>بيانات التواصل</h3>
                        <p><span class="dashicons dashicons-location"></span> <?php echo esc_html($shipping['address']); ?></p>
                        <p><span class="dashicons dashicons-phone"></span> <?php echo esc_html($shipping['phone']); ?></p>
                        <p><span class="dashicons dashicons-email"></span> <?php echo esc_html($shipping['email']); ?></p>
                    </div>
                    <div class="shipping-contact-form-wrapper">
                        <form class="shipping-public-form">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                                <input type="text" placeholder="الاسم الأول" class="shipping-input">
                                <input type="text" placeholder="اسم العائلة" class="shipping-input">
                            </div>
                            <div class="shipping-form-group"><input type="email" placeholder="البريد الإلكتروني" class="shipping-input"></div>
                            <div class="shipping-form-group"><textarea placeholder="رسالتك" class="shipping-textarea" rows="5"></textarea></div>
                            <button type="button" class="shipping-btn" onclick="alert('شكراً لتواصلك معنا، تم استلام رسالتك.')">إرسال الرسالة</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_public_tracking() {
        ob_start();
        include SHIPPING_PLUGIN_DIR . 'templates/public-shipment-tracking-page.php';
        return ob_get_clean();
    }

    public function shortcode_blog() {
        $articles = Shipping_DB::get_articles(12);
        $page = Shipping_DB::get_page_by_shortcode('shipping_blog');
        ob_start();
        ?>
        <div class="shipping-public-page shipping-blog-page" dir="rtl">
            <div class="shipping-page-header">
                <h2><?php echo esc_html($page->title ?? 'أخبار ومقالات'); ?></h2>
            </div>
            <div class="shipping-content-container">
                <?php if (empty($articles)): ?>
                    <p style="text-align:center; padding:50px; color:#718096;">لا توجد مقالات منشورة حالياً.</p>
                <?php else: ?>
                    <div class="shipping-blog-grid">
                        <?php foreach($articles as $a): ?>
                            <div class="shipping-blog-card">
                                <?php if($a->image_url): ?>
                                    <div class="shipping-blog-image" style="background-image: url('<?php echo esc_url($a->image_url); ?>');"></div>
                                <?php endif; ?>
                                <div class="shipping-blog-content">
                                    <span class="shipping-blog-date"><?php echo date('Y-m-d', strtotime($a->created_at)); ?></span>
                                    <h4><?php echo esc_html($a->title); ?></h4>
                                    <p><?php echo mb_strimwidth(strip_tags($a->content), 0, 120, '...'); ?></p>
                                    <a href="#" class="shipping-read-more">اقرأ المزيد ←</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_login() {
        if (is_user_logged_in()) {
            wp_redirect(home_url('/shipping-admin'));
            exit;
        }
        $shipping = Shipping_Settings::get_shipping_info();

        ob_start();
        ?>
        <div class="shipping-auth-wrapper" dir="rtl">
            <div class="shipping-auth-container">
                <div class="shipping-auth-header">
                    <?php if ($shipping['shipping_logo']): ?>
                        <img src="<?php echo esc_url($shipping['shipping_logo']); ?>" alt="Logo" class="auth-logo">
                    <?php endif; ?>
                    <h2><?php echo esc_html($shipping['shipping_name']); ?></h2>
                    <p>بوابتك الرقمية للخدمات الموحدة</p>
                </div>

                <div class="shipping-auth-tabs">
                    <button class="auth-tab active" onclick="switchAuthTab('login')">تسجيل الدخول</button>
                    <button class="auth-tab" onclick="switchAuthTab('register')">إنشاء حساب</button>
                </div>

                <!-- Login Form -->
                <div id="shipping-login-section" class="auth-section active">
                    <div class="auth-welcome-msg">مرحباً بك مجدداً! يرجى تسجيل الدخول للوصول إلى حسابك.</div>
                    <?php if (isset($_GET['login']) && $_GET['login'] == 'failed'): ?>
                        <div class="auth-alert error">بيانات الدخول غير صحيحة، يرجى المحاولة مرة أخرى.</div>
                    <?php endif; ?>

                    <form name="loginform" id="shipping_login_form" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
                        <div class="auth-input-group">
                            <input type="text" name="log" id="user_login" class="auth-input" placeholder="اسم المستخدم" required>
                            <span class="auth-tooltip">أدخل اسم المستخدم أو البريد الإلكتروني الخاص بك</span>
                        </div>
                        <div class="auth-input-group">
                            <input type="password" name="pwd" id="user_pass" class="auth-input" placeholder="كلمة المرور" required>
                            <span class="auth-tooltip">أدخل كلمة المرور السرية الخاصة بحسابك</span>
                        </div>
                        <div class="auth-options">
                            <label><input name="rememberme" type="checkbox" id="rememberme" value="forever"> تذكرني</label>
                            <a href="javascript:void(0)" onclick="shippingToggleRecovery()">نسيت كلمة المرور؟</a>
                        </div>
                        <button type="submit" name="wp-submit" id="wp-submit" class="auth-btn">
                            <span class="dashicons dashicons-lock"></span> دخول النظام
                        </button>
                        <input type="hidden" name="redirect_to" value="<?php echo home_url('/shipping-admin'); ?>">
                    </form>
                </div>

                <!-- Registration Form (Integrated) -->
                <div id="shipping-register-section" class="auth-section">
                    <div class="auth-welcome-msg">نسعد بانضمامك إلينا! يرجى ملء البيانات التالية لإنشاء حسابك الجديد.</div>

                    <div class="reg-progress-bar">
                        <div class="progress-step active" id="p-step-1"></div>
                        <div class="progress-step" id="p-step-2"></div>
                        <div class="progress-step" id="p-step-3"></div>
                        <div class="progress-step" id="p-step-4"></div>
                    </div>

                    <div id="reg-stages-container">
                        <!-- Registration Stage 1 -->
                        <div class="reg-stage active" id="reg-stage-1">
                            <div class="auth-row">
                                <div class="auth-input-group">
                                    <input type="text" id="reg_first_name" class="auth-input" placeholder="الاسم الأول" required>
                                    <span class="dashicons dashicons-id-alt"></span>
                                    <span class="auth-tooltip">أهلاً بك! يرجى إدخال اسمك الشخصي الأول</span>
                                </div>
                                <div class="auth-input-group">
                                    <input type="text" id="reg_last_name" class="auth-input" placeholder="اسم العائلة" required>
                                    <span class="dashicons dashicons-groups"></span>
                                    <span class="auth-tooltip">يرجى إدخال اسم العائلة أو اللقب الكريم</span>
                                </div>
                            </div>
                            <div class="auth-row">
                                <div class="auth-input-group">
                                    <select id="reg_gender" class="auth-input">
                                        <option value="male">ذكر</option>
                                        <option value="female">أنثى</option>
                                    </select>
                                    <span class="dashicons dashicons-universal-access"></span>
                                    <span class="auth-tooltip">يسعدنا تحديد الجنس لتخصيص تجربتك</span>
                                </div>
                                <div class="auth-input-group">
                                    <input type="number" id="reg_yob" class="auth-input" placeholder="سنة الميلاد" min="1900" max="<?php echo date('Y'); ?>" required>
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <span class="auth-tooltip">يرجى إدخال سنة ميلادك (مثلاً: 1990)</span>
                                </div>
                            </div>
                            <button class="auth-btn" onclick="nextRegStage(1)">متابعة <span class="dashicons dashicons-arrow-left-alt"></span></button>
                        </div>

                        <!-- Registration Stage 2 -->
                        <div class="reg-stage" id="reg-stage-2">
                            <div class="auth-row">
                                <div class="auth-input-group">
                                    <input type="email" id="reg_email" class="auth-input" placeholder="البريد الإلكتروني" oninput="debounceValidation('email')" required>
                                    <span class="dashicons dashicons-email"></span>
                                    <span class="auth-tooltip">أدخل بريدك الإلكتروني لاستلام رمز التحقق الآمن</span>
                                    <div id="email-validation-msg" class="validation-msg"></div>
                                </div>
                                <div class="auth-input-group">
                                    <input type="text" id="reg_username" class="auth-input" placeholder="اسم المستخدم" oninput="debounceValidation('username')" required>
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <span class="auth-tooltip">اختر اسماً فريداً يميزك عند الدخول للنظام</span>
                                    <div id="username-validation-msg" class="validation-msg"></div>
                                </div>
                            </div>
                            <div class="auth-row">
                                <div class="auth-input-group">
                                    <input type="password" id="reg_password" class="auth-input" placeholder="كلمة المرور" required>
                                    <span class="dashicons dashicons-lock"></span>
                                    <span class="auth-tooltip">يرجى اختيار كلمة مرور قوية (8 أحرف على الأقل)</span>
                                </div>
                                <div class="auth-input-group">
                                    <input type="password" id="reg_password_confirm" class="auth-input" placeholder="تأكيد كلمة المرور" required>
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <span class="auth-tooltip">يرجى إعادة كتابة كلمة المرور للتأكيد</span>
                                </div>
                            </div>
                            <div class="auth-nav">
                                <button class="auth-btn-link" onclick="prevRegStage(2)">السابق</button>
                                <button class="auth-btn" id="btn-reg-stage-2" onclick="nextRegStage(2)">إرسال رمز التحقق</button>
                            </div>
                        </div>

                        <!-- Registration Stage 3: OTP -->
                        <div class="reg-stage" id="reg-stage-3">
                            <p class="auth-subtitle" style="text-align: center; color: #64748b; margin-bottom: 20px; font-size: 0.9em;">لقد أرسلنا رمزاً مكوناً من 6 أرقام إلى بريدك الإلكتروني.</p>
                            <div class="auth-input-group">
                                <input type="text" id="reg_otp" class="auth-input otp-input" placeholder="000000" maxlength="6">
                                <span class="dashicons dashicons-shield"></span>
                                <span class="auth-tooltip">أدخل الرمز المكون من 6 أرقام المرسل لبريدك</span>
                            </div>
                            <button class="auth-btn" onclick="verifyRegOTP()"><span class="dashicons dashicons-yes-alt"></span> تحقق وإكمال</button>
                            <p style="text-align: center; margin-top: 15px; font-size: 0.85em; color: #64748b;">لم يصلك الرمز؟ <a href="javascript:void(0)" onclick="sendRegOTP()" style="color: var(--shipping-primary-color); font-weight: 700; text-decoration: none;">إعادة إرسال</a></p>
                        </div>

                        <!-- Registration Stage 4: Success & Photo -->
                        <div class="reg-stage" id="reg-stage-4">
                            <h3 style="text-align: center; margin: 0 0 10px 0; font-weight: 800; color: #10b981;">تم التحقق بنجاح!</h3>
                            <p style="text-align: center; color: #64748b; margin-bottom: 20px; font-size: 0.9em;">يمكنك الآن إضافة صورة شخصية لتمييز ملفك (اختياري)</p>
                            <div class="auth-photo-upload" onclick="document.getElementById('reg_photo').click()">
                                <div id="reg-photo-preview">📸</div>
                                <input type="file" id="reg_photo" style="display:none" accept="image/*" onchange="previewRegPhoto(this)">
                            </div>
                            <button class="auth-btn" onclick="completeReg()">إتمام التسجيل والدخول <span class="dashicons dashicons-unlock"></span></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recovery Modal -->
            <div id="shipping-recovery-modal" class="auth-modal">
                <div class="auth-modal-content" dir="rtl">
                    <button class="modal-close" onclick="shippingToggleRecovery()">&times;</button>
                    <h3 style="margin-top:0; margin-bottom:25px; text-align:center; font-weight:800;">استعادة كلمة المرور</h3>
                    <div id="recovery-step-1">
                        <p style="font-size:14px; color:#64748b; margin-bottom:20px; line-height:1.6;">أدخل اسم المستخدم الخاص بك للتحقق وإرسال رمز الاستعادة.</p>
                        <div class="auth-input-group">
                            <input type="text" id="rec_username" class="auth-input" placeholder="اسم المستخدم">
                        </div>
                        <button onclick="shippingRequestOTP()" class="auth-btn">إرسال رمز التحقق</button>
                    </div>
                    <div id="recovery-step-2" style="display:none;">
                        <p style="font-size:13px; color:#38a169; margin-bottom:15px;">تم إرسال الرمز بنجاح. يرجى التحقق من بريدك.</p>
                        <div class="auth-input-group">
                            <input type="text" id="rec_otp" class="auth-input" placeholder="الرمز (6 أرقام)">
                        </div>
                        <div class="auth-input-group">
                            <input type="password" id="rec_new_pass" class="auth-input" placeholder="كلمة المرور الجديدة">
                        </div>
                        <button onclick="shippingResetPassword()" class="auth-btn">تغيير كلمة المرور</button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .shipping-auth-wrapper {
                display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 20px;
                background: #f8fafc; font-family: 'Rubik', sans-serif;
            }
            .shipping-auth-container {
                width: 100%; max-width: 500px; background: #fff; border-radius: 24px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.05); overflow: hidden; border: 1px solid #f1f5f9;
            }
            .shipping-auth-header {
                padding: 40px 30px 20px; text-align: center; background: var(--shipping-dark-color); color: #fff;
            }
            .auth-logo { max-height: 60px; margin-bottom: 15px; }
            .shipping-auth-header h2 { margin: 0; font-size: 1.6em; font-weight: 900; }
            .shipping-auth-header p { margin: 5px 0 0; opacity: 0.8; font-size: 0.9em; }

            .auth-welcome-msg { text-align: center; color: #64748b; margin-bottom: 25px; font-size: 0.95em; line-height: 1.5; }

            .shipping-auth-tabs { display: flex; border-bottom: 1px solid #f1f5f9; }
            .auth-tab {
                flex: 1; padding: 15px; border: none; background: #fdfdfd; cursor: pointer;
                font-weight: 700; color: #64748b; transition: 0.3s; font-family: 'Rubik', sans-serif;
            }
            .auth-tab.active { background: #fff; color: var(--shipping-primary-color); border-bottom: 3px solid var(--shipping-primary-color); }

            .auth-section { display: none; padding: 30px; animation: authFadeIn 0.4s ease; }
            .auth-section.active { display: block; }

            .auth-alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85em; text-align: center; font-weight: 600; }
            .auth-alert.error { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }

            .auth-row { display: flex; gap: 15px; margin-bottom: 15px; }
            .auth-input-group { position: relative; flex: 1; }
            .auth-input {
                width: 100%; padding: 14px 18px 14px 45px; border: 2px solid #f1f5f9; border-radius: 12px;
                font-size: 0.95em; outline: none; transition: 0.3s; font-family: 'Rubik', sans-serif;
                background: #fcfcfc;
            }
            .auth-input-group .dashicons {
                position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
                color: #94a3b8; font-size: 18px; transition: 0.3s; pointer-events: none;
            }
            .auth-input:focus + .dashicons + .auth-tooltip, .auth-input:focus + .dashicons { color: var(--shipping-primary-color); }
            .auth-input:focus { border-color: var(--shipping-primary-color); background: #fff; }

            .auth-tooltip {
                position: absolute; bottom: 100%; right: 0; background: #334155; color: #fff;
                padding: 5px 10px; border-radius: 6px; font-size: 0.75em; visibility: hidden;
                opacity: 0; transition: 0.3s; transform: translateY(5px); pointer-events: none; z-index: 10;
                white-space: nowrap;
            }
            .auth-input-group:hover .auth-tooltip, .auth-input-group:focus-within .auth-tooltip { visibility: visible; opacity: 1; transform: translateY(-5px); }

            .auth-options { display: flex; justify-content: space-between; align-items: center; margin: -5px 0 20px; font-size: 0.85em; }
            .auth-options label { color: #64748b; display: flex; align-items: center; gap: 6px; }
            .auth-options a { color: var(--shipping-primary-color); text-decoration: none; font-weight: 600; }

            .auth-btn {
                width: 100%; padding: 15px; background: var(--shipping-primary-color); color: #fff;
                border: none; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s;
                font-size: 1.05em; font-family: 'Rubik', sans-serif;
                display: flex; align-items: center; justify-content: center; gap: 10px;
            }
            .auth-btn:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

            .reg-stage { display: none; animation: authSlideIn 0.3s ease; }
            .reg-stage.active { display: block; }

            .auth-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
            .auth-btn-link { background: none; border: none; color: #64748b; font-weight: 600; cursor: pointer; text-decoration: underline; font-family: 'Rubik', sans-serif; }

            .otp-input { text-align: center; letter-spacing: 10px; font-size: 1.5em; font-weight: 900; }
            .auth-photo-upload {
                width: 100px; height: 100px; background: #f8fafc; border: 2px dashed #cbd5e0;
                border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;
                font-size: 2em; cursor: pointer; overflow: hidden;
            }
            #reg-photo-preview img { width: 100%; height: 100%; object-fit: cover; }

            .auth-modal {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);
                display: none; align-items: center; justify-content: center; z-index: 10000;
                backdrop-filter: blur(4px);
            }
            .auth-modal-content { background: #fff; padding: 40px; border-radius: 24px; width: 90%; max-width: 420px; position: relative; }
            .modal-close { position: absolute; top: 20px; left: 20px; border: none; background: none; font-size: 24px; cursor: pointer; color: #94a3b8; }

            .validation-msg { font-size: 0.8em; margin-top: 4px; }
            .validation-msg.error { color: #ef4444; }
            .validation-msg.success { color: #10b981; }

            @keyframes authFadeIn { from { opacity: 0; } to { opacity: 1; } }
            @keyframes authSlideIn { from { transform: translateX(20px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

            .reg-progress-bar { display: flex; gap: 8px; margin-bottom: 25px; }
            .progress-step { flex: 1; height: 6px; background: #f1f5f9; border-radius: 10px; transition: 0.4s; }
            .progress-step.active { background: var(--shipping-primary-color); }
            .progress-step.complete { background: #10b981; }

            @media (max-width: 480px) {
                .auth-row { flex-direction: column; gap: 10px; }
                .auth-tooltip { display: none; } /* Hide tooltips on mobile for better UX */
            }
        </style>

        <script>
        const regData = {};
        function switchAuthTab(tab) {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.auth-section').forEach(s => s.classList.remove('active'));
            if (tab === 'login') {
                document.querySelector('.auth-tab:first-child').classList.add('active');
                document.getElementById('shipping-login-section').classList.add('active');
            } else {
                document.querySelector('.auth-tab:last-child').classList.add('active');
                document.getElementById('shipping-register-section').classList.add('active');
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('auth') === 'register') {
                switchAuthTab('register');
            }
        });

        function nextRegStage(stage) {
            if (stage === 1) {
                regData.first_name = document.getElementById('reg_first_name').value;
                regData.last_name = document.getElementById('reg_last_name').value;
                regData.gender = document.getElementById('reg_gender').value;
                regData.year_of_birth = document.getElementById('reg_yob').value;
                if (!regData.first_name || !regData.last_name || !regData.year_of_birth) return alert('يرجى إكمال جميع الحقول');
            } else if (stage === 2) {
                regData.email = document.getElementById('reg_email').value;
                regData.username = document.getElementById('reg_username').value;
                regData.password = document.getElementById('reg_password').value;
                const confirm = document.getElementById('reg_password_confirm').value;
                if (!regData.email || !regData.username || !regData.password) return alert('يرجى إكمال جميع الحقول');
                if (regData.password !== confirm) return alert('كلمات المرور غير متطابقة');
                if (regData.password.length < 8) return alert('كلمة المرور قصيرة جداً');

                sendRegOTP();
                return;
            }
            goToRegStage(stage + 1);
        }

        function prevRegStage(stage) { goToRegStage(stage - 1); }
        function goToRegStage(stage) {
            document.querySelectorAll('.reg-stage').forEach(s => s.classList.remove('active'));
            document.getElementById('reg-stage-' + stage).classList.add('active');

            // Update progress bar
            document.querySelectorAll('.progress-step').forEach((el, idx) => {
                el.classList.remove('active', 'complete');
                if (idx + 1 < stage) el.classList.add('complete');
                else if (idx + 1 === stage) el.classList.add('active');
            });
        }

        let valTimeout;
        function debounceValidation(type) {
            clearTimeout(valTimeout);
            valTimeout = setTimeout(() => {
                const val = document.getElementById('reg_' + type).value;
                if (!val) return;
                const fd = new FormData();
                fd.append('action', 'shipping_check_username_email');
                if (type === 'username') fd.append('username', val); else fd.append('email', val);
                fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
                    const msgEl = document.getElementById(type + '-validation-msg');
                    if (res.success) {
                        msgEl.innerText = type === 'username' ? 'متاح' : 'بريد متاح';
                        msgEl.className = 'validation-msg success';
                    } else {
                        msgEl.innerText = res.data.message;
                        msgEl.className = 'validation-msg error';
                    }
                });
            }, 500);
        }

        function sendRegOTP() {
            const btn = document.getElementById('btn-reg-stage-2');
            btn.disabled = true; btn.innerText = 'جاري الإرسال...';
            const fd = new FormData(); fd.append('action', 'shipping_register_send_otp'); fd.append('email', regData.email);
            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
                btn.disabled = false; btn.innerText = 'إرسال رمز التحقق';
                if (res.success) goToRegStage(3); else alert(res.data);
            });
        }

        function verifyRegOTP() {
            const otp = document.getElementById('reg_otp').value;
            const fd = new FormData(); fd.append('action', 'shipping_register_verify_otp');
            fd.append('email', regData.email); fd.append('otp', otp);
            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
                if (res.success) goToRegStage(4); else alert(res.data);
            });
        }

        function previewRegPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => document.getElementById('reg-photo-preview').innerHTML = `<img src="${e.target.result}">`;
                reader.readAsDataURL(input.files[0]);
            }
        }

        function completeReg() {
            const btn = document.querySelector('#reg-stage-4 .auth-btn');
            btn.disabled = true; btn.innerText = 'جاري المعالجة...';
            const fd = new FormData();
            for (const k in regData) fd.append(k, regData[k]);
            fd.append('action', 'shipping_register_complete');
            const photo = document.getElementById('reg_photo').files[0];
            if (photo) fd.append('profile_image', photo);
            fetch(ajaxurl, {method:'POST', body:fd}).then(r=>r.json()).then(res=>{
                if (res.success) window.location.href = res.data.redirect_url;
                else { btn.disabled = false; btn.innerText = 'إتمام التسجيل والدخول'; alert(res.data); }
            });
        }

        function shippingToggleRecovery() {
            const m = document.getElementById("shipping-recovery-modal");
            m.style.display = m.style.display === "flex" ? "none" : "flex";
        }
        function shippingRequestOTP() {
            const username = document.getElementById("rec_username").value;
            const fd = new FormData(); fd.append("action", "shipping_forgot_password_otp"); fd.append("username", username);
            fetch(ajaxurl, {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) { document.getElementById("recovery-step-1").style.display="none"; document.getElementById("recovery-step-2").style.display="block"; } else alert(res.data);
            });
        }
        function shippingResetPassword() {
            const username = document.getElementById("rec_username").value;
            const otp = document.getElementById("rec_otp").value;
            const pass = document.getElementById("rec_new_pass").value;
            const fd = new FormData(); fd.append("action", "shipping_reset_password_otp");
            fd.append("username", username); fd.append("otp", otp); fd.append("new_password", pass);
            fetch(ajaxurl, {method:"POST", body:fd}).then(r=>r.json()).then(res=>{
                if(res.success) { alert(res.data); location.reload(); } else alert(res.data);
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    public function shortcode_admin_dashboard() {
        if (!is_user_logged_in()) {
            return $this->shortcode_login();
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $active_tab = isset($_GET['shipping_tab']) ? sanitize_text_field($_GET['shipping_tab']) : 'summary';

        $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
        $is_sys_admin = in_array('administrator', $roles);
        $is_administrator = in_array('administrator', $roles);
        $is_subscriber = in_array('subscriber', $roles);

        // Fetch data
        $stats = Shipping_DB::get_statistics();

        ob_start();
        include SHIPPING_PLUGIN_DIR . 'templates/public-admin-panel.php';
        return ob_get_clean();
    }

    public function login_failed($username) {
        $referrer = wp_get_referer();
        if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
            wp_redirect(add_query_arg('login', 'failed', $referrer));
            exit;
        }
    }

    public function log_successful_login($user_login, $user) {
        Shipping_Logger::log('تسجيل دخول', "المستخدم: $user_login");
    }

    public function ajax_get_customer() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $username = sanitize_text_field($_POST['username'] ?? '');
        $customer = Shipping_DB::get_customer_by_username($username);
        if ($customer) {
            if (!$this->can_access_customer($customer->id)) wp_send_json_error('Access denied');
            wp_send_json_success($customer);
        } else {
            wp_send_json_error('Customer not found');
        }
    }

    public function ajax_get_customer_comprehensive() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');

        $id = intval($_GET['id']);
        if (!$this->can_access_customer($id)) wp_send_json_error('Access denied');

        $data = Shipping_DB::get_customer_comprehensive($id);
        if ($data) wp_send_json_success($data);
        else wp_send_json_error('Customer data not found');
    }

    public function ajax_search_customers() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $query = sanitize_text_field($_POST['query']);
        $customers = Shipping_DB::get_customers(array('search' => $query));
        wp_send_json_success($customers);
    }

    public function ajax_refresh_dashboard() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        wp_send_json_success(array('stats' => Shipping_DB::get_statistics()));
    }

    public function ajax_update_customer_photo() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_photo_action', 'shipping_photo_nonce');

        $customer_id = intval($_POST['customer_id']);
        if (!$this->can_access_customer($customer_id)) wp_send_json_error('Access denied');

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('customer_photo', 0);
        if (is_wp_error($attachment_id)) wp_send_json_error($attachment_id->get_error_message());

        $photo_url = wp_get_attachment_url($attachment_id);
        $customer_id = intval($_POST['customer_id']);
        Shipping_DB::update_customer_photo($customer_id, $photo_url);
        wp_send_json_success(array('photo_url' => $photo_url));
    }

    public function ajax_add_staff() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['shipping_nonce'], 'shippingCustomerAction')) wp_send_json_error('Security check failed');

        $role = sanitize_text_field($_POST['role']);

        // Strict separation: System staff management requires manage_options
        // and non-administrators cannot create other administrators.
        if ($role !== 'subscriber' && !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized role assignment');
        }

        $res = Shipping_DB::save_user($_POST);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());

        Shipping_Logger::log('إضافة مستخدم (بنية موحدة)', "الاسم: {$_POST['first_name']} {$_POST['last_name']} الدور: $role");
        wp_send_json_success($res);
    }

    public function ajax_delete_staff() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'shippingCustomerAction')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['user_id']);
        if ($user_id === get_current_user_id()) wp_send_json_error('Cannot delete yourself');
        if (!$this->can_manage_user($user_id)) wp_send_json_error('Access denied');

        // Check if it's a customer
        global $wpdb;
        $customer_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user_id));
        if ($customer_id) {
            Shipping_DB::delete_customer($customer_id);
        } else {
            wp_delete_user($user_id);
        }

        wp_send_json_success('Deleted');
    }

    public function ajax_update_staff() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['shipping_nonce'], 'shippingCustomerAction')) wp_send_json_error('Security check failed');

        $user_id = intval($_POST['edit_officer_id']);
        if (!$this->can_manage_user($user_id)) wp_send_json_error('Access denied');

        $role = sanitize_text_field($_POST['role']);
        if ($role !== 'subscriber' && !current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized role update');
        }

        $data = $_POST;
        $data['id'] = $user_id;

        $res = Shipping_DB::save_user($data);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());

        Shipping_Logger::log('تحديث مستخدم (بنية موحدة)', "الاسم: {$_POST['first_name']} {$_POST['last_name']}");
        wp_send_json_success('Updated');
    }

    public function ajax_add_customer() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_add_customer', 'shipping_nonce');
        $res = Shipping_DB::add_customer($_POST);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());
        else wp_send_json_success($res);
    }

    public function ajax_update_customer() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_add_customer', 'shipping_nonce');

        $customer_id = intval($_POST['customer_id']);
        if (!$this->can_access_customer($customer_id)) wp_send_json_error('Access denied');

        Shipping_DB::update_customer($customer_id, $_POST);
        wp_send_json_success('Updated');
    }

    public function ajax_delete_customer() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_delete_customer', 'nonce');

        $customer_id = intval($_POST['customer_id']);
        if (!$this->can_access_customer($customer_id)) wp_send_json_error('Access denied');

        Shipping_DB::delete_customer($customer_id);
        wp_send_json_success('Deleted');
    }

    public function ajax_reset_system() {
        if (!current_user_can('manage_options') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');

        $password = $_POST['admin_password'] ?? '';
        $current_user = wp_get_current_user();
        if (!wp_check_password($password, $current_user->user_pass, $current_user->ID)) {
            wp_send_json_error('كلمة المرور غير صحيحة. يرجى إدخال كلمة مرور مدير النظام للمتابعة.');
        }

        global $wpdb;
        $tables = [
            'shipping_customers', 'shipping_logs', 'shipping_messages'
        ];

        // 1. Delete WordPress Users associated with customers
        $customer_wp_ids = $wpdb->get_col("SELECT wp_user_id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id IS NOT NULL");
        if (!empty($customer_wp_ids)) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');
            foreach ($customer_wp_ids as $uid) {
                wp_delete_user($uid);
            }
        }

        // 2. Truncate Tables
        foreach ($tables as $t) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}$t");
        }

        Shipping_Logger::log('إعادة تهيئة النظام', "تم مسح كافة البيانات وتصفير النظام بالكامل");
        wp_send_json_success();
    }

    public function ajax_rollback_log() {
        if (!current_user_can('manage_options') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');

        $log_id = intval($_POST['log_id']);
        global $wpdb;
        $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_logs WHERE id = %d", $log_id));

        if (!$log || strpos($log->details, 'ROLLBACK_DATA:') !== 0) {
            wp_send_json_error('لا توجد بيانات استعادة لهذه العملية');
        }

        $json = str_replace('ROLLBACK_DATA:', '', $log->details);
        $rollback_info = json_decode($json, true);

        if (!$rollback_info || !isset($rollback_info['table'])) {
            wp_send_json_error('تنسيق بيانات الاستعادة غير صحيح');
        }

        $table = $rollback_info['table'];
        $data = $rollback_info['data'];

        if ($table === 'customers') {
            // Migration for old structure in logs
            if (isset($data['national_id']) && !isset($data['username'])) {
                $data['username'] = $data['national_id'];
                unset($data['national_id']);
            }

            if (isset($data['name']) && !isset($data['first_name'])) {
                $parts = explode(' ', $data['name']);
                $data['first_name'] = $parts[0];
                $data['last_name'] = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : '.';
            }
            $full_name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

            // Re-insert into shipping_customers
            $wp_user_id = $data['wp_user_id'] ?? null;

            // Check if user login already exists
            if (!empty($data['username']) && username_exists($data['username'])) {
                wp_send_json_error('لا يمكن الاستعادة: اسم المستخدم موجود بالفعل');
            }

            // Re-create WP User if it was deleted
            if ($wp_user_id && !get_userdata($wp_user_id)) {
                $digits = ''; for ($i = 0; $i < 10; $i++) $digits .= mt_rand(0, 9);
                $temp_pass = 'SHIPPING' . $digits;
                $wp_user_id = wp_insert_user([
                    'user_login' => $data['username'],
                    'user_email' => $data['email'] ?: $data['username'] . '@shipping.com',
                    'display_name' => $full_name,
                    'user_pass' => $temp_pass,
                    'role' => 'subscriber'
                ]);
                if (is_wp_error($wp_user_id)) wp_send_json_error($wp_user_id->get_error_message());
                update_user_meta($wp_user_id, 'shipping_temp_pass', $temp_pass);
                update_user_meta($wp_user_id, 'first_name', $data['first_name']);
                update_user_meta($wp_user_id, 'last_name', $data['last_name']);
            }

            unset($data['id']);
            $data['wp_user_id'] = $wp_user_id;
            if (isset($data['name'])) unset($data['name']);

            $res = $wpdb->insert("{$wpdb->prefix}shipping_customers", $data);
            if ($res) {
                Shipping_Logger::log('استعادة بيانات', "تم استعادة العميل: " . $full_name);
                wp_send_json_success();
            } else {
                wp_send_json_error('فشل في إدراج البيانات في قاعدة البيانات: ' . $wpdb->last_error);
            }
        }

        wp_send_json_error('نوع الاستعادة غير مدعوم حالياً');
    }


    public function ajax_update_profile() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_profile_action', 'nonce');

        $user_id = get_current_user_id();
        $is_customer = in_array('subscriber', (array)wp_get_current_user()->roles);

        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['user_email']);
        $pass = $_POST['user_pass'];

        $user_data = ['ID' => $user_id];

        if (!$is_customer) {
            $user_data['display_name'] = trim($first_name . ' ' . $last_name);
            $user_data['user_email'] = $email;
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
        }

        if (!empty($pass)) {
            $user_data['user_pass'] = $pass;
        }

        $res = wp_update_user($user_data);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());

        Shipping_Logger::log('تحديث الملف الشخصي', "قام المستخدم بتحديث بياناته الشخصية");
        wp_send_json_success();
    }

    public function ajax_delete_log() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}shipping_logs", ['id' => intval($_POST['log_id'])]);
        wp_send_json_success();
    }

    public function ajax_clear_all_logs() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}shipping_logs");
        wp_send_json_success();
    }

    public function ajax_get_alerts() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $alerts = Shipping_DB::get_active_alerts_for_user(get_current_user_id());
        wp_send_json_success($alerts);
    }

    public function ajax_acknowledge_alert() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $id = intval($_POST['id']);
        Shipping_DB::acknowledge_alert($id, get_current_user_id());
        wp_send_json_success();
    }

    public function ajax_export_csv() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_ajax_referer('shipping_export_nonce', 'nonce');

        $type = $_GET['type'] ?? '';
        $filename = 'export_' . $type . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        // Add BOM for Excel compatibility with UTF-8
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        global $wpdb;

        switch ($type) {
            case 'shipments':
                fputcsv($output, ['رقم الشحنة', 'العميل', 'المنشأ', 'الوجهة', 'الوزن', 'الحالة', 'التاريخ']);
                $data = $wpdb->get_results("SELECT s.shipment_number, CONCAT(c.first_name, ' ', c.last_name) as customer, s.origin, s.destination, s.weight, s.status, s.created_at FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id");
                foreach ($data as $row) fputcsv($output, (array)$row);
                break;

            case 'customers':
                fputcsv($output, ['اسم المستخدم', 'الاسم الأول', 'اسم العائلة', 'البريد', 'الهاتف', 'الحالة', 'التصنيف']);
                $data = $wpdb->get_results("SELECT username, first_name, last_name, email, phone, account_status, classification FROM {$wpdb->prefix}shipping_customers");
                foreach ($data as $row) fputcsv($output, (array)$row);
                break;

            case 'invoices':
                fputcsv($output, ['رقم الفاتورة', 'العميل', 'الإجمالي', 'الحالة', 'تاريخ الاستحقاق']);
                $data = $wpdb->get_results("SELECT i.invoice_number, CONCAT(c.first_name, ' ', c.last_name) as customer, i.total_amount, i.status, i.due_date FROM {$wpdb->prefix}shipping_invoices i LEFT JOIN {$wpdb->prefix}shipping_customers c ON i.customer_id = c.id");
                foreach ($data as $row) fputcsv($output, (array)$row);
                break;
        }

        fclose($output);
        exit;
    }

    public function ajax_get_user_role() {
        if (!current_user_can('manage_options') && !current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $user_id = intval($_GET['user_id']);
        $user = get_userdata($user_id);
        if ($user) {
            $role = !empty($user->roles) ? $user->roles[0] : '';
            wp_send_json_success(['role' => $role]);
        }
        wp_send_json_error('User not found');
    }

    public function ajax_update_customer_account() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'shipping_nonce');

        $customer_id = intval($_POST['customer_id']);
        $customer = Shipping_DB::get_customer_by_id($customer_id);
        if (!$customer) wp_send_json_error('Customer not found');

        $wp_user_id = $customer->wp_user_id;
        $email = sanitize_email($_POST['account_email'] ?? $_POST['email']);
        $password = $_POST['new_password'] ?? $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        if (!$this->can_access_customer($customer_id)) wp_send_json_error('Access denied');

        // Update email in WP User and SM Customers table
        $user_data = ['ID' => $wp_user_id, 'user_email' => $email];
        if (!empty($password)) {
            $user_data['user_pass'] = $password;
        }

        $res = wp_update_user($user_data);
        if (is_wp_error($res)) wp_send_json_error($res->get_error_message());

        // Handle role change (only for full admins)
        if (!empty($role) && (current_user_can('manage_options'))) {
            $user = new WP_User($wp_user_id);
            $user->set_role($role);
        }

        // Sync email to customers table
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}shipping_customers", ['email' => $email], ['id' => $customer_id]);

        Shipping_Logger::log('تحديث حساب عميل', "تم تحديث بيانات الحساب للعميل ID: $customer_id");
        wp_send_json_success();
    }


    public function ajax_verify_document() {
        $val = sanitize_text_field($_POST['search_value'] ?? '');
        $type = sanitize_text_field($_POST['search_type'] ?? 'all');

        if (empty($val)) wp_send_json_error('يرجى إدخال قيمة للبحث');

        $customer = null;
        $results = [];

        switch ($type) {
            case 'customership':
                $customer = Shipping_DB::get_customer_by_id_number($val);
                if ($customer) {
                    $results['customership'] = [
                        'label' => 'بيانات الحساب',
                        'name' => $customer->name,
                        'number' => $customer->id_number,
                        'status' => $customer->account_status,
                        'expiry' => $customer->account_expiration_date
                    ];
                }
                break;
            default: // 'all' - Username
                $customer = Shipping_DB::get_customer_by_username($val);
                if (!$customer) {
                    $customer = Shipping_DB::get_customer_by_username($val);
                }

                if ($customer) {
                    $results['customership'] = [
                        'label' => 'بيانات الحساب',
                        'name' => $customer->name,
                        'number' => $customer->id_number,
                        'status' => $customer->account_status,
                        'expiry' => $customer->account_expiration_date
                    ];
                }
                break;
        }

        if (empty($results)) {
            wp_send_json_error('عذراً، لم يتم العثور على أي بيانات مطابقة لمدخلات البحث.');
        }

        wp_send_json_success($results);
    }


    public function handle_form_submission() {
        if (isset($_POST['shipping_import_customers_csv'])) {
            $this->handle_customer_csv_import();
        }
        if (isset($_POST['shipping_import_staffs_csv'])) {
            $this->handle_staff_csv_import();
        }
        if (isset($_POST['shipping_save_appearance'])) {
            check_admin_referer('shipping_admin_action', 'shipping_admin_nonce');
            $data = Shipping_Settings::get_appearance();
            foreach ($data as $k => $v) {
                if (isset($_POST[$k])) $data[$k] = sanitize_text_field($_POST[$k]);
            }
            Shipping_Settings::save_appearance($data);
            wp_redirect(add_query_arg('shipping_tab', 'advanced-settings', wp_get_referer()));
            exit;
        }
        if (isset($_POST['shipping_save_labels'])) {
            check_admin_referer('shipping_admin_action', 'shipping_admin_nonce');
            $labels = Shipping_Settings::get_labels();
            foreach ($labels as $k => $v) {
                if (isset($_POST[$k])) $labels[$k] = sanitize_text_field($_POST[$k]);
            }
            Shipping_Settings::save_labels($labels);
            wp_redirect(add_query_arg('shipping_tab', 'advanced-settings', wp_get_referer()));
            exit;
        }

        if (isset($_POST['shipping_save_settings_unified'])) {
            check_admin_referer('shipping_admin_action', 'shipping_admin_nonce');

            // 1. Save Shipping Info
            $info = Shipping_Settings::get_shipping_info();
            $info['shipping_name'] = sanitize_text_field($_POST['shipping_name']);
            $info['shipping_officer_name'] = sanitize_text_field($_POST['shipping_officer_name']);
            $info['phone'] = sanitize_text_field($_POST['shipping_phone']);
            $info['email'] = sanitize_email($_POST['shipping_email']);
            $info['shipping_logo'] = esc_url_raw($_POST['shipping_logo']);
            $info['address'] = sanitize_text_field($_POST['shipping_address']);
            $info['map_link'] = esc_url_raw($_POST['shipping_map_link'] ?? '');
            $info['extra_details'] = sanitize_textarea_field($_POST['shipping_extra_details'] ?? '');

            Shipping_Settings::save_shipping_info($info);

            // 2. Save Section Labels
            $labels = Shipping_Settings::get_labels();
            foreach($labels as $key => $val) {
                if (isset($_POST[$key])) {
                    $labels[$key] = sanitize_text_field($_POST[$key]);
                }
            }
            Shipping_Settings::save_labels($labels);

            wp_redirect(add_query_arg(['shipping_tab' => 'advanced-settings', 'sub' => 'init', 'settings_saved' => 1], wp_get_referer()));
            exit;
        }

    }

    private function handle_customer_csv_import() {
        if (!current_user_can('manage_options')) return;
        check_admin_referer('shipping_admin_action', 'shipping_admin_nonce');

        if (empty($_FILES['customer_csv_file']['tmp_name'])) return;

        $handle = fopen($_FILES['customer_csv_file']['tmp_name'], 'r');
        if (!$handle) return;

        $results = ['total' => 0, 'success' => 0, 'warning' => 0, 'error' => 0];

        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            $results['total']++;
            if (count($data) < 3) { $results['error']++; continue; }

            $customer_data = [
                'username' => sanitize_text_field($data[0]),
                'first_name' => sanitize_text_field($data[1]),
                'last_name' => sanitize_text_field($data[2]),
                'phone' => sanitize_text_field($data[3] ?? ''),
                'email' => sanitize_email($data[4] ?? '')
            ];

            $res = Shipping_DB::add_customer($customer_data);
            if (is_wp_error($res)) {
                $results['error']++;
            } else {
                $results['success']++;
            }
        }
        fclose($handle);

        set_transient('shipping_import_results_' . get_current_user_id(), $results, 3600);
        wp_redirect(add_query_arg('shipping_tab', 'users-management', wp_get_referer()));
        exit;
    }

    private function handle_staff_csv_import() {
        if (!current_user_can('manage_options')) return;
        check_admin_referer('shipping_admin_action', 'shipping_admin_nonce');

        if (empty($_FILES['csv_file']['tmp_name'])) return;

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) return;

        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) < 5) continue;

            $username = sanitize_user($data[0]);
            $email = sanitize_email($data[1]);
            $first_name = sanitize_text_field($data[2]);
            $last_name = sanitize_text_field($data[3]);
            $officer_id = sanitize_text_field($data[4]);
            $role_label = sanitize_text_field($data[5] ?? 'عميل Shipping');
            $phone = sanitize_text_field($data[6] ?? '');

            $pass = !empty($data[7]) ? $data[7] : 'SHP' . sprintf("%010d", mt_rand(0, 9999999999));

            $role = 'subscriber';
            if (strpos($role_label, 'مدير') !== false) $role = 'administrator';
            elseif (strpos($role_label, 'مسؤول') !== false) $role = 'administrator';

            $user_id = wp_insert_user([
                'user_login' => $username,
                'user_email' => $email ?: $username . '@shipping.com',
                'display_name' => trim($first_name . ' ' . $last_name),
                'user_pass' => $pass,
                'role' => $role
            ]);

            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'shipping_temp_pass', $pass);
                update_user_meta($user_id, 'first_name', $first_name);
                update_user_meta($user_id, 'last_name', $last_name);
                update_user_meta($user_id, 'shippingCustomerIdAttr', $officer_id);
                update_user_meta($user_id, 'shipping_phone', $phone);
                // If it's a subscriber/customer, ensure it's in customers table too
                if ($role === 'subscriber') {
                    Shipping_DB::add_customer([
                        'username' => $officer_id ?: $username,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email ?: $username . '@shipping.com',
                        'phone' => $phone,
                        'wp_user_id' => $user_id
                    ]);
                }
            }
        }
        fclose($handle);

        wp_redirect(add_query_arg('shipping_tab', 'users-management', wp_get_referer()));
        exit;
    }


    public function ajax_bulk_delete_users() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        if (!wp_verify_nonce($_POST['nonce'], 'shippingCustomerAction')) wp_send_json_error('Security check failed');

        $ids = explode(',', $_POST['user_ids']);
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id === get_current_user_id()) continue;
            if (!$this->can_manage_user($id)) continue;
            wp_delete_user($id);
        }
        wp_send_json_success();
    }

    public function ajax_send_message() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_message_action', 'nonce');

        $sender_id = get_current_user_id();
        $customer_id = intval($_POST['customer_id'] ?? 0);

        if (!$customer_id) {
            // Try to find customer_id from current user if they are a customer
            global $wpdb;
            $customer_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $sender_id));
            if ($customer_by_wp) $customer_id = $customer_by_wp->id;
        }

        if (!$this->can_access_customer($customer_id)) wp_send_json_error('Access denied');

        $customer = Shipping_DB::get_customer_by_id($customer_id);
        if (!$customer) wp_send_json_error('Invalid customer context');

        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $receiver_id = intval($_POST['receiver_id'] ?? 0);

        $file_url = null;
        if (!empty($_FILES['message_file']['name'])) {
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['message_file']['type'], $allowed_types)) {
                wp_send_json_error('نوع الملف غير مسموح به. يسمح فقط بملفات PDF والصور.');
            }

            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('message_file', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            }
        }

        Shipping_DB::send_message($sender_id, $receiver_id, $message, $customer_id, $file_url);
        wp_send_json_success();
    }

    public function ajax_get_conversation() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_message_action', 'nonce');

        $customer_id = intval($_POST['customer_id'] ?? 0);
        if (!$customer_id) {
            $sender_id = get_current_user_id();
            global $wpdb;
            $customer_by_wp = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $sender_id));
            if ($customer_by_wp) $customer_id = $customer_by_wp->id;
        }

        if (!$this->can_access_customer($customer_id)) wp_send_json_error('Access denied');

        wp_send_json_success(Shipping_DB::get_ticket_messages($customer_id));
    }

    public function ajax_get_conversations() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_message_action', 'nonce');

        $user = wp_get_current_user();
        $has_full_access = current_user_can('manage_options');

        if (in_array('subscriber', (array)$user->roles)) {
             $officials = Shipping_DB::get_officials();
             $data = [];
             foreach($officials as $o) {
                 $data[] = [
                     'official' => [
                         'ID' => $o->ID,
                         'display_name' => $o->display_name,
                         'avatar' => get_avatar_url($o->ID)
                     ]
                 ];
             }
             wp_send_json_success(['type' => 'customer_view', 'officials' => $data]);
        } else {
             $conversations = Shipping_DB::get_all_conversations();
             foreach($conversations as &$c) {
                 $c['customer']->avatar = $c['customer']->photo_url ?: get_avatar_url($c['customer']->wp_user_id ?: 0);
             }
             wp_send_json_success(['type' => 'official_view', 'conversations' => $conversations]);
        }
    }

    public function ajax_mark_read() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_message_action', 'nonce');
        global $wpdb;
        $wpdb->update("{$wpdb->prefix}shipping_messages", ['is_read' => 1], ['receiver_id' => get_current_user_id(), 'sender_id' => intval($_POST['other_user_id'])]);
        wp_send_json_success();
    }


    public function handle_print() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');

        $type = sanitize_text_field($_GET['print_type'] ?? '');
        $customer_id = intval($_GET['customer_id'] ?? 0);

        if ($customer_id && !$this->can_access_customer($customer_id)) wp_die('Access denied');

        $customers = [];
        if ($customer_id) {
            $customers = [Shipping_DB::get_customer_by_id($customer_id)];
        } else {
            $customers = Shipping_DB::get_customers();
        }

        switch($type) {
            case 'id_card':
                include SHIPPING_PLUGIN_DIR . 'templates/print-id-cards.php';
                break;
            case 'credentials':
                include SHIPPING_PLUGIN_DIR . 'templates/print-customer-credentials.php';
                break;
            case 'credentials_card':
                include SHIPPING_PLUGIN_DIR . 'templates/print-customer-credentials-card.php';
                break;
            default:
                wp_die('Invalid print type');
        }
        exit;
    }


    public function ajax_forgot_password_otp() {
        $username = sanitize_text_field($_POST['username'] ?? '');
        $customer = Shipping_DB::get_customer_by_username($username);
        if (!$customer || !$customer->wp_user_id) {
            wp_send_json_error('اسم المستخدم غير مسجل في النظام');
        }

        $user = get_userdata($customer->wp_user_id);
        $otp = sprintf("%06d", mt_rand(1, 999999));

        update_user_meta($user->ID, 'shipping_recovery_otp', $otp);
        update_user_meta($user->ID, 'shipping_recovery_otp_time', time());
        update_user_meta($user->ID, 'shipping_recovery_otp_used', 0);

        $shipping = Shipping_Settings::get_shipping_info();
        $subject = "رمز استعادة كلمة المرور - " . $shipping['shipping_name'];
        $message = "عزيزي العميل " . $customer->name . ",\n\n";
        $message .= "رمز التحقق الخاص بك هو: " . $otp . "\n";
        $message .= "هذا الرمز صالح لمدة 10 دقائق فقط ولمرة واحدة.\n\n";
        $message .= "إذا لم تطلب هذا الرمز، يرجى تجاهل هذه الرسالة.\n";

        wp_mail($customer->email, $subject, $message);

        wp_send_json_success('تم إرسال رمز التحقق إلى بريدك الإلكتروني المسجل');
    }

    public function ajax_reset_password_otp() {
        $username = sanitize_text_field($_POST['username'] ?? '');
        $otp = sanitize_text_field($_POST['otp'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';

        $customer = Shipping_DB::get_customer_by_username($username);
        if (!$customer || !$customer->wp_user_id) wp_send_json_error('بيانات غير صحيحة');

        $user_id = $customer->wp_user_id;
        $saved_otp = get_user_meta($user_id, 'shipping_recovery_otp', true);
        $otp_time = get_user_meta($user_id, 'shipping_recovery_otp_time', true);
        $otp_used = get_user_meta($user_id, 'shipping_recovery_otp_used', true);

        if ($otp_used || $saved_otp !== $otp || (time() - $otp_time) > 600) {
            update_user_meta($user_id, 'shipping_recovery_otp_used', 1); // Mark as attempt made
            wp_send_json_error('رمز التحقق غير صحيح أو منتهي الصلاحية');
        }

        if (strlen($new_pass) < 10 || !preg_match('/^[a-zA-Z0-9]+$/', $new_pass)) {
            wp_send_json_error('كلمة المرور يجب أن تكون 10 أحرف على الأقل وتتكون من حروف وأرقام فقط بدون رموز');
        }

        wp_set_password($new_pass, $user_id);
        update_user_meta($user_id, 'shipping_recovery_otp_used', 1);

        wp_send_json_success('تمت إعادة تعيين كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول');
    }


    public function ajax_save_template_ajax() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');
        $res = Shipping_Notifications::save_template($_POST);
        if ($res) wp_send_json_success();
        else wp_send_json_error('Failed to save template');
    }



    public function ajax_save_page_settings() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');

        $id = intval($_POST['id']);
        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'instructions' => sanitize_textarea_field($_POST['instructions']),
            'settings' => stripslashes($_POST['settings'] ?? '{}')
        ];

        if (Shipping_DB::update_page($id, $data)) wp_send_json_success();
        else wp_send_json_error('Failed to update page');
    }

    public function ajax_add_article() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');

        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'content' => wp_kses_post($_POST['content']),
            'image_url' => esc_url_raw($_POST['image_url'] ?? ''),
            'status' => 'publish'
        ];

        if (Shipping_DB::add_article($data)) wp_send_json_success();
        else wp_send_json_error('Failed to add article');
    }

    public function ajax_delete_article() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_admin_action', 'nonce');

        if (Shipping_DB::delete_article(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete article');
    }


    public function ajax_check_username_email() {
        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if (!empty($username) && username_exists($username)) {
            wp_send_json_error(['field' => 'username', 'message' => 'اسم المستخدم هذا مستخدم بالفعل.']);
        }

        if (!empty($email) && email_exists($email)) {
            wp_send_json_error(['field' => 'email', 'message' => 'البريد الإلكتروني هذا مسجل بالفعل.']);
        }

        wp_send_json_success();
    }

    public function ajax_register_send_otp() {
        $email = sanitize_email($_POST['email'] ?? '');
        if (empty($email) || !is_email($email)) {
            wp_send_json_error('يرجى إدخال بريد إلكتروني صحيح.');
        }

        if (email_exists($email)) {
            wp_send_json_error('البريد الإلكتروني هذا مسجل بالفعل.');
        }

        $otp = sprintf("%06d", mt_rand(1, 999999));
        set_transient('shipping_reg_otp_' . md5($email), $otp, 15 * MINUTE_IN_SECONDS);

        $shipping = Shipping_Settings::get_shipping_info();
        $subject = "رمز التحقق الخاص بك - " . $shipping['shipping_name'];
        $message = "رمز التحقق الخاص بك لإتمام عملية التسجيل هو: " . $otp . "\nهذا الرمز صالح لمدة 15 دقيقة.";

        wp_mail($email, $subject, $message);

        wp_send_json_success('تم إرسال رمز التحقق إلى بريدك الإلكتروني.');
    }

    public function ajax_register_verify_otp() {
        $email = sanitize_email($_POST['email'] ?? '');
        $otp = sanitize_text_field($_POST['otp'] ?? '');

        $saved_otp = get_transient('shipping_reg_otp_' . md5($email));

        if ($saved_otp && $saved_otp === $otp) {
            delete_transient('shipping_reg_otp_' . md5($email));
            set_transient('shipping_reg_verified_' . md5($email), true, 30 * MINUTE_IN_SECONDS);
            wp_send_json_success('تم التحقق بنجاح.');
        } else {
            wp_send_json_error('رمز التحقق غير صحيح أو منتهي الصلاحية.');
        }
    }

    public function ajax_register_complete() {
        $data = $_POST;
        $email = sanitize_email($data['email'] ?? '');

        if (!get_transient('shipping_reg_verified_' . md5($email))) {
            wp_send_json_error('يرجى التحقق من البريد الإلكتروني أولاً.');
        }

        $username = sanitize_user($data['username']);
        $password = $data['password'];

        if (username_exists($username)) wp_send_json_error('اسم المستخدم موجود مسبقاً.');
        if (email_exists($email)) wp_send_json_error('البريد الإلكتروني مسجل بالفعل.');

        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => sanitize_text_field($data['first_name'] . ' ' . $data['last_name']),
            'role' => 'subscriber'
        ]);

        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }

        update_user_meta($user_id, 'first_name', sanitize_text_field($data['first_name']));
        update_user_meta($user_id, 'last_name', sanitize_text_field($data['last_name']));
        update_user_meta($user_id, 'shipping_account_status', 'active');

        $customer_data = [
            'username' => $username,
            'first_name' => sanitize_text_field($data['first_name']),
            'last_name' => sanitize_text_field($data['last_name']),
            'gender' => sanitize_text_field($data['gender']),
            'year_of_birth' => intval($data['year_of_birth']),
            'email' => $email,
            'wp_user_id' => $user_id,
            'account_status' => 'active'
        ];

        $customer_id = Shipping_DB::add_customer_record($customer_data);

        // Handle Profile Image
        if (!empty($_FILES['profile_image']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_image', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                Shipping_DB::update_customer_photo($customer_id, $photo_url);
            }
        }

        delete_transient('shipping_reg_verified_' . md5($email));

        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success(['redirect_url' => home_url('/shipping-admin')]);
    }


    // Ticketing System AJAX Handlers
    public function ajax_get_tickets() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_ticket_action', 'nonce');
        $args = array(
            'status' => $_GET['status'] ?? '',
            'category' => $_GET['category'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'search' => $_GET['search'] ?? ''
        );
        $tickets = Shipping_DB::get_tickets($args);
        wp_send_json_success($tickets);
    }

    public function ajax_create_ticket() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_ticket_action', 'nonce');

        $user = wp_get_current_user();
        global $wpdb;
        $customer = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user->ID));

        if (!$customer) wp_send_json_error('Customer profile not found');

        $file_url = null;
        if (!empty($_FILES['attachment']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('attachment', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            }
        }

        $data = array(
            'customer_id' => $customer->id,
            'subject' => sanitize_text_field($_POST['subject']),
            'category' => sanitize_text_field($_POST['category']),
            'priority' => sanitize_text_field($_POST['priority'] ?? 'medium'),
            'message' => sanitize_textarea_field($_POST['message']),
            'file_url' => $file_url
        );

        $ticket_id = Shipping_DB::create_ticket($data);
        if ($ticket_id) wp_send_json_success($ticket_id);
        else wp_send_json_error('Failed to create ticket');
    }

    public function ajax_get_ticket_details() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_ticket_action', 'nonce');
        $id = intval($_GET['id']);
        $ticket = Shipping_DB::get_ticket($id);

        if (!$ticket) wp_send_json_error('Ticket not found');

        // Check permission
        $user = wp_get_current_user();
        $is_sys_admin = in_array('administrator', $user->roles);

        if (!$is_sys_admin) {
             global $wpdb;
             $customer_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}shipping_customers WHERE wp_user_id = %d", $user->ID));
             if ($ticket->customer_id != $customer_id) wp_send_json_error('Access denied');
        }

        $thread = Shipping_DB::get_ticket_thread($id);
        wp_send_json_success(array('ticket' => $ticket, 'thread' => $thread));
    }

    public function ajax_add_ticket_reply() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_ticket_action', 'nonce');

        $ticket_id = intval($_POST['ticket_id']);

        $file_url = null;
        if (!empty($_FILES['attachment']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('attachment', 0);
            if (!is_wp_error($attachment_id)) {
                $file_url = wp_get_attachment_url($attachment_id);
            }
        }

        $data = array(
            'ticket_id' => $ticket_id,
            'sender_id' => get_current_user_id(),
            'message' => sanitize_textarea_field($_POST['message']),
            'file_url' => $file_url
        );

        $reply_id = Shipping_DB::add_ticket_reply($data);
        if ($reply_id) {
            // If officer replies, set status to in-progress
            if (!in_array('subscriber', wp_get_current_user()->roles)) {
                Shipping_DB::update_ticket_status($ticket_id, 'in-progress');
            }
            wp_send_json_success($reply_id);
        } else wp_send_json_error('Failed to add reply');
    }

    public function ajax_close_ticket() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_ticket_action', 'nonce');

        $id = intval($_POST['id']);
        if (Shipping_DB::update_ticket_status($id, 'closed')) wp_send_json_success();
        else wp_send_json_error('Failed to close ticket');
    }

    public function ajax_create_shipment() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_shipment_action', 'nonce');

        global $wpdb;
        $customer_id = intval($_POST['customer_id']);
        $origin_country = sanitize_text_field($_POST['origin_country'] ?? '');
        $destination_country = sanitize_text_field($_POST['destination_country'] ?? '');

        $origin = sanitize_text_field($origin_country . ', ' . ($_POST['origin_city'] ?? ''));
        $destination = sanitize_text_field($destination_country . ', ' . ($_POST['destination_city'] ?? ''));

        // Generate Tracking Number: [INT/LOC]YYYYMMDDXXXX
        $prefix = ($origin_country !== $destination_country) ? 'INT' : 'LOC';
        $date_part = current_time('Ymd');
        $random_part = sprintf('%04d', mt_rand(0, 9999));
        $shipment_number = $prefix . $date_part . $random_part;

        // Automated Cost Calculation
        $route_id = intval($_POST['route_id'] ?? 0);
        $distance = 0;
        if ($route_id) {
            $distance = $wpdb->get_var($wpdb->prepare("SELECT total_distance FROM {$wpdb->prefix}shipping_logistics WHERE id = %d", $route_id));
        }

        $estimate = Shipping_DB::estimate_shipment_cost([
            'weight' => floatval($_POST['weight']),
            'distance' => floatval($distance),
            'customer_id' => $customer_id,
            'classification' => sanitize_text_field($_POST['classification']),
            'is_urgent' => !empty($_POST['is_urgent']),
            'is_insured' => !empty($_POST['is_insured'])
        ]);

        $data = array(
            'shipment_number' => $shipment_number,
            'customer_id' => $customer_id,
            'origin' => $origin,
            'destination' => $destination,
            'weight' => floatval($_POST['weight']),
            'dimensions' => sanitize_text_field($_POST['dimensions']),
            'classification' => sanitize_text_field($_POST['classification']),
            'status' => 'pending',
            'pickup_date' => $_POST['pickup_date'],
            'dispatch_date' => $_POST['dispatch_date'],
            'delivery_date' => $_POST['delivery_date'],
            'carrier_id' => intval($_POST['carrier_id']),
            'route_id' => $route_id,
            'estimated_cost' => $estimate['total_cost'],
            'cost_breakdown_json' => json_encode($estimate['breakdown'])
        );

        $id = Shipping_DB::add_shipment($data);
        if ($id) {
            $order_id = intval($_POST['order_id'] ?? 0);
            if ($order_id) {
                Shipping_DB::update_order($order_id, ['shipment_id' => $id, 'status' => 'in-progress']);
            }

            // Automated Invoice Generation
            $invoice_id = Shipping_DB::create_invoice([
                'customer_id' => $customer_id,
                'order_id' => $order_id,
                'subtotal' => $estimate['breakdown']['base'] + $estimate['breakdown']['weight'] + $estimate['breakdown']['distance'],
                'tax_amount' => 0,
                'discount_amount' => $estimate['breakdown']['discount'],
                'total_amount' => $estimate['total_cost'],
                'items_json' => json_encode([
                    ['description' => 'Shipping Base Rate', 'amount' => $estimate['breakdown']['base']],
                    ['description' => 'Weight Surcharge', 'amount' => $estimate['breakdown']['weight']],
                    ['description' => 'Distance Rate', 'amount' => $estimate['breakdown']['distance']],
                    ['description' => 'Additional Fees/Insurance', 'amount' => $estimate['breakdown']['fees']]
                ]),
                'due_date' => date('Y-m-d', strtotime('+7 days'))
            ]);

            wp_send_json_success([
                'shipment_id' => $id,
                'shipment_number' => $shipment_number,
                'invoice_id' => $invoice_id,
                'total_cost' => $estimate['total_cost']
            ]);
        } else wp_send_json_error('Failed to create shipment');
    }

    public function ajax_update_shipment() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_shipment_action', 'nonce');

        $id = intval($_POST['id']);
        $data = array();
        $fields = ['status', 'location', 'description', 'carrier_id', 'route_id', 'is_archived'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) $data[$f] = sanitize_text_field($_POST[$f]);
        }

        if (Shipping_DB::update_shipment($id, $data)) wp_send_json_success();
        else wp_send_json_error('Failed to update shipment');
    }

    public function ajax_delete_shipment() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_shipment_action', 'nonce');
        $id = intval($_POST['id']);
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}shipping_shipment_tracking_events", ['shipment_id' => $id]);
        $wpdb->delete("{$wpdb->prefix}shipping_shipment_logs", ['shipment_id' => $id]);
        $wpdb->delete("{$wpdb->prefix}shipping_shipments", ['id' => $id]);
        wp_send_json_success();
    }

    public function ajax_get_shipment_tracking() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_shipment_action', 'nonce');
        $val = $_GET['id'] ?? $_GET['number'];

        if ($val === 'all') {
            global $wpdb;
            $shipments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}shipping_shipments WHERE status != 'delivered' AND is_archived = 0 AND current_lat IS NOT NULL");
            wp_send_json_success($shipments);
        } else {
            $shipment = Shipping_DB::get_shipment_with_tracking($val);
            if ($shipment) wp_send_json_success($shipment);
            else wp_send_json_error('Shipment not found');
        }
    }

    public function ajax_bulk_shipments() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_shipment_action', 'nonce');

        $rows = json_decode(stripslashes($_POST['rows']), true);
        $count = Shipping_DB::bulk_add_shipments($rows);
        wp_send_json_success($count);
    }

    public function ajax_save_invoice() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_billing_action', 'nonce');

        $data = array(
            'customer_id' => intval($_POST['customer_id']),
            'order_id' => intval($_POST['order_id'] ?? 0),
            'subtotal' => floatval($_POST['subtotal']),
            'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
            'discount_amount' => floatval($_POST['discount_amount'] ?? 0),
            'total_amount' => floatval($_POST['total_amount']),
            'items_json' => stripslashes($_POST['items_json']),
            'due_date' => sanitize_text_field($_POST['due_date']),
            'is_recurring' => intval($_POST['is_recurring'] ?? 0),
            'billing_interval' => sanitize_text_field($_POST['billing_interval'] ?? '')
        );

        $id = Shipping_DB::create_invoice($data);
        if ($id) wp_send_json_success($id);
        else wp_send_json_error('Failed to save invoice');
    }

    public function ajax_process_payment() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_billing_action', 'nonce');

        $data = array(
            'invoice_id' => intval($_POST['invoice_id']),
            'transaction_id' => 'TRX-' . strtoupper(wp_generate_password(10, false)),
            'amount_paid' => floatval($_POST['amount_paid']),
            'payment_method' => sanitize_text_field($_POST['payment_method'])
        );

        if (Shipping_DB::record_payment($data)) wp_send_json_success();
        else wp_send_json_error('Failed to process payment');
    }

    public function ajax_get_billing_report() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_revenue_stats());
    }


    public function ajax_add_order() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_order_action', 'nonce');

        $res = Shipping_DB::add_order($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add order');
    }

    public function ajax_get_orders() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $args = [
            'id' => intval($_GET['id'] ?? 0),
            'status' => sanitize_text_field($_GET['status'] ?? ''),
            'customer_id' => intval($_GET['customer_id'] ?? 0),
            'search' => sanitize_text_field($_GET['search'] ?? '')
        ];
        wp_send_json_success(Shipping_DB::get_orders($args));
    }

    public function ajax_update_order() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_order_action', 'nonce');
        $id = intval($_POST['id']);
        if (Shipping_DB::update_order($id, $_POST)) wp_send_json_success('Updated');
        else wp_send_json_error('Failed to update order');
    }

    public function ajax_delete_order() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_order_action', 'nonce');
        if (Shipping_DB::delete_order(intval($_POST['id']))) wp_send_json_success('Deleted');
        else wp_send_json_error('Failed to delete order');
    }

    public function ajax_get_order_logs() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_order_logs(intval($_GET['id'])));
    }

    public function ajax_bulk_update_orders() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_order_action', 'nonce');
        $ids = explode(',', $_POST['ids']);
        $status = sanitize_text_field($_POST['status']);
        $count = 0;
        foreach ($ids as $id) {
            if (Shipping_DB::update_order(intval($id), ['status' => $status])) $count++;
        }
        wp_send_json_success($count);
    }

    public function ajax_add_route() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        $res = Shipping_DB::add_route($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add route');
    }

    public function ajax_add_customs() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_customs_action', 'nonce');
        $res = Shipping_DB::add_customs_entry($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add customs entry');
    }

    public function ajax_get_customs_docs() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_customs_docs(intval($_GET['shipment_id'] ?? 0)));
    }

    public function ajax_add_customs_doc() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_customs_action', 'nonce');
        $res = Shipping_DB::add_customs_doc($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add customs document');
    }

    public function ajax_get_template_ajax() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $type = sanitize_text_field($_POST['type']);
        $template = Shipping_Notifications::get_template($type);
        if ($template) wp_send_json_success($template);
        else wp_send_json_error('Template not found');
    }

    public function ajax_get_contracts() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_contracts(intval($_GET['customer_id'] ?? 0)));
    }

    public function ajax_add_contract() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_contract_action', 'nonce');
        $res = Shipping_DB::add_contract($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add contract');
    }

    public function ajax_get_customs_status() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_customs_entries());
    }

    public function ajax_get_all_shipments() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        global $wpdb;
        $shipments = $wpdb->get_results("SELECT id, shipment_number FROM {$wpdb->prefix}shipping_shipments WHERE is_archived = 0 ORDER BY id DESC LIMIT 100");
        wp_send_json_success($shipments);
    }

    public function ajax_get_shipment_logs() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_shipment_logs(intval($_GET['id'])));
    }

    public function ajax_add_pricing() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_pricing_action', 'nonce');
        $res = Shipping_DB::add_pricing_rule($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add pricing rule');
    }

    public function ajax_get_pricing_rules() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_pricing_rules());
    }

    public function ajax_delete_pricing_rule() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_pricing_action', 'nonce');
        if (Shipping_DB::delete_pricing_rule(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete pricing rule');
    }

    public function ajax_get_additional_fees() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_additional_fees());
    }

    public function ajax_add_additional_fee() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_pricing_action', 'nonce');
        $res = Shipping_DB::add_additional_fee($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add additional fee');
    }

    public function ajax_delete_additional_fee() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_pricing_action', 'nonce');
        if (Shipping_DB::delete_additional_fee(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete additional fee');
    }


    public function ajax_estimate_cost() {
        if (!is_user_logged_in()) wp_send_json_error('Unauthorized');
        $res = Shipping_DB::estimate_shipment_cost($_POST);
        wp_send_json_success($res);
    }

    public function ajax_get_routes() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_routes());
    }

    public function ajax_update_route() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        $id = intval($_POST['id']);
        if (Shipping_DB::update_route($id, $_POST)) wp_send_json_success();
        else wp_send_json_error('Failed to update route');
    }

    public function ajax_delete_route() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::delete_route(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete route');
    }

    public function ajax_get_route_stops() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_route_stops(intval($_GET['route_id'])));
    }

    public function ajax_add_route_stop() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        $res = Shipping_DB::add_route_stop($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add route stop');
    }

    public function ajax_update_route_stop() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::update_route_stop(intval($_POST['id']), $_POST)) wp_send_json_success();
        else wp_send_json_error('Failed to update route stop');
    }

    public function ajax_delete_route_stop() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::delete_route_stop(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete route stop');
    }

    public function ajax_get_warehouses() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_warehouses());
    }

    public function ajax_add_warehouse() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        $res = Shipping_DB::add_warehouse($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add warehouse');
    }

    public function ajax_update_warehouse() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::update_warehouse(intval($_POST['id']), $_POST)) wp_send_json_success();
        else wp_send_json_error('Failed to update warehouse');
    }

    public function ajax_delete_warehouse() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::delete_warehouse(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete warehouse');
    }

    public function ajax_get_inventory() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_inventory(intval($_GET['warehouse_id'])));
    }

    public function ajax_add_inventory_item() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        $res = Shipping_DB::add_inventory_item($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add inventory item');
    }

    public function ajax_update_inventory_item() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::update_inventory_item(intval($_POST['id']), $_POST)) wp_send_json_success();
        else wp_send_json_error('Failed to update inventory item');
    }

    public function ajax_delete_inventory_item() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::delete_inventory_item(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete inventory item');
    }

    public function ajax_get_fleet() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_fleet());
    }

    public function ajax_add_vehicle() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        $res = Shipping_DB::add_vehicle($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add vehicle');
    }

    public function ajax_update_vehicle() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::update_vehicle(intval($_POST['id']), $_POST)) wp_send_json_success();
        else wp_send_json_error('Failed to update vehicle');
    }

    public function ajax_delete_vehicle() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::delete_vehicle(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete vehicle');
    }

    public function ajax_get_maintenance_logs() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_maintenance_logs(intval($_GET['vehicle_id'])));
    }

    public function ajax_add_maintenance_log() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        $res = Shipping_DB::add_maintenance_log($_POST);
        if ($res) wp_send_json_success($res);
        else wp_send_json_error('Failed to add maintenance log');
    }

    public function ajax_update_maintenance_log() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::update_maintenance_log(intval($_POST['id']), $_POST)) wp_send_json_success();
        else wp_send_json_error('Failed to update maintenance log');
    }

    public function ajax_delete_maintenance_log() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_logistic_action', 'nonce');
        if (Shipping_DB::delete_maintenance_log(intval($_POST['id']))) wp_send_json_success();
        else wp_send_json_error('Failed to delete maintenance log');
    }

    public function ajax_get_logistics_analytics() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        wp_send_json_success(Shipping_DB::get_logistics_analytics());
    }

    public function ajax_get_vehicle_shipments() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $vehicle_id = intval($_GET['vehicle_id']);
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name
             FROM {$wpdb->prefix}shipping_shipments s
             JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id
             WHERE s.carrier_id = %d AND s.is_archived = 0",
            $vehicle_id
        ));
        wp_send_json_success($results);
    }

    public function ajax_update_shipment_location() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        check_ajax_referer('shipping_shipment_action', 'nonce');
        $id = intval($_POST['id']);
        $data = array(
            'current_lat' => floatval($_POST['lat']),
            'current_lng' => floatval($_POST['lng']),
            'location' => sanitize_text_field($_POST['location'] ?? '')
        );
        if (Shipping_DB::update_shipment($id, $data)) {
            Shipping_DB::log_shipment_event($id, $_POST['status'] ?? 'in-transit', 'Location updated', $data['location']);
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to update location');
        }
    }

    public function ajax_public_tracking_ajax() {
        $number = sanitize_text_field($_GET['number'] ?? '');
        if (empty($number)) wp_send_json_error('Missing number');

        $shipment = Shipping_DB::get_shipment_with_tracking($number);
        if ($shipment) {
            // Data hardening: Only return fields safe for public viewing
            $public_data = [
                'shipment_number' => $shipment->shipment_number,
                'status'          => $shipment->status,
                'origin'          => $shipment->origin,
                'destination'     => $shipment->destination,
                'location'        => $shipment->location,
                'pickup_date'     => $shipment->pickup_date,
                'delivery_date'   => $shipment->delivery_date,
                'events'          => $shipment->events ?? []
            ];
            wp_send_json_success($public_data);
        } else {
            wp_send_json_error('Not found');
        }
    }

    public function ajax_get_shipment_full_details() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $id = intval($_GET['id']);
        global $wpdb;

        $shipment = $wpdb->get_row($wpdb->prepare("SELECT s.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name, f.vehicle_number, r.route_name FROM {$wpdb->prefix}shipping_shipments s LEFT JOIN {$wpdb->prefix}shipping_customers c ON s.customer_id = c.id LEFT JOIN {$wpdb->prefix}shipping_fleet f ON s.carrier_id = f.id LEFT JOIN {$wpdb->prefix}shipping_logistics r ON s.route_id = r.id WHERE s.id = %d", $id));
        if (!$shipment) wp_send_json_error('Shipment not found');

        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_orders WHERE shipment_id = %d", $id));
        $customs = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_customs WHERE shipment_id = %d", $id));
        $docs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_customs_docs WHERE shipment_id = %d", $id));

        $invoice = null;
        if ($order) {
            $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_invoices WHERE order_id = %d", $order->id));
        }

        $events = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}shipping_shipment_tracking_events WHERE shipment_id = %d ORDER BY created_at DESC", $id));

        wp_send_json_success([
            'shipment' => $shipment,
            'order' => $order,
            'customs' => $customs,
            'docs' => $docs,
            'invoice' => $invoice,
            'events' => $events
        ]);
    }


}
