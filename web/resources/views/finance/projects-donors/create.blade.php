@extends('layouts.finance')

@section('title', 'Projects & Donors')

@section('finance-content')
    <x-page-toolbar title="Projects & Donors" meta="Create project">
        <x-slot:actions>
            <a href="{{ route('finance.projects-donors.index', $department) }}" class="tich-btn tich-btn-ghost">Back</a>
        </x-slot:actions>
    </x-page-toolbar>

    <div class="tich-card">
        <form method="POST" action="{{ route('finance.projects-donors.store', $department) }}" class="tich-form">
            @csrf
            <div class="tich-form__row">
                <label class="tich-form__label">Project name</label>
                <input type="text" name="name" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Donor</label>
                <select name="donor_id" class="tich-form__input" required>
                    <option value="">Select donor</option>
                </select>
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Budget (USD)</label>
                <input type="number" name="budget_usd" class="tich-form__input" step="0.01" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Start date</label>
                <input type="date" name="start_date" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">End date</label>
                <input type="date" name="end_date" class="tich-form__input" required />
            </div>
            <div class="tich-form__row">
                <label class="tich-form__label">Notes</label>
                <textarea name="notes" class="tich-form__input" rows="3"></textarea>
            </div>
            <button type="submit" class="tich-btn tich-btn-primary">Create project</button>
        </form>
    </div>
@endsection

