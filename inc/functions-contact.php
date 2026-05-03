<?php
add_filter('render_block', function(string $block_content, array $block): string {
    if (!isset($block['blockName']) || $block['blockName'] !== 'core/html') {
        return $block_content;
    }
    $raw = isset($block['innerHTML']) ? $block['innerHTML'] : '';
    if (false === strpos($raw, 'spoke_contact_form')) {
        return $block_content;
    }

    // ── Use FluentForms if installed ──────────────────────────────
    if (shortcode_exists('fluentform')) {
        return do_shortcode('[fluentform id="1"]');
    }

    // ── Fallback form ─────────────────────────────────────────────
    ob_start();
    ?>
<style>
.cf-input,.cf-select,.cf-textarea{width:100%;background:#f3f4f5;border:1px solid rgba(0,0,0,0.12);border-radius:8px;font-family:inherit;font-size:16px;color:#191c1d;transition:border-color 200ms,box-shadow 200ms;appearance:none;-webkit-appearance:none;}
.cf-input,.cf-select{height:50px;padding:0 16px;}
.cf-textarea{padding:14px 16px;min-height:130px;resize:vertical;height:auto;}
.cf-input:focus,.cf-select:focus,.cf-textarea:focus{outline:none;border-color:#F4A726;box-shadow:0 0 0 3px rgba(244,167,38,0.15);}
.cf-input.cf-invalid,.cf-select.cf-invalid,.cf-textarea.cf-invalid{border-color:#BA1A1A!important;box-shadow:0 0 0 3px rgba(186,26,26,0.10)!important;}
.cf-field-error{font-size:12px;color:#BA1A1A;margin-top:4px;display:none;}
.cf-field-error.cf-show{display:block;}
</style>
<form id="cf-form" novalidate class="flex flex-col gap-5">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="flex flex-col gap-1.5">
            <label for="cf-fname" class="text-[11px] font-bold uppercase tracking-[0.8px]" style="color:#43474f">First Name <span style="color:#F4A726">*</span></label>
            <input type="text" id="cf-fname" name="first_name" class="cf-input" placeholder="John" autocomplete="given-name">
            <span class="cf-field-error" id="cf-fname-err">Please enter your first name.</span>
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="cf-lname" class="text-[11px] font-bold uppercase tracking-[0.8px]" style="color:#43474f">Last Name <span style="color:#F4A726">*</span></label>
            <input type="text" id="cf-lname" name="last_name" class="cf-input" placeholder="Smith" autocomplete="family-name">
            <span class="cf-field-error" id="cf-lname-err">Please enter your last name.</span>
        </div>
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="cf-email" class="text-[11px] font-bold uppercase tracking-[0.8px]" style="color:#43474f">Email Address <span style="color:#F4A726">*</span></label>
        <input type="email" id="cf-email" name="email" class="cf-input" placeholder="john@example.co.uk" autocomplete="email">
        <span class="cf-field-error" id="cf-email-err">Please enter a valid email address.</span>
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="cf-phone" class="text-[11px] font-bold uppercase tracking-[0.8px]" style="color:#43474f">Phone Number <span class="font-normal normal-case tracking-normal" style="color:#43474f">(optional)</span></label>
        <input type="tel" id="cf-phone" name="phone" class="cf-input" placeholder="+44 7700 000000" autocomplete="tel">
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="cf-subject" class="text-[11px] font-bold uppercase tracking-[0.8px]" style="color:#43474f">Enquiry Type <span style="color:#F4A726">*</span></label>
        <div class="relative">
            <select id="cf-subject" name="subject" class="cf-select pr-10">
                <option value="" disabled selected>Please select&hellip;</option>
                <option value="course">Course Enquiry</option>
                <option value="accreditation">Accreditation Question</option>
                <option value="corporate">Corporate / Group Training</option>
                <option value="technical">Technical Support</option>
                <option value="billing">Billing &amp; Payments</option>
                <option value="other">Other</option>
            </select>
            <svg class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none w-4 h-4" style="color:#43474f" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <span class="cf-field-error" id="cf-subject-err">Please select an enquiry type.</span>
    </div>

    <div class="flex flex-col gap-1.5">
        <label for="cf-message" class="text-[11px] font-bold uppercase tracking-[0.8px]" style="color:#43474f">Message <span style="color:#F4A726">*</span></label>
        <textarea id="cf-message" name="message" class="cf-textarea" rows="5" placeholder="Tell us how we can help&hellip;"></textarea>
        <span class="cf-field-error" id="cf-message-err">Please enter a message (at least 10 characters).</span>
    </div>

    <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" id="cf-consent" name="consent" class="mt-0.5 w-4 h-4 rounded flex-shrink-0" style="accent-color:#1A3C6E">
        <span class="text-[13px]" style="color:#43474f">I agree to StudyMate Central&rsquo;s <a href="/privacy-policy/" class="font-semibold underline" style="color:#1A3C6E">Privacy Policy</a>.</span>
    </label>
    <span class="cf-field-error" id="cf-consent-err">You must agree to the privacy policy to continue.</span>

    <button type="submit" id="cf-submit" class="w-full h-[52px] rounded-lg font-bold text-[17px] transition-all hover:brightness-105" style="background:#F4A726;color:#6b4500">Send Message</button>

    <div class="hidden rounded-lg px-4 py-4 flex flex-col gap-1" id="cf-success" role="alert" style="background:#E8F4EC;border:1px solid #c8e6c9;">
        <p class="font-bold text-[14px] m-0" style="color:#1b5e20">&#10003; Message received &mdash; thank you!</p>
        <p class="text-[13px] m-0" style="color:#2e7d32">We aim to respond within 2 business hours, Monday to Friday 9am&ndash;6pm GMT.</p>
    </div>

</form>
<script>
(function(){
    'use strict';

    var form     = document.getElementById('cf-form');
    var btn      = document.getElementById('cf-submit');
    var success  = document.getElementById('cf-success');
    if (!form) return;

    // ── Helpers ───────────────────────────────────────────────────
    function isEmail(v) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
    }

    function setError(fieldId, errId, show) {
        var field = document.getElementById(fieldId);
        var err   = document.getElementById(errId);
        if (!field || !err) return;
        if (show) {
            field.classList.add('cf-invalid');
            err.classList.add('cf-show');
        } else {
            field.classList.remove('cf-invalid');
            err.classList.remove('cf-show');
        }
    }

    // ── Live clear-on-fix ─────────────────────────────────────────
    ['cf-fname','cf-lname','cf-email','cf-subject','cf-message'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function() {
            el.classList.remove('cf-invalid');
            var errEl = document.getElementById(id + '-err');
            if (errEl) errEl.classList.remove('cf-show');
        });
    });
    var consentEl = document.getElementById('cf-consent');
    if (consentEl) {
        consentEl.addEventListener('change', function() {
            setError('cf-consent', 'cf-consent-err', false);
        });
    }

    // ── Submit ────────────────────────────────────────────────────
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var fname   = document.getElementById('cf-fname').value.trim();
        var lname   = document.getElementById('cf-lname').value.trim();
        var email   = document.getElementById('cf-email').value.trim();
        var subject = document.getElementById('cf-subject').value;
        var message = document.getElementById('cf-message').value.trim();
        var consent = document.getElementById('cf-consent').checked;

        // Validate each field independently so multiple errors show at once.
        var valid = true;

        if (!fname)                          { setError('cf-fname',   'cf-fname-err',   true);  valid = false; } else { setError('cf-fname',   'cf-fname-err',   false); }
        if (!lname)                          { setError('cf-lname',   'cf-lname-err',   true);  valid = false; } else { setError('cf-lname',   'cf-lname-err',   false); }
        if (!email || !isEmail(email))       { setError('cf-email',   'cf-email-err',   true);  valid = false; } else { setError('cf-email',   'cf-email-err',   false); }
        if (!subject)                        { setError('cf-subject', 'cf-subject-err', true);  valid = false; } else { setError('cf-subject', 'cf-subject-err', false); }
        if (!message || message.length < 10) { setError('cf-message', 'cf-message-err', true);  valid = false; } else { setError('cf-message', 'cf-message-err', false); }
        if (!consent)                        { setError('cf-consent', 'cf-consent-err', true);  valid = false; } else { setError('cf-consent', 'cf-consent-err', false); }

        if (!valid) {
            // Scroll to first error.
            var first = form.querySelector('.cf-invalid');
            if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // ── Fake send ─────────────────────────────────────────────
        btn.textContent = 'Sending\u2026';
        btn.disabled    = true;
        btn.style.opacity = '0.7';

        setTimeout(function() {
            form.style.display = 'none';
            success.classList.remove('hidden');
        }, 1000);
    });
}());
</script>
    <?php
    $html = ob_get_clean();
    $html = preg_replace('/<br\s*\/?>/i', '', $html);
    $html = preg_replace('/<p>(\s|&nbsp;)*<\/p>/i', '', $html);
    return $html;

}, 10, 2);