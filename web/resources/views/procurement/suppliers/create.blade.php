@extends('layouts.procurement')

@section('title', 'Register Supplier')

@section('procurement-content')
    <x-page-toolbar title="Register Supplier" meta="Onboard a new supplier into the procurement registry">
        <x-slot:actions>
            <a href="{{ route('procurement.suppliers.index') }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    @if ($errors->any())
        <div class="tich-alert tich-alert--error tich-mt-4">
            <ul style="margin:0; padding-left:1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="uf-form">
        <form method="POST" action="{{ route('procurement.suppliers.store') }}" enctype="multipart/form-data" data-uf="ready">
            @csrf

            <div class="uf-amount-bar">
                <div>
                    <div class="uf-amount-bar__ref">SUP · Supplier registry</div>
                    <div class="uf-amount-bar__sum">Onboard new supplier</div>
                </div>
                <span class="uf-badge">New</span>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Business Details</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="supplier_name">Company name <span class="uf-req">*</span></label>
                            <input type="text" id="supplier_name" name="supplier_name" value="{{ old('supplier_name') }}" required maxlength="300">
                        </div>
                        <div class="uf-field">
                            <label for="registration_number">Registration number</label>
                            <input type="text" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" maxlength="100">
                        </div>
                        <div class="uf-field">
                            <label for="contact_person">Contact person</label>
                            <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person') }}" maxlength="200">
                        </div>
                        <div class="uf-field">
                            <label for="email">Email <span class="uf-req">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255">
                        </div>
                        <div class="uf-field">
                            <label for="phone">Phone <span class="uf-req">*</span></label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required maxlength="30">
                        </div>
                        <div class="uf-field">
                            <label for="postal_address">Postal address</label>
                            <input type="text" id="postal_address" name="postal_address" value="{{ old('postal_address') }}" maxlength="300">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="physical_address">Physical address</label>
                        <input type="text" id="physical_address" name="physical_address" value="{{ old('physical_address') }}" maxlength="500">
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Compliance & Tax</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="kra_pin">KRA PIN</label>
                            <input type="text" id="kra_pin" name="kra_pin" value="{{ old('kra_pin') }}" maxlength="50">
                        </div>
                        <div class="uf-field">
                            <label for="compliance_doc_path">Compliance certificate</label>
                            <input type="file" id="compliance_doc_path" name="compliance_doc_path" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>
                        <div class="uf-field">
                            <label for="pin_certificate_path">PIN certificate</label>
                            <input type="file" id="pin_certificate_path" name="pin_certificate_path" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>
                        <div class="uf-field">
                            <label for="cr12_path">CR12</label>
                            <input type="file" id="cr12_path" name="cr12_path" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="uf-field">
                        <label for="audited_financial_statements_path">Audited financial statements (last 2 years)</label>
                        <input type="file" id="audited_financial_statements_path" name="audited_financial_statements_path" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                    </div>
                </div>
            </div>

            <div class="uf-form-section">
                <div class="uf-section-head">Bank Details & Submit</div>
                <div class="uf-section-body">
                    <div class="uf-form-grid-2">
                        <div class="uf-field">
                            <label for="payment_method">Payment method <span class="uf-req">*</span></label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="bank" @selected(old('payment_method', 'bank') === 'bank')>Bank transfer</option>
                                <option value="paybill" @selected(old('payment_method') === 'paybill')>M-Pesa Paybill</option>
                                <option value="till" @selected(old('payment_method') === 'till')>M-Pesa Till</option>
                                <option value="mobile" @selected(old('payment_method') === 'mobile')>Mobile number</option>
                            </select>
                        </div>
                        <div class="uf-field" id="bank_details_wrap" @style(['display:none' => old('payment_method', 'bank') !== 'bank'] )>
                            <label for="bank_name">Bank name</label>
                            <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" maxlength="200">
                        </div>
                        <div class="uf-field" id="bank_details_wrap2" @style(['display:none' => old('payment_method', 'bank') !== 'bank'] )>
                            <label for="bank_account_name">Account name</label>
                            <input type="text" id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name') }}" maxlength="300">
                        </div>
                        <div class="uf-field" id="bank_details_wrap3" @style(['display:none' => old('payment_method', 'bank') !== 'bank'] )>
                            <label for="bank_account_number">Account number</label>
                            <input type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number') }}" maxlength="50">
                        </div>
                        <div class="uf-field" id="bank_details_wrap4" @style(['display:none' => old('payment_method', 'bank') !== 'bank'] )>
                            <label for="bank_branch">Branch</label>
                            <input type="text" id="bank_branch" name="bank_branch" value="{{ old('bank_branch') }}" maxlength="200">
                        </div>
                        <div class="uf-field" id="paybill_wrap" @style(['display:none' => old('payment_method') !== 'paybill'] )>
                            <label for="paybill_number">Paybill number <span class="uf-req">*</span></label>
                            <input type="text" id="paybill_number" name="paybill_number" value="{{ old('paybill_number') }}" maxlength="20" @if(old('payment_method') === 'paybill') required @endif>
                            <label for="paybill_account_number">Account number <span class="uf-req">*</span></label>
                            <input type="text" id="paybill_account_number" name="paybill_account_number" value="{{ old('paybill_account_number') }}" maxlength="50" @if(old('payment_method') === 'paybill') required @endif placeholder="Account number for Paybill">
                        </div>
                        <div class="uf-field" id="till_wrap" @style(['display:none' => old('payment_method') !== 'till'] )>
                            <label for="till_number">Till number <span class="uf-req">*</span></label>
                            <input type="text" id="till_number" name="till_number" value="{{ old('till_number') }}" maxlength="20" @if(old('payment_method') === 'till') required @endif>
                        </div>
                        <div class="uf-field" id="mobile_wrap" @style(['display:none' => old('payment_method') !== 'mobile'] )>
                            <label for="mobile_number">Mobile number <span class="uf-req">*</span></label>
                            <input type="text" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}" maxlength="20" placeholder="07XX XXXXXXX" @if(old('payment_method') === 'mobile') required @endif>
                        </div>
                    </div>
                    <div class="uf-form-actions">
                        <button type="submit" class="uf-btn uf-btn-primary">Register supplier</button>
                        <a href="{{ route('procurement.suppliers.index') }}" class="uf-btn uf-btn-secondary">Cancel</a>
                    </div>
                </div>
            </div>

            <p class="uf-form-footnote"><span class="uf-req">*</span> Required field</p>
        </form>
    </div>

    <script>
        (function () {
            var select = document.getElementById('payment_method');
            var bankWrap = document.getElementById('bank_details_wrap');
            var bankWrap2 = document.getElementById('bank_details_wrap2');
            var bankWrap3 = document.getElementById('bank_details_wrap3');
            var bankWrap4 = document.getElementById('bank_details_wrap4');
            var paybillWrap = document.getElementById('paybill_wrap');
            var tillWrap = document.getElementById('till_wrap');
            var mobileWrap = document.getElementById('mobile_wrap');
            if (!select) return;

            function syncPaymentFields() {
                var val = select.value;
                var isBank = val === 'bank';
                var isPaybill = val === 'paybill';
                var isTill = val === 'till';
                var isMobile = val === 'mobile';
                if (bankWrap) bankWrap.style.display = isBank ? '' : 'none';
                if (bankWrap2) bankWrap2.style.display = isBank ? '' : 'none';
                if (bankWrap3) bankWrap3.style.display = isBank ? '' : 'none';
                if (bankWrap4) bankWrap4.style.display = isBank ? '' : 'none';
                if (paybillWrap) paybillWrap.style.display = isPaybill ? '' : 'none';
                if (tillWrap) tillWrap.style.display = isTill ? '' : 'none';
                if (mobileWrap) mobileWrap.style.display = isMobile ? '' : 'none';
            }

            select.addEventListener('change', syncPaymentFields);
            syncPaymentFields();
        })();
    </script>
@endsection