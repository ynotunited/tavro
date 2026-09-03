import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Terms of Use | Tavro",
  description: "Tavro's Terms of Use — the legal agreement governing your use of our platform.",
};

export default function TermsPage() {
  return (
    <article className="prose-charcoal">
      <h1 className="text-4xl font-bold font-display text-charcoal-900 mb-2">Terms of Use</h1>
      <p className="text-sm text-charcoal-500 mb-8">Last updated: August 26, 2026</p>

      <div className="space-y-8 text-charcoal-700 leading-relaxed">
        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">1. Agreement to Terms</h2>
          <p>
            These Terms of Use (&ldquo;Terms&rdquo;) constitute a legally binding agreement between you (&ldquo;you,&rdquo; &ldquo;your,&rdquo; or &ldquo;User&rdquo;) and Tavro Technologies (&ldquo;Tavro,&rdquo; &ldquo;we,&rdquo; &ldquo;us,&rdquo; or &ldquo;our&rdquo;). By accessing or using the Tavro restaurant management platform, website, APIs, and related services (collectively, the &ldquo;Service&rdquo;), you agree to be bound by these Terms.
          </p>
          <p>
            If you are using the Service on behalf of an organisation, you represent that you have authority to bind that organisation to these Terms.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">2. Description of Service</h2>
          <p>
            Tavro provides a cloud-based restaurant management platform including point-of-sale (POS), kitchen display system (KDS), inventory management, staff management, reporting, and payment processing. The Service is available as a subscription with multiple tiers as described on our pricing page.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">3. Account Registration</h2>
          <ul className="list-disc pl-6 space-y-2">
            <li>You must provide accurate, complete information during registration.</li>
            <li>You are responsible for safeguarding your account credentials.</li>
            <li>You must notify us immediately of any unauthorised access.</li>
            <li>One account per person; accounts are non-transferable without written consent.</li>
            <li>You must be at least 18 years old to create an account.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">4. Subscriptions &amp; Payment</h2>
          <ul className="list-disc pl-6 space-y-2">
            <li>Subscriptions are billed monthly in advance in Nigerian Naira (₦).</li>
            <li>Pricing and plan features are described on our website and may change with 30 days&rsquo; notice.</li>
            <li>All fees are non-refundable except as required by applicable law or as expressly stated in these Terms.</li>
            <li>Free trials convert to paid subscriptions at the end of the trial period unless cancelled.</li>
            <li>Failure to pay may result in suspension or termination of your account after a 7-day grace period.</li>
            <li>You may upgrade or downgrade at any time; changes take effect at the next billing cycle.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">5. Acceptable Use</h2>
          <p>You agree not to:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li>Use the Service for any unlawful purpose or in violation of any applicable regulation.</li>
            <li>Reverse engineer, decompile, or disassemble any part of the Service.</li>
            <li>Circumvent usage limits, authentication, or security measures.</li>
            <li>Upload malicious code, viruses, or harmful data.</li>
            <li>Attempt to gain unauthorised access to other users&rsquo; accounts or our infrastructure.</li>
            <li>Resell, sublicense, or redistribute the Service without written permission.</li>
            <li>Use automated scripts, bots, or scrapers to access the Service.</li>
            <li>Misrepresent your identity or affiliation.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">6. Intellectual Property</h2>
          <p>
            The Service, including all software, design, text, graphics, logos, and documentation, is the exclusive property of Tavro Technologies and is protected by Nigerian and international intellectual property laws. These Terms grant you a limited, non-exclusive, non-transferable, revocable licence to use the Service for your internal business purposes during your subscription term.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">7. Your Data</h2>
          <ul className="list-disc pl-6 space-y-2">
            <li>You retain ownership of all data you input into the Service (&ldquo;Your Data&rdquo;).</li>
            <li>You grant Tavro a limited licence to host, process, and display Your Data solely to provide the Service.</li>
            <li>We will not access, use, or disclose Your Data except as necessary to provide the Service or as required by law.</li>
            <li>Upon account deletion, we will delete Your Data within 90 days (subject to legal retention requirements).</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">8. Third-Party Integrations</h2>
          <p>
            The Service integrates with third-party providers (payment processors, SMS providers, etc.). Your use of these integrations is subject to their respective terms. We are not responsible for the availability, accuracy, or practices of third-party services.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">9. Service Availability &amp; SLA</h2>
          <p>
            We target 99.9% uptime measured monthly, excluding scheduled maintenance. We will provide at least 48 hours&rsquo; notice for planned maintenance. The Service may experience temporary interruptions due to circumstances beyond our reasonable control (force majeure, internet outages, etc.).
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">10. Limitation of Liability</h2>
          <p>
            To the maximum extent permitted by law, Tavro&rsquo;s total aggregate liability arising out of or relating to these Terms or the Service shall not exceed the total fees paid by you in the 12 months preceding the claim. We shall not be liable for indirect, incidental, special, consequential, or punitive damages, or any loss of profits, revenue, data, or business opportunity.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">11. Warranty Disclaimer</h2>
          <p>
            The Service is provided &ldquo;as is&rdquo; and &ldquo;as available&rdquo; without warranties of any kind, whether express or implied, including but not limited to implied warranties of merchantability, fitness for a particular purpose, and non-infringement. We do not warrant that the Service will be uninterrupted, error-free, or completely secure.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">12. Termination</h2>
          <ul className="list-disc pl-6 space-y-2">
            <li>Either party may terminate with 30 days&rsquo; written notice.</li>
            <li>We may suspend or terminate immediately for material breach, non-payment, or legal compliance.</li>
            <li>Upon termination, your right to use the Service ceases immediately.</li>
            <li>Sections 6, 7, 10, 11, and 14 survive termination.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">13. Dispute Resolution</h2>
          <p>
            Any dispute arising from these Terms shall first be resolved through good-faith negotiation within 30 days. If unresolved, disputes shall be submitted to binding arbitration under the Arbitration and Mediation Act of Nigeria (AMA 2023) held in Lagos, Nigeria. Either party may seek injunctive relief in court for intellectual property matters.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">14. General Provisions</h2>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>Governing Law:</strong> These Terms are governed by the laws of the Federal Republic of Nigeria.</li>
            <li><strong>Entire Agreement:</strong> These Terms, together with our Privacy Policy, constitute the entire agreement.</li>
            <li><strong>Severability:</strong> If any provision is found unenforceable, the remaining provisions remain in effect.</li>
            <li><strong>Waiver:</strong> Failure to enforce any right does not constitute a waiver.</li>
            <li><strong>Assignment:</strong> You may not assign these Terms without our written consent.</li>
            <li><strong>Notices:</strong> We will send notices to the email associated with your account. You may send notices to legal@tavro.ng.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">15. Contact Us</h2>
          <ul className="list-disc pl-6 space-y-1">
            <li>Email: <a href="mailto:legal@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">legal@tavro.ng</a></li>
            <li>Website: <a href="https://tavro.ng" className="text-amber-600 hover:text-amber-700 underline">https://tavro.ng</a></li>
          </ul>
        </section>
      </div>
    </article>
  );
}
