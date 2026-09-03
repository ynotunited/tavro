import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Privacy Policy | Tavro",
  description: "Tavro's Privacy Policy — how we collect, use, protect, and share your data.",
};

export default function PrivacyPage() {
  return (
    <article className="prose-charcoal">
      <h1 className="text-4xl font-bold font-display text-charcoal-900 mb-2">Privacy Policy</h1>
      <p className="text-sm text-charcoal-500 mb-8">Last updated: August 26, 2026</p>

      <div className="space-y-8 text-charcoal-700 leading-relaxed">
        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">1. Introduction</h2>
          <p>
            Tavro Technologies (&ldquo;Tavro,&rdquo; &ldquo;we,&rdquo; &ldquo;us,&rdquo; or &ldquo;our&rdquo;) operates the Tavro restaurant management platform (the &ldquo;Service&rdquo;). This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our Service, website, and related services.
          </p>
          <p>
            By accessing or using the Service, you agree to the collection and use of information in accordance with this Privacy Policy. If you do not agree, please discontinue use immediately.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">2. Information We Collect</h2>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">2.1 Information You Provide</h3>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>Account Information:</strong> Name, email address, phone number, business name, and role when you register.</li>
            <li><strong>Business Information:</strong> Branch locations, menu items, pricing, staff assignments, and operational data you input into the Service.</li>
            <li><strong>Payment Information:</strong> Transaction records, payment method identifiers, and billing details processed through integrated payment providers (Paystack, Flutterwave). We do not store full card numbers on our servers.</li>
            <li><strong>Communications:</strong> Support requests, feedback, and correspondence you send to us.</li>
          </ul>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">2.2 Information Collected Automatically</h3>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>Device Information:</strong> Browser type, operating system, device identifiers, and screen resolution.</li>
            <li><strong>Usage Data:</strong> Pages visited, features used, timestamps, session duration, and interaction patterns within the Service.</li>
            <li><strong>Log Data:</strong> IP addresses, access times, referring URLs, and error logs.</li>
            <li><strong>Offline Data:</strong> When using offline mode, transaction data is stored locally on your device and synced when connectivity is restored.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">3. How We Use Your Information</h2>
          <ul className="list-disc pl-6 space-y-2">
            <li>To provide, maintain, and improve the Service.</li>
            <li>To process transactions and send related information (receipts, invoices, payment confirmations).</li>
            <li>To send administrative notifications (service updates, security alerts, support messages).</li>
            <li>To detect, prevent, and address fraud, abuse, and technical issues.</li>
            <li>To generate aggregated, anonymised analytics about how the Service is used.</li>
            <li>To comply with legal obligations and enforce our terms.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">4. How We Share Your Information</h2>
          <p>We do not sell your personal data. We may share information with:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>Service Providers:</strong> Third-party vendors who perform services on our behalf (hosting, payment processing, analytics, email delivery). They access information only as needed to perform their obligations and are bound by confidentiality agreements.</li>
            <li><strong>Payment Processors:</strong> Paystack and Flutterwave process payment data subject to their own privacy policies. We receive only transaction references and status, not full card details.</li>
            <li><strong>Legal Requirements:</strong> When required by law, court order, or governmental regulation, or to protect the rights, property, or safety of Tavro, our users, or the public.</li>
            <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets, with notice to affected users.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">5. Data Security</h2>
          <p>
            We implement industry-standard security measures including TLS encryption in transit, AES-256 encryption at rest, role-based access controls, and regular security audits. However, no method of electronic transmission or storage is 100% secure. We cannot guarantee absolute security but will promptly notify affected users in the event of a data breach as required by applicable law.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">6. Data Retention</h2>
          <p>
            We retain your information for as long as your account is active or as needed to provide the Service. We will also retain data as necessary to comply with legal obligations, resolve disputes, and enforce our agreements. Upon account deletion, we will delete or anonymise personal data within 90 days, except where retention is required by law.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">7. Your Rights</h2>
          <p>Depending on your jurisdiction, you may have the right to:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li>Access, correct, or delete your personal data.</li>
            <li>Port your data to another service.</li>
            <li>Object to or restrict certain processing.</li>
            <li>Withdraw consent at any time (where processing is based on consent).</li>
            <li>Lodge a complaint with a supervisory authority.</li>
          </ul>
          <p className="mt-3">
            To exercise these rights, contact us at <a href="mailto:privacy@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">privacy@tavro.ng</a>.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">8. Nigeria Data Protection Regulation (NDPR)</h2>
          <p>
            Tavro complies with the Nigeria Data Protection Regulation (NDPR) and the Nigeria Data Protection Act 2023 (NDPA). We process personal data on a lawful basis — consent, contractual necessity, legitimate interest, or legal obligation — and maintain appropriate technical and organisational measures to protect data.
          </p>
          <p>
            Our Data Protection Officer can be reached at <a href="mailto:dpo@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">dpo@tavro.ng</a>.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">9. International Transfers</h2>
          <p>
            Your data may be processed in countries outside Nigeria where our infrastructure providers operate. We ensure appropriate safeguards are in place, including standard contractual clauses where required, to protect your data during international transfers.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">10. Children&rsquo;s Privacy</h2>
          <p>
            The Service is not intended for individuals under 18. We do not knowingly collect personal data from children. If you believe a child has provided us with personal data, please contact us and we will delete it.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">11. Changes to This Policy</h2>
          <p>
            We may update this Privacy Policy from time to time. We will notify you of material changes by posting the new policy on this page and updating the &ldquo;Last updated&rdquo; date. Your continued use of the Service after changes take effect constitutes acceptance of the revised policy.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">12. Contact Us</h2>
          <p>For questions about this Privacy Policy:</p>
          <ul className="list-disc pl-6 space-y-1">
            <li>Email: <a href="mailto:privacy@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">privacy@tavro.ng</a></li>
            <li>Data Protection Officer: <a href="mailto:dpo@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">dpo@tavro.ng</a></li>
            <li>Website: <a href="https://tavro.ng" className="text-amber-600 hover:text-amber-700 underline">https://tavro.ng</a></li>
          </ul>
        </section>
      </div>
    </article>
  );
}
