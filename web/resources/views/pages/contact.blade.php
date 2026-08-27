@extends('layouts.app')

@section('title', 'Contact Us')
@section('meta_description', config('tich-seo.pages.contact.description'))

@section('content')
    <section class="tich-section tich-section--white" aria-labelledby="contact-heading">
        <div class="tich-container">
            <header class="tich-mb-8">
                <h1 id="contact-heading" class="tich-h1">Contact Us</h1>
                <p class="tich-text tich-mt-2">Reach TICH admissions, research, and general enquiries.</p>
            </header>

            <div class="tich-grid tich-grid--2" style="gap: 2rem; align-items: start;">
                <div>
                    <h2 class="tich-h2">Leave a message</h2>
                    <p class="tich-text tich-mt-4">Use the form below for general inquiries, and find direct contact details for our key departments.</p>

                    <form class="tich-mt-6" method="post" action="#" aria-label="Contact form" onsubmit="event.preventDefault(); alert('Thank you for reaching out. We will get back to you shortly.');">
                        <div class="tich-grid tich-grid--2">
                            <div>
                                <label class="tich-label" for="contact-name">Name</label>
                                <input id="contact-name" type="text" class="tich-input" name="name" autocomplete="name" required>
                            </div>
                            <div>
                                <label class="tich-label" for="contact-email">Email</label>
                                <input id="contact-email" type="email" class="tich-input" name="email" autocomplete="email" required>
                            </div>
                        </div>
                        <div class="tich-mt-4">
                            <label class="tich-label" for="contact-phone">Phone</label>
                            <input id="contact-phone" type="tel" class="tich-input" name="phone" autocomplete="tel">
                        </div>
                        <div class="tich-mt-4">
                            <label class="tich-label" for="contact-message">Message</label>
                            <textarea id="contact-message" class="tich-input" name="message" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Send Message</button>
                    </form>
                </div>
                <aside aria-labelledby="contact-info-heading">
                    <div class="tich-card tich-mb-6">
                        <h2 id="contact-info-heading" class="tich-h3">Contact Information</h2>

                        <section class="tich-mt-4" aria-labelledby="contact-admissions-heading">
                            <h3 id="contact-admissions-heading" class="tich-h4">Admissions</h3>
                            <p class="tich-caption tich-mt-2">Address</p>
                            <p class="tich-text">Milimani Kisumu, Nyanza Province, Kenya</p>
                            <p class="tich-caption tich-mt-2">Email</p>
                            <p class="tich-text"><a class="tich-link" href="mailto:info@tichinafrica.org">info@tichinafrica.org</a></p>
                            <p class="tich-caption tich-mt-2">Phone</p>
                            <p class="tich-text"><a class="tich-link" href="tel:+254743964736">+254 743 964 736</a></p>
                        </section>

                        <section class="tich-mt-6" aria-labelledby="contact-research-heading">
                            <h3 id="contact-research-heading" class="tich-h4">Research</h3>
                            <p class="tich-caption tich-mt-2">Address</p>
                            <p class="tich-text">Milimani Kisumu, Nyanza Province, Kenya</p>
                            <p class="tich-caption tich-mt-2">Email</p>
                            <p class="tich-text"><a class="tich-link" href="mailto:info@tichinafrica.org">info@tichinafrica.org</a></p>
                            <p class="tich-caption tich-mt-2">Phone</p>
                            <p class="tich-text"><a class="tich-link" href="tel:+254743964736">+254 743 964 736</a></p>
                        </section>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection

@section('seo_jsonld')
    @include('partials.seo-jsonld-organization')
    @php
        $contactPage = [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => 'Contact Us',
            'url' => route('contact'),
            'isPartOf' => ['@type' => 'WebSite', 'url' => url('/')],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($contactPage, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
