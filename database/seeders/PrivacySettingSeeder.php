<?php

namespace Database\Seeders;

use App\Models\PrivacySetting;
use Illuminate\Database\Seeder;

class PrivacySettingSeeder extends Seeder
{
    public function run(): void
    {
        PrivacySetting::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Privacy & Cookie Policy',
                'content' => '<h2>Who We Are</h2>
<p>This website is operated by Masias, a Full-Stack Developer based in the United Kingdom. Masias is the data controller for any personal data collected through this website. Contact is available at: <a href="mailto:hello@masias.co.uk">hello@masias.co.uk</a></p>

<h2>What Data Is Collected</h2>
<p>When the contact form is used, the following data is collected:</p>
<ul>
<li><strong>Name</strong> — to provide a personalised response</li>
<li><strong>Email address</strong> — to reply to the message</li>
<li><strong>Message</strong> — the content of the enquiry</li>
</ul>
<p>No other personal data is collected through this website.</p>

<h2>How Data Is Used</h2>
<p>Data collected via the contact form is used solely to respond to enquiries. It will not be used for marketing purposes or shared with third parties for their own use.</p>

<h2>Legal Basis for Processing</h2>
<p>Personal data is processed on the basis of <strong>legitimate interests</strong> — specifically, to respond to messages sent through this website.</p>

<h2>How Long Data Is Kept</h2>
<p>Contact form submissions are retained until deletion is requested. To request deletion, please email <a href="mailto:hello@masias.co.uk">hello@masias.co.uk</a> and it will be removed promptly.</p>

<h2>Third Parties</h2>
<p>Contact form submissions are transmitted via an SMTP email service to deliver email notifications. This service acts as a data processor and handles data only as necessary to deliver those emails.</p>
<p>This website uses Google Tag Manager (GTM) to manage analytics scripts. If analytics cookies are accepted, Google Analytics may be loaded via GTM to collect anonymised data about how the site is used — such as pages visited and time spent. No personally identifiable information is stored. Google acts as a data processor for this purpose. For more information, see <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google\'s Privacy Policy</a>.</p>

<h2>Your Rights Under UK GDPR</h2>
<p>The following rights apply regarding personal data held by this website:</p>
<ul>
<li><strong>Right of access</strong> — a copy of the personal data held can be requested</li>
<li><strong>Right to rectification</strong> — correction of inaccurate data can be requested</li>
<li><strong>Right to erasure</strong> — deletion of personal data can be requested</li>
<li><strong>Right to restriction</strong> — restriction of data processing can be requested</li>
<li><strong>Right to object</strong> — processing based on legitimate interests can be objected to</li>
</ul>
<p>To exercise any of these rights, please contact <a href="mailto:hello@masias.co.uk">hello@masias.co.uk</a>. All requests will be responded to within one calendar month.</p>
<p>If you are not satisfied with how data is handled, a complaint can be lodged with the Information Commissioner\'s Office (ICO) at <a href="https://ico.org.uk" target="_blank" rel="noopener noreferrer">ico.org.uk</a>.</p>

<h2>Cookies</h2>
<p>This website uses two categories of cookies:</p>

<h3>Essential Cookies (always active)</h3>
<p>These are strictly necessary for the site to function and do not require consent:</p>
<ul>
<li><strong>Session cookie</strong> — maintains session state while browsing (deleted when the browser is closed)</li>
<li><strong>CSRF token cookie</strong> — protects against cross-site request forgery attacks when submitting forms</li>
</ul>

<h3>Analytics Cookies (require consent)</h3>
<p>These are only loaded if analytics cookies are accepted via the cookie banner:</p>
<ul>
<li><strong>_ga</strong> — used by Google Analytics to distinguish users (expires after 2 years)</li>
<li><strong>_ga_*</strong> — used by Google Analytics to maintain session state (expires after 2 years)</li>
</ul>
<p>Analytics cookies help understand how the site is used. No personally identifiable information is stored. Cookie preferences can be changed at any time by clearing cookies in your browser settings.</p>

<h2>Changes to This Policy</h2>
<p>This policy may be updated from time to time. The "last updated" date at the top of this page will always reflect the most recent revision. Continued use of this website after any changes constitutes acceptance of the updated policy.</p>',
            ]
        );
    }
}
