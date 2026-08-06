@extends('layouts.app')

@section('title', 'Contact Us')

@section('content')
    <section class="tich-section tich-section--white">
        <div class="tich-container">
            <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                <div>
                    <h2 class="tich-h2">Contact Details</h2>
                    <p class="tich-text tich-mt-4">Use the form below for general inquiries, and find direct contact details for our key departments.</p>

                    <h3 class="tich-h3 tich-mt-6">Leave a message</h3>
                    <form class="tich-mt-4" onsubmit="event.preventDefault(); alert('Thank you for reaching out. We will get back to you shortly.');">
                        <div class="tich-grid tich-grid--2">
                            <div>
                                <label class="tich-label">Name</label>
                                <input type="text" class="tich-input" required>
                            </div>
                            <div>
                                <label class="tich-label">Email</label>
                                <input type="email" class="tich-input" required>
                            </div>
                        </div>
                        <div class="tich-mt-4">
                            <label class="tich-label">Phone</label>
                            <input type="tel" class="tich-input">
                        </div>
                        <div class="tich-mt-4">
                            <label class="tich-label">Message</label>
                            <textarea class="tich-input" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Send Message</button>
                    </form>
                </div>
                <div>
                    <div class="tich-card tich-mb-6">
                        <h3 class="tich-h3">Contact Information</h3>
                        
                        <div class="tich-mt-4">
                            <h4 class="tich-h4">ADMISSIONS</h4>
                            <p class="tich-caption tich-mt-2">Address</p>
                            <p class="tich-text">Milimani Kisumu, Nyanza Province, Kenya</p>
                            <p class="tich-caption tich-mt-2">Email</p>
                            <p class="tich-text">info@tichinafrica.org</p>
                            <p class="tich-caption tich-mt-2">Phone</p>
                            <p class="tich-text">+254 743 964 736</p>
                        </div>

                        <div class="tich-mt-6">
                            <h4 class="tich-h4">RESEARCH</h4>
                            <p class="tich-caption tich-mt-2">Address</p>
                            <p class="tich-text">Milimani Kisumu, Nyanza Province, Kenya</p>
                            <p class="tich-caption tich-mt-2">Email</p>
                            <p class="tich-text">info@tichinafrica.org</p>
                            <p class="tich-caption tich-mt-2">Phone</p>
                            <p class="tich-text">+254 743 964 736</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
