@extends('layouts.app')

@section('title', 'Support Us')
@section('meta_description', config('tich-seo.pages.support.description'))

@section('content')
    <x-animated-section animation="fade">
        <section class="tich-section" aria-labelledby="support-heading">
            <div class="tich-container">
                <header class="tich-mb-8">
                    <h1 id="support-heading" class="tich-h1">Support Us</h1>
                    <p class="tich-text tich-mt-2">Partner with TICH Fund to expand community health education across Africa.</p>
                </header>

                <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                    <x-animated-card animation="left">
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
                    </x-animated-card>
                    <x-animated-card animation="right">
                        <aside class="tich-card" aria-labelledby="donate-heading">
                            <h2 id="donate-heading" class="tich-h3">Make a Donation</h2>
                            <p class="tich-text tich-mt-2">Support our mission and help us empower communities.</p>
                            <form class="tich-mt-4" method="post" action="#" aria-label="Donation form" onsubmit="event.preventDefault(); alert('Thank you for your generous donation. This is a demo - no payment was processed.');">
                                <div>
                                    <label class="tich-label" for="donation-amount">Donation Amount (KES)</label>
                                    <input id="donation-amount" type="number" class="tich-input" name="amount" min="1" placeholder="Enter amount">
                                </div>
                                <div class="tich-mt-4">
                                    <label class="tich-label" for="donation-name">Full Name</label>
                                    <input id="donation-name" type="text" class="tich-input" name="name" autocomplete="name">
                                </div>
                                <div class="tich-mt-4">
                                    <label class="tich-label" for="donation-email">Email</label>
                                    <input id="donation-email" type="email" class="tich-input" name="email" autocomplete="email">
                                </div>
                                <div class="tich-mt-4">
                                    <label class="tich-label" for="donation-message">Message (optional)</label>
                                    <textarea id="donation-message" class="tich-input" name="message" rows="3"></textarea>
                                </div>
                                <button type="submit" class="tich-btn tich-btn-success tich-mt-4">Donate Now</button>
                            </form>
                        </aside>
                    </x-animated-card>
                </div>
            </div>
        </section>
    </x-animated-section>
@endsection
