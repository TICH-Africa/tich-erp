<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

class KenyaDataProtectionNotice
{
    public static function policyVersion(): string
    {
        return (string) config('data-protection.policy_version', '1.0');
    }

    public static function htmlBody(): string
    {
        $controller = config('data-protection.controller.legal_name');
        $dpoEmail = e(config('data-protection.dpo.email', 'dpo@tich.africa'));
        $dpoName = e(config('data-protection.dpo.name') ?: 'Data Protection Officer');
        $odpc = e(config('data-protection.odpc.name', 'Office of the Data Protection Commissioner'));
        $odpcUrl = e(config('data-protection.odpc.website', 'https://www.odpc.go.ke'));
        $version = e(self::policyVersion());
        $dataRequestUrl = e(URL::route('privacy.data-request'));
        $odpcReg = config('data-protection.controller.odpc_registration_number');
        $regLine = $odpcReg !== ''
            ? '<p><strong>ODPC registration:</strong> '.e($odpcReg).'</p>'
            : '';

        $activitiesList = collect(config('data-protection.processing_activities', []))
            ->map(function (array $activity) {
                $cats = implode(', ', $activity['data_categories'] ?? []);

                return '<li><strong>'.e($activity['name']).'</strong> — '.e($activity['purposes'] ?? '').' (categories: '.$cats.').</li>';
            })
            ->implode("\n");

        return <<<HTML
<p>This Privacy Notice explains how <strong>{$controller}</strong> (“we”, “TICH”) collects, uses, stores, and protects personal data when you use our website, application portal, student information systems, and staff platforms. We process personal data in accordance with the <strong>Kenya Data Protection Act, 2019</strong> and regulations issued by the {$odpc}.</p>
{$regLine}
<h2>Data controller</h2>
<p>{$controller} is the data controller for personal data described in this notice.</p>
<h2>Data protection contact</h2>
<p>Contact our {$dpoName} at <a href="mailto:{$dpoEmail}">{$dpoEmail}</a> for questions about this notice or to exercise your rights.</p>
<h2>What we collect</h2>
<p>Depending on your relationship with us, we may collect identity and contact details, education history, employment and payroll information, next-of-kin details, government identifiers (such as national ID or tax PIN where required), documents you upload, and technical data such as IP address when you use our services.</p>
<h2>Why we use your data</h2>
<p>We use personal data only for clear institutional purposes, including:</p>
<ul>
{$activitiesList}
</ul>
<p>We rely on lawful bases under the Act, including <strong>consent</strong> (where you tick agreement on applications), <strong>contract</strong> (enrolment and employment), <strong>legal obligation</strong> (statutory filings), and <strong>legitimate interests</strong> (secure operations) where appropriate.</p>
<h2>How long we keep data</h2>
<p>We retain personal data only as long as needed for the purpose collected, including periods required by Kenyan education, employment, and tax law. Retention schedules are recorded in our processing register.</p>
<h2>Who we share data with</h2>
<p>Access is limited to authorised staff and contracted processors who need the data to support teaching, administration, payroll, or IT services. We do not sell personal data.</p>
<h2>Security</h2>
<p>We apply technical and organisational measures including access controls, audit logging, and secure storage for credentials and uploaded documents.</p>
<h2>Your rights</h2>
<p>Subject to the Act, you may request:</p>
<ul>
<li>confirmation whether we process your data and access to a copy;</li>
<li>correction of inaccurate data;</li>
<li>erasure where applicable;</li>
<li>objection to certain processing;</li>
<li>withdrawal of consent where processing is consent-based (without affecting prior lawful processing).</li>
</ul>
<p>You may lodge a complaint with the <a href="{$odpcUrl}" target="_blank" rel="noopener noreferrer">{$odpc}</a>.</p>
<h2>Submit a data request</h2>
<p>Use the <a href="{$dataRequestUrl}">data subject request form</a> or email <a href="mailto:{$dpoEmail}">{$dpoEmail}</a>. We aim to respond within 30 days.</p>
<h2>Children</h2>
<p>Where applicants are minors, a parent or guardian should provide information and consent as required by law and institutional policy.</p>
<h2>Changes</h2>
<p>We may update this notice. The version below is current. Material changes will be published on this page.</p>
<p><em>Notice version: {$version} · Governed by the Kenya Data Protection Act, 2019.</em></p>
HTML;
    }
}
