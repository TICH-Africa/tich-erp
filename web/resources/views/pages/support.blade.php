@extends('layouts.app')

@section('title', 'Support Us')

@section('content')
    <section class="tich-section">
        <div class="tich-container">
            <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                <div>
                    <h2 class="tich-h2">About TICH Fund</h2>
                    <p class="tich-text tich-mt-4">
                        At TICH, we believe in empowering individuals, families, and communities to achieve a healthy, just, and prosperous future. Our work is driven by the principle that every person and community has the inherent potential to thrive, and with the right resources, support, and partnerships, we can foster sustainable and transformative change.
                    </p>
                    <p class="tich-text tich-mt-4">
                        Your support plays a vital role in realizing this vision. By contributing to our initiatives, you are directly impacting the development of community health, vocational skills, and academic excellence. Together, we can help create a world where everyone is equipped with the necessary capacities to lead dignified lives, overcome challenges, and respond effectively to crises.
                    </p>
                    <p class="tich-text tich-mt-4">
                        By supporting TICH, you are helping to realize a vision of a sustainable, just society where individuals and communities have the tools, knowledge, and support to thrive. With your donation, we can continue to:
                    </p>
                    <ul class="tich-mt-4">
                        <li>Provide academic and vocational training programs that empower individuals and uplift communities.</li>
                        <li>Strengthen healthcare systems by training leaders in community health, medical education, and technological skills.</li>
                        <li>Contribute to sustainable development through evidence-based practices and collaborative efforts with partners across the globe.</li>
                    </ul>
                    <p class="tich-text tich-mt-4">Thank you for your support</p>
                    <p class="tich-text">On behalf of the entire TICH family, we extend our heartfelt gratitude for your commitment to building a better future. Your generosity drives our mission forward and brings us closer to a world where everyone has access to the opportunities they need to succeed.</p>
                </div>
                <div class="tich-card">
                    <h3 class="tich-h3">Make a Donation</h3>
                    <p class="tich-text tich-mt-2">Support our mission and help us empower communities.</p>
                    <div class="tich-mt-4">
                        <label class="tich-label">Donation Amount (KES)</label>
                        <input type="number" class="tich-input" placeholder="Enter amount">
                    </div>
                    <div class="tich-mt-4">
                        <label class="tich-label">Full Name</label>
                        <input type="text" class="tich-input">
                    </div>
                    <div class="tich-mt-4">
                        <label class="tich-label">Email</label>
                        <input type="email" class="tich-input">
                    </div>
                    <div class="tich-mt-4">
                        <label class="tich-label">Message (optional)</label>
                        <textarea class="tich-input" rows="3"></textarea>
                    </div>
                    <button type="button" class="tich-btn tich-btn-success tich-mt-4" onclick="alert('Thank you for your generous donation. This is a demo — no payment was processed.')">Donate Now</button>
                </div>
            </div>
        </div>
    </section>
@endsection
