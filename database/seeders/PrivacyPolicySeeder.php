<?php

namespace Database\Seeders;

use App\Models\PrivacySetting;
use Illuminate\Database\Seeder;

/**
 * The privacy and cookie policy text.
 *
 * The policy is editable in the admin panel, but it is kept here as well so
 * the wording that matches the code is versioned alongside it, and so the same
 * text can be applied to another environment without retyping it. Running this
 * overwrites whatever the admin panel currently holds.
 */
class PrivacyPolicySeeder extends Seeder
{
    public function run(): void
    {
        $setting = PrivacySetting::current();

        $setting->update([
            'title' => 'Privacy & Cookie Policy',
            'content' => $this->content(),
        ]);
    }

    private function content(): string
    {
        return <<<'HTML'
<h2>Who We Are</h2>
<p>This website is operated by Masias, a Full-Stack Developer based in the United Kingdom. Masias is the data controller for any personal data collected through this website. Contact is available at: <a href="mailto:hello@masias.co.uk">hello@masias.co.uk</a></p>

<h2>What Data Is Collected</h2>
<p>When the contact form is used, the following data is collected:</p>
<ul>
<li><strong>Name</strong> — to provide a personalised response</li>
<li><strong>Email address</strong> — to reply to the message</li>
<li><strong>Message</strong> — the content of the enquiry</li>
</ul>
<p>If analytics cookies are accepted, Google Analytics also collects:</p>
<ul>
<li><strong>Pages viewed</strong> and the time spent on them</li>
<li><strong>Approximate location</strong> — country and city, derived from your IP address</li>
<li><strong>Device and browser type</strong></li>
<li><strong>Referring website</strong>, where one exists</li>
<li><strong>A randomly generated identifier</strong>, stored in a cookie, used to recognise repeat visits</li>
</ul>
<p>That identifier does not include a name or email address, but it is still personal data under UK GDPR, because it distinguishes one visitor from another. It is not combined with anything submitted through the contact form.</p>
<p>Nothing is collected by Google unless analytics cookies are accepted.</p>

<h2>Cookies</h2>
<h3>Strictly necessary</h3>
<p>These are required for the site to work and are set without consent, as permitted by the Privacy and Electronic Communications Regulations.</p>
<ul>
<li><strong>masias-session</strong> — maintains your session while browsing. Expires after 2 hours.</li>
<li><strong>XSRF-TOKEN</strong> — protects the contact form against cross-site request forgery. Expires after 2 hours.</li>
<li><strong>cookie_consent</strong> — remembers whether you accepted or rejected analytics, so you are not asked on every page. Expires after 6 months.</li>
</ul>

<h3>Analytics — only with consent</h3>
<p>Google Tag Manager and Google Analytics are <strong>not loaded at all</strong> until analytics cookies are accepted. Until then, no request is made to Google and none of the cookies below exist.</p>
<ul>
<li><strong>_ga</strong> — distinguishes visitors. Expires after 2 years.</li>
<li><strong>_ga_*</strong> — retains session state for this site's analytics property. Expires after 2 years.</li>
</ul>

<h3>Changing or withdrawing consent</h3>
<p>Your choice can be changed at any time using the <strong>Cookie Settings</strong> link in the footer of every page. Withdrawing consent deletes the Google Analytics cookies already stored on your device, reloads the page so that Google Analytics is no longer loaded, and stops any further collection.</p>
<p>The stored choice expires after 6 months, after which you will be asked again.</p>

<h2>How Data Is Used</h2>
<p>Data collected via the contact form is used solely to respond to enquiries. It will not be used for marketing purposes or shared with third parties for their own use.</p>
<p>Analytics data is used only to understand how the site is used in aggregate — which pages are read, and roughly where visitors are from. It is not used for advertising, profiling, or automated decision-making.</p>

<h2>Legal Basis for Processing</h2>
<ul>
<li><strong>Contact form</strong> — legitimate interests, specifically responding to messages sent through this website.</li>
<li><strong>Analytics cookies</strong> — consent. Nothing is stored or collected until you accept, and consent can be withdrawn at any time without affecting anything processed beforehand.</li>
<li><strong>Strictly necessary cookies</strong> — legitimate interests in providing a functioning, secure website.</li>
</ul>

<h2>How Long Data Is Kept</h2>
<ul>
<li><strong>Contact form submissions</strong> — retained until deletion is requested.</li>
<li><strong>Google Analytics data</strong> — retained by Google according to this site's configured retention period, after which visitor-level records are deleted automatically.</li>
<li><strong>Aggregate statistics</strong> — totals such as visitor counts per month and visits per country are copied to this site's own database and kept indefinitely. These are counts only. They contain no identifiers and cannot be traced back to an individual.</li>
</ul>
<p>To request deletion of contact form data, please email <a href="mailto:hello@masias.co.uk">hello@masias.co.uk</a> and it will be removed promptly.</p>

<h2>Third Parties</h2>
<p>Contact form submissions are transmitted via an SMTP email service to deliver email notifications. This service acts as a data processor and handles data only as necessary to deliver those emails.</p>
<p>If analytics cookies are accepted, Google Tag Manager and Google Analytics are loaded, and Google processes the data described above on this site's behalf. Google may process that data outside the United Kingdom, under the transfer safeguards set out in its own terms. For more information, see <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google's Privacy Policy</a>.</p>

<h2>Your Rights Under UK GDPR</h2>
<p>The following rights apply regarding personal data held by this website:</p>
<ul>
<li><strong>Right of access</strong> — a copy of the personal data held can be requested</li>
<li><strong>Right to rectification</strong> — correction of inaccurate data can be requested</li>
<li><strong>Right to erasure</strong> — deletion of personal data can be requested</li>
<li><strong>Right to restriction</strong> — restriction of data processing can be requested</li>
<li><strong>Right to object</strong> — processing based on legitimate interests can be objected to</li>
<li><strong>Right to withdraw consent</strong> — analytics consent can be withdrawn at any time via the Cookie Settings link in the footer</li>
</ul>
<p>To exercise any of these rights, please contact <a href="mailto:hello@masias.co.uk">hello@masias.co.uk</a>. All requests will be responded to within one calendar month.</p>
<p>If you are not satisfied with how data is handled, a complaint can be lodged with the Information Commissioner's Office (ICO) at <a href="https://ico.org.uk" target="_blank" rel="noopener noreferrer">ico.org.uk</a>.</p>
HTML;
    }
}
