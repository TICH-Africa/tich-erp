@extends('layouts.procurement')

@section('title', 'Edit ' . $supplier->supplier_name)

@section('procurement-content')
    <x-page-toolbar title="Edit supplier" meta="{{ $supplier->supplier_code }}">
        <x-slot:actions>
            <a href="{{ route('procurement.suppliers.show', $supplier) }}" class="tich-btn tich-btn-ghost">Cancel</a>
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

    <form method="POST" action="{{ route('procurement.suppliers.update', $supplier) }}" class="tich-card tich-mt-6">
        @csrf
        @method('PUT')

        <h2 class="tich-h3">Business details</h2>
        <div class="tich-grid tich-grid--2" style="gap:1rem;">
            <div class="tich-form-group">
                <label class="tich-label" for="supplier_code">Supplier code</label>
                <input type="text" id="supplier_code" name="supplier_code" class="tich-input" value="{{ old('supplier_code', $supplier->supplier_code) }}" readonly>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="supplier_name">Company name *</label>
                <input type="text" id="supplier_name" name="supplier_name" class="tich-input" value="{{ old('supplier_name', $supplier->supplier_name) }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="registration_number">Registration number</label>
                <input type="text" id="registration_number" name="registration_number" class="tich-input" value="{{ old('registration_number', $supplier->registration_number) }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="contact_person">Contact person</label>
                <input type="text" id="contact_person" name="contact_person" class="tich-input" value="{{ old('contact_person', $supplier->contact_person) }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="email">Email *</label>
                <input type="email" id="email" name="email" class="tich-input" value="{{ old('email', $supplier->email) }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="phone">Phone *</label>
                <input type="text" id="phone" name="phone" class="tich-input" value="{{ old('phone', $supplier->phone) }}" required>
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="postal_address">Postal address</label>
                <input type="text" id="postal_address" name="postal_address" class="tich-input" value="{{ old('postal_address', $supplier->postal_address) }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="physical_address">Physical address</label>
                <input type="text" id="physical_address" name="physical_address" class="tich-input" value="{{ old('physical_address', $supplier->physical_address) }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="kra_pin">KRA PIN</label>
                <input type="text" id="kra_pin" name="kra_pin" class="tich-input" value="{{ old('kra_pin', $supplier->kra_pin) }}">
            </div>
            <div class="tich-form-group">
                <label class="tich-label" for="payment_method">Payment method</label>
                <select id="payment_method" name="payment_method" class="tich-input">
                    <option value="bank" @selected($supplier->payment_method === 'bank')>Bank transfer</option>
                    <option value="paybill" @selected($supplier->payment_method === 'paybill')>M-Pesa Paybill</option>
                    <option value="till" @selected($supplier->payment_method === 'till')>M-Pesa Till</option>
                    <option value="mobile" @selected($supplier->payment_method === 'mobile')>Mobile number</option>
                </select>
            </div>
            <div class="tich-form-group" id="bank_details_wrap" @style(['display:none' => ($supplier->payment_method ?? 'bank') !== 'bank'] )>
                <label class="tich-label" for="bank_name">Bank</label>
                <input type="text" id="bank_name" name="bank_name" class="tich-input" value="{{ old('bank_name', $supplier->bank_name) }}">
            </div>
            <div class="tich-form-group" id="bank_details_wrap2" @style(['display:none' => ($supplier->payment_method ?? 'bank') !== 'bank'] )>
                <label class="tich-label" for="bank_account_name">Account name</label>
                <input type="text" id="bank_account_name" name="bank_account_name" class="tich-input" value="{{ old('bank_account_name', $supplier->bank_account_name) }}">
            </div>
            <div class="tich-form-group" id="bank_details_wrap3" @style(['display:none' => ($supplier->payment_method ?? 'bank') !== 'bank'] )>
                <label class="tich-label" for="bank_account_number">Account number</label>
                <input type="text" id="bank_account_number" name="bank_account_number" class="tich-input" value="{{ old('bank_account_number', $supplier->bank_account_number) }}">
            </div>
            <div class="tich-form-group" id="bank_details_wrap4" @style(['display:none' => ($supplier->payment_method ?? 'bank') !== 'bank'] )>
                <label class="tich-label" for="bank_branch">Branch</label>
                <input type="text" id="bank_branch" name="bank_branch" class="tich-input" value="{{ old('bank_branch', $supplier->bank_branch) }}">
            </div>
            <div class="tich-form-group" id="bank_details_wrap5" @style(['display:none' => ($supplier->payment_method ?? 'bank') !== 'bank'] )>
                <label class="tich-label" for="bank_code">Bank code</label>
                <input type="text" id="bank_code" name="bank_code" class="tich-input" value="{{ old('bank_code', $supplier->bank_code) }}">
            </div>
            <div class="tich-form-group" id="paybill_wrap" @style(['display:none' => ($supplier->payment_method ?? '') !== 'paybill'] )>
                <label class="tich-label" for="paybill_number">Paybill number</label>
                <input type="text" id="paybill_number" name="paybill_number" class="tich-input" value="{{ old('paybill_number', $supplier->paybill_number) }}">
                <label class="tich-label" for="paybill_account_number">Account number</label>
                <input type="text" id="paybill_account_number" name="paybill_account_number" class="tich-input" value="{{ old('paybill_account_number', $supplier->paybill_account_number) }}" maxlength="50" placeholder="Account number for Paybill">
            </div>
            <div class="tich-form-group" id="till_wrap" @style(['display:none' => ($supplier->payment_method ?? '') !== 'till'] )>
                <label class="tich-label" for="till_number">Till number</label>
                <input type="text" id="till_number" name="till_number" class="tich-input" value="{{ old('till_number', $supplier->till_number) }}">
            </div>
            <div class="tich-form-group" id="mobile_wrap" @style(['display:none' => ($supplier->payment_method ?? '') !== 'mobile'] )>
                <label class="tich-label" for="mobile_number">Mobile number</label>
                <input type="text" id="mobile_number" name="mobile_number" class="tich-input" value="{{ old('mobile_number', $supplier->mobile_number) }}" placeholder="07XX XXXXXXX">
            </div>
            <div class="tich-form-group" style="grid-column: 1 / -1;">
                <label class="tich-label" for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" class="tich-input">{{ old('notes', $supplier->notes) }}</textarea>
            </div>
        </div>

        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Update supplier</button>
        </div>
    </form>

    <script>
        (function () {
            var select = document.getElementById('payment_method');
            var bankWrap = document.getElementById('bank_details_wrap');
            var bankWrap2 = document.getElementById('bank_details_wrap2');
            var bankWrap3 = document.getElementById('bank_details_wrap3');
            var bankWrap4 = document.getElementById('bank_details_wrap4');
            var bankWrap5 = document.getElementById('bank_details_wrap5');
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
                if (bankWrap5) bankWrap5.style.display = isBank ? '' : 'none';
                if (paybillWrap) paybillWrap.style.display = isPaybill ? '' : 'none';
                if (tillWrap) tillWrap.style.display = isTill ? '' : 'none';
                if (mobileWrap) mobileWrap.style.display = isMobile ? '' : 'none';
            }

            select.addEventListener('change', syncPaymentFields);
            syncPaymentFields();
        })();
    </script>
@endsection