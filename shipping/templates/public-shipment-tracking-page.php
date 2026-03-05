<?php if (!defined('ABSPATH')) exit; ?>
<div class="shipping-public-tracking" dir="rtl" style="font-family: 'Rubik', sans-serif; max-width: 800px; margin: 40px auto; padding: 30px; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
    <div style="text-align: center; margin-bottom: 40px;">
        <h2 style="font-weight: 800; color: #111F35; font-size: 2em; margin-bottom: 10px;">تتبع شحنتك</h2>
        <p style="color: #64748b;">أدخل رقم التتبع الخاص بشحنتك لمعرفة حالتها وموقعها الحالي فوراً.</p>
    </div>

    <div class="tracking-search-form" style="display: flex; gap: 10px; background: #f8fafc; padding: 20px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 40px;">
        <input type="text" id="public-track-number" placeholder="مثال: SHP-XXXXXX" style="flex: 1; height: 55px; border: 2px solid #e2e8f0; border-radius: 12px; padding: 0 20px; font-size: 1.1em; outline: none; transition: 0.3s;" onfocus="this.style.borderColor='var(--shipping-primary-color)'" onblur="this.style.borderColor='#e2e8f0'">
        <button onclick="PublicController.trackShipment()" class="shipping-btn" style="width: auto; height: 55px; padding: 0 35px; border-radius: 12px; font-size: 1.1em; font-weight: 800;">تتبع الآن</button>
    </div>

    <div id="public-tracking-result" style="display: none; animation: fadeIn 0.4s ease;">
        <div style="background: #fff; border: 2px solid #edf2f7; border-radius: 15px; padding: 25px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
                <div>
                    <div style="font-size: 12px; color: #94a3b8; margin-bottom: 5px;">رقم الشحنة</div>
                    <h3 id="pub-res-number" style="margin: 0; color: var(--shipping-primary-color); font-weight: 800;"></h3>
                </div>
                <span id="pub-res-status" class="shipping-badge" style="font-size: 14px; padding: 10px 20px;"></span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; font-size: 14px;">
                <div><strong>من:</strong> <span id="pub-res-origin"></span></div>
                <div><strong>إلى:</strong> <span id="pub-res-destination"></span></div>
            </div>
        </div>

        <div id="pub-res-timeline" style="position: relative; padding-right: 35px; margin-top: 30px;">
            <!-- Events injected here -->
        </div>
    </div>

    <div id="pub-res-error" style="display: none; text-align: center; padding: 40px; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 15px; color: #c53030;">
        <span class="dashicons dashicons-warning" style="font-size: 40px; width: 40px; height: 40px; margin-bottom: 10px;"></span>
        <p style="font-weight: 700; margin: 0;">عذراً، لم يتم العثور على شحنة بهذا الرقم. يرجى التأكد من الرقم والمحاولة مرة أخرى.</p>
    </div>
</div>


<style>
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
