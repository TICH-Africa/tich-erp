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

    <form method="POST" action="{{ route('procurement.suppliers.store') }}" class="tich-mt-6" enctype="multipart/form-data">
        @csrf
        <div class="tich-card tich-form-stack">
            <h2 class="tich-h3" style="margin-top:0;">Business details</h2>
            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="supplier_name">Company name <span class="tich-text--danger">*</span></label>
                    <input type="text" id="supplier_name" name="supplier_name" class="tich-input" value="{{ old('supplier_name') }}" required maxlength="300">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="registration_number">Registration number</label>
                    <input type="text" id="registration_number" name="registration_number" class="tich-input" value="{{ old('registration_number') }}" maxlength="100">
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="contact_person">Contact person</label>
                    <input type="text" id="contact_person" name="contact_person" class="tich-input" value="{{ old('contact_person') }}" maxlength="200">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="email">Email <span class="tich-text--danger">*</span></label>
                    <input type="email" id="email" name="email" class="tich-input" value="{{ old('email') }}" required maxlength="255">
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="phone">Phone <span class="tich-text--danger">*</span></label>
                    <input type="text" id="phone" name="phone" class="tich-input" value="{{ old('phone') }}" required maxlength="30">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="postal_address">Postal address</label>
                    <input type="text" id="postal_address" name="postal_address" class="tich-input" value="{{ old('postal_address') }}" maxlength="300">
                </div>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="physical_address">Physical address</label>
                <input type="text" id="physical_address" name="physical_address" class="tich-input" value="{{ old('physical_address') }}" maxlength="500">
            </div>

            <h2 class="tich-h3 tich-mt-6">Compliance &amp; tax</h2>
            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="kra_pin">KRA PIN</label>
                    <input type="text" id="kra_pin" name="kra_pin" class="tich-input" value="{{ old('kra_pin') }}" maxlength="50">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="compliance_doc_path">Compliance certificate</label>
                    <input type="file" id="compliance_doc_path" name="compliance_doc_path" class="tich-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="pin_certificate_path">PIN certificate</label>
                    <input type="file" id="pin_certificate_path" name="pin_certificate_path" class="tich-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="cr12_path">CR12</label>
                    <input type="file" id="cr12_path" name="cr12_path" class="tich-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
            </div>

            <div class="tich-form-group">
                <label class="tich-label" for="audited_financial_statements_path">Audited financial statements (last 2 years)</label>
                <input type="file" id="audited_financial_statements_path" name="audited_financial_statements_path" class="tich-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </div>

            <h2 class="tich-h3 tich-mt-6">Bank details</h2>
            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="bank_name">Bank name</label>
                    <input type="text" id="bank_name" name="bank_name" class="tich-input" value="{{ old('bank_name') }}" maxlength="200">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="bank_account_name">Account name</label>
                    <input type="text" id="bank_account_name" name="bank_account_name" class="tich-input" value="{{ old('bank_account_name') }}" maxlength="300">
                </div>
            </div>

            <div class="tich-grid tich-grid--2" style="gap:1rem;">
                <div class="tich-form-group">
                    <label class="tich-label" for="bank_account_number">Account number</label>
                    <input type="text" id="bank_account_number" name="bank_account_number" class="tich-input" value="{{ old('bank_account_number') }}" maxlength="50">
                </div>
                <div class="tich-form-group">
                    <label class="tich-label" for="bank_branch">Branch</label>
                    <input type="text" id="bank_branch" name="bank_branch" class="tich-input" value="{{ old('bank_branch') }}" maxlength="200">
                </div>
            </div>
        </div>

        <div class="tich-mt-6">
            <button type="submit" class="tich-btn tich-btn-primary">Register supplier</button>
        </div>
    </form>
@endsection
