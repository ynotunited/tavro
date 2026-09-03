import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Acceptable Use Policy | Tavro",
  description: "Tavro's Acceptable Use Policy — rules and guidelines for using our platform.",
};

export default function AcceptableUsePage() {
  return (
    <article className="prose-charcoal">
      <h1 className="text-4xl font-bold font-display text-charcoal-900 mb-2">Acceptable Use Policy</h1>
      <p className="text-sm text-charcoal-500 mb-8">Last updated: August 26, 2026</p>

      <div className="space-y-8 text-charcoal-700 leading-relaxed">
        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">1. Purpose</h2>
          <p>
            This Acceptable Use Policy (&ldquo;AUP&rdquo;) defines the rules and guidelines for using the Tavro platform. It supplements our Terms of Use and applies to all users of the Service. By using Tavro, you agree to comply with this policy.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">2. Permitted Use</h2>
          <p>Tavro is designed for legitimate restaurant and hospitality business operations, including:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li>Point-of-sale transactions and order management.</li>
            <li>Menu and inventory management.</li>
            <li>Staff scheduling, shift management, and performance tracking.</li>
            <li>Financial reporting and reconciliation.</li>
            <li>Customer service and order fulfilment.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">3. Prohibited Activities</h2>
          <p>You must not use the Service to:</p>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">3.1 Illegal Activities</h3>
          <ul className="list-disc pl-6 space-y-2">
            <li>Process transactions for illegal goods or services.</li>
            <li>Launder money or facilitate financial crimes.</li>
            <li>Evade taxes or manipulate financial records for regulatory evasion.</li>
            <li>Violate sanctions, export controls, or anti-corruption laws.</li>
            <li>Engage in any activity that violates Nigerian law or the laws of your jurisdiction.</li>
          </ul>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">3.2 Platform Abuse</h3>
          <ul className="list-disc pl-6 space-y-2">
            <li>Create fake accounts or impersonate other businesses.</li>
            <li>Manipulate reports, sales data, or financial records to deceive stakeholders.</li>
            <li>Circumvent subscription limits (e.g., creating multiple accounts to avoid fees).</li>
            <li>Use the Service to send unsolicited communications (spam).</li>
            <li>Interfere with or disrupt the Service, servers, or networks.</li>
            <li>Attempt to access other users&rsquo; accounts, data, or systems without authorization.</li>
          </ul>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">3.3 Technical Abuse</h3>
          <ul className="list-disc pl-6 space-y-2">
            <li>Reverse engineer, decompile, or disassemble any part of the Service.</li>
            <li>Bypass rate limits, authentication mechanisms, or security controls.</li>
            <li>Use automated tools (bots, scripts, crawlers) to access the Service without written permission.</li>
            <li>Introduce malware, viruses, or harmful code.</li>
            <li>Attempt penetration testing or vulnerability scanning without authorisation.</li>
            <li>Interfere with other users&rsquo; access to the Service.</li>
          </ul>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">3.4 Data Misuse</h3>
          <ul className="list-disc pl-6 space-y-2">
            <li>Scrape, harvest, or collect user data without consent.</li>
            <li>Share or sell staff or customer data obtained through the Service.</li>
            <li>Use the Service to profile or discriminate against individuals.</li>
            <li>Transfer data to jurisdictions with inadequate data protection without proper safeguards.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">4. Content Standards</h2>
          <p>If the Service allows you to upload, display, or transmit content (menu items, images, descriptions, etc.), you must ensure that content:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li>Is accurate and not misleading.</li>
            <li>Does not infringe the intellectual property rights of others.</li>
            <li>Does not contain defamatory, obscene, or offensive material.</li>
            <li>Does not promote violence, discrimination, or illegal activity.</li>
            <li>Complies with food safety advertising regulations (NAFDAC, SON).</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">5. Staff &amp; Employee Conduct</h2>
          <p>
            Restaurant owners are responsible for ensuring that all staff members with Tavro accounts comply with this policy. Owners should:
          </p>
          <ul className="list-disc pl-6 space-y-2">
            <li>Provide adequate training on proper use of the Service.</li>
            <li>Remove access for terminated employees promptly.</li>
            <li>Monitor staff activity through Tavro&rsquo;s audit log features.</li>
            <li>Report any suspicious or unauthorised activity to Tavro support.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">6. Enforcement</h2>
          <p>We enforce this policy through:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>Warning:</strong> First-time minor violations may result in a written warning.</li>
            <li><strong>Account Suspension:</strong> Repeated or serious violations may result in temporary suspension of your account.</li>
            <li><strong>Account Termination:</strong> Severe violations or persistent non-compliance may result in permanent termination.</li>
            <li><strong>Legal Action:</strong> We may pursue legal remedies for violations that cause harm or violate applicable law.</li>
          </ul>
          <p className="mt-3">
            We may also report illegal activity to law enforcement authorities. We reserve the right to take any action we deem necessary to protect the Service, our users, and the public.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">7. Reporting Violations</h2>
          <p>
            If you become aware of any violation of this Acceptable Use Policy, please report it to us immediately:
          </p>
          <ul className="list-disc pl-6 space-y-1">
            <li>Email: <a href="mailto:abuse@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">abuse@tavro.ng</a></li>
            <li>Security: <a href="mailto:security@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">security@tavro.ng</a></li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">8. Modifications</h2>
          <p>
            We may update this Acceptable Use Policy from time to time. Material changes will be communicated via email or in-app notification. Continued use of the Service after changes take effect constitutes acceptance of the updated policy.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">9. Contact</h2>
          <ul className="list-disc pl-6 space-y-1">
            <li>Abuse Reports: <a href="mailto:abuse@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">abuse@tavro.ng</a></li>
            <li>Legal: <a href="mailto:legal@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">legal@tavro.ng</a></li>
          </ul>
        </section>
      </div>
    </article>
  );
}
